<?php
session_start();
require_once 'config.php';
require_once 'includes/security.php';

function detectClientMeta(string $userAgent): array {
    $platform = 'Неизвестно';
    $browser = 'Браузер';
    $device = 'Устройство';

    $ua = strtolower($userAgent);
    if (strpos($ua, 'windows') !== false) {
        $platform = 'Windows';
    } elseif (strpos($ua, 'mac os') !== false || strpos($ua, 'macintosh') !== false) {
        $platform = 'macOS';
    } elseif (strpos($ua, 'android') !== false) {
        $platform = 'Android';
    } elseif (strpos($ua, 'iphone') !== false || strpos($ua, 'ipad') !== false) {
        $platform = 'iOS';
    } elseif (strpos($ua, 'linux') !== false) {
        $platform = 'Linux';
    }

    if (strpos($ua, 'chrome') !== false && strpos($ua, 'edge') === false) {
        $browser = 'Chrome';
    } elseif (strpos($ua, 'safari') !== false && strpos($ua, 'chrome') === false) {
        $browser = 'Safari';
    } elseif (strpos($ua, 'firefox') !== false) {
        $browser = 'Firefox';
    } elseif (strpos($ua, 'edge') !== false) {
        $browser = 'Edge';
    } elseif (strpos($ua, 'opera') !== false || strpos($ua, 'opr') !== false) {
        $browser = 'Opera';
    }

    if (strpos($ua, 'mobile') !== false || strpos($ua, 'android') !== false || strpos($ua, 'iphone') !== false) {
        $device = 'Мобильное';
    } else {
        $device = 'Desktop';
    }

    return [$platform, $browser, $device];
}

function detectIpLocation(string $ip): string {
    if (empty($ip) || in_array($ip, ['127.0.0.1', '::1'], true)) {
        return 'Локальная сеть';
    }

    $parts = [];
    $server = $_SERVER;

    if (!empty($server['GEOIP_CITY'])) {
        $parts[] = $server['GEOIP_CITY'];
    }
    if (!empty($server['GEOIP_COUNTRY_NAME'])) {
        $parts[] = $server['GEOIP_COUNTRY_NAME'];
    } elseif (!empty($server['HTTP_CF_IPCOUNTRY'])) {
        $parts[] = $server['HTTP_CF_IPCOUNTRY'];
    }

    if (!empty($parts)) {
        return implode(', ', $parts);
    }

    $url = 'http://ip-api.com/json/' . urlencode($ip) . '?fields=status,country,regionName,city&lang=ru';
    $response = null;

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 2,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
    } else {
        $context = stream_context_create([
            'http' => [
                'timeout' => 2,
            ]
        ]);
        $response = @file_get_contents($url, false, $context);
    }

    if ($response) {
        $payload = json_decode($response, true);
        if (($payload['status'] ?? '') === 'success') {
            $city = $payload['city'] ?? '';
            $region = $payload['regionName'] ?? '';
            $country = $payload['country'] ?? '';
            $locationParts = array_filter([$city, $region, $country]);
            if (!empty($locationParts)) {
                return implode(', ', $locationParts);
            }
        }
    }

    return 'Не определено';
}

function logUserSession(PDO $pdo, int $userId): void {
    $sessionHash = session_id() ? hash('sha256', session_id()) : bin2hex(random_bytes(16));
    $ip = Security::getClientIp();
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    [$platform, $browser, $device] = detectClientMeta($userAgent);
    $location = detectIpLocation($ip);

    try {
        $stmt = $pdo->prepare("INSERT INTO user_sessions (user_id, session_hash, ip_address, ip_location, user_agent, platform, browser, device, created_at, last_seen) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE ip_address = VALUES(ip_address), ip_location = VALUES(ip_location), user_agent = VALUES(user_agent), platform = VALUES(platform), browser = VALUES(browser), device = VALUES(device), last_seen = NOW()");
        $stmt->execute([$userId, $sessionHash, $ip, $location, $userAgent, $platform, $browser, $device]);
    } catch (PDOException $e) {
        Security::logSecurityEvent('Session logging failed', ['user_id' => $userId, 'error' => $e->getMessage()]);
    }
}

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';
$csrfToken = Security::generateCSRFToken();

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !Security::verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Недействительный запрос. Попробуйте снова';
        Security::logSecurityEvent('CSRF token validation failed', ['action' => 'login']);
    } else {
        $identifier = trim($_POST['identifier'] ?? '');
        $password = $_POST['password'] ?? '';
        $rememberMe = isset($_POST['remember_me']);
        
        // Check rate limiting
        $rateLimit = Security::checkRateLimit('login_' . $_SERVER['REMOTE_ADDR']);
        
        if (is_array($rateLimit) && !$rateLimit['allowed']) {
            $error = "Слишком много попыток входа. Попробуйте через {$rateLimit['time_left']} минут";
            Security::logSecurityEvent('Rate limit exceeded', ['ip' => $_SERVER['REMOTE_ADDR'], 'action' => 'login']);
        } elseif (empty($identifier) || empty($password)) {
            $error = 'Пожалуйста, заполните все поля';
        } else {
            $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);
            $lookupValue = null;
            $query = '';

            if ($isEmail) {
                $email = Security::validateEmail($identifier);
                if (!$email) {
                    $error = 'Некорректный email или логин';
                } else {
                    $lookupValue = $email;
                    $query = "SELECT id, username, email, password, role, session_version FROM users WHERE email = ? LIMIT 1";
                }
            } else {
                $usernameCheck = Security::validateUsername($identifier);
                if ($usernameCheck !== true) {
                    $error = 'Некорректный email или логин';
                } else {
                    $lookupValue = $identifier;
                    $query = "SELECT id, username, email, password, role, session_version FROM users WHERE username = ? LIMIT 1";
                }
            }

            if (!$error) {
                try {
                    $stmt = $pdo->prepare($query);
                    $stmt->execute([$lookupValue]);
                    $user = $stmt->fetch();
                    
                    if ($user && Security::verifyPassword($password, $user['password'])) {
                        // Regenerate session ID to prevent session fixation
                        Security::regenerateSession();

                        // Centralized session bootstrap
                        Security::startUserSession($user);
                        logUserSession($pdo, $user['id']);

                        // Handle Remember Me
                        try {
                            if ($rememberMe) {
                                Security::rememberUser($pdo, $user['id']);
                            } else {
                                Security::forgetRememberMe($pdo, $user['id']);
                            }
                        } catch (PDOException $e) {
                            Security::logSecurityEvent('Remember-me persistence failed', [
                                'user_id' => $user['id'],
                                'error' => $e->getMessage()
                            ]);
                        }

                        // Check if password needs rehashing
                        if (Security::needsRehash($user['password'])) {
                            $newHash = Security::hashPassword($password);
                            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                            $stmt->execute([$newHash, $user['id']]);
                        }
                        
                        Security::logSecurityEvent('Successful login', ['user_id' => $user['id'], 'identifier' => $lookupValue]);
                        
                        header('Location: index.php');
                        exit;
                    } else {
                        $error = 'Неверный email/логин или пароль';
                        Security::logSecurityEvent('Failed login attempt', ['identifier' => $lookupValue]);
                    }
                } catch (PDOException $e) {
                    $error = 'Ошибка подключения к базе данных';
                    Security::logSecurityEvent('Database error on login', ['error' => $e->getMessage()]);
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в аккаунт - <?= SITE_NAME ?></title>
    <script>
        // Apply saved theme immediately to avoid flash when navigating directly to login page
        (function () {
            const storedTheme = localStorage.getItem('theme');
            const savedTheme = storedTheme || 'dark';
            if (!storedTheme) {
                localStorage.setItem('theme', savedTheme);
            }
            const root = document.documentElement;
            root.setAttribute('data-theme', savedTheme);
            root.style.backgroundColor = savedTheme === 'light' ? '#ffffff' : '#0f172a';
            root.style.colorScheme = savedTheme;
        })();
    </script>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/login.css">
    <script src="js/login.js" defer></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="login-page">
        <div class="login-container">
            <div class="login-header">
                <i class="fas fa-user-circle"></i>
                <h1>Добро пожаловать</h1>
                <p>Войдите или создайте новый аккаунт</p>
            </div>

            <div class="login-tabs">
                <button class="login-tab active" onclick="switchTab('login')">
                    <i class="fas fa-sign-in-alt"></i> Вход
                </button>
                <button class="login-tab" onclick="switchTab('register')">
                    <i class="fas fa-user-plus"></i> Регистрация
                </button>
            </div>

            <div class="login-content">
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <span><?= htmlspecialchars($success) ?></span>
                    </div>
                <?php endif; ?>

                <!-- Login Form -->
                <div id="login-form" class="tab-content active">
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        
                        <div class="form-group">
                            <label for="identifier">
                                <i class="fas fa-user"></i>
                                Email или логин
                            </label>
                            <input 
                                type="text" 
                                id="identifier" 
                                name="identifier" 
                                placeholder="your@email.com или username"
                                value="<?= htmlspecialchars($_POST['identifier'] ?? '') ?>"
                                maxlength="255"
                                required
                                autocomplete="username"
                            >
                        </div>

                        <div class="form-group">
                            <label for="password">
                                <i class="fas fa-lock"></i>
                                Пароль
                            </label>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                placeholder="••••••••"
                                required
                                autocomplete="current-password"
                            >
                        </div>

                        <div class="form-group">
                            <label class="checkbox-label">
                                <input 
                                    type="checkbox" 
                                    id="remember_me" 
                                    name="remember_me"
                                >
                                <span class="checkbox-text">Запомнить меня</span>
                            </label>
                        </div>

                        <button type="submit" name="login" class="btn-submit">
                            <i class="fas fa-sign-in-alt"></i> Войти
                        </button>

                        <div class="form-footer form-footer--links">
                            <a class="forgot-password-link" href="password-reset.php">Забыли пароль?</a>
                        </div>

                        <div class="form-footer">
                            Нет аккаунта? <a href="register.php">Зарегистрируйтесь</a>
                        </div>
                    </form>
                </div>

                <!-- Registration moved to separate page -->
                <div id="register-form" class="tab-content">
                    <div style="text-align: center; padding: 40px 20px;">
                        <i class="fas fa-user-plus" style="font-size: 48px; color: var(--primary); margin-bottom: 20px;"></i>
                        <h3 style="margin-bottom: 12px;">Регистрация нового аккаунта</h3>
                        <p style="color: var(--text-secondary); margin-bottom: 24px;">Для регистрации перейдите на отдельную страницу с расширенной защитой</p>
                        <a href="register.php" class="btn-submit" style="display: inline-block; text-decoration: none;">
                            <i class="fas fa-arrow-right"></i> Перейти к регистрации
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="js/main.js"></script>
</body>
</html>

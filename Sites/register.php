<?php
session_start();
require_once 'config.php';
require_once 'includes/security.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';
$csrfToken = Security::generateCSRFToken();

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !Security::verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Недействительный запрос. Попробуйте снова';
        Security::logSecurityEvent('CSRF token validation failed', ['action' => 'register']);
    } else {
        // Check rate limiting
        $rateLimit = Security::checkRateLimit('register_' . $_SERVER['REMOTE_ADDR'], 3, 3600);
        
        if (is_array($rateLimit) && !$rateLimit['allowed']) {
            $error = "Слишком много попыток регистрации. Попробуйте через {$rateLimit['time_left']} минут";
            Security::logSecurityEvent('Rate limit exceeded', ['ip' => $_SERVER['REMOTE_ADDR'], 'action' => 'register']);
        } else {
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            $agreeTerms = isset($_POST['agree_terms']);
            
            // Check consent
            if (!$agreeTerms) {
                $error = 'Вы должны согласиться с Политикой конфиденциальности и Условиями использования';
            } elseif (Security::detectSuspiciousActivity($username . $email)) {
                $error = 'Обнаружена подозрительная активность';
                Security::logSecurityEvent('Suspicious registration attempt', [
                    'username' => substr($username, 0, 50),
                    'email' => $email
                ]);
            } elseif (empty($username) || empty($email) || empty($password) || empty($confirmPassword)) {
                $error = 'Пожалуйста, заполните все поля';
            } else {
                // Validate username
                $usernameValidation = Security::validateUsername($username);
                if ($usernameValidation !== true) {
                    $error = $usernameValidation;
                } else {
                    // Validate email
                    $validatedEmail = Security::validateEmail($email);
                    if (!$validatedEmail) {
                        $error = 'Некорректный email адрес или использование одноразовых почтовых сервисов запрещено';
                    } else {
                        // Validate password
                        $passwordValidation = Security::validatePassword($password);
                        if ($passwordValidation !== true) {
                            $error = implode('<br>', $passwordValidation);
                        } elseif ($password !== $confirmPassword) {
                            $error = 'Пароли не совпадают';
                        } else {
                            try {
                                // Check if email already exists
                                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
                                $stmt->execute([$validatedEmail]);
                                if ($stmt->fetch()) {
                                    $error = 'Пользователь с таким email уже существует';
                                    Security::logSecurityEvent('Registration attempt with existing email', ['email' => $validatedEmail]);
                                } else {
                                    // Check if username already exists
                                    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
                                    $stmt->execute([$username]);
                                    if ($stmt->fetch()) {
                                        $error = 'Это имя пользователя уже занято';
                                    } else {
                                        // Create new user with secure password hashing
                                        $hashedPassword = Security::hashPassword($password);
                                        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, created_at) VALUES (?, ?, ?, NOW())");
                                        $stmt->execute([$username, $validatedEmail, $hashedPassword]);
                                        
                                        $userId = $pdo->lastInsertId();
                                        
                                        Security::logSecurityEvent('Successful registration', [
                                            'user_id' => $userId,
                                            'username' => $username,
                                            'email' => $validatedEmail
                                        ]);
                                        
                                        $success = 'Регистрация успешна! Перенаправление на страницу входа...';
                                        
                                        // Redirect to login after 2 seconds
                                        header("refresh:2;url=login.php");
                                    }
                                }
                            } catch (PDOException $e) {
                                $error = 'Ошибка регистрации. Попробуйте позже';
                                Security::logSecurityEvent('Database error on registration', ['error' => $e->getMessage()]);
                            }
                        }
                    }
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
    <title>Регистрация - <?= SITE_NAME ?></title>
    <script>
        // Apply saved theme immediately to avoid flash of incorrect styles
        (function() {
            try {
                const storedTheme = localStorage.getItem('theme');
                const savedTheme = storedTheme || 'dark';
                if (!storedTheme) {
                    localStorage.setItem('theme', savedTheme);
                }
                const root = document.documentElement;
                root.setAttribute('data-theme', savedTheme);
                root.style.backgroundColor = savedTheme === 'light' ? '#ffffff' : '#0f172a';
                root.style.colorScheme = savedTheme;
            } catch (e) {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        })();
    </script>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/register.css">
    <script src="js/register.js" defer></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="register-page">
        <div class="register-container">
            <div class="register-header">
                <i class="fas fa-user-plus"></i>
                <h1>Создать аккаунт</h1>
                <p>Присоединяйтесь к HyperPC</p>
            </div>

            <div class="register-content">
                <div class="security-badge">
                    <i class="fas fa-shield-halved"></i>
                    <span>Регистрация HyperPC с защитой персональных данных</span>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?= $error ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <span><?= htmlspecialchars($success) ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" id="registerForm">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    
                    <div class="form-group">
                        <label for="username">
                            <i class="fas fa-user"></i>
                            Имя пользователя
                        </label>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            placeholder="Введите ваше имя"
                            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                            minlength="3"
                            maxlength="50"
                            pattern="[a-zA-Z0-9_-]+"
                            required
                            autocomplete="username"
                        >
                        <div class="password-requirements">
                            От 3 до 50 символов. Только буквы, цифры, дефис и подчеркивание
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">
                            <i class="fas fa-envelope"></i>
                            Email
                        </label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            placeholder="your@email.com"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                            maxlength="255"
                            required
                            autocomplete="email"
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
                            autocomplete="new-password"
                            oninput="checkPasswordStrength()"
                        >
                        <div class="password-strength">
                            <div class="strength-bar">
                                <div class="strength-fill" id="strengthFill"></div>
                            </div>
                        </div>
                        <div class="password-requirements">
                            <strong>Требования к паролю:</strong>
                            <ul id="passwordReqs">
                                <li id="req-length"><i class="fas fa-circle"></i> Минимум 8 символов</li>
                                <li id="req-upper"><i class="fas fa-circle"></i> Заглавная буква</li>
                                <li id="req-lower"><i class="fas fa-circle"></i> Строчная буква</li>
                                <li id="req-number"><i class="fas fa-circle"></i> Цифра</li>
                            </ul>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">
                            <i class="fas fa-lock"></i>
                            Подтвердите пароль
                        </label>
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            placeholder="••••••••"
                            required
                            autocomplete="new-password"
                        >
                    </div>

                    <div class="form-group">
                        <label class="checkbox-label">
                            <input 
                                type="checkbox" 
                                id="agree_terms" 
                                name="agree_terms" 
                                required
                            >
                            <span class="checkbox-text">
                                Я согласен с 
                                <a href="privacy.php" target="_blank">Политикой конфиденциальности</a> 
                                и 
                                <a href="terms.php" target="_blank">Условиями использования</a>
                            </span>
                        </label>
                    </div>

                    <button type="submit" name="register" class="btn-submit">
                        <i class="fas fa-user-plus"></i> Зарегистрироваться
                    </button>

                    <div class="form-footer">
                        Уже есть аккаунт? <a href="login.php">Войдите</a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="js/main.js"></script>
</body>
</html>

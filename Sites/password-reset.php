<?php
session_start();
require_once 'config.php';
require_once 'includes/security.php';

ensurePasswordResetTable($pdo);

/**
 * NOTE: ensure you have a password_resets table similar to:
 * CREATE TABLE password_resets (
 *   id INT AUTO_INCREMENT PRIMARY KEY,
 *   user_id INT NOT NULL,
 *   token VARCHAR(128) NOT NULL UNIQUE,
 *   code_hash VARCHAR(255) NOT NULL,
 *   expires_at DATETIME NOT NULL,
 *   created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 *   FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
 * );
 */

$step = 'request';
$error = '';
$success = '';
$activeReset = null;

$incomingToken = $_GET['token'] ?? '';
if ($incomingToken) {
    $activeReset = fetchValidReset($pdo, $incomingToken);
    if ($activeReset) {
        $step = 'verify';
    } else {
        $error = 'Ссылка недействительна или истекла. Запросите код ещё раз.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['request_reset'])) {
        $email = trim($_POST['email'] ?? '');
        if (!Security::validateEmail($email)) {
            $error = 'Введите корректный email';
        } else {
            try {
                $stmt = $pdo->prepare('SELECT id, username FROM users WHERE email = ? LIMIT 1');
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                if (!$user) {
                    $error = 'Пользователь с таким email не найден';
                } else {
                    $code = random_int(1000, 9999);
                    $token = bin2hex(random_bytes(32));
                    $expiresAt = (new DateTime('+15 minutes'))->format('Y-m-d H:i:s');
                    $codeHash = password_hash((string)$code, PASSWORD_DEFAULT);

                    $pdo->prepare('DELETE FROM password_resets WHERE user_id = ?')->execute([$user['id']]);
                    $pdo->prepare('INSERT INTO password_resets (user_id, token, code_hash, expires_at) VALUES (?, ?, ?, ?)')
                        ->execute([$user['id'], $token, $codeHash, $expiresAt]);

                    sendResetCodeEmail($email, $user['username'] ?? 'пользователь', $code, $token);
                    $success = 'Код из 4 цифр отправлен на вашу почту. Он действует 15 минут.';
                    $step = 'verify';
                    $activeReset = fetchValidReset($pdo, $token);
                }
            } catch (PDOException $e) {
                Security::logSecurityEvent('Password reset request failed', ['error' => $e->getMessage(), 'email' => $email]);
                $error = 'Не удалось обработать запрос. Попробуйте позже.';
            }
        }
    }

    if (isset($_POST['reset_password'])) {
        $token = $_POST['token'] ?? '';
        $code = trim($_POST['code'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        $activeReset = fetchValidReset($pdo, $token);
        if (!$activeReset) {
            $error = 'Ссылка или токен устарели. Запросите новый код.';
            $step = 'request';
        } elseif (!ctype_digit($code) || strlen($code) !== 4) {
            $error = 'Введите 4-значный код';
            $step = 'verify';
        } elseif ($password !== $passwordConfirm) {
            $error = 'Пароли не совпадают';
            $step = 'verify';
        } elseif (!Security::validatePassword($password)) {
            $error = 'Пароль не соответствует требованиям безопасности';
            $step = 'verify';
        } elseif (!password_verify($code, $activeReset['code_hash'])) {
            $error = 'Неверный код из письма';
            $step = 'verify';
        } else {
            try {
                $hash = Security::hashPassword($password);
                $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([$hash, $activeReset['user_id']]);
                $pdo->prepare('DELETE FROM password_resets WHERE id = ?')->execute([$activeReset['id']]);
                $success = 'Пароль успешно обновлён. Теперь вы можете войти.';
                $step = 'request';
                $activeReset = null;
            } catch (PDOException $e) {
                Security::logSecurityEvent('Password reset apply failed', ['error' => $e->getMessage(), 'user_id' => $activeReset['user_id']]);
                $error = 'Ошибка при обновлении пароля. Попробуйте позже.';
                $step = 'verify';
            }
        }
    }
}

function fetchValidReset(PDO $pdo, string $token): ?array {
    if (!$token) {
        return null;
    }
    try {
        $stmt = $pdo->prepare('SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW() LIMIT 1');
        $stmt->execute([$token]);
        return $stmt->fetch() ?: null;
    } catch (PDOException $e) {
        Security::logSecurityEvent('fetchValidReset failed', ['token' => $token, 'error' => $e->getMessage()]);
        return null;
    }
}

function ensurePasswordResetTable(PDO $pdo): void {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token VARCHAR(128) NOT NULL UNIQUE,
            code_hash VARCHAR(255) NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } catch (PDOException $e) {
        Security::logSecurityEvent('password_resets table creation failed', ['error' => $e->getMessage()]);
    }
}

function sendResetCodeEmail(string $email, string $username, int $code, string $token): void {
    $subject = 'Восстановление пароля на JKT';
    $resetLink = SITE_URL . '/password-reset.php?token=' . urlencode($token);
    $body = "Здравствуйте, {$username}!\n\n"
        . "Вы запросили восстановление пароля. Ваш код: {$code}.\n"
        . "Он действует 15 минут. Чтобы продолжить, перейдите по ссылке: {$resetLink}\n\n"
        . "Если это были не вы — просто игнорируйте письмо.";

    // В продакшене интегрируйте почтовый сервис (SMTP/PHPMailer/Mailgun).
    // mail($email, $subject, $body);
    Security::logSecurityEvent('Password reset code sent', ['email' => $email, 'token' => $token]);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Восстановление пароля - <?= SITE_NAME ?></title>
    <script>
        (function() {
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
</head>
<body>
<?php include 'includes/header.php'; ?>
<main class="login-page">
    <div class="login-container">
        <div class="login-header">
            <i class="fas fa-unlock"></i>
            <h1>Восстановление доступа</h1>
            <p>Получите одноразовый код и задайте новый пароль</p>
        </div>

        <div class="login-content">
            <?php if ($error): ?>
                <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i><span><?= htmlspecialchars($error) ?></span></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i><span><?= htmlspecialchars($success) ?></span></div>
            <?php endif; ?>

            <?php if ($step === 'request'): ?>
                <form method="POST" class="password-reset-form">
                    <div class="form-group">
                        <label for="resetEmail"><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" id="resetEmail" name="email" placeholder="your@email.com" required autocomplete="email">
                    </div>
                    <button class="btn-submit" type="submit" name="request_reset"><i class="fas fa-paper-plane"></i> Получить код</button>
                </form>
            <?php else: ?>
                <form method="POST" class="password-reset-form">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($activeReset['token'] ?? ($incomingToken ?? '')) ?>">
                    <div class="form-group">
                        <label for="resetCode"><i class="fas fa-key"></i> Код из письма</label>
                        <input type="text" id="resetCode" name="code" maxlength="4" pattern="\d{4}" placeholder="1234" required>
                    </div>
                    <div class="form-group">
                        <label for="newPassword"><i class="fas fa-lock"></i> Новый пароль</label>
                        <input type="password" id="newPassword" name="password" placeholder="••••••••" required autocomplete="new-password">
                    </div>
                    <div class="form-group">
                        <label for="newPasswordConfirm"><i class="fas fa-lock"></i> Повторите пароль</label>
                        <input type="password" id="newPasswordConfirm" name="password_confirm" placeholder="••••••••" required autocomplete="new-password">
                    </div>
                    <button class="btn-submit" type="submit" name="reset_password"><i class="fas fa-check"></i> Сохранить пароль</button>
                    <div class="form-footer form-footer--links">
                        <a href="password-reset.php">Запросить новый код</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</main>
<?php include 'includes/footer.php'; ?>
<script src="js/main.js"></script>
</body>
</html>

<?php
/**
 * Security utilities for authentication and input validation
 */

class Security {
    private const REMEMBER_ME_LIFETIME = 30 * 24 * 60 * 60; // 30 days
    
    /**
     * Generate CSRF token
     */
    public static function generateCSRFToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verify CSRF token
     */
    public static function verifyCSRFToken($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Rate limiting for login attempts
     */
    public static function checkRateLimit($identifier, $maxAttempts = 5, $timeWindow = 900) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $key = 'rate_limit_' . md5($identifier);
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                'attempts' => 0,
                'first_attempt' => time()
            ];
        }
        
        $data = $_SESSION[$key];
        
        // Reset if time window has passed
        if (time() - $data['first_attempt'] > $timeWindow) {
            $_SESSION[$key] = [
                'attempts' => 1,
                'first_attempt' => time()
            ];
            return true;
        }
        
        // Check if exceeded max attempts
        if ($data['attempts'] >= $maxAttempts) {
            $timeLeft = $timeWindow - (time() - $data['first_attempt']);
            return [
                'allowed' => false,
                'time_left' => ceil($timeLeft / 60) // minutes
            ];
        }
        
        // Increment attempts
        $_SESSION[$key]['attempts']++;
        
        return true;
    }
    
    /**
     * Sanitize input to prevent XSS
     */
    public static function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeInput'], $input);
        }
        
        $input = trim($input);
        $input = stripslashes($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        
        return $input;
    }
    
    /**
     * Validate email format
     */
    public static function validateEmail($email) {
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        
        // Additional check for common disposable email domains
        $disposableDomains = ['tempmail.com', 'throwaway.email', '10minutemail.com'];
        $domain = substr(strrchr($email, "@"), 1);
        
        if (in_array(strtolower($domain), $disposableDomains)) {
            return false;
        }
        
        return $email;
    }
    
    /**
     * Validate password strength
     */
    public static function validatePassword($password) {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Пароль должен содержать минимум 8 символов';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Пароль должен содержать хотя бы одну заглавную букву';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Пароль должен содержать хотя бы одну строчную букву';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Пароль должен содержать хотя бы одну цифру';
        }
        
        // Check for common weak passwords
        $weakPasswords = ['password', '12345678', 'qwerty123', 'admin123'];
        if (in_array(strtolower($password), $weakPasswords)) {
            $errors[] = 'Этот пароль слишком простой';
        }
        
        return empty($errors) ? true : $errors;
    }
    
    /**
     * Validate username
     */
    public static function validateUsername($username) {
        $username = trim($username);
        
        if (strlen($username) < 3 || strlen($username) > 50) {
            return 'Имя пользователя должно быть от 3 до 50 символов';
        }
        
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
            return 'Имя пользователя может содержать только буквы, цифры, дефис и подчеркивание';
        }
        
        return true;
    }
    
    /**
     * Hash password securely
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
    }
    
    /**
     * Verify password
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Check if password needs rehashing
     */
    public static function needsRehash($hash) {
        return password_needs_rehash($hash, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
    }
    
    /**
     * Generate secure random token
     */
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }

    /**
     * Initialize authenticated session data
     */
    public static function startUserSession(array $user) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = self::sanitizeInput($user['username'] ?? '');
        $_SESSION['email'] = filter_var($user['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $_SESSION['user_role'] = $user['role'] ?? 'user';
        $_SESSION['login_time'] = time();
        $_SESSION['user_ip'] = self::getClientIp();
        $_SESSION['session_version'] = (int)($user['session_version'] ?? 1);
    }

    /**
     * Detect the real client IP, honoring proxy headers
     */
    public static function getClientIp(): string {
        $server = $_SERVER ?? [];
        $candidates = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        ];

        $firstValid = null;

        foreach ($candidates as $key) {
            if (empty($server[$key])) {
                continue;
            }

            $values = $key === 'HTTP_X_FORWARDED_FOR'
                ? array_map('trim', explode(',', $server[$key]))
                : [trim($server[$key])];

            foreach ($values as $ip) {
                if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                    continue;
                }

                if ($firstValid === null) {
                    $firstValid = $ip;
                }

                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $firstValid ?? 'unknown';
    }

    /**
     * Persist remember-me token and cookie
     */
    public static function rememberUser(PDO $pdo, int $userId) {
        $token = self::generateToken(32);
        $hashedToken = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', time() + self::REMEMBER_ME_LIFETIME);

        try {
            $stmt = $pdo->prepare("UPDATE users SET remember_token = ?, remember_token_expires = ? WHERE id = ?");
            $stmt->execute([$hashedToken, $expiresAt, $userId]);
        } catch (PDOException $e) {
            self::logSecurityEvent('Failed to store remember token', ['user_id' => $userId, 'error' => $e->getMessage()]);
            throw $e;
        }

        self::setRememberMeCookie($userId, $token);
    }

    /**
     * Remove remember-me token and cookie
     */
    public static function forgetRememberMe(?PDO $pdo = null, ?int $userId = null) {
        if ($pdo && $userId) {
            try {
                $stmt = $pdo->prepare("UPDATE users SET remember_token = NULL, remember_token_expires = NULL WHERE id = ?");
                $stmt->execute([$userId]);
            } catch (PDOException $e) {
                self::logSecurityEvent('Failed to clear remember token', ['user_id' => $userId, 'error' => $e->getMessage()]);
            }
        }

        self::clearRememberMeCookie();
    }

    /**
     * Auto-login user if remember-me cookie is present
     */
    public static function attemptAutoLogin(PDO $pdo) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['user_id']) || empty($_COOKIE['remember_token'])) {
            return;
        }

        $cookieParts = explode(':', $_COOKIE['remember_token'], 2);
        if (count($cookieParts) !== 2) {
            self::clearRememberMeCookie();
            return;
        }

        [$userId, $token] = $cookieParts;
        $userId = (int) $userId;

        if ($userId <= 0 || empty($token)) {
            self::clearRememberMeCookie();
            return;
        }

        try {
            $stmt = $pdo->prepare("SELECT id, username, email, remember_token, remember_token_expires FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
        } catch (PDOException $e) {
            self::logSecurityEvent('Auto login database error', ['error' => $e->getMessage()]);
            return;
        }

        if (!$user || empty($user['remember_token']) || empty($user['remember_token_expires'])) {
            self::forgetRememberMe($pdo, $userId);
            return;
        }

        if (strtotime($user['remember_token_expires']) < time()) {
            self::forgetRememberMe($pdo, $userId);
            return;
        }

        $hashedToken = hash('sha256', $token);
        if (!hash_equals($user['remember_token'], $hashedToken)) {
            self::forgetRememberMe($pdo, $userId);
            self::logSecurityEvent('Remember token mismatch', ['user_id' => $userId]);
            return;
        }

        self::regenerateSession();
        self::startUserSession($user);

        try {
            self::rememberUser($pdo, $user['id']); // Rotate token to prevent replay
        } catch (PDOException $e) {
            // Already logged in; token rotation failure is logged inside rememberUser
        }

        self::logSecurityEvent('Auto login via remember me', ['user_id' => $user['id']]);
    }

    /**
     * Ensure session_version matches current user record
     */
    public static function enforceSessionVersion(PDO $pdo) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['user_id'])) {
            return;
        }

        try {
            $stmt = $pdo->prepare("SELECT session_version FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$_SESSION['user_id']]);
            $serverVersion = (int)($stmt->fetchColumn() ?: 1);
            $currentVersion = (int)($_SESSION['session_version'] ?? 0);

            if ($currentVersion === 0) {
                $_SESSION['session_version'] = $serverVersion;
                return;
            }

            if ($serverVersion !== $currentVersion) {
                self::forgetRememberMe($pdo, $_SESSION['user_id']);
                session_unset();
                session_destroy();
                header('Location: login.php?session_expired=1');
                exit;
            }
        } catch (PDOException $e) {
            self::logSecurityEvent('Session version check failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Prevent session fixation
     */
    public static function regenerateSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        session_regenerate_id(true);
    }
    
    /**
     * Log security events
     */
    public static function logSecurityEvent($event, $details = []) {
        $logFile = __DIR__ . '/../logs/security.log';
        $logDir = dirname($logFile);
        
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $ip = self::getClientIp();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $logEntry = sprintf(
            "[%s] %s | IP: %s | UA: %s | Details: %s\n",
            $timestamp,
            $event,
            $ip,
            $userAgent,
            json_encode($details)
        );
        
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Check for suspicious activity
     */
    public static function detectSuspiciousActivity($input) {
        $suspiciousPatterns = [
            '/(\bor\b|\band\b).*?=.*?/i',  // SQL injection patterns
            '/<script.*?>/i',               // XSS patterns
            '/javascript:/i',               // JavaScript protocol
            '/on\w+\s*=/i',                 // Event handlers
            '/\.\.\//i',                    // Path traversal
            '/union.*?select/i',            // SQL UNION
            '/exec\s*\(/i',                 // Code execution
        ];
        
        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                self::logSecurityEvent('Suspicious input detected', [
                    'input' => substr($input, 0, 100),
                    'pattern' => $pattern
                ]);
                return true;
            }
        }
        
        return false;
    }

    /**
     * Set secure remember-me cookie
     */
    private static function setRememberMeCookie(int $userId, string $token) {
        $options = [
            'expires' => time() + self::REMEMBER_ME_LIFETIME,
            'path' => '/',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Strict'
        ];

        setcookie('remember_token', $userId . ':' . $token, $options);
        $_COOKIE['remember_token'] = $userId . ':' . $token;
    }

    /**
     * Clear remember-me cookie
     */
    private static function clearRememberMeCookie() {
        setcookie('remember_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Strict'
        ]);

        unset($_COOKIE['remember_token']);
    }
}

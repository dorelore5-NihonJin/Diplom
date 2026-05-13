<?php
// Set default timezone to UTC for all database operations
date_default_timezone_set('UTC');

// Autoload security helpers
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/timezone.php';

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'jkt_pc_builder');
define('DB_USER', 'root');
define('DB_PASS', '');

// Site configuration
define('SITE_NAME', 'HyperPC');
define('SITE_URL', 'http://localhost/HyperPC');
define('ENABLE_REMOTE_IMAGES', false);
define('UNSPLASH_ACCESS_KEY', 'XPLTRpRgmUDsUomMGPR-RvSnNwcLNuen_YBc2eKls_s');
define('CURRENCY', '₽');

// Database connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Attempt auto-login via remember-me cookie if session missing
Security::attemptAutoLogin($pdo);
Security::enforceSessionVersion($pdo);

// Helper functions
function formatPrice($price) {
    return number_format($price, 0, ',', ' ') . ' ' . CURRENCY;
}

function getSessionId() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    require_once 'config.php';
    require_once 'includes/security.php';
    if (!isset($_SESSION['build_id'])) {
        $_SESSION['build_id'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['build_id'];
}
?>

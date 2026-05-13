<?php
session_start();
require_once 'config.php';

$userId = $_SESSION['user_id'] ?? null;

// Destroy all session data
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Clear remember-me token and cookie
if (isset($pdo) && $userId) {
    Security::forgetRememberMe($pdo, (int)$userId);
} else {
    Security::forgetRememberMe();
}

// Redirect to home page
header('Location: index.php');
exit;

<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
    exit;
}

try {
    // Update user's last activity and set online status
    $stmt = $pdo->prepare("
        UPDATE users 
        SET last_activity = NOW(), is_online = 1 
        WHERE id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    
    // Mark users as offline if they haven't been active for 5 minutes
    $stmt = $pdo->prepare("
        UPDATE users 
        SET is_online = 0 
        WHERE last_activity < DATE_SUB(NOW(), INTERVAL 5 MINUTE) AND is_online = 1
    ");
    $stmt->execute();
    
    echo json_encode(['success' => true]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ошибка сервера']);
    error_log('Update activity error: ' . $e->getMessage());
}

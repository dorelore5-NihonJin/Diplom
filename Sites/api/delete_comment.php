<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Необходима авторизация']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['comment_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Неверные данные']);
    exit;
}

$commentId = intval($input['comment_id']);
$userId = $_SESSION['user_id'];

try {
    // Check if comment exists and belongs to user
    $stmt = $pdo->prepare("SELECT user_id FROM build_comments WHERE id = ?");
    $stmt->execute([$commentId]);
    $comment = $stmt->fetch();
    
    if (!$comment) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Комментарий не найден']);
        exit;
    }
    
    if ($comment['user_id'] != $userId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Нет прав на удаление']);
        exit;
    }
    
    // Delete comment (replies will be deleted automatically due to CASCADE)
    $stmt = $pdo->prepare("DELETE FROM build_comments WHERE id = ?");
    $stmt->execute([$commentId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Комментарий удалён'
    ]);
    
} catch (PDOException $e) {
    error_log("Error deleting comment: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Ошибка сервера']);
}

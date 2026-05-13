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

if (!$input || !isset($input['comment_id']) || !isset($input['comment'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Неверные данные']);
    exit;
}

$commentId = intval($input['comment_id']);
$comment = trim($input['comment']);
$userId = $_SESSION['user_id'];

// Validate comment
if (empty($comment)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Комментарий не может быть пустым']);
    exit;
}

if (strlen($comment) > 1000) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Комментарий слишком длинный (макс. 1000 символов)']);
    exit;
}

try {
    // Check if comment exists and belongs to user
    $stmt = $pdo->prepare("SELECT user_id FROM build_comments WHERE id = ?");
    $stmt->execute([$commentId]);
    $existingComment = $stmt->fetch();
    
    if (!$existingComment) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Комментарий не найден']);
        exit;
    }
    
    if ($existingComment['user_id'] != $userId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Нет прав на редактирование']);
        exit;
    }
    
    // Update comment
    $stmt = $pdo->prepare("UPDATE build_comments SET comment = ? WHERE id = ?");
    $stmt->execute([$comment, $commentId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Комментарий обновлён'
    ]);
    
} catch (PDOException $e) {
    error_log("Error editing comment: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Ошибка сервера']);
}

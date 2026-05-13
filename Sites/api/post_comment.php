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

if (!$input || !isset($input['build_id']) || !isset($input['comment'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Неверные данные']);
    exit;
}

$buildId = intval($input['build_id']);
$comment = trim($input['comment']);
$parentId = isset($input['parent_id']) ? intval($input['parent_id']) : null;
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
    // Check if build exists and is public or owned by user
    $stmt = $pdo->prepare("SELECT id FROM user_builds WHERE id = ? AND (is_public = 1 OR user_id = ?)");
    $stmt->execute([$buildId, $userId]);
    
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Сборка не найдена']);
        exit;
    }
    
    // Validate parent_id if provided
    if ($parentId) {
        $stmt = $pdo->prepare("SELECT id FROM build_comments WHERE id = ? AND build_id = ?");
        $stmt->execute([$parentId, $buildId]);
        if (!$stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Родительский комментарий не найден']);
            exit;
        }
    }
    
    // Insert comment
    $stmt = $pdo->prepare("INSERT INTO build_comments (build_id, user_id, comment, parent_id, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$buildId, $userId, $comment, $parentId]);
    
    $commentId = $pdo->lastInsertId();
    
    // Get user info for response
    $stmt = $pdo->prepare("SELECT username, avatar FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'message' => 'Комментарий добавлен',
        'comment' => [
            'id' => $commentId,
            'username' => $user['username'] ?? 'Пользователь',
            'avatar' => $user['avatar'] ?? null,
            'comment' => $comment,
            'parent_id' => $parentId,
            'created_at' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Error posting comment: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Ошибка сервера']);
}

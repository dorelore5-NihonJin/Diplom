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

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Метод не разрешен']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    $data = $_POST;
}

$orderId = isset($data['order_id']) ? (int)$data['order_id'] : 0;
$message = isset($data['message']) ? trim($data['message']) : '';

// Validate input
if (empty($orderId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID заказа не указан']);
    exit;
}

if (empty($message)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Сообщение не может быть пустым']);
    exit;
}

if (strlen($message) > 5000) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Сообщение слишком длинное (максимум 5000 символов)']);
    exit;
}

try {
    // Check if order belongs to user
    $stmt = $pdo->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$orderId, $_SESSION['user_id']]);
    
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Доступ запрещен']);
        exit;
    }
    
    // Insert message
    $stmt = $pdo->prepare("
        INSERT INTO support_messages (order_id, user_id, message, is_support, is_read, created_at)
        VALUES (?, ?, ?, 0, 1, NOW())
    ");
    
    $stmt->execute([$orderId, $_SESSION['user_id'], $message]);
    $messageId = $pdo->lastInsertId();
    
    // Get inserted message with user info
    $stmt = $pdo->prepare("
        SELECT sm.*, u.username, u.avatar, u.role
        FROM support_messages sm
        LEFT JOIN users u ON sm.user_id = u.id
        WHERE sm.id = ?
    ");
    $stmt->execute([$messageId]);
    $messageData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Format message for response
    $response = [
        'success' => true,
        'message' => 'Сообщение отправлено',
        'data' => [
            'id' => $messageData['id'],
            'message' => $messageData['message'],
            'username' => $messageData['username'] ?? $_SESSION['username'] ?? 'Вы',
            'avatar' => $messageData['avatar'],
            'is_support' => (bool)$messageData['is_support'],
            'created_at' => $messageData['created_at'],
            'formatted_time' => date('H:i', strtotime($messageData['created_at']))
        ]
    ];
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ошибка сервера. Попробуйте позже.']);
    error_log('Send message error: ' . $e->getMessage());
}

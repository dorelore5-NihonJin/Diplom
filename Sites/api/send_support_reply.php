<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

// Check if user is logged in and has support role
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
    exit;
}

// Check support role
try {
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user || !in_array($user['role'], ['support', 'admin', 'high-admin', 'owner'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Доступ запрещен']);
        exit;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ошибка сервера']);
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
    // Check if order exists and get user
    $stmt = $pdo->prepare("SELECT id, user_id, order_number FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Заказ не найден']);
        exit;
    }
    
    // Insert message as support message with support user_id for logging
    $stmt = $pdo->prepare("
        INSERT INTO support_messages (order_id, user_id, message, is_support, is_read, created_at)
        VALUES (?, ?, ?, 1, 0, NOW())
    ");
    
    $stmt->execute([$orderId, $_SESSION['user_id'], $message]);
    $messageId = $pdo->lastInsertId();

    // Create notification for customer
    if (!empty($order['user_id'])) {
        $title = 'Новое сообщение по заказу #' . htmlspecialchars($order['order_number'] ?? $orderId);
        $preview = mb_strlen($message) > 120 ? mb_substr($message, 0, 120) . '…' : $message;
        $stmt = $pdo->prepare("
            INSERT INTO order_notifications (user_id, order_id, type, title, message)
            VALUES (?, ?, 'support', ?, ?)
        ");
        $stmt->execute([
            $order['user_id'],
            $orderId,
            $title,
            $preview
        ]);
    }
    
    // Get inserted message
    $stmt = $pdo->prepare("
        SELECT sm.*, 'Техподдержка' as username
        FROM support_messages sm
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
            'username' => 'Техподдержка',
            'avatar' => null,
            'is_support' => true,
            'created_at' => $messageData['created_at'],
            'formatted_time' => date('H:i', strtotime($messageData['created_at']))
        ]
    ];
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ошибка сервера. Попробуйте позже.']);
    error_log('Send support reply error: ' . $e->getMessage());
}

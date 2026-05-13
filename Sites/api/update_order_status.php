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
$newStatus = isset($data['status']) ? trim($data['status']) : '';

// Validate input
if (empty($orderId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID заказа не указан']);
    exit;
}

if (empty($newStatus)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Статус не указан']);
    exit;
}

// Validate status
$validStatuses = ['pending', 'confirmed', 'processing', 'assembling', 'shipping', 'shipped', 'ready_pickup', 'delivered', 'completed', 'cancelled'];
if (!in_array($newStatus, $validStatuses)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Недопустимый статус']);
    exit;
}

try {
    // Check if order exists
    $stmt = $pdo->prepare("SELECT id, status, user_id, order_number FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    
    if (!$order) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Заказ не найден']);
        exit;
    }
    
    // Update order status
    $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$newStatus, $orderId]);
    
    // Create notification for customer
    if (!empty($order['user_id'])) {
        $statusLabels = [
            'pending' => 'ожидает подтверждения',
            'confirmed' => 'подтверждён',
            'processing' => 'в обработке',
            'assembling' => 'собирается',
            'shipping' => 'передан курьеру',
            'shipped' => 'отправлен',
            'ready_pickup' => 'ждёт получения',
            'delivered' => 'доставлен',
            'completed' => 'завершён',
            'cancelled' => 'отменён'
        ];
        $statusText = $statusLabels[$newStatus] ?? $newStatus;
        $title = 'Статус заказа #' . htmlspecialchars($order['order_number'] ?? $orderId) . ' обновлён';
        $messageText = 'Новый статус: ' . mb_convert_case($statusText, MB_CASE_TITLE, 'UTF-8');
        $stmt = $pdo->prepare("INSERT INTO order_notifications (user_id, order_id, type, title, message) VALUES (?, ?, 'status', ?, ?)");
        $stmt->execute([$order['user_id'], $orderId, $title, $messageText]);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Статус заказа успешно изменен',
        'old_status' => $order['status'],
        'new_status' => $newStatus
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ошибка сервера. Попробуйте позже.']);
    error_log('Update order status error: ' . $e->getMessage());
}

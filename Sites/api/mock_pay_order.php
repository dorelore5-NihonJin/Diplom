<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Метод не разрешён']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    $data = $_POST;
}

$orderId = isset($data['order_id']) ? (int)$data['order_id'] : 0;
$userId = (int)$_SESSION['user_id'];

if ($orderId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Некорректный ID заказа']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT id, user_id, status, payment_status, order_number FROM orders WHERE id = ? LIMIT 1');
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order || (int)$order['user_id'] !== $userId) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Заказ не найден']);
        exit;
    }

    if ($order['status'] === 'cancelled') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Отменённый заказ нельзя оплатить']);
        exit;
    }

    if ($order['payment_status'] === 'paid') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Заказ уже оплачен']);
        exit;
    }

    $pdo->beginTransaction();

    $nextStatus = $order['status'];
    if ($order['status'] === 'pending') {
        $nextStatus = 'confirmed';
    }

    $stmt = $pdo->prepare('UPDATE orders SET payment_status = "paid", status = ?, updated_at = NOW() WHERE id = ?');
    $stmt->execute([$nextStatus, $orderId]);

    $title = 'Оплата заказа #' . htmlspecialchars($order['order_number'] ?? $orderId) . ' подтверждена';
    $message = 'Мы получили оплату. Заказ отправлен в работу.';
    $stmt = $pdo->prepare('INSERT INTO order_notifications (user_id, order_id, type, title, message, created_at) VALUES (?, ?, "status", ?, ?, NOW())');
    $stmt->execute([$userId, $orderId, $title, $message]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Оплата успешно подтверждена',
        'new_status' => $nextStatus
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ошибка сервера при обработке оплаты']);
    error_log('Mock pay order error: ' . $e->getMessage());
}

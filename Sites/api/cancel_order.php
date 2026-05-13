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

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$orderId = isset($input['order_id']) ? (int)$input['order_id'] : 0;
$userId = (int)$_SESSION['user_id'];

if ($orderId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Некорректный ID заказа']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT id, status, user_id, order_number FROM orders WHERE id = ? LIMIT 1');
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order || (int)$order['user_id'] !== $userId) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Заказ не найден']);
        exit;
    }

    if (!in_array($order['status'], ['pending', 'confirmed'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Отмена доступна только до сборки заказа']);
        exit;
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare('UPDATE orders SET status = "cancelled", updated_at = NOW() WHERE id = ?');
    $stmt->execute([$orderId]);

    $title = 'Заказ #' . htmlspecialchars($order['order_number'] ?? $orderId) . ' отменён';
    $message = 'Вы отменили заказ. Мы надеемся увидеть вас снова!';

    $stmt = $pdo->prepare('INSERT INTO order_notifications (user_id, order_id, type, title, message, created_at) VALUES (?, ?, "status", ?, ?, NOW())');
    $stmt->execute([$userId, $orderId, $title, $message]);

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Заказ отменён']);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ошибка сервера при отмене заказа']);
    error_log('Cancel order error: ' . $e->getMessage());
}

<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user || !in_array($user['role'], ['support', 'admin', 'high-admin', 'owner'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Доступ запрещён']);
        exit;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ошибка сервера']);
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

if ($orderId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID заказа не указан']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT id FROM orders WHERE id = ?');
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Заказ не найден']);
        exit;
    }

    $pdo->beginTransaction();

    $tablesToDelete = [
        'order_items',
        'support_messages',
        'order_notifications'
    ];

    foreach ($tablesToDelete as $table) {
        $column = $table === 'order_notifications' ? 'order_id' : 'order_id';
        $stmt = $pdo->prepare("DELETE FROM {$table} WHERE {$column} = ?");
        $stmt->execute([$orderId]);
    }

    $stmt = $pdo->prepare('DELETE FROM orders WHERE id = ?');
    $stmt->execute([$orderId]);

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Заказ удалён']);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ошибка сервера при удалении заказа']);
    error_log('Delete order error: ' . $e->getMessage());
}

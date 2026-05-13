<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
    exit;
}

$ticketId = isset($_GET['ticket_id']) ? (int)$_GET['ticket_id'] : 0;
$since = isset($_GET['since']) ? trim($_GET['since']) : '';

if ($ticketId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Некорректный тикет']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$isStaff = false;

try {
    $stmt = $pdo->prepare('SELECT role FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $role = $stmt->fetchColumn();
$isStaff = in_array($role, ['support', 'moderator', 'admin', 'high-admin', 'owner'], true);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ошибка сервера']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT user_id FROM support_tickets WHERE id = ? LIMIT 1');
    $stmt->execute([$ticketId]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $ticket = false;
}

if (!$ticket) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Тикет не найден']);
    exit;
}

if (!$isStaff && (int)$ticket['user_id'] !== $userId) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Нет доступа к этому тикету']);
    exit;
}

$params = [$ticketId];
$conditions = '';

if ($since !== '') {
    $conditions = 'AND tr.created_at > ?';
    $params[] = $since;
}

try {
    $stmt = $pdo->prepare("SELECT tr.*, COALESCE(u.username, 'Пользователь') AS username, u.avatar
        FROM ticket_replies tr
        LEFT JOIN users u ON tr.user_id = u.id
        WHERE tr.ticket_id = ? $conditions
        ORDER BY tr.created_at ASC");
    $stmt->execute($params);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formatted = array_map(function ($msg) {
        return [
            'id' => $msg['id'],
            'message' => $msg['message'],
            'created_at' => $msg['created_at'],
            'formatted_time' => date('d.m.Y H:i', strtotime($msg['created_at'])),
            'is_staff' => (bool)$msg['is_staff'],
            'username' => $msg['is_staff'] ? 'Техподдержка' : ($msg['username'] ?? 'Пользователь'),
            'avatar' => $msg['is_staff'] ? null : ($msg['avatar'] ?? null)
        ];
    }, $messages);

    $latestTimestamp = $formatted ? end($formatted)['created_at'] : $since;

    echo json_encode([
        'success' => true,
        'messages' => $formatted,
        'latest_timestamp' => $latestTimestamp
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ошибка загрузки обновлений']);
}

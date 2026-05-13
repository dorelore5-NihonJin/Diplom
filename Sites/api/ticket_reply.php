<?php
session_start();
require_once '../config.php';
require_once '../includes/security.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Метод не разрешен']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!$payload) {
    $payload = $_POST;
}

$ticketId = isset($payload['ticket_id']) ? (int)$payload['ticket_id'] : 0;
$message = trim($payload['message'] ?? '');
$csrfToken = $payload['csrf_token'] ?? '';

if (!Security::verifyCSRFToken($csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Недействительный CSRF токен']);
    exit;
}

if ($ticketId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Некорректный тикет']);
    exit;
}

if ($message === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Сообщение не может быть пустым']);
    exit;
}

if (mb_strlen($message) > 5000) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Сообщение слишком длинное (максимум 5000 символов)']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$currentUser = null;
$isStaff = false;

try {
    $stmt = $pdo->prepare('SELECT username, avatar, role FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
    $role = $currentUser['role'] ?? 'user';
$isStaff = in_array($role, ['support', 'moderator', 'admin', 'high-admin', 'owner'], true);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ошибка сервера']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT id, user_id, status FROM support_tickets WHERE id = ? LIMIT 1');
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

$isTicketOwner = (int)$ticket['user_id'] === $userId;

if (!$isStaff && !$isTicketOwner) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Недостаточно прав для ответа']);
    exit;
}

$forceStaff = !empty($payload['as_staff']);
$replyIsStaff = $isStaff && $forceStaff;

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('INSERT INTO ticket_replies (ticket_id, user_id, is_staff, message, created_at) VALUES (?, ?, ?, ?, NOW())');
    $stmt->execute([$ticketId, $userId, $replyIsStaff ? 1 : 0, $message]);
    $replyId = $pdo->lastInsertId();

    $stmt = $pdo->prepare('UPDATE support_tickets SET updated_at = NOW() WHERE id = ?');
    $stmt->execute([$ticketId]);

    // Create notification for user if reply is from staff
    if ($replyIsStaff && isset($ticket['user_id']) && (int)$ticket['user_id'] !== $userId) {
        try {
            $notifStmt = $pdo->prepare("
                INSERT INTO order_notifications (user_id, order_id, type, title, message, created_at)
                VALUES (?, ?, 'support', ?, ?, NOW())
            ");
            
            $title = "Новый ответ в тикете #" . ($ticket['id'] ?? $ticketId);
            // Shorten message for notification
            $preview = mb_strlen($message) > 100 ? mb_substr($message, 0, 100) . '...' : $message;
            
            $notifStmt->execute([
                $ticket['user_id'],
                $ticketId, // Using order_id to store ticket_id for support type notifications
                $title,
                $preview
            ]);
        } catch (Exception $e) {
            // Ignore notification errors to not block reply
        }
    }

    $pdo->commit();

    $response = [
        'success' => true,
        'message' => 'Ответ отправлен',
        'data' => [
            'id' => $replyId,
            'message' => $message,
            'created_at' => date('Y-m-d H:i:s'),
            'formatted_time' => date('d.m.Y H:i'),
            'is_staff' => $replyIsStaff,
            'username' => $replyIsStaff ? 'Техподдержка' : ($currentUser['username'] ?? 'Вы'),
            'avatar' => $replyIsStaff ? null : ($currentUser['avatar'] ?? null)
        ]
    ];

    echo json_encode($response);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Не удалось отправить сообщение']);
}

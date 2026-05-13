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

// Validate input
if (empty($orderId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID заказа не указан']);
    exit;
}

try {
    // Check user role
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    $isSupport = $user && in_array($user['role'], ['support', 'admin', 'high-admin', 'owner']);
    
    if (!$isSupport) {
        // For regular users, check if order belongs to them
        $stmt = $pdo->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ?");
        $stmt->execute([$orderId, $_SESSION['user_id']]);
        
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Доступ запрещен']);
            exit;
        }
        
        // Mark support messages as read (messages FROM support)
        $stmt = $pdo->prepare("
            UPDATE support_messages 
            SET is_read = 1 
            WHERE order_id = ? AND is_support = 1 AND is_read = 0
        ");
    } else {
        // For support staff, check if order exists
        $stmt = $pdo->prepare("SELECT id FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Заказ не найден']);
            exit;
        }
        
        // Mark user messages as read (messages FROM users)
        $stmt = $pdo->prepare("
            UPDATE support_messages 
            SET is_read = 1 
            WHERE order_id = ? AND is_support = 0 AND is_read = 0
        ");
    }
    
    $stmt->execute([$orderId]);
    $updatedCount = $stmt->rowCount();
    
    echo json_encode([
        'success' => true,
        'message' => 'Сообщения отмечены как прочитанные',
        'updated_count' => $updatedCount
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ошибка сервера. Попробуйте позже.']);
    error_log('Mark messages read error: ' . $e->getMessage());
}

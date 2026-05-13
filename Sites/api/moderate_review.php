<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

// Check if user is support staff or owner
$hasAccess = false;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    $hasAccess = in_array($user['role'] ?? '', ['support', 'owner']);
}

if (!$hasAccess) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$reviewId = (int)($data['review_id'] ?? 0);
$newStatus = $data['status'] ?? '';

if (!$reviewId || !in_array($newStatus, ['pending', 'published', 'archived'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE component_reviews SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$newStatus, $reviewId]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Статус обзора обновлён',
            'new_status' => $newStatus
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Review not found']);
    }
} catch (PDOException $e) {
    error_log('Moderate review error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}

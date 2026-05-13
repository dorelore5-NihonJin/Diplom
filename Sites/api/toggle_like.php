<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Get request data
$data = json_decode(file_get_contents('php://input'), true);
$buildId = $data['build_id'] ?? null;
$userId = $_SESSION['user_id'];

if (!$buildId) {
    echo json_encode(['success' => false, 'error' => 'Missing build_id']);
    exit;
}

try {
    // Check if already liked
    $stmt = $pdo->prepare("SELECT id FROM build_likes WHERE build_id = ? AND user_id = ?");
    $stmt->execute([$buildId, $userId]);
    $existingLike = $stmt->fetch();
    
    if ($existingLike) {
        // Unlike
        $stmt = $pdo->prepare("DELETE FROM build_likes WHERE build_id = ? AND user_id = ?");
        $stmt->execute([$buildId, $userId]);
        $action = 'unliked';
    } else {
        // Like
        $stmt = $pdo->prepare("INSERT INTO build_likes (build_id, user_id) VALUES (?, ?)");
        $stmt->execute([$buildId, $userId]);
        $action = 'liked';
    }
    
    // Get updated like count
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM build_likes WHERE build_id = ?");
    $stmt->execute([$buildId]);
    $likeCount = $stmt->fetch()['count'];
    
    echo json_encode([
        'success' => true,
        'action' => $action,
        'like_count' => $likeCount
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}

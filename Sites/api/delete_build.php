<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Необходима авторизация']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$buildId = $data['build_id'] ?? null;

if (!$buildId) {
    echo json_encode(['success' => false, 'error' => 'Неверные данные']);
    exit;
}

try {
    // Check if user owns this build
    $stmt = $pdo->prepare("SELECT user_id FROM user_builds WHERE id = ?");
    $stmt->execute([$buildId]);
    $build = $stmt->fetch();
    
    if (!$build) {
        echo json_encode(['success' => false, 'error' => 'Сборка не найдена']);
        exit;
    }
    
    if ($build['user_id'] != $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'error' => 'Нет прав на удаление']);
        exit;
    }
    
    // Delete build components first
    $stmt = $pdo->prepare("DELETE FROM build_components WHERE build_id = ?");
    $stmt->execute([$buildId]);
    
    // Delete build likes
    $stmt = $pdo->prepare("DELETE FROM build_likes WHERE build_id = ?");
    $stmt->execute([$buildId]);
    
    // Delete build comments
    $stmt = $pdo->prepare("DELETE FROM build_comments WHERE build_id = ?");
    $stmt->execute([$buildId]);
    
    // Delete the build itself
    $stmt = $pdo->prepare("DELETE FROM user_builds WHERE id = ?");
    $stmt->execute([$buildId]);
    
    echo json_encode(['success' => true, 'message' => 'Сборка удалена']);
} catch (PDOException $e) {
    error_log('Delete build error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Ошибка удаления']);
}
?>

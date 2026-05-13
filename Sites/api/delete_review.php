<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Необходима авторизация']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$reviewId = $data['review_id'] ?? null;

if (!$reviewId) {
    echo json_encode(['success' => false, 'error' => 'Некорректный запрос']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT user_id FROM component_reviews WHERE id = ? LIMIT 1');
    $stmt->execute([$reviewId]);
    $review = $stmt->fetch();

    if (!$review) {
        echo json_encode(['success' => false, 'error' => 'Обзор не найден']);
        exit;
    }

    if ((int)$review['user_id'] !== (int)$_SESSION['user_id']) {
        echo json_encode(['success' => false, 'error' => 'Нет прав на удаление']);
        exit;
    }

    $stmt = $pdo->prepare('DELETE FROM component_reviews WHERE id = ?');
    $stmt->execute([$reviewId]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    error_log('Delete review error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Ошибка удаления']);
}

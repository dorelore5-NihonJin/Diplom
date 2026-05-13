<?php
require_once '../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Необходима авторизация']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['name']) || !isset($data['components'])) {
    echo json_encode(['success' => false, 'error' => 'Неверные данные']);
    exit;
}

if (empty($data['components'])) {
    echo json_encode(['success' => false, 'error' => 'Добавьте хотя бы один компонент']);
    exit;
}

$allowedPurposes = ['gaming', 'work', 'streaming', 'editing', 'other'];
$purpose = isset($data['purpose']) && in_array($data['purpose'], $allowedPurposes, true)
    ? $data['purpose']
    : 'other';

try {
    $userId = $_SESSION['user_id'];
    $sessionId = getSessionId();
    
    // Prepare components JSON for storage
    $componentsJson = json_encode($data['summary'] ?? [], JSON_UNESCAPED_UNICODE);
    
    // Insert build
    $stmt = $pdo->prepare("
        INSERT INTO user_builds (user_id, build_name, purpose, user_session, total_price, total_power, components, is_public, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())
    ");
    $stmt->execute([
        $userId,
        $data['name'],
        $purpose,
        $sessionId,
        $data['totalPrice'],
        $data['totalPower'],
        $componentsJson
    ]);
    
    $buildId = $pdo->lastInsertId();
    
    // Insert components into build_components table
    $stmt = $pdo->prepare("
        INSERT INTO build_components (build_id, component_id, quantity)
        VALUES (?, ?, 1)
    ");
    
    foreach ($data['components'] as $categoryId => $component) {
        if (isset($component['id'])) {
            $stmt->execute([$buildId, $component['id']]);
            continue;
        }

        if (is_array($component)) {
            foreach ($component as $nested) {
                if (is_array($nested) && isset($nested['id'])) {
                    $stmt->execute([$buildId, $nested['id']]);
                }
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'build_id' => $buildId,
        'message' => 'Сборка успешно сохранена'
    ]);
} catch (PDOException $e) {
    error_log('Save build error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Ошибка сохранения сборки'
    ]);
}
?>

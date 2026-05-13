<?php
require_once '../config.php';
require_once '../includes/components_union.php';

header('Content-Type: application/json');

$category = $_GET['category'] ?? '';

try {
    $componentsSource = getComponentsUnionSource();
    $categoryMap = getCategoryMap();
    $selectedCategoryId = $category && isset($categoryMap[$category]) ? $categoryMap[$category] : null;

    if ($selectedCategoryId) {
        $query = "SELECT MIN(c.price) as min_price, MAX(c.price) as max_price
                  FROM {$componentsSource} AS c 
                  WHERE c.category_id = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$selectedCategoryId]);
    } else {
        $query = "SELECT MIN(c.price) as min_price, MAX(c.price) as max_price FROM {$componentsSource} AS c";
        $stmt = $pdo->query($query);
    }
    
    $result = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'min_price' => (int)($result['min_price'] ?? 0),
        'max_price' => (int)($result['max_price'] ?? 0)
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

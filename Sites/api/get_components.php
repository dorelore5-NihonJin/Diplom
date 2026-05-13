<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once '../config.php';

header('Content-Type: application/json');

$categoryId = $_GET['category'] ?? '';

if (!$categoryId) {
    echo json_encode([]);
    exit;
}

// Map category IDs to their dedicated tables
$categoryTables = [
    1 => 'components_cpu',
    2 => 'components_gpu',
    3 => 'components_mobo',
    4 => 'components_ram',
    5 => 'components_storage',
    6 => 'components_psu',
    7 => 'components_case',
    8 => 'components_cooling',
];

$table = $categoryTables[(int)$categoryId] ?? null;

if (!$table) {
    echo json_encode(['error' => 'Invalid category']);
    exit;
}

try {
    // Define columns per table
    $tableColumns = [
        'components_cpu' => ['id', 'category_id', 'name', 'manufacturer', 'model', 'price', 'specs', 'performance_score', 'power_consumption', 'stock_status', 'socket_type', 'cpu_cores', 'cpu_threads', 'cpu_base_clock'],
        'components_gpu' => ['id', 'category_id', 'name', 'manufacturer', 'model', 'price', 'specs', 'performance_score', 'power_consumption', 'stock_status', 'gpu_memory', 'gpu_memory_type', 'pcie_version'],
        'components_mobo' => ['id', 'category_id', 'name', 'manufacturer', 'model', 'price', 'specs', 'performance_score', 'power_consumption', 'stock_status', 'socket_type', 'mobo_form_factor', 'mobo_chipset', 'mobo_ram_type', 'mobo_max_ram_speed', 'mobo_ram_slots', 'mobo_m2_slots'],
        'components_ram' => ['id', 'category_id', 'name', 'manufacturer', 'model', 'price', 'specs', 'performance_score', 'power_consumption', 'stock_status', 'ram_capacity', 'ram_type', 'ram_speed'],
        'components_storage' => ['id', 'category_id', 'name', 'manufacturer', 'model', 'price', 'specs', 'performance_score', 'power_consumption', 'stock_status', 'storage_capacity', 'storage_type', 'storage_interface'],
        'components_psu' => ['id', 'category_id', 'name', 'manufacturer', 'model', 'price', 'specs', 'performance_score', 'power_consumption', 'stock_status', 'psu_wattage', 'psu_efficiency'],
        'components_case' => ['id', 'category_id', 'name', 'manufacturer', 'model', 'price', 'specs', 'performance_score', 'power_consumption', 'stock_status', 'case_form_factor', 'case_max_gpu_length', 'case_max_cooler_height'],
        'components_cooling' => ['id', 'category_id', 'name', 'manufacturer', 'model', 'price', 'specs', 'performance_score', 'power_consumption', 'stock_status', 'cooler_type', 'cooler_height', 'cooler_tdp', 'cooler_socket'],
    ];
    
    $columns = $tableColumns[$table] ?? ['id', 'name', 'manufacturer', 'model', 'price', 'specs', 'performance_score', 'power_consumption', 'stock_status'];
    $columnsList = implode(', ', $columns);
    
    $stmt = $pdo->prepare("
        SELECT {$columnsList}
        FROM {$table}
        WHERE stock_status != 'out_of_stock'
        ORDER BY performance_score DESC, price ASC
    ");
    $stmt->execute();
    $components = $stmt->fetchAll();
    
    // Log for debugging
    error_log("Category: $categoryId, Table: $table, Count: " . count($components));
    
    echo json_encode($components);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>

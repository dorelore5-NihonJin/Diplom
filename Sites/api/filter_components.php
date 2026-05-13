<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once '../config.php';
require_once '../includes/category_filters.php';
require_once '../includes/components_union.php';
require_once '../includes/component_specs.php';

header('Content-Type: application/json');

$categoryImages = [
    'cpu' => 'pictures/catalog/cpu.svg',
    'gpu' => 'pictures/catalog/gpu.svg',
    'motherboard' => 'pictures/catalog/motherboard.svg',
    'ram' => 'pictures/catalog/ram.svg',
    'storage' => 'pictures/catalog/storage.svg',
    'storage_nvme' => 'pictures/catalog/storage_nvme.svg',
    'storage_sata' => 'pictures/catalog/storage_sata.svg',
    'storage_hdd' => 'pictures/catalog/storage_hdd.svg',
    'psu' => 'pictures/catalog/psu.svg',
    'case' => 'pictures/catalog/case.svg',
    'cooling' => 'pictures/catalog/cooling.svg',
    'cooling_air' => 'pictures/catalog/cooling_air.svg',
    'cooling_aio' => 'pictures/catalog/cooling_aio.svg',
];
function normalize_component_field($value) {
    if (!is_string($value)) {
        return '';
    }
    return mb_strtolower(trim($value));
}
function get_category_image_path($slug, $categoryImages) {
    return $categoryImages[$slug] ?? 'pictures/catalog/default.svg';
}
function get_component_image_path($component, $categoryImages) {
    $slug = $component['category_slug'] ?? '';
    if ($slug === 'storage') {
        $type = normalize_component_field($component['storage_type'] ?? '');
        $iface = normalize_component_field($component['storage_interface'] ?? '');
        if (strpos($type, 'hdd') !== false) {
            return $categoryImages['storage_hdd'];
        }
        if (strpos($type, 'nvme') !== false || strpos($iface, 'nvme') !== false || strpos($iface, 'pcie') !== false) {
            return $categoryImages['storage_nvme'];
        }
        if (strpos($type, 'ssd') !== false || strpos($iface, 'sata') !== false) {
            return $categoryImages['storage_sata'];
        }
        return $categoryImages['storage'];
    }
    if ($slug === 'cooling') {
        $coolerType = normalize_component_field($component['cooler_type'] ?? '');
        if ($coolerType && (strpos($coolerType, 'aio') !== false || strpos($coolerType, 'liquid') !== false || strpos($coolerType, 'water') !== false)) {
            return $categoryImages['cooling_aio'];
        }
        if ($coolerType) {
            return $categoryImages['cooling_air'];
        }
    }
    return get_category_image_path($slug, $categoryImages);
}
// Get filters
$category = $_GET['category'] ?? '';
$manufacturer = $_GET['manufacturer'] ?? '';
$minPrice = $_GET['min_price'] ?? '';
$maxPrice = $_GET['max_price'] ?? '';
$minYear = $_GET['min_year'] ?? '';
$maxYear = $_GET['max_year'] ?? '';
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'name_asc';

// Category-specific filters
$categoryFilters = [
    'cpu_cores_min' => $_GET['cpu_cores_min'] ?? '',
    'cpu_cores_max' => $_GET['cpu_cores_max'] ?? '',
    'cpu_tdp_min' => $_GET['cpu_tdp_min'] ?? '',
    'cpu_tdp_max' => $_GET['cpu_tdp_max'] ?? '',
    'gpu_memory_min' => $_GET['gpu_memory_min'] ?? '',
    'gpu_memory_max' => $_GET['gpu_memory_max'] ?? '',
    'gpu_tdp_min' => $_GET['gpu_tdp_min'] ?? '',
    'gpu_tdp_max' => $_GET['gpu_tdp_max'] ?? '',
    'mobo_form_factor' => $_GET['mobo_form_factor'] ?? '',
    'mobo_chipset' => $_GET['mobo_chipset'] ?? '',
    'mobo_socket' => $_GET['mobo_socket'] ?? '',
    'mobo_ram_slots_min' => $_GET['mobo_ram_slots_min'] ?? '',
    'mobo_ram_slots_max' => $_GET['mobo_ram_slots_max'] ?? '',
    'mobo_m2_slots_min' => $_GET['mobo_m2_slots_min'] ?? '',
    'mobo_m2_slots_max' => $_GET['mobo_m2_slots_max'] ?? '',
    'ram_capacity_min' => $_GET['ram_capacity_min'] ?? '',
    'ram_capacity_max' => $_GET['ram_capacity_max'] ?? '',
    'ram_speed_min' => $_GET['ram_speed_min'] ?? '',
    'ram_speed_max' => $_GET['ram_speed_max'] ?? '',
    'storage_capacity_min' => $_GET['storage_capacity_min'] ?? '',
    'storage_capacity_max' => $_GET['storage_capacity_max'] ?? '',
    'storage_interface' => $_GET['storage_interface'] ?? '',
    'psu_wattage_min' => $_GET['psu_wattage_min'] ?? '',
    'psu_wattage_max' => $_GET['psu_wattage_max'] ?? '',
    'psu_efficiency' => $_GET['psu_efficiency'] ?? '',
    'case_gpu_length_min' => $_GET['case_gpu_length_min'] ?? '',
    'case_gpu_length_max' => $_GET['case_gpu_length_max'] ?? '',
    'case_fans_min' => $_GET['case_fans_min'] ?? '',
    'case_fans_max' => $_GET['case_fans_max'] ?? '',
    'case_form_factor' => $_GET['case_form_factor'] ?? '',
    'case_side_panel' => $_GET['case_side_panel'] ?? '',
    'cooler_type' => $_GET['cooler_type'] ?? '',
    'cooler_height_max' => $_GET['cooler_height_max'] ?? '',
];

// Build query using UNION source
$componentsSource = getComponentsUnionSource();
$categoryMap = getCategoryMap();
$selectedCategoryId = $category && isset($categoryMap[$category]) ? $categoryMap[$category] : null;

// Log for debugging
error_log("Category slug: '$category', Selected ID: " . ($selectedCategoryId ?? 'null'));
error_log("Min price: '$minPrice' (empty check: " . ($minPrice !== '' ? 'true' : 'false') . "), Max price: '$maxPrice' (empty check: " . ($maxPrice !== '' ? 'true' : 'false') . ")");

$query = "SELECT c.*, cat.name as category_name, cat.icon as category_icon, cat.slug as category_slug 
          FROM {$componentsSource} AS c 
          JOIN categories cat ON c.category_id = cat.id 
          WHERE 1=1";
$params = [];

if ($selectedCategoryId) {
    $query .= " AND c.category_id = ?";
    $params[] = $selectedCategoryId;
}

if ($manufacturer) {
    $manufacturers_array = explode(',', $manufacturer);
    $placeholders = str_repeat('?,', count($manufacturers_array) - 1) . '?';
    $query .= " AND c.manufacturer IN ($placeholders)";
    $params = array_merge($params, $manufacturers_array);
}

if ($minPrice !== '') {
    $query .= " AND c.price >= ?";
    $params[] = $minPrice;
}

if ($maxPrice !== '') {
    $query .= " AND c.price <= ?";
    $params[] = $maxPrice;
}

if ($minYear !== '') {
    $query .= " AND c.release_year >= ?";
    $params[] = $minYear;
}

if ($maxYear !== '') {
    $query .= " AND c.release_year <= ?";
    $params[] = $maxYear;
}

if ($search) {
    $query .= " AND (c.name LIKE ? OR c.model LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Apply category-specific filters
if ($selectedCategoryId) {
    applyCategoryFilters($query, $params, (int)$selectedCategoryId, $categoryFilters);
}

// Sorting
switch ($sort) {
    case 'price_asc':
        $query .= " ORDER BY c.price ASC";
        break;
    case 'price_desc':
        $query .= " ORDER BY c.price DESC";
        break;
    case 'performance_desc':
        $query .= " ORDER BY c.performance_score DESC";
        break;
    default:
        $query .= " ORDER BY c.name ASC";
}

// Add limit for initial load
$limit = 20;
$query .= " LIMIT $limit";

try {
    // Log query for debugging
    error_log("Filter query: " . $query);
    error_log("Params: " . print_r($params, true));
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $components = $stmt->fetchAll();
    
    error_log("Found components: " . count($components));
    
    // Generate HTML for components
    ob_start();
    
    if (empty($components)) {
        ?>
        <div class="no-results">
            <i class="fas fa-box-open fa-4x"></i>
            <h3>Ничего не найдено</h3>
            <p>Попробуйте изменить параметры фильтрации</p>
        </div>
        <?php
    } else {
        foreach ($components as $component) {
            $imageQuery = trim(($component['manufacturer'] ?? '') . ' ' . ($component['model'] ?? ''));
            $imageQuery = preg_replace('/\s+/', ' ', $imageQuery);
            $categorySlug = $component['category_slug'] ?? '';
            $categoryImage = get_component_image_path($component, $categoryImages);
            ?>
            <div class="component-card" data-component-id="<?= $component['id'] ?>" data-category-id="<?= $component['category_id'] ?>" data-image-query="<?= htmlspecialchars($imageQuery) ?>" data-category-slug="<?= htmlspecialchars($categorySlug) ?>">
                <div class="component-header">
                    <div class="component-category">
                        <i class="fas <?= $component['category_icon'] ?>"></i>
                        <?= htmlspecialchars($component['category_name']) ?>
                    </div>
                    <?php if ($component['stock_status'] === 'in_stock'): ?>
                        <span class="stock-badge in-stock">В наличии</span>
                    <?php elseif ($component['stock_status'] === 'low_stock'): ?>
                        <span class="stock-badge low-stock">Мало</span>
                    <?php else: ?>
                        <span class="stock-badge out-stock">Нет в наличии</span>
                    <?php endif; ?>
                </div>

                <div class="component-image">
                    <img class="component-photo is-loaded" src="<?= htmlspecialchars($categoryImage) ?>" alt="<?= htmlspecialchars($component['name']) ?>" loading="lazy">
                    <i class="fas <?= $component['category_icon'] ?> fa-4x component-icon is-hidden"></i>
                </div>

                <div class="component-info">
                    <h3><?= htmlspecialchars($component['name']) ?></h3>
                    <p class="component-manufacturer"><?= htmlspecialchars($component['manufacturer']) ?></p>
                    
                    <?php if ($component['performance_score']): ?>
                    <div class="performance-bar">
                        <div class="performance-fill" style="width: <?= $component['performance_score'] / 100 ?>%"></div>
                        <span class="performance-score"><?= $component['performance_score'] ?></span>
                    </div>
                    <?php endif; ?>

                    <div class="component-specs">
                        <?php
                        $specItems = get_component_spec_items($component);
                        foreach ($specItems as $value) {
                            echo '<span class="spec-item">' . htmlspecialchars((string)$value) . '</span>';
                        }
                        ?>
                    </div>
                </div>

                <div class="component-footer">
                    <div class="component-price"><?= formatPrice($component['price']) ?></div>
                    <button class="btn btn-primary btn-add-to-build" 
                            data-id="<?= $component['id'] ?>"
                            data-name="<?= htmlspecialchars($component['name']) ?>"
                            data-price="<?= $component['price'] ?>"
                            data-category="<?= $component['category_id'] ?>">
                        <i class="fas fa-plus"></i>
                        В сборку
                    </button>
                </div>
            </div>
            <?php
        }
    }
    
    $html = ob_get_clean();
    
    // Get total count
    $countQuery = preg_replace('/SELECT c\.\*, cat\.name as category_name, cat\.icon as category_icon, cat\.slug as category_slug/', 'SELECT COUNT(*)', $query);
    $countQuery = preg_replace('/\s+LIMIT\s+\d+/', '', $countQuery);
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $totalCount = $countStmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'html' => $html,
        'count' => count($components),
        'total' => (int)$totalCount
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

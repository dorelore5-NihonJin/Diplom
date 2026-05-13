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
// Get filters and pagination
$category = $_GET['category'] ?? '';
$manufacturer = $_GET['manufacturer'] ?? '';
$minPrice = $_GET['min_price'] ?? '';
$maxPrice = $_GET['max_price'] ?? '';
$minYear = $_GET['min_year'] ?? '';
$maxYear = $_GET['max_year'] ?? '';
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'name_asc';
$page = (int)($_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

// Category-specific filters
$categoryFilters = [
    'cpu_cores_min' => $_GET['cpu_cores_min'] ?? '',
    'cpu_cores_max' => $_GET['cpu_cores_max'] ?? '',
    'cpu_threads_min' => $_GET['cpu_threads_min'] ?? '',
    'cpu_threads_max' => $_GET['cpu_threads_max'] ?? '',
    'cpu_base_clock_min' => $_GET['cpu_base_clock_min'] ?? '',
    'cpu_base_clock_max' => $_GET['cpu_base_clock_max'] ?? '',
    'cpu_tdp_min' => $_GET['cpu_tdp_min'] ?? '',
    'cpu_tdp_max' => $_GET['cpu_tdp_max'] ?? '',
    'cpu_socket' => $_GET['cpu_socket'] ?? '',
    'gpu_memory_min' => $_GET['gpu_memory_min'] ?? '',
    'gpu_memory_max' => $_GET['gpu_memory_max'] ?? '',
    'gpu_tdp_min' => $_GET['gpu_tdp_min'] ?? '',
    'gpu_tdp_max' => $_GET['gpu_tdp_max'] ?? '',
    'gpu_memory_type' => $_GET['gpu_memory_type'] ?? '',
    'gpu_boost_clock_min' => $_GET['gpu_boost_clock_min'] ?? '',
    'gpu_boost_clock_max' => $_GET['gpu_boost_clock_max'] ?? '',
];

// Build query using UNION source
$componentsSource = getComponentsUnionSource();
$categoryMap = getCategoryMap();
$selectedCategoryId = $category && isset($categoryMap[$category]) ? $categoryMap[$category] : null;

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
    $manufacturers = explode(',', $manufacturer);
    $placeholders = str_repeat('?,', count($manufacturers) - 1) . '?';
    $query .= " AND c.manufacturer IN ($placeholders)";
    $params = array_merge($params, $manufacturers);
}

if ($minPrice) {
    $query .= " AND c.price >= ?";
    $params[] = $minPrice;
}

if ($maxPrice) {
    $query .= " AND c.price <= ?";
    $params[] = $maxPrice;
}

if ($minYear) {
    $query .= " AND c.release_year >= ?";
    $params[] = $minYear;
}

if ($maxYear) {
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

// Add pagination
$query .= " LIMIT $limit OFFSET $offset";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $components = $stmt->fetchAll();
    
    // Generate HTML
    ob_start();
    foreach ($components as $component):
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
    endforeach;
    $html = ob_get_clean();
    
    echo json_encode([
        'success' => true,
        'html' => $html,
        'count' => count($components),
        'hasMore' => count($components) === $limit
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

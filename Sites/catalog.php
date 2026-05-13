<?php
require_once 'config.php';
require_once 'includes/category_filters.php';
require_once 'includes/components_union.php';
require_once 'includes/component_specs.php';

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
    'case_cooler_height_min' => $_GET['case_cooler_height_min'] ?? '',
    'case_cooler_height_max' => $_GET['case_cooler_height_max'] ?? '',
    'case_form_factor' => $_GET['case_form_factor'] ?? '',
    'cooler_type' => $_GET['cooler_type'] ?? '',
    'cooler_height_max' => $_GET['cooler_height_max'] ?? '',
];

// Use helper to build UNION source
$componentsSource = getComponentsUnionSource();
$categoryMap = getCategoryMap();
$selectedCategoryId = $category && isset($categoryMap[$category]) ? $categoryMap[$category] : null;
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

// Common filter conditions reused across queries
$baseWhere = '';
$baseParams = [];

if ($manufacturer !== '') {
    $manufacturersArray = array_filter(array_map('trim', explode(',', $manufacturer)), static fn($value) => $value !== '');
    if ($manufacturersArray) {
        $placeholders = implode(',', array_fill(0, count($manufacturersArray), '?'));
        $baseWhere .= " AND c.manufacturer IN ($placeholders)";
        $baseParams = array_merge($baseParams, $manufacturersArray);
    }
}

if ($minPrice !== '' && is_numeric($minPrice)) {
    $baseWhere .= " AND c.price >= ?";
    $baseParams[] = (float)$minPrice;
}

if ($maxPrice !== '' && is_numeric($maxPrice)) {
    $baseWhere .= " AND c.price <= ?";
    $baseParams[] = (float)$maxPrice;
}

if ($minYear !== '' && is_numeric($minYear)) {
    $baseWhere .= " AND c.release_year >= ?";
    $baseParams[] = (int)$minYear;
}

if ($maxYear !== '' && is_numeric($maxYear)) {
    $baseWhere .= " AND c.release_year <= ?";
    $baseParams[] = (int)$maxYear;
}

if ($search !== '') {
    $baseWhere .= " AND (c.name LIKE ? OR c.model LIKE ?)";
    $baseParams[] = "%$search%";
    $baseParams[] = "%$search%";
}

// Build query based on aggregated data source
$query = "SELECT c.*, cat.name as category_name, cat.icon as category_icon, cat.slug as category_slug 
          FROM {$componentsSource} AS c 
          JOIN categories cat ON c.category_id = cat.id 
          WHERE 1=1{$baseWhere}";
$countQuery = "SELECT COUNT(*) 
               FROM {$componentsSource} AS c 
               JOIN categories cat ON c.category_id = cat.id 
               WHERE 1=1{$baseWhere}";
$params = $baseParams;
$countParams = $baseParams;

if ($selectedCategoryId) {
    $query .= " AND c.category_id = ?";
    $countQuery .= " AND c.category_id = ?";
    $params[] = $selectedCategoryId;
    $countParams[] = $selectedCategoryId;

    applyCategoryFilters($query, $params, (int)$selectedCategoryId, $categoryFilters);
    applyCategoryFilters($countQuery, $countParams, (int)$selectedCategoryId, $categoryFilters);
}

// Sorting (single alias)
$orderAlias = 'c';
switch ($sort) {
    case 'price_asc':
        $query .= " ORDER BY {$orderAlias}.price ASC";
        break;
    case 'price_desc':
        $query .= " ORDER BY {$orderAlias}.price DESC";
        break;
    case 'performance_desc':
        $query .= " ORDER BY {$orderAlias}.performance_score DESC";
        break;
    default:
        $query .= " ORDER BY {$orderAlias}.name ASC";
}

// Pagination for infinite scroll
$limit = 20;
$query .= " LIMIT $limit";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$components = $stmt->fetchAll();

// Get total count for display
$countStmt = $pdo->prepare($countQuery);
$countStmt->execute($countParams);
$totalCount = (int)$countStmt->fetchColumn();

// Get categories for filter
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Get manufacturers for filter (from aggregated view)
if ($category && $selectedCategoryId) {
    $mfrQuery = "SELECT DISTINCT c.manufacturer 
                 FROM {$componentsSource} AS c 
                 JOIN categories cat ON c.category_id = cat.id 
                 WHERE c.manufacturer IS NOT NULL AND cat.slug = ? 
                 ORDER BY c.manufacturer";
    $mfrStmt = $pdo->prepare($mfrQuery);
    $mfrStmt->execute([$category]);
    $manufacturers = $mfrStmt->fetchAll();
} else {
    $manufacturers = $pdo->query("SELECT DISTINCT manufacturer FROM {$componentsSource} AS c WHERE c.manufacturer IS NOT NULL ORDER BY c.manufacturer")->fetchAll();
}

$manufacturerLogos = [
    'intel' => 'intel.png',
    'amd' => 'AMD.png',
    'nvidia' => 'NVIDIA.png',
    'msi' => 'MSI.png',
    'asus' => 'ASUS.png',
    'gigabyte' => 'Gigabyte.png',
    'corsair' => 'Corsair.png',
    'g.skill' => 'G.Skill.png',
    'gskill' => 'G.Skill.png',
    'kingston' => 'Kingston.png',
    'samsung' => 'Samsung.png',
    'western digital' => 'WD.png',
    'wd' => 'WD.png',
    'crucial' => 'Crucial.png',
    'seasonic' => 'Seasonic.png',
    'be quiet!' => 'be_quiet.png',
    'be quiet' => 'be_quiet.png',
    'lian li' => 'LIAN_LI.png',
    'fractal design' => 'Fractal_design.png',
    'nzxt' => 'NZXT.png',
    'noctua' => 'NOCTUA.png',
    'arctic' => 'ARCTIC.png'
    // Остальные производители будут отображаться с буквами
];

function getManufacturerLogo(string $name, array $map): ?string {
    $key = strtolower($name);
    $logo = $map[$key] ?? null;
    
    // Check if file actually exists
    if ($logo && file_exists('Company_logo/' . $logo)) {
        return $logo;
    }
    
    return null;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Каталог комплектующих - <?= SITE_NAME ?></title>
    <script>
        // Apply theme before page renders to prevent flash
        (function() {
            const storedTheme = localStorage.getItem('theme');
            const savedTheme = storedTheme || 'dark';
            if (!storedTheme) {
                localStorage.setItem('theme', savedTheme);
            }
            const root = document.documentElement;
            root.setAttribute('data-theme', savedTheme);
            root.style.backgroundColor = savedTheme === 'light' ? '#ffffff' : '#0f172a';
            root.style.colorScheme = savedTheme;
        })();
    </script>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/catalog.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="catalog-page">
        <div class="container">
            <div class="page-header">
                <h1>Каталог комплектующих</h1>
                <p>Найдите идеальные компоненты для вашей сборки</p>
            </div>

            <div class="catalog-layout">
                <!-- Filters Sidebar -->
                <aside class="filters-sidebar">
                    <div class="filter-section">
                        <h3><i class="fas fa-layer-group"></i> Категория</h3>
                        <div class="filter-options" id="categoryFilters">
                            <label class="filter-option">
                                <input type="radio" name="category" value="" 
                                       <?= !$category ? 'checked' : '' ?> 
                                       onchange="applyFilters()">
                                <span>Все категории</span>
                            </label>
                            <?php 
                            $showLimit = 5;
                            foreach ($categories as $index => $cat): 
                                $isHidden = $index >= $showLimit;
                            ?>
                            <label class="filter-option <?= $isHidden ? 'filter-hidden' : '' ?>">
                                <input type="radio" name="category" value="<?= $cat['slug'] ?>" 
                                       <?= $category === $cat['slug'] ? 'checked' : '' ?>
                                       onchange="applyFilters()">
                                <span>
                                    <i class="fas <?= $cat['icon'] ?>"></i>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <?php if (count($categories) > $showLimit): ?>
                        <button class="btn-show-more" onclick="toggleFilterSection('categoryFilters', this)">
                            <i class="fas fa-chevron-down"></i>
                            Показать все (<?= count($categories) ?>)
                        </button>
                        <?php endif; ?>
                    </div>

                    <div class="filter-section">
                        <h3><i class="fas fa-building"></i> Производитель</h3>
                        <div class="filter-options" id="manufacturerFilters">
                            <?php 
                            $mfrShowLimit = 5;
                            $currentLetter = '';
                            $selectedManufacturers = !empty($manufacturer) ? explode(',', $manufacturer) : [];
                            
                            foreach ($manufacturers as $index => $mfr): 
                                $isHidden = $index >= $mfrShowLimit;
                                $logo = getManufacturerLogo($mfr['manufacturer'], $manufacturerLogos);
                                $firstLetter = mb_strtoupper(mb_substr($mfr['manufacturer'], 0, 1));
                                $isChecked = in_array($mfr['manufacturer'], $selectedManufacturers);
                                
                                // Show letter divider only for manufacturers without logos when expanded
                                if ($isHidden && !$logo && $firstLetter !== $currentLetter) {
                                    $currentLetter = $firstLetter;
                                    echo '<div class="manufacturer-letter filter-hidden">' . htmlspecialchars($currentLetter) . '</div>';
                                }
                            ?>
                            <label class="filter-option logo-option <?= $isHidden ? 'filter-hidden' : '' ?>" data-manufacturer="<?= htmlspecialchars($mfr['manufacturer']) ?>">
                                <input type="checkbox" class="manufacturer-checkbox" value="<?= htmlspecialchars($mfr['manufacturer']) ?>" 
                                       <?= $isChecked ? 'checked' : '' ?>
                                       onchange="toggleManufacturer(this)">
                                <span class="manufacturer-pill">
                                    <span class="manufacturer-logo-box">
                                        <?php if ($logo): ?>
                                            <img src="Company_logo/<?= $logo ?>" alt="<?= htmlspecialchars($mfr['manufacturer']) ?>">
                                        <?php else: ?>
                                            <span class="manufacturer-initial"><?= $firstLetter ?></span>
                                        <?php endif; ?>
                                    </span>
                                    <span class="manufacturer-name"><?= htmlspecialchars($mfr['manufacturer']) ?></span>
                                </span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <?php if (count($manufacturers) > $mfrShowLimit): ?>
                        <button class="btn-show-more" onclick="toggleManufacturers(this)">
                            <i class="fas fa-chevron-down"></i>
                            Показать все (<?= count($manufacturers) ?>)
                        </button>
                        <?php endif; ?>
                    </div>

                    <div class="filter-section">
                        <h3><i class="fas fa-ruble-sign"></i> Цена</h3>
                        <div class="price-range-inputs">
                            <input type="number" class="numeric-input price-range-input" id="minPriceInput" name="min_price" placeholder="Мин" 
                                   value="<?= htmlspecialchars($minPrice) ?>"
                                   oninput="applyFilters()">
                            <input type="number" class="numeric-input price-range-input" id="maxPriceInput" name="max_price" placeholder="Макс" 
                                   value="<?= htmlspecialchars($maxPrice) ?>"
                                   oninput="applyFilters()">
                        </div>
                        <div class="price-presets">
                            <label class="price-preset-option">
                                <input type="radio" name="price_preset" value="0-45000" onchange="applyPricePreset(this)">
                                <span>до 45000 ₽</span>
                            </label>
                            <label class="price-preset-option">
                                <input type="radio" name="price_preset" value="45000-70000" onchange="applyPricePreset(this)">
                                <span>45000–70000 ₽</span>
                            </label>
                            <label class="price-preset-option">
                                <input type="radio" name="price_preset" value="70000-125000" onchange="applyPricePreset(this)">
                                <span>70000–125000 ₽</span>
                            </label>
                            <label class="price-preset-option">
                                <input type="radio" name="price_preset" value="125000-999999" onchange="applyPricePreset(this)">
                                <span>125000 ₽ и дороже</span>
                            </label>
                            <label class="price-preset-option">
                                <input type="radio" name="price_preset" value="" checked onchange="applyPricePreset(this)">
                                <span>Неважно</span>
                        </div>
                    </div>

                    <div class="filter-section">
                        <h3><i class="fas fa-calendar-alt"></i> Год выпуска</h3>
                        <div class="year-inputs">
                            <div class="price-input-wrapper">
                                <span class="price-input-label">От</span>
                                <input type="number" class="numeric-input" name="min_year" placeholder="Например, 2018" 
                                       value="<?= htmlspecialchars($minYear) ?>"
                                       oninput="applyFilters()">
                            </div>
                            <div class="price-input-wrapper">
                                <span class="price-input-label">До</span>
                                <input type="number" class="numeric-input" name="max_year" placeholder="Например, 2024" 
                                       value="<?= htmlspecialchars($maxYear) ?>"
                                       oninput="applyFilters()">
                            </div>
                        </div>
                    </div>

                    <!-- Category-specific filters -->
                    <div id="categorySpecificFilters"></div>

                    <button type="button" class="btn btn-secondary" onclick="resetFilters()">
                        <i class="fas fa-rotate-right"></i>
                        Сбросить фильтры
                    </button>
                </aside>

                <!-- Products Grid -->
                <div class="catalog-content">
                    <!-- Search Bar -->
                    <div class="catalog-search-bar">
                        <div class="search-wrapper">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="search" class="catalog-search-input" 
                                   placeholder="Поиск по названию или модели..." 
                                   value="<?= htmlspecialchars($search) ?>"
                                   oninput="applyFilters()">
                        </div>
                    </div>

                    <div class="loading-overlay" id="loadingOverlay">
                        <div class="loading-content">
                            <div class="loading-spinner-modern">
                                <div class="spinner-ring"></div>
                                <div class="spinner-ring"></div>
                                <div class="spinner-ring"></div>
                            </div>
                            <div class="loading-text">
                                <span class="loading-title">Загрузка компонентов</span>
                                <span class="loading-subtitle">Применяем фильтры...</span>
                            </div>
                        </div>
                    </div>
                    <div class="catalog-toolbar">
                        <div class="toolbar-left">
                            <div class="results-count">
                                Показано: <strong><span id="loadedCount"><?= count($components) ?></span></strong> из <strong><span id="totalCount"><?= $totalCount ?></span></strong> товаров
                            </div>
                            <?php 
                            $activeFiltersCount = 0;
                            if ($category) $activeFiltersCount++;
                            if ($manufacturer) $activeFiltersCount++;
                            if ($minPrice || $maxPrice) $activeFiltersCount++;
                            if ($minYear || $maxYear) $activeFiltersCount++;
                            if ($search) $activeFiltersCount++;
                            
                            if ($activeFiltersCount > 0): ?>
                            <div class="active-filters-badge">
                                <i class="fas fa-filter"></i>
                                <span><?= $activeFiltersCount ?> активных фильтров</span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="sort-controls">
                            <label><i class="fas fa-sort"></i> Сортировка:</label>
                            <select name="sort" onchange="applyFilters()">
                                <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>По названию (А-Я)</option>
                                <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Цена (возр.)</option>
                                <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Цена (убыв.)</option>
                                <option value="performance_desc" <?= $sort === 'performance_desc' ? 'selected' : '' ?>>По производительности</option>
                            </select>
                        </div>
                    </div>

                    <div class="products-grid" data-page="1" data-total="<?= $totalCount ?>" data-loaded="<?= count($components) ?>">
                        <?php if (empty($components)): ?>
                            <div class="no-results">
                                <i class="fas fa-box-open fa-4x"></i>
                                <h3>Ничего не найдено</h3>
                                <p>Попробуйте изменить параметры фильтрации</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($components as $component): ?>
                            <?php
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
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Infinite Scroll Loader -->
                    <?php if (count($components) < $totalCount): ?>
                    <div class="infinite-scroll-loader" id="infiniteLoader">
                        <div class="loader-content">
                            <div class="loader-spinner">
                                <div class="spinner-dot"></div>
                                <div class="spinner-dot"></div>
                                <div class="spinner-dot"></div>
                            </div>
                            <span class="loader-text">Загружаем еще товары...</span>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <div class="component-modal" id="componentModal" aria-hidden="true">
        <div class="component-modal-overlay" data-modal-close></div>
        <div class="component-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="componentModalTitle">
            <button class="component-modal-close" type="button" data-modal-close aria-label="Закрыть">
                <i class="fas fa-times"></i>
            </button>
            <div class="component-modal-header">
                <div class="component-modal-title">
                    <span class="component-modal-category" id="componentModalCategory"></span>
                    <h2 id="componentModalTitle">Компонент</h2>
                    <p id="componentModalSubtitle"></p>
                </div>
                <div class="component-modal-price" id="componentModalPrice"></div>
            </div>
            <div class="component-modal-tabs">
                <button type="button" class="modal-tab is-active" data-tab="specs">Характеристики</button>
                <button type="button" class="modal-tab" data-tab="reviews">Обзоры</button>
            </div>
            <div class="component-modal-body">
                <div class="component-modal-panel is-active" data-panel="specs">
                    <div class="component-modal-specs">
                        <h3>Характеристики</h3>
                        <div class="specs-grid" id="componentModalSpecs"></div>
                    </div>
                </div>
                <div class="component-modal-panel" data-panel="reviews">
                    <div class="component-modal-reviews">
                        <div class="component-modal-reviews-header">
                        <h3>Обзоры</h3>
                            <span class="reviews-summary" id="componentModalReviewsSummary"></span>
                        </div>
                        <div class="reviews-list" id="componentModalReviews"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
                    <script>
                        window.ENABLE_REMOTE_IMAGES = <?= defined('ENABLE_REMOTE_IMAGES') && ENABLE_REMOTE_IMAGES ? 'true' : 'false' ?>;
                    </script>
                    <script src="js/catalog.js"></script>
    <script src="js/sticky-filters.js"></script>
</body>
</html>

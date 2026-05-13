<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once '../config.php';
require_once '../includes/components_union.php';

header('Content-Type: application/json');

$componentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

if ($componentId <= 0 || $categoryId <= 0) {
    echo json_encode(['success' => false, 'error' => 'invalid_parameters']);
    exit;
}

function format_spec_value($value) {
    if ($value === null || $value === '') {
        return null;
    }
    if (is_array($value)) {
        return implode(', ', $value);
    }
    return (string)$value;
}

function normalize_label($label) {
    $label = mb_strtolower(trim((string)$label), 'UTF-8');
    $label = str_replace(['ё', '•', ':', '.', ','], ['е', '', '', '', ''], $label);
    $label = preg_replace('/\s+/', ' ', $label);
    return $label;
}

function normalize_value($value) {
    $value = mb_strtolower(trim((string)$value), 'UTF-8');
    $value = str_replace(['ё', '•', ','], ['е', '', '.'], $value);
    $value = preg_replace('/\s+/', ' ', $value);
    return $value;
}

function build_specs($component) {
    $sections = [];
    $categoryId = (int)($component['category_id'] ?? 0);
    $seen = [];

    $add = function($section, $label, $value) use (&$sections, &$seen) {
        $value = format_spec_value($value);
        if ($value === null || $value === '') {
            return;
        }
        $labelKey = normalize_label($label);
        $valueKey = normalize_value($value);
        $key = $labelKey . '::' . $valueKey;
        if (isset($seen[$key]) || isset($seen[$labelKey])) {
            return;
        }
        $seen[$key] = true;
        $seen[$labelKey] = true;
        if (!isset($sections[$section])) {
            $sections[$section] = [];
        }
        $sections[$section][] = ['label' => $label, 'value' => $value];
    };

    $add('Основное', 'Производитель', $component['manufacturer'] ?? null);
    $add('Основное', 'Модель', $component['model'] ?? null);
    $add('Основное', 'Рейтинг', isset($component['rating']) ? number_format((float)$component['rating'], 2) : null);

    if ($categoryId === 1) { // CPU
        $add('Производительность', 'Ядра', $component['cpu_cores'] ?? null);
        $add('Производительность', 'Потоки', $component['cpu_threads'] ?? null);
        $add('Производительность', 'Базовая частота', isset($component['cpu_base_clock']) ? $component['cpu_base_clock'] . ' ГГц' : null);
        $add('Совместимость', 'Сокет', $component['socket_type'] ?? null);
        $add('Питание', 'TDP', isset($component['power_consumption']) ? $component['power_consumption'] . ' Вт' : null);
    } elseif ($categoryId === 2) { // GPU
        $add('Память', 'Объём', isset($component['gpu_memory']) ? $component['gpu_memory'] . ' ГБ' : null);
        $add('Память', 'Тип', $component['gpu_memory_type'] ?? null);
        $add('Интерфейс', 'PCIe', $component['pcie_version'] ?? null);
        $add('Питание', 'Энергопотребление', isset($component['power_consumption']) ? $component['power_consumption'] . ' Вт' : null);
    } elseif ($categoryId === 3) { // Motherboard
        $add('Плата', 'Форм-фактор', $component['mobo_form_factor'] ?? null);
        $add('Плата', 'Чипсет', $component['mobo_chipset'] ?? null);
        $add('Память', 'Тип памяти', $component['mobo_ram_type'] ?? null);
        $add('Память', 'Слотов RAM', $component['mobo_ram_slots'] ?? null);
        $add('Накопители', 'Слотов M.2', $component['mobo_m2_slots'] ?? null);
        $add('Совместимость', 'Сокет', $component['socket_type'] ?? null);
    } elseif ($categoryId === 4) { // RAM
        $add('Память', 'Объём', isset($component['ram_capacity']) ? $component['ram_capacity'] . ' ГБ' : null);
        $add('Память', 'Тип', $component['ram_type'] ?? null);
        $add('Память', 'Частота', isset($component['ram_speed']) ? $component['ram_speed'] . ' МГц' : null);
    } elseif ($categoryId === 5) { // Storage
        $add('Накопитель', 'Объём', isset($component['storage_capacity']) ? $component['storage_capacity'] . ' ГБ' : null);
        $add('Накопитель', 'Тип', $component['storage_type'] ?? null);
        $add('Накопитель', 'Интерфейс', $component['storage_interface'] ?? null);
    } elseif ($categoryId === 6) { // PSU
        $add('Питание', 'Мощность', isset($component['psu_wattage']) ? $component['psu_wattage'] . ' Вт' : null);
        $add('Питание', 'Сертификат', $component['psu_efficiency'] ?? null);
    } elseif ($categoryId === 7) { // Case
        $add('Корпус', 'Форм-фактор', $component['case_form_factor'] ?? null);
        $add('Совместимость', 'Макс. длина GPU', isset($component['case_max_gpu_length']) ? $component['case_max_gpu_length'] . ' мм' : null);
        $add('Совместимость', 'Макс. высота кулера', isset($component['case_max_cooler_height']) ? $component['case_max_cooler_height'] . ' мм' : null);
    } elseif ($categoryId === 8) { // Cooling
        $add('Охлаждение', 'Тип', $component['cooler_type'] ?? null);
        $add('Охлаждение', 'Высота', isset($component['cooler_height']) ? $component['cooler_height'] . ' мм' : null);
        $add('Охлаждение', 'TDP', isset($component['cooler_tdp']) ? $component['cooler_tdp'] . ' Вт' : null);
        $add('Совместимость', 'Сокеты', $component['cooler_socket'] ?? null);
    }

    $specsRaw = $component['specs'] ?? null;
    if ($specsRaw) {
        $decoded = json_decode($specsRaw, true);
        if (is_array($decoded)) {
            $skipKeys = [
                'cores', 'threads', 'cpu_cores', 'cpu_threads',
                'base_clock', 'boost_clock', 'cpu_base_clock', 'cpu_boost_clock',
                'ram_type', 'ram_speed', 'ram_capacity',
                'storage_capacity', 'storage_type', 'storage_interface',
                'psu_wattage', 'psu_efficiency',
                'cooler_type', 'cooler_height', 'cooler_tdp', 'cooler_socket',
                'mobo_form_factor', 'mobo_chipset', 'mobo_ram_type', 'mobo_ram_slots', 'mobo_m2_slots',
                'socket', 'socket_type',
                'gpu_memory', 'gpu_memory_type', 'pcie', 'pcie_version',
                'capacity', 'interface', 'form_factor', 'read_speed', 'write_speed',
                'max_gpu_length', 'max_cooler_height', 'fan_slots', 'side_panel', 'usb_ports',
                'ram_slots', 'max_ram', 'm2_slots', 'pcie_slots',
                'type', 'speed', 'cas_latency', 'modules',
                'radiator_size', 'fan_count', 'fan_size', 'pump_speed',
                'memory_bus', 'xe_cores', 'ray_tracing_units'
            ];
            $labelMap = [
                'capacity' => 'Объём',
                'interface' => 'Интерфейс',
                'form_factor' => 'Форм-фактор',
                'read_speed' => 'Скорость чтения',
                'write_speed' => 'Скорость записи',
                'boost_clock' => 'Boost частота',
                'base_clock' => 'Базовая частота',
                'cores' => 'Ядра',
                'threads' => 'Потоки',
                'memory' => 'Память',
                'memory_type' => 'Тип памяти',
                'cache' => 'Кэш',
                'graphics' => 'Графика',
                'socket' => 'Сокет',
                'tdp' => 'TDP',
                'ram_type' => 'Тип памяти',
                'ram_speed' => 'Частота памяти',
                'max_tdp' => 'Макс. TDP',
                'noise_level' => 'Уровень шума',
                'dimensions' => 'Габариты',
                'weight' => 'Вес',
                'pci' => 'PCIe',
                'pcie' => 'PCIe',
                'memory_bus' => 'Шина памяти',
                'xe_cores' => 'Xe-ядра',
                'ray_tracing_units' => 'Блоки трассировки лучей',
                'fan_slots' => 'Места под вентиляторы',
                'side_panel' => 'Боковая панель',
                'usb_ports' => 'USB-порты',
                'ram_slots' => 'Слотов RAM',
                'max_ram' => 'Макс. объём RAM',
                'm2_slots' => 'Слотов M.2',
                'pcie_slots' => 'Слотов PCIe',
                'type' => 'Тип',
                'speed' => 'Частота',
                'cas_latency' => 'CAS latency',
                'modules' => 'Комплект',
                'radiator_size' => 'Размер радиатора',
                'fan_count' => 'Количество вентиляторов',
                'fan_size' => 'Размер вентилятора',
                'pump_speed' => 'Скорость помпы'
            ];
            foreach ($decoded as $key => $value) {
                $normalizedKey = normalize_label($key);
                $normalizedKey = str_replace([' ', '-'], '_', $normalizedKey);
                $normalizedKey = preg_replace('/_+/', '_', $normalizedKey);
                if (in_array($normalizedKey, $skipKeys, true)) {
                    continue;
                }
                $label = $labelMap[$normalizedKey] ?? ucfirst(str_replace('_', ' ', (string)$key));
                if (preg_match('/^[a-z0-9 _\\-]+$/i', $label)) {
                    $label = str_replace('_', ' ', $label);
                }
                $label = preg_replace('/\\bmhz\\b/i', 'МГц', $label);
                $label = preg_replace('/\\bgb\\b/i', 'ГБ', $label);
                $label = preg_replace('/\\bmm\\b/i', 'мм', $label);
                $add('Дополнительно', $label, $value);
            }
        }
    }

    $result = [];
    foreach ($sections as $section => $items) {
        $result[] = ['title' => $section, 'items' => $items];
    }

    return $result;
}

try {
    $componentsSource = getComponentsUnionSource();
    $stmt = $pdo->prepare("SELECT c.*, cat.name AS category_name FROM {$componentsSource} AS c JOIN categories cat ON c.category_id = cat.id WHERE c.id = ? AND c.category_id = ? LIMIT 1");
    $stmt->execute([$componentId, $categoryId]);
    $component = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$component) {
        echo json_encode(['success' => false, 'error' => 'not_found']);
        exit;
    }

    $reviewStmt = $pdo->prepare("
        SELECT cr.id, cr.rating, cr.title, cr.summary, cr.pros, cr.cons, cr.usage_context, cr.recommended, cr.created_at, u.username, u.avatar
        FROM component_reviews cr
        JOIN users u ON u.id = cr.user_id
        WHERE cr.component_id = ? AND cr.component_category_id = ? AND cr.status = 'published'
        ORDER BY cr.created_at DESC
        LIMIT 5
    ");
    $reviewStmt->execute([$componentId, $categoryId]);
    $reviews = $reviewStmt->fetchAll(PDO::FETCH_ASSOC);

    $countStmt = $pdo->prepare("SELECT COUNT(*) AS total, AVG(rating) AS avg_rating FROM component_reviews WHERE component_id = ? AND component_category_id = ? AND status = 'published'");
    $countStmt->execute([$componentId, $categoryId]);
    $reviewStats = $countStmt->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0, 'avg_rating' => 0];

    echo json_encode([
        'success' => true,
        'component' => [
            'id' => (int)$component['id'],
            'name' => $component['name'],
            'manufacturer' => $component['manufacturer'],
            'model' => $component['model'],
            'price' => $component['price'],
            'category' => $component['category_name'],
            'specs' => build_specs($component)
        ],
        'reviews' => $reviews,
        'review_stats' => [
            'total' => (int)$reviewStats['total'],
            'avg_rating' => $reviewStats['avg_rating'] ? round((float)$reviewStats['avg_rating'], 1) : 0
        ]
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'db_error']);
}
?>

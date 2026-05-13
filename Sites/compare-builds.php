<?php
session_start();
require_once 'config.php';
require_once 'includes/components_union.php';

$idsParam = $_GET['ids'] ?? '';
$ids = array_values(array_unique(array_filter(array_map('intval', explode(',', $idsParam)))));
$ids = array_slice($ids, 0, 3);

if (count($ids) < 2) {
    header('Location: builds.php');
    exit;
}

$currentUserId = $_SESSION['user_id'] ?? 0;
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$orderField = implode(',', $ids);

try {
    $sql = "SELECT b.*, u.username, u.avatar,
                (SELECT COUNT(*) FROM build_likes bl WHERE bl.build_id = b.id) as likes_count,
                (SELECT COUNT(*) FROM build_comments bc WHERE bc.build_id = b.id) as comments_count
            FROM user_builds b
            LEFT JOIN users u ON b.user_id = u.id
            WHERE b.id IN ($placeholders)
              AND (b.is_public = 1 OR b.user_id = ?)
            ORDER BY FIELD(b.id, $orderField)";

    $stmt = $pdo->prepare($sql);
    $params = array_merge($ids, [$currentUserId]);
    $stmt->execute($params);
    $builds = $stmt->fetchAll();
} catch (PDOException $e) {
    $builds = [];
}

if (count($builds) < 2) {
    $message = 'Сравнение доступно только для публичных конфигураций или ваших собственных. Выберите другие сборки.';
    include 'includes/header.php';
    echo "<main class='compare-page'><div class='container'><div class='compare-empty'><p>" . htmlspecialchars($message) . "</p><a class='btn-go-back' href='builds.php'>Вернуться к сборкам</a></div></div></main>";
    include 'includes/footer.php';
    exit;
}

$purposeLabels = [
    'gaming' => 'Игровая',
    'work' => 'Рабочая / офисная',
    'streaming' => 'Для стриминга',
    'editing' => 'Для монтажа',
    'other' => 'Универсальная'
];

$categoryConfigs = [
    1 => ['slug' => 'cpu', 'label' => 'Процессор', 'icon' => 'fa-microchip', 'metric_type' => 'higher', 'metric_keys' => ['performance_score', 'cpu_cores', 'cpu_threads', 'cpu_base_clock'], 'unit' => 'баллов'],
    2 => ['slug' => 'gpu', 'label' => 'Видеокарта', 'icon' => 'fa-display', 'metric_type' => 'higher', 'metric_keys' => ['performance_score', 'gpu_memory'], 'unit' => 'баллов'],
    3 => ['slug' => 'motherboard', 'label' => 'Материнская плата', 'icon' => 'fa-diagram-project', 'metric_type' => 'higher', 'metric_keys' => ['mobo_ram_slots', 'mobo_m2_slots'], 'unit' => 'слотов'],
    4 => ['slug' => 'ram', 'label' => 'Оперативная память', 'icon' => 'fa-memory', 'metric_type' => 'higher', 'metric_keys' => ['ram_capacity', 'ram_speed'], 'unit' => 'ГБ'],
    5 => ['slug' => 'storage', 'label' => 'Хранилище', 'icon' => 'fa-hard-drive', 'metric_type' => 'higher', 'metric_keys' => ['storage_capacity'], 'unit' => 'ГБ'],
    6 => ['slug' => 'psu', 'label' => 'Блок питания', 'icon' => 'fa-plug', 'metric_type' => 'higher', 'metric_keys' => ['psu_wattage'], 'unit' => 'Вт'],
    7 => ['slug' => 'case', 'label' => 'Корпус', 'icon' => 'fa-box', 'metric_type' => null, 'metric_keys' => []],
    8 => ['slug' => 'cooling', 'label' => 'Охлаждение', 'icon' => 'fa-fan', 'metric_type' => 'higher', 'metric_keys' => ['cooler_tdp'], 'unit' => 'Вт']
];

$buildMap = [];
foreach ($builds as &$buildRef) {
    $purposeKey = $buildRef['purpose'] ?? 'other';
    $buildRef['purpose_label'] = $purposeLabels[$purposeKey] ?? $purposeLabels['other'];
    $buildRef['components_snapshot'] = json_decode($buildRef['components'], true) ?? [];
    $buildRef['component_count'] = isset($buildRef['components_snapshot']['components'])
        ? count($buildRef['components_snapshot']['components'])
        : 0;
    $buildMap[$buildRef['id']] = $buildRef;
}
unset($buildRef);

$buildIds = array_column($builds, 'id');
$componentUnion = getComponentsUnionSource();
$componentsPerBuild = [];
$componentMatrix = [];
$performanceScores = array_fill_keys($buildIds, 0);
$storageTotals = array_fill_keys($buildIds, 0);
$ramTotals = array_fill_keys($buildIds, 0);
$storageDriveCounts = array_fill_keys($buildIds, 0);
$categoryPresenceCounts = array_fill_keys($buildIds, 0);
$coveragePercentages = array_fill_keys($buildIds, 0);
$valueScores = array_fill_keys($buildIds, null);
$totalCategories = count($categoryConfigs);

try {
    $componentSql = "SELECT bc.build_id, c.*
                    FROM build_components bc
                    JOIN {$componentUnion} c ON bc.component_id = c.id
                    WHERE bc.build_id IN ($placeholders)";
    $stmt = $pdo->prepare($componentSql);
    $stmt->execute($buildIds);
    $componentRows = $stmt->fetchAll();

    foreach ($componentRows as $row) {
        $componentsPerBuild[$row['build_id']][] = $row;
    }
} catch (PDOException $e) {
    $componentsPerBuild = [];
}

foreach ($builds as $buildInfo) {
    $buildId = $buildInfo['id'];
    $uniqueCategories = [];
    $componentsList = $componentsPerBuild[$buildId] ?? [];

    foreach ($componentsList as $component) {
        $categoryId = (int)($component['category_id'] ?? 0);
        if ($categoryId === 0) {
            continue;
        }
        $uniqueCategories[$categoryId] = true;

        if (!empty($component['performance_score'])) {
            $performanceScores[$buildId] += (int)$component['performance_score'];
        }

        if ($categoryId === 4 && isset($component['ram_capacity'])) {
            $ramTotals[$buildId] += (int)$component['ram_capacity'];
        }

        if ($categoryId === 5 && isset($component['storage_capacity'])) {
            $storageTotals[$buildId] += (int)$component['storage_capacity'];
            $storageDriveCounts[$buildId]++;
        }
    }

    $categoryPresenceCounts[$buildId] = count($uniqueCategories);
    $coveragePercentages[$buildId] = $totalCategories > 0
        ? round(($categoryPresenceCounts[$buildId] / $totalCategories) * 100)
        : 0;

    if ($performanceScores[$buildId] > 0 && !empty($buildInfo['total_price'])) {
        $valueScores[$buildId] = round($buildInfo['total_price'] / $performanceScores[$buildId], 2);
    }
}

$insights = [];
$priceLeader = null;
$perfLeader = null;
$storageLeader = null;

foreach ($builds as $buildInfo) {
    if ($priceLeader === null || $buildInfo['total_price'] < $priceLeader['total_price']) {
        $priceLeader = $buildInfo;
    }

    $buildId = $buildInfo['id'];
    if ($perfLeader === null || $performanceScores[$buildId] > $performanceScores[$perfLeader['id']]) {
        $perfLeader = $buildInfo;
    }

    if ($storageLeader === null || $storageTotals[$buildId] > $storageTotals[$storageLeader['id']]) {
        $storageLeader = $buildInfo;
    }
}

if ($priceLeader) {
    $insights[] = sprintf(
        '<strong>%s</strong> — самая доступная сборка (%s).',
        htmlspecialchars($priceLeader['build_name']),
        formatPrice($priceLeader['total_price'])
    );
}

if ($perfLeader && $performanceScores[$perfLeader['id']] > 0) {
    $insights[] = sprintf(
        '<strong>%s</strong> лидирует по суммарной мощности компонентов (индекс %s).',
        htmlspecialchars($perfLeader['build_name']),
        number_format($performanceScores[$perfLeader['id']])
    );
}

if ($storageLeader && $storageTotals[$storageLeader['id']] > 0) {
    $insights[] = sprintf(
        '<strong>%s</strong> предлагает максимальный объём хранилища — %s ГБ в %s накопителях.',
        htmlspecialchars($storageLeader['build_name']),
        number_format($storageTotals[$storageLeader['id']]),
        $storageDriveCounts[$storageLeader['id']]
    );
}

function extractMetricValue(array $component, array $keys)
{
    foreach ($keys as $key) {
        if (isset($component[$key]) && is_numeric($component[$key])) {
            return (float) $component[$key];
        }
    }
    return null;
}

function determineWinners(array $values, ?string $type): array
{
    $filtered = array_filter($values, static fn($value) => $value !== null);
    if (empty($filtered) || $type === null) {
        return [];
    }

    $bestValue = $type === 'lower' ? min($filtered) : max($filtered);
    $bestKeys = array_keys(array_filter($values, static fn($value) => $value === $bestValue));
    if (count($bestKeys) > 1) {
        return [];
    }
    return $bestKeys;
}

function formatMetricBadge($value, ?string $unit)
{
    if ($value === null) {
        return '';
    }
    $formatted = is_float($value) ? round($value, 1) : $value;
    return $unit ? $formatted . ' ' . $unit : $formatted;
}

function extractRamModules(?string $specs): ?string
{
    if (!$specs) {
        return null;
    }

    $data = json_decode($specs, true);
    if (!is_array($data)) {
        return null;
    }

    $modules = $data['modules'] ?? $data['module_count'] ?? $data['sticks'] ?? null;
    if (!$modules) {
        return null;
    }

    return preg_replace('/\s+/', '', (string)$modules);
}

function describeComponent(array $component, string $slug): string
{
    switch ($slug) {
        case 'cpu':
            $cores = $component['cpu_cores'] ?? null;
            $threads = $component['cpu_threads'] ?? null;
            $clock = $component['cpu_base_clock'] ?? null;
            return trim(sprintf('%sC / %sT%s', $cores ?: '-', $threads ?: '-', $clock ? ' • ' . $clock . ' ГГц' : ''));
        case 'gpu':
            $memory = $component['gpu_memory'] ?? null;
            $type = $component['gpu_memory_type'] ?? null;
            return trim(sprintf('%s %s', $memory ?: '', $type ?: ''));
        case 'ram':
            $capacity = $component['ram_capacity'] ?? null;
            $speed = $component['ram_speed'] ?? null;
            $type = $component['ram_type'] ?? null;
            $modules = extractRamModules($component['specs'] ?? null);
            $base = trim(sprintf('%s ГБ • %s МГц • %s', $capacity ?: '-', $speed ?: '-', $type ?: '-'));
            return $modules ? $base . ' • ' . $modules : $base;
        case 'storage':
            $capacity = $component['storage_capacity'] ?? null;
            $type = $component['storage_type'] ?? null;
            return trim(sprintf('%s • %s', $capacity ? $capacity . ' ГБ' : '-', $type ?: '-'));
        case 'psu':
            $watt = $component['psu_wattage'] ?? null;
            $eff = $component['psu_efficiency'] ?? null;
            return trim(sprintf('%s Вт %s', $watt ?: '-', $eff ? '• ' . $eff : ''));
        case 'cooling':
            $type = $component['cooler_type'] ?? null;
            $tdp = $component['cooler_tdp'] ?? null;
            return trim(sprintf('%s %s', $type ?: '-', $tdp ? '• ' . $tdp . ' Вт' : ''));
        default:
            return trim($component['manufacturer'] ?? '');
    }
}

$globalMetrics = [
    'price' => ['label' => 'Стоимость', 'type' => 'lower', 'format' => fn($v) => formatPrice($v)],
    'total_power' => ['label' => 'Суммарное потребление', 'type' => 'lower', 'format' => fn($v) => $v ? $v . ' Вт' : '—'],
    'likes' => ['label' => 'Лайков', 'type' => 'higher', 'field' => 'likes_count', 'format' => fn($v) => (int) $v],
    'comments' => ['label' => 'Комментариев', 'type' => 'higher', 'field' => 'comments_count', 'format' => fn($v) => (int) $v]
];

?><!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Сравнение сборок - <?= SITE_NAME ?></title>
    <script>
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
    <link rel="stylesheet" href="css/compare.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<?php include 'includes/header.php'; ?>

<main class="compare-page">
    <div class="container">
        <div class="compare-intro">
            <div>
                <p class="compare-meta"><?= count($builds) ?> <?= count($builds) === 2 ? 'сборки' : 'сборок' ?> выбраны для сравнения</p>
                <h1>Сравнение сборок</h1>
                <p>HyperPC подсвечивает сильные стороны каждого ПК и помогает оценить цену, набор компонентов и общий баланс.</p>
            </div>
            <div class="compare-controls">
                <a href="builds.php" class="btn-outline"><i class="fas fa-arrow-left"></i> Назад к сборкам</a>
                <button class="btn-clear" id="resetCompare"><i class="fas fa-broom"></i> Сбросить выбор</button>
            </div>
        </div>

        <div class="build-preview-grid">
            <?php foreach ($builds as $build): ?>
                <?php $buildId = $build['id']; ?>
                <div class="build-preview-card">
                    <div class="card-header">
                        <div>
                            <h2><?= htmlspecialchars($build['build_name']) ?></h2>
                            <div class="card-tags">
                                <span class="tag tag-purpose"><i class="fas fa-bullseye"></i> <?= htmlspecialchars($build['purpose_label']) ?></span>
                                <span class="tag"><i class="fas fa-shapes"></i> <?= $categoryPresenceCounts[$buildId] ?? 0 ?>/<?= $totalCategories ?> категорий</span>
                                <span class="tag"><i class="fas fa-chart-pie"></i> <?= $coveragePercentages[$buildId] ?? 0 ?>%</span>
                            </div>
                        </div>
                        <span class="price"><?= formatPrice($build['total_price']) ?></span>
                    </div>
                    <div class="card-meta">
                        <span><i class="fas fa-user"></i> <?= htmlspecialchars($build['username'] ?? 'Пользователь') ?></span>
                        <span><i class="fas fa-bolt"></i> <?= $build['total_power'] ? $build['total_power'] . ' Вт' : '—' ?></span>
                        <span><i class="fas fa-heart"></i> <?= (int) $build['likes_count'] ?></span>
                    </div>
                    <ul class="mini-specs">
                        <li>
                            <span>RAM</span>
                            <strong><?= $ramTotals[$buildId] ? number_format($ramTotals[$buildId]) . ' ГБ' : '—' ?></strong>
                        </li>
                        <li>
                            <span>Хранилище</span>
                            <strong>
                                <?= $storageTotals[$buildId] ? number_format($storageTotals[$buildId]) . ' ГБ' : '—' ?>
                                <?= $storageDriveCounts[$buildId] ? ' · ' . $storageDriveCounts[$buildId] . ' шт.' : '' ?>
                            </strong>
                        </li>
                        <li>
                            <span>Индекс</span>
                            <strong><?= $performanceScores[$buildId] ? number_format($performanceScores[$buildId]) : '—' ?></strong>
                        </li>
                        <li>
                            <span>Компонентов</span>
                            <strong><?= $build['component_count'] ?: ($categoryPresenceCounts[$buildId] ?? 0) ?></strong>
                        </li>
                    </ul>
                </div>
            <?php endforeach; ?>
        </div>

        <?php
        $kpiCards = [
            [
                'label' => 'Покрытие компонентов',
                'icon' => 'fa-layer-group',
                'unit' => '%',
                'values' => $coveragePercentages,
                'type' => 'higher',
                'decimals' => 0,
                'description' => 'доля категорий, присутствующих в сборке'
            ],
            [
                'label' => 'Суммарный объём RAM',
                'icon' => 'fa-memory',
                'unit' => 'ГБ',
                'values' => $ramTotals,
                'type' => 'higher',
                'decimals' => 0,
                'description' => 'все установленные модули памяти'
            ],
            [
                'label' => 'Хранилище',
                'icon' => 'fa-hard-drive',
                'unit' => 'ГБ',
                'values' => $storageTotals,
                'type' => 'higher',
                'decimals' => 0,
                'description' => 'совокупный объём всех накопителей'
            ],
            [
                'label' => 'Количество накопителей',
                'icon' => 'fa-server',
                'unit' => 'шт.',
                'values' => $storageDriveCounts,
                'type' => 'higher',
                'decimals' => 0,
                'description' => 'SSD + HDD в конфигурации'
            ],
            [
                'label' => 'Индекс производительности',
                'icon' => 'fa-gauge-high',
                'unit' => 'балл',
                'values' => $performanceScores,
                'type' => 'higher',
                'decimals' => 0,
                'description' => 'сумма рейтингов ключевых компонентов'
            ],
            [
                'label' => 'Цена за балл',
                'icon' => 'fa-scale-balanced',
                'unit' => '₽/балл',
                'values' => $valueScores,
                'type' => 'lower',
                'decimals' => 2,
                'description' => 'эффективность вложений'
            ],
        ];
        ?>

        <div class="compare-stats-grid">
            <?php foreach ($kpiCards as $card):
                $values = $card['values'];
                $metricType = $card['type'] ?? null;
                $winners = $metricType ? determineWinners($values, $metricType === 'lower' ? 'lower' : 'higher') : [];
            ?>
                <div class="kpi-card">
                    <div class="kpi-header">
                        <i class="fas <?= $card['icon'] ?>"></i>
                        <div>
                            <p><?= $card['label'] ?></p>
                            <span><?= $card['description'] ?></span>
                        </div>
                    </div>
                    <div class="kpi-values">
                        <?php foreach ($builds as $build):
                            $value = $values[$build['id']] ?? null;
                            $isBest = $value !== null ? in_array($build['id'], $winners, true) : false;
                            $decimals = $card['decimals'] ?? 0;
                            $formatted = $value === null ? '—' : number_format($value, $decimals) . ($card['unit'] ? ' ' . $card['unit'] : '');
                        ?>
                            <div class="kpi-value <?= $isBest ? 'is-best' : '' ?>">
                                <span><?= htmlspecialchars($build['build_name']) ?></span>
                                <strong><?= $formatted ?></strong>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="metrics-row">
            <?php foreach ($globalMetrics as $key => $settings):
                $values = [];
                foreach ($builds as $build) {
                    $field = $settings['field'] ?? $key;
                    $values[$build['id']] = isset($build[$field]) ? (float)$build[$field] : null;
                }
                $winners = determineWinners($values, $settings['type']);
            ?>
                <div class="metric-card">
                    <p class="metric-label"><?= $settings['label'] ?></p>
                    <div class="metric-values">
                        <?php foreach ($builds as $build):
                            $field = $settings['field'] ?? $key;
                            $value = $build[$field] ?? null;
                            $formatted = $value !== null ? $settings['format']($value) : '—';
                            $isBest = in_array($build['id'], $winners, true);
                        ?>
                            <div class="metric-value <?= $isBest ? 'is-best' : '' ?>">
                                <span><?= htmlspecialchars($build['build_name']) ?></span>
                                <strong><?= $formatted ?></strong>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="compare-table">
            <div class="table-header">
                <div class="table-cell category">Компоненты</div>
                <?php foreach ($builds as $build): ?>
                    <div class="table-cell build-name"><?= htmlspecialchars($build['build_name']) ?></div>
                <?php endforeach; ?>
            </div>

            <?php foreach ($categoryConfigs as $categoryId => $config):
                $values = [];
                foreach ($builds as $build) {
                    $componentsForCategory = [];
                    if (!empty($componentsPerBuild[$build['id']])) {
                        foreach ($componentsPerBuild[$build['id']] as $item) {
                            if ((int)$item['category_id'] === $categoryId) {
                                $componentsForCategory[] = $item;
                                if ($config['slug'] !== 'storage') {
                                    break;
                                }
                            }
                        }
                    }

                    $metricValue = null;
                    if (!empty($componentsForCategory)) {
                        if ($config['slug'] === 'storage') {
                            $metricValue = array_reduce($componentsForCategory, static function ($carry, $item) {
                                return $carry + (int)($item['storage_capacity'] ?? 0);
                            }, 0);
                        } else {
                            $metricValue = extractMetricValue($componentsForCategory[0], $config['metric_keys']);
                        }
                    }

                    $values[$build['id']] = $metricValue;
                    $componentMatrix[$categoryId][$build['id']] = [
                        'components' => $componentsForCategory,
                        'metric' => $metricValue
                    ];
                }
                $winners = determineWinners($values, $config['metric_type']);
            ?>
                <div class="table-row">
                    <div class="table-cell category">
                        <i class="fas <?= $config['icon'] ?>"></i>
                        <span><?= $config['label'] ?></span>
                    </div>
                    <?php foreach ($builds as $build):
                        $cell = $componentMatrix[$categoryId][$build['id']] ?? null;
                        $componentsList = $cell['components'] ?? [];
                        $metricValue = $cell['metric'] ?? null;
                        $isWinner = in_array($build['id'], $winners, true);
                    ?>
                        <div class="table-cell <?= $isWinner ? 'is-better' : '' ?>">
                            <?php if (!empty($componentsList)): ?>
                                <div class="component-stack">
                                    <?php foreach ($componentsList as $index => $componentItem):
                                        if ($index === 2 && count($componentsList) > 2): ?>
                                            <span class="component-extra">+<?= count($componentsList) - 2 ?> дополнительно</span>
                                            <?php break; ?>
                                        <?php endif; ?>
                                        <div class="component-pill">
                                            <strong><?= htmlspecialchars($componentItem['name']) ?></strong>
                                            <span><?= htmlspecialchars(describeComponent($componentItem, $config['slug'])) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php if ($metricValue !== null): ?>
                                    <span class="metric-badge"><?= formatMetricBadge($metricValue, $config['unit'] ?? null) ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="component-empty">—</div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (!empty($insights)): ?>
            <div class="insights-panel">
                <h3><i class="fas fa-chart-line"></i> Инсайты HyperPC</h3>
                <ul>
                    <?php foreach ($insights as $insight): ?>
                        <li><?= $insight ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="compare-footer">
            <div class="tips">
                <h3><i class="fas fa-lightbulb"></i> Как читать сравнение</h3>
                <ul>
                    <li>Зелёным подсвечены лучшие значения. Если данные одинаковые, подсветка будет у нескольких сборок.</li>
                    <li>Для цены и энергопотребления лучшим считается минимальное значение.</li>
                    <li>Если у сборки отсутствует компонент, ячейка остаётся пустой.</li>
                </ul>
            </div>
            <div class="actions">
                <a href="builder.php" class="btn-primary">
                    <i class="fas fa-screwdriver-wrench"></i> Создать свою конфигурацию
                </a>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
<script>
    document.getElementById('resetCompare')?.addEventListener('click', function() {
        localStorage.removeItem('compareBuilds');
        window.location.href = 'builds.php';
    });
</script>
</body>
</html>

<?php
session_start();
require_once 'config.php';

// Get filter parameters
$sortBy = $_GET['sort'] ?? 'newest';
$filterPrice = $_GET['price'] ?? 'all';
$filterPurpose = $_GET['purpose'] ?? 'all';
$searchQuery = $_GET['search'] ?? '';

// Build SQL query
$sql = "SELECT b.*, u.username, 
        COUNT(DISTINCT bl.user_id) as likes_count,
        COUNT(DISTINCT bc.id) as comments_count
        FROM user_builds b
        LEFT JOIN users u ON b.user_id = u.id
        LEFT JOIN build_likes bl ON b.id = bl.build_id
        LEFT JOIN build_comments bc ON b.id = bc.build_id
        WHERE b.is_public = 1";

// Add search filter
if (!empty($searchQuery)) {
    $sql .= " AND (b.build_name LIKE :search OR b.description LIKE :search OR u.username LIKE :search)";
}

// Add price filter
if ($filterPrice !== 'all') {
    switch ($filterPrice) {
        case 'budget':
            $sql .= " AND b.total_price < 50000";
            break;
        case 'mid':
            $sql .= " AND b.total_price BETWEEN 50000 AND 100000";
            break;
        case 'high':
            $sql .= " AND b.total_price > 100000";
            break;
    }
}

// Add purpose filter
if ($filterPurpose !== 'all') {
    $sql .= " AND b.purpose = :purpose";
}

$sql .= " GROUP BY b.id";

// Add sorting
switch ($sortBy) {
    case 'popular':
        $sql .= " ORDER BY likes_count DESC, b.created_at DESC";
        break;
    case 'price_low':
        $sql .= " ORDER BY b.total_price ASC";
        break;
    case 'price_high':
        $sql .= " ORDER BY b.total_price DESC";
        break;
    default: // newest
        $sql .= " ORDER BY b.created_at DESC";
}

try {
    $stmt = $pdo->prepare($sql);
    
    if (!empty($searchQuery)) {
        $stmt->bindValue(':search', "%$searchQuery%");
    }
    if ($filterPurpose !== 'all') {
        $stmt->bindValue(':purpose', $filterPurpose);
    }
    
    $stmt->execute();
    $builds = $stmt->fetchAll();
} catch (PDOException $e) {
    $builds = [];
}

function extractRamModulesFromSpecs(?string $specs): ?string
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

$ramModulesByBuild = [];
$buildIds = array_column($builds, 'id');
if ($buildIds) {
    try {
        $placeholders = implode(',', array_fill(0, count($buildIds), '?'));
        $stmt = $pdo->prepare("
            SELECT bc.build_id, r.specs
            FROM build_components bc
            JOIN components_ram r ON bc.component_id = r.id
            WHERE bc.build_id IN ($placeholders)
        ");
        $stmt->execute($buildIds);
        $rows = $stmt->fetchAll();

        foreach ($rows as $row) {
            $buildId = (int)$row['build_id'];
            if (isset($ramModulesByBuild[$buildId])) {
                continue;
            }
            $modules = extractRamModulesFromSpecs($row['specs'] ?? null);
            if ($modules) {
                $ramModulesByBuild[$buildId] = $modules;
            }
        }
    } catch (PDOException $e) {
        $ramModulesByBuild = [];
    }
}

// Get user's liked builds
$userLikes = [];
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT build_id FROM build_likes WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $userLikes = array_column($stmt->fetchAll(), 'build_id');
    } catch (PDOException $e) {
        $userLikes = [];
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Готовые сборки - <?= SITE_NAME ?></title>
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
    <link rel="stylesheet" href="css/builds.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="builds-page">
        <div class="container">
            <div class="builds-header">
                <div>
                    <h1><i class="fas fa-folder-open"></i> Готовые сборки</h1>
                    <p>Подборки от сообщества HyperPC: смотрите, сравнивайте и делитесь своей конфигурацией</p>
                </div>
                <button type="button" class="btn-compare-start" onclick="openComparePage(true)">
                    <i class="fas fa-scale-balanced"></i>
                    <span>Сравнить конфигурации</span>
                </button>
            </div>

            <div class="builds-controls">
                <form method="GET" action="">
                    <div class="controls-row">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input 
                                type="text" 
                                name="search" 
                                placeholder="Поиск по названию, автору..." 
                                value="<?= htmlspecialchars($searchQuery) ?>"
                            >
                        </div>

                        <div class="filter-group">
                            <label>Сортировка:</label>
                            <select name="sort" class="filter-select" onchange="this.form.submit()">
                                <option value="newest" <?= $sortBy === 'newest' ? 'selected' : '' ?>>Новые</option>
                                <option value="popular" <?= $sortBy === 'popular' ? 'selected' : '' ?>>Популярные</option>
                                <option value="price_low" <?= $sortBy === 'price_low' ? 'selected' : '' ?>>Цена ↑</option>
                                <option value="price_high" <?= $sortBy === 'price_high' ? 'selected' : '' ?>>Цена ↓</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label>Цена:</label>
                            <select name="price" class="filter-select" onchange="this.form.submit()">
                                <option value="all" <?= $filterPrice === 'all' ? 'selected' : '' ?>>Все</option>
                                <option value="budget" <?= $filterPrice === 'budget' ? 'selected' : '' ?>>До 50к</option>
                                <option value="mid" <?= $filterPrice === 'mid' ? 'selected' : '' ?>>50к - 100к</option>
                                <option value="high" <?= $filterPrice === 'high' ? 'selected' : '' ?>>Более 100к</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label>Назначение:</label>
                            <select name="purpose" class="filter-select" onchange="this.form.submit()">
                                <option value="all" <?= $filterPurpose === 'all' ? 'selected' : '' ?>>Все</option>
                                <option value="gaming" <?= $filterPurpose === 'gaming' ? 'selected' : '' ?>>Игры</option>
                                <option value="work" <?= $filterPurpose === 'work' ? 'selected' : '' ?>>Работа</option>
                                <option value="streaming" <?= $filterPurpose === 'streaming' ? 'selected' : '' ?>>Стриминг</option>
                                <option value="editing" <?= $filterPurpose === 'editing' ? 'selected' : '' ?>>Монтаж</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>

            <div class="builds-grid">
                <?php if (empty($builds)): ?>
                    <div class="empty-state" style="grid-column: 1 / -1;">
                        <i class="fas fa-folder-open"></i>
                        <h2>Сборки не найдены</h2>
                        <p>Попробуйте изменить фильтры или соберите свою первую конфигурацию</p>
                        <a href="builder.php" class="btn-build-pc">
                            <i class="fas fa-screwdriver-wrench"></i>
                            <span>Создать конфигурацию</span>
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($builds as $build): ?>
                        <?php
                        $components = json_decode($build['components'], true) ?? [];
                        $purposeLabels = [
                            'gaming' => 'Игровая',
                            'work' => 'Рабочая / офисная',
                            'streaming' => 'Для стриминга',
                            'editing' => 'Для монтажа',
                            'other' => 'Другое'
                        ];
                        $purposeKey = strtolower($build['purpose'] ?? 'other');
                        ?>
                        <div class="build-card" data-build-id="<?= $build['id'] ?>" onclick="viewBuild(<?= $build['id'] ?>)">
                            <div class="build-header">
                                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $build['user_id']): ?>
                                    <button 
                                        class="btn-delete-build" 
                                        onclick="event.stopPropagation(); openDeleteModal(<?= $build['id'] ?>, '<?= htmlspecialchars($build['build_name'], ENT_QUOTES) ?>')" 
                                        title="Удалить сборку"
                                    >
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php endif; ?>
                                <div class="build-title">
                                    <i class="fas fa-desktop"></i>
                                    <?= htmlspecialchars($build['build_name']) ?>
                                </div>
                                <div class="build-author">
                                    <i class="fas fa-user"></i>
                                    <?= htmlspecialchars($build['username']) ?>
                                </div>
                                <?php if ($purposeKey): ?>
                                    <span class="build-purpose <?= htmlspecialchars($purposeKey) ?>">
                                        <?= htmlspecialchars($purposeLabels[$purposeKey] ?? ucfirst($purposeKey)) ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="build-specs">
                                <?php if (!empty($components['cpu'])): ?>
                                <div class="spec-item">
                                    <div class="spec-icon">
                                        <i class="fas fa-microchip"></i>
                                    </div>
                                    <div class="spec-content">
                                        <div class="spec-label">Процессор</div>
                                        <div class="spec-value"><?= htmlspecialchars($components['cpu']) ?></div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <?php if (!empty($components['gpu'])): ?>
                                <div class="spec-item">
                                    <div class="spec-icon">
                                        <i class="fas fa-display"></i>
                                    </div>
                                    <div class="spec-content">
                                        <div class="spec-label">Видеокарта</div>
                                        <div class="spec-value"><?= htmlspecialchars($components['gpu']) ?></div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <?php if (!empty($components['ram'])): ?>
                                <div class="spec-item">
                                    <div class="spec-icon">
                                        <i class="fas fa-memory"></i>
                                    </div>
                                    <div class="spec-content">
                                        <div class="spec-label">Оперативная память</div>
                                        <div class="spec-value">
                                            <?= htmlspecialchars($components['ram']) ?>
                                            <?php if (!empty($ramModulesByBuild[$build['id']])): ?>
                                                • <?= htmlspecialchars($ramModulesByBuild[$build['id']]) ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>

                            <div class="build-footer">
                                <div class="build-price">
                                    <?= formatPrice($build['total_price']) ?>
                                </div>
                                <div class="footer-actions" onclick="event.stopPropagation()">
                                    <label class="compare-toggle" title="Добавить в сравнение">
                                        <input type="checkbox" class="compare-checkbox"
                                            data-build-id="<?= $build['id'] ?>"
                                            data-build-name="<?= htmlspecialchars($build['build_name']) ?>"
                                            data-build-price="<?= (float)$build['total_price'] ?>">
                                        <span><i class="fas fa-scale-balanced"></i> Сравнить</span>
                                    </label>
                                    <div class="build-actions">
                                        <button 
                                            class="action-btn <?= in_array($build['id'], $userLikes) ? 'liked' : '' ?>" 
                                            onclick="toggleLike(<?= $build['id'] ?>)"
                                            title="Нравится"
                                        >
                                            <i class="fas fa-heart"></i>
                                            <?php if ($build['likes_count'] > 0): ?>
                                                <span class="like-count"><?= $build['likes_count'] ?></span>
                                            <?php endif; ?>
                                        </button>
                                        <a 
                                            class="action-btn" 
                                            href="build-details.php?id=<?= $build['id'] ?>#comments"
                                            title="Комментарии"
                                            role="button"
                                        >
                                            <i class="fas fa-comment"></i>
                                            <?php if ($build['comments_count'] > 0): ?>
                                                <span class="like-count"><?= $build['comments_count'] ?></span>
                                            <?php endif; ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="compare-bar" id="compareBar">
                <div class="compare-bar-content">
                    <div class="selected-builds" id="compareSelected"></div>
                    <div class="compare-actions">
                        <button type="button" class="btn-clear-compare" onclick="clearCompareSelection()">
                            <i class="fas fa-broom"></i> Очистить
                        </button>
                        <button type="button" class="btn-compare" id="compareActionBtn" disabled onclick="openComparePage()">
                            <i class="fas fa-chart-column"></i>
                            <span id="compareBtnLabel">Выберите минимум 2 сборки</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <!-- Compare warning modal -->
    <div class="compare-modal" id="compareWarningModal" aria-hidden="true">
        <div class="compare-modal-overlay" onclick="closeCompareWarning()"></div>
        <div class="compare-modal-content" role="dialog" aria-modal="true" aria-labelledby="compareWarningTitle">
            <button class="compare-modal-close" onclick="closeCompareWarning()" aria-label="Закрыть">
                <i class="fas fa-times"></i>
            </button>
            <div class="compare-modal-icon">
                <i class="fas fa-circle-info"></i>
            </div>
            <h3 id="compareWarningTitle">Недостаточно сборок</h3>
            <p id="compareWarningMessage">Выберите минимум две сборки для сравнения.</p>
            <div class="compare-modal-actions">
                <button type="button" class="btn btn-primary" onclick="closeCompareWarning()">Понятно</button>
            </div>
        </div>
    </div>

    <!-- Delete confirmation modal -->
    <div class="compare-modal" id="deleteBuildModal" aria-hidden="true">
        <div class="compare-modal-overlay" onclick="closeDeleteModal()"></div>
        <div class="compare-modal-content danger" role="dialog" aria-modal="true" aria-labelledby="deleteModalTitle">
            <button class="compare-modal-close" onclick="closeDeleteModal()" aria-label="Закрыть">
                <i class="fas fa-times"></i>
            </button>
            <div class="compare-modal-icon danger">
                <i class="fas fa-trash"></i>
            </div>
            <h3 id="deleteModalTitle">Удалить сборку?</h3>
            <p id="deleteModalMessage">Вы уверены, что хотите удалить эту сборку? Действие необратимо.</p>
            <div class="compare-modal-actions">
                <button type="button" class="btn btn-outline" onclick="closeDeleteModal()">Отмена</button>
                <button type="button" class="btn btn-danger" id="deleteModalConfirm" onclick="confirmDeleteBuild()">
                    <span class="btn-label">Удалить</span>
                    <span class="btn-spinner" aria-hidden="true"></span>
                </button>
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script>
        const isUserLoggedIn = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;
        const compareLimit = 3;
    </script>
    <script src="js/builds.js"></script>
</body>
</html>

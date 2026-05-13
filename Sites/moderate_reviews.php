<?php
session_start();
require_once 'config.php';

// Check if user is support staff or owner
$hasAccess = false;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    $hasAccess = in_array($user['role'] ?? '', ['support', 'owner']);
}

if (!$hasAccess) {
    http_response_code(403);
    header('Location: index.php');
    exit;
}

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Filters
$statusFilter = $_GET['status'] ?? 'pending';
$categoryFilter = $_GET['category'] ?? '';

// Build query
$whereConditions = [];
$params = [];

if ($statusFilter) {
    $whereConditions[] = "cr.status = ?";
    $params[] = $statusFilter;
}

if ($categoryFilter) {
    $whereConditions[] = "cr.component_category_id = ?";
    $params[] = $categoryFilter;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get reviews with user info
$reviews = [];
$totalReviews = 0;
$stats = ['total' => 0, 'pending' => 0, 'published' => 0, 'archived' => 0];

try {
    // Get stats
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published,
            SUM(CASE WHEN status = 'archived' THEN 1 ELSE 0 END) as archived
        FROM component_reviews
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get total count for pagination
    $countSql = "SELECT COUNT(*) FROM component_reviews cr $whereClause";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $totalReviews = $stmt->fetchColumn();
    
    // Get reviews
    $sql = "
        SELECT cr.*, 
               u.username, u.email, u.avatar,
               cat.name as category_name, cat.icon as category_icon
        FROM component_reviews cr
        LEFT JOIN users u ON cr.user_id = u.id
        LEFT JOIN categories cat ON cr.component_category_id = cat.id
        $whereClause
        ORDER BY cr.created_at DESC
        LIMIT $perPage OFFSET $offset
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reviews = $stmt->fetchAll();
    
    // Get categories for filter
    $categories = $pdo->query("SELECT id, name, icon FROM categories ORDER BY name")->fetchAll();
    
} catch (PDOException $e) {
    error_log('Moderate reviews error: ' . $e->getMessage());
}

$totalPages = ceil($totalReviews / $perPage);
$statusLabels = [
    'pending' => 'На модерации',
    'published' => 'Опубликовано',
    'archived' => 'В архиве'
];

ob_start();
if (empty($reviews)): ?>
    <div class="empty-state">
        <i class="fas fa-inbox"></i>
        <h3>Обзоров не найдено</h3>
        <p>По выбранным фильтрам обзоры отсутствуют</p>
    </div>
<?php else: ?>
    <div class="reviews-list console-cards">
        <?php foreach ($reviews as $review): ?>
            <div class="review-moderate-card console-card" data-review-id="<?= $review['id'] ?>">
                <div class="review-moderate-header">
                    <div class="review-user-info">
                        <div class="user-avatar">
                            <?php if (!empty($review['avatar'])): ?>
                                <img src="<?= htmlspecialchars($review['avatar']) ?>" alt="Avatar">
                            <?php else: ?>
                                <i class="fas fa-user-circle"></i>
                            <?php endif; ?>
                        </div>
                        <div class="user-details">
                            <div class="user-name"><?= htmlspecialchars($review['username']) ?></div>
                            <div class="review-date">
                                <i class="fas fa-clock"></i>
                                <?= date('d.m.Y H:i', strtotime($review['created_at'])) ?>
                            </div>
                        </div>
                    </div>
                    <div class="review-status-badge status-<?= $review['status'] ?>">
                        <?= $statusLabels[$review['status']] ?? $review['status'] ?>
                    </div>
                </div>

                <div class="review-moderate-content">
                    <div class="review-component-info">
                        <div class="component-category">
                            <i class="fas <?= htmlspecialchars($review['category_icon'] ?? 'fa-microchip') ?>"></i>
                            <?= htmlspecialchars($review['category_name'] ?? 'Категория') ?>
                        </div>
                        <div class="component-name"><?= htmlspecialchars($review['component_name']) ?></div>
                        <div class="review-rating">
                            <?php
                            $ratingValue = (int)($review['rating'] ?? 0);
                            for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star <?= $i <= $ratingValue ? 'active' : '' ?>"></i>
                            <?php endfor; ?>
                            <span><?= $ratingValue ?>/5</span>
                        </div>
                    </div>

                    <div class="review-text-content">
                        <h3 class="review-title"><?= htmlspecialchars($review['title']) ?></h3>
                        <p class="review-summary"><?= nl2br(htmlspecialchars($review['summary'])) ?></p>
                        
                        <?php if (!empty($review['pros']) || !empty($review['cons'])): ?>
                            <div class="review-pros-cons">
                                <?php if (!empty($review['pros'])): ?>
                                    <div class="pros-section">
                                        <strong><i class="fas fa-plus"></i> Плюсы:</strong>
                                        <p><?= nl2br(htmlspecialchars($review['pros'])) ?></p>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($review['cons'])): ?>
                                    <div class="cons-section">
                                        <strong><i class="fas fa-minus"></i> Минусы:</strong>
                                        <p><?= nl2br(htmlspecialchars($review['cons'])) ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($review['usage_context'])): ?>
                            <div class="usage-context">
                                <i class="fas fa-briefcase"></i>
                                <span><?= htmlspecialchars($review['usage_context']) ?></span>
                            </div>
                        <?php endif; ?>

                        <div class="review-recommendation">
                            <?php if ($review['recommended']): ?>
                                <i class="fas fa-thumbs-up"></i>
                                <span>Рекомендует компонент</span>
                            <?php else: ?>
                                <i class="fas fa-circle-info"></i>
                                <span>Нейтральная оценка</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="review-moderate-actions">
                    <?php if ($review['status'] === 'pending'): ?>
                        <button class="btn btn-approve" onclick="moderateReview(<?= $review['id'] ?>, 'published')">
                            <i class="fas fa-check"></i>
                            Одобрить
                        </button>
                        <button class="btn btn-archive" onclick="openArchiveModal(<?= $review['id'] ?>)">
                            <i class="fas fa-archive"></i>
                            В архив
                        </button>
                    <?php elseif ($review['status'] === 'published'): ?>
                        <button class="btn btn-archive" onclick="openArchiveModal(<?= $review['id'] ?>)">
                            <i class="fas fa-archive"></i>
                            В архив
                        </button>
                    <?php elseif ($review['status'] === 'archived'): ?>
                        <button class="btn btn-approve" onclick="moderateReview(<?= $review['id'] ?>, 'published')">
                            <i class="fas fa-check"></i>
                            Опубликовать
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif;
$reviewsHtml = ob_get_clean();

ob_start();
if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>&status=<?= urlencode($statusFilter) ?>&category=<?= urlencode($categoryFilter) ?>" class="pagination-btn">
                <i class="fas fa-chevron-left"></i>
                Назад
            </a>
        <?php endif; ?>
        
        <div class="pagination-info">
            Страница <?= $page ?> из <?= $totalPages ?>
        </div>
        
        <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>&status=<?= urlencode($statusFilter) ?>&category=<?= urlencode($categoryFilter) ?>" class="pagination-btn">
                Вперёд
                <i class="fas fa-chevron-right"></i>
            </a>
        <?php endif; ?>
    </div>
<?php endif;
$paginationHtml = ob_get_clean();

if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'reviews_html' => $reviewsHtml,
        'pagination_html' => $paginationHtml,
        'total' => (int)$totalReviews,
        'page' => (int)$page,
        'total_pages' => (int)$totalPages
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Модерация обзоров - <?= SITE_NAME ?></title>
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
    <link rel="stylesheet" href="css/admin-console.css?v=<?= time() ?>">
    <link rel="stylesheet" href="css/moderate-reviews.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="internal-page moderate-reviews-page">
<?php include 'includes/header.php'; ?>

<main class="admin-console moderate-container">
    <div class="console-hero">
        <div class="hero-icon gradient-amber">
            <i class="fas fa-star-half-stroke"></i>
        </div>
        <div class="hero-meta">
            <h1>Модерация обзоров</h1>
            <p>Проверка и одобрение пользовательских обзоров комплектующих</p>
        </div>
        <div class="hero-actions">
            <a href="support_tickets.php" class="btn-outline-link">
                <i class="fas fa-comments"></i>
                К обращениям
            </a>
        </div>
    </div>

    <div class="stats-grid console-stats">
        <div class="stat-card">
            <div class="stat-icon pending">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= (int)$stats['pending'] ?></div>
                <div class="stat-label">На модерации</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon published">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= (int)$stats['published'] ?></div>
                <div class="stat-label">Опубликовано</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon archived">
                <i class="fas fa-archive"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= (int)$stats['archived'] ?></div>
                <div class="stat-label">В архиве</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon total">
                <i class="fas fa-list"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= (int)$stats['total'] ?></div>
                <div class="stat-label">Всего обзоров</div>
            </div>
        </div>
    </div>

    <div class="moderate-filters console-filters">
        <form method="get" class="filters-form">
            <div class="filter-group">
                <label>
                    <span>Статус</span>
                    <select name="status">
                        <option value="">Все статусы</option>
                        <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>На модерации</option>
                        <option value="published" <?= $statusFilter === 'published' ? 'selected' : '' ?>>Опубликовано</option>
                        <option value="archived" <?= $statusFilter === 'archived' ? 'selected' : '' ?>>В архиве</option>
                    </select>
                </label>
            </div>
            <div class="filter-group">
                <label>
                    <span>Категория</span>
                    <select name="category">
                        <option value="">Все категории</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $categoryFilter == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            <a href="moderate_reviews.php" class="btn-reset <?= ($statusFilter || $categoryFilter) ? '' : 'is-hidden' ?>" id="filtersReset">
                <i class="fas fa-times"></i>
                Сбросить
            </a>
        </form>
    </div>

    <div id="reviewsContainer">
        <?= $reviewsHtml ?>
    </div>
    <div id="paginationContainer">
        <?= $paginationHtml ?>
    </div>
</main>

<div class="moderate-modal" id="archiveModal" aria-hidden="true">
    <div class="moderate-modal-overlay" onclick="closeArchiveModal()"></div>
    <div class="moderate-modal-content" role="dialog" aria-modal="true" aria-labelledby="archiveModalTitle">
        <button class="moderate-modal-close" aria-label="Закрыть" onclick="closeArchiveModal()">
            <i class="fas fa-times"></i>
        </button>
        <div class="moderate-modal-icon">
            <i class="fas fa-box-archive"></i>
        </div>
        <h3 id="archiveModalTitle">Архивация обзора</h3>
        <p class="moderate-modal-description">Подтвердите действие. Обзор будет перемещен в архив и не будет виден в каталоге.</p>
        <div class="moderate-modal-options">
            <button type="button" class="moderate-option" onclick="confirmArchiveReview()">
                <div>
                    <strong>В архив</strong>
                    <span>Скрыть обзор из публичного списка</span>
                </div>
                <i class="fas fa-archive"></i>
            </button>
        </div>
        <div class="moderate-modal-actions">
            <button type="button" class="btn btn-outline" onclick="closeArchiveModal()">Отмена</button>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
<script src="js/main.js"></script>
<script src="js/moderate-reviews.js"></script>
</body>
</html>

<?php
require_once 'config.php';
require_once 'includes/security.php';
require_once 'includes/components_union.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = 'Обзоры комплектующих';
$currentUserId = $_SESSION['user_id'] ?? null;
$csrfToken = Security::generateCSRFToken();
$successMessage = $_SESSION['review_success'] ?? null;
unset($_SESSION['review_success']);
$selectedCategory = isset($_GET['category']) ? (int)$_GET['category'] : null;
$componentSource = getComponentsUnionSource();

// Fetch categories
try {
    $categories = $pdo->query("SELECT id, name, slug, icon FROM categories ORDER BY id")->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}
$categoryNameMap = [];
foreach ($categories as $category) {
    $categoryNameMap[$category['id']] = $category;
}

// Fetch component options for the submission form
$componentOptions = [];
try {
    $componentUnionSql = getComponentsUnionSource();
    $stmt = $pdo->query("SELECT id, category_id, name, manufacturer FROM {$componentUnionSql} ORDER BY name LIMIT 800");
    $componentOptions = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Failed to fetch components for reviews form: ' . $e->getMessage());
    $componentOptions = [];
}

// Helper to fetch component meta by id + category
function findComponentMeta(PDO $pdo, string $componentSource, int $categoryId, int $componentId): ?array {
    try {
        $stmt = $pdo->prepare("SELECT id, category_id, name, manufacturer FROM {$componentSource} WHERE category_id = ? AND id = ? LIMIT 1");
        $stmt->execute([$categoryId, $componentId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (PDOException $e) {
        return null;
    }
}

function slugify(string $text): string {
    $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = trim($text, '-');
    $text = strtolower($text);
    return preg_replace('~[^-a-z0-9]+~', '', $text);
}


// Stats
$stats = [
    'total_reviews' => 0,
    'avg_rating' => 0,
    'recommended_rate' => 0
];
try {
    $stmt = $pdo->query("SELECT COUNT(*) AS total_reviews, AVG(rating) AS avg_rating, SUM(recommended) AS recommended_reviews
        FROM component_reviews WHERE status = 'published'");
    $statsRow = $stmt->fetch();
    if ($statsRow) {
        $stats['total_reviews'] = (int)$statsRow['total_reviews'];
        $stats['avg_rating'] = $statsRow['avg_rating'] ? round($statsRow['avg_rating'], 1) : 0;
        $stats['recommended_rate'] = $stats['total_reviews'] > 0 && $statsRow['recommended_reviews'] !== null
            ? round(($statsRow['recommended_reviews'] / $stats['total_reviews']) * 100)
            : 0;
    }
} catch (PDOException $e) {
    error_log('Failed to fetch review stats: ' . $e->getMessage());
}

// Category stats for chips
$categoryStats = [];
try {
    $stmt = $pdo->query("SELECT component_category_id, COUNT(*) AS total, ROUND(AVG(rating), 1) AS avg_rating
        FROM component_reviews WHERE status = 'published' GROUP BY component_category_id");
    $categoryStats = $stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE);
} catch (PDOException $e) {
    error_log('Failed to fetch category stats: ' . $e->getMessage());
    $categoryStats = [];
}

// Reviews feed
$reviews = [];
try {
    $componentsUnionSql = getComponentsUnionSource();
    $sql = "SELECT cr.*, u.username, u.avatar AS avatar_url, cat.name AS category_name, cat.icon AS category_icon,
            comp.manufacturer
            FROM component_reviews cr
            JOIN users u ON u.id = cr.user_id
            LEFT JOIN categories cat ON cat.id = cr.component_category_id
            LEFT JOIN ({$componentsUnionSql}) comp ON comp.id = cr.component_id AND comp.category_id = cr.component_category_id
            WHERE cr.status = 'published'";
    $params = [];
    if ($selectedCategory) {
        $sql .= " AND cr.component_category_id = ?";
        $params[] = $selectedCategory;
    }
    $sql .= " ORDER BY cr.created_at DESC LIMIT 40";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reviews = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Failed to fetch reviews feed: ' . $e->getMessage());
    $reviews = [];
}

?><!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Обзоры комплектующих - <?= SITE_NAME ?></title>
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
    <link rel="stylesheet" href="css/reviews.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="internal-page reviews-page-body">
<?php include 'includes/header.php'; ?>
<main class="reviews-page">
    <section class="reviews-hero">
        <div class="hero-content">
            <p class="eyebrow">Комьюнити HyperPC</p>
            <h1>Обзоры комплектующих</h1>
            <p class="lead">Делитесь реальным опытом, помогайте другим выбрать идеальную конфигурацию и узнавайте, как компоненты ведут себя в боевых сценариях.</p>
            <div class="hero-cta">
                <a href="create-review.php" class="btn btn-primary"><i class="fas fa-pen"></i> Написать обзор</a>
                <button class="btn btn-ghost" type="button" onclick="document.getElementById('reviews-feed').scrollIntoView({behavior: 'smooth'});">
                    <i class="fas fa-list"></i> Смотреть отзывы
                </button>
            </div>
        </div>
        <div class="hero-metrics">
            <div class="metric-card">
                <span class="metric-label">Средняя оценка</span>
                <div class="metric-value">
                    <?= number_format($stats['avg_rating'], 1) ?>
                    <small>/5</small>
                </div>
                <div class="metric-stars" aria-label="Средняя оценка">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="fas fa-star<?= $i <= round($stats['avg_rating']) ? '' : '-o' ?>"></i>
                    <?php endfor; ?>
                </div>
            </div>
            <div class="metric-card">
                <span class="metric-label">Пользователи рекомендуют</span>
                <div class="metric-value"><?= $stats['recommended_rate'] ?>%</div>
                <small>из <?= $stats['total_reviews'] ?> обзоров</small>
            </div>
        </div>
    </section>

    <section class="reviews-metrics">
        <div class="metric pill">
            <i class="fas fa-clipboard-check"></i>
            <div>
                <span>Доверенный опыт</span>
                <strong>Модерация и верификация авторов</strong>
            </div>
        </div>
        <div class="metric pill">
            <i class="fas fa-microchip"></i>
            <div>
                <span>Каталог компонентов</span>
                <strong>Привязка к реальным позициям HyperPC</strong>
            </div>
        </div>
        <div class="metric pill">
            <i class="fas fa-shield"></i>
            <div>
                <span>Прозрачность</span>
                <strong>Оценки и факты без рекламы</strong>
            </div>
        </div>
    </section>

    <section class="reviews-content" id="reviews-feed">
        <div class="reviews-main">
            <div class="reviews-toolbar">
                <form class="filter-form" method="get" aria-label="Фильтр по категориям">
                    <label class="filter-field">
                        <span>Категория</span>
                        <select name="category" onchange="this.form.submit()">
                            <option value="">Все комплектующие</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>" <?= $selectedCategory === (int)$category['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </form>
                <div class="category-chips" role="list">
                    <?php if (!empty($categoryStats)): ?>
                        <?php foreach ($categoryStats as $categoryId => $stat): ?>
                            <?php $catInfo = $categoryNameMap[$categoryId] ?? null; ?>
                            <button class="chip <?= $selectedCategory === (int)$categoryId ? 'active' : '' ?>" data-category-link="<?= $categoryId ?>">
                                <i class="fas <?= htmlspecialchars($catInfo['icon'] ?? 'fa-layer-group') ?>"></i>
                                <span><?= htmlspecialchars($catInfo['name'] ?? 'Категория') ?></span>
                                <small><?= (int)$stat['total'] ?> обз.</small>
                            </button>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span class="chip muted">Обзоры появятся после модерации</span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($successMessage): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <p><?= htmlspecialchars($successMessage) ?></p>
                </div>
            <?php endif; ?>
            <?php if (empty($reviews)): ?>
                <div class="reviews-empty">
                    <i class="fas fa-comments"></i>
                    <p>Пока нет опубликованных обзоров для выбранной категории.</p>
                    <a class="btn btn-primary" href="create-review.php">Стать первым</a>
                </div>
            <?php else: ?>
                <div class="reviews-grid">
                    <?php foreach ($reviews as $review): ?>
                        <article id="review-<?= (int)$review['id'] ?>" class="review-card" data-rating="<?= (int)$review['rating'] ?>" data-review-id="<?= (int)$review['id'] ?>">
                            <header class="review-card__header">
                                <div>
                                    <h3><?= htmlspecialchars($review['title']) ?></h3>
                                    <div class="review-meta">
                                        <span class="rating-pill"><i class="fas fa-star"></i> <?= (int)$review['rating'] ?>/5</span>
                                        <?php if (!empty($review['category_name'])): ?>
                                            <span class="category-pill">
                                                <i class="fas <?= htmlspecialchars($review['category_icon'] ?? 'fa-layer-group') ?>"></i>
                                                <?= htmlspecialchars($review['category_name']) ?>
                                            </span>
                                        <?php endif; ?>
                                        <span class="review-date"><?= date('d.m.Y', strtotime($review['created_at'])) ?></span>
                                    </div>
                                </div>
                                <div class="review-author">
                                    <div class="avatar">
                                        <?php if (!empty($review['avatar_url'])): ?>
                                            <img src="<?= htmlspecialchars($review['avatar_url']) ?>" alt="Аватар">
                                        <?php else: ?>
                                            <i class="fas fa-user"></i>
                                        <?php endif; ?>
                                    </div>
                                    <span><?= htmlspecialchars($review['username']) ?></span>
                                </div>
                                <?php if ($currentUserId && (int)$currentUserId === (int)$review['user_id']): ?>
                                    <button
                                        type="button"
                                        class="review-delete-btn"
                                        data-review-id="<?= (int)$review['id'] ?>"
                                        data-review-title="<?= htmlspecialchars($review['title'], ENT_QUOTES) ?>"
                                        aria-label="Удалить обзор"
                                    >
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </header>
                            <div class="component-highlight">
                                <div>
                                    <span class="component-label">Компонент</span>
                                    <strong><?= htmlspecialchars($review['component_name']) ?></strong>
                                    <?php if (!empty($review['manufacturer'])): ?>
                                        <small><?= htmlspecialchars($review['manufacturer']) ?></small>
                                    <?php endif; ?>
                                </div>
                                <?php if ((int)$review['recommended'] === 1): ?>
                                    <span class="badge badge-positive"><i class="fas fa-thumbs-up"></i> Рекомендую</span>
                                <?php else: ?>
                                    <span class="badge badge-neutral"><i class="fas fa-circle-info"></i> Нейтрально</span>
                                <?php endif; ?>
                            </div>
                            <p class="review-summary"><?= nl2br(htmlspecialchars($review['summary'])) ?></p>
                            <div class="review-details">
                                <?php if (!empty($review['pros'])): ?>
                                    <div>
                                        <span class="detail-label positive"><i class="fas fa-plus"></i> Плюсы</span>
                                        <p><?= nl2br(htmlspecialchars($review['pros'])) ?></p>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($review['cons'])): ?>
                                    <div>
                                        <span class="detail-label negative"><i class="fas fa-minus"></i> Минусы</span>
                                        <p><?= nl2br(htmlspecialchars($review['cons'])) ?></p>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($review['usage_context'])): ?>
                                    <div>
                                        <span class="detail-label"><i class="fas fa-briefcase"></i> Сценарий использования</span>
                                        <p><?= nl2br(htmlspecialchars($review['usage_context'])) ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <aside class="reviews-sidebar">
            <div class="cta-card">
                <div class="cta-icon">
                    <i class="fas fa-pen-fancy"></i>
                </div>
                <h2>Поделитесь опытом</h2>
                <p>Ваш обзор поможет тысячам пользователей сделать правильный выбор комплектующих</p>
                <?php if (!$currentUserId): ?>
                    <a href="login.php?redirect=create-review.php" class="btn btn-primary btn-block">
                        <i class="fas fa-sign-in-alt"></i>
                        Войти и написать обзор
                    </a>
                <?php else: ?>
                    <a href="create-review.php" class="btn btn-primary btn-block">
                        <i class="fas fa-pen"></i>
                        Написать обзор
                    </a>
                <?php endif; ?>
                <div class="cta-features">
                    <div class="feature-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Удобная форма</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Модерация 24ч</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Помощь сообществу</span>
                    </div>
                </div>
            </div>

            <div class="guidelines">
                <h3>Как написать полезный обзор?</h3>
                <ul>
                    <li><i class="fas fa-circle-check"></i> Укажите конфигурацию и реальные сценарии нагрузки.</li>
                    <li><i class="fas fa-circle-check"></i> Сравните с предыдущими компонентами, если есть опыт.</li>
                    <li><i class="fas fa-circle-check"></i> Добавьте цифры: FPS, температуры, шум.</li>
                    <li><i class="fas fa-circle-check"></i> Будьте честны — это поможет сообществу.</li>
                </ul>
            </div>
        </aside>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
<div class="review-modal" id="deleteReviewModal" aria-hidden="true">
    <div class="review-modal-overlay" onclick="closeReviewDeleteModal()"></div>
    <div class="review-modal-content" role="dialog" aria-modal="true" aria-labelledby="deleteReviewTitle">
        <button class="review-modal-close" type="button" onclick="closeReviewDeleteModal()" aria-label="Закрыть">
            <i class="fas fa-times"></i>
        </button>
        <div class="review-modal-icon">
            <i class="fas fa-trash"></i>
        </div>
        <h3 id="deleteReviewTitle">Удалить обзор?</h3>
        <p id="deleteReviewMessage">Вы уверены, что хотите удалить этот обзор? Действие необратимо.</p>
        <div class="review-modal-actions">
            <button type="button" class="btn btn-outline" onclick="closeReviewDeleteModal()">Отмена</button>
            <button type="button" class="btn btn-danger" id="confirmReviewDelete">
                <span class="btn-label">Удалить</span>
                <span class="btn-spinner" aria-hidden="true"></span>
            </button>
        </div>
    </div>
</div>
<script src="js/main.js"></script>
<script src="js/reviews.js"></script>
</body>
</html>

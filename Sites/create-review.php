<?php
require_once 'config.php';
require_once 'includes/security.php';
require_once 'includes/components_union.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = 'Написать обзор';
$currentUserId = $_SESSION['user_id'] ?? null;
$csrfToken = Security::generateCSRFToken();
$errors = [];
$successMessage = null;

// Redirect if not logged in
if (!$currentUserId) {
    header('Location: login.php?redirect=create-review.php');
    exit;
}

// Fetch categories
try {
    $categories = $pdo->query("SELECT id, name, slug, icon FROM categories ORDER BY id")->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

// Fetch component options grouped by category for the submission form
$componentsByCategory = [];
try {
    $componentUnionSql = getComponentsUnionSource();
    $stmt = $pdo->query("SELECT cu.id, cu.category_id, cu.name, cu.manufacturer FROM ({$componentUnionSql}) cu ORDER BY cu.category_id, cu.name LIMIT 1000");
    $allComponents = $stmt->fetchAll();
    
    // Group by category
    foreach ($allComponents as $component) {
        $catId = $component['category_id'];
        if (!isset($componentsByCategory[$catId])) {
            $componentsByCategory[$catId] = [];
        }
        $componentsByCategory[$catId][] = $component;
    }
} catch (PDOException $e) {
    error_log('Failed to fetch components for reviews form: ' . $e->getMessage());
    $componentsByCategory = [];
}

// Helper to fetch component meta by id + category
function findComponentMeta(PDO $pdo, string $componentSource, int $categoryId, int $componentId): ?array {
    try {
        $stmt = $pdo->prepare("SELECT cs.id, cs.category_id, cs.name, cs.manufacturer FROM ({$componentSource}) cs WHERE cs.category_id = ? AND cs.id = ? LIMIT 1");
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

$oldInput = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['review_action'] ?? '') === 'create') {
    $oldInput = $_POST;

    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'CSRF токен недействителен, обновите страницу и попробуйте снова.';
    }

    $categoryId = (int)($_POST['component_category_id'] ?? 0);
    $componentId = (int)($_POST['component_id'] ?? 0);
    $rating = (int)($_POST['rating'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $summary = trim($_POST['summary'] ?? '');
    $pros = trim($_POST['pros'] ?? '');
    $cons = trim($_POST['cons'] ?? '');
    $usage = trim($_POST['usage_context'] ?? '');
    $recommended = isset($_POST['recommended']) ? 1 : 0;

    if ($categoryId <= 0) {
        $errors[] = 'Выберите категорию комплектующего.';
    }

    if ($componentId <= 0) {
        $errors[] = 'Выберите конкретный компонент.';
    }

    if ($rating < 1 || $rating > 5) {
        $errors[] = 'Оценка должна быть от 1 до 5.';
    }

    if (mb_strlen($title) < 6) {
        $errors[] = 'Заголовок должен содержать минимум 6 символов.';
    }

    if (mb_strlen($summary) < 40) {
        $errors[] = 'Поделитесь детальным опытом (минимум 40 символов).';
    }

    $componentSource = getComponentsUnionSource();
    $componentMeta = ($categoryId && $componentId) ? findComponentMeta($pdo, $componentSource, $categoryId, $componentId) : null;
    if (!$componentMeta) {
        $errors[] = 'Выбранный компонент не найден или больше не доступен в каталоге.';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO component_reviews 
                (user_id, component_id, component_category_id, component_name, component_slug, rating, title, summary, pros, cons, usage_context, recommended, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([
                $currentUserId,
                $componentId,
                $categoryId,
                $componentMeta['name'] ?? 'Компонент',
                slugify($componentMeta['name'] ?? ''),
                $rating,
                $title,
                $summary,
                $pros ?: null,
                $cons ?: null,
                $usage ?: null,
                $recommended
            ]);

            $_SESSION['review_success'] = 'Спасибо! Ваш обзор отправлен на модерацию и появится на странице после проверки.';
            header('Location: reviews.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Не удалось сохранить обзор. Попробуйте позже.';
        }
    }
}

?><!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Написать обзор - <?= SITE_NAME ?></title>
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
    <link rel="stylesheet" href="css/create-review.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="internal-page create-review-page">
<?php include 'includes/header.php'; ?>

<main class="create-review-container">
    <div class="review-form-wrapper">
        <div class="form-header">
            <a href="reviews.php" class="back-link">
                <i class="fas fa-arrow-left"></i>
                <span>Назад к обзорам</span>
            </a>
            <div class="header-content">
                <div class="header-icon">
                    <i class="fas fa-pen-fancy"></i>
                </div>
                <div>
                    <h1>Написать обзор</h1>
                    <p class="subtitle">Поделитесь опытом использования комплектующего и помогите другим сделать правильный выбор</p>
                </div>
            </div>
        </div>

        <?php if ($errors): ?>
            <div class="alert alert-error">
                <i class="fas fa-circle-exclamation"></i>
                <div>
                    <strong>Исправьте ошибки:</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <div class="form-layout">
            <div class="form-main">
                <form method="post" class="review-form" id="reviewForm">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="review_action" value="create">

                    <div class="form-section">
                        <h2 class="section-title">
                            <i class="fas fa-microchip"></i>
                            Выбор компонента
                        </h2>
                        <div class="form-grid">
                            <div class="form-field">
                                <label>
                                    <span class="field-label">Категория <span class="required">*</span></span>
                                    <select name="component_category_id" id="componentCategory" required>
                                        <option value="">Выберите категорию</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?= $category['id'] ?>" <?= ($oldInput['component_category_id'] ?? '') == $category['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($category['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                            </div>
                            <div class="form-field">
                                <label>
                                    <span class="field-label">Компонент <span class="required">*</span></span>
                                    <select name="component_id" id="componentSelect" required>
                                        <option value="">Сначала выберите категорию</option>
                                        <?php foreach ($componentsByCategory as $catId => $components): ?>
                                            <?php foreach ($components as $component): ?>
                                                <option value="<?= $component['id'] ?>" data-category="<?= $catId ?>" <?= ($oldInput['component_id'] ?? '') == $component['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($component['name']) ?><?= !empty($component['manufacturer']) ? ' — ' . htmlspecialchars($component['manufacturer']) : '' ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h2 class="section-title">
                            <i class="fas fa-star"></i>
                            Оценка
                        </h2>
                        <div class="rating-control">
                            <div class="rating-display">
                                <div class="rating-value" id="ratingValue"><?= (int)($oldInput['rating'] ?? 4) ?></div>
                                <div class="rating-stars" id="ratingStars">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                </div>
                            </div>
                            <input type="range" name="rating" id="ratingInput" min="1" max="5" step="1" value="<?= (int)($oldInput['rating'] ?? 4) ?>">
                            <div class="rating-labels">
                                <span>Плохо</span>
                                <span>Отлично</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h2 class="section-title">
                            <i class="fas fa-file-lines"></i>
                            Основная информация
                        </h2>
                        <div class="form-field">
                            <label>
                                <span class="field-label">Заголовок обзора <span class="required">*</span></span>
                                <input type="text" name="title" placeholder="Например: Отличная видеокарта для 4K гейминга" value="<?= htmlspecialchars($oldInput['title'] ?? '') ?>" required maxlength="160">
                                <small class="field-hint">Краткое резюме вашего опыта (минимум 6 символов)</small>
                            </label>
                        </div>
                        <div class="form-field">
                            <label>
                                <span class="field-label">Детальный обзор <span class="required">*</span></span>
                                <textarea name="summary" id="summaryField" rows="8" placeholder="Опишите производительность, стабильность, температурный режим, уровень шума и другие важные аспекты использования..." required><?= htmlspecialchars($oldInput['summary'] ?? '') ?></textarea>
                                <small class="field-hint">Минимум 40 символов. Чем подробнее, тем полезнее для других пользователей.</small>
                            </label>
                        </div>
                    </div>

                    <div class="form-section">
                        <h2 class="section-title">
                            <i class="fas fa-balance-scale"></i>
                            Плюсы и минусы
                        </h2>
                        <div class="form-grid">
                            <div class="form-field">
                                <label>
                                    <span class="field-label positive-label">
                                        <i class="fas fa-plus"></i>
                                        Плюсы
                                    </span>
                                    <textarea name="pros" class="bullet-textarea" rows="5" placeholder="Что особенно понравилось в этом компоненте?"><?= htmlspecialchars($oldInput['pros'] ?? '') ?></textarea>
                                </label>
                            </div>
                            <div class="form-field">
                                <label>
                                    <span class="field-label negative-label">
                                        <i class="fas fa-minus"></i>
                                        Минусы
                                    </span>
                                    <textarea name="cons" class="bullet-textarea" rows="5" placeholder="С какими проблемами или недостатками столкнулись?"><?= htmlspecialchars($oldInput['cons'] ?? '') ?></textarea>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h2 class="section-title">
                            <i class="fas fa-briefcase"></i>
                            Дополнительная информация
                        </h2>
                        <div class="form-field">
                            <label>
                                <span class="field-label">Сценарий использования</span>
                                <input type="text" name="usage_context" placeholder="Например: монтаж 4K видео, AAA-гейминг, 3D-рендеринг" value="<?= htmlspecialchars($oldInput['usage_context'] ?? '') ?>" maxlength="255">
                                <small class="field-hint">Укажите основные задачи, для которых используется компонент</small>
                            </label>
                        </div>
                        <div class="form-field checkbox-field">
                            <label class="checkbox-label">
                                <input type="checkbox" name="recommended" <?= !empty($oldInput['recommended']) ? 'checked' : '' ?>>
                                <span class="checkbox-text">
                                    <i class="fas fa-thumbs-up"></i>
                                    Я рекомендую этот компонент другим пользователям
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary btn-large">
                            <i class="fas fa-paper-plane"></i>
                            Отправить на модерацию
                        </button>
                        <a href="reviews.php" class="btn btn-ghost">
                            <i class="fas fa-times"></i>
                            Отменить
                        </a>
                    </div>

                    <div class="form-notice">
                        <i class="fas fa-shield-check"></i>
                        <p>Все обзоры проходят ручную модерацию для обеспечения высокого качества контента. Обычно проверка занимает до 24 часов.</p>
                    </div>
                </form>
            </div>

            <aside class="form-sidebar">
                <div class="sidebar-card tips-card">
                    <h3>
                        <i class="fas fa-lightbulb"></i>
                        Советы по написанию
                    </h3>
                    <ul class="tips-list">
                        <li>
                            <i class="fas fa-check-circle"></i>
                            <span>Укажите полную конфигурацию системы</span>
                        </li>
                        <li>
                            <i class="fas fa-check-circle"></i>
                            <span>Приведите конкретные цифры: FPS, температуры, шум</span>
                        </li>
                        <li>
                            <i class="fas fa-check-circle"></i>
                            <span>Сравните с предыдущими компонентами</span>
                        </li>
                        <li>
                            <i class="fas fa-check-circle"></i>
                            <span>Опишите реальные сценарии использования</span>
                        </li>
                        <li>
                            <i class="fas fa-check-circle"></i>
                            <span>Будьте честны и объективны</span>
                        </li>
                    </ul>
                </div>

                <div class="sidebar-card stats-card">
                    <h3>
                        <i class="fas fa-chart-line"></i>
                        Ваша активность
                    </h3>
                    <?php
                    try {
                        $stmt = $pdo->prepare("SELECT COUNT(*) as total, 
                            SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published,
                            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
                            FROM component_reviews WHERE user_id = ?");
                        $stmt->execute([$currentUserId]);
                        $userStats = $stmt->fetch();
                    } catch (PDOException $e) {
                        $userStats = ['total' => 0, 'published' => 0, 'pending' => 0];
                    }
                    ?>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-value"><?= (int)$userStats['total'] ?></div>
                            <div class="stat-label">Всего обзоров</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?= (int)$userStats['published'] ?></div>
                            <div class="stat-label">Опубликовано</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?= (int)$userStats['pending'] ?></div>
                            <div class="stat-label">На модерации</div>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</main>

<div class="draft-modal" id="draftModal" aria-hidden="true">
    <div class="draft-modal__backdrop"></div>
    <div class="draft-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="draftModalTitle">
        <button class="draft-modal__close" type="button" aria-label="Закрыть" id="draftModalClose">
            <i class="fas fa-times"></i>
        </button>
        <div class="draft-modal__icon">
            <i class="fas fa-info"></i>
        </div>
        <div class="draft-modal__content">
            <h3 id="draftModalTitle">Несохраненный черновик</h3>
            <p>Мы обнаружили черновик обзора. Хотите восстановить его и продолжить редактирование?</p>
        </div>
        <div class="draft-modal__actions">
            <button class="btn btn-ghost" type="button" id="draftModalDismiss">Позже</button>
            <button class="btn btn-primary" type="button" id="draftModalConfirm">Восстановить</button>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
<script src="js/main.js"></script>
<script src="js/create-review.js"></script>
</body>
</html>

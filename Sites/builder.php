<?php
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$builderUserId = $_SESSION['user_id'] ?? null;

// Get all categories with components
$categories = $pdo->query("SELECT * FROM categories ORDER BY id")->fetchAll();

// Get all games for FPS calculator
$games = $pdo->query("SELECT * FROM games ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Сборка ПК - <?= SITE_NAME ?></title>
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
    <link rel="stylesheet" href="css/builder.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="builder-page">
        <div class="container">
            <div class="page-header">
                <div class="header-content">
                    <div class="header-icon">
                        <i class="fas fa-screwdriver-wrench"></i>
                    </div>
                    <div class="header-text">
                        <h1>Сборка ПК</h1>
                        <p>Соберите свой идеальный компьютер с автоматической проверкой совместимости и расчетом производительности</p>
                    </div>
                </div>
                <div class="header-actions">
                    <a class="btn btn-outline" href="profile.php#my-builds">
                        <i class="fas fa-folder-open"></i>
                        Мои сборки
                    </a>
                    <button class="btn btn-outline" onclick="importBuild()">
                        <i class="fas fa-file-import"></i>
                        Импорт
                    </button>
                </div>
            </div>

            <div class="builder-layout">
                <!-- Build Components List -->
                <div class="build-section">
                    <div class="section-header">
                        <div class="section-title">
                            <h2>Ваша сборка</h2>
                            <span class="build-progress" id="buildProgress">0/8 компонентов</span>
                        </div>
                        <div class="section-actions">
                            <button class="btn btn-outline btn-sm" onclick="openExportModal()">
                                <i class="fas fa-download"></i>
                                Экспорт
                            </button>
                            <button class="btn btn-secondary btn-sm" onclick="openClearModal()">
                                <i class="fas fa-trash"></i>
                                Очистить
                            </button>
                        </div>
                    </div>

                    <div class="build-components" id="buildComponents">
                        <?php foreach ($categories as $category): ?>
                        <div class="component-slot" data-category="<?= $category['id'] ?>">
                            <div class="slot-header">
                                <div class="slot-icon">
                                    <i class="fas <?= $category['icon'] ?>"></i>
                                </div>
                                <div class="slot-info">
                                    <h3><?= htmlspecialchars($category['name']) ?></h3>
                                    <p class="slot-description"><?= htmlspecialchars($category['description']) ?></p>
                                </div>
                            </div>
                            <div class="slot-content" id="slot-<?= $category['id'] ?>">
                                <button class="btn-add-component" onclick="openComponentSelector(<?= $category['id'] ?>, '<?= htmlspecialchars($category['name']) ?>')">
                                    <i class="fas fa-plus"></i>
                                    Выбрать <?= htmlspecialchars($category['name']) ?>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Build Summary & FPS Calculator -->
                <aside class="build-sidebar">
                    <!-- Build Summary -->
                    <div class="summary-card">
                        <h3><i class="fas fa-calculator"></i> Итого</h3>
                        <div class="summary-stats">
                            <div class="stat-row">
                                <span>Компонентов:</span>
                                <strong id="totalComponents">0</strong>
                            </div>
                            <div class="stat-row">
                                <span>Потребление:</span>
                                <strong id="totalPower">0 Вт</strong>
                            </div>
                            <div class="stat-row total-price">
                                <span>Стоимость:</span>
                                <strong id="totalPrice">0 ₽</strong>
                            </div>
                        </div>

                        <div class="compatibility-status" id="compatibilityStatus">
                            <div class="compatibility-icon">
                                <i class="fas fa-circle-check"></i>
                            </div>
                            <div class="compatibility-text">
                                <strong>Проверка совместимости</strong>
                                <span class="compatibility-message">Добавьте компоненты для проверки</span>
                            </div>
                        </div>

                        <div class="power-indicator" id="powerIndicator" style="display: none;">
                            <div class="power-header">
                                <span>Энергопотребление</span>
                                <strong id="powerPercentage">0%</strong>
                            </div>
                            <div class="power-bar">
                                <div class="power-fill" id="powerFill" style="width: 0%"></div>
                            </div>
                            <div class="power-recommendation" id="powerRecommendation"></div>
                        </div>

                        <div class="summary-actions">
                            <button class="btn btn-primary btn-lg" onclick="proceedToCheckout()" id="checkoutBtn" style="display: none;">
                                <i class="fas fa-shopping-cart"></i>
                                Оформить заказ
                            </button>
                            <button class="btn btn-primary btn-lg" onclick="openSaveModal()">
                                <i class="fas fa-save"></i>
                                Сохранить сборку
                            </button>
                            <div class="action-row">
                                <button class="btn btn-secondary" onclick="shareBuild()">
                                    <i class="fas fa-share-nodes"></i>
                                    Поделиться
                                </button>
                                <button class="btn btn-secondary" onclick="printBuild()">
                                    <i class="fas fa-print"></i>
                                    Печать
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- FPS Calculator -->
                    <div class="fps-calculator">
                        <h3><i class="fas fa-gamepad"></i> Расчет FPS</h3>
                        <p class="fps-description">Выберите игру для расчета производительности</p>

                        <div class="game-selector">
                            <select id="gameSelect" onchange="calculateFPS()">
                                <option value="">Выберите игру...</option>
                                <?php foreach ($games as $game): ?>
                                <option value="<?= $game['id'] ?>"><?= htmlspecialchars($game['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="resolution-selector">
                            <label>Разрешение:</label>
                            <div class="resolution-buttons">
                                <button class="resolution-btn active" data-resolution="1920x1080" onclick="selectResolution(this)">
                                    1080p
                                </button>
                                <button class="resolution-btn" data-resolution="2560x1440" onclick="selectResolution(this)">
                                    1440p
                                </button>
                                <button class="resolution-btn" data-resolution="3840x2160" onclick="selectResolution(this)">
                                    4K
                                </button>
                            </div>
                        </div>

                        <div class="quality-selector">
                            <label>Качество графики:</label>
                            <div class="quality-buttons">
                                <button class="quality-btn" data-quality="low" onclick="selectQuality(this)">
                                    Низкое
                                </button>
                                <button class="quality-btn" data-quality="medium" onclick="selectQuality(this)">
                                    Среднее
                                </button>
                                <button class="quality-btn" data-quality="high" onclick="selectQuality(this)">
                                    Высокое
                                </button>
                                <button class="quality-btn active" data-quality="ultra" onclick="selectQuality(this)">
                                    Ультра
                                </button>
                            </div>
                        </div>

                        <div class="fps-result" id="fpsResult" style="display: none;">
                            <div class="fps-display">
                                <div class="fps-value" id="fpsValue">0</div>
                                <div class="fps-label">FPS</div>
                            </div>
                            <div class="fps-details">
                                <div class="fps-detail">
                                    <span>Минимум:</span>
                                    <strong id="minFps">0</strong>
                                </div>
                                <div class="fps-detail">
                                    <span>Максимум:</span>
                                    <strong id="maxFps">0</strong>
                                </div>
                            </div>
                            <div class="fps-meta" id="fpsMeta"></div>
                            <div class="fps-extra">
                                <div class="fps-extra-item">
                                    <span>Статус конфигурации:</span>
                                    <strong id="fpsStatus">—</strong>
                                </div>
                                <div class="fps-extra-item">
                                    <span>Баланс CPU / GPU:</span>
                                    <strong id="fpsLatency">—</strong>
                                </div>
                            </div>
                            <div class="fps-rating" id="fpsRating"></div>
                        </div>

                        <div class="fps-placeholder" id="fpsPlaceholder">
                            <i class="fas fa-chart-line fa-3x"></i>
                            <p>Добавьте GPU и выберите игру для расчета FPS</p>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </main>

    <!-- Component Selector Modal -->
    <div class="modal" id="componentModal">
        <div class="modal-overlay" onclick="closeComponentSelector()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Выбор компонента</h3>
                <button class="modal-close" onclick="closeComponentSelector()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="modal-filters">
                    <div class="modal-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="componentSearch" placeholder="Поиск по названию..." onkeyup="filterModalComponents()">
                    </div>
                    
                    <div class="modal-filter-row">
                        <div class="modal-filter-group">
                            <label><i class="fas fa-industry"></i> Производитель</label>
                            <select id="modalManufacturer" onchange="filterModalComponents()">
                                <option value="">Все производители</option>
                            </select>
                        </div>
                        
                        <div class="modal-filter-group">
                            <label><i class="fas fa-ruble-sign"></i> Цена</label>
                            <div class="price-inputs">
                                <input type="number" id="modalMinPrice" placeholder="От" onchange="filterModalComponents()">
                                <span>—</span>
                                <input type="number" id="modalMaxPrice" placeholder="До" onchange="filterModalComponents()">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Category-specific filters -->
                    <div id="categorySpecificFilters" class="category-specific-filters"></div>
                    
                    <div class="modal-sort">
                        <label><i class="fas fa-sort"></i> Сортировка</label>
                        <select id="modalSort" onchange="sortModalComponents()">
                            <option value="price_asc">Цена: по возрастанию</option>
                            <option value="price_desc">Цена: по убыванию</option>
                            <option value="name_asc">Название: А-Я</option>
                            <option value="name_desc">Название: Я-А</option>
                        </select>
                    </div>
                </div>
                
                <div class="modal-results-count" id="modalResultsCount">
                    Найдено: <strong>0</strong> компонентов
                </div>
                
                <div class="components-list" id="componentsList">
                    <!-- Components will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Save Result Modal -->
    <div class="save-modal" id="saveResultModal" aria-hidden="true">
        <div class="save-modal-overlay" onclick="closeSaveResultModal()"></div>
        <div class="save-modal-content" role="dialog" aria-modal="true" aria-labelledby="saveResultTitle">
            <button class="save-modal-close" onclick="closeSaveResultModal()" aria-label="Закрыть">
                <i class="fas fa-times"></i>
            </button>
            <h3 id="saveResultTitle"><i class="fas fa-check-circle"></i> Сборка сохранена</h3>
            <p class="save-modal-description" id="saveResultMessage">Сборка успешно добавлена в раздел «Мои сборки».</p>

            <div class="save-result-meta">
                <div>
                    <span class="meta-label">Название</span>
                    <strong id="saveResultName">—</strong>
                </div>
                <div>
                    <span class="meta-label">ID сохранения</span>
                    <strong id="saveResultId">—</strong>
                </div>
            </div>

            <div class="save-modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeSaveResultModal()">Остаться</button>
                <button type="button" class="btn btn-primary" id="goToMyBuildsBtn">
                    <i class="fas fa-folder-open"></i> Открыть «Мои сборки»
                </button>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <!-- Share Modal -->
    <div class="share-modal" id="shareModal" aria-hidden="true">
        <div class="share-modal-overlay" onclick="closeShareModal()"></div>
        <div class="share-modal-content" role="dialog" aria-modal="true" aria-labelledby="shareModalTitle">
            <button class="share-modal-close" onclick="closeShareModal()" aria-label="Закрыть">
                <i class="fas fa-times"></i>
            </button>
            <h3 id="shareModalTitle"><i class="fas fa-share-nodes"></i> Поделиться сборкой</h3>
            <p>Отправьте ссылку другу или сохраните её, чтобы вернуться к этой конфигурации.</p>
            <div class="share-link-group">
                <input type="text" id="shareLinkInput" readonly>
                <button class="share-copy-btn" onclick="copyShareLink()">
                    <i class="fas fa-copy"></i>
                </button>
            </div>
            <small id="shareStatus" class="share-status"></small>
        </div>
    </div>

    <!-- Save Build Modal -->
    <div class="save-modal" id="saveModal" aria-hidden="true">
        <div class="save-modal-overlay" onclick="closeSaveModal()"></div>
        <div class="save-modal-content" role="dialog" aria-modal="true" aria-labelledby="saveModalTitle">
            <button class="save-modal-close" onclick="closeSaveModal()" aria-label="Закрыть">
                <i class="fas fa-times"></i>
            </button>
            <form id="saveBuildForm">
                <h3 id="saveModalTitle"><i class="fas fa-save"></i> Сохранение сборки</h3>
                <p class="save-modal-description">Дайте имя вашей конфигурации и выберите для неё назначение.</p>

                <div class="form-group">
                    <label for="saveBuildName">Название сборки</label>
                    <input type="text" id="saveBuildName" name="build_name" maxlength="80" placeholder="Например, &quot;Геймерская станция&quot;">
                    <small id="guestSaveNotice" class="save-modal-hint" style="display:none;"></small>
                </div>

                <div class="form-group">
                    <label for="saveBuildCategory">Категория</label>
                    <select id="saveBuildCategory" name="build_category">
                        <option value="gaming">Игровая</option>
                        <option value="work">Рабочая / офисная</option>
                        <option value="streaming">Для стриминга</option>
                        <option value="editing">Для монтажа</option>
                        <option value="other" selected>Другое</option>
                    </select>
                </div>

                <div class="save-modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeSaveModal()">Отмена</button>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Export Modal -->
    <div class="export-modal" id="exportModal" aria-hidden="true">
        <div class="export-modal-overlay" onclick="closeExportModal()"></div>
        <div class="export-modal-content" role="dialog" aria-modal="true" aria-labelledby="exportModalTitle">
            <button class="export-modal-close" aria-label="Закрыть" onclick="closeExportModal()">
                <i class="fas fa-times"></i>
            </button>
            <div class="export-modal-icon">
                <i class="fas fa-file-export"></i>
            </div>
            <h3 id="exportModalTitle">Экспорт сборки</h3>
            <p class="export-modal-description">Выберите формат. Word удобен для отправки и печати, JSON нужен для импорта обратно в конфигуратор.</p>
            <div class="export-options">
                <button type="button" class="export-option" onclick="exportBuildDoc()">
                    <div>
                        <strong>Word (.doc)</strong>
                        <span>Для печати и отправки клиенту</span>
                    </div>
                    <i class="fas fa-file-word"></i>
                </button>
                <button type="button" class="export-option" onclick="exportBuildJson()">
                    <div>
                        <strong>JSON (.json)</strong>
                        <span>Для импорта и продолжения работы</span>
                    </div>
                    <i class="fas fa-code"></i>
                </button>
            </div>
            <div class="export-modal-actions">
                <button type="button" class="btn btn-outline" onclick="closeExportModal()">Отмена</button>
            </div>
        </div>
    </div>

    <!-- Clear Build Modal -->
    <div class="export-modal clear-modal" id="clearModal" aria-hidden="true">
        <div class="export-modal-overlay" onclick="closeClearModal()"></div>
        <div class="export-modal-content" role="dialog" aria-modal="true" aria-labelledby="clearModalTitle">
            <button class="export-modal-close" aria-label="Закрыть" onclick="closeClearModal()">
                <i class="fas fa-times"></i>
            </button>
            <div class="export-modal-icon clear-modal-icon">
                <i class="fas fa-broom"></i>
            </div>
            <h3 id="clearModalTitle">Очистка сборки</h3>
            <p class="export-modal-description">Подтвердите действие. Все выбранные компоненты будут удалены из сборки.</p>
            <div class="export-options">
                <button type="button" class="export-option clear-option" onclick="confirmClearBuild()">
                    <div>
                        <strong>Очистить сборку</strong>
                        <span>Сбросить все выбранные компоненты</span>
                    </div>
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            <div class="export-modal-actions">
                <button type="button" class="btn btn-outline" onclick="closeClearModal()">Отмена</button>
            </div>
        </div>
    </div>

    <script>
        // Pass PHP data to JavaScript
        const categories = <?= json_encode($categories) ?>;
        const games = <?= json_encode($games) ?>;
        const builderUserId = <?= $builderUserId ? (int)$builderUserId : 'null' ?>;
        const siteName = <?= json_encode(SITE_NAME) ?>;
        const siteUrl = <?= json_encode(SITE_URL) ?>;
    </script>
    <script src="js/main.js"></script>
    <script src="js/builder.js?v=<?= time() ?>"></script>
</body>
</html>

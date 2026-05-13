<?php
session_start();
require_once 'config.php';

// Get statistics - count all components from different tables
$totalComponents = 0;
$componentTables = ['components_cpu', 'components_gpu', 'components_mobo', 'components_ram', 
                    'components_storage', 'components_psu', 'components_case', 'components_cooling'];
foreach ($componentTables as $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM $table");
        $totalComponents += $stmt->fetch()['total'];
    } catch (PDOException $e) {
        // Table doesn't exist, skip
    }
}

// Get total builds (table may not exist yet)
$totalBuilds = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM user_builds WHERE is_public = 1");
    $totalBuilds = $stmt->fetch()['total'];
} catch (PDOException $e) {
    // Table doesn't exist yet
}

// Get featured components
$featuredGPU = null;
$featuredCPU = null;
try {
    $featuredGPU = $pdo->query("SELECT * FROM components_gpu ORDER BY performance_score DESC LIMIT 1")->fetch();
    $featuredCPU = $pdo->query("SELECT * FROM components_cpu ORDER BY performance_score DESC LIMIT 1")->fetch();
} catch (PDOException $e) {
    // Tables don't exist yet
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?> - Собери свой идеальный ПК</title>
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-bg"></div>
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title">
                    Собери свой <span class="gradient-text">идеальный ПК</span>
                </h1>
                <p class="hero-subtitle">
                    Профессиональный конфигуратор для создания ПК под ваши задачи.<br>
                    Проверка совместимости, расчет производительности и FPS в играх — и возможность оформить заказ на сборку.
                </p>
                <div class="hero-buttons">
                    <a href="builder.php" class="btn btn-primary">
                        <i class="fas fa-screwdriver-wrench"></i>
                        Начать сборку
                    </a>
                    <a href="catalog.php" class="btn btn-secondary">
                        <i class="fas fa-list"></i>
                        Каталог комплектующих
                    </a>
                </div>
                <div class="hero-stats">
                    <div class="stat-item">
                        <div class="stat-value"><?= $totalComponents ?>+</div>
                        <div class="stat-label">Комплектующих</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?= $totalBuilds ?>+</div>
                        <div class="stat-label">Готовых сборок</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">100%</div>
                        <div class="stat-label">Совместимость</div>
                    </div>
                </div>
            </div>
            <div class="hero-image">
                <div class="floating-card card-1">
                    <i class="fas fa-microchip"></i>
                    <span>CPU</span>
                </div>
                <div class="floating-card card-2">
                    <i class="fas fa-display"></i>
                    <span>GPU</span>
                </div>
                <div class="floating-card card-3">
                    <i class="fas fa-memory"></i>
                    <span>RAM</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="container">
            <h2 class="section-title">Почему выбирают нас?</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3>Проверка совместимости</h3>
                    <p>Автоматическая проверка совместимости всех компонентов. Никаких ошибок при сборке.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-gamepad"></i>
                    </div>
                    <h3>Расчет FPS</h3>
                    <p>Узнайте производительность вашей сборки в популярных играх перед покупкой.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h3>Расчет мощности</h3>
                    <p>Автоматический подбор блока питания с учетом энергопотребления компонентов.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <h3>Контроль бюджета</h3>
                    <p>Отслеживайте стоимость сборки в реальном времени и оптимизируйте затраты.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-share-nodes"></i>
                    </div>
                    <h3>Поделиться сборкой</h3>
                    <p>Сохраняйте и делитесь своими конфигурациями с друзьями и сообществом.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h3>Оформление заказа</h3>
                    <p>Сборку можно оформить как заказ: мы подготовим комплект и сопроводим доставку.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Актуальные цены</h3>
                    <p>Регулярное обновление цен и наличия комплектующих от проверенных продавцов.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="about-site">
        <div class="container">
            <div class="about-site-inner">
                <div class="about-site-copy">
                    <h2>HyperPC — сервис для тех, кому важны детали</h2>
                    <p>Прозрачные характеристики, актуальные цены и понятные рекомендации по каждой сборке — без лишнего шума. Конфигуратор показывает, где можно усилить систему и где уже достигнут баланс.</p>
                    <div class="about-site-points">
                        <div class="about-point">
                            <i class="fas fa-diagram-project"></i>
                            <span>Прозрачные параметры: сокеты, форм‑факторы, слоты</span>
                        </div>
                        <div class="about-point">
                            <i class="fas fa-gauge-high"></i>
                            <span>Понятная оценка производительности и сценариев</span>
                        </div>
                        <div class="about-point">
                            <i class="fas fa-bolt"></i>
                            <span>Аккуратный подбор питания и запаса по мощности</span>
                        </div>
                    </div>
                    <div class="about-site-actions">
                        <a href="builder.php" class="btn btn-primary">
                            <i class="fas fa-rocket"></i>
                            Начать сборку
                        </a>
                        <a href="catalog.php" class="btn btn-outline">
                            <i class="fas fa-layer-group"></i>
                            Каталог компонентов
                        </a>
                    </div>
                </div>
                <div class="about-site-cards">
                    <div class="about-card">
                        <div class="about-card-header">
                            <span>Рекомендации по сборке</span>
                            <i class="fas fa-lightbulb"></i>
                        </div>
                        <p>Система подсвечивает слабые места, чтобы сборка была сбалансированной по производительности.</p>
                    </div>
                    <div class="about-card">
                        <div class="about-card-header">
                            <span>Фильтры по совместимости</span>
                            <i class="fas fa-filter"></i>
                        </div>
                        <p>Авто‑фильтры под материнскую плату: сокеты, тип памяти и охлаждение можно менять вручную.</p>
                    </div>
                    <div class="about-card">
                        <div class="about-card-header">
                            <span>Живые сценарии</span>
                            <i class="fas fa-gamepad"></i>
                        </div>
                        <p>Проверяйте, как сборка ведет себя в играх и рабочих задачах, прежде чем оформлять заказ.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Components -->
    <section class="featured">
        <div class="container">
            <h2 class="section-title">Топовые комплектующие</h2>
            <div class="featured-grid">
                <?php if ($featuredCPU): ?>
                <div class="product-card featured-product">
                    <div class="product-badge">Топ CPU</div>
                    <div class="product-image">
                        <img src="pictures/catalog/cpu.svg" alt="CPU">
                    </div>
                    <div class="product-info">
                        <h3><?= htmlspecialchars($featuredCPU['name']) ?></h3>
                        <div class="product-specs">
                            <?php 
                            $specs = json_decode($featuredCPU['specs'], true);
                            echo $specs['cores'] . ' ядер / ' . $specs['threads'] . ' потоков';
                            ?>
                        </div>
                        <div class="product-footer">
                            <div class="product-price"><?= formatPrice($featuredCPU['price']) ?></div>
                            <div class="product-score">
                                <i class="fas fa-star"></i>
                                <?= $featuredCPU['performance_score'] ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($featuredGPU): ?>
                <div class="product-card featured-product">
                    <div class="product-badge">Топ GPU</div>
                    <div class="product-image">
                        <img src="pictures/catalog/gpu.svg" alt="GPU">
                    </div>
                    <div class="product-info">
                        <h3><?= htmlspecialchars($featuredGPU['name']) ?></h3>
                        <div class="product-specs">
                            <?php 
                            $specs = json_decode($featuredGPU['specs'], true);
                            echo $specs['memory'];
                            ?>
                        </div>
                        <div class="product-footer">
                            <div class="product-price"><?= formatPrice($featuredGPU['price']) ?></div>
                            <div class="product-score">
                                <i class="fas fa-star"></i>
                                <?= $featuredGPU['performance_score'] ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <script src="js/main.js"></script>
</body>
</html>

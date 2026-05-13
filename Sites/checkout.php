<?php
session_start();
require_once 'config.php';
require_once 'includes/security.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$user = null;

if ($isLoggedIn) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
    } catch (PDOException $e) {
        $isLoggedIn = false;
    }
}

$error = '';
$success = '';
$csrfToken = Security::generateCSRFToken();

// Handle order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    if (!$isLoggedIn) {
        $error = 'Войдите в аккаунт для оформления заказа';
    } elseif (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Недействительный запрос';
    } else {
        $fullName = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $postalCode = trim($_POST['postal_code'] ?? '');
        $deliveryMethod = $_POST['delivery_method'] ?? 'courier';
        $paymentMethod = $_POST['payment_method'] ?? 'card';
        $notes = trim($_POST['notes'] ?? '');
        $buildData = $_POST['build_data'] ?? '';
        
        // Validation
        if (empty($fullName) || empty($phone) || empty($email) || empty($address) || empty($city)) {
            $error = 'Пожалуйста, заполните все обязательные поля';
        } elseif (strlen($fullName) < 2 || strlen($fullName) > 100) {
            $error = 'Имя должно содержать от 2 до 100 символов';
        } elseif (!preg_match('/^[а-яёА-ЯЁa-zA-Z\s\-]+$/u', $fullName)) {
            $error = 'Имя может содержать только буквы, пробелы и дефисы';
        } elseif (!preg_match('/^\+?[0-9\s\-\(\)]{10,20}$/', $phone)) {
            $error = 'Введите корректный номер телефона';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Введите корректный email адрес';
        } elseif (strlen($address) < 5 || strlen($address) > 255) {
            $error = 'Адрес должен содержать от 5 до 255 символов';
        } elseif (strlen($city) < 2 || strlen($city) > 100) {
            $error = 'Название города должно содержать от 2 до 100 символов';
        } elseif (!preg_match('/^[а-яёА-ЯЁa-zA-Z\s\-]+$/u', $city)) {
            $error = 'Название города может содержать только буквы, пробелы и дефисы';
        } elseif (!empty($postalCode) && !preg_match('/^[0-9]{6}$/', $postalCode)) {
            $error = 'Почтовый индекс должен содержать 6 цифр';
        } elseif (!in_array($deliveryMethod, ['courier', 'pickup', 'post'])) {
            $error = 'Выберите корректный способ доставки';
        } elseif (!in_array($paymentMethod, ['card', 'cash', 'online'])) {
            $error = 'Выберите корректный способ оплаты';
        } elseif (strlen($notes) > 1000) {
            $error = 'Комментарий не должен превышать 1000 символов';
        } elseif (empty($buildData)) {
            $error = 'Данные сборки не найдены';
        } else {
            try {
                $build = json_decode($buildData, true);
                if (!$build || empty($build)) {
                    throw new Exception('Некорректные данные сборки');
                }

                $categoryNameMap = [];
                try {
                    $categories = $pdo->query("SELECT id, name FROM categories ORDER BY id")->fetchAll();
                    foreach ($categories as $category) {
                        $categoryNameMap[(int)$category['id']] = $category['name'];
                    }
                } catch (PDOException $e) {
                    $categoryNameMap = [];
                }
                
                // Flatten build data (handle arrays for storage)
                $flattenedBuild = [];
                foreach ($build as $categoryId => $componentData) {
                    if (is_array($componentData) && isset($componentData[0]) && is_array($componentData[0])) {
                        // This is an array of components (storage)
                        foreach ($componentData as $item) {
                            $flattenedBuild[] = $item;
                        }
                    } else {
                        // Single component
                        $flattenedBuild[] = $componentData;
                    }
                }
                
                // Calculate total
                $totalAmount = 0;
                foreach ($flattenedBuild as $component) {
                    $totalAmount += $component['price'] * ($component['quantity'] ?? 1);
                }
                
                // Generate order number
                $orderNumber = 'ORD-' . strtoupper(bin2hex(random_bytes(4)));
                
                // Full delivery address
                $fullAddress = "$address, $city, $postalCode";
                
                // Start transaction
                $pdo->beginTransaction();
                
                // Insert order
                $stmt = $pdo->prepare("
                    INSERT INTO orders (
                        user_id, order_number, status, total_amount, 
                        delivery_address, delivery_method, payment_method, 
                        payment_status, notes, created_at
                    ) VALUES (?, ?, 'pending', ?, ?, ?, ?, 'pending', ?, NOW())
                ");
                $stmt->execute([
                    $_SESSION['user_id'],
                    $orderNumber,
                    $totalAmount,
                    $fullAddress,
                    $deliveryMethod,
                    $paymentMethod,
                    $notes
                ]);
                
                $orderId = $pdo->lastInsertId();
                
                // Insert order items
                $stmt = $pdo->prepare("
                    INSERT INTO order_items (
                        order_id, component_id, component_name, 
                        component_category, quantity, price
                    ) VALUES (?, ?, ?, ?, ?, ?)
                ");
                
                foreach ($flattenedBuild as $component) {
                    $categoryLabel = 'Комплектующее';
                    if (!empty($component['category'])) {
                        $categoryLabel = (string)$component['category'];
                    } elseif (!empty($component['category_id']) && isset($categoryNameMap[(int)$component['category_id']])) {
                        $categoryLabel = $categoryNameMap[(int)$component['category_id']];
                    }
                    $stmt->execute([
                        $orderId,
                        $component['id'],
                        $component['name'],
                        $categoryLabel,
                        $component['quantity'] ?? 1,
                        $component['price']
                    ]);
                }
                
                $pdo->commit();
                
                // Clear checkout build from localStorage (will be done via JS)
                $success = "Заказ успешно оформлен! Номер заказа: <strong>$orderNumber</strong>";
                
                Security::logSecurityEvent('Order placed', [
                    'order_number' => $orderNumber,
                    'user_id' => $_SESSION['user_id'],
                    'total_amount' => $totalAmount
                ]);
                
                // Redirect to orders page after 3 seconds
                header("refresh:3;url=orders.php");
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Ошибка оформления заказа. Попробуйте позже';
                error_log('Checkout error: ' . $e->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Оформление заказа - <?= SITE_NAME ?></title>
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/checkout.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="checkout-page">
        <div class="container">
            <?php if (!$isLoggedIn): ?>
                <!-- Not logged in message -->
                <div class="auth-required">
                    <div class="auth-icon">
                        <i class="fas fa-lock"></i>
                    </div>
                    <h2>Требуется авторизация</h2>
                    <p>Для оформления заказа необходимо войти в аккаунт или зарегистрироваться</p>
                    <div class="auth-actions">
                        <a href="login.php?redirect=checkout.php" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt"></i>
                            Войти
                        </a>
                        <a href="register.php?redirect=checkout.php" class="btn btn-secondary">
                            <i class="fas fa-user-plus"></i>
                            Регистрация
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="checkout-header">
                    <h1><i class="fas fa-shopping-cart"></i> Оформление заказа</h1>
                    <p>Заполните данные для доставки вашей сборки</p>
                </div>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <div>
                            <strong>Успешно!</strong>
                            <p><?= $success ?></p>
                            <p style="margin-top: 8px; font-size: 14px;">Перенаправление на страницу заказов...</p>
                        </div>
                    </div>
                    <script>
                        // Clear checkout build from localStorage after successful order
                        localStorage.removeItem('checkoutBuild');
                    </script>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <div>
                            <strong>Ошибка!</strong>
                            <p><?= htmlspecialchars($error) ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="checkout-container">
                    <!-- Order Summary -->
                    <div class="order-summary">
                        <div class="summary-card-header">
                            <div class="summary-title">
                                <div class="summary-icon">
                                    <i class="fas fa-box-open"></i>
                                </div>
                                <div>
                                    <h2>Ваша сборка</h2>
                                    <p>Проверьте конфигурацию перед оплатой</p>
                                </div>
                            </div>
                            <div class="summary-chips">
                                <div class="summary-chip" id="summaryComponentsChip">
                                    <i class="fas fa-puzzle-piece"></i>
                                    <span>0 компонентов</span>
                                </div>
                            </div>
                        </div>
                        <div id="buildSummary" class="build-items">
                            <!-- Will be filled by JavaScript -->
                        </div>
                        <div class="summary-total-card">
                            <div>
                                <span>Итого</span>
                                <small>С учётом выбранных комплектующих</small>
                            </div>
                            <strong id="totalPrice">0 ₽</strong>
                        </div>
                    </div>

                    <!-- Checkout Form -->
                    <div class="checkout-form-container">
                        <form method="POST" action="" id="checkoutForm">
                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                            <input type="hidden" name="build_data" id="buildDataInput">

                            <!-- Contact Information -->
                            <div class="form-section">
                                <h3><i class="fas fa-user"></i> Контактная информация</h3>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Полное имя *</label>
                                        <input type="text" name="full_name" placeholder="Иван Иванов" value="<?= htmlspecialchars($user['username'] ?? '') ?>" required>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Телефон *</label>
                                        <input type="tel" name="phone" placeholder="+7 (999) 123-45-67" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Email *</label>
                                        <input type="email" name="email" placeholder="your@email.com" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                                    </div>
                                </div>
                            </div>

                            <!-- Delivery Address -->
                            <div class="form-section">
                                <h3><i class="fas fa-map-marker-alt"></i> Адрес доставки</h3>
                                
                                <div class="form-group">
                                    <label>Адрес *</label>
                                    <input type="text" name="address" placeholder="Улица, дом, квартира" required>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Город *</label>
                                        <input type="text" name="city" placeholder="Москва" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Индекс</label>
                                        <input type="text" name="postal_code" placeholder="123456">
                                    </div>
                                </div>
                            </div>

                            <!-- Delivery Method -->
                            <div class="form-section">
                                <h3><i class="fas fa-truck"></i> Способ доставки</h3>
                                
                                <div class="radio-group">
                                    <label class="radio-option">
                                        <input type="radio" name="delivery_method" value="courier" checked>
                                        <div class="radio-content">
                                            <div class="radio-title">
                                                <i class="fas fa-motorcycle"></i>
                                                Курьером
                                            </div>
                                            <div class="radio-description">Доставка в течение 1-3 дней</div>
                                        </div>
                                    </label>

                                    <label class="radio-option">
                                        <input type="radio" name="delivery_method" value="pickup">
                                        <div class="radio-content">
                                            <div class="radio-title">
                                                <i class="fas fa-store"></i>
                                                Самовывоз
                                            </div>
                                            <div class="radio-description">Забрать из магазина</div>
                                        </div>
                                    </label>

                                    <label class="radio-option">
                                        <input type="radio" name="delivery_method" value="express">
                                        <div class="radio-content">
                                            <div class="radio-title">
                                                <i class="fas fa-bolt"></i>
                                                Экспресс
                                            </div>
                                            <div class="radio-description">Доставка в день заказа</div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Payment Method -->
                            <div class="form-section">
                                <h3><i class="fas fa-credit-card"></i> Способ оплаты</h3>
                                
                                <div class="radio-group">
                                    <label class="radio-option">
                                        <input type="radio" name="payment_method" value="card" checked>
                                        <div class="radio-content">
                                            <div class="radio-title">
                                                <i class="fas fa-credit-card"></i>
                                                Банковская карта
                                            </div>
                                            <div class="radio-description">Visa, MasterCard, МИР</div>
                                        </div>
                                    </label>

                                    <label class="radio-option">
                                        <input type="radio" name="payment_method" value="cash">
                                        <div class="radio-content">
                                            <div class="radio-title">
                                                <i class="fas fa-money-bill-wave"></i>
                                                Наличными
                                            </div>
                                            <div class="radio-description">При получении</div>
                                        </div>
                                    </label>

                                    <label class="radio-option">
                                        <input type="radio" name="payment_method" value="online">
                                        <div class="radio-content">
                                            <div class="radio-title">
                                                <i class="fas fa-wallet"></i>
                                                Онлайн-оплата
                                            </div>
                                            <div class="radio-description">СБП, ЮMoney, QIWI</div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Additional Notes -->
                            <div class="form-section">
                                <h3><i class="fas fa-comment"></i> Комментарий к заказу</h3>
                                <div class="form-group">
                                    <textarea name="notes" placeholder="Дополнительные пожелания или инструкции..." rows="4"></textarea>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="form-actions">
                                <button type="submit" name="place_order" class="btn btn-primary btn-lg">
                                    <i class="fas fa-check"></i>
                                    Оформить заказ
                                </button>
                                <a href="builder.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i>
                                    Вернуться к сборке
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="js/main.js"></script>
    <script>
        // Load build from localStorage
        const checkoutBuild = JSON.parse(localStorage.getItem('checkoutBuild') || '{}');
        
        // Flatten arrays (for storage) into individual items
        const buildItems = [];
        Object.entries(checkoutBuild).forEach(([categoryId, data]) => {
            const normalizedCategoryId = Number(categoryId);
            if (Array.isArray(data)) {
                // For arrays (storage), add each item separately
                data.forEach(item => {
                    if (item && typeof item === 'object' && !item.category_id) {
                        item.category_id = normalizedCategoryId;
                    }
                    buildItems.push(item);
                });
            } else {
                // For single components
                if (data && typeof data === 'object' && !data.category_id) {
                    data.category_id = normalizedCategoryId;
                }
                buildItems.push(data);
            }
        });
        
        if (buildItems.length === 0 && <?= $isLoggedIn ? 'true' : 'false' ?>) {
            window.location.href = 'builder.php';
        }
        
        // Display build summary
        const buildSummary = document.getElementById('buildSummary');
        const buildDataInput = document.getElementById('buildDataInput');
        const componentsChip = document.querySelector('#summaryComponentsChip span');
        const powerChip = null;
        buildSummary.innerHTML = '';
        let totalPrice = 0;
        let totalPower = 0;
        
        const CATEGORY_META = {
            cpu: { icon: 'fa-microchip', label: 'Процессор', keywords: ['cpu', 'процессор', 'processor'] },
            gpu: { icon: 'fa-display', label: 'Видеокарта', keywords: ['gpu', 'видео', 'videocard', 'graphics'] },
            motherboard: { icon: 'fa-memory', label: 'Материнская плата', keywords: ['mat', 'motherboard', 'плата'] },
            ram: { icon: 'fa-memory', label: 'Оперативная память', keywords: ['ram', 'оператив', 'память'] },
            storage: { icon: 'fa-hard-drive', label: 'Накопитель', keywords: ['ssd', 'hdd', 'storage', 'накоп'] },
            psu: { icon: 'fa-bolt', label: 'Блок питания', keywords: ['psu', 'питания', 'блок'] },
            case: { icon: 'fa-box', label: 'Корпус', keywords: ['case', 'корпус'] },
            cooling: { icon: 'fa-fan', label: 'Охлаждение', keywords: ['cool', 'охлаж', 'кулер'] },
            default: { icon: 'fa-puzzle-piece', label: 'Компонент', keywords: [] }
        };

        const CATEGORY_LABEL_BY_ID = {
            1: 'Процессор',
            2: 'Видеокарта',
            3: 'Материнская плата',
            4: 'Оперативная память',
            5: 'Накопитель',
            6: 'Блок питания',
            7: 'Корпус',
            8: 'Охлаждение'
        };

        const CATEGORY_THEME_BY_ID = {
            1: 'cpu',
            2: 'gpu',
            3: 'motherboard',
            4: 'ram',
            5: 'storage',
            6: 'psu',
            7: 'case',
            8: 'cooling'
        };
        
        function resolveCategoryTheme(categoryName = '', slug = '', categoryId = null) {
            if (categoryId && CATEGORY_THEME_BY_ID[categoryId]) {
                return CATEGORY_THEME_BY_ID[categoryId];
            }
            const normalizedSlug = slug.toLowerCase();
            if (CATEGORY_META[normalizedSlug]) {
                return normalizedSlug;
            }
            const normalizedName = categoryName.toString().toLowerCase();
            for (const [theme, meta] of Object.entries(CATEGORY_META)) {
                if (meta.keywords.some(keyword => normalizedName.includes(keyword))) {
                    return theme;
                }
            }
            return 'default';
        }

        function resolveCategoryLabel(component, themeMeta) {
            const categoryId = Number(component.category_id || component.categoryId || component.category_id_fk);
            if (CATEGORY_LABEL_BY_ID[categoryId]) {
                return CATEGORY_LABEL_BY_ID[categoryId];
            }
            if (component.category_name) {
                return component.category_name;
            }
            if (component.category && typeof component.category === 'string') {
                return component.category;
            }
            const name = (component.name || '').toLowerCase();
            for (const meta of Object.values(CATEGORY_META)) {
                if (meta.keywords.some(keyword => name.includes(keyword))) {
                    return meta.label;
                }
            }
            return themeMeta.label || 'Компонент';
        }
        
        if (buildItems.length === 0) {
            buildSummary.innerHTML = `
                <div class="summary-empty">
                    <i class="fas fa-box-open"></i>
                    <p>Добавьте компоненты в сборщик, чтобы оформить заказ</p>
                    <a href="builder.php" class="btn btn-secondary btn-sm">Перейти к сборке</a>
                </div>
            `;
        }
        
        buildItems.forEach(component => {
            const quantity = component.quantity || 1;
            const price = (component.price || 0) * quantity;
            totalPrice += price;
            const estimatedPower = (component.power || component.tdp || component.wattage || component.psu_wattage || 0) * quantity;
            totalPower += estimatedPower;
        });
        
        buildItems.forEach(component => {
            const quantity = component.quantity || 1;
            const price = (component.price || 0) * quantity;
            const estimatedPower = (component.power || component.tdp || component.wattage || component.psu_wattage || 0) * quantity;
            const costShare = 0;
            const categoryName = component.category || component.slug || 'Компонент';
            const categoryId = Number(component.category_id || component.categoryId || component.category_id_fk);
            const theme = resolveCategoryTheme(categoryName, component.slug || '', categoryId || null);
            const meta = CATEGORY_META[theme] || CATEGORY_META.default;
            const categoryLabel = resolveCategoryLabel(component, meta);
            const brand = component.manufacturer || component.brand || '';
            const model = component.model || '';
            const subtitle = [brand, model].filter(Boolean).join(' • ');
            const badges = [];
            if (quantity > 1) badges.push(`<span class="item-badge badge-quantity">×${quantity}</span>`);
            if (estimatedPower) badges.push(`<span class="item-badge badge-power"><i class='fas fa-bolt'></i>${estimatedPower}Вт</span>`);
            const priceMeta = [];
            if (quantity > 1) {
                priceMeta.push(`${formatPrice(component.price)} за шт.`);
            }
            
            const item = document.createElement('div');
            item.className = 'build-item';
            item.dataset.categoryTheme = theme;
            item.innerHTML = `
                <div class="item-media">
                    <i class="fas ${meta.icon}"></i>
                </div>
                <div class="item-info">
                    <div class="item-name">${component.name || 'Компонент'}</div>
                    ${subtitle ? `<div class="item-subtitle">${subtitle}</div>` : ''}
                    <div class="item-price-inline">${formatPrice(price)}</div>
                    <div class="item-meta">
                        <span class="item-category-pill">${categoryLabel}</span>
                        ${badges.join('')}
                    </div>
                </div>
                <div class="item-price">
                    ${formatPrice(price)}
                    ${priceMeta.length ? `<small>${priceMeta.join(' · ')}</small>` : ''}
                </div>
            `;
            buildSummary.appendChild(item);
        });
        
        document.getElementById('totalPrice').textContent = formatPrice(totalPrice);
        if (componentsChip) {
            componentsChip.textContent = buildItems.length
                ? `${buildItems.length} ${getComponentWord(buildItems.length)}`
                : 'Нет компонентов';
        }
        if (powerChip) {
            powerChip.textContent = '';
        }
        if (buildDataInput) {
            buildDataInput.value = JSON.stringify(checkoutBuild);
        }
        
        // Clear checkout build after successful order
        <?php if ($success): ?>
            localStorage.removeItem('checkoutBuild');
        <?php endif; ?>
        
        function formatPrice(price) {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB',
                minimumFractionDigits: 0
            }).format(price);
        }
        
        // Form validation
        const checkoutForm = document.getElementById('checkoutForm');
        if (checkoutForm) {
            checkoutForm.addEventListener('submit', function(e) {
                const fullName = document.querySelector('input[name="full_name"]').value.trim();
                const phone = document.querySelector('input[name="phone"]').value.trim();
                const email = document.querySelector('input[name="email"]').value.trim();
                const address = document.querySelector('input[name="address"]').value.trim();
                const city = document.querySelector('input[name="city"]').value.trim();
                const postalCode = document.querySelector('input[name="postal_code"]').value.trim();
                const notes = document.querySelector('textarea[name="notes"]').value.trim();
                
                // Full name validation
                if (fullName.length < 2 || fullName.length > 100) {
                    e.preventDefault();
                    showError('Имя должно содержать от 2 до 100 символов');
                    return false;
                }
                if (!/^[а-яёА-ЯЁa-zA-Z\s\-]+$/u.test(fullName)) {
                    e.preventDefault();
                    showError('Имя может содержать только буквы, пробелы и дефисы');
                    return false;
                }
                
                // Phone validation
                if (!/^\+?[0-9\s\-\(\)]{10,20}$/.test(phone)) {
                    e.preventDefault();
                    showError('Введите корректный номер телефона (например: +7 999 123-45-67)');
                    return false;
                }
                
                // Email validation
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                    e.preventDefault();
                    showError('Введите корректный email адрес');
                    return false;
                }
                
                // Address validation
                if (address.length < 5 || address.length > 255) {
                    e.preventDefault();
                    showError('Адрес должен содержать от 5 до 255 символов');
                    return false;
                }
                
                // City validation
                if (city.length < 2 || city.length > 100) {
                    e.preventDefault();
                    showError('Название города должно содержать от 2 до 100 символов');
                    return false;
                }
                if (!/^[а-яёА-ЯЁa-zA-Z\s\-]+$/u.test(city)) {
                    e.preventDefault();
                    showError('Название города может содержать только буквы, пробелы и дефисы');
                    return false;
                }
                
                // Postal code validation (optional)
                if (postalCode && !/^[0-9]{6}$/.test(postalCode)) {
                    e.preventDefault();
                    showError('Почтовый индекс должен содержать 6 цифр');
                    return false;
                }
                
                // Notes validation
                if (notes.length > 1000) {
                    e.preventDefault();
                    showError('Комментарий не должен превышать 1000 символов');
                    return false;
                }
                
                return true;
            });
            
            // Real-time phone formatting
            const phoneInput = document.querySelector('input[name="phone"]');
            if (phoneInput) {
                phoneInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/[^\d+]/g, '');
                    if (value.startsWith('8')) {
                        value = '+7' + value.substring(1);
                    }
                    if (value.startsWith('7') && !value.startsWith('+7')) {
                        value = '+7' + value.substring(1);
                    }
                    
                    // Format: +7 (XXX) XXX-XX-XX
                    if (value.startsWith('+7') && value.length > 2) {
                        const numbers = value.substring(2);
                        let formatted = '+7';
                        if (numbers.length > 0) {
                            formatted += ' (' + numbers.substring(0, 3);
                        }
                        if (numbers.length >= 3) {
                            formatted += ') ' + numbers.substring(3, 6);
                        }
                        if (numbers.length >= 6) {
                            formatted += '-' + numbers.substring(6, 8);
                        }
                        if (numbers.length >= 8) {
                            formatted += '-' + numbers.substring(8, 10);
                        }
                        e.target.value = formatted;
                    } else {
                        e.target.value = value;
                    }
                });
            }
            
            // Postal code formatting
            const postalInput = document.querySelector('input[name="postal_code"]');
            if (postalInput) {
                postalInput.addEventListener('input', function(e) {
                    e.target.value = e.target.value.replace(/[^\d]/g, '').substring(0, 6);
                });
            }
        }
        
        function getComponentWord(count) {
            const mod10 = count % 10;
            const mod100 = count % 100;
            if (mod10 === 1 && mod100 !== 11) return 'компонент';
            if (mod10 >= 2 && mod10 <= 4 && (mod100 < 10 || mod100 >= 20)) return 'компонента';
            return 'компонентов';
        }
        
        function showError(message) {
            const existingAlert = document.querySelector('.alert-error');
            if (existingAlert) {
                existingAlert.remove();
            }
            
            const alert = document.createElement('div');
            alert.className = 'alert alert-error';
            alert.style.animation = 'slideInDown 0.3s ease';
            alert.innerHTML = `
                <i class="fas fa-exclamation-circle"></i>
                <div>
                    <strong>Ошибка валидации!</strong>
                    <p>${message}</p>
                </div>
            `;
            
            const container = document.querySelector('.checkout-container');
            container.parentNode.insertBefore(alert, container);
            
            // Scroll to alert
            alert.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            // Remove after 5 seconds
            setTimeout(() => {
                alert.style.animation = 'slideOutUp 0.3s ease';
                setTimeout(() => alert.remove(), 300);
            }, 5000);
        }
    </script>
</body>
</html>

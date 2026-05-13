<?php
session_start();
require_once 'config.php';
require_once 'includes/components_union.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$componentSource = getComponentsUnionSource();
$categoryNameMap = [];
try {
    $categories = $pdo->query("SELECT id, name FROM categories ORDER BY id")->fetchAll();
    foreach ($categories as $category) {
        $categoryNameMap[(int)$category['id']] = $category['name'];
    }
} catch (PDOException $e) {
    $categoryNameMap = [];
}

function resolveOrderItemCategory(array $item, PDO $pdo, string $componentSource, array $categoryNameMap, array &$cache): string {
    $raw = trim((string)($item['component_category'] ?? ''));
    if ($raw !== '' && mb_strtolower($raw) !== 'unknown') {
        if (ctype_digit($raw)) {
            $categoryId = (int)$raw;
            if (isset($categoryNameMap[$categoryId])) {
                return $categoryNameMap[$categoryId];
            }
        }
        return $raw;
    }

    $nameKey = $item['component_name'] ?? null;
    if ($nameKey && isset($cache['name:' . $nameKey])) {
        return $cache['name:' . $nameKey];
    }
    $idKey = $item['component_id'] ?? null;
    if ($idKey && isset($cache['id:' . $idKey])) {
        return $cache['id:' . $idKey];
    }

    $categoryId = null;
    if ($componentSource) {
        $componentName = trim((string)($item['component_name'] ?? ''));
        $componentId = $item['component_id'] ?? null;
        $params = [];
        $conditions = [];
        $orderParts = [];

        if ($componentName !== '') {
            $conditions[] = "name = ?";
            $params[] = $componentName;
            $conditions[] = "model = ?";
            $params[] = $componentName;
            $conditions[] = "? LIKE CONCAT('%', model, '%')";
            $params[] = $componentName;
            $conditions[] = "? LIKE CONCAT('%', name, '%')";
            $params[] = $componentName;

            $orderParts[] = "(name = ?) DESC";
            $params[] = $componentName;
            $orderParts[] = "(model = ?) DESC";
            $params[] = $componentName;
            $orderParts[] = "(? LIKE CONCAT('%', model, '%')) DESC";
            $params[] = $componentName;
            $orderParts[] = "(? LIKE CONCAT('%', name, '%')) DESC";
            $params[] = $componentName;
        }

        if (!empty($componentId)) {
            $conditions[] = "id = ?";
            $params[] = $componentId;
            $orderParts[] = "(id = ?) DESC";
            $params[] = $componentId;
        }

        if (!empty($conditions)) {
            try {
                $where = implode(' OR ', $conditions);
                $order = !empty($orderParts) ? ' ORDER BY ' . implode(', ', $orderParts) : '';
                $stmt = $pdo->prepare("SELECT category_id FROM {$componentSource} AS components_union WHERE {$where}{$order} LIMIT 1");
                $stmt->execute($params);
                $categoryId = $stmt->fetchColumn();
            } catch (PDOException $e) {
                $categoryId = null;
            }
        }
    }

    $label = $categoryId && isset($categoryNameMap[(int)$categoryId])
        ? $categoryNameMap[(int)$categoryId]
        : 'Комплектующее';

    if ($nameKey) {
        $cache['name:' . $nameKey] = $label;
    }
    if ($idKey) {
        $cache['id:' . $idKey] = $label;
    }

    return $label;
}

// Get user orders
$orders = [];
try {
    $stmt = $pdo->prepare("
        SELECT o.*, 
               COUNT(oi.id) as items_count,
               SUM(oi.quantity) as total_items
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.user_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$userId]);
    $orders = $stmt->fetchAll();
    
    // Get order items and support messages for each order
    foreach ($orders as &$order) {
        $stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ? ORDER BY id");
        $stmt->execute([$order['id']]);
        $order['items'] = $stmt->fetchAll();
        
        // Get support messages
        try {
            $stmt = $pdo->prepare("
                SELECT sm.*, u.username, u.avatar, u.role
                FROM support_messages sm
                LEFT JOIN users u ON sm.user_id = u.id
                WHERE sm.order_id = ?
                ORDER BY sm.created_at ASC
            ");
            $stmt->execute([$order['id']]);
            $order['support_messages'] = $stmt->fetchAll();
            
            // Count unread messages from support
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as unread_count
                FROM support_messages
                WHERE order_id = ? AND is_support = 1 AND is_read = 0
            ");
            $stmt->execute([$order['id']]);
            $order['unread_count'] = (int)$stmt->fetchColumn();

            // Mark messages as read once user sees them on the orders page
            if ($order['unread_count'] > 0) {
                $stmt = $pdo->prepare("UPDATE support_messages SET is_read = 1 WHERE order_id = ? AND is_support = 1 AND is_read = 0");
                $stmt->execute([$order['id']]);
                $order['unread_count'] = 0;
            }
        } catch (PDOException $e) {
            $order['support_messages'] = [];
            $order['unread_count'] = 0;
        }
    }
    unset($order);
} catch (PDOException $e) {
    // Orders table doesn't exist yet
    $orders = [];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои заказы - <?= SITE_NAME ?></title>
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
    <link rel="stylesheet" href="css/orders.css">
    <script src="js/orders.js" defer></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="orders-page">
        <div class="container">
            <div class="orders-header">
                <h1><i class="fas fa-shopping-bag"></i> Мои заказы</h1>
                <p>История покупок HyperPC и актуальный статус сборок</p>
            </div>

            <div class="orders-container">
                <?php if (empty($orders)): ?>
                    <div class="empty-state">
                        <i class="fas fa-box-open"></i>
                        <h2>У вас пока нет заказов</h2>
                        <p>Создайте свою первую сборку ПК и оформите заказ</p>
                        <a href="builder.php" class="btn-build-pc">
                            <i class="fas fa-screwdriver-wrench"></i>
                            <span>Собрать ПК</span>
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <div class="order-card">
                            <div class="order-header">
                                <div class="order-info">
                                    <div class="order-number">
                                        <i class="fas fa-hashtag"></i>
                                        Заказ <?= htmlspecialchars($order['order_number'] ?? $order['id']) ?>
                                    </div>
                                    <div class="order-date">
                                        <i class="fas fa-clock"></i>
                                        <?= date('d.m.Y H:i', strtotime($order['created_at'])) ?>
                                    </div>
                                </div>
                                <div class="order-badges">
                                    <div class="order-status status-badge status-<?= strtolower($order['status']) ?>">
                                        <?php
                                        $statusData = [
                                            'pending' => ['label' => 'Ожидает подтверждения', 'icon' => 'fa-clock'],
                                            'confirmed' => ['label' => 'Подтвержден', 'icon' => 'fa-check-circle'],
                                            'assembling' => ['label' => 'Собирается', 'icon' => 'fa-screwdriver-wrench'],
                                            'shipping' => ['label' => 'В пути', 'icon' => 'fa-truck-fast'],
                                            'ready_pickup' => ['label' => 'Ждет получения', 'icon' => 'fa-box-open'],
                                            'completed' => ['label' => 'Получен', 'icon' => 'fa-circle-check'],
                                            'cancelled' => ['label' => 'Отменён', 'icon' => 'fa-circle-xmark'],
                                            // Fallback для старых статусов
                                            'processing' => ['label' => 'В обработке', 'icon' => 'fa-gear'],
                                            'shipped' => ['label' => 'Отправлен', 'icon' => 'fa-truck-fast'],
                                            'delivered' => ['label' => 'Доставлен', 'icon' => 'fa-circle-check']
                                        ];
                                        $status = $statusData[$order['status']] ?? ['label' => $order['status'], 'icon' => 'fa-circle'];
                                        ?>
                                        <i class="fas <?= $status['icon'] ?>"></i>
                                        <?= $status['label'] ?>
                                    </div>
                                    <?php if ($order['payment_status'] === 'paid'): ?>
                                    <div class="payment-badge paid">
                                        <i class="fas fa-circle-check"></i>
                                        Оплачен
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="order-details">
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="fas fa-box"></i>
                                    </div>
                                    <div class="detail-content">
                                        <div class="detail-label">Товаров</div>
                                        <div class="detail-value"><?= $order['total_items'] ?? 0 ?> шт.</div>
                                    </div>
                                </div>

                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="fas fa-ruble-sign"></i>
                                    </div>
                                    <div class="detail-content">
                                        <div class="detail-label">Сумма</div>
                                        <div class="detail-value"><?= formatPrice($order['total_amount']) ?></div>
                                    </div>
                                </div>

                                <?php if (!empty($order['delivery_address'])): ?>
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="fas fa-location-dot"></i>
                                    </div>
                                    <div class="detail-content">
                                        <div class="detail-label">Адрес доставки</div>
                                        <div class="detail-value detail-address"><?= htmlspecialchars($order['delivery_address']) ?></div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>

                            <div class="order-actions">
                                <button class="btn-order btn-primary" onclick="toggleOrderDetails(<?= $order['id'] ?>)" id="details-btn-<?= $order['id'] ?>">
                                    <i class="fas fa-chevron-down btn-icon"></i>
                                    <span>Подробнее</span>
                                </button>
                                <button class="btn-order btn-notifications" onclick="toggleSupportChat(<?= $order['id'] ?>)" id="chat-btn-<?= $order['id'] ?>">
                                    <i class="fas fa-comments"></i>
                                    <span>Уведомления</span>
                                    <?php if ($order['unread_count'] > 0): ?>
                                    <span class="notification-badge"><?= $order['unread_count'] ?></span>
                                    <?php endif; ?>
                                </button>
                                <?php if ($order['payment_status'] !== 'paid' && $order['status'] !== 'cancelled' && $order['status'] !== 'completed'): ?>
                                <button class="btn-order btn-pay" onclick="payOrder(<?= $order['id'] ?>)">
                                    <i class="fas fa-credit-card"></i>
                                    <span>Оплатить</span>
                                </button>
                                <?php endif; ?>
                                <?php if (in_array($order['status'], ['pending', 'confirmed'])): ?>
                                <button class="btn-order btn-cancel" onclick="cancelOrder(<?= $order['id'] ?>)">
                                    <i class="fas fa-times"></i>
                                    <span>Отменить</span>
                                </button>
                                <?php endif; ?>
                            </div>

                            <!-- Expandable Details -->
                            <div class="order-expanded-details" id="details-<?= $order['id'] ?>">
                                <div class="expanded-content">
                                    <!-- Order Items -->
                                    <?php if (!empty($order['items'])): ?>
                                    <div class="order-items-list">
                                        <div class="order-items-title">
                                            <i class="fas fa-box-open"></i>
                                            Состав заказа
                                        </div>
                                    <?php
                                    $categoryCache = [];
                                    foreach ($order['items'] as $item):
                                        $resolvedCategory = resolveOrderItemCategory($item, $pdo, $componentSource, $categoryNameMap, $categoryCache);
                                    ?>
                                        <div class="order-item">
                                            <div class="item-details">
                                                <div class="item-name"><?= htmlspecialchars($item['component_name']) ?></div>
                                                <div class="item-category"><?= htmlspecialchars($resolvedCategory) ?></div>
                                            </div>
                                            <div class="item-price"><?= formatPrice($item['price']) ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>

                                    <!-- Additional Info -->
                                    <div class="expanded-info-grid">
                                        <?php if (!empty($order['delivery_method'])): ?>
                                        <div class="info-block">
                                            <div class="info-block-title">
                                                <i class="fas fa-truck"></i>
                                                Способ доставки
                                            </div>
                                            <div class="info-block-content">
                                                <?php
                                                $deliveryMethods = [
                                                    'courier' => 'Курьером',
                                                    'pickup' => 'Самовывоз',
                                                    'express' => 'Экспресс-доставка'
                                                ];
                                                echo $deliveryMethods[$order['delivery_method']] ?? $order['delivery_method'];
                                                ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>

                                        <?php if (!empty($order['payment_method'])): ?>
                                        <div class="info-block">
                                            <div class="info-block-title">
                                                <i class="fas fa-credit-card"></i>
                                                Способ оплаты
                                            </div>
                                            <div class="info-block-content">
                                                <?php
                                                $paymentMethods = [
                                                    'card' => 'Банковская карта',
                                                    'cash' => 'Наличными при получении',
                                                    'online' => 'Онлайн-оплата'
                                                ];
                                                echo $paymentMethods[$order['payment_method']] ?? $order['payment_method'];
                                                ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>

                                        <?php if (!empty($order['notes'])): ?>
                                        <div class="info-block">
                                            <div class="info-block-title">
                                                <i class="fas fa-comment"></i>
                                                Комментарий
                                            </div>
                                            <div class="info-block-content">
                                                <?= nl2br(htmlspecialchars($order['notes'])) ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Support Chat -->
                            <div class="order-support-chat" id="chat-<?= $order['id'] ?>">
                                <div class="chat-content">
                                    <div class="chat-header">
                                        <div class="chat-header-icon">
                                            <i class="fas fa-headset"></i>
                                        </div>
                                        <div class="chat-header-info">
                                            <div class="chat-header-title">Техническая поддержка</div>
                                            <div class="chat-header-status">
                                                <span class="status-dot"></span>
                                                Онлайн
                                            </div>
                                        </div>
                                    </div>

                                    <div class="chat-messages" id="messages-<?= $order['id'] ?>">
                                        <?php if (empty($order['support_messages'])): ?>
                                        <div class="chat-empty">
                                            <i class="fas fa-comments"></i>
                                            <p>Пока нет сообщений. Напишите, если у вас есть вопросы по заказу.</p>
                                        </div>
                                        <?php else: ?>
                                            <?php 
                                            $lastDate = null;
                                            foreach ($order['support_messages'] as $message): 
                                                $messageDate = date('Y-m-d', strtotime($message['created_at']));
                                                $today = date('Y-m-d');
                                                $yesterday = date('Y-m-d', strtotime('-1 day'));
                                                
                                                // Show date separator if date changed
                                                if ($lastDate !== $messageDate):
                                                    $lastDate = $messageDate;
                                            ?>
                                            <div class="chat-date-separator">
                                                <span>
                                                    <?php
                                                    if ($messageDate === $today) {
                                                        echo 'Сегодня';
                                                    } elseif ($messageDate === $yesterday) {
                                                        echo 'Вчера';
                                                    } else {
                                                        echo date('d.m.Y', strtotime($message['created_at']));
                                                    }
                                                    ?>
                                                </span>
                                            </div>
                                            <?php endif; ?>
                                            <div class="chat-message <?= $message['is_support'] ? 'support' : 'user' ?>">
                                                <div class="chat-avatar">
                                                    <?php if ($message['is_support']): ?>
                                                        <i class="fas fa-headset"></i>
                                                    <?php elseif (!empty($message['avatar'])): ?>
                                                        <img src="<?= htmlspecialchars($message['avatar']) ?>" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover; border-radius: 10px;">
                                                    <?php else: ?>
                                                        <?= strtoupper(substr($message['username'] ?? $_SESSION['username'] ?? 'U', 0, 1)) ?>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="chat-bubble">
                                                    <div class="chat-bubble-header">
                                                        <span class="chat-sender">
                                                            <?php if ($message['is_support']): ?>
                                                                Техподдержка
                                                                <?php if (!empty($message['role']) && in_array($message['role'], ['admin', 'high-admin', 'owner'])): ?>
                                                                    <i class="fas fa-shield-halved" style="font-size: 11px; margin-left: 4px;" title="<?= ucfirst($message['role']) ?>"></i>
                                                                <?php endif; ?>
                                                            <?php else: ?>
                                                                <?= htmlspecialchars($message['username'] ?? $_SESSION['username'] ?? 'Вы') ?>
                                                            <?php endif; ?>
                                                        </span>
                                                        <span class="chat-time">
                                                            <?php
                                                            $messageTime = strtotime($message['created_at']);
                                                            $today = strtotime('today');
                                                            $yesterday = strtotime('yesterday');
                                                            
                                                            if ($messageTime >= $today) {
                                                                echo date('H:i', $messageTime);
                                                            } elseif ($messageTime >= $yesterday) {
                                                                echo 'Вчера ' . date('H:i', $messageTime);
                                                            } else {
                                                                echo date('d.m.Y H:i', $messageTime);
                                                            }
                                                            ?>
                                                        </span>
                                                    </div>
                                                    <div class="chat-text">
                                                        <?= nl2br(htmlspecialchars($message['message'])) ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>

                                    <div class="chat-input-area">
                                        <div class="chat-input-wrapper">
                                            <textarea 
                                                class="chat-input" 
                                                id="chat-input-<?= $order['id'] ?>"
                                                placeholder="Напишите ваше сообщение..."
                                                rows="1"
                                            ></textarea>
                                        </div>
                                        <button class="chat-send-btn" onclick="sendMessage(<?= $order['id'] ?>, event)">
                                            <i class="fas fa-paper-plane"></i>
                                            <span>Отправить</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="js/main.js"></script>
</body>
</html>

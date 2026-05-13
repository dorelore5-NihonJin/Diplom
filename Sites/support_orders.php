<?php
session_start();
require_once 'config.php';

// Check if user is support staff
$hasAccess = false;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    $hasAccess = in_array($user['role'] ?? '', ['support', 'admin', 'high-admin', 'owner']);
}

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Filters
$statusFilter = $_GET['status'] ?? '';
$searchQuery = $_GET['search'] ?? '';

// Build query
$whereConditions = [];
$params = [];

if ($statusFilter) {
    $whereConditions[] = "o.status = ?";
    $params[] = $statusFilter;
}

if ($searchQuery) {
    $whereConditions[] = "(o.order_number LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
    $searchParam = "%$searchQuery%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get orders with user info
$orders = [];
$totalOrders = 0;
$stats = ['total' => 0, 'pending' => 0, 'processing' => 0, 'completed' => 0];

if (!$hasAccess) {
    http_response_code(403);
}

if ($hasAccess) {
    try {
        // Get total count
        $countSql = "SELECT COUNT(*) FROM orders o LEFT JOIN users u ON o.user_id = u.id $whereClause";
        $stmt = $pdo->prepare($countSql);
        $stmt->execute($params);
        $totalOrders = $stmt->fetchColumn();
        
        // Get orders
        $sql = "
            SELECT o.*, 
                   u.username, u.email, u.avatar,
                   COUNT(oi.id) as items_count,
                   SUM(oi.quantity) as total_items
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            LEFT JOIN order_items oi ON o.id = oi.order_id
            $whereClause
            GROUP BY o.id
            ORDER BY o.created_at DESC
            LIMIT $perPage OFFSET $offset
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
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
                
                // Count unread messages from users
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as unread_count
                    FROM support_messages
                    WHERE order_id = ? AND is_support = 0 AND is_read = 0
                ");
                $stmt->execute([$order['id']]);
                $order['unread_count'] = $stmt->fetchColumn();
            } catch (PDOException $e) {
                $order['support_messages'] = [];
                $order['unread_count'] = 0;
            }
        }
        unset($order);
        
        // Get statistics
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status IN ('confirmed', 'processing', 'assembling', 'shipping', 'shipped') THEN 1 ELSE 0 END) as processing,
                SUM(CASE WHEN status IN ('ready_pickup', 'delivered', 'completed') THEN 1 ELSE 0 END) as completed
            FROM orders
        ");
        $stats = $stmt->fetch();
        
    } catch (PDOException $e) {
        $orders = [];
    }
}

$totalPages = ceil($totalOrders / $perPage);

ob_start();
if (empty($orders)): ?>
    <div class="empty-state">
        <i class="fas fa-box-open"></i>
        <h2>Заказов не найдено</h2>
        <p>Попробуйте изменить фильтры поиска</p>
    </div>
<?php else: ?>
    <div class="console-cards">
    <?php foreach ($orders as $order): ?>
        <div class="order-card console-card">
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
                            'processing' => ['label' => 'В обработке', 'icon' => 'fa-gear'],
                            'assembling' => ['label' => 'Собирается', 'icon' => 'fa-screwdriver-wrench'],
                            'shipping' => ['label' => 'В пути', 'icon' => 'fa-truck-fast'],
                            'shipped' => ['label' => 'Отправлен', 'icon' => 'fa-truck'],
                            'ready_pickup' => ['label' => 'Ждет получения', 'icon' => 'fa-box-open'],
                            'delivered' => ['label' => 'Доставлен', 'icon' => 'fa-house-circle-check'],
                            'completed' => ['label' => 'Получен', 'icon' => 'fa-circle-check'],
                            'cancelled' => ['label' => 'Отменён', 'icon' => 'fa-circle-xmark']
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
                    <?php elseif ($order['payment_status'] === 'pending'): ?>
                    <div class="payment-badge pending">
                        <i class="fas fa-clock"></i>
                        Ожидает оплаты
                    </div>
                    <?php elseif ($order['payment_status'] === 'failed'): ?>
                    <div class="payment-badge failed">
                        <i class="fas fa-times-circle"></i>
                        Ошибка оплаты
                    </div>
                    <?php elseif ($order['payment_status'] === 'refunded'): ?>
                    <div class="payment-badge refunded">
                        <i class="fas fa-rotate-left"></i>
                        Возврат
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="user-info">
                <div class="user-avatar">
                    <?php if (!empty($order['avatar'])): ?>
                        <img src="<?= htmlspecialchars($order['avatar']) ?>" alt="Avatar">
                    <?php else: ?>
                        <?= strtoupper(substr($order['username'] ?? 'U', 0, 1)) ?>
                    <?php endif; ?>
                </div>
                <div class="user-details">
                    <div class="user-name"><?= htmlspecialchars($order['username'] ?? 'Неизвестный пользователь') ?></div>
                    <div class="user-email"><?= htmlspecialchars($order['email'] ?? '') ?></div>
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
                        <div class="detail-value"><?= htmlspecialchars($order['delivery_address']) ?></div>
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
                    <span>Чат с клиентом</span>
                    <?php if ($order['unread_count'] > 0): ?>
                    <span class="notification-badge"><?= $order['unread_count'] ?></span>
                    <?php endif; ?>
                </button>
                
                <div class="status-dropdown">
                    <button class="btn-order btn-status" id="status-btn-<?= $order['id'] ?>" onclick="toggleStatusMenu(<?= $order['id'] ?>)">
                        <i class="fas fa-exchange-alt"></i>
                        <span>Изменить статус</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="status-menu" id="status-menu-<?= $order['id'] ?>">
                        <button class="status-menu-item status-pending" onclick="changeOrderStatus(<?= $order['id'] ?>, 'pending')">
                            <i class="fas fa-clock"></i>
                            Ожидает подтверждения
                        </button>
                        <button class="status-menu-item status-confirmed" onclick="changeOrderStatus(<?= $order['id'] ?>, 'confirmed')">
                            <i class="fas fa-check-circle"></i>
                            Подтвержден
                        </button>
                        <button class="status-menu-item status-processing" onclick="changeOrderStatus(<?= $order['id'] ?>, 'processing')">
                            <i class="fas fa-gear"></i>
                            В обработке
                        </button>
                        <button class="status-menu-item status-assembling" onclick="changeOrderStatus(<?= $order['id'] ?>, 'assembling')">
                            <i class="fas fa-screwdriver-wrench"></i>
                            Собирается
                        </button>
                        <button class="status-menu-item status-shipping" onclick="changeOrderStatus(<?= $order['id'] ?>, 'shipping')">
                            <i class="fas fa-truck-fast"></i>
                            В пути
                        </button>
                        <button class="status-menu-item status-shipped" onclick="changeOrderStatus(<?= $order['id'] ?>, 'shipped')">
                            <i class="fas fa-truck"></i>
                            Отправлен
                        </button>
                        <button class="status-menu-item status-ready_pickup" onclick="changeOrderStatus(<?= $order['id'] ?>, 'ready_pickup')">
                            <i class="fas fa-box-open"></i>
                            Ждет получения
                        </button>
                        <button class="status-menu-item status-delivered" onclick="changeOrderStatus(<?= $order['id'] ?>, 'delivered')">
                            <i class="fas fa-house-circle-check"></i>
                            Доставлен
                        </button>
                        <button class="status-menu-item status-completed" onclick="changeOrderStatus(<?= $order['id'] ?>, 'completed')">
                            <i class="fas fa-circle-check"></i>
                            Получен
                        </button>
                        <button class="status-menu-item status-cancelled" onclick="changeOrderStatus(<?= $order['id'] ?>, 'cancelled')">
                            <i class="fas fa-circle-xmark"></i>
                            Отменён
                        </button>
                    </div>
                </div>

                <button 
                    type="button" 
                    class="btn-order btn-delete-order" 
                    title="Удалить заказ"
                    onclick="openOrderDeleteModal(<?= $order['id'] ?>, '<?= htmlspecialchars($order['order_number'] ?? $order['id'], ENT_QUOTES) ?>')"
                >
                    <i class="fas fa-trash"></i>
                    <span class="btn-label">Удалить</span>
                </button>
            </div>

            <div class="order-expanded-details" id="details-<?= $order['id'] ?>">
                <div class="expanded-content">
                    <?php if (!empty($order['items'])): ?>
                    <div class="order-items-list">
                        <div class="order-items-title">
                            <i class="fas fa-box-open"></i>
                            Состав заказа
                        </div>
                        <?php foreach ($order['items'] as $item): ?>
                        <div class="order-item">
                            <div class="item-details">
                                <div class="item-name"><?= htmlspecialchars($item['component_name']) ?></div>
                                <div class="item-category"><?= htmlspecialchars($item['component_category'] ?? 'Компонент') ?></div>
                            </div>
                            <div class="item-price"><?= formatPrice($item['price']) ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

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

            <div class="order-support-chat" id="chat-<?= $order['id'] ?>">
                <div class="chat-content">
                    <div class="chat-header">
                        <div class="chat-header-icon">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="chat-header-info">
                            <div class="chat-header-title">Чат с <?= htmlspecialchars($order['username'] ?? 'клиентом') ?></div>
                            <div class="chat-header-status">
                                <span class="status-dot online"></span>
                                Онлайн
                            </div>
                        </div>
                    </div>

                    <div class="chat-messages" id="messages-<?= $order['id'] ?>">
                        <?php if (empty($order['support_messages'])): ?>
                        <div class="chat-empty">
                            <i class="fas fa-comments"></i>
                            <p>Пока нет сообщений. Начните диалог с клиентом.</p>
                        </div>
                        <?php else: ?>
                            <?php 
                            $lastDate = null;
                            foreach ($order['support_messages'] as $message): 
                                $messageDate = date('Y-m-d', strtotime($message['created_at']));
                                $today = date('Y-m-d');
                                $yesterday = date('Y-m-d', strtotime('-1 day'));
                                
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
                                        <?= strtoupper(substr($message['username'] ?? 'U', 0, 1)) ?>
                                    <?php endif; ?>
                                </div>
                                <div class="chat-bubble">
                                    <div class="chat-bubble-header">
                                        <span class="chat-sender">
                                            <?php if ($message['is_support']): ?>
                                                Техподдержка
                                            <?php else: ?>
                                                <?= htmlspecialchars($message['username'] ?? 'Клиент') ?>
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
                                placeholder="Напишите ответ клиенту..."
                                rows="1"
                            ></textarea>
                        </div>
                        <button class="chat-send-btn" onclick="sendMessage(<?= $order['id'] ?>)">
                            <i class="fas fa-paper-plane"></i>
                            <span>Отправить</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
<?php endif;
$ordersHtml = ob_get_clean();

ob_start();
if ($totalPages > 1): ?>
<div class="pagination">
    <?php if ($page > 1): ?>
    <a href="?page=<?= $page - 1 ?><?= $statusFilter ? '&status=' . $statusFilter : '' ?><?= $searchQuery ? '&search=' . urlencode($searchQuery) : '' ?>" class="pagination-btn">
        <i class="fas fa-chevron-left"></i>
        Назад
    </a>
    <?php endif; ?>

    <div class="pagination-numbers">
        <?php
        $start = max(1, $page - 2);
        $end = min($totalPages, $page + 2);
        
        if ($start > 1): ?>
            <a href="?page=1<?= $statusFilter ? '&status=' . $statusFilter : '' ?><?= $searchQuery ? '&search=' . urlencode($searchQuery) : '' ?>" class="pagination-number">1</a>
            <?php if ($start > 2): ?>
                <span class="pagination-dots">...</span>
            <?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $start; $i <= $end; $i++): ?>
            <a href="?page=<?= $i ?><?= $statusFilter ? '&status=' . $statusFilter : '' ?><?= $searchQuery ? '&search=' . urlencode($searchQuery) : '' ?>" 
               class="pagination-number <?= $i === $page ? 'active' : '' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>

        <?php if ($end < $totalPages): ?>
            <?php if ($end < $totalPages - 1): ?>
                <span class="pagination-dots">...</span>
            <?php endif; ?>
            <a href="?page=<?= $totalPages ?><?= $statusFilter ? '&status=' . $statusFilter : '' ?><?= $searchQuery ? '&search=' . urlencode($searchQuery) : '' ?>" class="pagination-number"><?= $totalPages ?></a>
        <?php endif; ?>
    </div>

    <?php if ($page < $totalPages): ?>
    <a href="?page=<?= $page + 1 ?><?= $statusFilter ? '&status=' . $statusFilter : '' ?><?= $searchQuery ? '&search=' . urlencode($searchQuery) : '' ?>" class="pagination-btn">
        Вперед
        <i class="fas fa-chevron-right"></i>
    </a>
    <?php endif; ?>
</div>
<?php endif;
$paginationHtml = ob_get_clean();

if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'orders_html' => $ordersHtml,
        'pagination_html' => $paginationHtml,
        'total' => $totalOrders,
        'page' => $page,
        'total_pages' => $totalPages
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Проверка заказов - <?= SITE_NAME ?></title>
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
    <link rel="stylesheet" href="css/admin-console.css?v=<?= time() ?>">
    <link rel="stylesheet" href="css/support_orders.css?v=<?= time() ?>">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="orders-page admin-console">
        <div class="container">
            <?php if (!$hasAccess): ?>
                <div class="access-denied">
                    <i class="fas fa-shield-halved"></i>
                    <h2>Недостаточно прав</h2>
                    <p>Страница проверки заказов доступна только сотрудникам техподдержки. Если вы считаете, что это ошибка, обратитесь к администратору.</p>
                </div>
            <?php else: ?>
            <div class="console-hero">
                <div class="hero-icon gradient-indigo">
                    <i class="fas fa-headset"></i>
                </div>
                <div class="hero-meta">
                    <h1>Проверка заказов</h1>
                    <p>Текущий статус заказов, оплата и общение с клиентами</p>
                </div>
                <div class="hero-actions">
                    <a href="support_tickets.php" class="btn-outline-link">
                        <i class="fas fa-comments"></i>
                        К обращениями
                    </a>
                </div>
            </div>

            <!-- Statistics -->
            <div class="stats-bar console-stats">
                <div class="stat-item">
                    <i class="fas fa-shopping-cart"></i>
                    <div class="stat-info">
                        <div class="stat-label">Всего заказов</div>
                        <div class="stat-value"><?= $stats['total'] ?></div>
                    </div>
                </div>
                <div class="stat-item">
                    <i class="fas fa-clock"></i>
                    <div class="stat-info">
                        <div class="stat-label">Ожидают</div>
                        <div class="stat-value"><?= $stats['pending'] ?></div>
                    </div>
                </div>
                <div class="stat-item">
                    <i class="fas fa-gear"></i>
                    <div class="stat-info">
                        <div class="stat-label">В работе</div>
                        <div class="stat-value"><?= $stats['processing'] ?></div>
                    </div>
                </div>
                <div class="stat-item">
                    <i class="fas fa-circle-check"></i>
                    <div class="stat-info">
                        <div class="stat-label">Завершено</div>
                        <div class="stat-value"><?= $stats['completed'] ?></div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="filter-bar console-filters">
                <div class="filter-group">
                    <label for="statusFilter">
                        <i class="fas fa-filter"></i>
                        Статус:
                    </label>
                    <select id="statusFilter" class="filter-select">
                        <option value="">Все статусы</option>
                        <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Ожидает подтверждения</option>
                        <option value="confirmed" <?= $statusFilter === 'confirmed' ? 'selected' : '' ?>>Подтвержден</option>
                        <option value="processing" <?= $statusFilter === 'processing' ? 'selected' : '' ?>>В обработке</option>
                        <option value="assembling" <?= $statusFilter === 'assembling' ? 'selected' : '' ?>>Собирается</option>
                        <option value="shipping" <?= $statusFilter === 'shipping' ? 'selected' : '' ?>>В пути</option>
                        <option value="shipped" <?= $statusFilter === 'shipped' ? 'selected' : '' ?>>Отправлен</option>
                        <option value="ready_pickup" <?= $statusFilter === 'ready_pickup' ? 'selected' : '' ?>>Ждет получения</option>
                        <option value="delivered" <?= $statusFilter === 'delivered' ? 'selected' : '' ?>>Доставлен</option>
                        <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>Получен</option>
                        <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Отменён</option>
                    </select>
                </div>

                <div class="console-search">
                    <i class="fas fa-search"></i>
                    <input 
                        type="text" 
                        id="searchInput" 
                        placeholder="Поиск по номеру заказа, имени или email..." 
                        value="<?= htmlspecialchars($searchQuery) ?>"
                    >
                </div>

                <button class="btn-reset-filters <?= ($statusFilter || $searchQuery) ? '' : 'is-hidden' ?>" type="button" id="ordersReset">
                    <i class="fas fa-times"></i>
                    Сбросить
                </button>
            </div>

            <div id="ordersContainer" class="orders-container">
                <?= $ordersHtml ?>
            </div>
            <div id="ordersPagination">
                <?= $paginationHtml ?>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <!-- Order delete modal -->
    <div class="order-modal" id="orderDeleteModal" aria-hidden="true">
        <div class="order-modal-overlay" onclick="closeOrderDeleteModal()"></div>
        <div class="order-modal-content danger" role="dialog" aria-modal="true" aria-labelledby="orderDeleteTitle">
            <button class="order-modal-close" aria-label="Закрыть" onclick="closeOrderDeleteModal()">
                <i class="fas fa-times"></i>
            </button>
            <div class="order-modal-icon danger">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
            <h3 id="orderDeleteTitle">Удалить заказ?</h3>
            <p id="orderDeleteMessage">Эта операция удалит заказ без возможности восстановления.</p>
            <div class="order-modal-actions">
                <button type="button" class="btn btn-outline" onclick="closeOrderDeleteModal()">Отмена</button>
                <button type="button" class="btn btn-danger" id="orderDeleteConfirm" onclick="confirmOrderDelete()">
                    <span class="btn-label">Удалить</span>
                    <span class="btn-spinner" aria-hidden="true"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Status change modal -->
    <div class="order-modal" id="orderStatusModal" aria-hidden="true">
        <div class="order-modal-overlay" onclick="closeStatusModal()"></div>
        <div class="order-modal-content" role="dialog" aria-modal="true" aria-labelledby="statusModalTitle">
            <button class="order-modal-close" aria-label="Закрыть" onclick="closeStatusModal()">
                <i class="fas fa-times"></i>
            </button>
            <div class="order-modal-icon">
                <i class="fas fa-clipboard-check"></i>
            </div>
            <h3 id="statusModalTitle">Изменить статус заказа?</h3>
            <p id="statusModalMessage">Подтвердите изменение статуса. Клиент получит уведомление об обновлении заказа.</p>
            <div class="order-modal-actions">
                <button type="button" class="btn btn-outline" onclick="closeStatusModal()">Отмена</button>
                <button type="button" class="btn btn-primary" id="statusModalConfirm" onclick="confirmStatusChange()">
                    <span class="btn-label">Подтвердить</span>
                    <span class="btn-spinner" aria-hidden="true"></span>
                </button>
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script src="js/support_orders.js" defer></script>
</body>
</html>

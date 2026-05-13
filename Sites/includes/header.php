<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$notifications = [];
$unreadNotificationCount = 0;
$authUser = null;
$showBlockedOverlay = false;
$blockedOverlayMeta = [];

if (isset($_SESSION['user_id']) && isset($pdo)) {
    try {
        $stmt = $pdo->prepare("
            SELECT id, order_id, type, title, message, is_read, created_at
            FROM order_notifications
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($notifications as $notification) {
            if (empty($notification['is_read'])) {
                $unreadNotificationCount++;
            }
        }
        
        $userStmt = $pdo->prepare("SELECT username, email, avatar, role, status, blocked_at, blocked_until, block_reason FROM users WHERE id = ? LIMIT 1");
        $userStmt->execute([$_SESSION['user_id']]);
        $authUser = $userStmt->fetch(PDO::FETCH_ASSOC) ?: null;
        $currentPage = basename($_SERVER['PHP_SELF']);
        $allowPages = ['support.php', 'privacy.php', 'terms.php', 'cookie-policy.php'];
        if ($authUser && ($authUser['status'] ?? '') === 'blocked' && !in_array($currentPage, $allowPages, true)) {
            $showBlockedOverlay = true;
            $reasonText = trim($authUser['block_reason'] ?? '') ?: 'Причина не указана';
            if (!empty($authUser['blocked_until'])) {
                $blockedUntilDate = new DateTime($authUser['blocked_until'], new DateTimeZone('UTC'));
                $blockedOverlayMeta['duration'] = 'До ' . $blockedUntilDate->format('d.m.Y H:i') . ' (UTC)';
            } else {
                $blockedOverlayMeta['duration'] = 'На неопределённый срок';
            }
            $blockedOverlayMeta['reason'] = $reasonText;
            $blockedOverlayMeta['blocked_at'] = !empty($authUser['blocked_at']) ? (new DateTime($authUser['blocked_at']))->format('d.m.Y H:i') : null;
        }
    } catch (PDOException $e) {
        $notifications = [];
        $unreadNotificationCount = 0;
    }
}
?>
<?php if ($showBlockedOverlay): ?>
<div class="blocked-self-overlay" aria-hidden="false">
    <div class="blocked-self-modal">
        <div class="blocked-icon">
            <i class="fas fa-shield-alt"></i>
        </div>
        <h2>Аккаунт временно недоступен</h2>
        <p class="blocked-subtitle">Ваш профиль заблокирован модератором. Доступ к сайту ограничен до окончания блокировки.</p>
        <div class="blocked-meta">
            <div>
                <span>Статус</span>
                <strong><?= htmlspecialchars($blockedOverlayMeta['duration']) ?></strong>
            </div>
            <?php if (!empty($blockedOverlayMeta['blocked_at'])): ?>
            <div>
                <span>Дата блокировки</span>
                <strong><?= htmlspecialchars($blockedOverlayMeta['blocked_at']) ?></strong>
            </div>
            <?php endif; ?>
        </div>
        <div class="blocked-reason">
            <span>Причина</span>
            <p><?= nl2br(htmlspecialchars($blockedOverlayMeta['reason'])) ?></p>
        </div>
        <div class="blocked-note">
            Обратитесь в <a href="support.php">поддержку</a>, если считаете блокировку ошибочной.
        </div>
    </div>
</div>
<style>
.blocked-self-overlay {
    position: fixed;
    inset: 0;
    background: rgba(2, 6, 23, 0.85);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
    z-index: 2000;
}
.blocked-self-modal {
    width: min(460px, 100%);
    background: #0f172a;
    border-radius: 32px;
    padding: 36px;
    border: 1px solid rgba(148, 163, 184, 0.25);
    box-shadow: 0 40px 80px rgba(2, 6, 23, 0.6);
    text-align: center;
    color: #e2e8f0;
}
.blocked-icon {
    width: 64px;
    height: 64px;
    border-radius: 18px;
    background: rgba(252, 165, 165, 0.18);
    color: #fda4af;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 18px;
    font-size: 28px;
}
.blocked-self-modal h2 { font-size: 24px; margin-bottom: 10px; }
.blocked-subtitle { color: rgba(226,232,240,0.7); font-size: 14px; margin-bottom: 24px; }
.blocked-meta { display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap: 12px; margin-bottom: 20px; }
.blocked-meta span { font-size: 12px; color: rgba(226,232,240,0.6); text-transform: uppercase; letter-spacing: 0.05em; }
.blocked-meta strong { display: block; margin-top: 4px; font-size: 16px; }
.blocked-reason { text-align: left; background: rgba(15,23,42,0.9); border-radius: 18px; border: 1px solid rgba(148,163,184,0.2); padding: 18px; margin-bottom: 16px; }
.blocked-reason span { font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: rgba(226,232,240,0.6); }
.blocked-reason p { margin-top: 8px; font-size: 14px; line-height: 1.6; }
.blocked-note { font-size: 13px; color: rgba(226,232,240,0.65); }
body { overflow: hidden; }
[data-theme="light"] .blocked-self-overlay {
    background: rgba(15, 23, 42, 0.6);
}
[data-theme="light"] .blocked-self-modal {
    background: #ffffff;
    color: #0f172a;
    border-color: rgba(15, 23, 42, 0.08);
}
[data-theme="light"] .blocked-subtitle,
[data-theme="light"] .blocked-meta span,
[data-theme="light"] .blocked-reason span,
[data-theme="light"] .blocked-note {
    color: rgba(15, 23, 42, 0.65);
}
[data-theme="light"] .blocked-reason {
    background: rgba(241, 245, 249, 0.9);
    border-color: rgba(148, 163, 184, 0.25);
}
[data-theme="light"] .blocked-icon {
    background: rgba(248, 113, 113, 0.15);
    color: #dc2626;
}
[data-theme="light"] .blocked-note a,
[data-theme="light"] .blocked-note {
    color: #2563eb;
}
.blocked-note a {
    color: inherit;
    text-decoration: underline;
}
</style>

<script>
    (function() {
        if (!document.querySelector('link[rel="icon"]')) {
            const link = document.createElement('link');
            link.rel = 'icon';
            link.href = '/HyperPC/favicon.ico';
            document.head.appendChild(link);
        }
    })();
</script>
<?php endif; ?>
<header class="header">
    <div class="container">
        <nav class="navbar">
            <a href="index.php" class="logo logo-image">
                <img class="logo-dark" src="pictures/JKT_full_clear.png" alt="HyperPC">
                <img class="logo-light" src="pictures/JKT_full_clear_bl.png" alt="HyperPC">
            </a>
            <ul class="nav-menu">
                <li><a href="index.php" <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'class="active"' : '' ?>>Главная</a></li>
                <li><a href="catalog.php" <?= basename($_SERVER['PHP_SELF']) === 'catalog.php' ? 'class="active"' : '' ?>>Каталог</a></li>
                <li><a href="builder.php" <?= basename($_SERVER['PHP_SELF']) === 'builder.php' ? 'class="active"' : '' ?>>Сборка ПК</a></li>
                <li><a href="builds.php" <?= basename($_SERVER['PHP_SELF']) === 'builds.php' ? 'class="active"' : '' ?>>Готовые сборки</a></li>
                <li><a href="reviews.php" <?= basename($_SERVER['PHP_SELF']) === 'reviews.php' ? 'class="active"' : '' ?>>Обзоры</a></li>
            </ul>
            <div class="nav-actions">
                <button class="btn-icon" id="themeToggle" title="Переключить тему">
                    <i class="fas fa-moon"></i>
                </button>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="notifications-menu">
                        <button class="btn-notification <?= $unreadNotificationCount ? 'has-unread' : '' ?>" onclick="toggleNotificationsMenu()" id="notificationsButton">
                            <div class="btn-notification-icon">
                                <i class="fas fa-bell"></i>
                            </div>
                            <div class="btn-notification-label">
                                <span>Уведомления</span>
                                <small>по заказам</small>
                            </div>
                            <?php if ($unreadNotificationCount > 0): ?>
                                <span class="notification-dot"><?= $unreadNotificationCount ?></span>
                            <?php endif; ?>
                        </button>
                        <div class="notification-dropdown" id="notificationsDropdown">
                            <div class="notification-dropdown-header">
                                <div>
                                    <div class="notification-title">Уведомления</div>
                                    <div class="notification-subtitle">
                                        <?= $unreadNotificationCount > 0 ? 'Непрочитанных: ' . $unreadNotificationCount : 'Все уведомления прочитаны' ?>
                                    </div>
                                </div>
                                <button class="notification-settings" type="button" title="Фильтры" onclick="toggleNotificationFilters(event)">
                                    <i class="fas fa-sliders"></i>
                                </button>
                                <div class="notification-filters" id="notificationFilters">
                                    <div class="notification-filters-header">
                                        <span>Фильтровать</span>
                                        <button class="notification-filters-reset" type="button" onclick="resetNotificationFilters()">
                                            Сбросить
                                        </button>
                                    </div>
                                    <div class="notification-filters-group">
                                        <label class="notification-filter-option">
                                            <input type="checkbox" class="notification-filter-checkbox" data-filter="status" checked>
                                            <span>Обновления заказа</span>
                                        </label>
                                        <label class="notification-filter-option">
                                            <input type="checkbox" class="notification-filter-checkbox" data-filter="support" checked>
                                            <span>Техподдержка</span>
                                        </label>
                                        <label class="notification-filter-option">
                                            <input type="checkbox" class="notification-filter-checkbox" data-filter="system" checked>
                                            <span>Система</span>
                                        </label>
                                    </div>
                                    <div class="notification-filters-divider"></div>
                                    <label class="notification-filter-option notification-filter-toggle">
                                        <span>Показывать прочитанные</span>
                                        <input type="checkbox" class="notification-filter-checkbox" data-filter="showRead" id="filterShowRead" checked>
                                    </label>
                                </div>
                            </div>
                            <div class="notification-list">
                                <?php if (empty($notifications)): ?>
                                    <div class="notification-empty">
                                        <i class="fas fa-bell-slash"></i>
                                        <p>Пока нет уведомлений</p>
                                        <span>Мы сообщим, когда появятся новости по вашим заказам</span>
                                    </div>
                                <?php else: ?>
                                    <?php
                                    $typeIcons = [
                                        'status' => 'fa-arrows-rotate',
                                        'support' => 'fa-headset',
                                        'system' => 'fa-circle-info'
                                    ];
                                    $typeLabels = [
                                        'status' => 'Обновление заказа',
                                        'support' => 'Техподдержка',
                                        'system' => 'Система'
                                    ];
                                    ?>
                                    <?php foreach ($notifications as $notification): ?>
                                        <?php $type = $notification['type'] ?? 'system'; ?>
                                        <div class="notification-item <?= empty($notification['is_read']) ? 'unread' : '' ?>" 
                                             data-type="<?= htmlspecialchars($type) ?>" 
                                             data-read="<?= !empty($notification['is_read']) ? 1 : 0 ?>"
                                             onclick="handleNotificationClick(<?= $notification['order_id'] ? (int)$notification['order_id'] : 0 ?>, '<?= htmlspecialchars($type) ?>')">
                                            <div class="notification-icon">
                                                <i class="fas <?= $typeIcons[$type] ?? 'fa-circle-info' ?>"></i>
                                            </div>
                                            <div class="notification-content">
                                                <div class="notification-type"><?= $typeLabels[$type] ?? 'Уведомление' ?></div>
                                                <div class="notification-title-line"><?= htmlspecialchars($notification['title'] ?? 'Новость по заказу') ?></div>
                                                <div class="notification-text"><?= htmlspecialchars($notification['message'] ?? '') ?></div>
                                                <div class="notification-date"><?= date('d.m.Y H:i', strtotime($notification['created_at'])) ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    <div class="notification-empty notification-empty-filter" id="notificationFiltersEmpty" style="display: none;">
                                        <i class="fas fa-filter"></i>
                                        <p>Нет уведомлений по выбранным фильтрам</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="notification-footer">
                                <a href="orders.php" class="notification-link">
                                    <span>Перейти к заказам</span>
                                    <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="user-menu">
                        <button class="btn-user" onclick="toggleUserMenu()">
                            <?php
                            // Get user avatar
                            $userAvatar = $authUser['avatar'] ?? null;
                            $userRole = $authUser['role'] ?? 'user';
                            ?>
                            <?php if ($userAvatar): ?>
                                <img src="<?= htmlspecialchars($userAvatar) ?>" alt="Avatar" class="btn-user-avatar">
                            <?php else: ?>
                                <i class="fas fa-user-circle"></i>
                            <?php endif; ?>
                            <span><?= htmlspecialchars($_SESSION['username']) ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="user-dropdown" id="userDropdown">
                            <div class="user-info">
                                <div class="user-avatar">
                                    <?php if ($userAvatar): ?>
                                        <img src="<?= htmlspecialchars($userAvatar) ?>" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover; border-radius: 12px;">
                                    <?php else: ?>
                                        <i class="fas fa-user-circle"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="user-details">
                                    <div class="user-name"><?= htmlspecialchars($_SESSION['username']) ?></div>
                                    <div class="user-email"><?= htmlspecialchars($_SESSION['email']) ?></div>
                                </div>
                            </div>
                            <div class="dropdown-divider"></div>
                            <?php
                                $profileLink = 'profile.php';
                                if (!empty($_SESSION['username'])) {
                                    $profileLink = 'profile.php?username=' . urlencode($_SESSION['username']);
                                }
                            ?>
                            <a href="<?= $profileLink ?>" class="dropdown-item">
                                <i class="fas fa-user"></i>
                                <span>Мой профиль</span>
                            </a>
                            <a href="orders.php" class="dropdown-item">
                                <i class="fas fa-shopping-bag"></i>
                                <span>Мои заказы</span>
                            </a>
                            <a href="settings.php" class="dropdown-item">
                                <i class="fas fa-sliders-h"></i>
                                <span>Настройки</span>
                            </a>
                            <?php if (in_array($userRole, ['support', 'admin', 'high-admin', 'owner'])): ?>
                            <div class="dropdown-divider"></div>
                            <a href="support_orders.php" class="dropdown-item support-item">
                                <i class="fas fa-headset"></i>
                                <span>Проверка заказов</span>
                            </a>
                            <?php if (in_array($userRole, ['support', 'owner'], true)): ?>
                            <a href="support_tickets.php" class="dropdown-item support-item">
                                <i class="fas fa-comments"></i>
                                <span>Проверка обращений</span>
                            </a>
                            <a href="moderate_reviews.php" class="dropdown-item support-item">
                                <i class="fas fa-star-half-stroke"></i>
                                <span>Проверка обзоров</span>
                            </a>
                            <?php endif; ?>
                            <?php endif; ?>
                            <div class="dropdown-divider"></div>
                            <a href="logout.php" class="dropdown-item logout">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Выйти</span>
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="btn-login" title="Войти в аккаунт">
                        <i class="fas fa-user"></i>
                        <span>Войти</span>
                    </a>
                <?php endif; ?>
                <button class="btn-icon mobile-menu-toggle" id="mobileMenuToggle" title="Открыть меню" onclick="toggleMobileMenu()">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </nav>
        <div class="mobile-nav" id="mobileNav">
            <div class="mobile-nav-links">
                <a href="index.php" <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'class="active"' : '' ?>>Главная</a>
                <a href="catalog.php" <?= basename($_SERVER['PHP_SELF']) === 'catalog.php' ? 'class="active"' : '' ?>>Каталог</a>
                <a href="builder.php" <?= basename($_SERVER['PHP_SELF']) === 'builder.php' ? 'class="active"' : '' ?>>Сборка ПК</a>
                <a href="builds.php" <?= basename($_SERVER['PHP_SELF']) === 'builds.php' ? 'class="active"' : '' ?>>Готовые сборки</a>
                <a href="reviews.php" <?= basename($_SERVER['PHP_SELF']) === 'reviews.php' ? 'class="active"' : '' ?>>Обзоры</a>
            </div>
        </div>
    </div>
</header>

<style>
.notifications-menu {
    position: relative;
}

.btn-notification {
    position: relative;
    width: 44px;
    height: 44px;
    border-radius: 12px;
    border: 2px solid transparent;
    background: linear-gradient(135deg, rgba(124, 58, 237, 0.2), rgba(59, 130, 246, 0.2));
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    padding: 0;
}

.btn-notification:hover {
    border-color: var(--primary);
    box-shadow: 0 6px 18px rgba(79, 70, 229, 0.25);
    transform: translateY(-1px);
}

.btn-notification.has-unread {
    border-color: rgba(239, 68, 68, 0.45);
}

.btn-notification-icon {
    width: 100%;
    height: 100%;
    border-radius: 10px;
    background: linear-gradient(135deg, #8b5cf6, #6366f1);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}

.btn-notification-label {
    display: none;
}

.notification-dot {
    position: absolute;
    top: -4px;
    right: -4px;
    min-width: 22px;
    height: 22px;
    border-radius: 50%;
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
    font-size: 11px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.6);
    animation: notificationPulse 2s infinite;
}

.notification-dropdown {
    position: absolute;
    top: calc(100% + 12px);
    right: 0;
    width: 360px;
    background: var(--bg-primary);
    border-radius: 20px;
    border: 1px solid var(--border);
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.35);
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.25s ease;
    z-index: 1200;
    overflow: visible;
    backdrop-filter: blur(12px);
}

.notification-dropdown.active {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.notification-dropdown-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px 12px;
    border-bottom: 1px solid var(--border);
}

.notification-settings {
    border: none;
    background: var(--hover-bg);
    border-radius: 12px;
    width: 38px;
    height: 38px;
    color: var(--text-secondary);
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.notification-settings.active {
    color: var(--primary);
    background: rgba(124, 58, 237, 0.2);
}

.notification-filters {
    position: absolute;
    top: calc(100% + 8px);
    right: 24px;
    width: 220px;
    background: var(--bg-secondary);
    border: 1px solid var(--border);
    border-radius: 16px;
    box-shadow: 0 16px 40px rgba(0, 0, 0, 0.35);
    padding: 16px;
    display: none;
    flex-direction: column;
    gap: 12px;
    z-index: 1300;
}

.notification-filters.show {
    display: flex;
}

.notification-filters-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-weight: 600;
    font-size: 14px;
}

.notification-filters-reset {
    border: none;
    background: none;
    color: var(--primary);
    font-size: 12px;
    cursor: pointer;
}

.notification-filters-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.notification-filter-option {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    font-size: 13px;
    color: var(--text-secondary);
}

.notification-filter-checkbox {
    accent-color: var(--primary);
}

.notification-filters-divider {
    height: 1px;
    background: var(--border);
    margin: 6px 0;
}

.notification-filter-toggle {
    font-weight: 600;
}

.notification-title {
    font-weight: 700;
    font-size: 16px;
}

.notification-subtitle {
    font-size: 12px;
    color: var(--text-secondary);
    margin-top: 4px;
}

.notification-settings {
    border: none;
    background: var(--hover-bg);
    border-radius: 12px;
    width: 38px;
    height: 38px;
    color: var(--text-secondary);
    cursor: pointer;
    transition: all 0.2s ease;
}

.notification-settings:hover {
    color: var(--primary);
}

.notification-list {
    max-height: 360px;
    overflow-y: auto;
    padding: 12px 0;
}

.notification-item {
    display: flex;
    gap: 12px;
    padding: 14px 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.04);
}

.notification-item:last-child {
    border-bottom: none;
}

.notification-item.unread {
    background: linear-gradient(135deg, rgba(124, 58, 237, 0.08), rgba(59, 130, 246, 0.08));
}

.notification-icon {
    width: 40px;
    height: 40px;
    border-radius: 14px;
    background: var(--hover-bg);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary);
    font-size: 16px;
}

.notification-content {
    flex: 1;
}

.notification-type {
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: var(--text-secondary);
}

.notification-title-line {
    font-weight: 700;
    font-size: 14px;
    margin: 2px 0;
}

.notification-text {
    font-size: 13px;
    color: var(--text-secondary);
    line-height: 1.4;
}

.notification-date {
    font-size: 11px;
    color: var(--text-tertiary);
    margin-top: 6px;
}

.notification-empty {
    text-align: center;
    padding: 40px 20px;
    color: var(--text-secondary);
}

.notification-empty i {
    font-size: 36px;
    margin-bottom: 12px;
    color: var(--text-tertiary);
}

.notification-footer {
    padding: 14px 20px;
    border-top: 1px solid var(--border);
    background: var(--bg-secondary);
}

.notification-link {
    display: flex;
    align-items: center;
    justify-content: space-between;
    text-decoration: none;
    color: var(--primary);
    font-weight: 600;
}

.notification-link i {
    transition: transform 0.2s ease;
}

.notification-link:hover i {
    transform: translateX(4px);
}

.notification-list::-webkit-scrollbar {
    width: 6px;
}

.notification-list::-webkit-scrollbar-track {
    background: transparent;
}

.notification-list::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.15);
    border-radius: 10px;
}

@keyframes notificationPulse {
    0%,
    100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.1);
    }
}

.build-counter {
    position: absolute;
    top: -6px;
    right: -6px;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
    font-size: 11px;
    font-weight: 700;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-icon {
    position: relative;
}

.btn-login {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
    border-radius: 12px;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.3s ease;
    text-decoration: none;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.2);
}

.btn-login:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.btn-login i {
    font-size: 16px;
}

@media (max-width: 768px) {
    .btn-login span {
        display: none;
    }
    
    .btn-login {
        padding: 10px 12px;
        border-radius: 50%;
        width: 44px;
        height: 44px;
        justify-content: center;
    }
}

/* User Menu Styles */
.user-menu {
    position: relative;
}

.btn-user {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 18px;
    background: var(--card-bg);
    border: 2px solid var(--border);
    border-radius: 14px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    color: var(--text);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.btn-user:hover {
    border-color: var(--primary);
    background: var(--hover-bg);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
}

.btn-user.active {
    border-color: var(--primary);
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(139, 92, 246, 0.1));
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
}

.btn-user i:first-child {
    font-size: 22px;
    color: var(--primary);
    transition: all 0.3s ease;
}

.btn-user:hover i:first-child {
    transform: scale(1.1);
}

.btn-user-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--primary);
}

.btn-user i:last-child {
    font-size: 12px;
    transition: transform 0.3s ease;
    opacity: 0.7;
}

.btn-user.active i:last-child {
    transform: rotate(180deg);
}

.user-dropdown {
    position: absolute;
    top: calc(100% + 12px);
    right: 0;
    min-width: 300px;
    background: var(--bg-primary);
    border: 1px solid var(--border);
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 1000;
    overflow: hidden;
    backdrop-filter: blur(10px);
}

.user-dropdown.active {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.user-info {
    padding: 24px;
    display: flex;
    gap: 14px;
    align-items: center;
    background: var(--bg-secondary);
    border-bottom: 1px solid var(--border);
}

.user-avatar {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 26px;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    flex-shrink: 0;
}

.user-details {
    flex: 1;
    min-width: 0;
}

.user-name {
    font-weight: 700;
    font-size: 16px;
    color: var(--text);
    margin-bottom: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-email {
    font-size: 13px;
    color: var(--text-secondary);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    opacity: 0.8;
}

.dropdown-divider {
    height: 1px;
    background: var(--border);
    margin: 0;
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 24px;
    color: var(--text);
    text-decoration: none;
    transition: all 0.2s ease;
    font-size: 15px;
    font-weight: 500;
    position: relative;
}

.dropdown-item:hover {
    background: var(--hover-bg);
    padding-left: 28px;
}

.dropdown-item i {
    width: 20px;
    color: var(--text-secondary);
    font-size: 17px;
    transition: all 0.2s ease;
}

.dropdown-item:hover i {
    color: var(--primary);
    transform: scale(1.1);
}

.dropdown-item.logout {
    color: #dc2626;
    margin-top: 4px;
    border-top: 1px solid var(--border);
}

.dropdown-item.logout i {
    color: #dc2626;
}

.dropdown-item.logout:hover {
    background: rgba(220, 38, 38, 0.08);
}

.dropdown-item.logout:hover i {
    color: #dc2626;
    transform: scale(1.1);
}

.dropdown-item.support-item {
    background: linear-gradient(135deg, rgba(139, 92, 246, 0.1), rgba(124, 58, 237, 0.1));
    border-left: 3px solid #8b5cf6;
    font-weight: 600;
}

.dropdown-item.support-item i {
    color: #8b5cf6;
}

.dropdown-item.support-item:hover {
    background: linear-gradient(135deg, rgba(139, 92, 246, 0.15), rgba(124, 58, 237, 0.15));
    border-left-color: #7c3aed;
}

@media (max-width: 768px) {
    .btn-user span {
        display: none;
    }
    
    .btn-user {
        padding: 11px;
        border-radius: 50%;
        width: 46px;
        height: 46px;
        justify-content: center;
        gap: 0;
    }
    
    .btn-user i:first-child {
        font-size: 20px;
    }
    
    .btn-user i:last-child {
        display: none;
    }
    
    .user-dropdown {
        right: -10px;
        min-width: 280px;
        border-radius: 16px;
    }
    
    .user-info {
        padding: 20px;
    }
    
    .user-avatar {
        width: 50px;
        height: 50px;
        font-size: 24px;
    }
    
    .dropdown-item {
        padding: 12px 20px;
        font-size: 14px;
    }
    
    .dropdown-item:hover {
        padding-left: 24px;
    }
}
</style>

<script>
function toggleUserMenu() {
    const dropdown = document.getElementById('userDropdown');
    const button = document.querySelector('.btn-user');
    
    dropdown.classList.toggle('active');
    button.classList.toggle('active');
}

function handleNotificationClick(orderId, type) {
    if (type === 'support') {
        window.location.href = 'ticket-details.php?id=' + orderId;
    } else {
        window.location.href = 'orders.php#order-' + orderId;
    }
}

function toggleNotificationsMenu() {
    const dropdown = document.getElementById('notificationsDropdown');
    const button = document.getElementById('notificationsButton');
    const filtersPanel = document.getElementById('notificationFilters');
    const filtersBtn = document.querySelector('.notification-settings');
    if (!dropdown || !button) {
        return;
    }

    const isOpening = !dropdown.classList.contains('active');
    dropdown.classList.toggle('active');
    button.classList.toggle('active');
    if (!isOpening && filtersPanel) {
        filtersPanel.classList.remove('show');
        if (filtersBtn) filtersBtn.classList.remove('active');
    }

    if (isOpening) {
        markNotificationsAsRead();
    }
}

function toggleMobileMenu() {
    const menu = document.getElementById('mobileNav');
    const button = document.getElementById('mobileMenuToggle');
    if (!menu || !button) {
        return;
    }
    const isOpen = menu.classList.toggle('open');
    button.classList.toggle('active', isOpen);
    document.body.classList.toggle('no-scroll', isOpen);
}

async function markNotificationsAsRead() {
    if (window.notificationsMarked) {
        return;
    }

    try {
        const response = await fetch('api/mark_notifications_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        });

        const data = await response.json();
        if (data.success) {
            window.notificationsMarked = true;
            const badge = document.querySelector('#notificationsButton .notification-dot');
            if (badge) {
                badge.style.animation = 'fadeOut 0.3s ease';
                setTimeout(() => badge.remove(), 300);
            }

            document.querySelectorAll('.notification-item.unread').forEach((item) => {
                item.classList.remove('unread');
            });
        }
    } catch (error) {
        console.error('Notifications mark read error:', error);
    }
}

const notificationFiltersState = {
    status: true,
    support: true,
    system: true,
    showRead: true
};

function toggleNotificationFilters(event) {
    if (event) event.stopPropagation();
    const panel = document.getElementById('notificationFilters');
    const button = document.querySelector('.notification-settings');
    if (!panel || !button) {
        return;
    }
    panel.classList.toggle('show');
    button.classList.toggle('active');
}

function resetNotificationFilters() {
    document.querySelectorAll('.notification-filter-checkbox').forEach((checkbox) => {
        const filter = checkbox.dataset.filter;
        checkbox.checked = true;
        notificationFiltersState[filter] = checkbox.checked;
    });
    applyNotificationFilters();
}

function registerNotificationFilters() {
    const panel = document.getElementById('notificationFilters');
    if (panel) {
        panel.addEventListener('click', (e) => e.stopPropagation());
    }
    document.querySelectorAll('.notification-filter-checkbox').forEach((checkbox) => {
        checkbox.addEventListener('change', () => {
            const filter = checkbox.dataset.filter;
            notificationFiltersState[filter] = checkbox.checked;
            applyNotificationFilters();
        });
    });
    applyNotificationFilters();
}

function applyNotificationFilters() {
    const items = document.querySelectorAll('.notification-item');
    const emptyState = document.getElementById('notificationFiltersEmpty');
    let visibleCount = 0;

    items.forEach((item) => {
        const type = item.dataset.type || 'system';
        const isRead = item.dataset.read === '1';
        const typeAllowed = notificationFiltersState[type];
        const readAllowed = notificationFiltersState.showRead || !isRead;

        if (typeAllowed && readAllowed) {
            item.style.display = '';
            visibleCount++;
        } else {
            item.style.display = 'none';
        }
    });

    if (emptyState) {
        emptyState.style.display = visibleCount === 0 ? 'block' : 'none';
    }
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const userMenu = document.querySelector('.user-menu');
    if (userMenu && !userMenu.contains(event.target)) {
        const dropdown = document.getElementById('userDropdown');
        const button = document.querySelector('.btn-user');
        if (dropdown) dropdown.classList.remove('active');
        if (button) button.classList.remove('active');
    }

    const notificationsMenu = document.querySelector('.notifications-menu');
    if (notificationsMenu && !notificationsMenu.contains(event.target)) {
        const dropdown = document.getElementById('notificationsDropdown');
        const button = document.getElementById('notificationsButton');
        const filtersPanel = document.getElementById('notificationFilters');
        const filtersBtn = document.querySelector('.notification-settings');
        if (dropdown) dropdown.classList.remove('active');
        if (button) button.classList.remove('active');
        if (filtersPanel) filtersPanel.classList.remove('show');
        if (filtersBtn) filtersBtn.classList.remove('active');
    }

    const mobileNav = document.getElementById('mobileNav');
    const mobileToggle = document.getElementById('mobileMenuToggle');
    if (mobileNav && mobileToggle && mobileNav.classList.contains('open')) {
        const isToggle = mobileToggle.contains(event.target);
        if (!mobileNav.contains(event.target) && !isToggle) {
            mobileNav.classList.remove('open');
            mobileToggle.classList.remove('active');
            document.body.classList.remove('no-scroll');
        }
    }
});

document.addEventListener('DOMContentLoaded', () => {
    registerNotificationFilters();
    const mobileNav = document.getElementById('mobileNav');
    if (mobileNav) {
        mobileNav.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', () => {
                mobileNav.classList.remove('open');
                const toggle = document.getElementById('mobileMenuToggle');
                if (toggle) toggle.classList.remove('active');
                document.body.classList.remove('no-scroll');
            });
        });
    }
});
</script>

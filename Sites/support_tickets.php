<?php
session_start();
require_once 'config.php';

$allowedRoles = ['support', 'moderator', 'admin', 'high-admin', 'owner'];
$hasAccess = false;
$userRole = 'user';

if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $userRole = $stmt->fetchColumn() ?: 'user';
        $hasAccess = in_array($userRole, $allowedRoles, true);
    } catch (PDOException $e) {
        $hasAccess = false;
    }
}

if (!$hasAccess) {
    http_response_code(403);
}

$statusFilter = $_GET['status'] ?? '';
$priorityFilter = $_GET['priority'] ?? '';
$searchQuery = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$statusMap = [
    'open' => ['label' => 'Открыт', 'badge' => 'status-open', 'icon' => 'fa-circle-dot'],
    'in-progress' => ['label' => 'В работе', 'badge' => 'status-progress', 'icon' => 'fa-rotate-right'],
    'resolved' => ['label' => 'Решён', 'badge' => 'status-resolved', 'icon' => 'fa-circle-check'],
    'closed' => ['label' => 'Закрыт', 'badge' => 'status-closed', 'icon' => 'fa-lock']
];

$priorityLabels = [
    'low' => 'Низкий',
    'medium' => 'Средний',
    'high' => 'Высокий',
    'urgent' => 'Срочный'
];

$csrfToken = Security::generateCSRFToken();
$serverOffsetMinutes = (int)((new DateTime())->getOffset() / 60);

if (
    $hasAccess &&
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_status'], $_POST['ticket_id'], $_POST['new_status'])
) {
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $statusMessage = ['type' => 'error', 'text' => 'Недействительный CSRF токен'];
    } else {
        $ticketId = (int)$_POST['ticket_id'];
        $newStatus = $_POST['new_status'];
        if (!isset($statusMap[$newStatus])) {
            $statusMessage = ['type' => 'error', 'text' => 'Некорректный статус'];
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE support_tickets SET status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$newStatus, $ticketId]);
                $statusMessage = ['type' => 'success', 'text' => 'Статус обновлён'];
            } catch (PDOException $e) {
                $statusMessage = ['type' => 'error', 'text' => 'Не удалось обновить статус'];
            }
        }
    }
}

$where = [];
$params = [];

if ($statusFilter && isset($statusMap[$statusFilter])) {
    $where[] = 't.status = ?';
    $params[] = $statusFilter;
}

if ($priorityFilter && isset($priorityLabels[$priorityFilter])) {
    $where[] = 't.priority = ?';
    $params[] = $priorityFilter;
}

if ($searchQuery !== '') {
    $where[] = '(t.ticket_number LIKE ? OR t.subject LIKE ? OR u.username LIKE ? OR u.email LIKE ?)';
    $like = "%{$searchQuery}%";
    array_push($params, $like, $like, $like, $like);
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$totalTickets = 0;
$stats = ['total' => 0, 'open' => 0, 'in-progress' => 0, 'resolved' => 0, 'closed' => 0];
$tickets = [];

if ($hasAccess) {
    try {
        $countSql = "SELECT COUNT(*) FROM support_tickets t LEFT JOIN users u ON t.user_id = u.id $whereClause";
        $stmt = $pdo->prepare($countSql);
        $stmt->execute($params);
        $totalTickets = (int)$stmt->fetchColumn();

        $dataSql = "
            SELECT t.*, u.username, u.email, u.avatar,
                   COALESCE(tr.replies_count, 0) AS replies_count,
                   COALESCE(tr.last_reply, t.created_at) AS last_activity
            FROM support_tickets t
            LEFT JOIN users u ON t.user_id = u.id
            LEFT JOIN (
                SELECT ticket_id, COUNT(*) AS replies_count, MAX(created_at) AS last_reply
                FROM ticket_replies
                GROUP BY ticket_id
            ) tr ON tr.ticket_id = t.id
            $whereClause
            ORDER BY last_activity DESC
            LIMIT $perPage OFFSET $offset
        ";
        $stmt = $pdo->prepare($dataSql);
        $stmt->execute($params);
        $tickets = $stmt->fetchAll();

        foreach ($tickets as &$ticket) {
            $thread = [];
            $thread[] = [
                'id' => 'ticket-' . $ticket['id'],
                'is_staff' => 0,
                'username' => $ticket['contact_name'] ?: ($ticket['username'] ?? 'Пользователь'),
                'avatar' => $ticket['avatar'] ?? null,
                'message' => $ticket['message'],
                'created_at' => $ticket['created_at']
            ];

            try {
                $stmtReplies = $pdo->prepare("SELECT tr.*, COALESCE(u.username, 'Пользователь') AS username, u.avatar FROM ticket_replies tr LEFT JOIN users u ON tr.user_id = u.id WHERE tr.ticket_id = ? ORDER BY tr.created_at ASC");
                $stmtReplies->execute([$ticket['id']]);
                $replies = $stmtReplies->fetchAll();
                foreach ($replies as $reply) {
                    $thread[] = $reply;
                }
            } catch (PDOException $e) {
                // ignore thread errors per ticket
            }

            $ticket['thread'] = $thread;
        }
        unset($ticket);

        $statsSql = "
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) AS open,
                SUM(CASE WHEN status = 'in-progress' THEN 1 ELSE 0 END) AS `in-progress`,
                SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) AS resolved,
                SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) AS closed
            FROM support_tickets
        ";
        $stats = $pdo->query($statsSql)->fetch();

    } catch (PDOException $e) {
        $tickets = [];
    }
}

$totalPages = max(1, (int)ceil($totalTickets / $perPage));

ob_start();
if (empty($tickets)): ?>
    <div class="empty-state">
        <i class="fas fa-inbox"></i>
        <h2>Нет тикетов по выбранным фильтрам</h2>
        <p>Попробуйте изменить параметры поиска</p>
    </div>
<?php else: ?>
    <div class="tickets-grid console-cards">
        <?php foreach ($tickets as $ticket): ?>
            <?php
                $statusData = $statusMap[$ticket['status']] ?? ['label' => $ticket['status'], 'badge' => 'status-open', 'icon' => 'fa-circle'];
                $priorityLabel = $priorityLabels[$ticket['priority']] ?? $ticket['priority'];
                $lastActivity = TimezoneHelper::toUserTime($ticket['last_activity'] ?? $ticket['created_at']);
            ?>
            <div class="ticket-card console-card">
                <div class="ticket-card-header">
                    <div>
                        <div class="ticket-number">#<?= htmlspecialchars($ticket['ticket_number']) ?></div>
                        <div class="ticket-subject"><?= htmlspecialchars($ticket['subject']) ?></div>
                    </div>
                    <span class="status-pill <?= $statusData['badge'] ?>">
                        <i class="fas <?= $statusData['icon'] ?>"></i>
                        <?= $statusData['label'] ?>
                    </span>
                </div>
                <div class="ticket-meta">
                    <div class="ticket-user">
                        <div class="avatar">
                            <?php if (!empty($ticket['avatar'])): ?>
                                <img src="<?= htmlspecialchars($ticket['avatar']) ?>" alt="Avatar">
                            <?php else: ?>
                                <?= strtoupper(substr($ticket['username'] ?? 'U', 0, 1)) ?>
                            <?php endif; ?>
                        </div>
                        <div>
                            <span class="user-name"><?= htmlspecialchars($ticket['username'] ?? 'Гость') ?></span>
                            <span class="user-email"><?= htmlspecialchars($ticket['email'] ?? $ticket['contact_email'] ?? '') ?></span>
                        </div>
                    </div>
                    <div class="ticket-info">
                        <span><i class="fas fa-clone"></i>Категория: <?= htmlspecialchars($ticket['category']) ?></span>
                        <span><i class="fas fa-flag"></i>Приоритет: <?= htmlspecialchars($priorityLabel) ?></span>
                        <span><i class="fas fa-reply"></i>Ответов: <?= $ticket['replies_count'] ?></span>
                        <span><i class="fas fa-clock"></i>Последняя активность: <?= $lastActivity ?></span>
                    </div>
                </div>
                <div class="ticket-actions">
                    <form method="POST" class="status-form">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                        <select name="new_status">
                            <?php foreach ($statusMap as $key => $data): ?>
                                <option value="<?= $key ?>" <?= $ticket['status'] === $key ? 'selected' : '' ?>><?= $data['label'] ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="update_status">
                            <i class="fas fa-save"></i>
                            Обновить
                        </button>
                    </form>
                    <button type="button" class="btn-secondary btn-chat-toggle" data-ticket-id="<?= $ticket['id'] ?>">
                        <i class="fas fa-comments"></i>
                        Чат с клиентом
                    </button>
                    <a href="ticket-details.php?id=<?= $ticket['id'] ?>" class="btn-secondary" target="_blank">
                        <i class="fas fa-arrow-up-right-from-square"></i>
                        Открыть тикет
                    </a>
                </div>

                <div class="ticket-thread" id="ticket-thread-<?= $ticket['id'] ?>">
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

                        <div class="chat-messages" data-ticket-id="<?= $ticket['id'] ?>">
                            <?php if (empty($ticket['thread'])): ?>
                                <div class="chat-empty">
                                    <i class="fas fa-comments"></i>
                                    <p>Пока нет сообщений. Напишите, если у вас есть вопросы по тикету.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($ticket['thread'] as $message): ?>
                                    <?php
                                        $isStaff = (bool)$message['is_staff'];
                                        $username = $isStaff ? 'Техподдержка' : ($message['username'] ?? 'Пользователь');
                                        $initial = mb_strtoupper(mb_substr($username, 0, 1));
                                        $time = TimezoneHelper::toUserTime($message['created_at']);
                                    ?>
                                    <div class="chat-message <?= $isStaff ? 'support' : 'user' ?>" data-timestamp="<?= htmlspecialchars($message['created_at']) ?>">
                                        <div class="chat-avatar">
                                            <?php if ($isStaff): ?>
                                                <i class="fas fa-headset"></i>
                                            <?php elseif (!empty($message['avatar'])): ?>
                                                <img src="<?= htmlspecialchars($message['avatar']) ?>" alt="Avatar">
                                            <?php else: ?>
                                                <?= htmlspecialchars($initial) ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="chat-bubble">
                                            <div class="chat-bubble-header">
                                                <span class="chat-sender"><?= htmlspecialchars($username) ?></span>
                                                <span class="chat-time" data-utc-time="<?= htmlspecialchars($message['created_at']) ?>"><?= $time ?></span>
                                            </div>
                                            <div class="chat-text"><?= nl2br(htmlspecialchars($message['message'])) ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <div class="chat-input-area">
                            <div class="chat-input-wrapper">
                                <textarea class="chat-input" placeholder="Ответьте клиенту..." data-ticket-id="<?= $ticket['id'] ?>" rows="1"></textarea>
                            </div>
                            <button type="button" class="chat-send-btn btn-send-reply" data-ticket-id="<?= $ticket['id'] ?>">
                                <i class="fas fa-paper-plane"></i>
                                Отправить
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif;
$ticketsHtml = ob_get_clean();

ob_start();
if ($totalPages > 1): ?>
    <div class="pagination">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php
                $query = $_GET;
                $query['page'] = $i;
                $url = 'support_tickets.php?' . http_build_query($query);
            ?>
            <a href="<?= $url ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
<?php endif;
$paginationHtml = ob_get_clean();

if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'tickets_html' => $ticketsHtml,
        'pagination_html' => $paginationHtml,
        'total' => $totalTickets,
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
    <title>Проверка обращений - <?= SITE_NAME ?></title>
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
    <link rel="stylesheet" href="css/support_tickets.css?v=<?= time() ?>">
    <link rel="stylesheet" href="css/orders.css?v=<?= time() ?>">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="tickets-page admin-console" data-csrf="<?= htmlspecialchars($csrfToken) ?>" data-offset="<?= htmlspecialchars($serverOffsetMinutes) ?>">
        <div class="container">
            <?php if (!$hasAccess): ?>
                <div class="access-denied">
                    <i class="fas fa-shield-halved"></i>
                    <h2>Недостаточно прав</h2>
                    <p>Страница проверки обращений доступна только сотрудникам техподдержки или владельцу проекта. Если вам нужен доступ, свяжитесь с администратором.</p>
                </div>
            <?php else: ?>
                <div class="console-hero">
                    <div class="hero-icon gradient-sky">
                        <i class="fas fa-comments"></i>
                    </div>
                    <div class="hero-meta">
                        <h1>Проверка обращений</h1>
                        <p>Статусы, приоритеты и история клиента в одном месте</p>
                    </div>
                    <div class="hero-actions">
                        <a href="support_orders.php" class="btn-outline-link">
                            <i class="fas fa-headset"></i>
                            К заказам
                        </a>
                    </div>
                </div>

                <?php if (!empty($statusMessage)): ?>
                    <div class="alert alert-<?= $statusMessage['type'] ?>">
                        <i class="fas <?= $statusMessage['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                        <span><?= htmlspecialchars($statusMessage['text']) ?></span>
                    </div>
                <?php endif; ?>

                <div class="tickets-stats console-stats">
                    <div class="stat-card">
                        <span class="stat-label">Всего</span>
                        <strong><?= $stats['total'] ?></strong>
                        <span class="stat-helper">тикетов</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-label">Открыты</span>
                        <strong><?= $stats['open'] ?></strong>
                        <span class="badge badge-open">нужны ответы</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-label">В работе</span>
                        <strong><?= $stats['in-progress'] ?></strong>
                        <span class="badge badge-progress">есть эскалации</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-label">Решены</span>
                        <strong><?= $stats['resolved'] ?></strong>
                        <span class="badge badge-resolved">ожидают закрытия</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-label">Закрыты</span>
                        <strong><?= $stats['closed'] ?></strong>
                        <span class="badge badge-closed">архив</span>
                    </div>
                </div>

                <form class="filter-bar console-filters" method="GET">
                    <div class="filter-group">
                        <label for="statusFilter">
                            <i class="fas fa-filter"></i>
                            Статус:
                        </label>
                        <select id="statusFilter" name="status" class="filter-select">
                            <option value="">Все</option>
                            <?php foreach ($statusMap as $key => $data): ?>
                                <option value="<?= $key ?>" <?= $statusFilter === $key ? 'selected' : '' ?>><?= $data['label'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="priorityFilter">
                            <i class="fas fa-flag"></i>
                            Приоритет:
                        </label>
                        <select id="priorityFilter" name="priority" class="filter-select">
                            <option value="">Все</option>
                            <?php foreach ($priorityLabels as $key => $label): ?>
                                <option value="<?= $key ?>" <?= $priorityFilter === $key ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" id="searchInput" placeholder="Номер тикета, тема, клиент..." value="<?= htmlspecialchars($searchQuery) ?>">
                    </div>
                    <button class="btn-reset-filters <?= ($statusFilter || $priorityFilter || $searchQuery !== '') ? '' : 'is-hidden' ?>" type="button" id="ticketsReset">
                        <i class="fas fa-times"></i>
                        Сбросить
                    </button>
                </form>

                <div id="ticketsContainer">
                    <?= $ticketsHtml ?>
                </div>
                <div id="ticketsPagination">
                    <?= $paginationHtml ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
    <script src="js/main.js"></script>
    <script>
        const ticketCsrfToken = <?= json_encode($csrfToken) ?>;
        const serverTzOffsetMinutes = <?= json_encode($serverOffsetMinutes) ?>;
    </script>
    <script src="js/support_tickets.js?v=<?= time() ?>"></script>
</body>
</html>

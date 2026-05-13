<?php
session_start();
require_once 'config.php';
require_once 'includes/security.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$ticketId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($ticketId <= 0) {
    header('Location: support.php');
    exit;
}

$isStaff = false;
$hasAccess = false;
$userId = (int)$_SESSION['user_id'];
$userRole = 'user';

try {
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $userRole = $stmt->fetchColumn() ?: 'user';
    $isStaff = in_array($userRole, ['support', 'admin', 'high-admin', 'owner'], true);
} catch (PDOException $e) {
    $isStaff = false;
}

try {
    $stmt = $pdo->prepare("SELECT t.*, u.username, u.email, u.avatar FROM support_tickets t LEFT JOIN users u ON t.user_id = u.id WHERE t.id = ? LIMIT 1");
    $stmt->execute([$ticketId]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $ticket = false;
}

if (!$ticket) {
    header('Location: support.php');
    exit;
}

$accessDenied = true;
if ($isStaff || ($ticket['user_id'] && (int)$ticket['user_id'] === $userId)) {
    $hasAccess = true;
    $accessDenied = false;
} else {
    http_response_code(403);
}

$csrfToken = Security::generateCSRFToken();

$conversation = [];
if (!$accessDenied) {
    $initialMessage = [
        'id' => 'ticket-' . $ticket['id'],
        'is_staff' => 0,
        'username' => $ticket['contact_name'] ?: ($ticket['username'] ?? 'Пользователь'),
        'avatar' => $ticket['avatar'] ?? null,
        'message' => $ticket['message'],
        'created_at' => $ticket['created_at'],
    ];
    $conversation[] = $initialMessage;

    try {
        $stmt = $pdo->prepare("SELECT tr.*, COALESCE(u.username, 'Пользователь') AS username, u.avatar FROM ticket_replies tr LEFT JOIN users u ON tr.user_id = u.id WHERE tr.ticket_id = ? ORDER BY tr.created_at ASC");
        $stmt->execute([$ticketId]);
        $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($replies as $reply) {
            $conversation[] = $reply;
        }
    } catch (PDOException $e) {
        $replies = [];
    }
}

$statusLabels = [
    'open' => 'Открыт',
    'in-progress' => 'В работе',
    'resolved' => 'Решён',
    'closed' => 'Закрыт'
];

$categoryLabels = [
    'technical' => 'Техническая проблема',
    'account' => 'Вопрос по аккаунту',
    'billing' => 'Оплата и заказы',
    'suggestion' => 'Предложение',
    'other' => 'Другое'
];

$categoryLabel = $categoryLabels[$ticket['category']] ?? $ticket['category'];
$authorName = $ticket['contact_name'] ?: ($ticket['username'] ?? 'Пользователь');
$authorEmail = $ticket['contact_email'] ?: ($ticket['email'] ?? '—');
$serverOffsetMinutes = (int)((new DateTime())->getOffset() / 60);

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тикет <?= htmlspecialchars($ticket['ticket_number']) ?> - <?= SITE_NAME ?></title>
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
    <link rel="stylesheet" href="css/support.css?v=<?= time() ?>">
    <link rel="stylesheet" href="css/orders.css?v=<?= time() ?>">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="support-page ticket-details-page">
        <div class="container">
            <?php if ($accessDenied): ?>
                <div class="access-denied">
                    <i class="fas fa-shield-halved"></i>
                    <h2>Недостаточно прав</h2>
                    <p>Вы не можете просматривать это обращение. Убедитесь, что авторизованы под владельцем тикета или обратитесь к техподдержке.</p>
                    <a href="support.php" class="btn-secondary" style="margin-top: 20px; display: inline-flex; gap: 8px; align-items: center;">
                        <i class="fas ва-arrow-left"></i>
                        Вернуться на страницу поддержки
                    </a>
                </div>
            <?php else: ?>
            <div class="support-header">
                <h1><i class="fas fa-ticket"></i> Тикет <?= htmlspecialchars($ticket['ticket_number']) ?></h1>
                <p><?= htmlspecialchars($ticket['subject']) ?></p>
            </div>

            <div class="support-container ticket-details-layout">
                <div class="support-card chat-panel">
                    <div class="ticket-overview">
                        <div class="ticket-overview__item">
                            <div class="ticket-overview__label">Статус</div>
                            <div class="ticket-overview__value">
                                <span class="status-pill status-<?= htmlspecialchars($ticket['status']) ?>">
                                    <i class="fas fa-circle"></i>
                                    <?= $statusLabels[$ticket['status']] ?? $ticket['status'] ?>
                                </span>
                            </div>
                        </div>
                        <div class="ticket-overview__item">
                            <div class="ticket-overview__label">Категория</div>
                            <div class="ticket-overview__value"><?= htmlspecialchars($categoryLabel) ?></div>
                        </div>
                        <div class="ticket-overview__item">
                            <div class="ticket-overview__label">Создан</div>
                            <div class="ticket-overview__value" data-utc-time="<?= htmlspecialchars($ticket['created_at']) ?>"><?= TimezoneHelper::toUserTime($ticket['created_at']) ?></div>
                        </div>
                        <div class="ticket-overview__item">
                            <div class="ticket-overview__label">Последнее обновление</div>
                            <div class="ticket-overview__value" data-utc-time="<?= htmlspecialchars($ticket['updated_at']) ?>"><?= TimezoneHelper::toUserTime($ticket['updated_at']) ?></div>
                        </div>
                    </div>

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

                        <div class="chat-messages" id="chatThread">
                            <?php if (empty($conversation)): ?>
                            <div class="chat-empty">
                                <i class="fas fa-comments"></i>
                                <p>Пока нет сообщений. Напишите, если у вас есть вопросы по тикету.</p>
                            </div>
                            <?php else: ?>
                                <?php
                                $lastDate = null;
                                foreach ($conversation as $message):
                                    $messageDate = date('Y-m-d', strtotime($message['created_at']));
                                    $today = date('Y-m-d');
                                    $yesterday = date('Y-m-d', strtotime('-1 day'));
                                    $isSupport = (bool)$message['is_staff'];
                                    $username = $isSupport ? 'Техподдержка' : ($message['username'] ?? 'Пользователь');
                                    $displayTime = TimezoneHelper::toUserTime($message['created_at']);
                                    $initial = mb_strtoupper(mb_substr($username, 0, 1));
                                    $messageId = $message['id'] ?? 'ticket-' . $ticket['id'];

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
                                <div class="chat-message <?= $isSupport ? 'support' : 'user' ?>" data-timestamp="<?= htmlspecialchars($message['created_at']) ?>" data-message-id="<?= htmlspecialchars($messageId) ?>">
                                    <div class="chat-avatar">
                                        <?php if ($isSupport): ?>
                                            <i class="fas fa-headset"></i>
                                        <?php elseif (!empty($message['avatar'])): ?>
                                            <img src="<?= htmlspecialchars($message['avatar']) ?>" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover; border-radius: 10px;">
                                        <?php else: ?>
                                            <?= htmlspecialchars($initial) ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="chat-bubble">
                                        <div class="chat-bubble-header">
                                            <span class="chat-sender"><?= htmlspecialchars($username) ?></span>
                                            <span class="chat-time" data-utc-time="<?= htmlspecialchars($message['created_at']) ?>"><?= $displayTime ?></span>
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
                                    id="replyMessage"
                                    placeholder="Напишите ваше сообщение..."
                                    rows="1"
                                ></textarea>
                                <div class="reply-error" id="replyError" role="alert" aria-live="polite"></div>
                            </div>
                            <button class="chat-send-btn" type="button" id="sendReplyBtn">
                                <i class="fas fa-paper-plane"></i>
                                <span>Отправить</span>
                            </button>
                        </div>
                    </div>
                </div>

                <aside class="ticket-sidebar">
                    <div class="ticket-history">
                        <h3>Информация</h3>
                        <div class="history-list">
                            <div class="history-item">
                                <i class="fas fa-user"></i>
                                <div>
                                    <small>Автор</small>
                                    <div><?= htmlspecialchars($authorName) ?></div>
                                </div>
                            </div>
                            <div class="history-item">
                                <i class="fas fa-envelope"></i>
                                <div>
                                    <small>Email</small>
                                    <div><?= htmlspecialchars($authorEmail) ?></div>
                                </div>
                            </div>
                            <div class="history-item">
                                <i class="fas fa-hashtag"></i>
                                <div>
                                    <small>Номер тикета</small>
                                    <div><?= htmlspecialchars($ticket['ticket_number']) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
    <script src="js/main.js"></script>
    <script>
        const ticketId = <?= json_encode($ticketId) ?>;
        const csrfToken = <?= json_encode($csrfToken) ?>;
        const serverTzOffsetMinutes = <?= json_encode($serverOffsetMinutes) ?>;
    </script>
    <script src="js/ticket.js?v=<?= time() ?>"></script>
</body>
</html>

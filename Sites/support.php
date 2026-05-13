<?php
session_start();
require_once 'config.php';
require_once 'includes/security.php';

$error = '';
$success = '';
$csrfToken = Security::generateCSRFToken();

// Handle ticket submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_ticket'])) {
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Недействительный запрос';
    } else {
        $subject = trim($_POST['subject'] ?? '');
        $category = $_POST['category'] ?? '';
        $message = trim($_POST['message'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $name = trim($_POST['name'] ?? '');
        
        if (empty($subject) || empty($category) || empty($message)) {
            $error = 'Пожалуйста, заполните все обязательные поля';
        } elseif (!isset($_SESSION['user_id']) && (empty($email) || empty($name))) {
            $error = 'Пожалуйста, укажите ваше имя и email';
        } else {
            try {
                $userId = $_SESSION['user_id'] ?? null;
                $ticketNumber = 'TKT-' . strtoupper(bin2hex(random_bytes(4)));
                
                $stmt = $pdo->prepare("
                    INSERT INTO support_tickets (user_id, ticket_number, subject, category, message, contact_email, contact_name, status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'open', NOW())
                ");
                $stmt->execute([
                    $userId,
                    $ticketNumber,
                    $subject,
                    $category,
                    $message,
                    $email ?: ($_SESSION['email'] ?? null),
                    $name ?: ($_SESSION['username'] ?? null)
                ]);
                
                $success = "Ваше обращение успешно отправлено! Номер тикета: <strong>$ticketNumber</strong>";
                
                Security::logSecurityEvent('Support ticket created', [
                    'ticket_number' => $ticketNumber,
                    'user_id' => $userId
                ]);
            } catch (PDOException $e) {
                $error = 'Ошибка отправки обращения. Попробуйте позже';
            }
        }
    }
}

// Get user's tickets if logged in
$userTickets = [];
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("
            SELECT t.*, 
                   COUNT(tr.id) as replies_count,
                   MAX(tr.created_at) as last_reply
            FROM support_tickets t
            LEFT JOIN ticket_replies tr ON t.id = tr.ticket_id
            WHERE t.user_id = ?
            GROUP BY t.id
            ORDER BY t.created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $userTickets = $stmt->fetchAll();
    } catch (PDOException $e) {
        $userTickets = [];
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Обратная связь - <?= SITE_NAME ?></title>
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
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="support-page">
        <div class="container">
            <div class="support-header">
                <h1><i class="fas fa-headset"></i> Обратная связь</h1>
                <p>Мы всегда рады помочь! Опишите вашу проблему или предложение</p>
            </div>

            <div class="support-container">
                <!-- Форма создания тикета -->
                <div class="support-card support-form-section">
                    <h2 class="section-title">
                        <i class="fas fa-paper-plane"></i>
                        Создать обращение
                    </h2>

                    <?php if ($error): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i>
                            <span><?= htmlspecialchars($error) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <span><?= $success ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                        <?php if (!isset($_SESSION['user_id'])): ?>
                        <div class="form-group">
                            <label>
                                <i class="fas fa-user"></i>
                                Ваше имя *
                            </label>
                            <input 
                                type="text" 
                                name="name" 
                                placeholder="Введите ваше имя"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label>
                                <i class="fas fa-envelope"></i>
                                Email *
                            </label>
                            <input 
                                type="email" 
                                name="email" 
                                placeholder="your@email.com"
                                required
                            >
                        </div>
                        <?php endif; ?>

                        <div class="form-group">
                            <label>
                                <i class="fas fa-tag"></i>
                                Категория *
                            </label>
                            <select name="category" required>
                                <option value="">Выберите категорию</option>
                                <option value="technical">Техническая проблема</option>
                                <option value="account">Вопрос по аккаунту</option>
                                <option value="billing">Оплата и заказы</option>
                                <option value="suggestion">Предложение</option>
                                <option value="other">Другое</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>
                                <i class="fas fa-heading"></i>
                                Тема обращения *
                            </label>
                            <input 
                                type="text" 
                                name="subject" 
                                placeholder="Кратко опишите проблему"
                                maxlength="200"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label>
                                <i class="fas fa-comment"></i>
                                Сообщение *
                            </label>
                            <textarea 
                                name="message" 
                                placeholder="Подробно опишите вашу проблему или вопрос..."
                                required
                            ></textarea>
                        </div>

                        <button type="submit" name="submit_ticket" class="btn-submit">
                            <i class="fas fa-paper-plane"></i>
                            Отправить обращение
                        </button>
                    </form>
                </div>

                <!-- Список тикетов пользователя -->
                <div class="support-card support-tickets-section">
                    <h2 class="section-title">
                        <i class="fas fa-ticket-alt"></i>
                        <?= isset($_SESSION['user_id']) ? 'Мои обращения' : 'Информация' ?>
                    </h2>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if (empty($userTickets)): ?>
                            <div class="empty-tickets">
                                <i class="fas fa-inbox"></i>
                                <p>У вас пока нет обращений</p>
                            </div>
                        <?php else: ?>
                            <div class="ticket-list">
                                <?php foreach ($userTickets as $ticket): ?>
                                    <div class="ticket-card" onclick="viewTicket(<?= $ticket['id'] ?>)">
                                        <div class="ticket-header">
                                            <span class="ticket-number"><?= htmlspecialchars($ticket['ticket_number']) ?></span>
                                            <span class="ticket-status status-<?= $ticket['status'] ?>">
                                                <?php
                                                $statusLabels = [
                                                    'open' => 'Открыт',
                                                    'in-progress' => 'В работе',
                                                    'resolved' => 'Решён',
                                                    'closed' => 'Закрыт'
                                                ];
                                                echo $statusLabels[$ticket['status']] ?? $ticket['status'];
                                                ?>
                                            </span>
                                        </div>
                                        <div class="ticket-subject"><?= htmlspecialchars($ticket['subject']) ?></div>
                                        <div class="ticket-meta">
                                            <span><i class="fas fa-clock"></i><?= date('d.m.Y', strtotime($ticket['created_at'])) ?></span>
                                            <?php if ($ticket['replies_count'] > 0): ?>
                                                <span><i class="fas fa-comment"></i><?= $ticket['replies_count'] ?> ответов</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="support-login-block">
                            <h3><i class="fas fa-info-circle"></i> Войдите для отслеживания</h3>
                            <p>
                                Войдите в аккаунт, чтобы видеть историю ваших обращений и получать ответы от службы поддержки прямо на сайте.
                            </p>
                            <a class="support-login-button" href="login.php">
                                <i class="fas fa-sign-in-alt"></i>
                                Войти в аккаунт
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="js/main.js"></script>
    <script>
        function viewTicket(ticketId) {
            window.location.href = `ticket-details.php?id=${ticketId}`;
        }
    </script>
</body>
</html>

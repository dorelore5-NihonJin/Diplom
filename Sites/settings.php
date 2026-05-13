<?php
session_start();
require_once 'config.php';
require_once 'includes/security.php';

function ensureSettingsColumns(PDO $pdo): void {
    static $checked = false;
    if ($checked) {
        return;
    }

    $columns = [
        'username_changed_at' => "ALTER TABLE `users` ADD COLUMN `username_changed_at` datetime DEFAULT NULL AFTER `profile_updated`",
        'notify_order_updates' => "ALTER TABLE `users` ADD COLUMN `notify_order_updates` tinyint(1) NOT NULL DEFAULT 1 AFTER `username_changed_at`",
        'notify_support_replies' => "ALTER TABLE `users` ADD COLUMN `notify_support_replies` tinyint(1) NOT NULL DEFAULT 1 AFTER `notify_order_updates`",
        'profile_visibility' => "ALTER TABLE `users` ADD COLUMN `profile_visibility` enum('public','members','private') NOT NULL DEFAULT 'public' AFTER `notify_support_replies`",
        'show_online_status' => "ALTER TABLE `users` ADD COLUMN `show_online_status` tinyint(1) NOT NULL DEFAULT 1 AFTER `profile_visibility`",
        'session_version' => "ALTER TABLE `users` ADD COLUMN `session_version` int(11) NOT NULL DEFAULT 1 AFTER `show_online_status`",
        'session_invalidated_at' => "ALTER TABLE `users` ADD COLUMN `session_invalidated_at` datetime DEFAULT NULL AFTER `session_version`"
    ];

    foreach ($columns as $column => $alterSql) {
        try {
            $stmt = $pdo->prepare("SHOW COLUMNS FROM `users` LIKE ?");
            $stmt->execute([$column]);
            if (!$stmt->fetch()) {
                $pdo->exec($alterSql);
            }
        } catch (PDOException $e) {
            error_log('Failed ensuring column ' . $column . ': ' . $e->getMessage());
        }
    }

    $checked = true;
}
ensureSettingsColumns($pdo);

function ensureSessionTable(PDO $pdo): void {
    static $created = false;
    if ($created) {
        return;
    }

    $createSql = "CREATE TABLE IF NOT EXISTS `user_sessions` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `user_id` INT(11) NOT NULL,
        `session_hash` VARCHAR(128) NOT NULL,
        `ip_address` VARCHAR(45) DEFAULT NULL,
        `user_agent` TEXT DEFAULT NULL,
        `platform` VARCHAR(50) DEFAULT NULL,
        `browser` VARCHAR(50) DEFAULT NULL,
        `device` VARCHAR(50) DEFAULT NULL,
        `created_at` DATETIME NOT NULL,
        `last_seen` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uniq_session_hash` (`session_hash`),
        KEY `idx_user_sessions_user` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    try {
        $pdo->exec($createSql);
    } catch (PDOException $e) {
        Security::logSecurityEvent('Failed to ensure user_sessions table', ['error' => $e->getMessage()]);
    }

    $created = true;
}
ensureSessionTable($pdo);

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$messages = [
    'success' => [],
    'error' => []
];

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header('Location: logout.php');
        exit;
    }
} catch (PDOException $e) {
    die('Database error');
}

$csrfToken = Security::generateCSRFToken();
$now = new DateTime();
$usernameCooldownDays = 90;
$usernameChangedAt = !empty($user['username_changed_at']) ? new DateTime($user['username_changed_at']) : null;
$nextUsernameChange = $usernameChangedAt ? (clone $usernameChangedAt)->modify('+' . $usernameCooldownDays . ' days') : null;
$canChangeUsername = !$usernameChangedAt || $now >= $nextUsernameChange;
$profileVisibilityOptions = [
    'public' => 'Виден всем',
    'members' => 'Только зарегистрированные',
    'private' => 'Только по прямой ссылке'
];
$currentVisibility = $user['profile_visibility'] ?? 'public';
$currentShowOnline = !empty($user['show_online_status']);
$currentSessionHash = session_id() ? hash('sha256', session_id()) : null;
$sessionHistory = [];
try {
    $sessionStmt = $pdo->prepare("SELECT session_hash, ip_address, ip_location, platform, browser, device, created_at, last_seen FROM user_sessions WHERE user_id = ? ORDER BY created_at DESC LIMIT 12");
    $sessionStmt->execute([$userId]);
    $sessionHistory = $sessionStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
    $sessionHistory = [];
}

function addSuccess(array &$messages, string $text): void {
    $messages['success'][] = $text;
}

function addError(array &$messages, string $text): void {
    $messages['error'][] = $text;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $messages['error'][] = 'Недействительный CSRF токен';
    } else {
        // Change password
        if (isset($_POST['change_password'])) {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (!Security::verifyPassword($currentPassword, $user['password'])) {
                $messages['error'][] = 'Текущий пароль введён неверно';
            } elseif ($newPassword !== $confirmPassword) {
                $messages['error'][] = 'Новый пароль и подтверждение не совпадают';
            } else {
                $validationResult = Security::validatePassword($newPassword);
                if ($validationResult !== true) {
                    $messages['error'][] = is_array($validationResult)
                        ? implode('<br>', $validationResult)
                        : $validationResult;
                } else {
                    $hashed = Security::hashPassword($newPassword);
                    try {
                        $updateStmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
                        $updateStmt->execute([$hashed, $userId]);
                        addSuccess($messages, 'Пароль успешно обновлён');
                    } catch (PDOException $e) {
                        addError($messages, 'Не удалось обновить пароль');
                    }
                }
            }
        }

        // Change username
        if (isset($_POST['change_username'])) {
            $newUsername = trim($_POST['new_username'] ?? '');
            if (!$canChangeUsername) {
                addError($messages, 'Изменение имени будет доступно ' . ($nextUsernameChange ? $nextUsernameChange->format('d.m.Y') : 'позже'));
            } else {
                $validation = Security::validateUsername($newUsername);
                if ($validation !== true) {
                    addError($messages, $validation);
                } elseif (strcasecmp($newUsername, $user['username']) === 0) {
                    addError($messages, 'Новое имя совпадает с текущим');
                } else {
                    try {
                        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id <> ?");
                        $checkStmt->execute([$newUsername, $userId]);
                        if ($checkStmt->fetchColumn() > 0) {
                            addError($messages, 'Пользователь с таким именем уже существует');
                        } else {
                            $pdo->beginTransaction();
                            $updateStmt = $pdo->prepare("UPDATE users SET username = ?, username_changed_at = NOW(), updated_at = NOW() WHERE id = ?");
                            $updateStmt->execute([$newUsername, $userId]);
                            $_SESSION['username'] = $newUsername;
                            $pdo->commit();
                            addSuccess($messages, 'Имя пользователя успешно изменено');
                            $user['username'] = $newUsername;
                            $user['username_changed_at'] = date('Y-m-d H:i:s');
                            $canChangeUsername = false;
                            $nextUsernameChange = (new DateTime())->modify('+' . $usernameCooldownDays . ' days');
                        }
                    } catch (PDOException $e) {
                        $pdo->rollBack();
                        addError($messages, 'Ошибка при изменении имени пользователя');
                    }
                }
            }
        }

        // Notification preferences
        if (isset($_POST['update_preferences'])) {
            $orderNotifications = isset($_POST['notify_order_updates']) ? 1 : 0;
            $supportNotifications = isset($_POST['notify_support_replies']) ? 1 : 0;
            try {
                $prefStmt = $pdo->prepare("UPDATE users SET notify_order_updates = ?, notify_support_replies = ?, updated_at = NOW() WHERE id = ?");
                $prefStmt->execute([$orderNotifications, $supportNotifications, $userId]);
                addSuccess($messages, 'Настройки уведомлений обновлены');
                $user['notify_order_updates'] = $orderNotifications;
                $user['notify_support_replies'] = $supportNotifications;
            } catch (PDOException $e) {
                addError($messages, 'Не удалось сохранить настройки уведомлений');
            }
        }

        // Privacy preferences
        if (isset($_POST['update_privacy'])) {
            $visibility = $_POST['profile_visibility'] ?? 'public';
            if (!array_key_exists($visibility, $profileVisibilityOptions)) {
                addError($messages, 'Недопустимое значение видимости профиля');
            } else {
                $showOnline = isset($_POST['show_online_status']) ? 1 : 0;
                try {
                    $privacyStmt = $pdo->prepare("UPDATE users SET profile_visibility = ?, show_online_status = ?, updated_at = NOW() WHERE id = ?");
                    $privacyStmt->execute([$visibility, $showOnline, $userId]);
                    addSuccess($messages, 'Параметры приватности обновлены');
                    $user['profile_visibility'] = $visibility;
                    $user['show_online_status'] = $showOnline;
                    $currentVisibility = $visibility;
                    $currentShowOnline = (bool) $showOnline;
                } catch (PDOException $e) {
                    addError($messages, 'Не удалось сохранить приватность');
                }
            }
        }

        // Session invalidation
        if (isset($_POST['invalidate_sessions'])) {
            try {
                $invalidateStmt = $pdo->prepare("UPDATE users SET session_version = session_version + 1, session_invalidated_at = NOW(), remember_token = NULL, remember_token_expires = NULL WHERE id = ?");
                $invalidateStmt->execute([$userId]);
                $user['session_version'] = ($user['session_version'] ?? 1) + 1;
                $user['session_invalidated_at'] = date('Y-m-d H:i:s');
                $_SESSION['session_version'] = $user['session_version'];
                Security::forgetRememberMe($pdo, $userId);
                if ($currentSessionHash) {
                    $deleteStmt = $pdo->prepare("DELETE FROM user_sessions WHERE user_id = ? AND session_hash <> ?");
                    $deleteStmt->execute([$userId, $currentSessionHash]);
                } else {
                    $deleteStmt = $pdo->prepare("DELETE FROM user_sessions WHERE user_id = ?");
                    $deleteStmt->execute([$userId]);
                }
                $sessionStmt = $pdo->prepare("SELECT session_hash, ip_address, ip_location, platform, browser, device, created_at, last_seen FROM user_sessions WHERE user_id = ? ORDER BY created_at DESC LIMIT 12");
                $sessionStmt->execute([$userId]);
                $sessionHistory = $sessionStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                Security::logSecurityEvent('Sessions invalidated by user', ['user_id' => $userId]);
                addSuccess($messages, 'Все активные сессии будут закрыты. Перелогиньтесь на других устройствах.');
            } catch (PDOException $e) {
                addError($messages, 'Не удалось завершить другие сессии');
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
    <title>Настройки аккаунта - <?= SITE_NAME ?></title>
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
    <link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
    <link rel="stylesheet" href="css/settings.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="settings-page">
        <div class="container">
            <header class="settings-hero">
                <div>
                    <p class="eyebrow">Центр управления аккаунтом</p>
                    <h1>Настройте HyperPC под себя</h1>
                    <p class="subtitle">Обновляйте профиль, безопасность и уведомления — все ключевые параметры собраны в одном месте.</p>
                </div>
                <div class="account-summary">
                    <div class="summary-avatar">
                        <?php if (!empty($user['avatar'])): ?>
                            <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="Avatar">
                        <?php else: ?>
                            <i class="fas fa-user"></i>
                        <?php endif; ?>
                    </div>
                    <div>
                        <h3><?= htmlspecialchars($user['username']) ?></h3>
                        <p><?= htmlspecialchars($user['email']) ?></p>
                        <span class="role-chip role-<?= htmlspecialchars($user['role']) ?>"><?= htmlspecialchars($user['role']) ?></span>
                    </div>
                </div>
            </header>


            <?php if (!empty($schemaIssues)): ?>
                <div class="settings-alert error">
                    <i class="fas fa-database"></i>
                    <span>Базе данных не хватает обязательных колонок. Выполните следующие запросы вручную, затем обновите страницу:</span>
                </div>
                <pre class="schema-alert-code"><?php foreach ($schemaIssues as $statement): ?><?= htmlspecialchars($statement) ?>;
<?php endforeach; ?></pre>
            <?php endif; ?>

            <?php foreach ($messages as $type => $list): ?>
                <?php foreach ($list as $message): ?>
                    <div class="settings-alert <?= $type === 'success' ? 'success' : 'error' ?>">
                        <i class="fas <?= $type === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i>
                        <span><?= $message ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>

            <div class="settings-tabs" role="tablist" aria-label="Разделы настроек">
                <?php
                    $sections = [
                        'profile' => ['label' => 'Профиль', 'icon' => 'fa-user-pen'],
                        'security' => ['label' => 'Безопасность', 'icon' => 'fa-shield-keyhole'],
                        'notifications' => ['label' => 'Уведомления', 'icon' => 'fa-bell'],
                        'privacy' => ['label' => 'Приватность', 'icon' => 'fa-user-shield'],
                        'interface' => ['label' => 'Интерфейс', 'icon' => 'fa-laptop'],
                        'sessions' => ['label' => 'Сессии', 'icon' => 'fa-right-from-bracket']
                    ];
                ?>
                <?php foreach ($sections as $slug => $info): ?>
                    <button class="settings-tab" data-tab="<?= $slug ?>" role="tab" type="button">
                        <i class="fas <?= $info['icon'] ?>"></i>
                        <span><?= $info['label'] ?></span>
                    </button>
                <?php endforeach; ?>
            </div>

            <section class="settings-sections">
                <article class="settings-card" data-section="profile">
                    <div class="card-header">
                        <div>
                            <h2><i class="fas fa-user-pen"></i> Имя пользователя</h2>
                            <p>Изменить никнейм можно раз в 3 месяца, чтобы защититься от злоупотреблений.</p>
                        </div>
                        <?php if (!$canChangeUsername && $nextUsernameChange): ?>
                            <span class="badge cooldown">Доступно после <?= $nextUsernameChange->format('d.m.Y') ?></span>
                        <?php endif; ?>
                    </div>
                    <form method="POST" class="settings-form">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        <div class="form-field">
                            <label for="new_username">Новое имя</label>
                            <input type="text" id="new_username" name="new_username" placeholder="Например, NihonJinPro" minlength="3" maxlength="50" <?= $canChangeUsername ? '' : 'disabled' ?>>
                        </div>
                        <button type="submit" name="change_username" class="btn-primary" <?= $canChangeUsername ? '' : 'disabled' ?>>
                            <i class="fas fa-rotate"></i> Обновить имя
                        </button>
                        <?php if (!$canChangeUsername && $nextUsernameChange): ?>
                            <div class="cooldown-notice">
                                <i class="fas fa-hourglass-half"></i>
                                Следующее изменение будет доступно <strong><?= $nextUsernameChange->format('d.m.Y') ?></strong>
                                <small>Осталось <?= $now->diff($nextUsernameChange)->days ?> дней ожидания</small>
                            </div>
                        <?php endif; ?>
                    </form>
                </article>

                <article class="settings-card" data-section="security">
                    <div class="card-header">
                        <div>
                            <h2><i class="fas fa-shield-keyhole"></i> Безопасность</h2>
                            <p>Регулярно меняйте пароль, чтобы сохранить аккаунт в безопасности.</p>
                        </div>
                    </div>
                    <form method="POST" class="settings-form">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        <div class="form-field">
                            <label for="current_password">Текущий пароль</label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>
                        <div class="form-row">
                            <div class="form-field">
                                <label for="new_password">Новый пароль</label>
                                <input type="password" id="new_password" name="new_password" required>
                            </div>
                            <div class="form-field">
                                <label for="confirm_password">Подтверждение</label>
                                <input type="password" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>
                        <button type="submit" name="change_password" class="btn-primary">
                            <i class="fas fa-key"></i> Сохранить пароль
                        </button>
                    </form>
                </article>

                <article class="settings-card" data-section="notifications">
                    <div class="card-header">
                        <div>
                            <h2><i class="fas fa-bell"></i> Уведомления</h2>
                            <p>Настройте, какие события будут приходить вам на e-mail.</p>
                        </div>
                    </div>
                    <form method="POST" class="settings-form">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        <label class="toggle">
                            <input type="checkbox" name="notify_order_updates" <?= !empty($user['notify_order_updates']) ? 'checked' : '' ?>>
                            <span>
                                <strong>Изменения по заказам</strong>
                                <small>Новые статусы, отправка и счёта</small>
                            </span>
                        </label>
                        <label class="toggle">
                            <input type="checkbox" name="notify_support_replies" <?= !empty($user['notify_support_replies']) ? 'checked' : '' ?>>
                            <span>
                                <strong>Ответы поддержки</strong>
                                <small>Когда мы ответим на ваш тикет</small>
                            </span>
                        </label>
                        <button type="submit" name="update_preferences" class="btn-secondary">
                            <i class="fas fa-save"></i> Сохранить уведомления
                        </button>
                    </form>
                </article>

                <article class="settings-card" data-section="interface">
                    <div class="card-header">
                        <div>
                            <h2><i class="fas fa-laptop"></i> Интерфейс</h2>
                            <p>Выберите, как должен выглядеть и ощущаться интерфейс HyperPC.</p>
                        </div>
                    </div>
                    <div class="settings-form">
                        <div class="preferences-row">
                            <div>
                                <h4>Тема сайта</h4>
                                <p>Сменить тему можно в любом месте с помощью переключателя в шапке.</p>
                            </div>
                            <button class="btn-outline" id="toggleThemeButton" type="button">
                                <i class="fas fa-adjust"></i> Переключить тему
                            </button>
                        </div>
                        <div class="preferences-row">
                            <div>
                                <h4>Часовой пояс</h4>
                                <p>Мы автоматически отображаем время в зоне вашего браузера.</p>
                            </div>
                            <span class="timezone-label" id="timezoneLabel"></span>
                        </div>
                    </div>
                </article>

                <article class="settings-card" data-section="privacy">
                    <div class="card-header">
                        <div>
                            <h2><i class="fas fa-user-shield"></i> Приватность</h2>
                            <p>Управляйте тем, кто видит ваш профиль и статус.</p>
                        </div>
                    </div>
                    <form method="POST" class="settings-form privacy-form">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        <div class="radio-group">
                            <?php foreach ($profileVisibilityOptions as $slug => $label): ?>
                                <label class="radio-pill">
                                    <input type="radio" name="profile_visibility" value="<?= $slug ?>" <?= $currentVisibility === $slug ? 'checked' : '' ?>>
                                    <span>
                                        <strong><?= $label ?></strong>
                                        <small>
                                            <?php if ($slug === 'public'): ?>Профиль доступен всем посетителям<?php elseif ($slug === 'members'): ?>Только авторизованные пользователи<?php else: ?>Профиль скрыт из публичных разделов<?php endif; ?>
                                        </small>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <label class="toggle">
                            <input type="checkbox" name="show_online_status" <?= $currentShowOnline ? 'checked' : '' ?>>
                            <span>
                                <strong>Показывать статус "Онлайн"</strong>
                                <small>При отключении другие пользователи видят только время последнего визита</small>
                            </span>
                        </label>
                        <button type="submit" name="update_privacy" class="btn-secondary">
                            <i class="fas fa-lock"></i> Сохранить приватность
                        </button>
                    </form>
                </article>

                <article class="settings-card" data-section="sessions">
                    <div class="card-header">
                        <div>
                            <h2><i class="fas fa-right-from-bracket"></i> Активные сессии</h2>
                            <p>Если подозреваете чужой вход, разлогиньте все устройства.</p>
                        </div>
                        <?php if (!empty($user['session_invalidated_at'])): ?>
                            <span class="badge muted">Последнее обнуление: <?= (new DateTime($user['session_invalidated_at']))->format('d.m.Y H:i') ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="sessions-panel">
                        <div>
                            <h4>Что произойдет?</h4>
                            <ul>
                                <li>Мы сбросим все токены "Запомнить меня".</li>
                                <li>На всех устройствах потребуется повторный вход.</li>
                                <li>Текущая сессия останется активной.</li>
                            </ul>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                            <button type="submit" name="invalidate_sessions" class="btn-danger">
                                <i class="fas fa-right-from-bracket"></i> Разлогинить все устройства
                            </button>
                        </form>
                    </div>
                    <div class="session-history">
                        <h4>Последние входы</h4>
                        <?php if (empty($sessionHistory)): ?>
                            <p class="session-empty">История пока пуста.</p>
                        <?php else: ?>
                            <ul>
                                <?php foreach ($sessionHistory as $record): ?>
                                    <li class="session-item <?= $currentSessionHash && $record['session_hash'] === $currentSessionHash ? 'current' : '' ?>">
                                        <div class="session-device">
                                            <span class="session-device-label">
                                                <?= htmlspecialchars($record['device'] ?? 'Устройство') ?> · <?= htmlspecialchars($record['platform'] ?? 'Платформа') ?>
                                            </span>
                                            <small><?= htmlspecialchars($record['browser'] ?? '') ?></small>
                                        </div>
                                        <div class="session-meta">
                                            <span><?= htmlspecialchars($record['ip_address'] ?? 'IP неизвестен') ?></span>
                                        </div>
                                        <div class="session-meta">
                                            <span><?= htmlspecialchars($record['ip_location'] ?? 'Локация неизвестна') ?></span>
                                            <span><?= date('d.m.Y H:i', strtotime($record['created_at'])) ?></span>
                                        </div>
                                        <?php if ($currentSessionHash && $record['session_hash'] === $currentSessionHash): ?>
                                            <span class="session-tag">Текущее устройство</span>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </article>
            </section>
        </div>
    </main>

    <script>
        const tabs = document.querySelectorAll('.settings-tab');
        const sections = document.querySelectorAll('.settings-card');
        const availableSections = Array.from(sections).map(section => section.dataset.section);
        const storedSection = localStorage.getItem('settings_section');
        const activeSection = availableSections.includes(storedSection) ? storedSection : 'profile';

        function activateSection(slug) {
            tabs.forEach(tab => {
                const match = tab.dataset.tab === slug;
                tab.classList.toggle('is-active', match);
                tab.setAttribute('aria-selected', match ? 'true' : 'false');
            });
            sections.forEach(section => {
                section.classList.toggle('is-active', section.dataset.section === slug);
            });
            localStorage.setItem('settings_section', slug);
        }

        tabs.forEach(tab => {
            tab.addEventListener('click', () => activateSection(tab.dataset.tab));
        });

        activateSection(activeSection);

        const themeButton = document.getElementById('toggleThemeButton');
        if (themeButton) {
            themeButton.addEventListener('click', () => {
                const root = document.documentElement;
                const newTheme = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                root.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
            });
        }

        const timezoneLabel = document.getElementById('timezoneLabel');
        if (timezoneLabel) {
            const tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
            timezoneLabel.textContent = tz;
        }
    </script>
</body>
</html>

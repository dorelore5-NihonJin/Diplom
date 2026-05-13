<?php
session_start();
require_once 'config.php';
require_once 'includes/security.php';
require_once 'includes/components_union.php';

$error = '';
$success = '';
$blockActionMessage = '';
$isOwnProfile = false;

$requestedUsername = null;
$userNotFound = false;
if (isset($_GET['username'])) {
    $requestedUsername = trim($_GET['username']);
} elseif (isset($_GET['user'])) {
    $requestedUsername = trim($_GET['user']);
}

if ($requestedUsername !== null && $requestedUsername !== '') {
    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$requestedUsername]);
        $profileUser = $stmt->fetch();
        
        if (!$profileUser) {
            $userNotFound = true;
        } else {
            $userId = (int)$profileUser['id'];
            $isOwnProfile = isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === $userId;
        }
    } catch (PDOException $e) {
        header('Location: index.php');
        exit;
    }
} else {
    // Viewing own profile requires authentication
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
    $userId = (int)$_SESSION['user_id'];
    $isOwnProfile = true;
}

if (!$userNotFound) {
    // Handle avatar upload (only for own profile)
    if ($isOwnProfile && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_avatar'])) {
        if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $error = 'Недействительный запрос';
        } else {
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $maxSize = 5 * 1024 * 1024; // 5MB
                
                if (!in_array($_FILES['avatar']['type'], $allowedTypes)) {
                    $error = 'Неподдерживаемый формат изображения';
                } elseif ($_FILES['avatar']['size'] > $maxSize) {
                    $error = 'Размер файла не должен превышать 5MB';
                } else {
                    $uploadDir = 'uploads/avatars/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                    $filename = 'avatar_' . $userId . '_' . time() . '.' . $extension;
                    $filepath = $uploadDir . $filename;
                    
                    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $filepath)) {
                        // Delete old avatar if exists
                        if (!empty($user['avatar']) && file_exists($user['avatar'])) {
                            unlink($user['avatar']);
                        }
                        
                        $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
                        $stmt->execute([$filepath, $userId]);
                        $success = 'Аватар успешно обновлён!';
                        $user['avatar'] = $filepath;
                    } else {
                        $error = 'Ошибка загрузки файла';
                    }
                }
            }
        }
    }

    // Handle profile update (only for own profile)
    if ($isOwnProfile && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
        if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $error = 'Недействительный запрос';
        } else {
            $bio = trim($_POST['bio'] ?? '');
            $location = trim($_POST['location'] ?? '');
            
            try {
                $stmt = $pdo->prepare("UPDATE users SET bio = ?, location = ?, profile_updated = 1 WHERE id = ?");
                $stmt->execute([$bio, $location, $userId]);
                $success = 'Профиль успешно обновлён!';
                $user['profile_updated'] = 1;
            } catch (PDOException $e) {
                $error = 'Ошибка обновления профиля';
            }
        }
    }

    // Get user data
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            header('Location: logout.php');
            exit;
        }
    } catch (PDOException $e) {
        die('Database error');
    }

    $currentUserRole = 'guest';
    if (isset($_SESSION['user_id'])) {
        if (!empty($_SESSION['user_role'])) {
            $currentUserRole = $_SESSION['user_role'];
        } else {
            try {
                $roleStmt = $pdo->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
                $roleStmt->execute([$_SESSION['user_id']]);
                $currentUserRole = $roleStmt->fetchColumn() ?: 'user';
                $_SESSION['user_role'] = $currentUserRole;
            } catch (PDOException $e) {
                $currentUserRole = 'user';
            }
        }
    }

    $isElevatedUser = in_array($currentUserRole, ['admin', 'high-admin', 'owner'], true);
    $isViewingAnotherProfile = !$isOwnProfile;

    $blockConfig = [
        'owner' => ['label' => 'Владелец', 'can_block' => ['admin', 'high-admin', 'support', 'vip', 'premium', 'user'], 'cannot_block' => ['owner']],
        'high-admin' => ['label' => 'Старший админ', 'can_block' => ['admin', 'support', 'vip', 'premium', 'user'], 'cannot_block' => ['high-admin', 'owner']],
        'admin' => ['label' => 'Админ', 'can_block' => ['support', 'vip', 'premium', 'user'], 'cannot_block' => ['admin', 'high-admin', 'owner']],
    ];

    $canSeeBlockControls = $isElevatedUser && $isViewingAnotherProfile;
    $blockPermissionError = '';
    if ($canSeeBlockControls) {
        $targetRole = $user['role'] ?? 'user';
        $roleRules = $blockConfig[$currentUserRole] ?? null;
        if ($roleRules) {
            if (in_array($targetRole, $roleRules['cannot_block'], true)) {
                $canSeeBlockControls = false;
                $blockPermissionError = 'Недостаточно прав для блокировки пользователя этой роли';
            }
        } else {
            $canSeeBlockControls = false;
        }
    }

    $blockModalShouldOpen = false;
    $blockReasonDraft = '';
    $blockModeDraft = 'permanent';
    $blockDurationDraft = '7d';
    $blockCustomValueDraft = '';
    $blockCustomUnitDraft = 'days';

    if ($canSeeBlockControls && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unblock_user'])) {
        if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $blockActionMessage = 'Недействительный запрос';
        } else {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("UPDATE users SET status = 'active', blocked_at = NULL, block_reason = NULL, blocked_by = NULL, blocked_until = NULL WHERE id = ?");
                $stmt->execute([$userId]);

                $logStmt = $pdo->prepare("INSERT INTO user_block_log (user_id, blocked_by, reason, blocked_until) VALUES (?, ?, ?, NULL)");
                $logStmt->execute([$userId, $_SESSION['user_id'] ?? null, 'Разблокирован досрочно модератором']);

                $pdo->commit();
                $blockActionMessage = 'Пользователь разблокирован';
                $user['status'] = 'active';
                $user['block_reason'] = null;
                $user['blocked_until'] = null;
            } catch (PDOException $e) {
                $pdo->rollBack();
                $blockActionMessage = 'Ошибка при разблокировке пользователя';
            }
        }
    } elseif ($canSeeBlockControls && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['block_user'])) {
        if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $blockActionMessage = 'Недействительный запрос';
            $blockModalShouldOpen = true;
        } else {
            $reason = trim($_POST['block_reason'] ?? '');
            $blockReasonDraft = $reason;
            $blockModeDraft = $_POST['block_mode'] ?? 'permanent';
            $blockDurationDraft = $_POST['block_duration'] ?? '7d';
            $blockCustomValueDraft = trim($_POST['block_custom_value'] ?? '');
            $blockCustomUnitDraft = $_POST['block_custom_unit'] ?? 'days';
            if ($reason === '') {
                $blockActionMessage = 'Укажите причину блокировки';
                $blockModalShouldOpen = true;
            } else {
                $blockedUntil = null;
                if ($blockModeDraft === 'temporary') {
                    $durationMap = [
                        '12h' => '+12 hours',
                        '1d' => '+1 day',
                        '3d' => '+3 days',
                        '7d' => '+7 days',
                        '30d' => '+30 days',
                        'custom' => null
                    ];
                    if ($blockDurationDraft === 'custom') {
                        $allowedUnits = [
                            'minutes' => ['label' => 'минут', 'max' => 720],
                            'hours' => ['label' => 'часов', 'max' => 168],
                            'days' => ['label' => 'дней', 'max' => 365],
                            'weeks' => ['label' => 'недель', 'max' => 52],
                            'months' => ['label' => 'месяцев', 'max' => 12]
                        ];
                        $unitKey = array_key_exists($blockCustomUnitDraft, $allowedUnits) ? $blockCustomUnitDraft : 'days';
                        $valueInt = (int)$blockCustomValueDraft;
                        $maxAllowed = $allowedUnits[$unitKey]['max'];
                        if ($valueInt <= 0 || $valueInt > $maxAllowed) {
                            $blockActionMessage = 'Укажите корректную длительность блокировки';
                            $blockModalShouldOpen = true;
                        } else {
                            $selectedInterval = "+{$valueInt} {$unitKey}";
                        }
                    } else {
                        $selectedInterval = $durationMap[$blockDurationDraft] ?? '+7 days';
                    }
                    if (!empty($selectedInterval) && !$blockModalShouldOpen) {
                        $blockedUntil = (new DateTime('now', new DateTimeZone('UTC')))->modify($selectedInterval)->format('Y-m-d H:i:s');
                    }
                }

                if (!$blockModalShouldOpen) {
                    try {
                        $pdo->beginTransaction();

                        $stmt = $pdo->prepare("UPDATE users SET status = 'blocked', blocked_at = NOW(), block_reason = ?, blocked_by = ?, blocked_until = ? WHERE id = ?");
                        $stmt->execute([$reason, $_SESSION['user_id'] ?? null, $blockedUntil, $userId]);

                        $statement = $pdo->prepare("INSERT INTO user_block_log (user_id, blocked_by, reason, blocked_until) VALUES (?, ?, ?, ?)");
                        $statement->execute([$userId, $_SESSION['user_id'] ?? null, $reason, $blockedUntil]);

                        $pdo->commit();
                        $blockActionMessage = 'Пользователь успешно заблокирован';
                        $user['status'] = 'blocked';
                        $user['block_reason'] = $reason;
                        $user['blocked_until'] = $blockedUntil;
                    } catch (PDOException $e) {
                        $pdo->rollBack();
                        $blockActionMessage = 'Ошибка при блокировке пользователя';
                        $blockModalShouldOpen = true;
                    }
                }
            }
        }
    }

    if ($blockActionMessage && str_contains(mb_strtolower($blockActionMessage), 'успеш')) {
        $blockModalShouldOpen = false;
    }

    $showSelfBlockModal = false;
    $selfBlockMeta = [];
    if ($isOwnProfile && ($user['status'] ?? '') === 'blocked') {
        $showSelfBlockModal = true;
        $reasonText = trim($user['block_reason'] ?? '') ?: 'Причина не указана';
        if (!empty($user['blocked_until'])) {
            $blockedUntilDate = new DateTime($user['blocked_until'], new DateTimeZone('UTC'));
            $selfBlockMeta['duration'] = 'До ' . $blockedUntilDate->format('d.m.Y H:i') . ' (UTC)';
        } else {
            $selfBlockMeta['duration'] = 'На неопределённый срок';
        }
        $selfBlockMeta['reason'] = $reasonText;
        $selfBlockMeta['blocked_at'] = !empty($user['blocked_at']) ? (new DateTime($user['blocked_at']))->format('d.m.Y H:i') : null;
    }

    $blockHistory = [];
    if ($canSeeBlockControls) {
        try {
            $historyStmt = $pdo->prepare("SELECT lbl.*, blocker.username AS blocker_username FROM user_block_log lbl LEFT JOIN users blocker ON lbl.blocked_by = blocker.id WHERE lbl.user_id = ? ORDER BY lbl.created_at DESC LIMIT 25");
            $historyStmt->execute([$userId]);
            $blockHistory = $historyStmt->fetchAll();
        } catch (PDOException $e) {
            $blockHistory = [];
        }
    }

    // Get user statistics
    $stats = ['builds' => 0, 'public_builds' => 0, 'likes_received' => 0, 'orders' => 0];

    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM user_builds WHERE user_id = ?");
        $stmt->execute([$userId]);
        $stats['builds'] = $stmt->fetch()['count'];
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM user_builds WHERE user_id = ? AND is_public = 1");
        $stmt->execute([$userId]);
        $stats['public_builds'] = $stmt->fetch()['count'];
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM build_likes bl JOIN user_builds ub ON bl.build_id = ub.id WHERE ub.user_id = ?");
        $stmt->execute([$userId]);
        $stats['likes_received'] = $stmt->fetch()['count'];
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ?");
        $stmt->execute([$userId]);
        $stats['orders'] = $stmt->fetch()['count'];
    } catch (PDOException $e) {}

    // Get user's recent builds
    $recentBuilds = [];
    try {
        $stmt = $pdo->prepare("SELECT b.*, COUNT(DISTINCT bl.user_id) as likes_count, COUNT(DISTINCT bc.id) as comments_count FROM user_builds b LEFT JOIN build_likes bl ON b.id = bl.build_id LEFT JOIN build_comments bc ON b.id = bc.build_id WHERE b.user_id = ? GROUP BY b.id ORDER BY b.created_at DESC LIMIT 6");
        $stmt->execute([$userId]);
        $recentBuilds = $stmt->fetchAll();
    } catch (PDOException $e) {}

    // Get user's published reviews
    $userReviews = [];
    try {
        $componentsUnionSql = getComponentsUnionSource();
        $stmt = $pdo->prepare("SELECT cr.id, cr.title, cr.rating, cr.recommended, cr.component_name,
                cr.created_at, cat.name AS category_name, cat.icon AS category_icon,
                comp.name AS union_component_name, comp.manufacturer
            FROM component_reviews cr
            LEFT JOIN categories cat ON cat.id = cr.component_category_id
            LEFT JOIN ({$componentsUnionSql}) comp ON comp.id = cr.component_id AND comp.category_id = cr.component_category_id
            WHERE cr.user_id = ? AND cr.status = 'published'
            ORDER BY cr.created_at DESC
            LIMIT 6");
        $stmt->execute([$userId]);
        $userReviews = $stmt->fetchAll();
    } catch (PDOException $e) {
        $userReviews = [];
    }

    $buildPurposeLabels = [
        'gaming' => 'Игровая',
        'work' => 'Рабочая',
        'streaming' => 'Для стриминга',
        'editing' => 'Для монтажа',
        'other' => 'Другая'
    ];

    // Profile highlight cards
    $highlightCards = [
        [
            'label' => 'Всего сборок',
            'value' => number_format($stats['builds']),
            'icon' => 'fa-microchip',
            'meta' => $stats['public_builds'] . ' публичных'
        ],
        [
            'label' => 'Лайков собрано',
            'value' => number_format($stats['likes_received']),
            'icon' => 'fa-heart',
            'meta' => $stats['likes_received'] ? 'сообщество ценит' : 'ещё всё впереди'
        ],
        [
            'label' => 'Заказов',
            'value' => number_format($stats['orders']),
            'icon' => 'fa-box',
            'meta' => $stats['orders'] ? 'оформлено заказов' : 'пока без заказов'
        ]
    ];

    // Achievement badges
    $achievementDefinitions = [
        [
            'key' => 'rookie-builder',
            'title' => 'Первый запуск',
            'description' => 'Создана первая сборка',
            'icon' => 'fa-rocket',
            'goal' => 1,
            'current' => $stats['builds']
        ],
        [
            'key' => 'community-star',
            'title' => 'Любимец сообщества',
            'description' => '50 лайков на сборках',
            'icon' => 'fa-star',
            'goal' => 50,
            'current' => $stats['likes_received']
        ],
        [
            'key' => 'public-architect',
            'title' => 'Витрина идей',
            'description' => '5 публичных сборок',
            'icon' => 'fa-display',
            'goal' => 5,
            'current' => $stats['public_builds']
        ],
        [
            'key' => 'trusted-client',
            'title' => 'Надёжный заказчик',
            'description' => '3 заказа в магазине',
            'icon' => 'fa-handshake',
            'goal' => 3,
            'current' => $stats['orders']
        ]
    ];

    $achievements = array_map(function ($item) {
        $progress = $item['goal'] > 0 ? min(100, (int)round(($item['current'] / $item['goal']) * 100)) : 0;
        $item['unlocked'] = $item['current'] >= $item['goal'];
        $item['progress'] = $progress;
        return $item;
    }, $achievementDefinitions);

    $builderScore = min(100, (int)ceil(
        ($stats['builds'] * 12) +
        ($stats['public_builds'] * 6) +
        ($stats['likes_received'] * 1.5) +
        ($stats['orders'] * 3)
    ));

    $builderTier = 'Newcomer';
    if ($builderScore >= 85) {
        $builderTier = 'Legend';
    } elseif ($builderScore >= 65) {
        $builderTier = 'Pro Builder';
    } elseif ($builderScore >= 45) {
        $builderTier = 'Enthusiast';
    }

    $avgLikes = $stats['builds'] > 0 ? round($stats['likes_received'] / max(1, $stats['builds']), 1) : 0;
    $publicShare = $stats['builds'] > 0 ? round(($stats['public_builds'] / max(1, $stats['builds'])) * 100) : 0;

    $recentOrders = [];
    if ($isOwnProfile) {
        try {
            $stmt = $pdo->prepare("SELECT id, total_price, status, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 3");
            $stmt->execute([$userId]);
            $recentOrders = $stmt->fetchAll();
        } catch (PDOException $e) {}
    }

    $activityTimeline = [];
    foreach ($recentBuilds as $build) {
        $activityTimeline[] = [
            'type' => 'build',
            'title' => $build['build_name'],
            'description' => $build['likes_count'] . ' лайков · ' . $build['comments_count'] . ' комментариев',
            'meta' => formatPrice($build['total_price']),
            'date' => $build['created_at'] ?? date('c'),
            'icon' => 'fa-microchip',
            'link' => 'build-details.php?id=' . $build['id']
        ];
    }

    if ($isOwnProfile && !empty($recentOrders)) {
        $statusLabels = [
            'pending' => 'Ожидает',
            'confirmed' => 'Подтверждён',
            'completed' => 'Выполнен',
            'cancelled' => 'Отменён'
        ];

        foreach ($recentOrders as $order) {
            $activityTimeline[] = [
                'type' => 'order',
                'title' => 'Заказ #' . $order['id'],
                'description' => 'Статус: ' . ($statusLabels[$order['status']] ?? ucfirst($order['status'])),
                'meta' => formatPrice($order['total_price']),
                'date' => $order['created_at'] ?? date('c'),
                'icon' => 'fa-store',
                'link' => 'orders.php#order-' . $order['id']
            ];
        }
    }

    usort($activityTimeline, function ($a, $b) {
        return strtotime($b['date']) <=> strtotime($a['date']);
    });
    $activityTimeline = array_slice($activityTimeline, 0, 6);
}

$csrfToken = Security::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мой профиль - <?= SITE_NAME ?></title>
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/profile.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tarekraafat/autocomplete.js@10.2.7/dist/css/autoComplete.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <?php if ($userNotFound): ?>
    <main class="profile-page">
        <div class="container">
            <div class="profile-not-found">
                <div class="not-found-icon">
                    <i class="fas fa-user-slash"></i>
                </div>
                <h1>Пользователь не найден</h1>
                <p>Мы не смогли найти профиль с именем <strong><?= htmlspecialchars($requestedUsername) ?></strong>. Проверьте правильность ника или вернитесь на главную.</p>
                <div class="not-found-actions">
                    <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> На главную</a>
                    <a href="builds.php" class="btn btn-primary"><i class="fas fa-layer-group"></i> Галерея сборок</a>
                </div>
            </div>
        </div>
    </main>
    <?php else: ?>
    <main class="profile-page">
        <div class="container">
            <div class="profile-container">
                <div class="profile-header">
                    <div class="profile-info">
                        <div class="profile-avatar-wrapper">
                            <div class="profile-avatar">
                                <?php if (!empty($user['avatar']) && file_exists($user['avatar'])): ?>
                                    <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="Avatar">
                                <?php else: ?>
                                    <i class="fas fa-user-circle"></i>
                                <?php endif; ?>
                            </div>
                            <?php if ($isOwnProfile): ?>
                                <button class="avatar-upload-btn" onclick="document.getElementById('avatarInput').click()">
                                    <i class="fas fa-camera"></i>
                                </button>
                                <form id="avatarForm" method="POST" enctype="multipart/form-data" style="display: none;">
                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                    <input type="file" id="avatarInput" name="avatar" accept="image/*" onchange="document.getElementById('avatarForm').submit()">
                                    <input type="hidden" name="upload_avatar" value="1">
                                </form>
                            <?php endif; ?>
                        </div>
                        <div class="profile-details">
                            <div class="profile-name">
                                <?= htmlspecialchars($user['username']) ?>
                                <?php
                                $roleLabels = [
                                    'premium' => ['label' => 'Premium', 'icon' => 'fa-crown'],
                                    'vip' => ['label' => 'VIP', 'icon' => 'fa-gem'],
                                    'support' => ['label' => 'Support', 'icon' => 'fa-headset'],
                                    'admin' => ['label' => 'Admin', 'icon' => 'fa-shield'],
                                    'high-admin' => ['label' => 'High Admin', 'icon' => 'fa-shield-halved'],
                                    'owner' => ['label' => 'Owner', 'icon' => 'fa-crown']
                                ];
                                
                                $userRole = $user['role'] ?? 'user';
                                if (isset($roleLabels[$userRole])):
                                ?>
                                    <span class="role-badge role-<?= $userRole ?>">
                                        <i class="fas <?= $roleLabels[$userRole]['icon'] ?>"></i>
                                        <?= $roleLabels[$userRole]['label'] ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="profile-email">
                                <i class="fas fa-envelope"></i> <?= htmlspecialchars($user['email']) ?>
                            </div>
                            <div class="profile-joined">
                                <i class="fas fa-calendar"></i> На сайте с <?= date('d.m.Y', strtotime($user['created_at'])) ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="profile-content">
                    <div class="profile-sidebar">
                        <div class="sidebar-card">
                            <h3 class="card-title"><i class="fas fa-chart-line"></i> Статистика</h3>
                            <div class="stats-grid">
                                <div class="stat-item">
                                    <div class="stat-value"><?= $stats['builds'] ?></div>
                                    <div class="stat-label">Сборок</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value"><?= $stats['public_builds'] ?></div>
                                    <div class="stat-label">Публичных</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value"><?= $stats['likes_received'] ?></div>
                                    <div class="stat-label">Лайков</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value"><?= $stats['orders'] ?></div>
                                    <div class="stat-label">Заказов</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="profile-main">
                        <?php if ($showSelfBlockModal): ?>
                            <div class="blocked-self-overlay" aria-hidden="false">
                                <div class="blocked-self-modal">
                                    <div class="blocked-icon">
                                        <i class="fas fa-shield-alt"></i>
                                    </div>
                                    <h2>Аккаунт временно недоступен</h2>
                                    <p class="blocked-subtitle">Ваш профиль заблокирован модератором. Вы не можете пользоваться сервисом, пока блокировка не закончится.</p>
                                    <div class="blocked-meta">
                                        <div>
                                            <span>Статус</span>
                                            <strong><?= htmlspecialchars($selfBlockMeta['duration']) ?></strong>
                                        </div>
                                        <?php if (!empty($selfBlockMeta['blocked_at'])): ?>
                                        <div>
                                            <span>Дата блокировки</span>
                                            <strong><?= htmlspecialchars($selfBlockMeta['blocked_at']) ?></strong>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="blocked-reason">
                                        <span>Причина</span>
                                        <p><?= nl2br(htmlspecialchars($selfBlockMeta['reason'])) ?></p>
                                    </div>
                                    <div class="blocked-note">
                                        Обратитесь в поддержку, если считаете блокировку ошибочной.
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($canSeeBlockControls && !empty($blockHistory)): ?>
                            <div class="block-modal-backdrop" id="historyModal" aria-hidden="true">
                                <div class="block-modal history-modal" role="dialog" aria-modal="true" aria-labelledby="historyModalTitle">
                                    <button type="button" class="block-modal-close js-close-history-modal" aria-label="Закрыть">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <div class="block-modal-icon">
                                        <i class="fas fa-clock-rotate-left"></i>
                                    </div>
                                    <h3 id="historyModalTitle">История блокировок</h3>
                                    <p class="block-modal-subtitle">Журнал всех действий модераторов по этому аккаунту.</p>

                                    <div class="block-history-list modal-list">
                                        <?php foreach ($blockHistory as $entry): ?>
                                            <div class="block-history-item">
                                                <div>
                                                    <strong><?= htmlspecialchars($entry['reason']) ?></strong>
                                                    <span class="block-history-meta"><?= date('d.m.Y H:i', strtotime($entry['created_at'])) ?></span>
                                                </div>
                                                <div class="block-history-info">
                                                    <span>Кем: <?= htmlspecialchars($entry['blocker_username'] ?? 'Система') ?></span>
                                                    <?php if (!empty($entry['blocked_until'])): ?>
                                                        <span>До: <?= date('d.m.Y H:i', strtotime($entry['blocked_until'])) ?> (UTC)</span>
                                                    <?php else: ?>
                                                        <span>Перманентно</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div>
                        <?php endif; ?>

                        <?php if ($blockPermissionError): ?>
                            <div class="admin-alert admin-alert-warning">
                                <i class="fas fa-shield"></i> <?= $blockPermissionError ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($canSeeBlockControls): ?>
                            <div class="admin-tools-card">
                                <div class="admin-tools-header">
                                    <div>
                                        <h2><i class="fas fa-user-shield"></i> Управление пользователем</h2>
                                        <p>Вы вошли как <?= htmlspecialchars($currentUserRole) ?>. Действия видны только модераторам.</p>
                                    </div>
                                    <span class="user-status-badge status-<?= htmlspecialchars($user['status'] ?? 'active') ?>">
                                        <?= ($user['status'] ?? 'active') === 'blocked' ? 'Заблокирован' : 'Активен' ?>
                                    </span>
                                </div>

                                <?php if ($blockActionMessage): ?>
                                    <?php $isBlockSuccess = str_contains(mb_strtolower($blockActionMessage), 'успеш'); ?>
                                    <div class="admin-alert <?= $isBlockSuccess ? 'admin-alert-success' : 'admin-alert-error' ?>">
                                        <i class="fas <?= $isBlockSuccess ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i>
                                        <?= $blockActionMessage ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (($user['status'] ?? 'active') === 'blocked'): ?>
                                    <div class="blocked-info">
                                        <p><strong>Причина:</strong> <?= htmlspecialchars($user['block_reason'] ?? '—') ?></p>
                                        <?php if (!empty($user['blocked_at'])): ?>
                                            <p><strong>Дата блокировки:</strong> <?= date('d.m.Y H:i', strtotime($user['blocked_at'])) ?></p>
                                        <?php endif; ?>
                                        <?php if (!empty($user['blocked_until'])): ?>
                                            <p><strong>Разблокируется автоматически:</strong> <?= date('d.m.Y H:i', strtotime($user['blocked_until'])) ?> (UTC)</p>
                                        <?php else: ?>
                                            <p><strong>Тип:</strong> Перманентная блокировка</p>
                                        <?php endif; ?>
                                        <p>Чтобы разблокировать раньше времени, используйте кнопку ниже или обновите статус пользователя напрямую.</p>
                                        <form method="POST" class="unblock-form">
                                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                            <button type="submit" name="unblock_user" class="btn-unblock">
                                                <i class="fas fa-unlock"></i> Разблокировать
                                            </button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <div class="admin-tools-row">
                                        <div>
                                            <p class="admin-hint">Блокировка не удаляет данные. Пользователь не сможет войти, пока статус не изменён.</p>
                                        </div>
                                        <button type="button" class="btn-block-primary js-open-block-modal">
                                            <i class="fas fa-user-slash"></i> Заблокировать
                                        </button>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($blockHistory)): ?>
                                    <button type="button" class="btn-history js-open-history-modal">
                                        <i class="fas fa-clock-rotate-left"></i> История блокировок
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($canSeeBlockControls && ($user['status'] ?? 'active') !== 'blocked'): ?>
                            <div class="block-modal-backdrop <?= $blockModalShouldOpen ? 'is-visible' : '' ?>" id="blockModal" aria-hidden="<?= $blockModalShouldOpen ? 'false' : 'true' ?>">
                                <div class="block-modal" role="dialog" aria-modal="true" aria-labelledby="blockModalTitle">
                                    <button type="button" class="block-modal-close js-close-block-modal" aria-label="Закрыть">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <div class="block-modal-icon">
                                        <i class="fas fa-shield-alt"></i>
                                    </div>
                                    <h3 id="blockModalTitle">Блокировка пользователя</h3>
                                    <p class="block-modal-subtitle">Укажите причину блокировки — она появится в профиле пользователя.</p>

                                    <form method="POST" class="block-modal-form">
                                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                        <input type="hidden" name="block_user" value="1">

                                        <div class="block-field">
                                            <label for="block_reason_modal">Причина блокировки</label>
                                            <textarea id="block_reason_modal" name="block_reason" minlength="10" maxlength="255" required placeholder="Например: Многократные нарушения правил сообщества"><?= htmlspecialchars($blockReasonDraft) ?></textarea>
                                            <small>Опишите нарушение максимально конкретно — текст покажется пользователю в профиле.</small>
                                        </div>

                                        <div class="block-field">
                                            <span class="block-field-label">Тип блокировки</span>
                                            <div class="block-radio-group">
                                                <label class="block-radio">
                                                    <input type="radio" name="block_mode" value="permanent" <?= $blockModeDraft === 'permanent' ? 'checked' : '' ?>>
                                                    <span>
                                                        <strong>Перманентная</strong>
                                                        <em>До ручного разблокирования</em>
                                                    </span>
                                                </label>
                                                <label class="block-radio">
                                                    <input type="radio" name="block_mode" value="temporary" <?= $blockModeDraft === 'temporary' ? 'checked' : '' ?>>
                                                    <span>
                                                        <strong>Временная</strong>
                                                        <em>Автоматически снимается после срока</em>
                                                    </span>
                                                </label>
                                            </div>
                                        </div>

                                        <div class="block-field block-duration-row" data-visible="<?= $blockModeDraft === 'temporary' ? 'true' : 'false' ?>">
                                            <label for="block_duration">Длительность</label>
                                            <div class="block-select-wrapper">
                                                <select id="block_duration" name="block_duration">
                                                    <option value="12h" <?= $blockDurationDraft === '12h' ? 'selected' : '' ?>>12 часов</option>
                                                    <option value="1d" <?= $blockDurationDraft === '1d' ? 'selected' : '' ?>>1 день</option>
                                                    <option value="3d" <?= $blockDurationDraft === '3d' ? 'selected' : '' ?>>3 дня</option>
                                                    <option value="7d" <?= $blockDurationDraft === '7d' ? 'selected' : '' ?>>7 дней</option>
                                                    <option value="30d" <?= $blockDurationDraft === '30d' ? 'selected' : '' ?>>30 дней</option>
                                                    <option value="custom" <?= $blockDurationDraft === 'custom' ? 'selected' : '' ?>>Свой срок</option>
                                                </select>
                                                <small>Срок отсчитывается от момента нажатия кнопки «Подтвердить».</small>
                                            </div>
                                            <div class="block-custom-duration" data-visible="<?= $blockDurationDraft === 'custom' ? 'true' : 'false' ?>">
                                                <label for="block_custom_value">Количество</label>
                                                <div class="block-custom-row">
                                                    <input type="number" id="block_custom_value" name="block_custom_value" min="1" max="365" value="<?= htmlspecialchars($blockCustomValueDraft) ?>" placeholder="Например, 5">
                                                    <select name="block_custom_unit">
                                                        <option value="minutes" <?= $blockCustomUnitDraft === 'minutes' ? 'selected' : '' ?>>минут</option>
                                                        <option value="hours" <?= $blockCustomUnitDraft === 'hours' ? 'selected' : '' ?>>часов</option>
                                                        <option value="days" <?= $blockCustomUnitDraft === 'days' ? 'selected' : '' ?>>дней</option>
                                                        <option value="weeks" <?= $blockCustomUnitDraft === 'weeks' ? 'selected' : '' ?>>недель</option>
                                                        <option value="months" <?= $blockCustomUnitDraft === 'months' ? 'selected' : '' ?>>месяцев</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="block-modal-actions">
                                            <button type="button" class="btn-secondary-outline js-close-block-modal">Отмена</button>
                                            <button type="submit" class="btn-block-user">
                                                <i class="fas fa-user-slash"></i> Подтвердить
                                            </button>
                                        </div>
                                        <p class="block-modal-hint">После подтверждения пользователь будет моментально отключён от всех сессий.</p>
                                    </form>
                                </div>
                            </div>
                            <script>
                                (function() {
                                    const backdrop = document.getElementById('blockModal');
                                    if (!backdrop) return;
                                    const openBtn = document.querySelector('.js-open-block-modal');
                                    const closeButtons = backdrop.querySelectorAll('.js-close-block-modal, .block-modal-close');
                                    const textarea = backdrop.querySelector('#block_reason_modal');
                                    const durationRow = backdrop.querySelector('.block-duration-row');
                                    const customRow = backdrop.querySelector('.block-custom-duration');
                                    const modeRadios = backdrop.querySelectorAll('input[name="block_mode"]');
                                    const durationSelect = backdrop.querySelector('#block_duration');

                                    const syncDurationVisibility = () => {
                                        const isTemporary = backdrop.querySelector('input[name="block_mode"]:checked')?.value === 'temporary';
                                        durationRow?.setAttribute('data-visible', isTemporary ? 'true' : 'false');
                                        if (!isTemporary) {
                                            customRow?.setAttribute('data-visible', 'false');
                                        } else if (durationSelect?.value === 'custom') {
                                            customRow?.setAttribute('data-visible', 'true');
                                        } else {
                                            customRow?.setAttribute('data-visible', 'false');
                                        }
                                    };

                                    modeRadios.forEach(radio => radio.addEventListener('change', syncDurationVisibility));
                                    durationSelect?.addEventListener('change', syncDurationVisibility);
                                    syncDurationVisibility();

                                    const openModal = () => {
                                        backdrop.classList.add('is-visible');
                                        backdrop.setAttribute('aria-hidden', 'false');
                                        setTimeout(() => textarea?.focus(), 120);
                                    };

                                    const closeModal = () => {
                                        backdrop.classList.remove('is-visible');
                                        backdrop.setAttribute('aria-hidden', 'true');
                                    };

                                    openBtn?.addEventListener('click', openModal);
                                    closeButtons.forEach(btn => btn.addEventListener('click', closeModal));
                                    backdrop.addEventListener('click', (e) => {
                                        if (e.target === backdrop) {
                                            closeModal();
                                        }
                                    });
                                    document.addEventListener('keydown', (e) => {
                                    if (e.key === 'Escape') {
                                            closeModal();
                                        }
                                    });

                                    if (<?= $blockModalShouldOpen ? 'true' : 'false' ?>) {
                                        openModal();
                                    }
                                })();
                            </script>
                        <?php endif; ?>

                        <?php if ($canSeeBlockControls && !empty($blockHistory)): ?>
                            <script>
                                (function() {
                                    const historyModal = document.getElementById('historyModal');
                                    if (!historyModal) return;
                                    const openButtons = document.querySelectorAll('.js-open-history-modal');
                                    const closeButton = historyModal.querySelector('.js-close-history-modal');

                                    const openHistory = () => {
                                        historyModal.classList.add('is-visible');
                                        historyModal.setAttribute('aria-hidden', 'false');
                                    };

                                    const closeHistory = () => {
                                        historyModal.classList.remove('is-visible');
                                        historyModal.setAttribute('aria-hidden', 'true');
                                    };

                                    openButtons.forEach(btn => btn.addEventListener('click', openHistory));
                                    closeButton?.addEventListener('click', closeHistory);
                                    historyModal.addEventListener('click', (e) => {
                                        if (e.target === historyModal) {
                                            closeHistory();
                                        }
                                    });
                                    document.addEventListener('keydown', (e) => {
                                        if (e.key === 'Escape') {
                                            closeHistory();
                                        }
                                    });
                                })();
                            </script>
                        <?php endif; ?>

                        <?php if ($isOwnProfile): ?>
                        <div class="main-card">
                            <div class="section-header">
                                <h2 class="section-title"><i class="fas fa-user-edit"></i> Редактировать профиль</h2>
                                <?php if (!empty($user['profile_updated'])): ?>
                                    <button class="btn-toggle-edit" onclick="toggleEditForm()">
                                        <i class="fas fa-edit"></i> Редактировать
                                    </button>
                                <?php endif; ?>
                            </div>

                            <div id="editForm" class="edit-form-container" style="<?= empty($user['profile_updated']) ? '' : 'display: none;' ?>">
                                <form method="POST" action="">
                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                    
                                    <div class="form-group">
                                        <label><i class="fas fa-quote-left"></i> О себе</label>
                                        <textarea name="bio" placeholder="Расскажите о себе..." maxlength="500"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                                        <small style="color: var(--text-secondary); font-size: 12px;">Максимум 500 символов</small>
                                    </div>

                                    <div class="form-group">
                                        <label><i class="fas fa-location-dot"></i> Местоположение</label>
                                        <input type="text" id="locationInput" name="location" placeholder="Начните вводить город..." value="<?= htmlspecialchars($user['location'] ?? '') ?>" autocomplete="off">
                                    </div>

                                    <button type="submit" name="update_profile" class="btn-save">
                                        <i class="fas fa-save"></i> Сохранить изменения
                                    </button>
                                </form>
                            </div>

                            <?php if (!empty($user['profile_updated']) && (!empty($user['bio']) || !empty($user['location']))): ?>
                                <div id="profileView" class="profile-view">
                                    <?php if (!empty($user['bio'])): ?>
                                        <div class="profile-field">
                                            <div class="field-label"><i class="fas fa-quote-left"></i> О себе</div>
                                            <div class="field-value"><?= nl2br(htmlspecialchars($user['bio'])) ?></div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($user['location'])): ?>
                                        <div class="profile-field">
                                            <div class="field-label"><i class="fas fa-location-dot"></i> Местоположение</div>
                                            <div class="field-value"><?= htmlspecialchars($user['location']) ?></div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                            <?php if (!empty($user['bio']) || !empty($user['location'])): ?>
                            <div class="main-card">
                                <h2 class="section-title"><i class="fas fa-user"></i> О пользователе</h2>
                                <div class="profile-view">
                                    <?php if (!empty($user['bio'])): ?>
                                        <div class="profile-field">
                                            <div class="field-label"><i class="fas fa-quote-left"></i> О себе</div>
                                            <div class="field-value"><?= nl2br(htmlspecialchars($user['bio'])) ?></div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($user['location'])): ?>
                                        <div class="profile-field">
                                            <div class="field-label"><i class="fas fa-location-dot"></i> Местоположение</div>
                                            <div class="field-value"><?= htmlspecialchars($user['location']) ?></div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <div class="main-card" id="my-builds">
                            <div class="section-header">
                                <h2 class="section-title"><i class="fas fa-folder"></i> <?= $isOwnProfile ? 'Мои сборки' : 'Сборки пользователя' ?></h2>
                                <?php if ($isOwnProfile): ?>
                                    <a class="btn-outline-link" href="builds.php"><i class="fas fa-layer-group"></i> Галерея сборок</a>
                                <?php endif; ?>
                            </div>
                            
                            <?php $hasBuildSlider = count($recentBuilds) > 2; ?>
                            <?php if (empty($recentBuilds)): ?>
                                <div class="profile-builds-empty">
                                    <i class="fas fa-folder-open"></i>
                                    <p><?= $isOwnProfile ? 'У вас пока нет сохранённых сборок' : 'Пользователь ещё не публиковал сборки' ?></p>
                                    <?php if ($isOwnProfile): ?>
                                        <a href="builder.php" class="btn btn-primary"><i class="fas fa-screwdriver-wrench"></i> Создать сборку</a>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <?php
                                    $cardsPerPage = 2;
                                    ob_start();
                                    foreach ($recentBuilds as $index => $build):
                                        $pageIndex = (int)floor($index / $cardsPerPage);
                                        $positionInPage = $index % $cardsPerPage;
                                        $cardSide = $positionInPage === 0 ? 'left' : 'right';
                                        $componentsData = json_decode($build['components'], true) ?? [];
                                        $specTargets = ['cpu' => 'Процессор', 'gpu' => 'Видеокарта', 'ram' => 'ОЗУ'];
                                        $specValues = [];
                                        foreach ($specTargets as $key => $label) {
                                            $value = '';
                                            if (isset($componentsData[$key]) && is_string($componentsData[$key])) {
                                                $value = $componentsData[$key];
                                            } else {
                                                foreach ($componentsData as $itemKey => $item) {
                                                    if (!is_array($item)) continue;
                                                    $slug = strtolower($item['slug'] ?? $itemKey);
                                                    if (strpos($slug, $key) !== false) {
                                                        $value = $item['name'] ?? $item['title'] ?? '';
                                                        break;
                                                    }
                                                }
                                            }
                                            if ($value) {
                                                $specValues[$key] = $value;
                                            }
                                        }
                                        $buildPurposeKey = strtolower($build['purpose'] ?? '');
                                ?>
                                        <article class="profile-build-card" data-build-page="<?= $pageIndex ?>" data-card-side="<?= $cardSide ?>">
                                            <div class="profile-build-card__top">
                                                <div>
                                                    <div class="build-card-title">
                                                        <i class="fas fa-desktop"></i>
                                                        <span><?= htmlspecialchars($build['build_name']) ?></span>
                                                    </div>
                                                    <div class="build-card-meta">
                                                        <span><i class="fas fa-clock"></i> <?= date('d.m.Y', strtotime($build['created_at'] ?? 'now')) ?></span>
                                                        <?php if (!empty($buildPurposeKey) && isset($buildPurposeLabels[$buildPurposeKey])): ?>
                                                            <span class="build-purpose-pill purpose-<?= htmlspecialchars($buildPurposeKey) ?>">
                                                                <?= $buildPurposeLabels[$buildPurposeKey] ?>
                                                            </span>
                                                        <?php endif; ?>
                                                        <?php if ($build['is_public']): ?>
                                                            <span class="build-badge public"><i class="fas fa-globe"></i> Публичная</span>
                                                        <?php else: ?>
                                                            <span class="build-badge private"><i class="fas fa-lock"></i> Приватная</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <a class="build-card-link" href="build-details.php?id=<?= $build['id'] ?>" title="Подробнее">
                                                    <i class="fas fa-arrow-right"></i>
                                                </a>
                                            </div>

                                            <?php if (!empty($specValues)): ?>
                                            <div class="profile-build-card__specs">
                                                <?php foreach ($specTargets as $specKey => $specLabel): ?>
                                                    <?php if (!empty($specValues[$specKey])): ?>
                                                        <div class="build-spec-row">
                                                            <div class="build-spec-icon">
                                                                <?php if ($specKey === 'cpu'): ?>
                                                                    <i class="fas fa-microchip"></i>
                                                                <?php elseif ($specKey === 'gpu'): ?>
                                                                    <i class="fas fa-display"></i>
                                                                <?php else: ?>
                                                                    <i class="fas fa-memory"></i>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div>
                                                                <div class="build-spec-label"><?= $specLabel ?></div>
                                                                <div class="build-spec-value"><?= htmlspecialchars($specValues[$specKey]) ?></div>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </div>
                                            <?php endif; ?>

                                            <div class="profile-build-card__footer">
                                                <div>
                                                    <div class="build-price-label">Итого</div>
                                                    <div class="build-price-value"><?= formatPrice($build['total_price']) ?></div>
                                                </div>
                                                <div class="build-footer-stats">
                                                    <span><i class="fas fa-heart"></i> <?= $build['likes_count'] ?></span>
                                                    <span><i class="fas fa-comment"></i> <?= $build['comments_count'] ?></span>
                                                </div>
                                            </div>
                                        </article>
                                <?php endforeach; $buildCardsHtml = ob_get_clean(); ?>

                                <?php if ($hasBuildSlider): ?>
                                    <div class="profile-builds-slider">
                                        <div class="profile-builds-window">
                                            <div class="profile-builds-grid has-slider" data-build-slider="true">
                                                <?= $buildCardsHtml ?>
                                            </div>
                                        </div>
                                        <?php $totalPages = (int)ceil(count($recentBuilds) / 2); ?>
                                        <div class="profile-builds-pagination" data-builds-slider aria-label="Переключение сборок">
                                            <button type="button" class="builds-nav-btn" data-nav="prev" aria-label="Предыдущие сборки"><i class="fas fa-arrow-left"></i></button>
                                            <div class="builds-dots" role="tablist">
                                                <?php for ($i = 0; $i < $totalPages; $i++): ?>
                                                    <button type="button" class="builds-page-dot" data-page-index="<?= $i ?>" aria-label="Страница <?= $i + 1 ?>" role="tab"></button>
                                                <?php endfor; ?>
                                            </div>
                        						<button type="button" class="builds-nav-btn" data-nav="next" aria-label="Следующие сборки"><i class="fas fa-arrow-right"></i></button>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="profile-builds-grid">
                                        <?= $buildCardsHtml ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($userReviews)): ?>
                        <div class="main-card" id="user-reviews">
                            <div class="section-header">
                                <h2 class="section-title"><i class="fas fa-pen"></i> <?= $isOwnProfile ? 'Мои обзоры' : 'Обзоры пользователя' ?></h2>
                                <a class="btn-outline-link" href="reviews.php"><i class="fas fa-comments"></i> Все обзоры</a>
                            </div>

                            <?php
                                $reviewsPerPage = 2;
                                $hasReviewSlider = count($userReviews) > $reviewsPerPage;
                                ob_start();
                                foreach ($userReviews as $index => $review):
                                    $componentDisplayName = $review['component_name'] ?: ($review['union_component_name'] ?? 'Компонент');
                                    $manufacturer = $review['manufacturer'] ?? '';
                                    $categoryName = $review['category_name'] ?? '';
                                    $categoryIcon = $review['category_icon'] ?? 'fa-layer-group';
                                    $reviewPageIndex = (int)floor($index / $reviewsPerPage);
                            ?>
                                <a class="profile-review-card" href="reviews.php#review-<?= (int)$review['id'] ?>" data-review-page="<?= $reviewPageIndex ?>">
                                    <header class="profile-review-card__header">
                                            <div>
                                                <h3><?= htmlspecialchars($review['title']) ?></h3>
                                                <div class="profile-review-meta">
                                                    <span class="review-rating-pill"><i class="fas fa-star"></i> <?= number_format((float)$review['rating'], 1) ?>/5</span>
                                                    <?php if ($categoryName): ?>
                                                        <span class="review-category-pill"><i class="fas <?= htmlspecialchars($categoryIcon) ?>"></i> <?= htmlspecialchars($categoryName) ?></span>
                                                    <?php endif; ?>
                                                    <span class="review-date"><?= date('d.m.Y', strtotime($review['created_at'] ?? 'now')) ?></span>
                                                </div>
                                            </div>
                                            <span class="review-recommendation <?= (int)$review['recommended'] === 1 ? 'positive' : 'neutral' ?>">
                                                <i class="fas <?= (int)$review['recommended'] === 1 ? 'fa-thumbs-up' : 'fa-circle-info' ?>"></i>
                                                <?= (int)$review['recommended'] === 1 ? 'Рекомендую' : 'Нейтрально' ?>
                                            </span>
                                    </header>

                                    <div class="profile-review-component">
                                        <span class="label">Компонент</span>
                                        <strong><?= htmlspecialchars($componentDisplayName) ?></strong>
                                        <?php if ($manufacturer): ?>
                                            <small><?= htmlspecialchars($manufacturer) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            <?php endforeach; $reviewCardsHtml = ob_get_clean(); ?>

                            <?php if ($hasReviewSlider): ?>
                                <div class="profile-reviews-slider">
                                    <div class="profile-reviews-window">
                                        <div class="profile-reviews-grid has-slider" data-review-slider="true">
                                            <?= $reviewCardsHtml ?>
                                        </div>
                                    </div>
                                    <?php $reviewPages = (int)ceil(count($userReviews) / $reviewsPerPage); ?>
                                    <div class="profile-reviews-pagination" data-reviews-slider aria-label="Переключение обзоров">
                                        <button type="button" class="builds-nav-btn" data-nav="prev" aria-label="Предыдущие обзоры"><i class="fas fa-arrow-left"></i></button>
                                        <div class="builds-dots" role="tablist">
                                            <?php for ($i = 0; $i < $reviewPages; $i++): ?>
                                                <button type="button" class="builds-page-dot" data-page-index="<?= $i ?>" aria-label="Страница <?= $i + 1 ?>" role="tab"></button>
                                            <?php endfor; ?>
                                        </div>
                                        <button type="button" class="builds-nav-btn" data-nav="next" aria-label="Следующие обзоры"><i class="fas fa-arrow-right"></i></button>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="profile-reviews-grid">
                                    <?= $reviewCardsHtml ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                    </div>

                </div>
            </div>
        </div>
    </main>

    <?php endif; ?>
    <?php include 'includes/footer.php'; ?>
    <script src="js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sliderGrid = document.querySelector('.profile-builds-grid[data-build-slider="true"]');
            if (!sliderGrid) return;

            const cards = sliderGrid.querySelectorAll('.profile-build-card');
            const pagination = document.querySelector('.profile-builds-pagination[data-builds-slider]');
            const reviewSlider = document.querySelector('.profile-reviews-grid[data-review-slider="true"]');
            const reviewPagination = document.querySelector('.profile-reviews-pagination[data-reviews-slider]');

            if (cards.length && pagination) {
                const dots = pagination.querySelectorAll('.builds-page-dot');
                const prevBtn = pagination.querySelector('.builds-nav-btn[data-nav="prev"]');
                const nextBtn = pagination.querySelector('.builds-nav-btn[data-nav="next"]');
                const pageSize = 2;
                let currentPage = 0;

                function renderPage(page) {
                    currentPage = page;
                    cards.forEach(card => {
                        const cardPage = parseInt(card.dataset.buildPage, 10) || 0;
                        card.dataset.visible = cardPage === currentPage ? 'true' : 'false';
                    });

                    const visibleCards = Array.from(cards).filter(card => card.dataset.visible === 'true');
                    if (visibleCards.length === 1) {
                        sliderGrid.classList.add('single-card-active');
                        sliderGrid.style.setProperty('--single-card-height', visibleCards[0].offsetHeight + 'px');
                    } else {
                        sliderGrid.classList.remove('single-card-active');
                        sliderGrid.style.removeProperty('--single-card-height');
                    }

                    dots.forEach((dot, idx) => {
                        dot.classList.toggle('active', idx === currentPage);
                        dot.setAttribute('aria-selected', idx === currentPage ? 'true' : 'false');
                    });

                    if (prevBtn) prevBtn.disabled = currentPage === 0;
                    if (nextBtn) nextBtn.disabled = currentPage >= Math.ceil(cards.length / pageSize) - 1;
                }

                dots.forEach(dot => {
                    dot.addEventListener('click', () => {
                        const target = parseInt(dot.dataset.pageIndex, 10) || 0;
                        renderPage(target);
                    });
                });

                if (prevBtn) {
                    prevBtn.addEventListener('click', () => {
                        if (currentPage > 0) renderPage(currentPage - 1);
                    });
                }
                if (nextBtn) {
                    nextBtn.addEventListener('click', () => {
                        const maxPage = Math.ceil(cards.length / pageSize) - 1;
                        if (currentPage < maxPage) renderPage(currentPage + 1);
                    });
                }

                renderPage(0);
            }

            if (reviewSlider && reviewPagination) {
                const reviewCards = reviewSlider.querySelectorAll('.profile-review-card');
                const reviewDots = reviewPagination.querySelectorAll('.builds-page-dot');
                const reviewPrev = reviewPagination.querySelector('.builds-nav-btn[data-nav="prev"]');
                const reviewNext = reviewPagination.querySelector('.builds-nav-btn[data-nav="next"]');
                let currentReviewPage = 0;

                function renderReviewPage(page) {
                    currentReviewPage = page;
                    reviewCards.forEach(card => {
                        const cardPage = parseInt(card.dataset.reviewPage, 10) || 0;
                        card.dataset.visible = cardPage === currentReviewPage ? 'true' : 'false';
                    });

                    reviewDots.forEach((dot, idx) => {
                        dot.classList.toggle('active', idx === currentReviewPage);
                        dot.setAttribute('aria-selected', idx === currentReviewPage ? 'true' : 'false');
                    });

                    if (reviewPrev) reviewPrev.disabled = currentReviewPage === 0;
                    if (reviewNext) reviewNext.disabled = currentReviewPage >= Math.ceil(reviewCards.length / 2) - 1;
                }

                reviewDots.forEach(dot => {
                    dot.addEventListener('click', () => {
                        const target = parseInt(dot.dataset.pageIndex, 10) || 0;
                        renderReviewPage(target);
                    });
                });

                if (reviewPrev) {
                    reviewPrev.addEventListener('click', () => {
                        if (currentReviewPage > 0) renderReviewPage(currentReviewPage - 1);
                    });
                }
                if (reviewNext) {
                    reviewNext.addEventListener('click', () => {
                        const maxReviewPage = Math.ceil(reviewCards.length / 2) - 1;
                        if (currentReviewPage < maxReviewPage) renderReviewPage(currentReviewPage + 1);
                    });
                }

                renderReviewPage(0);
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/@tarekraafat/autocomplete.js@10.2.7/dist/autoComplete.min.js"></script>
    <script>
        function toggleEditForm() {
            const editForm = document.getElementById('editForm');
            const profileView = document.getElementById('profileView');
            
            if (editForm.style.display === 'none') {
                editForm.style.display = 'block';
                if (profileView) profileView.style.display = 'none';
            } else {
                editForm.style.display = 'none';
                if (profileView) profileView.style.display = 'block';
            }
        }

        // Location autocomplete using OpenStreetMap Nominatim
        const locationInput = document.getElementById('locationInput');
        if (locationInput) {
            const autoCompleteJS = new autoComplete({
                selector: "#locationInput",
                placeHolder: "Начните вводить город...",
                data: {
                    src: async (query) => {
                        try {
                            const response = await fetch(
                                `https://nominatim.openstreetmap.org/search?` + 
                                `format=json&q=${encodeURIComponent(query)}&` +
                                `limit=8&addressdetails=1&accept-language=ru`,
                                {
                                    headers: {
                                        'User-Agent': 'JKT-PC-Builder/1.0'
                                    }
                                }
                            );
                            const data = await response.json();
                            
                            // Format results to show city, country
                            return data.map(item => {
                                const address = item.address || {};
                                const city = address.city || address.town || address.village || address.state;
                                const country = address.country;
                                
                                if (city && country) {
                                    return `${city}, ${country}`;
                                }
                                return item.display_name;
                            });
                        } catch (error) {
                            console.error('Autocomplete error:', error);
                            return [];
                        }
                    },
                    cache: false,
                },
                resultsList: {
                    element: (list, data) => {
                        if (!data.results.length) {
                            const message = document.createElement("div");
                            message.setAttribute("class", "no_result");
                            message.innerHTML = `<span>Ничего не найдено для "${data.query}"</span>`;
                            list.appendChild(message);
                        }
                    },
                    noResults: true,
                    maxResults: 8,
                    tabSelect: true
                },
                resultItem: {
                    highlight: {
                        render: true
                    }
                },
                events: {
                    input: {
                        selection: (event) => {
                            const selection = event.detail.selection.value;
                            autoCompleteJS.input.value = selection;
                        },
                        focus: () => {
                            if (autoCompleteJS.input.value.length) autoCompleteJS.start();
                        }
                    }
                },
                debounce: 300,
                threshold: 2
            });
        }
    </script>
</body>
</html>

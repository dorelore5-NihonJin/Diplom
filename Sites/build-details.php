<?php
session_start();
require_once 'config.php';

$buildId = $_GET['id'] ?? null;

if (!$buildId) {
    header('Location: builds.php');
    exit;
}

// Get build details with user info and component details
try {
    $stmt = $pdo->prepare("
        SELECT b.*, u.username, u.avatar,
               COUNT(DISTINCT bl.user_id) as likes_count,
               COUNT(DISTINCT bc.id) as comments_count
        FROM user_builds b
        LEFT JOIN users u ON b.user_id = u.id
        LEFT JOIN build_likes bl ON b.id = bl.build_id
        LEFT JOIN build_comments bc ON b.id = bc.build_id
        WHERE b.id = ? AND (b.is_public = 1 OR b.user_id = ?)
        GROUP BY b.id
    ");
    $stmt->execute([$buildId, $_SESSION['user_id'] ?? 0]);
    $build = $stmt->fetch();
    
    if (!$build) {
        header('Location: builds.php');
        exit;
    }
    
    // Get detailed components from build_components table
    $stmt = $pdo->prepare("
        SELECT bc.*, c.name, c.manufacturer, c.price, c.power_consumption, cat.name as category_name, cat.icon as category_icon
        FROM build_components bc
        LEFT JOIN (
            SELECT id, name, manufacturer, price, power_consumption, category_id, 'cpu' as type FROM components_cpu
            UNION ALL SELECT id, name, manufacturer, price, power_consumption, category_id, 'gpu' FROM components_gpu
            UNION ALL SELECT id, name, manufacturer, price, power_consumption, category_id, 'motherboard' FROM components_mobo
            UNION ALL SELECT id, name, manufacturer, price, power_consumption, category_id, 'ram' FROM components_ram
            UNION ALL SELECT id, name, manufacturer, price, power_consumption, category_id, 'storage' FROM components_storage
            UNION ALL SELECT id, name, manufacturer, price, power_consumption, category_id, 'psu' FROM components_psu
            UNION ALL SELECT id, name, manufacturer, price, power_consumption, category_id, 'case' FROM components_case
            UNION ALL SELECT id, name, manufacturer, price, power_consumption, category_id, 'cooling' FROM components_cooling
        ) c ON bc.component_id = c.id
        LEFT JOIN categories cat ON c.category_id = cat.id
        WHERE bc.build_id = ?
        ORDER BY cat.id
    ");
    $stmt->execute([$buildId]);
    $components = $stmt->fetchAll();
    
    // Check if current user liked this build
    $userLiked = false;
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT id FROM build_likes WHERE build_id = ? AND user_id = ?");
        $stmt->execute([$buildId, $_SESSION['user_id']]);
        $userLiked = $stmt->fetch() !== false;
    }
    
    // Get all comments in flat structure with parent info and reply count
    // Order: parent comments by creation time, then their replies grouped together
    $stmt = $pdo->prepare("
        SELECT bc.*, u.username, u.avatar,
               parent_u.username as parent_username,
               (SELECT COUNT(*) FROM build_comments WHERE parent_id = bc.id) as replies_count
        FROM build_comments bc
        LEFT JOIN users u ON bc.user_id = u.id
        LEFT JOIN build_comments parent_c ON bc.parent_id = parent_c.id
        LEFT JOIN users parent_u ON parent_c.user_id = parent_u.id
        WHERE bc.build_id = ?
        ORDER BY 
            CASE WHEN bc.parent_id IS NULL THEN bc.id ELSE bc.parent_id END,
            CASE WHEN bc.parent_id IS NULL THEN 0 ELSE 1 END,
            bc.created_at ASC
    ");
    $stmt->execute([$buildId]);
    $comments = $stmt->fetchAll();
    
} catch (PDOException $e) {
    header('Location: builds.php');
    exit;
}

$isOwner = isset($_SESSION['user_id']) && $_SESSION['user_id'] == $build['user_id'];
$componentsData = json_decode($build['components'], true) ?? [];
$totalComponents = count($components);

// Function to render a single comment in flat structure
function renderComment($comment, $isLoggedIn, $currentUserId = null) {
    $avatarContent = !empty($comment['avatar']) 
        ? '<img src="' . htmlspecialchars($comment['avatar']) . '" alt="Avatar">'
        : strtoupper(substr($comment['username'] ?? 'U', 0, 1));
    
    $isReply = !empty($comment['parent_username']);
    $isOwner = $currentUserId && $comment['user_id'] == $currentUserId;
    $itemClass = 'comment-item' . ($isReply ? ' comment-reply' : '');
    
    $parentAttr = $comment['parent_id'] ? ' data-parent-id="' . $comment['parent_id'] . '"' : '';
    echo '<div class="' . $itemClass . '" data-comment-id="' . $comment['id'] . '"' . $parentAttr . '>';
    
    // Show reply indicator if this is a reply
    if ($isReply) {
        echo '<div class="reply-indicator">';
        echo '<i class="fas fa-reply"></i> В ответ <strong>@' . htmlspecialchars($comment['parent_username']) . '</strong>';
        echo '</div>';
    }
    
    echo '<div class="comment-header">';
    echo '<div class="comment-avatar">' . $avatarContent . '</div>';
    echo '<div>';
    echo '<div class="comment-author">' . htmlspecialchars($comment['username'] ?? 'Пользователь') . '</div>';
    echo '<div class="comment-date">' . date('d.m.Y H:i', strtotime($comment['created_at'])) . '</div>';
    echo '</div>';
    echo '</div>';
    echo '<div class="comment-text" data-original-text="' . htmlspecialchars($comment['comment']) . '">' . nl2br(htmlspecialchars($comment['comment'])) . '</div>';
    
    if ($isLoggedIn) {
        echo '<div class="comment-actions">';
        echo '<button class="btn-reply" onclick="showReplyForm(' . $comment['id'] . ', \'' . htmlspecialchars($comment['username'], ENT_QUOTES) . '\')">';
        echo '<i class="fas fa-reply"></i> Ответить';
        echo '</button>';
        
        // Show edit and delete buttons for own comments
        if ($isOwner) {
            echo '<button class="btn-edit-comment" onclick="editComment(' . $comment['id'] . ')">';
            echo '<i class="fas fa-edit"></i> Редактировать';
            echo '</button>';
            echo '<button class="btn-delete-comment" onclick="deleteComment(' . $comment['id'] . ')">';
            echo '<i class="fas fa-trash"></i> Удалить';
            echo '</button>';
        }
        
        echo '</div>';
    }
    
    // Show toggle button for parent comments with replies
    if (!$isReply && $comment['replies_count'] > 0) {
        echo '<button class="btn-toggle-replies" onclick="toggleReplies(' . $comment['id'] . ')" data-comment-id="' . $comment['id'] . '">';
        echo '<i class="fas fa-chevron-down"></i>';
        echo '<span>Показать ответы</span>';
        echo '<span class="replies-count">' . $comment['replies_count'] . '</span>';
        echo '</button>';
    }
    
    echo '</div>';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($build['build_name']) ?> - <?= SITE_NAME ?></title>
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
    <style>
        .build-details-page {
            padding: 60px 0;
            min-height: calc(100vh - 160px);
        }

        .build-details-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .build-header-section {
            background: var(--card-bg);
            border-radius: 24px;
            padding: 40px;
            margin-bottom: 32px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.12);
            border: 1px solid rgba(148, 163, 184, 0.18);
            position: relative;
            overflow: hidden;
        }

        .build-header-section::after {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(600px 200px at 90% 0%, rgba(59, 130, 246, 0.18), transparent 60%),
                        radial-gradient(520px 220px at 0% 20%, rgba(139, 92, 246, 0.16), transparent 55%);
            opacity: 0.8;
            pointer-events: none;
        }

        .build-title-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 24px;
            position: relative;
            z-index: 1;
        }

        .build-main-title {
            font-size: 36px;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 16px;
        }

        .build-meta {
            display: flex;
            align-items: center;
            gap: 24px;
            flex-wrap: wrap;
        }

        .build-author-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .author-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            font-weight: 700;
        }

        .author-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .author-name {
            font-size: 16px;
            font-weight: 600;
            color: var(--text);
        }

        .build-date {
            color: var(--text-secondary);
            font-size: 14px;
        }

        .build-stats {
            display: flex;
            gap: 20px;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-secondary);
            font-size: 15px;
        }

        .stat-item i {
            color: var(--primary);
        }

        .build-actions-header {
            display: flex;
            gap: 12px;
        }

        .btn-action {
            padding: 12px 24px;
            border-radius: 12px;
            border: 2px solid var(--border);
            background: var(--card-bg);
            color: var(--text);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .btn-action.btn-like {
            border-color: #ef4444;
            color: #ef4444;
        }

        .btn-action.btn-like.liked {
            background: #ef4444;
            color: white;
        }

        .btn-action.btn-delete {
            border-color: #ef4444;
            color: #ef4444;
        }

        .btn-action.btn-delete:hover {
            background: #ef4444;
            color: white;
        }

        .build-price-section {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(139, 92, 246, 0.1));
            padding: 24px;
            border-radius: 16px;
            margin-top: 24px;
            position: relative;
            z-index: 1;
        }

        .price-label {
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .price-value {
            font-size: 42px;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .components-section {
            background: var(--card-bg);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 2px solid var(--border);
        }

        .section-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 28px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--text);
        }

        .section-title i {
            color: var(--primary);
        }

        .component-card {
            background: var(--bg-secondary);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 16px;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .component-card:hover {
            border-color: var(--primary);
            transform: translateX(4px);
        }

        .component-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 12px;
        }

        .component-icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.15), rgba(139, 92, 246, 0.15));
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 24px;
        }

        .component-info {
            flex: 1;
        }

        .component-category {
            font-size: 12px;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .component-name {
            font-size: 18px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 4px;
        }

        .component-manufacturer {
            font-size: 14px;
            color: var(--text-secondary);
        }

        .component-price {
            font-size: 20px;
            font-weight: 700;
            color: var(--primary);
        }

        .comments-section {
            background: var(--card-bg);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 2px solid var(--border);
        }

        .build-highlights {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-top: 24px;
            position: relative;
            z-index: 1;
        }

        .highlight-card {
            padding: 16px 18px;
            border-radius: 16px;
            background: rgba(15, 23, 42, 0.08);
            border: 1px solid rgba(148, 163, 184, 0.2);
        }

        .highlight-card .label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--text-secondary);
        }

        .highlight-card .value {
            font-size: 20px;
            font-weight: 700;
            margin-top: 6px;
            color: var(--text);
        }

        .build-content-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1.2fr);
            gap: 32px;
        }

        .comment-form {
            margin-bottom: 32px;
        }

        .comment-input {
            width: 100%;
            padding: 16px;
            border: 2px solid var(--border);
            border-radius: 12px;
            font-size: 15px;
            background: var(--input-bg);
            color: var(--text);
            resize: vertical;
            min-height: 100px;
            margin-bottom: 12px;
        }

        .comment-input:focus {
            outline: none;
            border-color: var(--primary);
        }

        .comment-item {
            padding: 24px;
            background: var(--bg-secondary);
            border-radius: 16px;
            margin-bottom: 12px;
            position: relative;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
        }

        .comment-item:hover {
            border-color: rgba(59, 130, 246, 0.3);
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.12);
            transform: none;
        }

        .comment-item.comment-reply {
            margin-left: 48px;
            margin-top: 8px;
            padding: 20px;
            border-left: 4px solid var(--primary);
            background: rgba(15, 23, 42, 0.04);
            display: none;
            border-radius: 12px;
        }

        .comment-item.comment-reply.visible {
            display: block;
            animation: slideInUp 0.3s ease;
        }

        .comment-item.comment-reply:first-of-type {
            margin-top: 12px;
        }

        .btn-toggle-replies {
            background: none;
            border: none;
            color: var(--primary);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 8px;
            transition: all 0.2s ease;
            margin-top: 8px;
        }

        .btn-toggle-replies:hover {
            background: rgba(59, 130, 246, 0.1);
        }

        .btn-toggle-replies i {
            transition: transform 0.3s ease;
        }

        .btn-toggle-replies.expanded i {
            transform: rotate(180deg);
        }

        .replies-count {
            background: var(--primary);
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 700;
        }

        .reply-indicator {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            color: var(--primary);
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.08), rgba(139, 92, 246, 0.08));
            padding: 8px 14px;
            border-radius: 20px;
            margin-bottom: 14px;
            font-weight: 600;
            border: 1px solid rgba(59, 130, 246, 0.2);
        }

        .reply-indicator i {
            font-size: 11px;
        }

        .reply-indicator strong {
            color: var(--primary);
            font-weight: 700;
        }

        .comment-header {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 14px;
        }

        .comment-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            flex-shrink: 0;
            font-size: 16px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.2);
        }

        .comment-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .comment-author {
            font-weight: 700;
            color: var(--text);
            font-size: 15px;
        }

        .comment-date {
            color: var(--text-secondary);
            font-size: 12px;
            margin-top: 2px;
        }

        .comment-text {
            color: var(--text);
            line-height: 1.7;
            margin-bottom: 14px;
            font-size: 15px;
            background: rgba(255, 255, 255, 0.02);
            padding: 12px 14px;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, 0.12);
        }

        .comment-actions {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }

        .btn-reply {
            background: rgba(59, 130, 246, 0.12);
            border: 1px solid rgba(59, 130, 246, 0.2);
            color: #60a5fa;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 999px;
            transition: all 0.2s ease;
        }

        .btn-reply:hover {
            background: rgba(59, 130, 246, 0.2);
            color: #93c5fd;
        }

        .btn-edit-comment {
            background: rgba(245, 158, 11, 0.12);
            border: 1px solid rgba(245, 158, 11, 0.2);
            color: #fbbf24;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 999px;
            transition: all 0.2s ease;
        }

        .btn-edit-comment:hover {
            background: rgba(245, 158, 11, 0.2);
            color: #fcd34d;
        }

        .btn-delete-comment {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #f87171;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 999px;
            transition: all 0.2s ease;
        }

        .btn-delete-comment:hover {
            background: rgba(239, 68, 68, 0.2);
            color: #fecaca;
        }

        .edit-form {
            margin-top: 12px;
            padding: 12px;
            background: var(--card-bg);
            border-radius: 8px;
            border: 2px solid #f59e0b;
        }

        .edit-input {
            width: 100%;
            padding: 10px;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            background: var(--input-bg);
            color: var(--text);
            resize: vertical;
            min-height: 60px;
            margin-bottom: 8px;
        }

        .edit-input:focus {
            outline: none;
            border-color: #f59e0b;
        }

        .edit-form-actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }

        .btn-save-edit {
            padding: 6px 12px;
            background: #f59e0b;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 13px;
        }

        .btn-save-edit:hover {
            background: #d97706;
        }

        .btn-cancel-edit {
            padding: 6px 12px;
            background: var(--bg-secondary);
            color: var(--text);
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 13px;
        }

        .btn-cancel-edit:hover {
            background: var(--border);
        }

        .reply-form {
            margin-top: 16px;
            padding: 16px;
            background: var(--card-bg);
            border-radius: 12px;
            border: 2px solid var(--primary);
            animation: slideInUp 0.3s ease;
        }

        .reply-form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .reply-to-label {
            font-size: 13px;
            color: var(--text-secondary);
        }

        .reply-to-label strong {
            color: var(--primary);
        }

        .btn-cancel-reply {
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        .btn-cancel-reply:hover {
            background: var(--bg-secondary);
            color: var(--text);
        }

        .reply-input {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            background: var(--input-bg);
            color: var(--text);
            resize: vertical;
            min-height: 80px;
            margin-bottom: 12px;
        }

        .reply-input:focus {
            outline: none;
            border-color: var(--primary);
        }

        .reply-form-actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }

        .btn-submit-reply {
            padding: 8px 16px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-submit-reply:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .build-header-section {
                padding: 24px;
            }

            .build-main-title {
                font-size: 28px;
            }

            .price-value {
                font-size: 32px;
            }

            .components-section {
                padding: 24px;
            }

            .build-highlights {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .build-content-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="build-details-page">
        <div class="container">
            <div class="build-details-container">
                <!-- Build Header -->
                <div class="build-header-section">
                    <div class="build-title-row">
                        <div>
                            <h1 class="build-main-title"><?= htmlspecialchars($build['build_name']) ?></h1>
                            <div class="build-meta">
                                <div class="build-author-info">
                                    <div class="author-avatar">
                                        <?php if (!empty($build['avatar'])): ?>
                                            <img src="<?= htmlspecialchars($build['avatar']) ?>" alt="Avatar">
                                        <?php else: ?>
                                            <?= strtoupper(substr($build['username'] ?? 'U', 0, 1)) ?>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div class="author-name"><?= htmlspecialchars($build['username'] ?? 'Пользователь') ?></div>
                                        <div class="build-date"><?= date('d.m.Y', strtotime($build['created_at'])) ?></div>
                                    </div>
                                </div>
                                <div class="build-stats">
                                    <div class="stat-item">
                                        <i class="fas fa-heart"></i>
                                        <span><?= $build['likes_count'] ?></span>
                                    </div>
                                    <div class="stat-item">
                                        <i class="fas fa-comment"></i>
                                        <span><?= $build['comments_count'] ?></span>
                                    </div>
                                    <div class="stat-item">
                                        <i class="fas fa-bolt"></i>
                                        <span><?= $build['total_power'] ?? 0 ?> Вт</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="build-actions-header">
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <button class="btn-action btn-like <?= $userLiked ? 'liked' : '' ?>" onclick="toggleLike(<?= $build['id'] ?>)">
                                    <i class="fas fa-heart"></i>
                                    <span><?= $userLiked ? 'Нравится' : 'Нравится' ?></span>
                                </button>
                            <?php endif; ?>
                            <?php if ($isOwner): ?>
                                <button class="btn-action btn-delete" onclick="deleteBuild(<?= $build['id'] ?>)">
                                    <i class="fas fa-trash"></i>
                                    <span>Удалить</span>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="build-price-section">
                        <div class="price-label">Общая стоимость</div>
                        <div class="price-value"><?= formatPrice($build['total_price']) ?></div>
                    </div>

                    <div class="build-highlights">
                        <div class="highlight-card">
                            <div class="label">Компонентов</div>
                            <div class="value"><?= $totalComponents ?></div>
                        </div>
                        <div class="highlight-card">
                            <div class="label">Потребление</div>
                            <div class="value"><?= $build['total_power'] ?? 0 ?> Вт</div>
                        </div>
                        <div class="highlight-card">
                            <div class="label">Лайки</div>
                            <div class="value"><?= $build['likes_count'] ?></div>
                        </div>
                        <div class="highlight-card">
                            <div class="label">Комментарии</div>
                            <div class="value"><?= $build['comments_count'] ?></div>
                        </div>
                    </div>
                </div>

                <div class="build-content-grid">
                    <!-- Components List -->
                    <div class="components-section">
                        <h2 class="section-title">
                            <i class="fas fa-microchip"></i>
                            Компоненты сборки
                        </h2>
                        <?php if (!empty($components)): ?>
                            <?php foreach ($components as $component): ?>
                                <div class="component-card">
                                    <div class="component-header">
                                        <div class="component-icon">
                                            <i class="fas <?= $component['category_icon'] ?? 'fa-microchip' ?>"></i>
                                        </div>
                                        <div class="component-info">
                                            <div class="component-category"><?= htmlspecialchars($component['category_name'] ?? 'Компонент') ?></div>
                                            <div class="component-name"><?= htmlspecialchars($component['name']) ?></div>
                                            <div class="component-manufacturer"><?= htmlspecialchars($component['manufacturer'] ?? '') ?></div>
                                        </div>
                                        <div class="component-price"><?= formatPrice($component['price']) ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="text-align: center; color: var(--text-secondary); padding: 40px;">Компоненты не найдены</p>
                        <?php endif; ?>
                    </div>

                    <!-- Comments Section -->
                    <div class="comments-section" id="comments">
                        <h2 class="section-title">
                            <i class="fas fa-comments"></i>
                            Комментарии (<?= count($comments) ?>)
                        </h2>

                        <?php if (isset($_SESSION['user_id'])): ?>
                            <div class="comment-form">
                                <textarea class="comment-input" id="commentText" placeholder="Напишите комментарий..."></textarea>
                                <button class="btn btn-primary" onclick="postComment()">
                                    <i class="fas fa-paper-plane"></i>
                                    Отправить
                                </button>
                            </div>
                        <?php endif; ?>

                        <div id="commentsList">
                            <?php if (!empty($comments)): ?>
                                <?php foreach ($comments as $comment): ?>
                                    <?php renderComment($comment, isset($_SESSION['user_id']), $_SESSION['user_id'] ?? null); ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p style="text-align: center; color: var(--text-secondary); padding: 40px;">Комментариев пока нет</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="js/main.js"></script>
    <script>
        const buildId = <?= $buildId ?>;
        const currentUserId = <?= isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'null' ?>;

        function toggleLike(id) {
            fetch('api/toggle_like.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ build_id: id })
            })
            .then(res => {
                if (!res.ok) {
                    throw new Error('Ошибка сервера');
                }
                return res.json();
            })
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    throw new Error(data.error || 'Ошибка');
                }
            })
            .catch(error => {
                console.error('Error toggling like:', error);
                showNotification(error.message || 'Ошибка изменения лайка', 'error');
            });
        }

        function postComment() {
            const text = document.getElementById('commentText').value.trim();
            if (!text) {
                showNotification('Введите текст комментария', 'warning');
                return;
            }

            const btn = event.target;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Отправка...';

            fetch('api/post_comment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ build_id: buildId, comment: text })
            })
            .then(res => {
                if (!res.ok) {
                    throw new Error('Ошибка сервера');
                }
                return res.json();
            })
            .then(data => {
                if (data.success) {
                    showNotification('Комментарий добавлен', 'success');
                    
                    // Clear textarea
                    document.getElementById('commentText').value = '';
                    
                    // Add comment to list dynamically
                    addCommentToList(data.comment);
                    
                    // Update comment count
                    updateCommentCount(1);
                    
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-paper-plane"></i> Отправить';
                } else {
                    throw new Error(data.error || 'Ошибка добавления комментария');
                }
            })
            .catch(error => {
                console.error('Error posting comment:', error);
                showNotification(error.message || 'Ошибка отправки комментария', 'error');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-paper-plane"></i> Отправить';
            });
        }

        function addCommentToList(comment, parentUsername = null, parentId = null) {
            const commentsList = document.getElementById('commentsList');
            const noCommentsMsg = commentsList.querySelector('p');
            
            // Remove "no comments" message if exists
            if (noCommentsMsg) {
                noCommentsMsg.remove();
            }
            
            // Create comment element
            const commentDiv = document.createElement('div');
            const isReply = !!parentUsername;
            commentDiv.className = 'comment-item' + (isReply ? ' comment-reply visible' : '');
            commentDiv.style.animation = 'slideInUp 0.3s ease';
            commentDiv.dataset.commentId = comment.id || Date.now();
            
            // Add parent-id for replies
            if (isReply && parentId) {
                commentDiv.dataset.parentId = parentId;
            }
            
            const avatarContent = comment.avatar 
                ? `<img src="${escapeHtml(comment.avatar)}" alt="Avatar">`
                : escapeHtml(comment.username.charAt(0).toUpperCase());
            
            // Build reply indicator if this is a reply
            const replyIndicator = parentUsername 
                ? `<div class="reply-indicator">
                       <i class="fas fa-reply"></i> В ответ <strong>@${escapeHtml(parentUsername)}</strong>
                   </div>`
                : '';
            
            // Build edit/delete buttons if it's current user's comment
            const editDeleteButtons = currentUserId 
                ? `<button class="btn-edit-comment" onclick="editComment(${comment.id})">
                       <i class="fas fa-edit"></i> Редактировать
                   </button>
                   <button class="btn-delete-comment" onclick="deleteComment(${comment.id})">
                       <i class="fas fa-trash"></i> Удалить
                   </button>`
                : '';
            
            commentDiv.innerHTML = `
                ${replyIndicator}
                <div class="comment-header">
                    <div class="comment-avatar">
                        ${avatarContent}
                    </div>
                    <div>
                        <div class="comment-author">${escapeHtml(comment.username)}</div>
                        <div class="comment-date">Только что</div>
                    </div>
                </div>
                <div class="comment-text" data-original-text="${escapeHtml(comment.comment)}">${escapeHtml(comment.comment).replace(/\n/g, '<br>')}</div>
                <div class="comment-actions">
                    <button class="btn-reply" onclick="showReplyForm(${comment.id || Date.now()}, '${escapeHtml(comment.username)}')">
                        <i class="fas fa-reply"></i> Ответить
                    </button>
                    ${editDeleteButtons}
                </div>
            `;
            
            // Insert reply after parent or append to end
            if (isReply && parentId) {
                const parentComment = document.querySelector(`[data-comment-id="${parentId}"]`);
                if (parentComment) {
                    // Find the last reply to this parent or insert after parent
                    let insertAfter = parentComment;
                    let nextSibling = parentComment.nextElementSibling;
                    
                    while (nextSibling && nextSibling.classList.contains('comment-reply')) {
                        insertAfter = nextSibling;
                        nextSibling = nextSibling.nextElementSibling;
                    }
                    
                    insertAfter.after(commentDiv);
                    return;
                }
            }
            
            // Append to the end if not a reply
            commentsList.appendChild(commentDiv);
        }

        let currentReplyTo = null;

        function showReplyForm(commentId, username) {
            // Remove any existing reply forms
            document.querySelectorAll('.reply-form').forEach(form => form.remove());
            
            currentReplyTo = { id: commentId, username: username };
            
            const commentItem = document.querySelector(`[data-comment-id="${commentId}"]`);
            if (!commentItem) return;
            
            const replyForm = document.createElement('div');
            replyForm.className = 'reply-form';
            replyForm.innerHTML = `
                <div class="reply-form-header">
                    <div class="reply-to-label">Ответ для <strong>${escapeHtml(username)}</strong></div>
                    <button class="btn-cancel-reply" onclick="cancelReply()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <textarea class="reply-input" id="replyText" placeholder="Напишите ответ..."></textarea>
                <div class="reply-form-actions">
                    <button class="btn-submit-reply" onclick="submitReply()">
                        <i class="fas fa-paper-plane"></i>
                        Отправить
                    </button>
                </div>
            `;
            
            commentItem.appendChild(replyForm);
            document.getElementById('replyText').focus();
        }

        function cancelReply() {
            document.querySelectorAll('.reply-form').forEach(form => form.remove());
            currentReplyTo = null;
        }

        function submitReply() {
            if (!currentReplyTo) return;
            
            const text = document.getElementById('replyText').value.trim();
            if (!text) {
                showNotification('Введите текст ответа', 'warning');
                return;
            }

            const btn = event.target;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Отправка...';

            fetch('api/post_comment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    build_id: buildId, 
                    comment: text,
                    parent_id: currentReplyTo.id
                })
            })
            .then(res => {
                if (!res.ok) {
                    throw new Error('Ошибка сервера');
                }
                return res.json();
            })
            .then(data => {
                if (data.success) {
                    showNotification('Ответ добавлен', 'success');
                    
                    // Add reply to list dynamically with parent username and ID
                    addCommentToList(data.comment, currentReplyTo.username, currentReplyTo.id);
                    
                    // Update or add toggle button for parent
                    updateParentToggleButton(currentReplyTo.id);
                    
                    // Auto-expand replies to show the new one
                    const btn = document.querySelector(`.btn-toggle-replies[data-comment-id="${currentReplyTo.id}"]`);
                    if (btn && !btn.classList.contains('expanded')) {
                        toggleReplies(currentReplyTo.id);
                    }
                    
                    // Update comment count
                    updateCommentCount(1);
                    
                    // Remove reply form
                    cancelReply();
                } else {
                    throw new Error(data.error || 'Ошибка добавления ответа');
                }
            })
            .catch(error => {
                console.error('Error posting reply:', error);
                showNotification(error.message || 'Ошибка отправки ответа', 'error');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-paper-plane"></i> Отправить';
            });
        }

        function updateCommentCount(delta) {
            const countElement = document.querySelector('.section-title');
            if (countElement) {
                const match = countElement.textContent.match(/\((\d+)\)/);
                if (match) {
                    const newCount = parseInt(match[1]) + delta;
                    countElement.innerHTML = `<i class="fas fa-comments"></i> Комментарии (${newCount})`;
                }
            }
            
            // Update stat in header
            const statElement = document.querySelector('.stat-item:nth-child(2) span');
            if (statElement) {
                const currentCount = parseInt(statElement.textContent) || 0;
                statElement.textContent = currentCount + delta;
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function toggleReplies(parentId) {
            const btn = document.querySelector(`.btn-toggle-replies[data-comment-id="${parentId}"]`);
            if (!btn) return;

            // Find all replies with matching parent-id
            const replies = document.querySelectorAll(`.comment-reply[data-parent-id="${parentId}"]`);
            const isExpanded = btn.classList.contains('expanded');
            
            if (isExpanded) {
                replies.forEach(reply => reply.classList.remove('visible'));
                btn.classList.remove('expanded');
                btn.querySelector('span:not(.replies-count)').textContent = 'Показать ответы';
            } else {
                replies.forEach(reply => reply.classList.add('visible'));
                btn.classList.add('expanded');
                btn.querySelector('span:not(.replies-count)').textContent = 'Скрыть ответы';
            }
        }

        function updateParentToggleButton(parentId) {
            const parentComment = document.querySelector(`[data-comment-id="${parentId}"]`);
            if (!parentComment || parentComment.classList.contains('comment-reply')) return;

            const repliesCount = document.querySelectorAll(`.comment-reply[data-parent-id="${parentId}"]`).length;

            let btn = parentComment.querySelector('.btn-toggle-replies');
            
            if (repliesCount > 0) {
                if (!btn) {
                    // Create toggle button
                    btn = document.createElement('button');
                    btn.className = 'btn-toggle-replies';
                    btn.dataset.commentId = parentId;
                    btn.onclick = () => toggleReplies(parentId);
                    btn.innerHTML = `
                        <i class="fas fa-chevron-down"></i>
                        <span>Показать ответы</span>
                        <span class="replies-count">${repliesCount}</span>
                    `;
                    parentComment.appendChild(btn);
                } else {
                    // Update count
                    const countSpan = btn.querySelector('.replies-count');
                    if (countSpan) {
                        countSpan.textContent = repliesCount;
                    }
                }
            }
        }

        function editComment(commentId) {
            const commentItem = document.querySelector(`[data-comment-id="${commentId}"]`);
            if (!commentItem) return;

            // Remove any existing edit forms
            document.querySelectorAll('.edit-form').forEach(form => form.remove());
            
            const commentTextEl = commentItem.querySelector('.comment-text');
            const originalText = commentTextEl.dataset.originalText || commentTextEl.textContent;
            
            const editForm = document.createElement('div');
            editForm.className = 'edit-form';
            editForm.innerHTML = `
                <textarea class="edit-input" id="editText${commentId}">${escapeHtml(originalText)}</textarea>
                <div class="edit-form-actions">
                    <button class="btn-save-edit" onclick="saveEdit(${commentId})">
                        <i class="fas fa-save"></i> Сохранить
                    </button>
                    <button class="btn-cancel-edit" onclick="cancelEdit(${commentId})">
                        Отмена
                    </button>
                </div>
            `;
            
            commentTextEl.style.display = 'none';
            commentTextEl.after(editForm);
            document.getElementById(`editText${commentId}`).focus();
        }

        function cancelEdit(commentId) {
            const commentItem = document.querySelector(`[data-comment-id="${commentId}"]`);
            if (!commentItem) return;
            
            const editForm = commentItem.querySelector('.edit-form');
            const commentTextEl = commentItem.querySelector('.comment-text');
            
            if (editForm) editForm.remove();
            if (commentTextEl) commentTextEl.style.display = 'block';
        }

        function saveEdit(commentId) {
            const text = document.getElementById(`editText${commentId}`).value.trim();
            if (!text) {
                showNotification('Комментарий не может быть пустым', 'warning');
                return;
            }

            fetch('api/edit_comment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ comment_id: commentId, comment: text })
            })
            .then(res => {
                if (!res.ok) throw new Error('Ошибка сервера');
                return res.json();
            })
            .then(data => {
                if (data.success) {
                    showNotification('Комментарий обновлён', 'success');
                    
                    const commentItem = document.querySelector(`[data-comment-id="${commentId}"]`);
                    const commentTextEl = commentItem.querySelector('.comment-text');
                    commentTextEl.innerHTML = escapeHtml(text).replace(/\n/g, '<br>');
                    commentTextEl.dataset.originalText = text;
                    commentTextEl.style.display = 'block';
                    
                    const editForm = commentItem.querySelector('.edit-form');
                    if (editForm) editForm.remove();
                } else {
                    throw new Error(data.error || 'Ошибка обновления');
                }
            })
            .catch(error => {
                console.error('Error editing comment:', error);
                showNotification(error.message || 'Ошибка редактирования', 'error');
            });
        }

        function deleteComment(commentId) {
            if (!confirm('Удалить комментарий?')) return;

            fetch('api/delete_comment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ comment_id: commentId })
            })
            .then(res => {
                if (!res.ok) throw new Error('Ошибка сервера');
                return res.json();
            })
            .then(data => {
                if (data.success) {
                    showNotification('Комментарий удалён', 'success');
                    
                    const commentItem = document.querySelector(`[data-comment-id="${commentId}"]`);
                    if (commentItem) {
                        commentItem.style.animation = 'slideOutRight 0.3s ease';
                        setTimeout(() => {
                            commentItem.remove();
                            updateCommentCount(-1);
                            
                            // Check if no comments left
                            const commentsList = document.getElementById('commentsList');
                            if (commentsList.children.length === 0) {
                                commentsList.innerHTML = '<p style="text-align: center; color: var(--text-secondary); padding: 40px;">Комментариев пока нет</p>';
                            }
                        }, 300);
                    }
                } else {
                    throw new Error(data.error || 'Ошибка удаления');
                }
            })
            .catch(error => {
                console.error('Error deleting comment:', error);
                showNotification(error.message || 'Ошибка удаления', 'error');
            });
        }

        function deleteBuild(id) {
            if (!confirm('Вы уверены, что хотите удалить эту сборку?')) return;

            fetch('api/delete_build.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ build_id: id })
            })
            .then(res => {
                if (!res.ok) {
                    throw new Error('Ошибка сервера');
                }
                return res.json();
            })
            .then(data => {
                if (data.success) {
                    showNotification('Сборка удалена', 'success');
                    setTimeout(() => window.location.href = 'builds.php', 500);
                } else {
                    throw new Error(data.error || 'Ошибка удаления');
                }
            })
            .catch(error => {
                console.error('Error deleting build:', error);
                showNotification(error.message || 'Ошибка удаления сборки', 'error');
            });
        }
    </script>
</body>
</html>

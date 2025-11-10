<?php
// FILE: includes/sections/main/view-profile.php

// $viewProfileData se carga desde config/routing/router.php con DATOS REALES.
global $pdo, $basePath;
$defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";
$userAvatar = $_SESSION['profile_image_url'] ?? $defaultAvatar;
$userId = $_SESSION['user_id'];

if (!isset($viewProfileData) || empty($viewProfileData)) {
    include dirname(__DIR__, 1) . '/main/404.php';
    return;
}

$profile = $viewProfileData;
$publications = $viewProfileData['publications'];
$friendshipStatus = $viewProfileData['friendship_status'] ?? 'not_friends';
$currentTab = $viewProfileData['current_tab'] ?? 'posts';

$roleIconMap = [
    'user' => 'person',
    'moderator' => 'shield_person',
    'administrator' => 'admin_panel_settings',
    'founder' => 'star'
];
$profileRoleIcon = $roleIconMap[$profile['role']] ?? 'person';

$isOwnProfile = ($profile['id'] == $userId);
$targetUserId = $profile['id']; // ID del perfil que se está viendo

// --- Lógica de Amigos (PREVIEW) ---
$friendCount = 0;
$profileFriends = [];
try {
    // 1. Contar amigos
    $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM friendships WHERE (user_id_1 = ? OR user_id_2 = ?) AND status = 'accepted'");
    $stmt_count->execute([$targetUserId, $targetUserId]);
    $friendCount = (int)$stmt_count->fetchColumn();

    // 2. Obtener 9 amigos aleatorios para la vista previa
    $stmt_friends = $pdo->prepare(
       "SELECT 
            u.username, u.profile_image_url, u.role 
        FROM friendships f
        JOIN users u ON (CASE WHEN f.user_id_1 = ? THEN f.user_id_2 ELSE f.user_id_1 END) = u.id
        WHERE (f.user_id_1 = ? OR f.user_id_2 = ?) AND f.status = 'accepted'
        ORDER BY RAND()
        LIMIT 9"
    );
    $stmt_friends->execute([$targetUserId, $targetUserId, $targetUserId]);
    $profileFriends = $stmt_friends->fetchAll();
    
} catch (PDOException $e) {
    logDatabaseError($e, 'view-profile - fetch friends preview');
}
// --- Fin Lógica de Amigos (PREVIEW) ---

// --- ▼▼▼ INICIO DE NUEVA LÓGICA (LISTA COMPLETA DE AMIGOS) ▼▼▼ ---
$fullFriendList = [];
try {
    // 1. Obtener TODOS los amigos para la pestaña "Amigos"
    $stmt_full_friends = $pdo->prepare(
       "SELECT 
            u.id, u.username, u.profile_image_url, u.role,
            (SELECT COUNT(*) 
             FROM friendships f_common
             WHERE 
                (f_common.user_id_1 = u.id OR f_common.user_id_2 = u.id) 
                AND f_common.status = 'accepted' 
                AND (f_common.user_id_1 IN (SELECT user_id_2 FROM friendships WHERE user_id_1 = ? AND status = 'accepted' UNION SELECT user_id_1 FROM friendships WHERE user_id_2 = ? AND status = 'accepted') OR f_common.user_id_2 IN (SELECT user_id_2 FROM friendships WHERE user_id_1 = ? AND status = 'accepted' UNION SELECT user_id_1 FROM friendships WHERE user_id_2 = ? AND status = 'accepted'))
            ) AS mutual_friends_count
        FROM friendships f
        JOIN users u ON (CASE WHEN f.user_id_1 = ? THEN f.user_id_2 ELSE f.user_id_1 END) = u.id
        WHERE (f.user_id_1 = ? OR f.user_id_2 = ?) AND f.status = 'accepted'
        ORDER BY u.username ASC"
    );
    // Se necesitan 7 parámetros para la consulta de amigos en común
    $stmt_full_friends->execute([$currentUserId, $currentUserId, $currentUserId, $currentUserId, $targetUserId, $targetUserId, $targetUserId]);
    $fullFriendList = $stmt_full_friends->fetchAll();
    
} catch (PDOException $e) {
    logDatabaseError($e, 'view-profile - fetch full friend list');
}
// --- ▲▲▲ FIN DE NUEVA LÓGICA (LISTA COMPLETA DE AMIGOS) ▲▲▲ ---


// --- Lógica de Estado (sin cambios) ---
$is_actually_online = false;
try {
    $context = stream_context_create(['http' => ['timeout' => 0.5]]); 
    $jsonResponse = @file_get_contents('http://127.0.0.1:8766/get-online-users', false, $context);
    
    if ($jsonResponse !== false) {
        $data = json_decode($jsonResponse, true);
        if (isset($data['status']) && $data['status'] === 'ok' && isset($data['online_users'])) {
            $is_actually_online = in_array($profile['id'], $data['online_users']);
        }
    }
} catch (Exception $e) {
    logDatabaseError($e, 'view-profile - (ws_get_online_fail)');
}

$statusBadgeHtml = '';
if ($is_actually_online) {
    $statusBadgeHtml = '<div class="profile-status-badge online" data-user-id="' . htmlspecialchars($profile['id']) . '"><span class="status-dot"></span>Activo ahora</div>';
} elseif (!empty($profile['last_seen'])) {
    $lastSeenTime = new DateTime($profile['last_seen'], new DateTimeZone('UTC'));
    $currentTime = new DateTime('now', new DateTimeZone('UTC'));
    $interval = $currentTime->diff($lastSeenTime);

    $timeAgo = '';
    if ($interval->y > 0) { $timeAgo = ($interval->y == 1) ? '1 año' : $interval->y . ' años'; }
    elseif ($interval->m > 0) { $timeAgo = ($interval->m == 1) ? '1 mes' : $interval->m . ' meses'; }
    elseif ($interval->d > 0) { $timeAgo = ($interval->d == 1) ? '1 día' : $interval->d . ' días'; }
    elseif ($interval->h > 0) { $timeAgo = ($interval->h == 1) ? '1 h' : $interval->h . ' h'; }
    elseif ($interval->i > 0) { $timeAgo = ($interval->i == 1) ? '1 min' : $interval->i . ' min'; }
    else { $timeAgo = 'unos segundos'; }
    
    $statusText = ($timeAgo === 'unos segundos') ? 'Activo hace unos momentos' : "Activo hace $timeAgo";
    $statusBadgeHtml = '<div class="profile-status-badge offline" data-user-id="' . htmlspecialchars($profile['id']) . '">' . htmlspecialchars($statusText) . '</div>';
}
// --- Fin Lógica de Estado ---

?>
<div class="section-content overflow-y <?php echo ($CURRENT_SECTION === 'view-profile') ? 'active' : 'disabled'; ?>" data-section="view-profile">

    <div class="page-toolbar-container" id="view-profile-toolbar-container">
        <div class="page-toolbar-floating">
            <div class="toolbar-action-default">
                <div class="page-toolbar-left">
                    <button type="button"
                        class="page-toolbar-button"
                        data-action="toggleSectionHome" 
                        data-tooltip="create_publication.backTooltip">
                        <span class="material-symbols-rounded">arrow_back</span>
                    </button>
                </div>
                
                <div class="page-toolbar-right profile-actions" data-user-id="<?php echo $profile['id']; ?>">
                    <?php if ($isOwnProfile): ?>
                        <button type="button"
                            class="page-toolbar-button"
                            data-action="toggleSectionSettingsProfile" 
                            data-tooltip="header.profile.settings">
                            <span class="material-symbols-rounded">edit</span>
                        </button>
                    <?php else: ?>
                        <?php if ($friendshipStatus === 'not_friends'): ?>
                            <button type="button" class="component-button component-button--primary" data-action="friend-send-request" data-user-id="<?php echo $profile['id']; ?>">
                                <span class="material-symbols-rounded">person_add</span>
                                <span data-i18n="friends.sendRequest">Agregar</span>
                            </button>
                        <?php elseif ($friendshipStatus === 'pending_sent'): ?>
                            <button type="button" class="component-button" data-action="friend-cancel-request" data-user-id="<?php echo $profile['id']; ?>">
                                <span class="material-symbols-rounded">close</span>
                                <span data-i18n="friends.cancelRequest">Cancelar</span>
                            </button>
                        <?php elseif ($friendshipStatus === 'pending_received'): ?>
                            <button type="button" class="component-button component-button--primary" data-action="friend-accept-request" data-user-id="<?php echo $profile['id']; ?>">
                                <span class="material-symbols-rounded">check</span>
                                <span data-i18n="friends.acceptRequest">Aceptar</span>
                            </button>
                            <button type="button" class="component-button" data-action="friend-decline-request" data-user-id="<?php echo $profile['id']; ?>">
                                <span class="material-symbols-rounded">close</span>
                                <span data-i18n="friends.declineRequest">Rechazar</span>
                            </button>
                        <?php elseif ($friendshipStatus === 'friends'): ?>
                            <button type="button" class="component-button" data-action="friend-remove" data-user-id="<?php echo $profile['id']; ?>">
                                <span class="material-symbols-rounded">person_remove</span>
                                <span data-i18n="friends.removeFriend">Eliminar</span>
                            </button>
                        <?php endif; ?>

                    <?php endif; ?>
                </div>
                </div>
        </div>
    </div>
    
    <div class="component-wrapper">

        <div class="profile-header-card">
            <div class="profile-banner"></div>
            <div class="profile-header-content">
                <div class="profile-avatar-container">
                    <div class="component-card__avatar" data-role="<?php echo htmlspecialchars($profile['role']); ?>">
                        <img src="<?php echo htmlspecialchars($profile['profile_image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($profile['username']); ?>" 
                             class="component-card__avatar-image">
                    </div>
                </div>

                <div class="profile-info">
                    <h1 class="profile-username"><?php echo htmlspecialchars($profile['username']); ?></h1>
                    
                    <div>
                        <div class="profile-role-badge" data-role="<?php echo htmlspecialchars($profile['role']); ?>">
                            <span class="material-symbols-rounded"><?php echo $profileRoleIcon; ?></span>
                            <span><?php echo htmlspecialchars(ucfirst($profile['role'])); ?></span>
                        </div>

                        <?php 
                        echo $statusBadgeHtml; 
                        ?>
                    </div>

                    <p class="profile-meta">
                        Se unió el <?php echo date('d/m/Y', strtotime($profile['created_at'])); ?>
                        &middot; <strong><?php echo $friendCount; ?></strong> Amigos
                    </p>
                </div>
                
                </div>
        </div>

        <div class="profile-nav-bar">
            <div class="profile-nav-left">
                <button type="button" 
                        class="profile-nav-button <?php echo ($currentTab === 'posts' || $currentTab === 'likes' || $currentTab === 'bookmarks') ? 'active' : ''; ?>" 
                        data-action="profile-tab-select" 
                        data-tab="posts">
                    Publicaciones
                </button>
                <button type="button" 
                        class="profile-nav-button" 
                        data-action="profile-tab-select" 
                        data-tab="info">
                    Informacion
                </button>
                <button type="button" 
                        class="profile-nav-button <?php echo ($currentTab === 'amigos') ? 'active' : ''; ?>" 
                        data-action="profile-tab-select" 
                        data-tab="amigos">
                    Amigos
                </button>
                <button type="button" 
                        class="profile-nav-button" 
                        data-action="profile-tab-select" 
                        data-tab="fotos">
                    Fotos
                </button>
            </div>
            <div class="profile-nav-right">
                <button type="button" class="header-button" data-action="toggleModuleProfileMore">
                    <span class="material-symbols-rounded">more_vert</span>
                </button>
                
                <div class="popover-module popover-module--anchor-right body-title disabled" data-module="moduleProfileMore">
                    <div class="menu-content">
                        <div class="menu-list">
                            <?php
                                $postsUrl = $basePath . '/profile/' . htmlspecialchars($profile['username']);
                                $likesUrl = $postsUrl . '/likes';
                                $bookmarksUrl = $postsUrl . '/bookmarks';
                            ?>
                            <?php if ($isOwnProfile): // Mostrar solo en el perfil propio ?>
                                <a class="menu-link" href="<?php echo $likesUrl; ?>" data-nav-js="true">
                                    <div class="menu-link-icon"><span class="material-symbols-rounded">favorite</span></div>
                                    <div class="menu-link-text"><span data-i18n="profile.tabs.likes">Favoritos</span></div>
                                </a>
                                <a class="menu-link" href="<?php echo $bookmarksUrl; ?>" data-nav-js="true">
                                    <div class="menu-link-icon"><span class="material-symbols-rounded">bookmark</span></div>
                                    <div class="menu-link-text"><span data-i18n="profile.tabs.bookmarks">Guardados</span></div>
                                </a>
                            <?php endif; ?>
                            
                            </div>
                    </div>
                </div>
            </div>
        </div>


        <div class="profile-content-container">

            <div class="profile-main-content <?php echo ($currentTab === 'posts' || $currentTab === 'likes' || $currentTab === 'bookmarks') ? 'active' : 'disabled'; ?>" data-profile-tab-content="posts">
                
                <div class="profile-left-column">
                    <div class="component-card component-card--column" id="profile-friends-preview-card">
                        <div class="profile-friends-header">
                            <h2 class="component-card__title" data-i18n="friends.list.title">Amigos</h2>
                            <span class="profile-friends-count"><?php echo $friendCount; ?></span>
                            </div>
                        
                        <?php if (empty($profileFriends)): ?>
                            <p class="profile-friends-empty" data-i18n="friends.list.noFriends">No tiene amigos.</p>
                        <?php else: ?>
                            <div class="profile-friends-grid">
                                <?php foreach ($profileFriends as $friend): ?>
                                    <?php
                                    $friendAvatar = $friend['profile_image_url'] ?? $defaultAvatar;
                                    if(empty($friendAvatar)) $friendAvatar = "https://ui-avatars.com/api/?name=" . urlencode($friend['username']) . "&size=100&background=e0e0e0&color=ffffff";
                                    ?>
                                    <a href="<?php echo $basePath . '/profile/' . htmlspecialchars($friend['username']); ?>" 
                                       data-nav-js="true" 
                                       class="friend-preview-item"
                                       title="<?php echo htmlspecialchars($friend['username']); ?>">
                                       
                                        <div class="comment-avatar" data-role="<?php echo htmlspecialchars($friend['role']); ?>" style="width: 100%; height: auto; padding-top: 100%; position: relative; border-radius: 8px;">
                                            <img src="<?php echo htmlspecialchars($friendAvatar); ?>" 
                                                 alt="<?php echo htmlspecialchars($friend['username']); ?>"
                                                 style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; border-radius: 8px;">
                                        </div>
                                        <span class="friend-preview-name"><?php echo htmlspecialchars($friend['username']); ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="profile-right-column">
                    
                    <?php if ($isOwnProfile && $currentTab === 'posts'): ?>
                        <div class="component-card component-card--post-creator" data-action="toggleSectionCreatePublication">
                            <div class="post-comment-avatar" data-role="<?php echo htmlspecialchars($_SESSION['role'] ?? 'user'); ?>">
                                <img src="<?php echo htmlspecialchars($userAvatar); ?>" alt="Tu avatar">
                            </div>
                            <div class="post-creator-placeholder">
                                ¿En qué estás pensando, <?php echo htmlspecialchars($_SESSION['username']); ?>?
                            </div>
                            <div class="post-creator-icon">
                                <span class="material-symbols-rounded">imagesmode</span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="card-list-container">
                        
                        <?php if (empty($publications)): ?>
                            <div class="component-card component-card--column">
                                <div class="component-card__content">
                                    <div class="component-card__icon">
                                        <span class="material-symbols-rounded">
                                            <?php
                                            switch ($currentTab) {
                                                case 'likes': echo 'favorite'; break;
                                                case 'bookmarks': echo 'bookmark'; break;
                                                default: echo 'feed'; break;
                                            }
                                            ?>
                                        </span>
                                    </div>
                                    <div class="component-card__text">
                                        <?php if ($currentTab === 'likes'): ?>
                                            <h2 class="component-card__title" data-i18n="profile.noLikes.title"></h2>
                                            <p class="component-card__description" data-i18n="profile.noLikes.desc"></p>
                                        <?php elseif ($currentTab === 'bookmarks'): ?>
                                            <h2 class="component-card__title" data-i18n="profile.noBookmarks.title"></h2>
                                            <p class="component-card__description" data-i18n="profile.noBookmarks.desc"></p>
                                        <?php else: ?>
                                            <h2 class="component-card__title" data-i18n="profile.noPosts.title"></h2>
                                            <?php if ($isOwnProfile): ?>
                                                <p class="component-card__description" data-i18n="profile.noPosts.descSelf"></p>
                                            <?php else: ?>
                                                <p class="component-card__description" data-i18n="profile.noPosts.descOther"><?php echo htmlspecialchars($profile['username']); ?> aún no ha publicado nada.</p>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($publications as $post): ?>
                                <?php
                                $postAvatar = $post['profile_image_url'] ?? $defaultAvatar;
                                if (empty($postAvatar)) $postAvatar = $defaultAvatar;
                                $postRole = $post['role'] ?? 'user';
                                $attachments = [];
                                if (!empty($post['attachments'])) {
                                    $attachments = explode(',', $post['attachments']);
                                }
                                $attachmentCount = count($attachments);
                                $isPoll = $post['post_type'] === 'poll';
                                $hasVoted = $post['user_voted_option_id'] !== null;
                                $totalVotes = (int)($post['total_votes'] ?? 0);
                                $pollOptions = $post['poll_options'] ?? [];
                                $likeCount = (int)($post['like_count'] ?? 0);
                                $userHasLiked = (int)($post['user_has_liked'] ?? 0) > 0;
                                $commentCount = (int)($post['comment_count'] ?? 0);
                                $userHasBookmarked = (int)($post['user_has_bookmarked'] ?? 0) > 0;
                                $privacyLevel = $post['privacy_level'] ?? 'public';
                                $privacyIcon = 'public';
                                $privacyTooltipKey = 'post.privacy.public';
                                if ($privacyLevel === 'friends') {
                                    $privacyIcon = 'group';
                                    $privacyTooltipKey = 'post.privacy.friends';
                                } elseif ($privacyLevel === 'private') {
                                    $privacyIcon = 'lock';
                                    $privacyTooltipKey = 'post.privacy.private';
                                }
                                ?>
                                
                                <div class="component-card component-card--post component-card--column" 
                                     data-post-id="<?php echo $post['id']; ?>"
                                     data-privacy="<?php echo htmlspecialchars($privacyLevel); ?>">
                                <div class="post-card-header">
                                        <div class="component-card__content">
                                            <div class="component-card__avatar" data-role="<?php echo htmlspecialchars($postRole); ?>">
                                                <img src="<?php echo htmlspecialchars($postAvatar); ?>" alt="<?php echo htmlspecialchars($post['username']); ?>" class="component-card__avatar-image">
                                            </div>
                                            <div class="component-card__text">
                                                <h2 class="component-card__title"><?php echo htmlspecialchars($post['username']); ?></h2>
                                                <p class="component-card__description">
                                                    <?php echo date('d/m/Y H:i', strtotime($post['created_at'])); ?>
                                                    <?php if (isset($post['community_name']) && $post['community_name']): ?>
                                                        <span> &middot; en <strong><?php echo htmlspecialchars($post['community_name']); ?></strong></span>
                                                    <?php endif; ?>
                                                    
                                                    <span class="post-privacy-icon" data-tooltip="<?php echo $privacyTooltipKey; ?>">
                                                        <span class="material-symbols-rounded"><?php echo $privacyIcon; ?></span>
                                                    </span>
                                                    </p>
                                            </div>
                                        </div>
                                        
                                        <?php if ($isOwnProfile): ?>
                                        <div class="post-card-options">
                                            <button type="button" 
                                                    class="component-action-button--icon" 
                                                    data-action="toggle-post-options"
                                                    data-post-id="<?php echo $post['id']; ?>"
                                                    data-tooltip="Más opciones">
                                                <span class="material-symbols-rounded">more_vert</span>
                                            </button>
                                            
                                            <div class="popover-module body-title disabled" data-module="modulePostOptions">
                                                <div class="menu-content">
                                                    <div class="menu-list">
                                                        <div class="menu-link" data-action="toggle-post-privacy">
                                                            <div class="menu-link-icon">
                                                                <span class="material-symbols-rounded">visibility</span>
                                                            </div>
                                                            <div class="menu-link-text">
                                                                <span data-i18n="post.options.changePrivacy"></span>
                                                            </div>
                                                        </div>
                                                        <div class="menu-link" data-action="post-delete">
                                                            <div class="menu-link-icon">
                                                                <span class="material-symbols-rounded" style="color: #c62828;">delete</span>
                                                            </div>
                                                            <div class="menu-link-text">
                                                                <span data-i18n="post.options.delete" style="color: #c62828;"></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="popover-module body-title disabled" data-module="modulePostPrivacy">
                                                <div class="menu-content">
                                                    <div class="menu-list">
                                                        <div class="menu-header" data-i18n="post.options.privacyTitle"></div>
                                                        
                                                        <div class="menu-link" data-action="post-set-privacy" data-value="public">
                                                            <div class="menu-link-icon">
                                                                <span class="material-symbols-rounded">public</span>
                                                            </div>
                                                            <div class="menu-link-text">
                                                                <span data-i18n="post.options.privacyPublic"></span>
                                                            </div>
                                                            <div class="menu-link-check-icon"></div>
                                                        </div>
                                                        
                                                        <div class="menu-link" data-action="post-set-privacy" data-value="friends">
                                                            <div class="menu-link-icon">
                                                                <span class="material-symbols-rounded">group</span>
                                                            </div>
                                                            <div class="menu-link-text">
                                                                <span data-i18n="post.options.privacyFriends"></span>
                                                            </div>
                                                            <div class="menu-link-check-icon"></div>
                                                        </div>
                                                        
                                                        <div class="menu-link" data-action="post-set-privacy" data-value="private">
                                                            <div class="menu-link-icon">
                                                                <span class="material-symbols-rounded">lock</span>
                                                            </div>
                                                            <div class="menu-link-text">
                                                                <span data-i18n="post.options.privacyPrivate"></span>
                                                            </div>
                                                            <div class="menu-link-check-icon"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            </div>
                                        <?php endif; ?>
                                        </div>

                                    <?php if (!empty($post['title']) && !$isPoll): ?>
                                        <div class="post-card-content" style="padding-bottom: 0;">
                                            <h3 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($post['text_content'])): ?>
                                        <div class="post-card-content" <?php if (!empty($post['title'])) echo 'style="padding-top: 8px;"'; ?>>
                                            <?php if ($isPoll): ?>
                                                <h3 class="poll-question"><?php echo htmlspecialchars($post['text_content']); ?></h3>
                                            <?php else: ?>
                                                <div>
                                                    <?php 
                                                    echo truncatePostText($post['text_content'], $post['id'], $basePath, 500); 
                                                    ?>
                                                </div>
                                            <?php endif; ?>
                                            </div>
                                    <?php endif; ?>

                                    <?php if ($isPoll && !empty($pollOptions)): ?>
                                        <div class="poll-container" id="poll-<?php echo $post['id']; ?>" data-poll-id="<?php echo $post['id']; ?>">
                                            <?php if ($hasVoted): ?>
                                                <div class="poll-results">
                                                    <?php foreach ($pollOptions as $option): 
                                                        $voteCount = (int)$option['vote_count'];
                                                        $percentage = ($totalVotes > 0) ? round(($voteCount / $totalVotes) * 100) : 0;
                                                        $isUserVote = ($option['id'] == $post['user_voted_option_id']);
                                                    ?>
                                                        <div class="poll-option-result <?php echo $isUserVote ? 'voted-by-user' : ''; ?>">
                                                            <div class="poll-option-bar" style="width: <?php echo $percentage; ?>%;"></div>
                                                            <div class="poll-option-text">
                                                                <span><?php echo htmlspecialchars($option['option_text']); ?></span>
                                                                <?php if ($isUserVote): ?>
                                                                    <span class="material-symbols-rounded poll-user-vote-icon">check_circle</span>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="poll-option-percent"><?php echo $percentage; ?>%</div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                    <p class="poll-total-votes" data-i18n="home.poll.totalVotes" data-count="<?php echo $totalVotes; ?>"><?php echo $totalVotes; ?> votos</p>
                                                </div>
                                            <?php else: ?>
                                                <form class="poll-form" data-action="submit-poll-vote">
                                                    <input type="hidden" name="publication_id" value="<?php echo $post['id']; ?>">
                                                    <?php foreach ($pollOptions as $option): ?>
                                                        <label class="poll-option-vote">
                                                            <input type="radio" name="poll_option_id" value="<?php echo $option['id']; ?>" required>
                                                            <span class="poll-option-radio"></span>
                                                            <span class="poll-option-text"><?php echo htmlspecialchars($option['option_text']); ?></span>
                                                        </label>
                                                    <?php endforeach; ?>
                                                    <div class="poll-form-actions">
                                                        <button type="submit" class="component-action-button component-action-button--primary" data-i18n="home.poll.voteButton">Votar</button>
                                                        <p class="poll-total-votes" data-i18n="home.poll.totalVotes" data-count="<?php echo $totalVotes; ?>"><?php echo $totalVotes; ?> votos</p>
                                                    </div>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!$isPoll && $attachmentCount > 0): ?>
                                    <div class="post-attachments-container" data-count="<?php echo $attachmentCount; ?>">
                                        <?php foreach ($attachments as $imgUrl): ?>
                                            <div class="post-attachment-item">
                                                <img src="<?php echo htmlspecialchars($imgUrl); ?>" alt="Adjunto de publicación" loading="lazy">
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="post-actions-container">
                                        <div class="post-actions-left">
                                            <button type="button" 
                                                    class="component-action-button--icon post-action-like <?php echo $userHasLiked ? 'active' : ''; ?>" 
                                                    data-tooltip="home.actions.like"
                                                    data-action="like-toggle"
                                                    data-post-id="<?php echo $post['id']; ?>">
                                        <span class="material-symbols-rounded"><?php echo $userHasLiked ? 'favorite' : 'favorite_border'; ?></span>
                                        <span class="action-text"><?php echo $likeCount; ?></span>
                                            </button>
                                            
                                            <button type="button"
                                               class="component-action-button--icon post-action-comment" 
                                               data-tooltip="home.actions.comment"
                                               data-action="toggleSectionPostView"
                                               data-post-id="<?php echo $post['id']; ?>">
                                                <span class="material-symbols-rounded">chat_bubble_outline</span>
                                                <span class="action-text"><?php echo $commentCount; ?></span>
                                            </button>
                                            <button type="button" class="component-action-button--icon" data-tooltip="home.actions.share">
                                                <span class="material-symbols-rounded">send</span>
                                            </button>
                                        </div>
                                        <div class="post-actions-right">
                                            <button type="button" 
                                                    class="component-action-button--icon post-action-bookmark <?php echo $userHasBookmarked ? 'active' : ''; ?>" 
                                                    data-tooltip="home.actions.save"
                                                    data-action="bookmark-toggle"
                                                    data-post-id="<?php echo $post['id']; ?>">
                                                <span class="material-symbols-rounded"><?php echo $userHasBookmarked ? 'bookmark' : 'bookmark_border'; ?></span>
                                            </button>
                                            </div>
                                    </div>
                                    
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
            
            <div class="profile-main-content <?php echo ($currentTab === 'amigos') ? 'active' : 'disabled'; ?>" data-profile-tab-content="amigos">
                <div class="component-card component-card--column">
                    <div class="profile-content-header">
                        <h2>Amigos</h2>
                        <div class="profile-content-header__actions">
                            <button type="button" class="page-toolbar-button">
                                <span class="material-symbols-rounded">search</span>
                            </button>
                            <button type="button" class="page-toolbar-button">
                                <span class="material-symbols-rounded">more_vert</span>
                            </button>
                        </div>
                    </div>

                    <div class="profile-friends-full-grid">
                        <?php if (empty($fullFriendList)): ?>
                            <p class="profile-friends-empty" data-i18n="friends.list.noFriends" style="grid-column: 1 / -1; padding: 32px 0;">No tiene amigos.</p>
                        <?php else: ?>
                            <?php foreach ($fullFriendList as $friend): ?>
                                <?php
                                $friendAvatar = $friend['profile_image_url'] ?? $defaultAvatar;
                                if(empty($friendAvatar)) $friendAvatar = "https://ui-avatars.com/api/?name=" . urlencode($friend['username']) . "&size=100&background=e0e0e0&color=ffffff";
                                $mutualCount = (int)($friend['mutual_friends_count'] ?? 0);
                                ?>
                                <div class="friend-item-card" data-user-id="<?php echo $friend['id']; ?>">
                                    <a href="<?php echo $basePath . '/profile/' . htmlspecialchars($friend['username']); ?>" data-nav-js="true" class="friend-item-card__avatar">
                                        <img src="<?php echo htmlspecialchars($friendAvatar); ?>" alt="<?php echo htmlspecialchars($friend['username']); ?>">
                                    </a>
                                    <div class="friend-item-card__info">
                                        <a href="<?php echo $basePath . '/profile/' . htmlspecialchars($friend['username']); ?>" data-nav-js="true" class="friend-item-card__name">
                                            <?php echo htmlspecialchars($friend['username']); ?>
                                        </a>
                                        <span class="friend-item-card__meta">
                                            <?php echo $mutualCount; ?> amigos en común
                                        </span>
                                    </div>
                                    <div class="friend-item-card__options">
                                        <button type="button" class="component-action-button--icon" data-action="toggleFriendItemOptions">
                                            <span class="material-symbols-rounded">more_vert</span>
                                        </button>
                                        <div class="popover-module popover-module--anchor-right body-title disabled" data-module="moduleFriendItemOptions">
                                            <div class="menu-content">
                                                <div class="menu-list">
                                                    <a class="menu-link" href="<?php echo $basePath . '/profile/' . htmlspecialchars($friend['username']); ?>" data-nav-js="true">
                                                        <div class="menu-link-icon"><span class="material-symbols-rounded">visibility</span></div>
                                                        <div class="menu-link-text"><span>Ver Perfil</span></div>
                                                    </a>
                                                    <?php // Aquí puedes añadir más lógica, ej. si es tu amigo o no ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="profile-main-content <?php echo ($currentTab === 'info') ? 'active' : 'disabled'; ?>" data-profile-tab-content="info" style="padding: 24px; text-align: center;">
                Aquí irá la sección de Información.
            </div>
            <div class="profile-main-content <?php echo ($currentTab === 'fotos') ? 'active' : 'disabled'; ?>" data-profile-tab-content="fotos" style="padding: 24px; text-align: center;">
                Aquí irá la sección de Fotos.
            </div>

        </div>
        
        </div>
</div>
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

// --- ▼▼▼ INICIO DE LÓGICA DE ESTADO (MODIFICADA) ▼▼▼ ---

$is_actually_online = false;
try {
    // 1. Consultar al servidor WebSocket quién está en línea
    // Usamos un timeout corto (0.5s) para no retrasar la carga de la página
    $context = stream_context_create(['http' => ['timeout' => 0.5]]); 
    $jsonResponse = @file_get_contents('http://127.0.0.1:8766/get-online-users', false, $context);
    
    if ($jsonResponse !== false) {
        $data = json_decode($jsonResponse, true);
        if (isset($data['status']) && $data['status'] === 'ok' && isset($data['online_users'])) {
            // 2. Comprobar si el ID del perfil está en la lista de IDs en línea
            $is_actually_online = in_array($profile['id'], $data['online_users']);
        }
    }
} catch (Exception $e) {
    logDatabaseError($e, 'view-profile - (ws_get_online_fail)');
    // Si el socket falla, $is_actually_online permanece 'false'.
}

// 3. Preparar el badge de estado
$statusBadgeHtml = '';
if ($is_actually_online) {
    // El WebSocket dice que está EN LÍNEA
    $statusBadgeHtml = '<div class="profile-status-badge online" data-user-id="' . htmlspecialchars($profile['id']) . '"><span class="status-dot"></span>Activo ahora</div>';
} elseif (!empty($profile['last_seen'])) {
    // El WebSocket dice que está OFFLINE, usamos 'last_seen' de la BD
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
// --- ▲▲▲ FIN DE LÓGICA DE ESTADO (MODIFICADA) ▲▲▲ ---

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
                        // --- ▼▼▼ INSERCIÓN DEL BADGE DE ESTADO (MODIFICADO) ▼▼▼ ---
                        // Ya no se usa la lógica de $profile['is_online'], se usa el HTML pre-calculado
                        echo $statusBadgeHtml; 
                        // --- ▲▲▲ FIN DE INSERCIÓN (MODIFICADO) ▲▲▲ ---
                        ?>
                    </div>

                    <p class="profile-meta">
                        Se unió el <?php echo date('d/m/Y', strtotime($profile['created_at'])); ?>
                    </p>
                </div>
                
                <div class="profile-tabs">
                    <?php
                        $postsUrl = $basePath . '/profile/' . htmlspecialchars($profile['username']);
                        $likesUrl = $postsUrl . '/likes';
                        $bookmarksUrl = $postsUrl . '/bookmarks';
                    ?>
                    <a href="<?php echo $postsUrl; ?>" data-nav-js="true" class="profile-tab <?php echo ($currentTab === 'posts') ? 'active' : ''; ?>">
                        <span data-i18n="create_publication.post">Publicaciones</span>
                    </a>
                    
                    <?php if ($isOwnProfile): // Mostrar solo en el perfil propio ?>
                        <a href="<?php echo $likesUrl; ?>" data-nav-js="true" class="profile-tab <?php echo ($currentTab === 'likes') ? 'active' : ''; ?>">
                            <span data-i18n="profile.tabs.likes">Favoritos</span>
                        </a>
                        <a href="<?php echo $bookmarksUrl; ?>" data-nav-js="true" class="profile-tab <?php echo ($currentTab === 'bookmarks') ? 'active' : ''; ?>">
                            <span data-i18n="profile.tabs.bookmarks">Guardados</span>
                        </a>
                    <?php endif; ?>
                </div>
                </div>
        </div>

        <div class="card-list-container">
            
            <?php if (!empty($publications)): ?>
                <?php foreach ($publications as $post): ?>
                    <?php
                    // Lógica de datos (copiada de home.php)
                    $postAvatar = $post['profile_image_url'] ?? $defaultAvatar;
                    if (empty($postAvatar)) $postAvatar = $defaultAvatar;
                    $postRole = $post['role'] ?? 'user';

                    $attachments = [];
                    if (!empty($post['attachments'])) {
                        $attachments = explode(',', $post['attachments']);
                    }
                    $attachmentCount = count($attachments);

                    // --- Variables para Encuesta ---
                    $isPoll = $post['post_type'] === 'poll';
                    $hasVoted = $post['user_voted_option_id'] !== null;
                    $totalVotes = (int)($post['total_votes'] ?? 0);
                    $pollOptions = $post['poll_options'] ?? [];

                    // --- Variables para Like/Comment ---
                    $likeCount = (int)($post['like_count'] ?? 0);
                    $userHasLiked = (int)($post['user_has_liked'] ?? 0) > 0;
                    $commentCount = (int)($post['comment_count'] ?? 0);
                    $userHasBookmarked = (int)($post['user_has_bookmarked'] ?? 0) > 0;
                    ?>
                    <div class="component-card component-card--post component-card--column" data-post-id="<?php echo $post['id']; ?>">
                        
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
                            </div>
                            <?php endif; ?>
                            </div>

                        <?php if (!empty($post['text_content'])): ?>
                            <div class="post-card-content">
                                <?php if ($isPoll): ?>
                                    <h3 class="poll-question"><?php echo htmlspecialchars($post['text_content']); ?></h3>
                                <?php else: ?>
                                    <div>
                                        <?php 
                                        // Usamos la nueva función helper
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
            <?php else: ?>
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
                                <h2 class="component-card__title" data-i18n="profile.noLikes.title">Sin Favoritos</h2>
                                <p class="component-card__description" data-i18n="profile.noLikes.desc">Aún no te ha gustado ninguna publicación. ¡Explora y reacciona al contenido!</p>
                            <?php elseif ($currentTab === 'bookmarks'): ?>
                                <h2 class="component-card__title" data-i18n="profile.noBookmarks.title">Sin Guardados</h2>
                                <p class="component-card__description" data-i18n="profile.noBookmarks.desc">Aún no has guardado ninguna publicación. Toca el ícono de guardar para verla aquí más tarde.</p>
                            <?php else: ?>
                                <h2 class="component-card__title" data-i18n="profile.noPosts.title">Sin publicaciones</h2>
                                <?php if ($isOwnProfile): ?>
                                    <p class="component-card__description" data-i18n="profile.noPosts.descSelf">Aún no has publicado nada. ¡Comparte algo con la comunidad!</p>
                                <?php else: ?>
                                    <p class="component-card__description" data-i18n="profile.noPosts.descOther"><?php echo htmlspecialchars($profile['username']); ?> aún no ha publicado nada.</p>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

        </div>
    </div>
    
    <div class="popover-module body-title disabled" data-module="modulePostOptions">
        <div class="menu-content">
            <div class="menu-list">
                <div class="menu-link" data-action="toggle-post-privacy">
                    <div class="menu-link-icon">
                        <span class="material-symbols-rounded">visibility</span>
                    </div>
                    <div class="menu-link-text">
                        <span data-i18n="post.options.changePrivacy">Cambiar privacidad</span>
                    </div>
                </div>
                <div class="menu-link" data-action="post-delete">
                    <div class="menu-link-icon">
                        <span class="material-symbols-rounded" style="color: #c62828;">delete</span>
                    </div>
                    <div class="menu-link-text">
                        <span data-i18n="post.options.delete" style="color: #c62828;">Eliminar publicación</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="popover-module body-title disabled" data-module="modulePostPrivacy">
        <div class="menu-content">
            <div class="menu-list">
                <div class="menu-header" data-i18n="post.options.privacyTitle">Seleccionar Privacidad</div>
                
                <div class="menu-link" data-action="post-set-privacy" data-value="public">
                    <div class="menu-link-icon">
                        <span class="material-symbols-rounded">public</span>
                    </div>
                    <div class="menu-link-text">
                        <span data-i18n="post.options.privacyPublic">Público</span>
                    </div>
                    <div class="menu-link-check-icon"></div>
                </div>
                
                <div class="menu-link" data-action="post-set-privacy" data-value="friends">
                    <div class="menu-link-icon">
                        <span class="material-symbols-rounded">group</span>
                    </div>
                    <div class="menu-link-text">
                        <span data-i18n="post.options.privacyFriends">Solo amigos</span>
                    </div>
                    <div class="menu-link-check-icon"></div>
                </div>
                
                <div class="menu-link" data-action="post-set-privacy" data-value="private">
                    <div class="menu-link-icon">
                        <span class="material-symbols-rounded">lock</span>
                    </div>
                    <div class="menu-link-text">
                        <span data-i18n="post.options.privacyPrivate">Solo yo</span>
                    </div>
                    <div class="menu-link-check-icon"></div>
                </div>
            </div>
        </div>
    </div>
    
    </div>
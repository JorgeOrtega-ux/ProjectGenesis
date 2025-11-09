<?php
// FILE: includes/sections/main/home.php
// (CÓDIGO MODIFICADO PARA MOSTRAR PUBLICACIONES, ENCUESTAS, LIKES Y COMENTARIOS)

global $pdo, $basePath; // <-- ¡AÑADIR $basePath!
$defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";
$userAvatar = $_SESSION['profile_image_url'] ?? $defaultAvatar;
$userId = $_SESSION['user_id']; // ID del usuario actual

$publications = [];
$currentCommunityId = null;
$currentCommunityNameKey = "home.popover.mainFeed"; // Default
$communityUuid = $_GET['community_uuid'] ?? null;

try {
    if ($communityUuid) {
        // --- VISTA DE COMUNIDAD FILTRADA ---
        $stmt_comm = $pdo->prepare("SELECT id, name FROM communities WHERE uuid = ?");
        $stmt_comm->execute([$communityUuid]);
        $community = $stmt_comm->fetch();
        
        if ($community) {
            $currentCommunityId = $community['id'];
            $currentCommunityNameKey = $community['name']; 
            
            // --- ▼▼▼ INICIO DE SQL MODIFICADO (AÑADIDA LÓGICA DE AMIGOS) ▼▼▼ ---
            $sql_posts = 
                "SELECT 
                    p.*, 
                    u.username, 
                    u.profile_image_url,
                    u.role,
                    p.title, 
                    p.privacy_level, -- <-- ¡AÑADIDO!
                    (SELECT GROUP_CONCAT(pf.public_url SEPARATOR ',') 
                     FROM publication_attachments pa
                     JOIN publication_files pf ON pa.file_id = pf.id
                     WHERE pa.publication_id = p.id
                     ORDER BY pa.sort_order ASC
                    ) AS attachments,
                    (SELECT COUNT(pv.id) FROM poll_votes pv WHERE pv.publication_id = p.id) AS total_votes,
                    (SELECT pv.poll_option_id FROM poll_votes pv WHERE pv.publication_id = p.id AND pv.user_id = ?) AS user_voted_option_id,
                    
                    (SELECT COUNT(*) FROM publication_likes pl WHERE pl.publication_id = p.id) AS like_count,
                    (SELECT COUNT(*) FROM publication_likes pl WHERE pl.publication_id = p.id AND pl.user_id = ?) AS user_has_liked,
                    (SELECT COUNT(*) FROM publication_bookmarks pb WHERE pb.publication_id = p.id AND pb.user_id = ?) AS user_has_bookmarked,
                    (SELECT COUNT(*) FROM publication_comments pc WHERE pc.publication_id = p.id) AS comment_count

                 FROM community_publications p
                 JOIN users u ON p.user_id = u.id
                 WHERE p.community_id = ?
                 AND p.post_status = 'active'
                 AND (
                     p.privacy_level = 'public' -- Ver posts públicos
                     OR (p.privacy_level = 'friends' AND ( -- O ver posts de amigos si:
                         p.user_id = ? -- 1. Yo soy el autor
                         OR p.user_id IN ( -- 2. El autor es mi amigo
                             (SELECT user_id_2 FROM friendships WHERE user_id_1 = ? AND status = 'accepted')
                             UNION
                             (SELECT user_id_1 FROM friendships WHERE user_id_2 = ? AND status = 'accepted')
                         )
                     ))
                     OR (p.privacy_level = 'private' AND p.user_id = ?) -- <-- ¡AÑADIDO! Ver mis posts privados
                 )
                 ORDER BY p.created_at DESC
                 LIMIT 50";
            
            $stmt_posts = $pdo->prepare($sql_posts);
            // ¡Ahora se pasan 8 parámetros!
            $stmt_posts->execute([$userId, $userId, $userId, $currentCommunityId, $userId, $userId, $userId, $userId]);
            $publications = $stmt_posts->fetchAll();
            // --- ▲▲▲ FIN DE SQL MODIFICADO ▲▲▲ ---
        } else {
            $communityUuid = null;
        }
    }
    
    if ($communityUuid === null) {
        // --- VISTA DE FEED PRINCIPAL ---
        // --- ▼▼▼ INICIO DE SQL MODIFICADO (AÑADIDA LÓGICA DE AMIGOS) ▼▼▼ ---
        $sql_posts = 
            "SELECT 
                p.*, 
                u.username, 
                u.profile_image_url, 
                u.role,
                p.title, 
                p.privacy_level, -- <-- ¡AÑADIDO!
                c.name AS community_name,
                (SELECT GROUP_CONCAT(pf.public_url SEPARATOR ',') 
                 FROM publication_attachments pa
                 JOIN publication_files pf ON pa.file_id = pf.id
                 WHERE pa.publication_id = p.id
                 ORDER BY pa.sort_order ASC
                ) AS attachments,
                (SELECT COUNT(pv.id) FROM poll_votes pv WHERE pv.publication_id = p.id) AS total_votes,
                (SELECT pv.poll_option_id FROM poll_votes pv WHERE pv.publication_id = p.id AND pv.user_id = ?) AS user_voted_option_id,

                (SELECT COUNT(*) FROM publication_likes pl WHERE pl.publication_id = p.id) AS like_count,
                (SELECT COUNT(*) FROM publication_likes pl WHERE pl.publication_id = p.id AND pl.user_id = ?) AS user_has_liked,
                (SELECT COUNT(*) FROM publication_bookmarks pb WHERE pb.publication_id = p.id AND pb.user_id = ?) AS user_has_bookmarked,
                (SELECT COUNT(*) FROM publication_comments pc WHERE pc.publication_id = p.id) AS comment_count

             FROM community_publications p
             JOIN users u ON p.user_id = u.id
             LEFT JOIN communities c ON p.community_id = c.id
             WHERE p.community_id IS NOT NULL 
             AND (c.privacy = 'public' OR p.community_id IN (SELECT community_id FROM user_communities WHERE user_id = ?)) -- <-- ¡MODIFICADO! Ver posts de mis grupos privados
             AND p.post_status = 'active'
             AND (
                 p.privacy_level = 'public' -- Ver posts públicos
                 OR (p.privacy_level = 'friends' AND ( -- O ver posts de amigos si:
                     p.user_id = ? -- 1. Yo soy el autor
                     OR p.user_id IN ( -- 2. El autor es mi amigo
                         (SELECT user_id_2 FROM friendships WHERE user_id_1 = ? AND status = 'accepted')
                         UNION
                         (SELECT user_id_1 FROM friendships WHERE user_id_2 = ? AND status = 'accepted')
                     )
                 ))
                 OR (p.privacy_level = 'private' AND p.user_id = ?) -- <-- ¡AÑADIDO! Ver mis posts privados
             )
             ORDER BY p.created_at DESC
             LIMIT 50";
        
        $stmt_posts = $pdo->prepare($sql_posts);
         // ¡Ahora se pasan 8 parámetros!
        $stmt_posts->execute([$userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId]);
        $publications = $stmt_posts->fetchAll();
        // --- ▲▲▲ FIN DE SQL MODIFICADO ▲▲▲ ---
    }
    
    // --- Bucle para cargar opciones de encuestas ---
    if (!empty($publications)) {
        $pollIds = [];
        foreach ($publications as $key => $post) {
            if ($post['post_type'] === 'poll') {
                $pollIds[] = $post['id'];
            }
        }
        
        if (!empty($pollIds)) {
            $placeholders = implode(',', array_fill(0, count($pollIds), '?'));
            
        $stmt_options = $pdo->prepare(
               "SELECT 
                    po.publication_id, 
                    po.id, 
                    po.option_text, 
                    COUNT(pv.id) AS vote_count
                FROM poll_options po
                LEFT JOIN poll_votes pv ON po.id = pv.poll_option_id
                WHERE po.publication_id IN ($placeholders)
                GROUP BY po.publication_id, po.id, po.option_text 
                ORDER BY po.id ASC"
            );
            $stmt_options->execute($pollIds);
            $options = $stmt_options->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);

            // Adjuntar opciones a sus publicaciones
            foreach ($publications as $key => $post) {
                if (isset($options[$post['id']])) {
                    $publications[$key]['poll_options'] = $options[$post['id']];
                } else {
                    $publications[$key]['poll_options'] = [];
                }
            }
        }
    }
    
} catch (PDOException $e) {
    logDatabaseError($e, 'home.php - fetch posts');
    $publications = [];
}

?>
<div class="section-content overflow-y <?php echo ($CURRENT_SECTION === 'home') ? 'active' : 'disabled'; ?>" data-section="home">
    
    <div class="page-toolbar-container" id="home-toolbar-container">
        <div class="page-toolbar-floating">
            <div class="toolbar-action-default">
                
                <div class="page-toolbar-left">
                    
                    <div id="current-group-display" 
                         class="page-toolbar-group-display active" 
                         data-i18n="<?php echo ($communityUuid === null) ? $currentCommunityNameKey : ''; ?>" 
                         data-community-id="<?php echo ($communityUuid === null) ? 'main_feed' : $currentCommunityId; ?>">
                        <?php echo ($communityUuid !== null) ? htmlspecialchars($currentCommunityNameKey) : ''; ?>
                    </div>
                    
                    <button type="button"
                        class="page-toolbar-button"
                        data-action="home-select-group"
                        data-tooltip="home.toolbar.selectGroup">
                        <span class="material-symbols-rounded">group</span>
                    </button>
                    <button type="button"
                        class="page-toolbar-button"
                        data-action="toggleSectionJoinGroup" 
                        data-tooltip="home.toolbar.joinGroup">
                    <span class="material-symbols-rounded">group_add</span>
                    </button>
                    
                    <button type="button"
                        class="page-toolbar-button"
                        data-action="toggleModuleCreatePost" 
                        data-tooltip="home.toolbar.createPost">
                    <span class="material-symbols-rounded">add</span>
                    </button>
                    </div>
                
                </div>

            </div>
            
        <div class="popover-module popover-module--anchor-left body-title disabled" data-module="moduleSelectGroup">
            <div class="menu-content">
                <div class="menu-header" data-i18n="home.popover.title">Mis Grupos</div>
                <div class="menu-list" id="my-groups-list">
                    <div class="menu-link" data-i18n="home.popover.loading">Cargando...</div>
                </div>
            </div>
        </div>
        
        <div class="popover-module popover-module--anchor-left body-title disabled" data-module="moduleCreatePost">
            <div class="menu-content">
                <div class="menu-list">
                    <div class="menu-link" data-action="toggleSectionCreatePublication">
                        <div class="menu-link-icon">
                            <span class="material-symbols-rounded">post_add</span>
                        </div>
                        <div class="menu-link-text">
                            <span data-i18n="home.popover.newPost">Crear publicación</span>
                        </div>
                    </div>
                    <div class="menu-link" data-action="toggleSectionCreatePoll">
                        <div class="menu-link-icon">
                            <span class="material-symbols-rounded">poll</span>
                        </div>
                        <div class="menu-link-text">
                            <span data-i18n="home.popover.newPoll">Crear encuesta</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>

    <div class="component-wrapper">

        <div class="card-list-container">
            <?php if (empty($publications)): ?>
                <div class="component-card component-card--column">
                    <div class="component-card__content">
                        <div class="component-card__text">
                            <h2 class="component-card__title" data-i18n="home.main.noPosts"></h2>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <?php foreach ($publications as $post): ?>
                    <?php
                    // --- ▼▼▼ ¡INICIO DE CÓDIGO PEGADO Y MODIFICADO! ▼▼▼ ---
                    
                    // Lógica de datos
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

                    // --- Variables para Like/Comment/Bookmark ---
                    $likeCount = (int)($post['like_count'] ?? 0);
                    $userHasLiked = (int)($post['user_has_liked'] ?? 0) > 0;
                    $commentCount = (int)($post['comment_count'] ?? 0);
                    $userHasBookmarked = (int)($post['user_has_bookmarked'] ?? 0) > 0; 
                    
                    // --- ▼▼▼ INICIO DE NUEVA LÓGICA DE PRIVACIDAD ▼▼▼ ---
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
                    // --- ▲▲▲ FIN DE NUEVA LÓGICA DE PRIVACIDAD ▲▲▲ ---
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
                                        
                                        <span class="post-privacy-icon" data-tooltip="<?php echo $privacyTooltipKey; ?>">
                                            <span class="material-symbols-rounded"><?php echo $privacyIcon; ?></span>
                                        </span>
                                        </p>
                                </div>
                            </div>
                            
                            </div>

                        <?php // --- ▼▼▼ INICIO DE BLOQUE MODIFICADO (TÍTULO) ▼▼▼ --- ?>
                        <?php if (!empty($post['title']) && !$isPoll): ?>
                            <div class="post-card-content" style="padding-bottom: 0;">
                                <h3 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                            </div>
                        <?php endif; ?>
                        <?php // --- ▲▲▲ FIN DE BLOQUE MODIFICADO ▲▲▲ --- ?>


                        <?php if (!empty($post['text_content'])): ?>
                            <div class="post-card-content" <?php if (!empty($post['title'])) echo 'style="padding-top: 8px;"'; ?>>
                                <?php if ($isPoll): ?>
                                    <h3 class="poll-question"><?php echo htmlspecialchars($post['text_content']); ?></h3>
                                <?php else: ?>
                                    <div>
                                        <?php 
                                        // Usamos la nueva función helper
                                        // (Se asume que config/utilities.php donde está la función ya fue cargado por config.php)
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
                    <?php
                    // --- ▲▲▲ FIN DE CÓDIGO PEGADO ▲▲▲ ---
                    ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
</div>
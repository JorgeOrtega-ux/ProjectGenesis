<?php
// FILE: includes/sections/main/home.php
// (LÓGICA DE DATOS MOVIDA A home_feed_fetcher.php)

// Estas variables ahora son cargadas por config/routing/router.php:
// $publications, $currentCommunityId, $currentCommunityNameKey, $communityUuid

// Variables globales y de sesión aún necesarias para el templating
global $pdo, $basePath; 
$defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";
$userAvatar = $_SESSION['profile_image_url'] ?? $defaultAvatar;
$userId = $_SESSION['user_id']; // ID del usuario actual

// --- ¡EL BLOQUE TRY...CATCH DE SQL (LÍNEAS 12-156) SE HA ELIMINADO DE AQUÍ! ---

?>
<style>
    .post-hashtag-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 12px;
    }
    .post-hashtag-link {
        display: inline-block;
        padding: 4px 12px;
        font-size: 13px;
        font-weight: 600;
        color: #0056b3; /* Color de enlace */
        background-color: #f0f5fa; /* Fondo azul claro */
        border-radius: 50px;
        text-decoration: none;
        transition: background-color 0.2s;
    }
    .post-hashtag-link:hover {
        background-color: #e0eafc;
        text-decoration: underline;
    }
</style>
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
                    
                    <?php // --- BOTÓN "CREAR PUBLICACIÓN" ELIMINADO DE AQUÍ --- ?>
                    
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
                    // --- (Lógica de renderizado de post) ---
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
                    $privacyTooltipKey = 'post.privacy.public';
                    
                    if ($privacyLevel === 'friends') {
                        $privacyTooltipKey = 'post.privacy.friends';
                    } elseif ($privacyLevel === 'private') {
                        $privacyTooltipKey = 'post.privacy.private';
                    }
                    
                    $isOwner = ($post['user_id'] == $userId);

                    $hashtags = [];
                    if (!empty($post['hashtags'])) {
                        $hashtags = explode(',', $post['hashtags']);
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
                                    
                                    <div class="profile-meta" style="padding: 0; margin-top: 4px; gap: 8px;">
                                        <div class="profile-meta-badge">
                                            <span><?php echo date('d/m/Y H:i', strtotime($post['created_at'])); ?></span>
                                        </div>
                                        
                                        <div class="profile-meta-badge" data-tooltip="<?php echo $privacyTooltipKey; ?>">
                                            <span data-i18n="<?php echo $privacyTooltipKey; ?>"></span>
                                        </div>
                                        
                                        <?php if (isset($post['community_name']) && $post['community_name']): ?>
                                            <div class="profile-meta-badge">
                                                <span class="material-symbols-rounded">group</span>
                                                <span style="font-weight: 600;"><?php echo htmlspecialchars($post['community_name']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                </div>
                            </div>
                            
                            <?php if ($isOwner): ?>
                            <div class="post-card-options">
                                </div>
                            <?php endif; ?>
                            </div>

                        <?php if (!empty($post['title']) && !$isPoll): ?>
                            <?php endif; ?>

                        <?php if (!empty($post['text_content'])): ?>
                            <?php endif; ?>
                        
                        <?php if (!empty($hashtags)): ?>
                            <div class="post-card-content" style="padding-top: 0; <?php if(empty($post['text_content'])) echo 'padding-top: 12px;'; ?>">
                                <div class="post-hashtag-list">
                                    <?php foreach ($hashtags as $tag): ?>
                                        <a href="<?php echo $basePath . '/search?q=' . urlencode('#' . htmlspecialchars($tag)); ?>" 
                                           class="post-hashtag-link" 
                                           data-nav-js="true">
                                            #<?php echo htmlspecialchars($tag); ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($isPoll && !empty($pollOptions)): ?>
                            <?php endif; ?>
                        
                        <?php if (!$isPoll && $attachmentCount > 0): ?>
                        <div class="post-attachments-container" data-count="<?php echo $attachmentCount; ?>">
                            </div>
                        <?php endif; ?>
                        
                        <div class="post-actions-container">
                            </div>
                        
                        </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
</div>
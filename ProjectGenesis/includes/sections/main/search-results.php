<?php
// FILE: includes/sections/main/search-results.php

global $pdo, $basePath;
$defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";
$userId = $_SESSION['user_id'];

// Estas variables ($searchQuery, $userResults, $postResults)
// son cargadas por config/routing/router.php
if (!isset($searchQuery)) $searchQuery = '';
if (!isset($userResults)) $userResults = [];
if (!isset($postResults)) $postResults = [];

$hasResults = !empty($userResults) || !empty($postResults);
?>

<div class="section-content overflow-y <?php echo ($CURRENT_SECTION === 'search-results') ? 'active' : 'disabled'; ?>" data-section="search-results">

    <div class="page-toolbar-container" id="search-results-toolbar-container">
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
            </div>
        </div>
    </div>
    
    <div class="component-wrapper" style="padding-top: 82px;">

        <div class="component-header-card">
            <h1 class="component-page-title">
                Resultados para "<?php echo htmlspecialchars($searchQuery); ?>"
            </h1>
        </div>

        <?php if (!$hasResults): ?>
            <div class="component-card">
                <div class="component-card__content" style="padding: 40px 24px; justify-content: center; text-align: center;">
                    <div class="component-card__icon">
                         <span class="material-symbols-rounded">search_off</span>
                    </div>
                    <div class="component-card__text">
                        <h2 class="component-card__title" style="font-size: 18px;" data-i18n="header.search.noResults"></h2>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($userResults)): ?>
            <div class="component-card component-card--column" style="gap: 8px;">
                <h2 class="component-card__title" style="font-size: 18px; padding: 8px 8px 0 8px;" data-i18n="header.search.people">Personas</h2>
                <div class="card-list-container" style="padding: 0 8px 8px 8px;">
                    <?php foreach ($userResults as $user): ?>
                         <a href="<?php echo $basePath . '/profile/' . htmlspecialchars($user['username']); ?>" 
                           data-nav-js="true" 
                           class="card-item" 
                           style="gap: 16px; padding: 16px; text-decoration: none; color: inherit;">
                        
                            <div class="component-card__avatar" style="width: 50px; height: 50px; flex-shrink: 0;" data-role="<?php echo htmlspecialchars($user['role']); ?>">
                                <img src="<?php echo htmlspecialchars($user['avatarUrl']); ?>"
                                    alt="<?php echo htmlspecialchars($user['username']); ?>"
                                    class="component-card__avatar-image">
                            </div>

                            <div class="card-item-details">
                                <div class="card-detail-item card-detail-item--full" style="border: none; padding: 0; background: none;">
                                    <span class="card-detail-value" style="font-size: 16px; font-weight: 600;"><?php echo htmlspecialchars($user['username']); ?></span>
                                </div>
                                <div class="card-detail-item">
                                    <span class="card-detail-label" data-i18n="admin.users.labelRole"></span>
                                    <span class="card-detail-value"><?php echo htmlspecialchars(ucfirst($user['role'])); ?></span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($postResults)): ?>
            <div class="component-card component-card--column" style="gap: 8px; padding: 16px;">
                <h2 class="component-card__title" style="font-size: 18px; padding-bottom: 8px;" data-i18n="header.search.posts">Publicaciones</h2>
                <div class="card-list-container" style="display: flex; flex-direction: column; gap: 16px;">
                    <?php foreach ($postResults as $post): ?>
                        <?php
                        // Copiamos la lógica de home.php para renderizar el post
                        $postAvatar = $post['profile_image_url'] ?? $defaultAvatar;
                        if (empty($postAvatar)) $postAvatar = $defaultAvatar;
                        $postRole = $post['role'] ?? 'user';
                        ?>
                        <a href="<?php echo $basePath . '/post/' . $post['id']; ?>" 
                           data-nav-js="true" 
                           class="component-card component-card--post" 
                           style="padding: 16px; align-items: stretch; flex-direction: column; text-decoration: none; color: inherit; border: 1px solid #00000020;" 
                           data-post-id="<?php echo $post['id']; ?>">
                            
                            <div class="post-card-header" style="padding: 0 0 12px 0;">
                                <div class="component-card__content" style="gap: 12px; padding-bottom: 0; border-bottom: none;">
                                    <div class="component-card__avatar" data-role="<?php echo htmlspecialchars($postRole); ?>" style="width: 40px; height: 40px; flex-shrink: 0;">
                                        <img src="<?php echo htmlspecialchars($postAvatar); ?>" alt="<?php echo htmlspecialchars($post['username']); ?>" class="component-card__avatar-image">
                                    </div>
                                    <div class="component-card__text">
                                        <h2 class="component-card__title" style="font-size: 16px;"><?php echo htmlspecialchars($post['username']); ?></h2>
                                        <p class="component-card__description" style="font-size: 13px;">
                                            <?php echo date('d/m/Y H:i', strtotime($post['created_at'])); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="post-card-content" style="padding: 0;">
                                <p style="font-size: 15px; line-height: 1.6; color: #1f2937; white-space: pre-wrap; width: 100%;">
                                    <?php echo htmlspecialchars(mb_substr($post['text_content'], 0, 300)); ?>
                                    <?php if (mb_strlen($post['text_content']) > 300): ?>
                                        ...
                                    <?php endif; ?>
                                </p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>
<?php
// FILE: includes/sections/main/search-results.php

global $pdo, $basePath;
$defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";
$userId = $_SESSION['user_id'];

// Estas variables ($searchQuery, $userResults, $postResults, $communityResults)
// son cargadas por config/routing/router.php
if (!isset($searchQuery)) $searchQuery = '';
if (!isset($userResults)) $userResults = [];
if (!isset($postResults)) $postResults = [];
if (!isset($communityResults)) $communityResults = []; // <-- AÑADIDO

$hasResults = !empty($userResults) || !empty($postResults) || !empty($communityResults); // <-- ACTUALIZADO
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
                
                <div class="page-toolbar-right">
                    <button type="button"
                        class="page-toolbar-button"
                        data-action="toggleModuleSearchFilter" 
                        data-tooltip="header.search.filter.tooltip">
                        <span class="material-symbols-rounded">filter_list</span>
                    </button>
                </div>
                </div>
        </div>

        <div class="popover-module body-title disabled" data-module="moduleSearchFilter">
            <div class="menu-content">
                <div class="menu-list">
                    <div class="menu-header" data-i18n="header.search.filter.title">Filtrar por</div>
                    
                    <div class="menu-link active" data-action="search-set-filter" data-filter="all">
                        <div class="menu-link-icon"><span class="material-symbols-rounded">select_all</span></div>
                        <div class="menu-link-text"><span data-i18n="header.search.filter.all">Todos</span></div>
                        <div class="menu-link-check-icon"><span class="material-symbols-rounded">check</span></div>
                    </div>
                    
                    <div class="menu-link" data-action="search-set-filter" data-filter="people">
                        <div class="menu-link-icon"><span class="material-symbols-rounded">person</span></div>
                        <div class="menu-link-text"><span data-i18n="header.search.people">Personas</span></div>
                        <div class="menu-link-check-icon"></div>
                    </div>
                    
                    <div class="menu-link" data-action="search-set-filter" data-filter="posts">
                        <div class="menu-link-icon"><span class="material-symbols-rounded">post</span></div>
                        <div class="menu-link-text"><span data-i18n="header.search.posts">Publicaciones</span></div>
                        <div class="menu-link-check-icon"></div>
                    </div>
                    
                    <div class="menu-link" data-action="search-set-filter" data-filter="communities">
                        <div class="menu-link-icon"><span class="material-symbols-rounded">groups</span></div>
                        <div class="menu-link-text"><span data-i18n="header.search.communities">Comunidades</span></div>
                        <div class="menu-link-check-icon"></div>
                    </div>
                    </div>
            </div>
        </div>
        </div>
    
    <div class="component-wrapper">

        <div class="component-header-card">
            <h1 class="component-page-title">
                Resultados para "<?php echo htmlspecialchars($searchQuery); ?>"
            </h1>
        </div>

        <div class="component-card component-card--column" id="search-no-results-card" <?php if ($hasResults) echo 'style="display: none;"'; ?>>
            <div class="component-card__content">
                <div class="component-card__icon">
                     <span class="material-symbols-rounded">search_off</span>
                </div>
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="header.search.noResults"></h2>
                </div>
            </div>
        </div>
        
        <div id="search-results-users" <?php if (empty($userResults)) echo 'style="display: none;"'; ?>>
            <?php if (!empty($userResults)): ?>
                <div class="component-card component-card--column">
                    <h2 class="component-card__title" data-i18n="header.search.people">Personas</h2>
                    <div class="card-list-container">
                        <?php foreach ($userResults as $user): ?>
                             <a href="<?php echo $basePath . '/profile/' . htmlspecialchars($user['username']); ?>" 
                               data-nav-js="true" 
                               class="card-item">
                            
                                <div class="component-card__avatar" data-role="<?php echo htmlspecialchars($user['role']); ?>">
                                    <img src="<?php echo htmlspecialchars($user['avatarUrl']); ?>"
                                        alt="<?php echo htmlspecialchars($user['username']); ?>"
                                        class="component-card__avatar-image">
                                </div>

                                <div class="card-item-details">
                                    <div class="card-detail-item card-detail-item--full">
                                        <span class="card-detail-value"><?php echo htmlspecialchars($user['username']); ?></span>
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
        </div>

        <div id="search-results-communities" <?php if (empty($communityResults)) echo 'style="display: none;"'; ?>>
            <?php if (!empty($communityResults)): ?>
                <div class="component-card component-card--column">
                    <h2 class="component-card__title" data-i18n="header.search.communities">Comunidades</h2>
                    <div class="card-list-container">
                        <?php foreach ($communityResults as $community): ?>
                             <a href="<?php echo $basePath . '/c/' . htmlspecialchars($community['uuid']); ?>" 
                               data-nav-js="true" 
                               class="card-item">
                            
                                <div class="component-card__avatar" style="width: 50px; height: 50px; flex-shrink: 0; border-radius: 8px;">
                                    <img src="<?php echo htmlspecialchars($community['icon_url']); ?>"
                                        alt="<?php echo htmlspecialchars($community['name']); ?>"
                                        class="component-card__avatar-image"
                                        style="border-radius: 8px;">
                                </div>

                                <div class="card-item-details">
                                    <div class="card-detail-item card-detail-item--full">
                                        <span class="card-detail-value"><?php echo htmlspecialchars($community['name']); ?></span>
                                    </div>
                                    <div class="card-detail-item">
                                        <span class="card-detail-label" data-i18n="admin.communities.labelMembers"></span>
                                        <span class="card-detail-value"><?php echo htmlspecialchars($community['member_count']); ?></span>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div id="search-results-posts" <?php if (empty($postResults)) echo 'style="display: none;"'; ?>>
            <?php if (!empty($postResults)): ?>
                <div class="component-card component-card--column">
                    <h2 class="component-card__title" data-i18n="header.search.posts">Publicaciones</h2>
                    <div class="card-list-container">
                        <?php foreach ($postResults as $post): ?>
                            <?php
                            $postAvatar = $post['profile_image_url'] ?? $defaultAvatar;
                            if (empty($postAvatar)) $postAvatar = $defaultAvatar;
                            $postRole = $post['role'] ?? 'user';
                            
                            // --- ▼▼▼ INICIO DE BLOQUE AÑADIDO (Lógica de Privacidad) ▼▼▼ ---
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
                            // --- ▲▲▲ FIN DE BLOQUE AÑADIDO ▲▲▲ ---
                            ?>
                            <a href="<?php echo $basePath . '/post/' . $post['id']; ?>" 
                               data-nav-js="true" 
                               class="component-card component-card--post component-card--column" 
                               data-post-id="<?php echo $post['id']; ?>">
                                
                                <div class="post-card-header">
                                    <div class="component-card__content">
                                        <div class="component-card__avatar" data-role="<?php echo htmlspecialchars($postRole); ?>">
                                            <img src="<?php echo htmlspecialchars($postAvatar); ?>" alt="<?php echo htmlspecialchars($post['username']); ?>" class="component-card__avatar-image">
                                        </div>
                                        
                                        <?php // --- ▼▼▼ INICIO DE BLOQUE MODIFICADO (Badges) ▼▼▼ --- ?>
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
                                        <?php // --- ▲▲▲ FIN DE BLOQUE MODIFICADO ▲▲▲ --- ?>

                                    </div>
                                </div>

                                <?php if (!empty($post['title'])): ?>
                                    <div class="post-card-content" style="padding-bottom: 0;">
                                        <h3 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                                    </div>
                                <?php endif; ?>

                                <div class="post-card-content" <?php if (!empty($post['title'])) echo 'style="padding-top: 8px;"'; ?>>
                                    <p>
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
</div>
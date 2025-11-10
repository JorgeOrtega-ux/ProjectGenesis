<?php
// FILE: includes/sections/main/profile-tabs/view-profile-posts.php
// (CORREGIDO - Añadidas variables faltantes para carga parcial)

// --- ▼▼▼ INICIO DE BLOQUE AÑADIDO ▼▼▼ ---
// --- Definir variables globales y de sesión ---
global $pdo, $basePath;
$defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";
$userId = $_SESSION['user_id'] ?? 0;
$userAvatar = $_SESSION['profile_image_url'] ?? $defaultAvatar;
// --- ▲▲▲ FIN DE BLOQUE AÑADIDO ▲▲▲ ---


// --- Estas variables vienen del 'view-profile.php' principal (en carga completa) O de router.php (en carga parcial) ---
// $profile (datos del perfil)
// $isOwnProfile (booleano)
// $currentTab ('posts', 'likes', 'bookmarks')

// --- Estos datos fueron cargados por router.php específicamente para esta pestaña ---
$publications = $viewProfileData['publications'] ?? [];
$profileFriends = $viewProfileData['profile_friends_preview'] ?? [];
$friendCount = $viewProfileData['friend_count'] ?? 0;
?>

<div class="profile-main-content active" data-profile-tab-content="posts">
                
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
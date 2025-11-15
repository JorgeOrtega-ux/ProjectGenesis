<?php
// FILE: includes/sections/main/view-post.php (NUEVO ARCHIVO)

// $viewPostData se carga desde config/routing/router.php
global $pdo, $basePath;
$defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";
$userAvatar = $_SESSION['profile_image_url'] ?? $defaultAvatar;
$userId = $_SESSION['user_id'];

// Verificar si el post existe. Si no, $viewPostData estará vacío o nulo.
if (!isset($viewPostData) || empty($viewPostData)) {
    // Si no se encontró el post, mostrar la página 404
    include dirname(__DIR__, 1) . '/main/404.php';
    return; // Detener la ejecución
}

// Si el post existe, asignar los datos a una variable más corta
$post = $viewPostData;

// --- ▼▼▼ INICIO DE NUEVA LÓGICA DE VISIBILIDAD ▼▼▼ ---
// Comprobar si el usuario actual tiene permiso para ver este post
$isOwner = ($post['user_id'] == $userId);

// 1. Ocultar si está eliminado
if ($post['post_status'] === 'deleted') {
    include dirname(__DIR__, 1) . '/main/404.php';
    return;
}

// 2. Ocultar si es privado y no es el dueño
if ($post['privacy_level'] === 'private' && !$isOwner) {
    include dirname(__DIR__, 1) . '/main/404.php';
    return;
}

// 3. Ocultar si es 'solo amigos' y no es el dueño (lógica de amigos no implementada aquí)
// (Por ahora, se asume que si tienes el enlace y es 'friends' o 'public', puedes verlo)
// --- ▲▲▲ FIN DE NUEVA LÓGICA DE VISIBILIDAD ▲▲▲ ---


// Lógica de datos (copiada de home.php)
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

// --- [HASTAGS] Lógica ELIMINADA ---

?>
<?php // --- [HASTAGS] <style> ELIMINADO --- ?>

<div class="section-content overflow-y <?php echo ($CURRENT_SECTION === 'post-view') ? 'active' : 'disabled'; ?>" data-section="post-view">

    <div class="page-toolbar-container" id="view-post-toolbar-container">
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
    
    <div class="component-wrapper">

        <div class="card-list-container">
            
            <?php
            // --- ▼▼▼ ¡INICIO DE CÓDIGO PEGADO! ▼▼▼ ---
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
                            
                            <?php // --- ▼▼▼ INICIO DE MODIFICACIÓN (BADGES SIN ICONOS) ▼▼▼ --- ?>
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
                            <?php // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ --- ?>

                        </div>
                    </div>
                    
                    <?php if ($isOwner): ?>
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
                            <p><?php echo nl2br(htmlspecialchars($post['text_content'])); ?></p>
                        <?php endif; ?>
                        </div>
                <?php endif; ?>

                <?php // --- [HASTAGS] Bloque de renderizado ELIMINADO --- ?>
                
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
                
                <form class="post-comment-input-container active" data-action="post-comment">
                    <input type="hidden" name="publication_id" value="<?php echo $post['id']; ?>">
                    <input type="hidden" name="parent_comment_id" value=""> 
                    <div class="post-comment-avatar" data-role="<?php echo htmlspecialchars($_SESSION['role'] ?? 'user'); ?>">
                        <img src="<?php echo htmlspecialchars($userAvatar); ?>" alt="Tu avatar">
                    </div>
                    <input type="text" class="post-comment-input" name="comment_text" placeholder="Añade un comentario..." required>
                    <button type="submit" class="post-comment-submit-btn" disabled>
                        <span class="material-symbols-rounded">send</span>
                    </button>
                </form>
                
              <div class="post-comments-container active" id="comments-for-post-<?php echo $post['id']; ?>" data-post-id="<?php echo $post['id']; ?>">
            </div>
            <?php
            // --- ▲▲▲ FIN DE CÓDIGO PEGADO ▲▲▲ ---
            ?>
            
        </div>

    </div>
    
    </div>
</div>
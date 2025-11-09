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

// Lógica de datos (copiada de home.php)
$postAvatar = $post['profile_image_url'] ?? $defaultAvatar;
if (empty($postAvatar)) $postAvatar = $defaultAvatar;
// --- ▼▼▼ LÍNEA AÑADIDA ▼▼▼ ---
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
// --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
$userHasBookmarked = (int)($post['user_has_bookmarked'] ?? 0) > 0;
// --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

?>
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
                </div>

                <?php if (!empty($post['text_content'])): ?>
                    <div class="post-card-content">
                        <?php if ($isPoll): ?>
                            <h3 class="poll-question"><?php echo htmlspecialchars($post['text_content']); ?></h3>
                        <?php else: ?>
                            <p><?php echo nl2br(htmlspecialchars($post['text_content'])); ?></p>
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
<?php
// FILE: includes/components/publication-card.php
// (NUEVO ARCHIVO REUTILIZABLE)

// Este componente asume que las siguientes variables ya existen
// en el scope donde es incluido:
// - $post (array con todos los datos de la publicación)
// - $defaultAvatar (string con la URL del avatar por defecto)
// - $userId (int con el ID del usuario logueado)
// - $userAvatar (string con la URL del avatar del usuario logueado)

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

?>
<div class="component-card component-card--post" style="padding: 0; align-items: stretch; flex-direction: column;" data-post-id="<?php echo $post['id']; ?>">
    
    <div class="post-card-header">
        <div class="component-card__content" style="gap: 12px; padding-bottom: 0; border-bottom: none;">
            <div class="component-card__avatar" data-role="<?php echo htmlspecialchars($postRole); ?>" style="width: 40px; height: 40px; flex-shrink: 0;">
                <img src="<?php echo htmlspecialchars($postAvatar); ?>" alt="<?php echo htmlspecialchars($post['username']); ?>" class="component-card__avatar-image">
            </div>
            <div class="component-card__text">
                <h2 class="component-card__title" style="font-size: 16px;"><?php echo htmlspecialchars($post['username']); ?></h2>
                <p class="component-card__description" style="font-size: 13px;">
                    <?php echo date('d/m/Y H:i', strtotime($post['created_at'])); ?>
                    <?php if (isset($post['community_name']) && $post['community_name']): ?>
                        <span style="color: #6b7280;"> &middot; en <strong><?php echo htmlspecialchars($post['community_name']); ?></strong></span>
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
                <p style="font-size: 15px; line-height: 1.6; color: #1f2937; white-space: pre-wrap; width: 100%;"><?php echo htmlspecialchars($post['text_content']); ?></p>
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
            <button type="button" class="component-action-button--icon" data-tooltip="home.actions.save">
                <span class="material-symbols-rounded">bookmark</span>
            </button>
        </div>
    </div>
    
    <form class="post-comment-input-container" data-action="post-comment" style="display: none;">
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
    
    <div class="post-comments-container" id="comments-for-post-<?php echo $post['id']; ?>" style="display: none;">
    </div>
    
</div>
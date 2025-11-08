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
    
    <div class="component-wrapper" style="padding-top: 82px;">

        <div class="card-list-container" style="display: flex; flex-direction: column; gap: 16px;">
            
            <?php
            // --- ▼▼▼ ¡INICIO DE REFACTORIZACIÓN! ▼▼▼ ---
            // $post ya fue definido y cargado por el router.
            // Usamos el mismo componente que home.php
            // Asume que $post, $defaultAvatar, $userId, y $userAvatar están definidos.
            include dirname(__DIR__, 2) . '/components/publication-card.php';
            // --- ▲▲▲ FIN DE REFACTORIZACIÓN ▲▲▲ ---
            ?>
            
        </div>

    </div>
</div>
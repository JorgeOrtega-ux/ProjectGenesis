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
            
            // --- ▼▼▼ INICIO DE SQL MODIFICADO (AÑADIDO u.role) ▼▼▼ ---
            $sql_posts = 
                "SELECT 
                    p.*, 
                    u.username, 
                    u.profile_image_url,
                    u.role,
                    (SELECT GROUP_CONCAT(pf.public_url SEPARATOR ',') 
                     FROM publication_attachments pa
                     JOIN publication_files pf ON pa.file_id = pf.id
                     WHERE pa.publication_id = p.id
                     ORDER BY pa.sort_order ASC
                    ) AS attachments,
                    (SELECT COUNT(pv.id) FROM poll_votes pv WHERE pv.publication_id = p.id) AS total_votes,
                    (SELECT pv.poll_option_id FROM poll_votes pv WHERE pv.publication_id = p.id AND pv.user_id = ?) AS user_voted_option_id,
                    
                    /* NUEVOS CAMPOS */
                    (SELECT COUNT(*) FROM publication_likes pl WHERE pl.publication_id = p.id) AS like_count,
                    (SELECT COUNT(*) FROM publication_likes pl WHERE pl.publication_id = p.id AND pl.user_id = ?) AS user_has_liked,
                    (SELECT COUNT(*) FROM publication_comments pc WHERE pc.publication_id = p.id) AS comment_count

                 FROM community_publications p
                 JOIN users u ON p.user_id = u.id
                 WHERE p.community_id = ?
                 ORDER BY p.created_at DESC
                 LIMIT 50";
            
            $stmt_posts = $pdo->prepare($sql_posts);
            // ¡Se añaden 2 IDs de usuario!
            $stmt_posts->execute([$userId, $userId, $currentCommunityId]);
            $publications = $stmt_posts->fetchAll();
            // --- ▲▲▲ FIN DE SQL MODIFICADO ▲▲▲ ---
        } else {
            $communityUuid = null;
        }
    }
    
    if ($communityUuid === null) {
        // --- VISTA DE FEED PRINCIPAL ---
        // --- ▼▼▼ INICIO DE SQL MODIFICADO (AÑADIDO u.role) ▼▼▼ ---
        $sql_posts = 
            "SELECT 
                p.*, 
                u.username, 
                u.profile_image_url, 
                u.role,
                c.name AS community_name,
                (SELECT GROUP_CONCAT(pf.public_url SEPARATOR ',') 
                 FROM publication_attachments pa
                 JOIN publication_files pf ON pa.file_id = pf.id
                 WHERE pa.publication_id = p.id
                 ORDER BY pa.sort_order ASC
                ) AS attachments,
                (SELECT COUNT(pv.id) FROM poll_votes pv WHERE pv.publication_id = p.id) AS total_votes,
                (SELECT pv.poll_option_id FROM poll_votes pv WHERE pv.publication_id = p.id AND pv.user_id = ?) AS user_voted_option_id,

                /* NUEVOS CAMPOS */
                (SELECT COUNT(*) FROM publication_likes pl WHERE pl.publication_id = p.id) AS like_count,
                (SELECT COUNT(*) FROM publication_likes pl WHERE pl.publication_id = p.id AND pl.user_id = ?) AS user_has_liked,
                (SELECT COUNT(*) FROM publication_comments pc WHERE pc.publication_id = p.id) AS comment_count

             FROM community_publications p
             JOIN users u ON p.user_id = u.id
             LEFT JOIN communities c ON p.community_id = c.id
             WHERE p.community_id IS NOT NULL 
             AND c.privacy = 'public' 
             ORDER BY p.created_at DESC
             LIMIT 50";
        
        $stmt_posts = $pdo->prepare($sql_posts);
         // ¡Se añaden 2 IDs de usuario!
        $stmt_posts->execute([$userId, $userId]);
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
            
        <div class="popover-module popover-module--anchor-left body-title disabled" data-module="moduleSelectGroup" style="top: calc(100% + 8px); left: 8px; width: 300px;">
            <div class="menu-content">
                <div class="menu-header" data-i18n="home.popover.title">Mis Grupos</div>
                <div class="menu-list" id="my-groups-list">
                    <div class="menu-link" data-i18n="home.popover.loading">Cargando...</div>
                </div>
            </div>
        </div>
        
        <div class="popover-module popover-module--anchor-left body-title disabled" data-module="moduleCreatePost" style="top: calc(100% + 8px); left: 8px; width: 300px;">
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

    <div class="component-wrapper" style="padding-top: 82px;">

        <div class="component-header-card">
            <h1 class="component-page-title" 
                data-i18n="<?php echo ($communityUuid === null) ? $currentCommunityNameKey : ''; ?>">
                <?php echo ($communityUuid !== null) ? htmlspecialchars($currentCommunityNameKey) : ''; ?>
            </h1>
            
            <?php if (empty($publications)): ?>
                <p class="component-page-description" data-i18n="home.main.noPosts"></p>
            <?php else: ?>
                <p class="component-page-description" data-i18n="home.main.welcome"></p>
            <?php endif; ?>
        </div>

        <div class="card-list-container" style="display: flex; flex-direction: column; gap: 16px;">
            <?php if (!empty($publications)): ?>
                <?php foreach ($publications as $post): ?>
                    <?php
                    // --- ▼▼▼ ¡INICIO DE REFACTORIZACIÓN! ▼▼▼ ---
                    // Todo el HTML de la publicación se ha movido a un componente reutilizable.
                    // Este componente asume que las variables $post, $defaultAvatar, $userId,
                    // y $userAvatar están definidas en este scope.
                    include dirname(__DIR__, 2) . '/components/publication-card.php';
                    // --- ▲▲▲ FIN DE REFACTORIZACIÓN ▲▲▲ ---
                    ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
</div>
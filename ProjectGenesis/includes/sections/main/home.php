<?php
// FILE: includes/sections/main/home.php
// (CÓDIGO MODIFICADO PARA MOSTRAR PUBLICACIONES CON FOTOS)

// Esta variable viene de config/routing/router.php
global $pdo; 
$defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";

$publications = [];
$currentCommunityId = null;
$currentCommunityNameKey = "home.popover.mainFeed"; // Default
$communityUuid = $_GET['community_uuid'] ?? null; // Viene de url-manager.js

try {
    if ($communityUuid) {
        // --- VISTA DE COMUNIDAD FILTRADA ---
        $stmt_comm = $pdo->prepare("SELECT id, name FROM communities WHERE uuid = ?");
        $stmt_comm->execute([$communityUuid]);
        $community = $stmt_comm->fetch();
        
        if ($community) {
            $currentCommunityId = $community['id'];
            $currentCommunityNameKey = $community['name']; // Usar el nombre real
            
            // --- ▼▼▼ INICIO DE SQL MODIFICADO (CON GROUP_CONCAT) ▼▼▼ ---
            $stmt_posts = $pdo->prepare(
                "SELECT 
                    p.*, 
                    u.username, 
                    u.profile_image_url,
                    (SELECT GROUP_CONCAT(pf.public_url SEPARATOR ',') 
                     FROM publication_attachments pa
                     JOIN publication_files pf ON pa.file_id = pf.id
                     WHERE pa.publication_id = p.id
                     ORDER BY pa.sort_order ASC
                    ) AS attachments
                 FROM community_publications p
                 JOIN users u ON p.user_id = u.id
                 WHERE p.community_id = ?
                 ORDER BY p.created_at DESC
                 LIMIT 50"
            );
            // --- ▲▲▲ FIN DE SQL MODIFICADO ▲▲▲ ---
            $stmt_posts->execute([$currentCommunityId]);
            $publications = $stmt_posts->fetchAll();
        } else {
            // UUID no válido, mostrar feed principal.
            $communityUuid = null;
        }
    }
    
    if ($communityUuid === null) {
        // --- VISTA DE FEED PRINCIPAL ---
        
        // --- ▼▼▼ INICIO DE SQL MODIFICADO (CON GROUP_CONCAT) ▼▼▼ ---
        $stmt_posts = $pdo->prepare(
            "SELECT 
                p.*, 
                u.username, 
                u.profile_image_url, 
                c.name AS community_name,
                (SELECT GROUP_CONCAT(pf.public_url SEPARATOR ',') 
                 FROM publication_attachments pa
                 JOIN publication_files pf ON pa.file_id = pf.id
                 WHERE pa.publication_id = p.id
                 ORDER BY pa.sort_order ASC
                ) AS attachments
             FROM community_publications p
             JOIN users u ON p.user_id = u.id
             LEFT JOIN communities c ON p.community_id = c.id
             WHERE p.community_id IS NULL OR c.privacy = 'public'
             ORDER BY p.created_at DESC
             LIMIT 50"
        );
        // --- ▲▲▲ FIN DE SQL MODIFICADO ▲▲▲ ---
        $stmt_posts->execute();
        $publications = $stmt_posts->fetchAll();
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
                <?php foreach ($publications as $post): 
                    $postAvatar = $post['profile_image_url'] ?? $defaultAvatar;
                    if (empty($postAvatar)) $postAvatar = $defaultAvatar;
                    
                    // --- ▼▼▼ INICIO DE LÓGICA DE ADJUNTOS ▼▼▼ ---
                    $attachments = [];
                    if (!empty($post['attachments'])) {
                        $attachments = explode(',', $post['attachments']);
                    }
                    $attachmentCount = count($attachments);
                    // --- ▲▲▲ FIN DE LÓGICA DE ADJUNTOS ▲▲▲ ---
                ?>
                    <div class="component-card component-card--column" style="align-items: stretch; gap: 8px; padding: 16px;">
                        <div class="component-card__content" style="gap: 12px; padding-bottom: 8px; border-bottom: 1px solid #00000020;">
                            <div class="component-card__avatar" style="width: 40px; height: 40px; flex-shrink: 0;">
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
                        
                        <?php if (!empty($post['text_content'])): ?>
                            <div class="component-card__content">
                                <p style="font-size: 15px; line-height: 1.6; color: #1f2937; white-space: pre-wrap; width: 100%;"><?php echo htmlspecialchars($post['text_content']); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($attachmentCount > 0): ?>
                        <div class="post-attachments-container" data-count="<?php echo $attachmentCount; ?>">
                            <?php foreach ($attachments as $imgUrl): ?>
                                <div class="post-attachment-item">
                                    <img src="<?php echo htmlspecialchars($imgUrl); ?>" alt="Adjunto de publicación" loading="lazy">
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
</div>
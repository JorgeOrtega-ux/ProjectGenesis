<?php
// FILE: includes/sections/main/explorer.php
// (Sección actualizada para mostrar comunidades dinámicas desde la BD)

global $pdo, $basePath;
$userId = $_SESSION['user_id'] ?? 0;
$publicCommunities = [];

try {
    // Esta consulta obtiene todas las comunidades públicas, cuenta sus miembros,
    // cuenta las publicaciones de hoy, y verifica si el usuario actual ya es miembro.
    
    // --- ▼▼▼ INICIO DE MODIFICACIÓN (SQL) ▼▼▼ ---
    $stmt = $pdo->prepare(
       "SELECT 
            c.id, c.name, c.community_type, c.icon_url, c.banner_url,
            COUNT(DISTINCT uc.user_id) AS member_count,
            (SELECT COUNT(DISTINCT p.id) 
             FROM community_publications p 
             WHERE p.community_id = c.id AND p.created_at >= CURDATE()) AS posts_today_count,
            (EXISTS(SELECT 1 FROM user_communities WHERE user_id = :current_user_id AND community_id = c.id)) AS is_member
        FROM communities c
        LEFT JOIN user_communities uc ON c.id = uc.community_id
        WHERE c.privacy = 'public'
        GROUP BY c.id, c.name, c.community_type, c.icon_url, c.banner_url
        ORDER BY member_count DESC"
    );
    // --- ▲▲▲ FIN DE MODIFICACIÓN (SQL) ▲▲▲ ---

    $stmt->execute(['current_user_id' => $userId]);
    $publicCommunities = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    logDatabaseError($e, 'explorer.php - fetch public communities');
    // $publicCommunities se quedará como un array vacío y mostrará el mensaje de "No hay comunidades".
}

// --- INICIO DE CSS (Se mantiene el CSS en línea como en tu archivo original) ---
?>
<style>
    /* * =============================================
     * ESTILOS PARA EXPLORER.PHP
     * =============================================
    */
    .explorer-full-width-container {
        width: 100%;
        height: 100%;
        padding: 82px 24px 24px 24px; 
        box-sizing: border-box;
    }
    .explorer-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 24px;
        width: 100%;
    }
    .community-card-preview {
        border-radius: 12px;
        background-color: #ffffff;
        border: 1px solid #00000020;
        padding: 12px;
        box-shadow: 0 2px 4px #00000010;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .community-card-preview:hover {
        transform: translateY(-4px);
        box-shadow: 0 6px 12px #00000015;
    }
    .community-card__banner {
        height: 120px;
        background-size: cover;
        background-position: center;
        background-color: #f5f5fa;
        border-radius: 8px; 
    }
    .community-card__content {
        padding: 0; 
    }
    .community-card__header {
        margin-top: -40px; 
        margin-left: 12px; 
        height: 64px; 
    }
    .community-card__icon {
        width: 64px;
        height: 64px;
        border-radius: 16px; 
        border: 4px solid #ffffff;
        background-color: #f0f0f0;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        box-shadow: 0 2px 4px #00000020;
        flex-shrink: 0;
    }
    .community-card__icon img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .community-card__info {
        padding-top: 0px; 
        padding-bottom: 16px;
        padding-left: 12px; 
        padding-right: 12px;
    }
    .community-card__name {
        font-weight: 700;
        font-size: 18px;
        color: #1f2937;
        margin-bottom: 4px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .community-card__description {
        font-size: 14px;
        color: #6b7280;
        line-height: 1.5;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
        min-height: 42px; /* 2 líneas de 21px */
    }
    .community-card__join-btn {
        width: 100%;
        height: 40px;
        background-color: #000; 
        color: #ffffff;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: background-color 0.2s ease;
    }
    .community-card__join-btn:hover {
        background-color: #333; 
    }
    .community-card__join-btn.danger {
        background-color: #ffffff;
        color: #c62828;
        border: 1px solid #ef9a9a;
    }
    .community-card__join-btn.danger:hover {
        background-color: #fbebee;
        border-color: #c62828;
    }
    
    .community-card__stats {
        display: flex;
        flex-wrap: wrap; 
        gap: 8px; 
        margin: 16px 0 0 0;
        padding-top: 0; 
        border-top: none; 
    }
    .community-card__stat-item {
        display: inline-flex; 
        align-items: center;
        gap: 6px;
        font-size: 13px;
        font-weight: 500;
        color: #6b7280;
        
        background-color: transparent;
        border: 1px solid #00000020;
        border-radius: 50px;
        padding: 6px 10px; 
    }

    .community-card__stat-item .material-symbols-rounded {
        font-size: 16px;
    }
</style>
<?php // --- FIN DE CSS --- ?>


<div class="section-content overflow-y <?php echo ($CURRENT_SECTION === 'explorer') ? 'active' : 'disabled'; ?>" data-section="explorer">
    
    <div class="page-toolbar-container" id="explorer-toolbar-container">
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
                        data-action="toggleSectionJoinGroup" 
                        data-tooltip="home.toolbar.joinGroup">
                    <span class="material-symbols-rounded">group_add</span>
                    </button>
                    
                    <button type="button"
                        class="page-toolbar-button"
                        data-action="explorer-search-dummy" 
                        data-tooltip="admin.users.search">
                        <span class="material-symbols-rounded">search</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="explorer-full-width-container">
    
        <div class="explorer-grid">

            <?php if (empty($publicCommunities)): ?>
                
                <div class="component-card" style="grid-column: 1 / -1;">
                    <div class="component-card__content">
                        <div class="component-card__icon">
                            <span class="material-symbols-rounded">search_off</span>
                        </div>
                        <div class="component-card__text">
                            <h2 class="component-card__title" data-i18n="join_group.noPublic">No hay comunidades públicas</h2>
                            <p class="component-card__description">Parece que aún no hay comunidades públicas. ¿Por qué no creas una?</p>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <?php foreach ($publicCommunities as $community): ?>
                    <?php
                        // Preparar los datos dinámicos
                        $name = htmlspecialchars($community['name']);
                        
                        // --- ▼▼▼ INICIO DE MODIFICACIÓN (LÓGICA DE i18n) ▼▼▼ ---
                        $descriptionKey = 'explorer.desc.municipio'; // Default
                        if ($community['community_type'] === 'universidad') {
                            $descriptionKey = 'explorer.desc.universidad';
                        }
                        // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
                        
                        $bannerUrl = !empty($community['banner_url']) 
                            ? htmlspecialchars($community['banner_url']) 
                            : 'https://picsum.photos/seed/' . preg_replace("/[^a-zA-Z0-9]/", '', $name) . '/400/120';
                            
                        $iconUrl = !empty($community['icon_url']) 
                            ? htmlspecialchars($community['icon_url']) 
                            : 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&size=64&background=random&color=ffffff&bold=true';
                        
                        $memberCount = (int)$community['member_count'];
                        $postsTodayCount = (int)$community['posts_today_count'];
                        $isMember = (bool)$community['is_member'];
                    ?>

                    <div class="community-card-preview">
                        <div class="community-card__banner" style="background-image: url('<?php echo $bannerUrl; ?>');"></div>
                        
                        <div class="community-card__content">
                            <div class="community-card__header">
                                <div class="community-card__icon">
                                    <img src="<?php echo $iconUrl; ?>" alt="Ícono de <?php echo $name; ?>">
                                </div>
                            </div>
                            
                            <div class="community-card__info">
                                <h3 class="community-card__name" title="<?php echo $name; ?>"><?php echo $name; ?></h3>
                                
                                <?php // --- ▼▼▼ INICIO DE MODIFICACIÓN (HTML) ▼▼▼ --- ?>
                                <p class="community-card__description" data-i18n="<?php echo $descriptionKey; ?>"></p>
                                <?php // --- ▲▲▲ FIN DE MODIFICACIÓN (HTML) ▲▲▲ --- ?>
                            </div>

                            <?php if ($isMember): ?>
                                <button class="community-card__join-btn danger" 
                                        data-action="leave-community" 
                                        data-community-id="<?php echo $community['id']; ?>"
                                        data-i18n="join_group.leave">
                                    Abandonar
                                </button>
                            <?php else: ?>
                                <button class="community-card__join-btn" 
                                        data-action="join-community" 
                                        data-community-id="<?php echo $community['id']; ?>"
                                        data-i18n="join_group.join">
                                    Unirme
                                </button>
                            <?php endif; ?>

                            <div class="community-card__stats">
                                <div class="community-card__stat-item">
                                    <span class="material-symbols-rounded">group</span>
                                    <span><?php echo number_format($memberCount); ?> Miembros</span>
                                </div>
                                <div class="community-card__stat-item">
                                    <span class="material-symbols-rounded">chat_bubble</span>
                                    <span>+<?php echo number_format($postsTodayCount); ?> publicaciones hoy</span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div> <?php // Fin de .explorer-grid ?>
    
    </div> <?php // Fin de .explorer-full-width-container ?>
</div>
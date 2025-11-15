<?php
// FILE: includes/sections/admin/manage-communities.php

global $pdo, $basePath;

$communitiesList = [];
$defaultIcon = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";

try {
    // 1. Contar el total de comunidades
    $totalCommunitiesStmt = $pdo->prepare("SELECT COUNT(*) FROM communities");
    $totalCommunitiesStmt->execute();
    $totalCommunities = (int)$totalCommunitiesStmt->fetchColumn();

    if ($totalCommunities > 0) {
        // 2. Obtener las comunidades (sin paginación por ahora, puedes añadirla luego si es necesario)
        $sqlSelect = "SELECT 
                        c.id, c.name, c.privacy, c.icon_url, c.uuid,
                        (SELECT COUNT(*) FROM user_communities uc WHERE uc.community_id = c.id) as member_count
                      FROM communities c
                      ORDER BY c.name ASC";
        
        $stmt = $pdo->prepare($sqlSelect);
        $stmt->execute();
        $communitiesList = $stmt->fetchAll();
    }

} catch (PDOException $e) {
    logDatabaseError($e, 'admin - manage-communities');
}
?>
<div class="section-content overflow-y <?php echo ($CURRENT_SECTION === 'admin-manage-communities') ? 'active' : 'disabled'; ?>" data-section="admin-communities">

    <div class="page-toolbar-container">
        <div class="page-toolbar-floating">

            <div class="toolbar-action-default">
                <div class="page-toolbar-left">
                    <button type="button"
                        class="page-toolbar-button"
                        data-action="toggleSectionAdminEditCommunity"
                        data-tooltip="admin.communities.create">
                        <span class="material-symbols-rounded">add</span>
                    </button>
                </div>
                
                <div class="page-toolbar-right">
                    </div>
            </div>
            
            <div class="toolbar-action-selection">
                <div class="page-toolbar-left">
                    <button type="button"
                        class="page-toolbar-button"
                        data-action="admin-community-edit-selected"
                        data-tooltip="admin.communities.editTooltip" disabled>
                        <span class="material-symbols-rounded">edit</span>
                    </button>
                    </div>

                <div class="page-toolbar-right">
                    <button type="button"
                        class="page-toolbar-button"
                        data-action="admin-community-clear-selection"
                        data-tooltip="admin.users.clearSelection" disabled>
                        <span class="material-symbols-rounded">close</span>
                    </button>
                </div>
            </div>
            
        </div>
    </div>
    
    <div class="component-wrapper" style="padding-top: 82px;">

        <?php outputCsrfInput(); ?>

        <div class="component-header-card">
            <h1 class="component-page-title" data-i18n="admin.communities.title">Gestionar Comunidades</h1>
            <p class="component-page-description" data-i18n="admin.communities.description">Busca, edita, crea o elimina comunidades en el sitio.</p>
        </div>

        <?php if (empty($communitiesList)): ?>

            <div class="component-card">
                <div class="component-card__content">
                    <div class="component-card__icon">
                        <span class="material-symbols-rounded">group_off</span>
                    </div>
                    <div class="component-card__text">
                        <h2 class="component-card__title" data-i18n="admin.communities.noCommunitiesTitle">No hay comunidades</h2>
                        <p class="component-card__description" data-i18n="admin.communities.noCommunitiesDesc">Aún no se ha creado ninguna comunidad.</p>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <div class="card-list-container" id="community-list-container">
                <?php foreach ($communitiesList as $community): ?>
                    <?php
                    $iconUrl = $community['icon_url'] ?? $defaultIcon;
                    if (empty($iconUrl)) {
                        $iconUrl = "https://ui-avatars.com/api/?name=" . urlencode($community['name']) . "&size=100&background=e0e0e0&color=ffffff";
                    }
                    $privacyText = ($community['privacy'] === 'public') ? 'admin.communities.privacyPublic' : 'admin.communities.privacyPrivate';
                    ?>
                    <div class="card-item" 
                         data-community-id="<?php echo $community['id']; ?>"
                         data-community-uuid="<?php echo htmlspecialchars($community['uuid']); ?>"
                    >
                    <div class="component-card__avatar" style="width: 50px; height: 50px; flex-shrink: 0; border-radius: 8px;">
                            <img src="<?php echo htmlspecialchars($iconUrl); ?>"
                                alt="<?php echo htmlspecialchars($community['name']); ?>"
                                class="component-card__avatar-image"
                                style="border-radius: 8px;">
                        </div>

                        <div class="card-item-details">

                            <div class="card-detail-item card-detail-item--full">
                                <span class="card-detail-label" data-i18n="admin.communities.labelName"></span>
                                <span class="card-detail-value"><?php echo htmlspecialchars($community['name']); ?></span>
                            </div>

                            <div class="card-detail-item">
                                <span class="card-detail-label" data-i18n="admin.communities.labelPrivacy"></span>
                                <span class="card-detail-value" data-i18n="<?php echo $privacyText; ?>"></span>
                            </div>
                            <div class="card-detail-item">
                                <span class="card-detail-label" data-i18n="admin.communities.labelMembers"></span>
                                <span class="card-detail-value"><?php echo htmlspecialchars($community['member_count']); ?></span>
                            </div>

                        </div>
                        
                        <?php // --- INICIO DE MODIFICACIÓN --- ?>
                        <?php // Se eliminó el botón '<a>' de editar que estaba aquí ?>
                        <?php // --- FIN DE MODIFICACIÓN --- ?>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</div>
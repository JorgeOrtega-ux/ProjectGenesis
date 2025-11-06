<?php
// FILE: includes/sections/main/home.php
// (Contenido MODIFICADO para añadir la nueva barra de herramientas)

// --- ▼▼▼ INICIO DE LÓGICA PHP MOVIDA DE main-layout.php ▼▼▼ ---
// Esta lógica solo se ejecuta si el usuario está logueado
if (isset($_SESSION['user_id'], $pdo)) {
    $user_groups = [];
    try {
        // Reutilizamos la lógica de 'my-groups.php' para obtener los grupos
        $stmt = $pdo->prepare(
            "SELECT 
                g.id,
                g.name
             FROM groups g
             JOIN user_groups ug_main ON g.id = ug_main.group_id
             WHERE ug_main.user_id = ?
             GROUP BY g.id, g.name
             ORDER BY g.name"
        );
        $stmt->execute([$_SESSION['user_id']]);
        $user_groups = $stmt->fetchAll();
    } catch (PDOException $e) {
        logDatabaseError($e, 'home.php - load user groups for popover');
    }
}
// --- ▲▲▲ FIN DE LÓGICA PHP MOVIDA ▲▲▲ ---
?>

<div class="section-content overflow-y <?php echo ($CURRENT_SECTION === 'home') ? 'active' : 'disabled'; ?>" data-section="home">

    <div class="page-toolbar-container" id="home-toolbar-container">
        <div class="page-toolbar-floating">
            <div class="toolbar-action-default">
                
                <div class="page-toolbar-left">
                    <button type="button"
                        class="page-toolbar-button"
                        data-action="toggleModuleGroupSelect" 
                        data-tooltip="toolbar.buttons.selectGroup">
                        <span class="material-symbols-rounded">groups</span>
                    </button>
                    
                    <div class="toolbar-group-display" id="selected-group-display">
                        <span class="material-symbols-rounded">label</span>
                        <span class="toolbar-group-text" data-i18n="toolbar.noGroupSelected">Ningún grupo</span>
                    </div>
                </div>

                <div class="page-toolbar-right">
                    <button type="button"
                        class="page-toolbar-button"
                        data-action="toggleSectionMyGroups"
                        data-tooltip="header.buttons.myGroups"> 
                        <span class="material-symbols-rounded">view_list</span>
                    </button>

                    <button type="button"
                        class="page-toolbar-button"
                        data-action="toggleSectionJoinGroup"
                        data-tooltip="home.noGroups.joinButton"> 
                        <span class="material-symbols-rounded">add</span>
                    </button>
                </div>
                
                </div>
        </div>

        <div class="popover-module body-title disabled" 
             data-module="moduleGroupSelect"
             style="width: 300px; left: 8px; right: auto; top: calc(100% + 8px);">
            <div class="menu-content">
                <?php if (empty($user_groups)): ?>
                    <div class="menu-list">
                        <div class="menu-header" data-i18n="modals.selectGroup.noGroups">No perteneces a ningún grupo.</div>
                        <div class="menu-link" data-action="toggleSectionJoinGroup">
                            <div class="menu-link-icon">
                                <span class="material-symbols-rounded">add</span>
                            </div>
                            <div class="menu-link-text">
                                <span data-i18n="modals.selectGroup.joinButton">Unirme a un grupo</span>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="menu-list">
                        <div class="menu-header" data-i18n="modals.selectGroup.title">Seleccionar Grupo</div>
                        
                        <div class="menu-link group-select-item"
                             data-group-id="none"
                             data-i18n-key="toolbar.noGroupSelected">
                            <div class="menu-link-icon">
                                <span class="material-symbols-rounded">label_off</span>
                            </div>
                            <div class="menu-link-text">
                                <span data-i18n="toolbar.noGroupSelected">Ningún grupo</span>
                            </div>
                            <div class="menu-link-check-icon">
                                </div>
                        </div>

                        <?php foreach ($user_groups as $group): ?>
                            <div class="menu-link group-select-item" 
                                 data-group-id="<?php echo $group['id']; ?>" 
                                 data-group-name="<?php echo htmlspecialchars($group['name']); ?>">
                                <div class="menu-link-icon">
                                    <span class="material-symbols-rounded">label</span>
                                </div>
                                <div class="menu-link-text">
                                    <span><?php echo htmlspecialchars($group['name']); ?></span>
                                </div>
                                <div class="menu-link-check-icon">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        </div>
    <div class="component-wrapper" style="padding-top: 82px;"> <div class="component-header-card">
            <h1 class="component-page-title" data-i18n="home.empty.title">Página Principal</h1>
            <p class="component-page-description" data-i18n="home.empty.description">
                Esta es tu página principal. El contenido se añadirá próximamente.
            </p>
        </div>

    </div>
</div>
<?php
// FILE: includes/sections/main/home.php
// (Contenido MODIFICADO para cargar grupo desde URL y cambiar texto)

// --- ▼▼▼ INICIO DE LÓGICA DE CARGA DE GRUPO ▼▼▼ ---
// $current_group_info y $currentGroupUuid son definidos en config/routing/router.php
// $pdo y $_SESSION['user_id'] están disponibles

$user_groups = []; // Para el Popover
$current_group_members = []; // <-- ¡NUEVO! Para el panel de miembros

if (isset($_SESSION['user_id'], $pdo)) {
    
    // 1. Obtener TODOS los grupos del usuario para el popover
    try {
        $stmt_all = $pdo->prepare(
            "SELECT 
                g.id,
                g.name,
                g.uuid -- ¡IMPORTANTE! Añadir UUID para el JS
             FROM groups g
             JOIN user_groups ug_main ON g.id = ug_main.group_id
             WHERE ug_main.user_id = ?
             GROUP BY g.id, g.name, g.uuid
             ORDER BY g.name"
        );
        $stmt_all->execute([$_SESSION['user_id']]);
        $user_groups = $stmt_all->fetchAll();
    } catch (PDOException $e) {
        logDatabaseError($e, 'home.php - load user groups for popover');
    }

    // --- ▼▼▼ ¡NUEVA LÓGICA! OBTENER MIEMBROS SI HAY GRUPO SELECCIONADO ▼▼▼ ---
    if (isset($current_group_info) && $current_group_info) {
        try {
            $stmt_members = $pdo->prepare(
                "SELECT u.id, u.username, u.profile_image_url, u.role as user_role, ug.role as group_role
                 FROM users u
                 JOIN user_groups ug ON u.id = ug.user_id
                 WHERE ug.group_id = ?
                 ORDER BY ug.role ASC, u.username ASC" // Ordenar por rol y luego nombre
            );
            $stmt_members->execute([$current_group_info['id']]);
            $current_group_members = $stmt_members->fetchAll();
        } catch (PDOException $e) {
            logDatabaseError($e, 'home.php - load group members');
            // $current_group_members se mantendrá vacío
        }
    }
    // --- ▲▲▲ FIN DE NUEVA LÓGICA ▲▲▲ ---

}

// 2. Preparar el texto para el H1 (basado en la variable del router)
$homeH1TextKey = 'home.chat.selectGroup';
$homeH1Text = '';
if (isset($current_group_info) && $current_group_info) {
    // Si hay un grupo, preparamos la clave y el texto
    $homeH1TextKey = 'home.chat.chattingWith';
    // (El JS reemplazará %groupName% en el H1, pero lo pre-cargamos por si JS falla)
    $homeH1Text = "Próximamente aquí estará el chat de <strong>" . htmlspecialchars($current_group_info['name']) . "</strong>";
} else {
    // Si no hay grupo, usamos el texto por defecto
    $homeH1Text = "Selecciona un grupo para comenzar a chatear";
}
// --- ▲▲▲ FIN DE LÓGICA DE CARGA DE GRUPO ▲▲▲ ---
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
                    
                    <div class="toolbar-group-display <?php echo (isset($current_group_info) && $current_group_info) ? 'active' : ''; ?>" id="selected-group-display">
                        <span class="material-symbols-rounded">label</span>
                        
                        <?php if (isset($current_group_info) && $current_group_info): ?>
                            <span class="toolbar-group-text">
                                <?php echo htmlspecialchars($current_group_info['name']); ?>
                            </span>
                        <?php else: ?>
                            <span class="toolbar-group-text" data-i18n="toolbar.noGroupSelected">
                                Ningún grupo
                            </span>
                        <?php endif; ?>
                    </div>
                    </div>

                <div class="page-toolbar-right">
                    <!-- --- ▼▼▼ ¡NUEVO BOTÓN DE MIEMBROS AÑADIDO! ▼▼▼ --- -->
                    <button type="button"
                        class="page-toolbar-button"
                        id="toggle-members-panel-btn"
                        data-action="toggleModuleGroupMembers"
                        data-tooltip="header.buttons.groupMembers"
                        <?php echo (!isset($current_group_info) || !$current_group_info) ? 'disabled' : ''; ?>
                        > 
                        <span class="material-symbols-rounded">group</span>
                    </button>
                    <!-- --- ▲▲▲ FIN DE BOTÓN AÑADIDO ▲▲▲ --- -->

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
                        
                        <div class="menu-link group-select-item <?php echo (!isset($current_group_info) || !$current_group_info) ? 'active' : ''; ?>"
                             data-group-id="none"
                             data-i18n-key="toolbar.noGroupSelected">
                            <div class="menu-link-icon">
                                <span class="material-symbols-rounded">label_off</span>
                            </div>
                            <div class="menu-link-text">
                                <span data-i18n="toolbar.noGroupSelected">Ningún grupo</span>
                            </div>
                            <div class="menu-link-check-icon">
                                <?php if (!isset($current_group_info) || !$current_group_info): ?>
                                    <span class="material-symbols-rounded">check</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php foreach ($user_groups as $group): ?>
                            <?php $is_active_group = (isset($current_group_info) && $current_group_info && $group['id'] === $current_group_info['id']); ?>
                            <div class="menu-link group-select-item <?php echo $is_active_group ? 'active' : ''; ?>" 
                                 data-group-id="<?php echo $group['id']; ?>" 
                                 data-group-name="<?php echo htmlspecialchars($group['name']); ?>"
                                 data-group-uuid="<?php echo htmlspecialchars($group['uuid']); ?>"> <div class="menu-link-icon">
                                    <span class="material-symbols-rounded">label</span>
                                </div>
                                <div class="menu-link-text">
                                    <span><?php echo htmlspecialchars($group['name']); ?></span>
                                </div>
                                <div class="menu-link-check-icon">
                                    <?php if ($is_active_group): ?>
                                        <span class="material-symbols-rounded">check</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        </div>
                <?php endif; ?>
            </div>
        </div>
        </div>
    
    <div class="component-wrapper" style="padding-top: 82px;"> 

        <div class="auth-container text-center" style="margin-top: 10vh;"> 
            
            <h1 class="auth-title" 
                id="home-chat-placeholder" 
                data-i18n="<?php echo $homeH1TextKey; ?>"
                data-i18n-key-default="home.chat.selectGroup"
                data-i18n-key-selected="home.chat.chattingWith"
                style="font-size: 24px; color: #6b7280; font-weight: 500; line-height: 1.6;">
                
                <?php
                // Imprimir el texto HTML inicial
                echo $homeH1Text;
                ?>
            </h1>
            </div>

    </div>

    <!-- --- ▼▼▼ ¡NUEVO PANEL DE MIEMBROS AÑADIDO! ▼▼▼ --- -->
    <div class="module-content module-members-surface body-title disabled" data-module="moduleGroupMembers">
        <div class="menu-content">
            
            <div class="members-header">
                <h3 class="members-title" data-i18n="members.title">Miembros del Grupo</h3>
                <button class="modal-close-btn" data-action="toggleModuleGroupMembers">
                    <span class="material-symbols-rounded">close</span>
                </button>
            </div>
            
            <div class="members-list-container">
                <?php if (!isset($current_group_info) || !$current_group_info): ?>
                    
                    <div class="members-empty-state">
                        <span class="material-symbols-rounded">group_off</span>
                        <p data-i18n="members.noGroup">Selecciona un grupo para ver sus miembros.</p>
                    </div>

                <?php elseif (empty($current_group_members)): ?>

                    <div class="members-empty-state">
                        <span class="material-symbols-rounded">person_off</span>
                        <p data-i18n="members.noMembers">Este grupo aún no tiene miembros.</p>
                    </div>

                <?php else: ?>
                    
                    <div class="member-list">
                    <?php 
                    $defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";
                    foreach ($current_group_members as $member): 
                        $avatarUrl = $member['profile_image_url'] ?? $defaultAvatar;
                        if (empty($avatarUrl)) {
                            $avatarUrl = "https://ui-avatars.com/api/?name=" . urlencode($member['username']) . "&size=100&background=e0e0e0&color=ffffff";
                        }
                    ?>
                        <div class="member-item" data-role="<?php echo htmlspecialchars($member['group_role']); ?>" data-user-role="<?php echo htmlspecialchars($member['user_role']); ?>">
                            <div class="component-card__avatar member-avatar" data-role="<?php echo htmlspecialchars($member['user_role']); ?>">
                                <img src="<?php echo htmlspecialchars($avatarUrl); ?>"
                                     alt="<?php echo htmlspecialchars($member['username']); ?>"
                                     class="component-card__avatar-image">
                            </div>
                            <div class="member-info">
                                <span class="member-name"><?php echo htmlspecialchars($member['username']); ?></span>
                                <span class="member-role" data-i18n="members.role.<?php echo htmlspecialchars($member['group_role']); ?>">
                                    <?php echo ucfirst($member['group_role']); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div>

                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- --- ▲▲▲ FIN DE PANEL AÑADIDO ▲▲▲ --- -->

</div>
<?php
// FILE: includes/sections/admin/admin-manage-status.php
// (Refactorizado para eliminar la lógica de expiración temporal)

// $manageUserData es cargado por config/routing/router.php
if (!isset($manageUserData) || !$manageUserData) {
    echo '<div class="component-wrapper"><p data-i18n="admin.users.errorLoadingUser">Error al cargar datos del usuario.</p></div>';
    return;
}

/**
 * Calcula el número de días restantes hasta una fecha de expiración.
 * @param string|null $expires_at_str El timestamp UTC de la BD.
 * @return int Días restantes (mínimo 1).
 */
// (Esta función ya no se usa en este archivo, pero se deja por si se reutiliza)
function get_days_until_expiry($expires_at_str) {
    if (empty($expires_at_str)) return 1; // Default a 1 día
    try {
        $expiry = new DateTime($expires_at_str, new DateTimeZone('UTC'));
        $now = new DateTime('now', new DateTimeZone('UTC'));
        if ($expiry <= $now) return 1; // Ya expiró, default a 1
        
        $interval = $now->diff($expiry);
        $days = (int)$interval->format('%a'); // Días totales
        
        if ($days === 0 && $expiry > $now) {
            return 1;
        }
        return $days;
    } catch (Exception $e) {
        return 1;
    }
}


// --- Variables de ayuda para el formulario ---
$userId = $manageUserData['id'];
$username = htmlspecialchars($manageUserData['username'] ?? 'Usuario');
$currentStatus = $manageUserData['account_status'] ?? 'active';

// Checar restricciones
$restrictions = $manageUserData['restrictions'] ?? [];

$isRestrictedPublish = isset($restrictions['CANNOT_PUBLISH']);
$isRestrictedComment = isset($restrictions['CANNOT_COMMENT']);
$isRestrictedMessage = isset($restrictions['CANNOT_MESSAGE']);
$isRestrictedSocial = isset($restrictions['CANNOT_SOCIAL']);


// --- Mapas para el nuevo selector de Estado General ---
$statusMap = [
    'active' => 'admin.users.statusActive',
    'suspended' => 'admin.users.statusSuspended',
    'deleted' => 'admin.users.statusDeleted'
];
$statusIconMap = [
    'active' => 'toggle_on',
    'suspended' => 'pause_circle',
    'deleted' => 'remove_circle'
];
$currentStatusKey = $statusMap[$currentStatus];
$currentStatusIcon = $statusIconMap[$currentStatus];

?>

<div class="section-content overflow-y <?php echo ($CURRENT_SECTION === 'admin-manage-status') ? 'active' : 'disabled'; ?>" data-section="admin-manage-status">
    
    <div class="page-toolbar-container" id="admin-status-toolbar-container">
        <div class="page-toolbar-floating">
            <div class="toolbar-action-default">
                <div class="page-toolbar-left">
                    <button type="button"
                        class="page-toolbar-button"
                        data-action="toggleSectionAdminManageUsers" 
                        data-tooltip="admin.users.cancelButton">
                        <span class="material-symbols-rounded">arrow_back</span>
                    </button>
                </div>
                <div class="page-toolbar-right">
                    <button type="button"
                        class="page-toolbar-button"
                        id="admin-save-status-btn"
                        data-tooltip="admin.users.saveChanges">
                        <span class="material-symbols-rounded">save</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="component-wrapper">
        
        <input type="hidden" id="admin-manage-status-user-id" value="<?php echo $userId; ?>">
        
        <input type="hidden" id="admin-manage-status-value" value="<?php echo $currentStatus; ?>">

        <div class="component-header-card">
            <h1 class="component-page-title">
                <span data-i18n="admin.users.manageStatusTitle">Gestionar Estado</span>: <?php echo $username; ?>
            </h1>
            <p class="component-page-description" data-i18n="admin.users.manageStatusDesc">Ajusta el estado de la cuenta y las restricciones de servicio para este usuario.</p>
        </div>
        
        <div class="component-card__error disabled" id="admin-manage-status-error" style="width: 100%; margin: -8px 0 16px 0;"></div>
        <?php // --- Selector de Estado General (REUTILIZADO) --- ?>
        <div class="component-card component-card--column">
            <div class="component-card__content">
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="admin.users.generalStatus">Estado General</h2>
                    <p class="component-card__description">Elige el estado principal de la cuenta del usuario.</p>
                </div>
            </div>
            <div class="component-card__actions">
                <div class="trigger-select-wrapper">
                    <div class="trigger-selector" data-action="toggleModuleAdminStatusSelect">
                        <div class="trigger-select-icon">
                            <span class="material-symbols-rounded"><?php echo $currentStatusIcon; ?></span>
                        </div>
                        <div class="trigger-select-text">
                            <span data-i18n="<?php echo htmlspecialchars($currentStatusKey); ?>"></span>
                        </div>
                        <div class="trigger-select-arrow">
                            <span class="material-symbols-rounded">arrow_drop_down</span>
                        </div>
                    </div>

                    <div class="popover-module popover-module--anchor-width body-title disabled"
                         data-module="moduleAdminStatusSelect">
                        <div class="menu-content">
                            <div class="menu-list">
                                <?php
                                foreach ($statusMap as $key => $textKey):
                                    $isActive = ($key === $currentStatus);
                                    $iconName = $statusIconMap[$key];
                                ?>
                                    <div class="menu-link <?php echo $isActive ? 'active' : ''; ?>"
                                         data-value="<?php echo htmlspecialchars($key); ?>">
                                        <div class="menu-link-icon">
                                            <span class="material-symbols-rounded"><?php echo $iconName; ?></span>
                                        </div>
                                        <div class="menu-link-text">
                                            <span data-i18n="<?php echo htmlspecialchars($textKey); ?>"></span>
                                        </div>
                                        <div class="menu-link-check-icon">
                                            <?php if ($isActive): ?>
                                                <span class="material-symbols-rounded">check</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div> 
            </div>
            
            <?php // --- BLOQUE DE DETALLES DE SUSPENSIÓN ELIMINADO --- ?>
        </div>
        
        <?php // --- Sección de Restricciones --- ?>
        <div id="admin-restrictions-section" class="<?php echo ($currentStatus === 'active') ? 'active' : 'disabled'; ?>">
            
            <div class="component-divider-header">
                <h3 class="component-card__title" data-i18n="admin.users.restrictionsTitle">Restricciones de Servicio</h3>
                <p class="component-card__description" data-i18n="admin.users.restrictionsDesc">Las restricciones solo se aplican si el estado general es "Activo".</p>
            </div>

            <?php
            // Helper Function para renderizar las tarjetas de restricción (SIMPLIFICADA)
            function render_restriction_card($id, $labelKey, $isChecked) {
                $checkedAttr = $isChecked ? 'checked' : '';
                $descKey = $labelKey . 'Desc';
                
                $html = "
                <div class='component-card component-card--edit-mode'>
                    <div class='component-card__content'>
                        <div class='component-card__text'>
                            <h2 class='component-card__title' data-i18n='$labelKey'></h2>
                            <p class='component-card__description' data-i18n='$descKey'></p>
                        </div>
                    </div>
                ";

                $html .= "
                        <div class='component-card__actions'>
                            <label class='component-toggle-switch'>
                                <input type='checkbox' class='admin-restriction-toggle' id='admin-restrict-$id' $checkedAttr>
                                <span class='component-toggle-slider'></span>
                            </label>
                        </div>
                </div>";
                
                echo $html;
            }

            render_restriction_card('publish', 'admin.users.restrictPublish', $isRestrictedPublish);
            render_restriction_card('comment', 'admin.users.restrictComment', $isRestrictedComment);
            render_restriction_card('message', 'admin.users.restrictMessage', $isRestrictedMessage);
            render_restriction_card('social', 'admin.users.restrictSocial', $isRestrictedSocial);
            
            ?>

        </div>
        
        <?php // --- TARJETA DE GUARDADO ELIMINADA ▼▼▼ --- ?>
        <?php /*
        <div class="component-card" id="admin-status-save-card">
            ... (CONTENIDO ELIMINADO) ...
        </div>
        */ ?>
        <?php // --- TARJETA DE GUARDADO ELIMINADA ▲▲▲ --- ?>

    </div>
</div>
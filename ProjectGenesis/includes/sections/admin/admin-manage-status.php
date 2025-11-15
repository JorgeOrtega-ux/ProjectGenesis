<?php
// FILE: includes/sections/admin/admin-manage-status.php
// (Refactorizado para usar componentes trigger-select y stepper, sin estilos inline)

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

// Formatear fecha de expiración de estado general
$statusExpiresDateStr = $manageUserData['status_expires_at'] ?? null;
$statusIsTemporary = !empty($statusExpiresDateStr);
$statusExpiresDays = $statusIsTemporary ? get_days_until_expiry($statusExpiresDateStr) : 1;

// Checar restricciones
$restrictions = $manageUserData['restrictions'] ?? [];

$isRestrictedPublish = isset($restrictions['CANNOT_PUBLISH']);
$publishExpiresDateStr = $isRestrictedPublish && $restrictions['CANNOT_PUBLISH'] ? $restrictions['CANNOT_PUBLISH'] : null;
$publishIsTemporary = !empty($publishExpiresDateStr);
$publishExpiresDays = $publishIsTemporary ? get_days_until_expiry($publishExpiresDateStr) : 1;

$isRestrictedComment = isset($restrictions['CANNOT_COMMENT']);
$commentExpiresDateStr = $isRestrictedComment && $restrictions['CANNOT_COMMENT'] ? $restrictions['CANNOT_COMMENT'] : null;
$commentIsTemporary = !empty($commentExpiresDateStr);
$commentExpiresDays = $commentIsTemporary ? get_days_until_expiry($commentExpiresDateStr) : 1;

$isRestrictedMessage = isset($restrictions['CANNOT_MESSAGE']);
$messageExpiresDateStr = $isRestrictedMessage && $restrictions['CANNOT_MESSAGE'] ? $restrictions['CANNOT_MESSAGE'] : null;
$messageIsTemporary = !empty($messageExpiresDateStr);
$messageExpiresDays = $messageIsTemporary ? get_days_until_expiry($messageExpiresDateStr) : 1;

$isRestrictedSocial = isset($restrictions['CANNOT_SOCIAL']);
$socialExpiresDateStr = $isRestrictedSocial && $restrictions['CANNOT_SOCIAL'] ? $restrictions['CANNOT_SOCIAL'] : null;
$socialIsTemporary = !empty($socialExpiresDateStr);
$socialExpiresDays = $socialIsTemporary ? get_days_until_expiry($socialExpiresDateStr) : 1;


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

// Mapa para los dropdowns de "Permanente" / "Temporal"
$expiryMap = [
    'permanent' => 'admin.users.permanent',
    'temporary' => 'admin.users.temporary'
];
$expiryIconMap = [
    'permanent' => 'event_busy',
    'temporary' => 'event_available'
];

?>

<div class="section-content overflow-y <?php echo ($CURRENT_SECTION === 'admin-manage-status') ? 'active' : 'disabled'; ?>" data-section="admin-manage-status">
    <div class="component-wrapper">
        
        <input type="hidden" id="admin-manage-status-user-id" value="<?php echo $userId; ?>">
        
        <input type="hidden" id="admin-manage-status-value" value="<?php echo $currentStatus; ?>">

        <div class="component-header-card">
            <h1 class="component-page-title">
                <span data-i18n="admin.users.manageStatusTitle">Gestionar Estado</span>: <?php echo $username; ?>
            </h1>
            <p class="component-page-description" data-i18n="admin.users.manageStatusDesc">Ajusta el estado de la cuenta y las restricciones de servicio para este usuario.</p>
        </div>

        <?php // --- Selector de Estado General (REUTILIZADO) --- ?>
        <div class="component-card component-card--column">
            <div class="component-card__content">
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="admin.users.generalStatus">Estado General</h2>
                    <p class="component-card__description">Elige el estado principal de la cuenta del usuario.</p>
                </div>
            </div>
            <div class="component-card__actions">
                <div class="trigger-select-wrapper" style="width: 100%;">
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
            
            <?php // --- ▼▼▼ INICIO DE MODIFICACIÓN (Tarjeta de Expiración) ▼▼▼ --- ?>
            <div class="component-card-sub-section <?php echo ($currentStatus === 'suspended') ? 'active' : 'disabled'; ?>" id="admin-status-suspension-details">

                <?php
                // --- Preparar variables para el dropdown de expiración ---
                $currentExpiryKey_Status = $statusIsTemporary ? 'temporary' : 'permanent';
                $currentExpiryIcon_Status = $expiryIconMap[$currentExpiryKey_Status];
                $currentExpiryTextKey_Status = $expiryMap[$currentExpiryKey_Status];
                ?>
                
                <div class="component-card__content" style="width: 100%; flex-direction: column; align-items: flex-start; gap: 8px; padding: 0;">
                    <label class="form-label">Duración de la Suspensión</label>
                    <div class="trigger-select-wrapper" style="width: 100%;">
                        <div class="trigger-selector" data-action="toggleModule-status-expiry">
                            <div class="trigger-select-icon">
                                <span class="material-symbols-rounded"><?php echo $currentExpiryIcon_Status; ?></span>
                            </div>
                            <div class="trigger-select-text">
                                <span data-i18n="<?php echo $currentExpiryTextKey_Status; ?>"></span>
                            </div>
                            <div class="trigger-select-arrow">
                                <span class="material-symbols-rounded">arrow_drop_down</span>
                            </div>
                        </div>
                        <div class="popover-module popover-module--anchor-width body-title disabled"
                             data-module="module-status-expiry">
                            <div class="menu-content">
                                <div class="menu-list">
                                    <?php foreach ($expiryMap as $key => $textKey):
                                        $isActive = ($key === $currentExpiryKey_Status);
                                        $iconName = $expiryIconMap[$key];
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
                                                <?php if ($isActive): ?><span class="material-symbols-rounded">check</span><?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div> 
                </div>

                <?php // --- (Stepper REUTILIZADO) --- ?>
                <div class="component-card-sub-section-target <?php echo $statusIsTemporary ? 'active' : 'disabled'; ?>" id="admin-status-expires-at-container">
                    
                    <div class="component-card component-card--column" style="padding: 0; border: none; background: none;">
                        <div class="component-card__content">
                            <div class="component-card__text">
                                <h2 class="component-card__title" data-i18n="admin.users.expiresInDays">Expira en (días)</h2>
                                <p class="component-card__description">Define cuántos días durará la suspensión.</p>
                            </div>
                        </div>
                        <div class="component-card__actions">
                            <div class="component-stepper component-stepper--multi"
                                id="admin-status-expires-stepper"
                                style="width: 100%;"
                                data-current-value="<?php echo $statusExpiresDays; ?>"
                                data-min="1" data-max="365" data-step-1="1" data-step-10="10">
                                
                                <button type="button" class="stepper-button" data-step-action="decrement-10" <?php echo ($statusExpiresDays <= 10) ? 'disabled' : ''; ?>>
                                    <span class="material-symbols-rounded">keyboard_double_arrow_left</span>
                                </button>
                                <button type="button" class="stepper-button" data-step-action="decrement-1" <?php echo ($statusExpiresDays <= 1) ? 'disabled' : ''; ?>>
                                    <span class="material-symbols-rounded">chevron_left</span>
                                </button>
                                <div class="stepper-value"><?php echo $statusExpiresDays; ?></div>
                                <button type="button" class="stepper-button" data-step-action="increment-1" <?php echo ($statusExpiresDays >= 365) ? 'disabled' : ''; ?>>
                                    <span class="material-symbols-rounded">chevron_right</span>
                                </button>
                                <button type="button" class="stepper-button" data-step-action="increment-10" <?php echo ($statusExpiresDays >= 356) ? 'disabled' : ''; ?>>
                                    <span class="material-symbols-rounded">keyboard_double_arrow_right</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
            <?php // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ --- ?>
        </div>
        
        <?php // --- Sección de Restricciones --- ?>
        <div id="admin-restrictions-section" class="<?php echo ($currentStatus === 'active') ? 'active' : 'disabled'; ?>" style="flex-direction: column; gap: 16px;">
            
            <div class="component-divider-header">
                <h3 class="component-card__title" data-i18n="admin.users.restrictionsTitle">Restricciones de Servicio</h3>
                <p class="component-card__description" data-i18n="admin.users.restrictionsDesc">Las restricciones solo se aplican si el estado general es "Activo".</p>
            </div>

            <?php
            // Helper Function para renderizar las tarjetas de restricción
            function render_restriction_card($id, $labelKey, $isChecked, $isTemporary, $expiresDays, $expiryMap, $expiryIconMap) {
                $checkedAttr = $isChecked ? 'checked' : '';
                
                $currentExpiryKey = $isTemporary ? 'temporary' : 'permanent';
                $currentExpiryIcon = $expiryIconMap[$currentExpiryKey];
                $currentExpiryTextKey = $expiryMap[$currentExpiryKey];

                $descKey = $labelKey . 'Desc';
                
                $html = "
                <div class='component-card component-card--edit-mode' style='flex-direction: column; align-items: stretch; gap: 0;'>
                    <div style='display: flex; align-items: center; justify-content: space-between; padding-bottom: 0;'>
                        <div class='component-card__content' style='padding: 0;'>
                            <div class='component-card__text'>
                                <h2 class='component-card__title' data-i18n='$labelKey'></h2>
                                <p class='component-card__description' data-i18n='$descKey'></p>
                            </div>
                        </div>
                ";

                $html .= "
                        <div class='component-card__actions' style='padding: 0;'>
                            <label class='component-toggle-switch'>
                                <input type='checkbox' class='admin-restriction-toggle' id='admin-restrict-$id' $checkedAttr>
                                <span class='component-toggle-slider'></span>
                            </label>
                        </div>
                    </div>
                    
                    <div class='component-card-sub-section " . ($isChecked ? 'active' : 'disabled') . "' id='admin-restrict-$id-details'>
                        
                        <div class='component-card__content' style='width: 100%; flex-direction: column; align-items: flex-start; gap: 8px; padding: 0;'>
                            <label class='form-label'>Duración de la Restricción</label>
                            <div class='trigger-select-wrapper' style='width: 100%;'>
                                <div class='trigger-selector' data-action='toggleModule-{$id}-expiry'>
                                    <div class='trigger-select-icon'>
                                        <span class='material-symbols-rounded'>{$currentExpiryIcon}</span>
                                    </div>
                                    <div class='trigger-select-text'>
                                        <span data-i18n='{$currentExpiryTextKey}'></span>
                                    </div>
                                    <div class='trigger-select-arrow'>
                                        <span class='material-symbols-rounded'>arrow_drop_down</span>
                                    </div>
                                </div>
                                <div class='popover-module popover-module--anchor-width body-title disabled'
                                     data-module='module-{$id}-expiry'>
                                    <div class='menu-content'>
                                        <div class='menu-list'>
                                            ";
                                            foreach ($expiryMap as $key => $textKey):
                                                $isActive = ($key === $currentExpiryKey);
                                                $iconName = $expiryIconMap[$key];
                                                $html .= "
                                                <div class='menu-link " . ($isActive ? 'active' : '') . "'
                                                     data-value='" . htmlspecialchars($key) . "'>
                                                    <div class='menu-link-icon'>
                                                        <span class='material-symbols-rounded'>{$iconName}</span>
                                                    </div>
                                                    <div class='menu-link-text'>
                                                        <span data-i18n='" . htmlspecialchars($textKey) . "'></span>
                                                    </div>
                                                    <div class='menu-link-check-icon'>
                                                        " . ($isActive ? "<span class='material-symbols-rounded'>check</span>" : "") . "
                                                    </div>
                                                </div>";
                                            endforeach;
                                        $html .= "
                                        </div>
                                    </div>
                                </div>
                            </div> 
                        </div>
                ";
                
                // --- (Stepper REUTILIZADO) ---
                $html .= "
                        <div class='component-card-sub-section-target " . ($isTemporary ? 'active' : 'disabled') . "' id='admin-restrict-$id-expires-at-container'>
                            
                            <div class='component-card component-card--column' style='padding: 0; border: none; background: none;'>
                                <div class='component-card__content'>
                                    <div class='component-card__text'>
                                        <h2 class='component-card__title' data-i18n='admin.users.expiresInDays'>Expira en (días)</h2>
                                        <p class='component-card__description'>Define cuántos días durará la restricción.</p>
                                    </div>
                                </div>
                                <div class='component-card__actions'>
                                    <div class='component-stepper component-stepper--multi'
                                        id='admin-restrict-$id-expires-stepper'
                                        style='width: 100%;'
                                        data-current-value='$expiresDays'
                                        data-min='1' data-max='365' data-step-1='1' data-step-10='10'>
                                        
                                        <button type'button' class='stepper-button' data-step-action='decrement-10' " . ($expiresDays <= 10 ? 'disabled' : '') . ">
                                            <span class='material-symbols-rounded'>keyboard_double_arrow_left</span>
                                        </button>
                                        <button type='button' class='stepper-button' data-step-action='decrement-1' " . ($expiresDays <= 1 ? 'disabled' : '') . ">
                                            <span class='material-symbols-rounded'>chevron_left</span>
                                        </button>
                                        <div class='stepper-value'>$expiresDays</div>
                                        <button type='button' class='stepper-button' data-step-action='increment-1' " . ($expiresDays >= 365 ? 'disabled' : '') . ">
                                            <span class='material-symbols-rounded'>chevron_right</span>
                                        </button>
                                        <button type='button' class='stepper-button' data-step-action='increment-10' " . ($expiresDays >= 356 ? 'disabled' : '') . ">
                                            <span class='material-symbols-rounded'>keyboard_double_arrow_right</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                        </div>";
                        
                $html .= "
                    </div>
                </div>";
                
                echo $html;
            }

            render_restriction_card('publish', 'admin.users.restrictPublish', $isRestrictedPublish, $publishIsTemporary, $publishExpiresDays, $expiryMap, $expiryIconMap);
            render_restriction_card('comment', 'admin.users.restrictComment', $isRestrictedComment, $commentIsTemporary, $commentExpiresDays, $expiryMap, $expiryIconMap);
            render_restriction_card('message', 'admin.users.restrictMessage', $isRestrictedMessage, $messageIsTemporary, $messageExpiresDays, $expiryMap, $expiryIconMap);
            render_restriction_card('social', 'admin.users.restrictSocial', $isRestrictedSocial, $socialIsTemporary, $socialExpiresDays, $expiryMap, $expiryIconMap);
            
            ?>

        </div>
        
        <div class="component-card" id="admin-status-save-card">
            <div class="component-card__content" style="flex-direction: column; align-items: stretch; gap: 16px; width: 100%;">
                
                <div id="admin-manage-status-error" class="component-card__error disabled" style="width: 100%;"></div>

                <div class="modal__footer modal__footer--small-buttons" style="padding: 0; border: none; justify-content: flex-end; width: 100%;">
                    <button type="button" class="modal__button-small modal__button-small--secondary" data-action="toggleSectionAdminManageUsers" data-i18n="admin.users.cancelButton">Cancelar</button>
                    <button type="button" class="modal__button-small modal__button-small--primary" id="admin-save-status-btn" data-i18n="admin.users.saveChanges">Guardar Cambios</button>
                </div>
            </div>
        </div>

    </div>
</div>
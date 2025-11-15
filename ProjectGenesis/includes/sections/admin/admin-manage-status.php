<?php
// FILE: includes/sections/admin/admin-manage-status.php
// (Refactorizado para usar steppers de días en lugar de datetime-local)

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
        
        // Si la diferencia es menor a 1 día pero en el futuro, redondear a 1
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

?>

<?php // --- INICIO DE CSS (Mover a components.css luego) --- ?>
<style>
    /* Estilos para las sub-secciones de expiración */
    .form-sub-section {
        width: 100%;
        padding: 16px;
        margin-top: 16px;
        border: 1px solid #00000020;
        border-radius: 8px;
        background-color: #f5f5fa;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }
    .form-sub-section--restriction {
         padding: 16px;
         margin-top: 0;
         border-top: 1px solid #00000020;
         border-top-left-radius: 0;
         border-top-right-radius: 0;
         margin-left: -24px;
         margin-right: -24px;
         margin-bottom: -24px;
         width: calc(100% + 48px);
    }
    
    .form-radio-group {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .form-radio-group--inline {
        flex-direction: row;
        gap: 24px;
    }
    .form-radio-label {
        display: flex;
        align-items: center;
        gap: 12px;
        cursor: pointer;
    }
    .form-radio-label input[type="radio"] {
        width: 18px;
        height: 18px;
        accent-color: #000;
        flex-shrink: 0;
    }
    .form-radio-label span {
        font-size: 15px;
        font-weight: 500;
        color: #1f2937;
    }
    .form-radio-description {
        font-size: 14px;
        color: #6b7280;
        padding-left: 30px; /* Alineado con el texto del radio */
    }
    
    .form-sub-section-target {
        display: none; /* Oculto por defecto */
    }

    .form-label {
        font-size: 13px;
        font-weight: 600;
        color: #6b7280;
        margin-bottom: 4px;
        display: block;
    }
    /* El input de fecha ya no se usa, pero se deja por si acaso */
    .form-input {
        width: 100%;
        height: 40px;
        padding: 0 12px;
        border: 1px solid #00000020;
        border-radius: 8px;
        font-size: 14px;
        outline: none;
        transition: border-color 0.2s, box-shadow 0.2s;
        background-color: #ffffff;
        color: #000;
    }
    .form-input:focus {
        border-color: #000;
    }
</style>
<?php // --- FIN DE CSS --- ?>


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

        <?php // --- Selector de Estado General --- ?>
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
        </div>
        
        <?php // --- Tarjeta de Expiración (para 'suspendido') --- ?>
        <div class="component-card" id="admin-status-suspension-details" style="display: <?php echo ($currentStatus === 'suspended') ? 'block' : 'none'; ?>;">
             <div class="component-card__content" style="width: 100%;">
                <div class="form-sub-section" style="padding: 0; margin: 0; border: none; background: none; width: 100%;">
                    <div class="form-radio-group form-radio-group--inline">
                        <label class="form-radio-label">
                            <input type="radio" name="status_expiry_type" value="permanent" <?php echo !$statusIsTemporary ? 'checked' : ''; ?>>
                            <span data-i18n="admin.users.permanent">Permanente</span>
                        </label>
                        <label class="form-radio-label">
                            <input type="radio" name="status_expiry_type" value="temporary" <?php echo $statusIsTemporary ? 'checked' : ''; ?>>
                            <span data-i18n="admin.users.temporary">Temporal</span>
                        </label>
                    </div>
                    
                    <?php // --- ▼▼▼ INICIO DE MODIFICACIÓN (Stepper) ▼▼▼ --- ?>
                    <div class="form-sub-section-target" id="admin-status-expires-at-container" style="display: <?php echo $statusIsTemporary ? 'block' : 'none'; ?>;">
                        <label class="form-label" data-i18n="admin.users.expiresInDays">Expira en (días):</label>
                        <div class="component-stepper component-stepper--multi"
                            id="admin-status-expires-stepper"
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
                    <?php // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ --- ?>
                    
                </div>
             </div>
        </div>


        <?php // --- Sección de Restricciones --- ?>
        <div id="admin-restrictions-section" style="display: <?php echo ($currentStatus === 'active') ? 'flex' : 'none'; ?>; flex-direction: column; gap: 16px;">
            
            <div class="component-card component-card--column" style="padding-bottom: 0;">
                <div class="component-card__content">
                    <h3 class="component-card__title" data-i18n="admin.users.restrictionsTitle">Restricciones de Servicio</h3>
                    <p class="component-card__description" data-i18n="admin.users.restrictionsDesc">Las restricciones solo se aplican si el estado general es "Activo".</p>
                </div>
            </div>

            <?php
            // Helper Function para renderizar las tarjetas de restricción
            function render_restriction_card($id, $labelKey, $isChecked, $isTemporary, $expiresDays) {
                $checkedAttr = $isChecked ? 'checked' : '';
                $displayStyle = $isChecked ? 'block' : 'none';
                $tempChecked = $isTemporary ? 'checked' : '';
                $permChecked = !$isTemporary ? 'checked' : '';
                
                $html = "
                <div class='component-card component-card--edit-mode' style='flex-direction: column; align-items: stretch; gap: 0;'>
                    <div style='display: flex; align-items: center; justify-content: space-between; padding-bottom: 16px;'>
                        <div class='component-card__content' style='padding: 0;'>
                            <div class='component-card__text'>
                                <h2 class='component-card__title' data-i18n='$labelKey'></h2>
                            </div>
                        </div>
                        <div class='component-card__actions' style='padding: 0;'>
                            <label class='component-toggle-switch'>
                                <input type='checkbox' class='admin-restriction-toggle' id='admin-restrict-$id' $checkedAttr>
                                <span class='component-toggle-slider'></span>
                            </label>
                        </div>
                    </div>
                    
                    <div class='form-sub-section form-sub-section--restriction' id='admin-restrict-$id-details' style='display: $displayStyle;'>
                        <div class='form-radio-group form-radio-group--inline'>
                            <label class='form-radio-label'>
                                <input type='radio' name='{$id}_expiry_type' value='permanent' $permChecked>
                                <span data-i18n='admin.users.permanent'>Permanente</span>
                            </label>
                            <label class='form-radio-label'>
                                <input type='radio' name='{$id}_expiry_type' value='temporary' $tempChecked>
                                <span data-i18n='admin.users.temporary'>Temporal</span>
                            </label>
                        </div>";
                
                // --- ▼▼▼ INICIO DE MODIFICACIÓN (Stepper) ▼▼▼ ---
                $html .= "
                        <div class='form-sub-section-target' id='admin-restrict-$id-expires-at-container' style='display: " . ($isTemporary ? 'block' : 'none') . ";'>
                            <label class='form-label' data-i18n='admin.users.expiresInDays'>Expira en (días):</label>
                            <div class='component-stepper component-stepper--multi'
                                id='admin-restrict-$id-expires-stepper'
                                data-current-value='$expiresDays'
                                data-min='1' data-max='365' data-step-1='1' data-step-10='10'>
                                
                                <button type='button' class='stepper-button' data-step-action='decrement-10' " . ($expiresDays <= 10 ? 'disabled' : '') . ">
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
                        </div>";
                // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
                        
                $html .= "
                    </div>
                </div>";
                
                echo $html;
            }

            render_restriction_card('publish', 'admin.users.restrictPublish', $isRestrictedPublish, $publishIsTemporary, $publishExpiresDays);
            render_restriction_card('comment', 'admin.users.restrictComment', $isRestrictedComment, $commentIsTemporary, $commentExpiresDays);
            render_restriction_card('message', 'admin.users.restrictMessage', $isRestrictedMessage, $messageIsTemporary, $messageExpiresDays);
            render_restriction_card('social', 'admin.users.restrictSocial', $isRestrictedSocial, $socialIsTemporary, $socialExpiresDays);
            
            ?>

        </div>
        
        <div class="component-card" id="admin-status-save-card">
            <div class="component-card__content" style="flex-direction: column; align-items: stretch; gap: 16px; width: 100%;">
                
                <div id="admin-manage-status-error" class="component-card__error" style="display: none; width: 100%;"></div>

                <div class="modal__footer modal__footer--small-buttons" style="padding: 0; border: none; justify-content: flex-end; width: 100%;">
                    <button type="button" class="modal__button-small modal__button-small--secondary" data-action="toggleSectionAdminManageUsers" data-i18n="admin.users.cancelButton">Cancelar</button>
                    <button type="button" class="modal__button-small modal__button-small--primary" id="admin-save-status-btn" data-i18n="admin.users.saveChanges">Guardar Cambios</button>
                </div>
            </div>
        </div>

    </div>
</div>
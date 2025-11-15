<?php
// FILE: includes/sections/admin/admin-manage-status.php (NUEVO)

// $manageUserData es cargado por config/routing/router.php
if (!isset($manageUserData) || !$manageUserData) {
    echo '<div class="component-wrapper"><p data-i18n="admin.users.errorLoadingUser">Error al cargar datos del usuario.</p></div>';
    return;
}

// --- Variables de ayuda para el formulario ---
$userId = $manageUserData['id'];
$username = htmlspecialchars($manageUserData['username'] ?? 'Usuario');
$currentStatus = $manageUserData['account_status'] ?? 'active';

// Formatear fecha de expiración de estado general
$statusExpires = $manageUserData['status_expires_at'] ? (new DateTime($manageUserData['status_expires_at']))->format('Y-m-d\TH:i') : '';
$statusIsTemporary = !empty($statusExpires);

// Checar restricciones
$restrictions = $manageUserData['restrictions'] ?? [];

$isRestrictedPublish = isset($restrictions['CANNOT_PUBLISH']);
$publishExpires = $isRestrictedPublish && $restrictions['CANNOT_PUBLISH'] ? (new DateTime($restrictions['CANNOT_PUBLISH']))->format('Y-m-d\TH:i') : '';
$publishIsTemporary = !empty($publishExpires);

$isRestrictedComment = isset($restrictions['CANNOT_COMMENT']);
$commentExpires = $isRestrictedComment && $restrictions['CANNOT_COMMENT'] ? (new DateTime($restrictions['CANNOT_COMMENT']))->format('Y-m-d\TH:i') : '';
$commentIsTemporary = !empty($commentExpires);

$isRestrictedMessage = isset($restrictions['CANNOT_MESSAGE']);
$messageExpires = $isRestrictedMessage && $restrictions['CANNOT_MESSAGE'] ? (new DateTime($restrictions['CANNOT_MESSAGE']))->format('Y-m-d\TH:i') : '';
$messageIsTemporary = !empty($messageExpires);

$isRestrictedSocial = isset($restrictions['CANNOT_SOCIAL']);
$socialExpires = $isRestrictedSocial && $restrictions['CANNOT_SOCIAL'] ? (new DateTime($restrictions['CANNOT_SOCIAL']))->format('Y-m-d\TH:i') : '';
$socialIsTemporary = !empty($socialExpires);

?>

<div class="section-content active" data-section="admin-manage-status">
    <div class="component-wrapper">
        
        <input type="hidden" id="admin-manage-status-user-id" value="<?php echo $userId; ?>">

        <div class="component-header-card">
            <h1 class="component-page-title">
                <span data-i18n="admin.users.manageStatusTitle">Gestionar Estado</span>: <?php echo $username; ?>
            </h1>
            <p class="component-page-description" data-i18n="admin.users.manageStatusDesc">Ajusta el estado de la cuenta y las restricciones de servicio para este usuario.</p>
        </div>

        <div class="component-card">
            <div class="component-card__content">
                <h3 class="component-card__title" data-i18n="admin.users.generalStatus" style="margin-bottom: 24px;">Estado General</h3>
                
                <div class="form-radio-group">
                    <label class="form-radio-label">
                        <input type="radio" name="general_status" value="active" <?php echo ($currentStatus === 'active') ? 'checked' : ''; ?>>
                        <span data-i18n="admin.users.statusActive">Activo</span>
                    </label>
                    <p class="form-radio-description" data-i18n="admin.users.statusActiveHelp">El usuario puede usar la plataforma (sujeto a restricciones).</p>
                </div>
                
                <div class="form-radio-group">
                    <label class="form-radio-label">
                        <input type="radio" name="general_status" value="suspended" <?php echo ($currentStatus === 'suspended') ? 'checked' : ''; ?>>
                        <span data-i18n="admin.users.statusSuspended">Suspendido</span>
                    </label>
                    <p class="form-radio-description" data-i18n="admin.users.statusSuspendedHelp">El usuario no podrá iniciar sesión temporal o permanentemente.</p>
                </div>

                <div class="form-sub-section" id="admin-status-suspension-details" style="display: <?php echo ($currentStatus === 'suspended') ? 'block' : 'none'; ?>;">
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
                    <div class="form-sub-section-target" id="admin-status-expires-at-container" style="display: <?php echo $statusIsTemporary ? 'block' : 'none'; ?>;">
                        <label for="admin-status-expires-at" class="form-label" data-i18n="admin.users.expiresAt">Expira el:</label>
                        <input type="datetime-local" class="form-input" id="admin-status-expires-at" value="<?php echo $statusExpires; ?>">
                    </div>
                </div>
                
                <div class="form-radio-group">
                    <label class="form-radio-label">
                        <input type="radio" name="general_status" value="deleted" <?php echo ($currentStatus === 'deleted') ? 'checked' : ''; ?>>
                        <span data-i18n="admin.users.statusDeleted">Eliminado (Inactivo)</span>
                    </label>
                    <p class="form-radio-description" data-i18n="admin.users.statusDeletedHelp">El usuario no podrá iniciar sesión y su contenido será ocultado.</p>
                </div>

            </div>
        </div>

        <div class="component-card" id="admin-restrictions-section" style="display: <?php echo ($currentStatus === 'active') ? 'block' : 'none'; ?>;">
            <div class="component-card__content">
                <h3 class="component-card__title" data-i18n="admin.users.restrictionsTitle" style="margin-bottom: 24px;">Restricciones de Servicio</h3>
                <p class="component-card__description" data-i18n="admin.users.restrictionsDesc" style="margin-bottom: 24px;">Las restricciones solo se aplican si el estado general es "Activo".</p>

                <?php
                // Helper Funtion (imaginaria) para no repetir HTML
                function render_restriction_row($id, $labelKey, $isChecked, $isTemporary, $expiresValue) {
                    $checkedAttr = $isChecked ? 'checked' : '';
                    $displayStyle = $isChecked ? 'block' : 'none';
                    $tempChecked = $isTemporary ? 'checked' : '';
                    $permChecked = !$isTemporary ? 'checked' : '';
                    
                    echo "
                    <div class='form-restriction-row'>
                        <div class='component-card--edit-mode' style='padding: 0; border: none;'>
                            <div class='component-card__content'>
                                <div class='component-card__text'>
                                    <h2 class='component-card__title' data-i18n='$labelKey'></h2>
                                </div>
                            </div>
                            <div class='component-card__actions'>
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
                                    <span data-i18n='admin.users.permanent'></span>
                                </label>
                                <label class='form-radio-label'>
                                    <input type='radio' name='{$id}_expiry_type' value='temporary' $tempChecked>
                                    <span data-i18n='admin.users.temporary'></span>
                                </label>
                            </div>
                            <div class='form-sub-section-target' id='admin-restrict-$id-expires-at-container' style='display: " . ($isTemporary ? 'block' : 'none') . ";'>
                                <label for='admin-restrict-$id-expires-at' class='form-label' data-i18n='admin.users.expiresAt'></label>
                                <input type='datetime-local' class='form-input' id='admin-restrict-$id-expires-at' value='$expiresValue'>
                            </div>
                        </div>
                    </div>";
                }

                render_restriction_row('publish', 'admin.users.restrictPublish', $isRestrictedPublish, $publishIsTemporary, $publishExpires);
                render_restriction_row('comment', 'admin.users.restrictComment', $isRestrictedComment, $commentIsTemporary, $commentExpires);
                render_restriction_row('message', 'admin.users.restrictMessage', $isRestrictedMessage, $messageIsTemporary, $messageExpires);
                render_restriction_row('social', 'admin.users.restrictSocial', $isRestrictedSocial, $socialIsTemporary, $socialExpires);
                
                ?>

            </div>
        </div>
        
        <div class="component-card">
            <div class="component-card__content" style="flex-direction: column; align-items: stretch; gap: 16px;">
                
                <div id="admin-manage-status-error" class="component-card__error" style="display: none;"></div>

                <div class="modal__footer modal__footer--small-buttons" style="padding: 0; border: none; justify-content: flex-end;">
                    <button type="button" class="modal__button-small modal__button-small--secondary" data-action="toggleSectionAdminManageUsers" data-i18n="admin.users.cancelButton">Cancelar</button>
                    <button type="button" class="modal__button-small modal__button-small--primary" id="admin-save-status-btn" data-i18n="admin.users.saveChanges">Guardar Cambios</button>
                </div>
            </div>
        </div>

    </div>
</div>
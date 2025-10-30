<?php
// FILE: includes/sections/settings/change-password.php
// (Se asume que config/router.php ya ha iniciado $pdo y la sesión)
?>

<div class="section-content <?php echo ($CURRENT_SECTION === 'settings-change-password') ? 'active' : 'disabled'; ?>" data-section="settings-change-password">
    <div class="settings-wrapper">

        <div class="settings-header-card">
            <h1 class="settings-title" data-i18n="settings.password.title">Actualizar Contraseña</h1>
            <p class="settings-description" data-i18n="settings.password.description">Para mayor seguridad, primero verifica tu identidad y luego crea tu nueva contraseña.</p>
        </div>

        <?php
        // Incluir el input CSRF una vez en la parte superior
        // para que esté disponible para todas las llamadas de API en esta página.
        outputCsrfInput();
        ?>

        <div class="settings-card settings-card--edit-mode" id="password-step-1">
            <div class="settings-card__content">
                <div class="settings-card__icon">
                    <span class="material-symbols-rounded">password</span>
                </div>
                <div class="settings-card__text">
                    <h2 class="settings-card__title" data-i18n="settings.login.modalVerifyTitle">Verifica tu identidad</h2>
                    <p class="settings-card__description" data-i18n="settings.login.modalVerifyDesc">Para continuar, por favor ingresa tu contraseña actual.</p>
                    
                    <div class="modal__input-group" style="margin-top: 8px;">
                        <input type="password" id="password-verify-current" name="current_password" class="modal__input" required placeholder=" ">
                        <label for="password-verify-current" data-i18n="settings.login.modalCurrentPass">Contraseña actual*</label>
                    </div>
                </div>
            </div>
            <div class="settings-card__actions">
                <button type="button" class="settings-button" id="password-verify-continue" data-i18n="settings.profile.continue">Continuar</button>
            </div>
            </div>

        <div class="settings-card settings-card--edit-mode" id="password-step-2" style="display: none;">
            <div class="settings-card__content">
                <div class="settings-card__icon">
                    <span class="material-symbols-rounded">lock_reset</span>
                </div>
                <div class="settings-card__text">
                    <h2 class="settings-card__title" data-i18n="settings.login.modalNewPassTitle">Crea una nueva contraseña</h2>
                    <p class="settings-card__description" data-i18n="settings.login.modalNewPassDesc">Tu nueva contraseña debe tener al menos 8 caracteres.</p>
                    
                    <div class="modal__input-group" style="margin-top: 8px;">
                        <input type="password" id="password-update-new" name="new_password" class="modal__input" required placeholder=" " minlength="8" maxlength="72">
                        <label for="password-update-new" data-i18n="settings.login.modalNewPass">Nueva contraseña*</label>
                    </div>
                    <div class="modal__input-group" style="margin-top: 8px;">
                        <input type="password" id="password-update-confirm" name="confirm_password" class="modal__input" required placeholder=" " minlength="8" maxlength="72">
                        <label for="password-update-confirm" data-i18n="settings.login.modalConfirmPass">Confirmar nueva contraseña*</label>
                    </div>
                </div>
            </div>
            <div class="settings-card__actions">
                <button type="button" class="settings-button" id="password-update-save" data-i18n="settings.login.savePassword">Guardar Contraseña</button>
            </div>
            </div>

    </div>
</div>
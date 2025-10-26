<?php
// FILE: jorgeortega-ux/projectgenesis/ProjectGenesis-98418948306e47bc505f1797114031c3351b5e33/ProjectGenesis/includes/sections/settings/login-security.php
?>
<div class="section-content <?php echo ($CURRENT_SECTION === 'settings-login') ? 'active' : 'disabled'; ?>" data-section="settings-login">
    <div class="settings-wrapper">

        <div class="settings-header-card">
            <h1 class="settings-title" data-i18n="settings.login.title"></h1>
            <p class="settings-description" data-i18n="settings.login.description"></p>
        </div>

        <div class="settings-card">
            <div class="settings-card-left">
                <div class="settings-card-icon">
                    <span class="material-symbols-rounded">lock</span>
                </div>
                <div class="settings-text-content">
                    <h2 class="settings-text-title" data-i18n="settings.login.password"></h2>

                    <p class="settings-text-description">
                        <?php
                        // Esta variable $lastPasswordUpdateText se define en config/router.php
                        // El texto dinámico ("Última actualización: ...") se maneja aquí.
                        // El JS de i18n no debe tocar esto.
                        echo htmlspecialchars($lastPasswordUpdateText);
                        ?>
                    </p>
                </div>
            </div>

            <div class="settings-card-right">
                <div class="settings-card-right-actions">
                    <button type="button" class="settings-button" id="password-edit-trigger" data-i18n="settings.login.update"></button>
                </div>
            </div>
        </div>

        <div class="settings-card">
            <div class="settings-card-left">
                <div class="settings-card-icon">
                    <span class="material-symbols-rounded">shield_lock</span>
                </div>
                <div class="settings-text-content">
                    <h2 class="settings-text-title" data-i18n="settings.login.2fa"></h2>
                    <p class="settings-text-description" id="tfa-status-text" data-i18n="<?php echo $is2faEnabled ? 'settings.login.2faEnabled' : 'settings.login.2faDisabled'; ?>">
                        <?php
                        // $is2faEnabled viene de config/router.php
                        // echo $is2faEnabled ? 'La autenticación de dos pasos está activa.' : 'Añade una capa extra de seguridad a tu cuenta.';
                        ?>
                    </p>
                </div>
            </div>

            <div class="settings-card-right">
                <div class="settings-card-right-actions">
                    <button type="button" 
                            class="settings-button <?php echo $is2faEnabled ? 'danger' : ''; ?>" 
                            id="tfa-toggle-button"
                            data-is-enabled="<?php echo $is2faEnabled ? '1' : '0'; ?>"
                            data-i18n="<?php echo $is2faEnabled ? 'settings.login.disable' : 'settings.login.enable'; ?>">
                        <?php // echo $is2faEnabled ? 'Deshabilitar' : 'Habilitar'; ?>
                    </button>
                    </div>
            </div>
        </div>

        <div class="settings-card settings-card-column">
            <div class="settings-card-left">
                <div class="settings-card-icon">
                    <span class="material-symbols-rounded">devices</span>
                </div>
                <div class="settings-text-content">
                    <h2 class="settings-text-title" data-i18n="settings.login.deviceSessions"></h2>
                    <p class="settings-text-description" data-i18n="settings.login.deviceSessionsDesc"></p>
                </div>
            </div>
            
            <div class="settings-card-bottom">
                <div class="settings-card-right-actions">
                    <button type="button" 
                            class="settings-button" 
                            data-action="toggleSectionSettingsDevices"
                            data-i18n="settings.login.manageDevices"> 
                    </button>
                </div>
            </div>
        </div>
        <div class="settings-card settings-card-column settings-card-danger">
            
            <div class="settings-text-content">
                <h2 class="settings-text-title" data-i18n="settings.login.deleteAccount"></h2>
                <p class="settings-text-description" data-i18n="settings.login.deleteAccountDesc"></p>
            </div>
            
            <div class="settings-card-bottom">
                <div class="settings-card-right-actions">
                    <button type="button" class="settings-button danger" data-i18n="settings.login.deleteAccountButton"></button>
                </div>
            </div>
        </div>
        </div>

    <div class="settings-modal-overlay" id="password-change-modal" style="display: none;">

        <button type="button" class="settings-modal-close-btn" id="password-verify-close">
            <span class="material-symbols-rounded">close</span>
        </button>

        <div class="settings-modal-content">

            <form class="auth-form" onsubmit="event.preventDefault();" novalidate>

                <fieldset class="auth-step active" data-step="1">
                    <h2 class="auth-title" data-i18n="settings.login.modalVerifyTitle"></h2>
                    <p class="auth-verification-text" data-i18n="settings.login.modalVerifyDesc"></p>

                    <div class="auth-error-message" id="password-verify-error" style="display: none;"></div>

                    <div class="auth-input-group">
                        <input type="password" id="password-verify-current" name="current_password" required placeholder=" ">
                        <label for="password-verify-current" data-i18n="settings.login.modalCurrentPass"></label>
                    </div>

                    <div class="auth-step-buttons">
                        <button type="button" class="auth-button" id="password-verify-continue" data-i18n="settings.profile.continue"></button>
                    </div>
                </fieldset>

                <fieldset class="auth-step" data-step="2" style="display: none;">
                    <h2 class="auth-title" data-i18n="settings.login.modalNewPassTitle"></h2>
                    <p class="auth-verification-text" data-i18n="settings.login.modalNewPassDesc"></p>

                    <div class="auth-error-message" id="password-update-error"></div>

                    <div class="auth-input-group">
                        <input type="password" id="password-update-new" name="new_password" required placeholder=" " minlength="8" maxlength="72">
                        <label for="password-update-new" data-i18n="settings.login.modalNewPass"></label>
                    </div>

                    <div class="auth-input-group">
                        <input type="password" id="password-update-confirm" name="confirm_password" required placeholder=" " minlength="8" maxlength="72">
                        <label for="password-update-confirm" data-i18n="settings.login.modalConfirmPass"></label>
                    </div>

                    <div class="auth-step-buttons">
                        <button type="button" class="auth-button-back" id="password-update-back" data-i18n="settings.login.back"></button>
                        <button type="button" class="auth-button" id="password-update-save" data-i18n="settings.login.savePassword"></button>
                    </div>
                </fieldset>

            </form>
        </div>
    </div>

    <div class="settings-modal-overlay" id="tfa-verify-modal" style="display: none;">
        <button type="button" class="settings-modal-close-btn" id="tfa-verify-close">
            <span class="material-symbols-rounded">close</span>
        </button>
        <div class="settings-modal-content">
            <form class="auth-form" onsubmit="event.preventDefault();" novalidate>
                <fieldset class="auth-step active">
                    <h2 class="auth-title" id="tfa-modal-title" data-i18n="settings.login.modalVerifyTitle"></h2>
                    <p class_id="tfa-modal-text" data-i18n="settings.login.modalVerifyDesc"></p>
                    <div class="auth-error-message" id="tfa-verify-error" style="display: none;"></div>
                    <div class="auth-input-group">
                        <input type="password" id="tfa-verify-password" name="current_password" required placeholder=" ">
                        <label for="tfa-verify-password" data-i1a8n="settings.login.modalCurrentPass"></label>
                    </div>
                    <div class="auth-step-buttons">
                        <button type="button" class="auth-button" id="tfa-verify-continue" data-i18n="settings.login.confirm"></button>
                    </div>
                </fieldset>
            </form>
        </div>
    </div>

    </div>
</div>
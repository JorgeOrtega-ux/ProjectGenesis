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
            <div class="settings-card__content">
                <div class="settings-card__icon">
                    <span class="material-symbols-rounded">lock</span>
                </div>
                <div class="settings-card__text">
                    <h2 class="settings-card__title" data-i18n="settings.login.password"></h2>
                    <p class="settings-card__description">
                        <?php
                        // Esta variable $lastPasswordUpdateText se define en config/router.php
                        echo htmlspecialchars($lastPasswordUpdateText);
                        ?>
                    </p>
                </div>
            </div>
            <div class="settings-card__actions">
                <button type="button" class="settings-button" id="password-edit-trigger" data-i18n="settings.login.update"></button>
            </div>
        </div>
        <div class="settings-card">
            <div class="settings-card__content">
                <div class="settings-card__icon">
                    <span class="material-symbols-rounded">shield_lock</span>
                </div>
                <div class="settings-card__text">
                    <h2 class="settings-card__title" data-i18n="settings.login.2fa"></h2>
                    <p class="settings-card__description" id="tfa-status-text" data-i18n="<?php echo $is2faEnabled ? 'settings.login.2faEnabled' : 'settings.login.2faDisabled'; ?>">
                    </p>
                </div>
            </div>
            <div class="settings-card__actions">
                <button type="button" 
                        class="settings-button <?php echo $is2faEnabled ? 'danger' : ''; ?>" 
                        id="tfa-toggle-button"
                        data-is-enabled="<?php echo $is2faEnabled ? '1' : '0'; ?>"
                        data-i18n="<?php echo $is2faEnabled ? 'settings.login.disable' : 'settings.login.enable'; ?>">
                </button>
            </div>
        </div>
        <div class="settings-card settings-card--action"> <div class="settings-card__content">
                <div class="settings-card__icon">
                    <span class="material-symbols-rounded">devices</span>
                </div>
                <div class="settings-card__text">
                    <h2 class="settings-card__title" data-i18n="settings.login.deviceSessions"></h2>
                    <p class="settings-card__description" data-i18n="settings.login.deviceSessionsDesc"></p>
                </div>
            </div>
            <div class="settings-card__actions">
                <button type="button" 
                        class="settings-button" 
                        data-action="toggleSectionSettingsDevices"
                        data-i18n="settings.login.manageDevices"> 
                </button>
            </div>
        </div>
        <div class="settings-card settings-card--action settings-card--danger"> <div class="settings-card__content">
                <div class="settings-card__text">
                    <h2 class="settings-card__title" data-i18n="settings.login.deleteAccount"></h2>
                    <p class="settings-card__description" data-i18n="settings.login.deleteAccountDesc"></p>
                </div>
            </div>
            <div class="settings-card__actions">
                <button type="button" class="settings-button danger" id="delete-account-trigger" data-i18n="settings.login.deleteAccountButton"></button>
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

    <div class="settings-modal-overlay" id="delete-account-modal" style="display: none;">
        <button type="button" class="settings-modal-close-btn" id="delete-account-close">
            <span class="material-symbols-rounded">close</span>
        </button>
        <div class="settings-modal-content">
            <form class="auth-form" onsubmit="event.preventDefault();" novalidate>
                <fieldset class="auth-step active" style="gap: 0;"> <h2 class="auth-title" data-i18n="settings.login.modalDeleteTitle" style="margin-bottom: 16px;"></h2>
                    <div class="delete-account-user-badge">
                        <img src="<?php echo htmlspecialchars($_SESSION['profile_image_url']); ?>" 
                             alt="Avatar"
                             class="delete-account-user-avatar">
                        <span class="delete-account-user-email"><?php echo htmlspecialchars($_SESSION['email']); ?></span>
                    </div>
                    <p class="auth-verification-text" data-i18n="settings.login.modalDeleteDesc" style="text-align: left; margin-top: 16px; margin-bottom: 16px;"></p>
                    <ul class="delete-account-bullets">
                        <li data-i18n="settings.login.modalDeleteBullet1"></li>
                        <li data-i18n="settings.login.modalDeleteBullet2"></li>
                    </ul>
                    <div class="auth-error-message" id="delete-account-error" style="display: none; margin-top: 16px;"></div>
                    <p class="auth-verification-text" data-i18n="settings.login.modalDeleteConfirmText" style="text-align: left; margin-top: 16px; margin-bottom: 8px; font-size: 14px;"></p>
                    <div class="auth-input-group">
                        <input type="password" id="delete-account-password" name="current_password" required placeholder=" ">
                        <label for="delete-account-password" data-i18n="settings.login.modalCurrentPass"></label>
                    </div>
                    <div class="auth-step-buttons" style="margin-top: 24px;"> <button type="button" class="auth-button-back" id="delete-account-cancel" style="flex: 1;" data-i18n="settings.devices.modalCancel"></button>
                        <button type="button" class="auth-button danger" id="delete-account-confirm" style="flex: 1; background-color: #c62828; border-color: #c62828;" data-i18n="settings.login.modalDeleteConfirm"></button>
                    </div>
                </fieldset>
            </form>
        </div>
    </div>
    
</div>
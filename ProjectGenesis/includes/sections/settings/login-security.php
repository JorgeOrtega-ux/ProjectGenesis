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

                    <p class="settings-card__description"
                       id="password-last-updated-text" 
                       data-i18n="<?php
                            echo htmlspecialchars($lastPasswordUpdateText);
                       ?>">
                        <?php /* Contenido rellenado por JS */ ?>
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

    <div class="modal-overlay" id="password-change-modal">
        <div class="modal-content">

            <div data-step="1">
                <div class="modal__header">
                    <h2 class="modal__title" data-i18n="settings.login.modalVerifyTitle"></h2>
                    <button type="button" class="modal-close-btn" id="password-verify-close">
                        <span class="material-symbols-rounded">close</span>
                    </button>
                </div>
                <div class="modal__body">
                    <p class="modal__description" data-i18n="settings.login.modalVerifyDesc"></p>
                    <div class="auth-error-message" id="password-verify-error" style="display: none;"></div>
                    <div class="modal__input-group">
                        <input type="password" id="password-verify-current" name="current_password" class="modal__input" required placeholder=" ">
                        <label for="password-verify-current" data-i18n="settings.login.modalCurrentPass"></label>
                    </div>
                </div>
                <div class="modal__footer">
                    <button type="button" class="modal__button modal__button--primary" id="password-verify-continue" data-i18n="settings.profile.continue"></button>
                </div>
            </div>

            <div data-step="2" style="display: none;">
                <div class="modal__header">
                     <h2 class="modal__title" data-i18n="settings.login.modalNewPassTitle"></h2>
                     <button type="button" class="modal-close-btn" data-action="close-modal"> <span class="material-symbols-rounded">close</span>
                    </button>
                </div>
                <div class="modal__body">
                    <p class="modal__description" data-i18n="settings.login.modalNewPassDesc"></p>
                    <div class="auth-error-message" id="password-update-error" style="display: none;"></div>
                    <div class="modal__input-group">
                        <input type="password" id="password-update-new" name="new_password" class="modal__input" required placeholder=" " minlength="8" maxlength="72">
                        <label for="password-update-new" data-i18n="settings.login.modalNewPass"></label>
                    </div>
                    <div class="modal__input-group">
                        <input type="password" id="password-update-confirm" name="confirm_password" class="modal__input" required placeholder=" " minlength="8" maxlength="72">
                        <label for="password-update-confirm" data-i18n="settings.login.modalConfirmPass"></label>
                    </div>
                </div>
                <div class="modal__footer">
                    <button type="button" class="modal__button modal__button--secondary" id="password-update-back" data-i18n="settings.login.back"></button>
                    <button type="button" class="modal__button modal__button--primary" id="password-update-save" data-i18n="settings.login.savePassword"></button>
                </div>
            </div>

        </div>
    </div>
    <div class="modal-overlay" id="tfa-verify-modal">
        <div class="modal-content">
            <div class="modal__header">
                 <h2 class="modal__title" id="tfa-modal-title" data-i18n="settings.login.modalVerifyTitle"></h2>
                <button type="button" class="modal-close-btn" id="tfa-verify-close">
                    <span class="material-symbols-rounded">close</span>
                </button>
            </div>
            <div class="modal__body">
                <p class="modal__description" id="tfa-modal-text" data-i18n="settings.login.modalVerifyDesc"></p>
                <div class="auth-error-message" id="tfa-verify-error" style="display: none;"></div>
                <div class="modal__input-group">
                    <input type="password" id="tfa-verify-password" name="current_password" class="modal__input" required placeholder=" ">
                    <label for="tfa-verify-password" data-i18n="settings.login.modalCurrentPass"></label>
                </div>
            </div>
            <div class="modal__footer">
                 <button type="button" class="modal__button modal__button--primary" id="tfa-verify-continue" data-i18n="settings.login.confirm"></button>
            </div>
        </div>
    </div>
    <div class="modal-overlay" id="delete-account-modal">
        <div class="modal-content">
            <div class="modal__header">
                <h2 class="modal__title" data-i18n="settings.login.modalDeleteTitle"></h2>
                <button type="button" class="modal-close-btn" id="delete-account-close">
                    <span class="material-symbols-rounded">close</span>
                </button>
            </div>
            <div class="modal__body">
                <div class="delete-account-user-badge" style="margin-bottom: 8px;">
                    <img src="<?php echo htmlspecialchars($_SESSION['profile_image_url']); ?>"
                         alt="Avatar"
                         class="delete-account-user-avatar">
                    <span class="delete-account-user-email"><?php echo htmlspecialchars($_SESSION['email']); ?></span>
                </div>
                <p class="modal__description" data-i18n="settings.login.modalDeleteDesc"></p>

                <ul class="modal__list">
                    <li data-i18n="settings.login.modalDeleteBullet1"></li>
                    <li data-i18n="settings.login.modalDeleteBullet2"></li>
                </ul>

                <div class="auth-error-message" id="delete-account-error" style="display: none;"></div>

                <p class="modal__description" data-i18n="settings.login.modalDeleteConfirmText" style="font-size: 14px; margin-top: 8px;"></p>

                <div class="modal__input-group">
                    <input type="password" id="delete-account-password" name="current_password" class="modal__input" required placeholder=" ">
                    <label for="delete-account-password" data-i18n="settings.login.modalCurrentPass"></label>
                </div>
            </div>
            <div class="modal__footer">
                 <button type="button" class="modal__button modal__button--secondary" id="delete-account-cancel" data-i18n="settings.devices.modalCancel"></button>
                 <button type="button" class="modal__button modal__button--danger" id="delete-account-confirm" data-i18n="settings.login.modalDeleteConfirm"></button>
            </div>
        </div>
    </div>
    </div>
</div>
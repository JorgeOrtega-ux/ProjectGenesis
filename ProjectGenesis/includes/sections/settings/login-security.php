<?php
// FILE: includes/sections/settings/login-security.php
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
                <a href="<?php echo $basePath; ?>/settings/change-password"
                   class="settings-button"
                   data-nav-js
                   data-i18n="settings.login.update">
                </a>
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

    <div class="modal-overlay" id="tfa-verify-modal">
        <div class="modal-content">
            <div class="modal__header">
                 <h2 class="modal__title" id="tfa-modal-title" data-i18n="settings.login.modalVerifyTitle"></h2>
                 </div>
            <div class="modal__body">
                <p class="modal__description" id="tfa-modal-text" data-i18n="settings.login.modalVerifyDesc"></p>
                <div class="auth-error-message" id="tfa-verify-error" style="display: none;"></div>
                <div class="modal__input-group">
                    <input type="password" id="tfa-verify-password" name="current_password" class="modal__input" required placeholder=" ">
                    <label for="tfa-verify-password" data-i18n="settings.login.modalCurrentPass"></label>
                </div>
            </div>
            <div class="modal__footer modal__footer--small-buttons">
                 <button type="button" class="modal__button-small modal__button-small--secondary" id="tfa-verify-cancel" data-i18n="settings.devices.modalCancel"></button>
                 <button type="button" class="modal__button-small modal__button-small--primary" id="tfa-verify-continue" data-i18n="settings.login.confirm"></button>
            </div>
        </div>
    </div>
    <div class="modal-overlay" id="delete-account-modal">
        <div class="modal-content">
            <div class="modal__header">
                <h2 class="modal__title" data-i18n="settings.login.modalDeleteTitle" style="padding-right: 0;"></h2>
            </div>

            <div class="modal__body">
                
                <div class="modal-warning-box" style="background-color: #fbebee;">
                    <span class="material-symbols-rounded" style="color: #c62828;">error</span>
                    <p data-i18n="settings.login.modalDeleteWarning"></p>
                </div>

                <p class="modal__description" data-i18n="settings.login.modalDeleteLosingTitle"></p>

                <ul class="modal__list">
                    <li data-i18n="settings.login.modalDeleteBullet1"></li>
                    <li data-i18n="settings.login.modalDeleteBullet2"></li>
                    <li data-i18n="settings.login.modalDeleteBullet3"></li>
                </ul>

                <div class="auth-error-message" id="delete-account-error" style="display: none;"></div>

                <p class="modal__description" style="margin-bottom: -8px;" data-i18n="settings.login.modalDeleteConfirmText"></p>

                <div class="modal__input-group">
                    <input type="password" 
                           id="delete-account-password" 
                           name="current_password" 
                           class="modal__input" 
                           required 
                           placeholder=" ">
                    <label for="delete-account-password" data-i18n="settings.login.modalDeletePasswordLabel"></label>
                </div>
            </div>

            <div class="modal__footer modal__footer--small-buttons">
                 <button type="button" class="modal__button-small modal__button-small--secondary" id="delete-account-cancel" data-i18n="settings.devices.modalCancel"></button>
                 <button type="button" class="modal__button-small modal__button-small--danger" id="delete-account-confirm" data-i18n="settings.login.modalDeleteConfirm" disabled></button>
            </div>
        </div>
    </div>
    
    </div>
</div>
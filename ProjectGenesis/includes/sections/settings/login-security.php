<?php
// FILE: includes/sections/settings/login-security.php

// Estas variables ($lastPasswordUpdateText, $is2faEnabled, $deleteAccountDescText) 
// son cargadas por config/router.php
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
                <button type="button"
                   class="settings-button"
                   data-action="toggleSectionSettingsPassword"
                   data-i18n="settings.login.update">
                </button>
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
                   data-action="toggleSectionSettingsToggle2fa"
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
                    <p class="settings-card__description" 
                       data-i18n="<?php echo htmlspecialchars($deleteAccountDescText); ?>">
                        <?php /* Contenido rellenado por JS */ ?>
                    </p>
                    </div>
            </div>
            <div class="settings-card__actions">
                <button type="button" 
                   class="settings-button danger" 
                   data-action="toggleSectionSettingsDeleteAccount"
                   data-i18n="settings.login.deleteAccountButton">
                </button>
            </div>
        </div>
        </div>

    </div>
</div>
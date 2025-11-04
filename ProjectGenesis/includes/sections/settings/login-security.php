<?php
// FILE: includes/sections/settings/login-security.php

// Estas variables ($lastPasswordUpdateText, $is2faEnabled, $deleteAccountDescText, $accountCreationDateText) 
// son cargadas por config/router.php
?>
<div class="section-content overflow-y <?php echo ($CURRENT_SECTION === 'settings-login') ? 'active' : 'disabled'; ?>" data-section="settings-login">
    <div class="component-wrapper">

        <div class="component-header-card">
            <h1 class="component-page-title" data-i18n="settings.login.title"></h1>
            <p class="component-page-description" data-i18n="settings.login.description"></p>
        </div>

        <div class="component-card">
            <div class="component-card__content">
                <div class="component-card__icon">
                    <span class="material-symbols-rounded">lock</span>
                </div>
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="settings.login.password"></h2>

                    <p class="component-card__description"
                       id="password-last-updated-text" 
                       data-i18n="<?php
                            echo htmlspecialchars($lastPasswordUpdateText);
                       ?>">
                        <?php /* Contenido rellenado por JS */ ?>
                    </p>
                    </div>
            </div>
            <div class="component-card__actions">
                <button type="button"
                   class="component-button"
                   data-action="toggleSectionSettingsPassword"
                   data-i18n="settings.login.update">
                </button>
                </div>
        </div>
        
        <div class="component-card">
            <div class="component-card__content">
                <div class="component-card__icon">
                    <span class="material-symbols-rounded">shield_lock</span>
                </div>
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="settings.login.2fa"></h2>
                    <p class="component-card__description" id="tfa-status-text" data-i18n="<?php echo $is2faEnabled ? 'settings.login.2faEnabled' : 'settings.login.2faDisabled'; ?>">
                    </p>
                </div>
            </div>
            <div class="component-card__actions">
                <button type="button"
                   class="component-button <?php echo $is2faEnabled ? 'danger' : ''; ?>"
                   data-action="toggleSectionSettingsToggle2fa"
                   data-i18n="<?php echo $is2faEnabled ? 'settings.login.disable' : 'settings.login.enable'; ?>">
                </button>
            </div>
        </div>
        <div class="component-card component-card--action"> <div class="component-card__content">
                <div class="component-card__icon">
                    <span class="material-symbols-rounded">devices</span>
                </div>
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="settings.login.deviceSessions"></h2>
                    <p class="component-card__description" data-i18n="settings.login.deviceSessionsDesc"></p>
                </div>
            </div>
            <div class="component-card__actions">
                <button type="button"
                        class="component-button"
                        data-action="toggleSectionSettingsDevices"
                        data-i18n="settings.login.manageDevices">
                </button>
            </div>
        </div>
        
        <div class="component-card component-card--action component-card--danger"> <div class="component-card__content">
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="settings.login.deleteAccount"></h2>
                    
                    <p class="component-card__description">
                        <span data-i18n="<?php echo htmlspecialchars($deleteAccountDescText); ?>">
                            <?php /* El JS pondrá aquí el texto de advertencia */ ?>
                        </span>
                        <?php if (!empty($accountCreationDateText)): ?>
                            <span style="color: #6b7280;"><?php echo ' ' . htmlspecialchars($accountCreationDateText); ?></span>
                        <?php endif; ?>
                    </p>
                    </div>
            </div>
            <div class="component-card__actions">
                <button type="button" 
                   class="component-button danger" 
                   data-action="toggleSectionSettingsDeleteAccount"
                   data-i18n="settings.login.deleteAccountButton">
                </button>
            </div>
        </div>
        </div>

    </div>
</div>
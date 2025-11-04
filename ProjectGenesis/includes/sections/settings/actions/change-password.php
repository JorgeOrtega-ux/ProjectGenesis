<?php
// FILE: includes/sections/settings/actions/change-password.php
// (Se asume que config/router.php ya ha iniciado $pdo y la sesión)
?>

<div class="section-content overflow-y <?php echo ($CURRENT_SECTION === 'settings-change-password') ? 'active' : 'disabled'; ?>" data-section="settings-change-password">
    <div class="component-wrapper">

        <div class="component-header-card">
            <h1 class="component-page-title" data-i18n="settings.password.title"></h1>
            <p class="component-page-description" data-i18n="settings.password.description"></p>
        </div>

        <?php
        outputCsrfInput();
        ?>

        <div class="component-card component-card--action active" id="password-step-1">
        <div class="component-card__content">
                <div class="component-card__icon">
                    <span class="material-symbols-rounded">password</span>
                </div>
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="settings.login.modalVerifyTitle"></h2>
                    <p class="component-card__description" data-i18n="settings.login.modalVerifyDesc"></p>
                </div>
            </div>
            
            <div class="component-input-group">
                <input type="password" id="password-verify-current" name="current_password" class="component-input" required placeholder=" ">
                <label for="password-verify-current" data-i18n="settings.login.modalCurrentPass"></label>
            </div>

            <div class="component-card__actions">
                <button type="button" class="component-action-button component-action-button--secondary" data-action="toggleSectionSettingsLogin" data-i18n="settings.profile.cancel"></button>
                <button type="button" class="component-action-button component-action-button--primary" id="password-verify-continue" data-i18n="settings.profile.continue"></button>
            </div>
        </div>
        <div class="component-card component-card--action disabled" id="password-step-2">
        <div class="component-card__content">
                <div class="component-card__icon">
                    <span class="material-symbols-rounded">lock_reset</span>
                </div>
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="settings.login.modalNewPassTitle"></h2>
                    <p class="component-card__description" data-i18n="settings.login.modalNewPassDesc"></p>
                </div>
            </div>
            
            <div class="component-input-group">
                <input type="password" id="password-update-new" name="new_password" class="component-input" required placeholder=" " minlength="8" maxlength="72">
                <label for="password-update-new" data-i18n="settings.login.modalNewPass"></label>
            </div>
            <div class="component-input-group">
                <input type="password" id="password-update-confirm" name="confirm_password" class="component-input" required placeholder=" " minlength="8" maxlength="72">
                <label for="password-update-confirm" data-i18n="settings.login.modalConfirmPass"></label>
            </div>

            <div class="component-checkbox-label">
                <div class="component-card__text">
                    <label for="password-logout-others" class="component-card__title" data-i18n="settings.password.logoutOthersLabel"></label>
                    <p class="component-card__description" data-i18n="settings.password.logoutOthersDesc"></p>
                </div>
                <label class="component-toggle-switch">
                    <input type="checkbox"
                           id="password-logout-others"
                           name="logout_others"
                           checked>
                    <span class="component-toggle-slider"></span>
                </label>
            </div>
            <div class="component-card__actions">
                <button type="button" class="component-action-button component-action-button--secondary" data-action="toggleSectionSettingsLogin" data-i18n="settings.profile.cancel"></button>
                <button type="button" class="component-action-button component-action-button--primary" id="password-update-save" data-i18n="settings.login.savePassword"></button>
            </div>
        </div>
    </div>
</div>
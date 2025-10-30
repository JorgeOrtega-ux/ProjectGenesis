<?php
// FILE: includes/sections/settings/actions/change-password.php
// (Se asume que config/router.php ya ha iniciado $pdo y la sesión)
?>

<div class="section-content <?php echo ($CURRENT_SECTION === 'settings-change-password') ? 'active' : 'disabled'; ?>" data-section="settings-change-password">
    <div class="settings-wrapper">

        <div class="settings-header-card">
            <h1 class="settings-title" data-i18n="settings.password.title"></h1>
            <p class="settings-description" data-i18n="settings.password.description"></p>
        </div>

        <?php
        outputCsrfInput();
        ?>

        <div class="settings-card settings-card--column" id="password-step-1" style="gap: 16px;">
            
            <div class="settings-card__content">
                <div class="settings-card__icon">
                    <span class="material-symbols-rounded">password</span>
                </div>
                <div class="settings-card__text">
                    <h2 class="settings-card__title" data-i18n="settings.login.modalVerifyTitle"></h2>
                    <p class="settings-card__description" data-i18n="settings.login.modalVerifyDesc"></p>
                </div>
            </div>
            
            <div class="modal__input-group" style="width: 100%;">
                <input type="password" id="password-verify-current" name="current_password" class="modal__input" required placeholder=" ">
                <label for="password-verify-current" data-i18n="settings.login.modalCurrentPass"></label>
            </div>

            <div class="settings-card__actions">
                <button type="button" class="settings-button" data-action="toggleSectionSettingsLogin" data-i18n="settings.profile.cancel"></button>
                <button type="button" class="settings-button" id="password-verify-continue" data-i18n="settings.profile.continue"></button>
            </div>
        </div>
        <div class="settings-card settings-card--column" id="password-step-2" style="display: none; gap: 16px;">
            
            <div class="settings-card__content">
                <div class="settings-card__icon">
                    <span class="material-symbols-rounded">lock_reset</span>
                </div>
                <div class="settings-card__text">
                    <h2 class="settings-card__title" data-i18n="settings.login.modalNewPassTitle"></h2>
                    <p class="settings-card__description" data-i18n="settings.login.modalNewPassDesc"></p>
                </div>
            </div>
            
            <div class="modal__input-group" style="width: 100%;">
                <input type="password" id="password-update-new" name="new_password" class="modal__input" required placeholder=" " minlength="8" maxlength="72">
                <label for="password-update-new" data-i18n="settings.login.modalNewPass"></label>
            </div>
            <div class="modal__input-group" style="width: 100%;">
                <input type="password" id="password-update-confirm" name="confirm_password" class="modal__input" required placeholder=" " minlength="8" maxlength="72">
                <label for="password-update-confirm" data-i18n="settings.login.modalConfirmPass"></label>
            </div>

            <div class="settings-card__actions">
                <button type="button" class="settings-button" data-action="toggleSectionSettingsLogin" data-i18n="settings.profile.cancel"></button>
                <button type="button" class="settings-button" id="password-update-save" data-i18n="settings.login.savePassword"></button>
            </div>
        </div>
        </div>
</div>
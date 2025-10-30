<?php
// FILE: includes/sections/settings/toggle-2fa.php

// 1. OBTENER DATOS PRECARGADOS (de config/router.php)
// Esta variable ($is2faEnabled) será cargada por config/router.php
?>

<div class="section-content <?php echo ($CURRENT_SECTION === 'settings-toggle-2fa') ? 'active' : 'disabled'; ?>" data-section="settings-toggle-2fa">
    <div class="settings-wrapper">

        <div class="settings-header-card">
            <h1 class="settings-title" data-i18n="settings.login.2fa"></h1>
            <p class="settings-description" data-i18n="<?php echo $is2faEnabled ? 'settings.2fa.descDisable' : 'settings.2fa.descEnable'; ?>"></p>
        </div>

        <?php
        // Incluir el input CSRF
        outputCsrfInput();
        ?>

        <div class="settings-card settings-card--column" id="2fa-step-1-verify">
        <div class="settings-card__content">
                <div class="settings-card__icon">
                    <span class="material-symbols-rounded">password</span>
                </div>
                <div class="settings-card__text">
                    <h2 class="settings-card__title" data-i18n="<?php echo $is2faEnabled ? 'settings.2fa.titleDisable' : 'settings.2fa.titleEnable'; ?>"></h2>
                    <p class="settings-card__description" data-i18n="settings.login.modalVerifyDesc"></p>
                    
                    <div class="modal__input-group" style="margin-top: 8px;">
                        <input type="password" id="tfa-verify-password" name="current_password" class="modal__input" required placeholder=" ">
                        <label for="tfa-verify-password" data-i18n="settings.login.modalCurrentPass"></label>
                    </div>
                </div>
            </div>
            <div class="settings-card__actions">
                <button type="button" class="settings-button" data-action="toggleSectionSettingsLogin" data-i18n="settings.profile.cancel"></button>
                <button type="button" class="settings-button" id="tfa-verify-continue" data-i18n="<?php echo $is2faEnabled ? 'settings.login.disable' : 'settings.login.enable'; ?>"></button>
            </div>
        </div>

    </div>
</div>
<?php
// FILE: includes/sections/settings/actions/change-email.php

// 1. OBTENER DATOS PRECARGADOS (de config/router.php)
// Estas variables ($userEmail, $initialEmailCooldown)
// serán cargadas por config/router.php
?>

<div class="section-content overflow-y <?php echo ($CURRENT_SECTION === 'settings-change-email') ? 'active' : 'disabled'; ?>" data-section="settings-change-email">
    <div class="component-wrapper">

        <div class="component-header-card">
            <h1 class="component-page-title" data-i18n="settings.profile.email"></h1>
            <p class="component-page-description" data-i18n="settings.email.description"></p>
        </div>

        <?php
        // Incluir el input CSRF
        outputCsrfInput();
        ?>

        <div class="component-card component-card--action active" id="email-step-1-verify">
        <div class="component-card__content">
                <div class="component-card__icon">
                    <span class="material-symbols-rounded">password</span>
                </div>
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="settings.profile.modalCodeTitle"></h2>
                    <p class="component-card__description">
                        <span data-i18n="settings.profile.modalCodeDesc"></span> 
                        <strong><?php echo htmlspecialchars($userEmail); ?></strong>
                    </p>
                </div>
            </div>
            
            <div class="component-input-group">
                <input type="text" id="email-verify-code" name="verification_code" class="component-input" required placeholder=" " maxlength="14">
                <label for="email-verify-code" data-i18n="settings.profile.modalCodeLabel"></label>
            </div>
            <p class="component-card__description" style="text-align: left; width: 100%; margin: 0;">
                <span data-i18n="settings.profile.modalCodeResendP"></span>
                
                <a id="email-verify-resend" 
                   data-i18n="page.register.resendCode"
                   data-cooldown="<?php echo isset($initialEmailCooldown) ? $initialEmailCooldown : 0; ?>"
                   class="<?php echo (isset($initialEmailCooldown) && $initialEmailCooldown > 0) ? 'disabled-interactive' : ''; ?>"
                   style="color: #000; font-weight: 600; text-decoration: none; cursor: pointer;"
                >
                   <?php 
                   if (isset($initialEmailCooldown) && $initialEmailCooldown > 0) {
                       echo " (" . $initialEmailCooldown . "s)";
                   }
                   ?>
                </a>
            </p>
            <div class="component-card__actions">
                <button type="button" class="component-action-button component-action-button--secondary" data-action="toggleSectionSettingsProfile" data-i18n="settings.profile.cancel"></button>
                <button type="button" class="component-action-button component-action-button--primary" id="email-verify-continue" data-i18n="settings.profile.continue"></button>
            </div>
        </div>

        <div class="component-card component-card--action disabled" id="email-step-2-update">
        <div class="component-card__content">
                <div class="component-card__icon">
                    <span class="material-symbols-rounded">mark_email_read</span>
                </div>
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="settings.email.newEmailTitle"></h2>
                    <p class="component-card__description" data-i18n="settings.email.newEmailDesc"></p>
                </div>
            </div>
            
            <div class="component-input-group">
                <input type="email"
                   class="component-input"
                   id="email-input-new"
                   name="email"
                   value="<?php echo htmlspecialchars($userEmail); ?>"
                   required
                   maxlength="255">
                <label for="email-input-new" data-iRed="settings.email.newEmailLabel"></label>
            </div>

            <div class="component-card__actions">
                 <button type="button" class="component-action-button component-action-button--secondary" data-action="toggleSectionSettingsProfile" data-i18n="settings.profile.cancel"></button>
                <button type="button" class="component-action-button component-action-button--primary" id="email-save-trigger-btn" data-i18n="settings.profile.save"></button>
            </div>
        </div>
    </div>
</div>
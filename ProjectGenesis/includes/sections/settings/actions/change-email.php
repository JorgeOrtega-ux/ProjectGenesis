<?php
// FILE: includes/sections/settings/actions/change-email.php

// 1. OBTENER DATOS PRECARGADOS (de config/router.php)
// Estas variables ($userEmail, $initialEmailCooldown)
// serán cargadas por config/router.php
?>

<div class="section-content overflow-y <?php echo ($CURRENT_SECTION === 'settings-change-email') ? 'active' : 'disabled'; ?>" data-section="settings-change-email">
    <div class="settings-wrapper">

        <div class="settings-header-card">
            <h1 class="settings-title" data-i18n="settings.profile.email"></h1>
            <p class="settings-description" data-i18n="settings.email.description"></p>
        </div>

        <?php
        // Incluir el input CSRF
        outputCsrfInput();
        ?>

        <div class="settings-card settings-card--action" id="email-step-1-verify">
            <div class="settings-card__content">
                <div class="settings-card__icon">
                    <span class="material-symbols-rounded">password</span>
                </div>
                <div class="settings-card__text">
                    <h2 class="settings-card__title" data-i18n="settings.profile.modalCodeTitle"></h2>
                    <p class="settings-card__description">
                        <span data-i18n="settings.profile.modalCodeDesc"></span> 
                        <strong><?php echo htmlspecialchars($userEmail); ?></strong>
                    </p>
                </div>
            </div>
            
            <div class="settings-input-group">
                <input type="text" id="email-verify-code" name="verification_code" class="settings-input" required placeholder=" " maxlength="14">
                <label for="email-verify-code" data-i18n="settings.profile.modalCodeLabel"></label>
            </div>
            <p class="settings-card__description" style="text-align: left; width: 100%; margin: 0;">
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
            <div class="settings-card__actions">
                <button type="button" class="settings-action-button settings-action-button--secondary" data-action="toggleSectionSettingsProfile" data-i18n="settings.profile.cancel"></button>
                <button type="button" class="settings-action-button settings-action-button--primary" id="email-verify-continue" data-i18n="settings.profile.continue"></button>
            </div>
        </div>

        <div class="settings-card settings-card--action" id="email-step-2-update" style="display: none;">
            <div class="settings-card__content">
                <div class="settings-card__icon">
                    <span class="material-symbols-rounded">mark_email_read</span>
                </div>
                <div class="settings-card__text">
                    <h2 class="settings-card__title" data-i18n="settings.email.newEmailTitle"></h2>
                    <p class="settings-card__description" data-i18n="settings.email.newEmailDesc"></p>
                </div>
            </div>
            
            <div class="settings-input-group">
                <input type="email"
                   class="settings-input"
                   id="email-input-new"
                   name="email"
                   value="<?php echo htmlspecialchars($userEmail); ?>"
                   required
                   maxlength="255">
                <label for="email-input-new" data-i1Red="settings.email.newEmailLabel"></label>
            </div>

            <div class="settings-card__actions">
                 <button type="button" class="settings-action-button settings-action-button--secondary" data-action="toggleSectionSettingsProfile" data-i18n="settings.profile.cancel"></button>
                <button type="button" class="settings-action-button settings-action-button--primary" id="email-save-trigger-btn" data-i18n="settings.profile.save"></button>
            </div>
        </div>
    </div>
</div>
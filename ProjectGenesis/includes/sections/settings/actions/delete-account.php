<?php
// FILE: includes/sections/settings/actions/delete-account.php

// 1. OBTENER DATOS PRECARGADOS (de config/router.php)
// Estas variables ($userEmail, $profileImageUrl)
// son cargadas por config/router.php
?>

<div class="section-content overflow-y <?php echo ($CURRENT_SECTION === 'settings-delete-account') ? 'active' : 'disabled'; ?>" data-section="settings-delete-account">
    <div class="component-wrapper">

        <div class="component-header-card">
            <h1 class="component-page-title" data-i18n="settings.login.modalDeleteTitle"></h1>
            
            <p class="component-page-description" data-i18n="settings.login.deleteAccountDesc"></p>
            
        </div>

        <?php
        // Incluir el input CSRF
        outputCsrfInput();
        ?>

        <div class="component-card component-card--action component-card--danger">
            <div class="component-card__content">
                <div class="component-card__text">
                    
                    <div class="component-warning-box" style="margin-bottom: 16px;">
                        <span class="material-symbols-rounded">error</span>
                        <p data-i18n="settings.login.modalDeleteWarning"></p>
                    </div>

                    <p class="component-card__description" style="font-weight: 400; color: #333;" data-i18n="settings.login.modalDeleteLosingTitle"></p>
                    
                    <ul class="component-list" style="margin-top: 8px; margin-bottom: 16px; gap: 4px; color: #333;">
                        <li data-i18n="settings.login.modalDeleteBullet1"></li>
                        <li data-i18n="settings.login.modalDeleteBullet2"></li>
                        <li data-i18n="settings.login.modalDeleteBullet3"></li>
                    </ul>
                    
                    <p class="component-card__description" style="font-weight: 400; color: #333; margin-bottom: 8px;" data-i18n="settings.login.modalDeleteConfirmText"></p>
                    
                </div>
            </div>
            
            <div class="component-input-group">
                <input type="password" 
                       id="delete-account-password" 
                       name="current_password" 
                       class="component-input" 
                       required 
                       placeholder=" ">
                <label for="delete-account-password" data-i18n="settings.login.modalDeletePasswordLabel"></label>
            </div>

            <div class="component-card__actions">
                 <button type="button"
                   class="component-button"
                   data-action="toggleSectionSettingsLogin"
                   data-i18n="settings.profile.cancel">
                </button>
                 <button type="button" class="component-button danger" id="delete-account-confirm" data-i18n="settings.login.modalDeleteConfirm" disabled></button>
            </div>
        </div>

    </div>
</div>
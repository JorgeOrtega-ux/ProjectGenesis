<?php
// FILE: includes/sections/settings/delete-account.php

// 1. OBTENER DATOS PRECARGADOS (de config/router.php)
// Estas variables ($userEmail, $profileImageUrl)
// son cargadas por config/router.php
?>

<div class="section-content <?php echo ($CURRENT_SECTION === 'settings-delete-account') ? 'active' : 'disabled'; ?>" data-section="settings-delete-account">
    <div class="settings-wrapper">

        <div class="settings-header-card">
            <h1 class="settings-title" data-i18n="settings.login.modalDeleteTitle"></h1>
            
            <div class="delete-account-user-badge" style="margin-top: 16px;">
                <img src="<?php echo htmlspecialchars($profileImageUrl); ?>" 
                     alt="<?php echo htmlspecialchars($userEmail); ?>" 
                     class="delete-account-user-avatar">
                <span class="delete-account-user-email"><?php echo htmlspecialchars($userEmail); ?></span>
            </div>
        </div>

        <?php
        // Incluir el input CSRF
        outputCsrfInput();
        ?>

        <div class="settings-card settings-card--edit-mode settings-card--danger">
            <div class="settings-card__content">
                <div class="settings-card__text">
                    
                    <div class="modal-warning-box" style="background-color: #fbebee; margin-bottom: 16px;">
                        <span class="material-symbols-rounded" style="color: #c62828;">error</span>
                        <p data-i18n="settings.login.modalDeleteWarning"></p>
                    </div>

                    <p class="modal__description" style="font-size: 14px; font-weight: 400; color: #333;" data-i18n="settings.login.modalDeleteLosingTitle"></p>
                    <ul class="modal__list" style="margin-top: 8px; margin-bottom: 16px;">
                        <li data-i18n="settings.login.modalDeleteBullet1"></li>
                        <li data-i18n="settings.login.modalDeleteBullet2"></li>
                        <li data-i18n="settings.login.modalDeleteBullet3"></li>
                    </ul>
                    
                    <p class="modal__description" style="font-size: 14px; font-weight: 400; color: #333; margin-bottom: 8px;" data-i18n="settings.login.modalDeleteConfirmText"></p>
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
            </div>
            <div class="settings-card__actions">
                 <a href="<?php echo $basePath; ?>/settings/login-security"
                   class="settings-button"
                   data-nav-js
                   data-i18n="settings.profile.cancel">
                </a>
                 <button type="button" class="settings-button danger" id="delete-account-confirm" data-i18n="settings.login.modalDeleteConfirm" disabled></button>
            </div>
        </div>

    </div>
</div>
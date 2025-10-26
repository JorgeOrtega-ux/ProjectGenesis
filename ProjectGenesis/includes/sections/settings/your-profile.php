<?php
// --- ▼▼▼ INICIO DE NUEVO BLOQUE PHP ▼▼▼ ---

// Estas variables ($userLanguage, $userUsageType, $openLinksInNewTab) 
// son cargadas por config/router.php

// 1. Definir los mapas de valores de BD a *CLAVES DE TRADUCCIÓN*
$usageMap = [
    'personal' => 'settings.profile.usagePersonal',
    'student' => 'settings.profile.usageStudent',
    'teacher' => 'settings.profile.usageTeacher',
    'small_business' => 'settings.profile.usageSmallBusiness',
    'large_company' => 'settings.profile.usageLargeCompany'
];

// --- ¡NUEVO MAPA DE ICONOS AÑADIDO! ---
$usageIconMap = [
    'personal' => 'person',
    'student' => 'school',
    'teacher' => 'history_edu',
    'small_business' => 'storefront',
    'large_company' => 'business'
];

$languageMap = [
    'es-latam' => 'settings.profile.langEsLatam',
    'es-mx' => 'settings.profile.langEsMx',
    'en-us' => 'settings.profile.langEnUs',
    'fr-fr' => 'settings.profile.langFrFr'
];

// 2. Obtener la *CLAVE* actual para mostrar en el botón
$currentUsageKey = $usageMap[$userUsageType] ?? 'settings.profile.usagePersonal';
$currentLanguageKey = $languageMap[$userLanguage] ?? 'settings.profile.langEnUs';

// --- ▲▲▲ FIN DE NUEVO BLOQUE PHP ▲▲▲ ---
?>
<div class="section-content <?php echo ($CURRENT_SECTION === 'settings-profile') ? 'active' : 'disabled'; ?>" data-section="settings-profile">
    <div class="settings-wrapper">
        
        <div class="settings-header-card">
            <h1 class="settings-title" data-i18n="settings.profile.title"></h1>
            <p class="settings-description" data-i18n="settings.profile.description"></p>
        </div>

        <?php
        // ¡Este bloque de lógica se ha ido!
        ?>
        
        <form id="avatar-form" onsubmit="event.preventDefault();" novovite>
            
            <?php outputCsrfInput(); ?>
            
            <input type="file" id="avatar-upload-input" name="avatar" class="visually-hidden" accept="image/png, image/jpeg, image/gif, image/webp">
            <div class="settings-card-avatar-error" id="avatar-error" style="display: none;"></div>

            <div class="settings-card">
                <div class="settings-card-left">
                    <div class="settings-avatar" data-role="<?php echo htmlspecialchars($userRole); ?>" id="avatar-preview-container">
                        <img src="<?php echo htmlspecialchars($profileImageUrl); ?>" 
                             alt="<?php echo htmlspecialchars($usernameForAlt); ?>"
                             class="settings-avatar-image"
                             id="avatar-preview-image"
                             data-i18n-alt-prefix="header.profile.altPrefix">
                        
                        <div class="settings-avatar-overlay">
                            <span class="material-symbols-rounded">photo_camera</span>
                        </div>
                        </div>
                    <div class="settings-text-content">
                        <h2 class="settings-text-title" data-i18n="settings.profile.avatarTitle"></h2>
                        <p class="settings-text-description" data-i18n="settings.profile.avatarDesc"></p>
                    </div>
                </div>
                
                <div class="settings-card-right">
                    
                    <div class="settings-card-right-actions" id="avatar-actions-default" <?php echo $isDefaultAvatar ? '' : 'style="display: none;"'; ?>>
                        <button type="button" class="settings-button" id="avatar-upload-trigger" data-i18n="settings.profile.uploadPhoto"></button>
                    </div>

                    <div class="settings-card-right-actions" id="avatar-actions-custom" <?php echo !$isDefaultAvatar ? '' : 'style="display: none;"'; ?>>
                        <button type="button" class="settings-button danger" id="avatar-remove-trigger" data-i18n="settings.profile.removePhoto"></button>
                        <button type="button" class="settings-button" id="avatar-change-trigger" data-i18n="settings.profile.changePhoto"></button>
                    </div>

                    <div class="settings-card-right-actions" id="avatar-actions-preview" style="display: none;">
                        <button type="button" class="settings-button" id="avatar-cancel-trigger" data-i18n="settings.profile.cancel"></button>
                        <button type="submit" class="settings-button" id="avatar-save-trigger" data-i18n="settings.profile.save"></button>
                    </div>

                </div>
            </div>
        </form>

        <form id="username-form" onsubmit="event.preventDefault();" novalidate>
            <?php outputCsrfInput(); ?>
            <input type="hidden" name="action" value="update-username">
            
            <div class="settings-card">
                
                <div class="settings-card-left" id="username-view-state" style="display: flex;">
                    <div class="settings-text-content">
                        <h2 class="settings-text-title" data-i18n="settings.profile.username"></h2>
                        <p class="settings-text-description" 
                           id="username-display-text" 
                           data-original-username="<?php echo htmlspecialchars($usernameForAlt); ?>">
                           <?php echo htmlspecialchars($usernameForAlt); ?>
                        </p>
                    </div>
                </div>
                <div class="settings-card-right" id="username-actions-view" style="display: flex;">
                    <button type="button" class="settings-button" id="username-edit-trigger" data-i18n="settings.profile.edit"></button>
                </div>

                <div class="settings-card-left" id="username-edit-state" style="display: none;">
                    <div class="settings-text-content" style="width: 100%;">
                        <h2 class="settings-text-title" data-i18n="settings.profile.username"></h2>
                        <input type="text" 
                               class="settings-username-input" 
                               id="username-input" 
                               name="username" 
                               value="<?php echo htmlspecialchars($usernameForAlt); ?>"
                               required
                               minlength="6"
                               maxlength="32">
                        </div>
                </div>
                <div class="settings-card-right-actions" id="username-actions-edit" style="display: none;">
                    <button type="button" class="settings-button" id="username-cancel-trigger" data-i18n="settings.profile.cancel"></button>
                    <button type="submit" class="settings-button" id="username-save-trigger" data-i18n="settings.profile.save"></button>
                </div>

            </div>
        </form>

        <form id="email-form" onsubmit="event.preventDefault();" novalidate>
            <?php outputCsrfInput(); ?>
            <input type="hidden" name="action" value="update-email">
            
            <div class="settings-card">
                
                <div class="settings-card-left" id="email-view-state" style="display: flex;">
                    <div class="settings-text-content">
                        <h2 class="settings-text-title" data-i18n="settings.profile.email"></h2>
                        <p class="settings-text-description" 
                           id="email-display-text" 
                           data-original-email="<?php echo htmlspecialchars($userEmail); ?>">
                           <?php echo htmlspecialchars($userEmail); ?>
                        </p>
                    </div>
                </div>
                <div class="settings-card-right" id="email-actions-view" style="display: flex;">
                    <button type="button" class="settings-button" id="email-edit-trigger" data-i18n="settings.profile.edit"></button>
                </div>

                <div class="settings-card-left" id="email-edit-state" style="display: none;">
                    <div class="settings-text-content" style="width: 100%;">
                        <h2 class="settings-text-title" data-i18n="settings.profile.email"></h2>
                        <input type="email" 
                               class="settings-username-input" 
                               id="email-input" 
                               name="email" 
                               value="<?php echo htmlspecialchars($userEmail); ?>"
                               required
                               maxlength="255">
                        </div>
                </div>
                <div class="settings-card-right-actions" id="email-actions-edit" style="display: none;">
                    <button type="button" class="settings-button" id="email-cancel-trigger" data-i18n="settings.profile.cancel"></button>
                    <button type="submit" class="settings-button" id="email-save-trigger" data-i18n="settings.profile.save"></button>
                </div>

            </div>
        </form>
        
        <div class="settings-modal-overlay" id="email-verify-modal" style="display: none;">
            
            <button type="button" class="settings-modal-close-btn" id="email-verify-close">
                <span class="material-symbols-rounded">close</span>
            </button>

            <div class="settings-modal-content">
                <h2 class="auth-title" style="margin-bottom: 16px;" data-i18n="settings.profile.modalCodeTitle"></h2>
                
                <p class="auth-verification-text" style="margin-bottom: 24px;" data-i18n="settings.profile.modalCodeDesc">
                    <strong id="email-verify-modal-email"><?php echo htmlspecialchars($userEmail); ?></strong>.
                </p>

                <div class="auth-error-message" id="email-verify-error" style="display: none; margin-bottom: 16px;"></div>

                <form onsubmit="event.preventDefault();" novalidate>
                    
                    <div class="auth-input-group">
                        <input type="text" id="email-verify-code" name="verification_code" required placeholder=" " maxlength="14">
                        <label for="email-verify-code" data-i18n="settings.profile.modalCodeLabel"></label>
                    </div>

                    <div class="auth-step-buttons">
                        <button type="button" class="auth-button" id="email-verify-continue" data-i18n="settings.profile.continue"></button>
                    </div>
                    </form>

                <div class="settings-modal-footer">
                    <p>
                        <span data-i18n="settings.profile.modalCodeResendP"></span>
                        <a id="email-verify-resend" data-i18n="settings.profile.modalCodeResendA"></a>
                    </p>
                </div>

            </div>
        </div>

        <div class="settings-card settings-card-trigger-column">
            <div class="settings-card-left">
                <div class="settings-text-content">
                    <h2 class="settings-text-title" data-i18n="settings.profile.usageTitle"></h2>
                    <p class="settings-text-description" data-i18n="settings.profile.usageDesc"></p>
                </div>
            </div>

            <div class="settings-card-right">
                
                <div class="trigger-select-wrapper">
                    
                    <div class="trigger-selector" 
                         data-action="toggleModuleUsageSelect">
                        
                        <div class="trigger-select-icon">
                            <span class="material-symbols-rounded">person</span>
                        </div>
                        <div class="trigger-select-text">
                            <span data-i18n="<?php echo htmlspecialchars($currentUsageKey); ?>"></span>
                        </div>
                        <div class="trigger-select-arrow">
                            <span class="material-symbols-rounded">arrow_drop_down</span>
                        </div>
                    </div>

                    <div class="module-content module-trigger-select body-title disabled" 
                         data-module="moduleUsageSelect"
                         data-preference-type="usage">
                        
                        <div class="menu-content">
                            <div class="menu-list">

                                <?php 
                                // --- ▼▼▼ INICIO DE MODIFICACIÓN DEL BUCLE ▼▼▼ ---
                                foreach ($usageMap as $key => $textKey): 
                                    $isActive = ($key === $userUsageType); 
                                    $iconName = $usageIconMap[$key] ?? 'person'; // Icono por defecto
                                ?>
                                    <div class="menu-link <?php echo $isActive ? 'active' : ''; ?>" 
                                         data-value="<?php echo htmlspecialchars($key); ?>">
                                        
                                        <div class="menu-link-icon">
                                            <span class="material-symbols-rounded"><?php echo $iconName; ?></span>
                                        </div>
                                        <div class="menu-link-text">
                                            <span data-i18n="<?php echo htmlspecialchars($textKey); ?>"></span>
                                        </div>
                                        <div class="menu-link-check-icon">
                                            <?php if ($isActive): ?>
                                                <span class="material-symbols-rounded">check</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; 
                                // --- ▲▲▲ FIN DE MODIFICACIÓN DEL BUCLE ▲▲▲ ---
                                ?>
                                
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <div class="settings-card settings-card-trigger-column">
            <div class="settings-card-left">
                <div class="settings-text-content">
                    <h2 class="settings-text-title" data-i18n="settings.profile.langTitle"></h2>
                    <p class="settings-text-description" data-i18n="settings.profile.langDesc"></p>
                </div>
            </div>

            <div class="settings-card-right">
                
                <div class="trigger-select-wrapper">
                    
                    <div class="trigger-selector" 
                         data-action="toggleModuleLanguageSelect">
                        
                        <div class="trigger-select-icon">
                            <span class="material-symbols-rounded">language</span>
                        </div>
                        <div class="trigger-select-text">
                            <span data-i18n="<?php echo htmlspecialchars($currentLanguageKey); ?>"></span>
                        </div>
                        <div class="trigger-select-arrow">
                            <span class="material-symbols-rounded">arrow_drop_down</span>
                        </div>
                    </div>

                    <div class="module-content module-trigger-select body-title disabled" 
                         data-module="moduleLanguageSelect"
                         data-preference-type="language">
                        
                        <div class="menu-content">
                            <div class="menu-list">

                                <?php 
                                // --- ▼▼▼ INICIO DE MODIFICACIÓN DEL BUCLE ▼▼▼ ---
                                foreach ($languageMap as $key => $textKey): 
                                    $isActive = ($key === $userLanguage); 
                                ?>
                                    <div class="menu-link <?php echo $isActive ? 'active' : ''; ?>" 
                                         data-value="<?php echo htmlspecialchars($key); ?>">
                                        
                                        <div class="menu-link-icon">
                                            <span class="material-symbols-rounded">language</span>
                                        </div>
                                        <div class="menu-link-text">
                                            <span data-i18n="<?php echo htmlspecialchars($textKey); ?>"></span>
                                        </div>
                                        <div class="menu-link-check-icon">
                                            <?php if ($isActive): ?>
                                                <span class="material-symbols-rounded">check</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; 
                                // --- ▲▲▲ FIN DE MODIFICACIÓN DEL BUCLE ▲▲▲ ---
                                ?>
                                
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        
        <div class="settings-card settings-card-align-bottom">
            <div class="settings-card-left">
                <div class="settings-text-content">
                    <h2 class="settings-text-title" data-i18n="settings.profile.newTabTitle"></h2>
                    <p class="settings-text-description" data-i18n="settings.profile.newTabDesc"></p>
                </div>
            </div>

            <div class="settings-card-right">
                
                <label class="settings-toggle-switch">
                    <input type="checkbox" 
                           id="toggle-new-tab"
                           data-preference-type="boolean"
                           data-field-name="open_links_in_new_tab"
                           <?php echo ($openLinksInNewTab == 1) ? 'checked' : ''; ?>> 
                    <span class="settings-toggle-slider"></span>
                </label>

            </div>
        </div>
        </div>
</div>
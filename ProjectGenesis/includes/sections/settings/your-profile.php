<?php
// FILE: jorgeortega-ux/projectgenesis/ProjectGenesis-970f96c7c06ba42e4101b6d17ae77c332c6f3044/ProjectGenesis/includes/sections/settings/your-profile.php
?>
<?php
// --- ▼▼▼ INICIO DE NUEVO BLOQUE PHP ▼▼▼ ---

// Estas variables ($userLanguage, $userUsageType, $openLinksInNewTab, $initialEmailCooldown)
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

        <div class="settings-card settings-card--edit-mode" id="avatar-section">

            <?php outputCsrfInput(); ?> <input type="file" class="visually-hidden" id="avatar-upload-input" name="avatar" accept="image/png, image/jpeg, image/gif, image/webp">

            <div class="settings-card__content">
                <div class="settings-card__avatar" id="avatar-preview-container" data-role="<?php echo htmlspecialchars($userRole); ?>">
                    <img src="<?php echo htmlspecialchars($profileImageUrl); ?>"
                         alt="<?php echo htmlspecialchars($usernameForAlt); ?>"
                         class="settings-card__avatar-image"
                         id="avatar-preview-image"
                         data-i18n-alt-prefix="header.profile.altPrefix">

                    <div class="settings-card__avatar-overlay">
                        <span class="material-symbols-rounded">photo_camera</span>
                    </div>
                </div>
                <div class="settings-card__text">
                    <h2 class="settings-card__title" data-i18n="settings.profile.avatarTitle"></h2>
                    <p class="settings-card__description" data-i18n="settings.profile.avatarDesc"></p>
                </div>
            </div>

            <div class="settings-card__actions">
                <div id="avatar-actions-default" <?php echo $isDefaultAvatar ? 'style="display: flex; gap: 12px;"' : 'style="display: none;"'; ?>>
                    <button type="button" class="settings-button" id="avatar-upload-trigger" data-i18n="settings.profile.uploadPhoto"></button>
                </div>

                <div id="avatar-actions-custom" <?php echo !$isDefaultAvatar ? 'style="display: flex; gap: 12px;"' : 'style="display: none;"'; ?>>
                    <button type="button" class="settings-button danger" id="avatar-remove-trigger" data-i18n="settings.profile.removePhoto"></button>
                    <button type="button" class="settings-button" id="avatar-change-trigger" data-i18n="settings.profile.changePhoto"></button>
                </div>

                <div id="avatar-actions-preview" style="display: none; gap: 12px;">
                    <button type="button" class="settings-button" id="avatar-cancel-trigger" data-i18n="settings.profile.cancel"></button>
                    <button type="button" class="settings-button" id="avatar-save-trigger-btn" data-i18n="settings.profile.save"></button>
                </div>
            </div>
        </div>
        <div class="settings-card settings-card--edit-mode" id="username-section">
            <?php outputCsrfInput(); ?> <input type="hidden" name="action" value="update-username"> <div class="settings-card__content" id="username-view-state" style="display: flex;">
                <div class="settings-card__text">
                    <h2 class="settings-card__title" data-i18n="settings.profile.username"></h2>
                    <p class="settings-card__description"
                       id="username-display-text"
                       data-original-username="<?php echo htmlspecialchars($usernameForAlt); ?>">
                       <?php echo htmlspecialchars($usernameForAlt); ?>
                    </p>
                </div>
            </div>
            <div class="settings-card__actions" id="username-actions-view" style="display: flex;">
                <button type="button" class="settings-button" id="username-edit-trigger" data-i18n="settings.profile.edit"></button>
            </div>

            <div class="settings-card__content" id="username-edit-state" style="display: none;">
                <div class="settings-card__text">
                    <h2 class="settings-card__title" data-i18n="settings.profile.username"></h2>
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
            <div class="settings-card__actions" id="username-actions-edit" style="display: none;">
                <button type="button" class="settings-button" id="username-cancel-trigger" data-i18n="settings.profile.cancel"></button>
                <button type="button" class="settings-button" id="username-save-trigger-btn" data-i18n="settings.profile.save"></button>
            </div>
        </div>
        <div class="settings-card settings-card--edit-mode" id="email-section">
            <?php outputCsrfInput(); ?> <input type="hidden" name="action" value="update-email"> <div class="settings-card__content" id="email-view-state" style="display: flex;">
                <div class="settings-card__text">
                    <h2 class="settings-card__title" data-i18n="settings.profile.email"></h2>
                    <p class="settings-card__description"
                       id="email-display-text"
                       data-original-email="<?php echo htmlspecialchars($userEmail); ?>">
                       <?php echo htmlspecialchars($userEmail); ?>
                    </p>
                </div>
            </div>
            <div class="settings-card__actions" id="email-actions-view" style="display: flex;">
                <button type="button" class="settings-button" id="email-edit-trigger" data-i18n="settings.profile.edit"></button>
            </div>

            <div class="settings-card__content" id="email-edit-state" style="display: none;">
                <div class="settings-card__text">
                    <h2 class="settings-card__title" data-i18n="settings.profile.email"></h2>
                    <input type="email"
                           class="settings-username-input"
                           id="email-input"
                           name="email"
                           value="<?php echo htmlspecialchars($userEmail); ?>"
                           required
                           maxlength="255">
                </div>
            </div>
            <div class="settings-card__actions" id="email-actions-edit" style="display: none;">
                <button type="button" class="settings-button" id="email-cancel-trigger" data-i18n="settings.profile.cancel"></button>
                <button type="button" class="settings-button" id="email-save-trigger-btn" data-i18n="settings.profile.save"></button>
            </div>
        </div>

        <div class="modal-overlay" id="email-verify-modal">
            <div class="modal-content">
                <div class="modal__header">
                    
                    <h2 class="modal__title" data-i18n="settings.profile.modalCodeTitle"></h2>
                    <button type="button" class="modal-close-btn" id="email-verify-close">
                        <span class="material-symbols-rounded">close</span>
                    </button>
                </div>
                <div class="modal__body">
                     
                     <p class="modal__description">
                        <span data-i18n="settings.profile.modalCodeDesc"></span> 
                        <strong><?php echo htmlspecialchars($userEmail); ?></strong>
                     </p>
                     <div class="auth-error-message" id="email-verify-error" style="display: none;"></div>
                    <div class="modal__input-group">
                        <input type="text" id="email-verify-code" name="verification_code" class="modal__input" required placeholder=" " maxlength="14">
                        <label for="email-verify-code" data-i18n="settings.profile.modalCodeLabel"></label>
                    </div>
                </div>
                <div class="modal__footer modal__footer--column">
                    <button type="button" class="modal__button modal__button--primary" id="email-verify-continue" data-i18n="settings.profile.continue"></button>
                    <p class="modal__footer-text" style="text-align: center;">
                        <span data-i18n="settings.profile.modalCodeResendP"></span>
                        
                        <a id="email-verify-resend" 
                           data-i18n="settings.profile.modalCodeResendA"
                           data-cooldown="<?php echo isset($initialEmailCooldown) ? $initialEmailCooldown : 0; ?>"
                           class="<?php echo (isset($initialEmailCooldown) && $initialEmailCooldown > 0) ? 'disabled-interactive' : ''; ?>"
                           style="<?php echo (isset($initialEmailCooldown) && $initialEmailCooldown > 0) ? 'opacity: 0.7; text-decoration: none;' : ''; ?>"
                        >
                        <?php 
                           if (isset($initialEmailCooldown) && $initialEmailCooldown > 0) {
                               // El JS pondrá el texto base, aquí solo añadimos el contador
                               echo " (" . $initialEmailCooldown . "s)";
                           }
                        ?>
                        </a>
                        </p>
                </div>
            </div>
        </div>
        <div class="settings-card settings-card--column">
            <div class="settings-card__content">
                <div class="settings-card__text">
                    <h2 class="settings-card__title" data-i18n="settings.profile.usageTitle"></h2>
                    <p class="settings-card__description" data-i18n="settings.profile.usageDesc"></p>
                </div>
            </div>
            <div class="settings-card__actions">
                <div class="trigger-select-wrapper">
                    <div class="trigger-selector" data-action="toggleModuleUsageSelect">
                        <div class="trigger-select-icon">
                            <span class="material-symbols-rounded"><?php echo $usageIconMap[$userUsageType] ?? 'person'; ?></span>
                        </div>
                        <div class="trigger-select-text">
                            <span data-i18n="<?php echo htmlspecialchars($currentUsageKey); ?>"></span>
                        </div>
                        <div class="trigger-select-arrow">
                            <span class="material-symbols-rounded">arrow_drop_down</span>
                        </div>
                    </div>

                    <div class="popover-module popover-module--anchor-width body-title disabled"
                         data-module="moduleUsageSelect"
                         data-preference-type="usage">
                        <div class="menu-content">
                            <div class="menu-list">
                                <?php
                                foreach ($usageMap as $key => $textKey):
                                    $isActive = ($key === $userUsageType);
                                    $iconName = $usageIconMap[$key] ?? 'person';
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
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="settings-card settings-card--column">
            <div class="settings-card__content">
                <div class="settings-card__text">
                    <h2 class="settings-card__title" data-i18n="settings.profile.langTitle"></h2>
                    <p class="settings-card__description" data-i18n="settings.profile.langDesc"></p>
                </div>
            </div>
            <div class="settings-card__actions">
                <div class="trigger-select-wrapper">
                    <div class="trigger-selector" data-action="toggleModuleLanguageSelect">
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

                    <div class="popover-module popover-module--anchor-width body-title disabled"
                         data-module="moduleLanguageSelect"
                         data-preference-type="language">
                        <div class="menu-content">
                            <div class="menu-list">
                                <?php
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
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="settings-card settings-card--edit-mode">
            <div class="settings-card__content">
                <div class="settings-card__text">
                    <h2 class="settings-card__title" data-i18n="settings.profile.newTabTitle"></h2>
                    <p class="settings-card__description" data-i18n="settings.profile.newTabDesc"></p>
                </div>
            </div>
            <div class="settings-card__actions">
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
<?php
// FILE: includes/sections/admin/server-settings.php

// (Se asume que config/router.php ya ha iniciado $pdo y la sesión)
// Las variables $maintenanceModeStatus y $allowRegistrationStatus son cargadas por config/router.php

// --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
// Cargar los nuevos valores desde $GLOBALS (definidos en bootstrapper.php)
$usernameCooldown = $GLOBALS['site_settings']['username_cooldown_days'] ?? '30';
$emailCooldown = $GLOBALS['site_settings']['email_cooldown_days'] ?? '12';
$avatarMaxSize = $GLOBALS['site_settings']['avatar_max_size_mb'] ?? '2';

// --- ▼▼▼ NUEVAS VARIABLES AÑADIDAS ▼▼▼ ---
$maxLoginAttempts = $GLOBALS['site_settings']['max_login_attempts'] ?? '5';
$lockoutTimeMinutes = $GLOBALS['site_settings']['lockout_time_minutes'] ?? '5';
$allowedEmailDomains = $GLOBALS['site_settings']['allowed_email_domains'] ?? 'gmail.com\noutlook.com';
$minPasswordLength = $GLOBALS['site_settings']['min_password_length'] ?? '8';
// --- ¡NUEVA LÍNEA! ---
$maxPasswordLength = $GLOBALS['site_settings']['max_password_length'] ?? '72';
// --- ▲▲▲ FIN DE NUEVAS VARIABLES ▲▲▲ ---
// --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
?>
<div class="section-content overflow-y <?php echo ($CURRENT_SECTION === 'admin-server-settings') ? 'active' : 'disabled'; ?>" data-section="admin-server-settings">
    <div class="component-wrapper">

        <?php
        // Incluir el input CSRF
        outputCsrfInput();
        ?>

        <div class="component-header-card">
            <h1 class="component-page-title" data-i18n="admin.server.title"></h1>
            <p class="component-page-description" data-i18n="admin.server.description"></p>
        </div>

        <div class="component-card component-card--edit-mode">
            <div class="component-card__content">
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="admin.server.maintenanceTitle"></h2>
                    <p class="component-card__description" data-i18n="admin.server.maintenanceDesc"></p>
                </div>
            </div>
            <div class="component-card__actions">
                <label class="component-toggle-switch">
                    <input type="checkbox" 
                           id="toggle-maintenance-mode"
                           data-action="update-maintenance-mode"
                           <?php echo ($maintenanceModeStatus == 1) ? 'checked' : ''; ?>
                           <?php echo ($_SESSION['role'] !== 'founder') ? 'disabled' : ''; ?>
                           > 
                    <span class="component-toggle-slider"></span>
                </label>
            </div>
        </div>
        
        <div class="component-card component-card--edit-mode">
            <div class="component-card__content">
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="admin.server.registrationTitle"></h2>
                    <p class="component-card__description" data-i18n="admin.server.registrationDesc"></p>
                </div>
            </div>
            <div class="component-card__actions">
                <label class="component-toggle-switch">
                    <input type="checkbox" 
                           id="toggle-allow-registration"
                           data-action="update-registration-mode"
                           <?php echo ($allowRegistrationStatus == 1) ? 'checked' : ''; ?>
                           <?php 
                           // Deshabilitado si no es fundador O si el modo mantenimiento está activo
                           echo ($_SESSION['role'] !== 'founder' || $maintenanceModeStatus == 1) ? 'disabled' : ''; 
                           ?>
                           > 
                    <span class="component-toggle-slider"></span>
                </label>
            </div>
        </div>

        <div class="component-card component-card--column">
            <div class="component-card__content">
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="admin.server.minPasswordLengthTitle"></h2>
                    <p class="component-card__description" data-i18n="admin.server.minPasswordLengthDesc"></p>
                </div>
            </div>
            <div class="component-card__actions">
                 <div class="component-stepper" 
                      style="max-width: 265px;"
                      data-action="update-min-password-length"
                      data-current-value="<?php echo htmlspecialchars($minPasswordLength); ?>"
                      data-min="8"
                      data-max="72"
                      data-step="1"
                      <?php echo ($_SESSION['role'] !== 'founder') ? 'disabled' : ''; ?>
                      >
                    <button type="button" class="stepper-button" data-step-action="decrement" <?php echo ($_SESSION['role'] !== 'founder') ? 'disabled' : ''; ?> <?php echo ($minPasswordLength <= 8) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">chevron_left</span>
                    </button>
                    <div class="stepper-value" id="stepper-value-min-password-length">
                         <?php echo htmlspecialchars($minPasswordLength); ?>
                    </div>
                    <button type="button" class="stepper-button" data-step-action="increment" <?php echo ($_SESSION['role'] !== 'founder') ? 'disabled' : ''; ?> <?php echo ($minPasswordLength >= 72) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">chevron_right</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="component-card component-card--column">
            <div class="component-card__content">
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="admin.server.maxPasswordLengthTitle"></h2>
                    <p class="component-card__description" data-i18n="admin.server.maxPasswordLengthDesc"></p>
                </div>
            </div>
            <div class="component-card__actions">
                 <div class="component-stepper" 
                      style="max-width: 265px;"
                      data-action="update-max-password-length"
                      data-current-value="<?php echo htmlspecialchars($maxPasswordLength); ?>"
                      data-min="8"
                      data-max="72"
                      data-step="1"
                      <?php echo ($_SESSION['role'] !== 'founder') ? 'disabled' : ''; ?>
                      >
                    <button type="button" class="stepper-button" data-step-action="decrement" <?php echo ($_SESSION['role'] !== 'founder') ? 'disabled' : ''; ?> <?php echo ($maxPasswordLength <= 8) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">chevron_left</span>
                    </button>
                    <div class="stepper-value" id="stepper-value-max-password-length">
                         <?php echo htmlspecialchars($maxPasswordLength); ?>
                    </div>
                    <button type="button" class="stepper-button" data-step-action="increment" <?php echo ($_SESSION['role'] !== 'founder') ? 'disabled' : ''; ?> <?php echo ($maxPasswordLength >= 72) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">chevron_right</span>
                    </button>
                </div>
            </div>
        </div>
        <div class="component-card component-card--column">
            <div class="component-card__content">
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="admin.server.maxLoginAttemptsTitle"></h2>
                    <p class="component-card__description" data-i18n="admin.server.maxLoginAttemptsDesc"></p>
                </div>
            </div>
            <div class="component-card__actions">
                 <div class="component-stepper" 
                      style="max-width: 265px;"
                      data-action="update-max-login-attempts"
                      data-current-value="<?php echo htmlspecialchars($maxLoginAttempts); ?>"
                      data-min="3"
                      data-max="20"
                      data-step="1"
                      <?php echo ($_SESSION['role'] !== 'founder') ? 'disabled' : ''; ?>
                      >
                    <button type="button" class="stepper-button" data-step-action="decrement" <?php echo ($_SESSION['role'] !== 'founder') ? 'disabled' : ''; ?> <?php echo ($maxLoginAttempts <= 3) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">chevron_left</span>
                    </button>
                    <div class="stepper-value" id="stepper-value-max-login-attempts">
                         <?php echo htmlspecialchars($maxLoginAttempts); ?>
                    </div>
                    <button type="button" class="stepper-button" data-step-action="increment" <?php echo ($_SESSION['role'] !== 'founder') ? 'disabled' : ''; ?> <?php echo ($maxLoginAttempts >= 20) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">chevron_right</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="component-card component-card--column">
            <div class="component-card__content">
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="admin.server.lockoutTimeMinutesTitle"></h2>
                    <p class="component-card__description" data-i18n="admin.server.lockoutTimeMinutesDesc"></p>
                </div>
            </div>
            <div class="component-card__actions">
                 <div class="component-stepper" 
                      style="max-width: 265px;"
                      data-action="update-lockout-time-minutes"
                      data-current-value="<?php echo htmlspecialchars($lockoutTimeMinutes); ?>"
                      data-min="1"
                      data-max="60"
                      data-step="1"
                      <?php echo ($_SESSION['role'] !== 'founder') ? 'disabled' : ''; ?>
                      >
                    <button type="button" class="stepper-button" data-step-action="decrement" <?php echo ($_SESSION['role'] !== 'founder') ? 'disabled' : ''; ?> <?php echo ($lockoutTimeMinutes <= 1) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">chevron_left</span>
                    </button>
                    <div class="stepper-value" id="stepper-value-lockout-time-minutes">
                         <?php echo htmlspecialchars($lockoutTimeMinutes); ?>
                    </div>
                    <button type="button" class="stepper-button" data-step-action="increment" <?php echo ($_SESSION['role'] !== 'founder') ? 'disabled' : ''; ?> <?php echo ($lockoutTimeMinutes >= 60) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">chevron_right</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="component-card component-card--column" id="admin-domain-card">
            <div class="component-card__content">
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="admin.server.allowedEmailDomainsTitle"></h2>
                    <p class="component-card__description" data-i18n="admin.server.allowedEmailDomainsDesc"></p>
                </div>
            </div>
            
            <div id="domain-view-state" <?php echo ($_SESSION['role'] !== 'founder') ? 'style="pointer-events: none; opacity: 0.7;"' : ''; ?>>
                <div class="domain-card-list">
                    <?php
                    $domains = preg_split('/[\s,]+/', $allowedEmailDomains, -1, PREG_SPLIT_NO_EMPTY);
                    if (empty($domains)):
                    ?>
                        <p class="component-card__description" style="margin: 8px 0;">No hay dominios configurados.</p>
                    <?php
                    else:
                        foreach ($domains as $domain):
                    ?>
                        <div class="domain-card-item" data-domain="<?php echo htmlspecialchars($domain); ?>">
                            <span class="material-symbols-rounded">language</span>
                            <span class="domain-card-text"><?php echo htmlspecialchars($domain); ?></span>
                            <button type="button" class="domain-card-delete" data-action="admin-domain-delete" data-domain="<?php echo htmlspecialchars($domain); ?>" data-tooltip="admin.server.deleteDomainTooltip">
                                <span class="material-symbols-rounded">delete</span>
                            </button>
                        </div>
                    <?php
                        endforeach;
                    endif;
                    ?>
                </div>
                
                <div class="component-card__actions" style="justify-content: flex-end; margin-top: 16px;">
                    <button type="button" class="component-action-button component-action-button--primary" data-action="admin-domain-show-add" data-i18n="admin.server.addDomain" <?php echo ($_SESSION['role'] !== 'founder') ? 'disabled' : ''; ?>>
                        Agregar dominio
                    </button>
                </div>
            </div>

            <div id="domain-add-state" style="display: none;">
                <div class="component-input-group" style="margin-top: 8px;">
                    <input type="text"
                           class="component-input"
                           id="setting-new-domain-input"
                           placeholder=" "
                           maxlength="100">
                    <label for="setting-new-domain-input" data-i18n="admin.server.addDomainPlaceholder">Escribe el dominio (ej. miempresa.com)</label>
                </div>
                <div class="component-card__actions" style="justify-content: flex-end; margin-top: 16px;">
                    <button type="button" class="component-action-button component-action-button--secondary" data-action="admin-domain-cancel-add" data-i18n="admin.server.cancel">
                        Cancelar
                    </button>
                    <button type="button" class="component-action-button component-action-button--primary" data-action="admin-domain-save-add" data-i18n="admin.server.saveChanges">
                        Guardar cambios
                    </button>
                </div>
            </div>
        </div>
        <div class="component-card component-card--column">
            <div class="component-card__content">
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="admin.server.usernameCooldownTitle"></h2>
                    <p class="component-card__description" data-i18n="admin.server.usernameCooldownDesc"></p>
                </div>
            </div>
            <div class="component-card__actions">
                <div class="component-stepper" 
                      style="max-width: 265px;"
                      data-action="update-username-cooldown"
                      data-current-value="<?php echo htmlspecialchars($usernameCooldown); ?>"
                      data-min="1"
                      data-max="365"
                      data-step="1"
                      <?php echo ($_SESSION['role'] !== 'founder') ? 'disabled' : ''; ?>
                      >
                    <button type="button" class="stepper-button" data-step-action="decrement" <?php echo ($_SESSION['role'] !== 'founder') ? 'disabled' : ''; ?> <?php echo ($usernameCooldown <= 1) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">chevron_left</span>
                    </button>
                    <div class="stepper-value" id="stepper-value-username-cooldown">
                         <?php echo htmlspecialchars($usernameCooldown); ?>
                    </div>
                    <button type="button" class="stepper-button" data-step-action="increment" <?php echo ($_SESSION['role'] !== 'founder') ? 'disabled' : ''; ?> <?php echo ($usernameCooldown >= 365) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">chevron_right</span>
                    </button>
                </div>
            </div>
        </div>
        
        <div class="component-card component-card--column">
            <div class="component-card__content">
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="admin.server.emailCooldownTitle"></h2>
                    <p class="component-card__description" data-i18n="admin.server.emailCooldownDesc"></p>
                </div>
            </div>
            <div class="component-card__actions">
                 <div class="component-stepper" 
                      style="max-width: 265px;"
                      data-action="update-email-cooldown"
                      data-current-value="<?php echo htmlspecialchars($emailCooldown); ?>"
                      data-min="1"
                      data-max="365"
                      data-step="1"
                      <?php echo ($_SESSION['role'] !== 'founder') ? 'disabled' : ''; ?>
                      >
                    <button type="button" class="stepper-button" data-step-action="decrement" <?php echo ($_SESSION['role'] !== 'founder') ? 'disabled' : ''; ?> <?php echo ($emailCooldown <= 1) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">chevron_left</span>
                    </button>
                    <div class="stepper-value" id="stepper-value-email-cooldown">
                         <?php echo htmlspecialchars($emailCooldown); ?>
                    </div>
                    <button type="button" class="stepper-button" data-step-action="increment" <?php echo ($_SESSION['role'] !== 'founder') ? 'disabled' : ''; ?> <?php echo ($emailCooldown >= 365) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">chevron_right</span>
                    </button>
                </div>
            </div>
        </div>
        
        <div class="component-card component-card--column">
            <div class="component-card__content">
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="admin.server.avatarMaxSizeTitle"></h2>
                    <p class="component-card__description" data-i18n="admin.server.avatarMaxSizeDesc"></p>
                </div>
            </div>
            <div class="component-card__actions">
                 <div class="component-stepper" 
                      style="max-width: 265px;"
                      data-action="update-avatar-max-size"
                      data-current-value="<?php echo htmlspecialchars($avatarMaxSize); ?>"
                      data-min="1"
                      data-max="20"
                      data-step="1"
                      <?php echo ($_SESSION['role'] !== 'founder') ? 'disabled' : ''; ?>
                      >
                    <button type="button" class="stepper-button" data-step-action="decrement" <?php echo ($_SESSION['role'] !== 'founder') ? 'disabled' : ''; ?> <?php echo ($avatarMaxSize <= 1) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">chevron_left</span>
                    </button>
                    <div class="stepper-value" id="stepper-value-avatar-max-size">
                         <?php echo htmlspecialchars($avatarMaxSize); ?>
                    </div>
                    <button type="button" class="stepper-button" data-step-action="increment" <?php echo ($_SESSION['role'] !== 'founder') ? 'disabled' : ''; ?> <?php echo ($avatarMaxSize >= 20) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">chevron_right</span>
                    </button>
                </div>
            </div>
        </div>
        
        </div>
</div>
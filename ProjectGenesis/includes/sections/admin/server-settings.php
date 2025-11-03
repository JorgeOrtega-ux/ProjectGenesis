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
                 <div class="component-input-group" style="max-width: 265px;">
                    <input type="number"
                           class="component-input"
                           id="setting-min-password-length"
                           data-action="update-min-password-length"
                           value="<?php echo htmlspecialchars($minPasswordLength); ?>"
                           min="8"
                           max="72"
                           step="1"
                           placeholder=" "
                           <?php echo ($_SESSION['role'] !== 'founder') ? 'disabled' : ''; ?>
                           >
                    <label for="setting-min-password-length" data-i18n="admin.server.minPasswordLengthTitle"></label>
                </div>
            </div>
        </div>

        <div class="component-card component-card--column">
            <div class="component-card__content">
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="admin.server.maxPasswordLengthTitle">Longitud Máxima de Contraseña</h2>
                    <p class="component-card__description" data-i18n="admin.server.maxPasswordLengthDesc">El número máximo de caracteres permitidos para cualquier contraseña nueva (máx. 72).</p>
                </div>
            </div>
            <div class="component-card__actions">
                 <div class="component-input-group" style="max-width: 265px;">
                    <input type="number"
                           class="component-input"
                           id="setting-max-password-length"
                           data-action="update-max-password-length"
                           value="<?php echo htmlspecialchars($maxPasswordLength); ?>"
                           min="8"
                           max="72"
                           step="1"
                           placeholder=" "
                           <?php echo ($_SESSION['role'] !== 'founder') ? 'disabled' : ''; ?>
                           >
                    <label for="setting-max-password-length" data-i18n="admin.server.maxPasswordLengthTitle">Longitud Máxima de Contraseña</label>
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
                 <div class="component-input-group" style="max-width: 265px;">
                    <input type="number"
                           class="component-input"
                           id="setting-max-login-attempts"
                           data-action="update-max-login-attempts"
                           value="<?php echo htmlspecialchars($maxLoginAttempts); ?>"
                           min="3"
                           max="20"
                           step="1"
                           placeholder=" "
                           <?php echo ($_SESSION['role'] !== 'founder') ? 'disabled' : ''; ?>
                           >
                    <label for="setting-max-login-attempts" data-i18n="admin.server.maxLoginAttemptsTitle"></label>
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
                 <div class="component-input-group" style="max-width: 265px;">
                    <input type="number"
                           class="component-input"
                           id="setting-lockout-time-minutes"
                           data-action="update-lockout-time-minutes"
                           value="<?php echo htmlspecialchars($lockoutTimeMinutes); ?>"
                           min="1"
                           max="60"
                           step="1"
                           placeholder=" "
                           <?php echo ($_SESSION['role'] !== 'founder') ? 'disabled' : ''; ?>
                           >
                    <label for="setting-lockout-time-minutes" data-i18n="admin.server.lockoutTimeMinutesTitle"></label>
                </div>
            </div>
        </div>

        <div class="component-card component-card--column">
            <div class="component-card__content">
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="admin.server.allowedEmailDomainsTitle"></h2>
                    <p class="component-card__description" data-i18n="admin.server.allowedEmailDomainsDesc"></p>
                </div>
            </div>
            <div class="component-card__actions">
                 <div class="component-input-group">
                    <textarea
                           class="component-input"
                           id="setting-allowed-email-domains"
                           data-action="update-allowed-email-domains"
                           placeholder=" "
                           style="height: 120px; resize: vertical; padding-top: 12px; font-size: 14px;"
                           <?php echo ($_SESSION['role'] !== 'founder') ? 'disabled' : ''; ?>
                           ><?php echo htmlspecialchars($allowedEmailDomains); ?></textarea>
                    <label for="setting-allowed-email-domains" data-i18n="admin.server.allowedEmailDomainsTitle"></label>
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
                <div class="component-input-group" style="max-width: 265px;">
                    <input type="number"
                           class="component-input"
                           id="setting-username-cooldown"
                           data-action="update-username-cooldown"
                           value="<?php echo htmlspecialchars($usernameCooldown); ?>"
                           min="1"
                           step="1"
                           placeholder=" "
                           <?php echo ($_SESSION['role'] !== 'founder') ? 'disabled' : ''; ?>
                           >
                    <label for="setting-username-cooldown" data-i18n="admin.server.usernameCooldownTitle"></label>
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
                 <div class="component-input-group" style="max-width: 265px;">
                    <input type="number"
                           class="component-input"
                           id="setting-email-cooldown"
                           data-action="update-email-cooldown"
                           value="<?php echo htmlspecialchars($emailCooldown); ?>"
                           min="1"
                           step="1"
                           placeholder=" "
                           <?php echo ($_SESSION['role'] !== 'founder') ? 'disabled' : ''; ?>
                           >
                    <label for="setting-email-cooldown" data-i18n="admin.server.emailCooldownTitle"></label>
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
                 <div class="component-input-group" style="max-width: 265px;">
                    <input type="number"
                           class="component-input"
                           id="setting-avatar-max-size"
                           data-action="update-avatar-max-size"
                           value="<?php echo htmlspecialchars($avatarMaxSize); ?>"
                           min="1"
                           step="1"
                           placeholder=" "
                           <?php echo ($_SESSION['role'] !== 'founder') ? 'disabled' : ''; ?>
                           >
                    <label for="setting-avatar-max-size" data-i18n="admin.server.avatarMaxSizeTitle"></label>
                </div>
            </div>
        </div>
        
        </div>
</div>
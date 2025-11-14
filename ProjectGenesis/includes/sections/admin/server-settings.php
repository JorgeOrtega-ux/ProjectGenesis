<?php
// --- LÓGICA PHP ORIGINAL (SIN CAMBIOS) ---
$usernameCooldown = $GLOBALS['site_settings']['username_cooldown_days'] ?? '30';
$emailCooldown = $GLOBALS['site_settings']['email_cooldown_days'] ?? '12';
$avatarMaxSize = $GLOBALS['site_settings']['avatar_max_size_mb'] ?? '2';
$maxLoginAttempts = $GLOBALS['site_settings']['max_login_attempts'] ?? '5';
$lockoutTimeMinutes = $GLOBALS['site_settings']['lockout_time_minutes'] ?? '5';
$allowedEmailDomains = $GLOBALS['site_settings']['allowed_email_domains'] ?? 'gmail.com\noutlook.com';
$minPasswordLength = $GLOBALS['site_settings']['min_password_length'] ?? '8';
$maxPasswordLength = $GLOBALS['site_settings']['max_password_length'] ?? '72';
$maxConcurrentUsers = $GLOBALS['site_settings']['max_concurrent_users'] ?? '500';
$minUsernameLength = $GLOBALS['site_settings']['min_username_length'] ?? '6';
$maxUsernameLength = $GLOBALS['site_settings']['max_username_length'] ?? '32';
$maxEmailLength = $GLOBALS['site_settings']['max_email_length'] ?? '255';
$codeResendCooldown = $GLOBALS['site_settings']['code_resend_cooldown_seconds'] ?? '60';

// --- ▼▼▼ INICIO DE LÍNEA AÑADIDA ▼▼▼ ---
$messagingServiceStatus = $GLOBALS['site_settings']['messaging_service_enabled'] ?? '1';
// --- ▲▲▲ FIN DE LÍNEA AÑADIDA ▲▲▲ ---
?>
<div class="section-content overflow-y <?php echo ($CURRENT_SECTION === 'admin-server-settings') ? 'active' : 'disabled'; ?>" data-section="admin-server-settings">
    <div class="component-wrapper">

        <?php
        outputCsrfInput();
        ?>

        <div class="component-header-card">
            <h1 class="component-page-title" data-i18n="admin.server.title"></h1>
            <p class="component-page-description" data-i18n="admin.server.description"></p>
        </div>

        <div class="component-accordion__header" data-action="toggle-accordion">
            <div class="component-accordion__icon">
                <span class="material-symbols-rounded">settings</span>
            </div>
            <div class="component-accordion__text">
                <h2 class="component-accordion__title">Configuración General</h2>
                <p class="component-accordion__description">Estado del sitio y gestión de usuarios.</p>
            </div>
            <div class="component-accordion__arrow">
                <span class="material-symbols-rounded">expand_more</span>
            </div>
        </div>

        <div class="component-card component-card--edit-mode disabled">
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
                        <?php echo ($_SESSION['role'] !== 'founder') ? 'disabled' : ''; ?>>
                    <span class="component-toggle-slider"></span>
                </label>
            </div>
        </div>

        <div class="component-card component-card--edit-mode disabled">
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
                        echo ($_SESSION['role'] !== 'founder' || $maintenanceModeStatus == 1) ? 'disabled' : '';
                        ?>>
                    <span class="component-toggle-slider"></span>
                </label>
            </div>
        </div>
        
        <div class="component-card component-card--edit-mode disabled">
            <div class="component-card__content">
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="admin.server.messagingTitle">Servicio de Mensajería</h2>
                    <p class="component-card__description" data-i18n="admin.server.messagingDesc">Activa o desactiva el sistema de chat y mensajería para los usuarios.</p>
                </div>
            </div>
            <div class="component-card__actions">
                <label class="component-toggle-switch">
                    <input type="checkbox"
                        id="toggle-messaging-service"
                        data-action="update-messaging-service"
                        <?php echo ($messagingServiceStatus == 1) ? 'checked' : ''; ?>
                        <?php echo ($_SESSION['role'] !== 'founder') ? 'disabled' : ''; ?>>
                    <span class="component-toggle-slider"></span>
                </label>
            </div>
        </div>
        <div class="component-card component-card--edit-mode disabled">
            <div class="component-card__content">
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="admin.server.concurrentUsersTitle"></h2>
                    <p class="component-card__description" data-i18n="admin.server.concurrentUsersDesc"></p>
                </div>
            </div>
            <div class="component-card__actions" style="gap: 12px;">
                <span id="concurrent-users-display" style="font-size: 16px; font-weight: 600; padding: 0 16px;" data-i18n="">---</span>
            </div>
        </div>
        
        <div class="component-card component-card--column disabled">
            <div class="component-card__content">
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="admin.server.maxConcurrentUsersTitle"></h2>
                    <p class="component-card__description" data-i18n="admin.server.maxConcurrentUsersDesc"></p>
                </div>
            </div>
            <div class="component-card__actions">
                <div class="component-stepper component-stepper--multi"
                    style="max-width: 265px;"
                    data-action="update-max-concurrent-users"
                    data-current-value="<?php echo htmlspecialchars($maxConcurrentUsers); ?>"
                    data-min="1"
                    data-max="5000"
                    <?php echo ($_SESSION['role'] !== 'founder') ? 'disabled' : ''; ?>>
                    <button type="button" class="stepper-button" data-step-action="decrement-10" <?php echo ($_SESSION['role'] !== 'founder' || $maxConcurrentUsers <= 10) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">keyboard_double_arrow_left</span>
                    </button>
                    <button type="button" class="stepper-button" data-step-action="decrement-1" <?php echo ($_SESSION['role'] !== 'founder' || $maxConcurrentUsers <= 1) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">chevron_left</span>
                    </button>
                    <div class="stepper-value">
                        <?php echo htmlspecialchars($maxConcurrentUsers); ?>
                    </div>
                    <button type="button" class="stepper-button" data-step-action="increment-1" <?php echo ($_SESSION['role'] !== 'founder' || $maxConcurrentUsers >= 5000) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">chevron_right</span>
                    </button>
                    <button type="button" class="stepper-button" data-step-action="increment-10" <?php echo ($_SESSION['role'] !== 'founder' || $maxConcurrentUsers >= 4991) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">keyboard_double_arrow_right</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="component-accordion__header" data-action="toggle-accordion">
            <div class="component-accordion__icon">
                <span class="material-symbols-rounded">person</span>
            </div>
            <div class="component-accordion__text">
                <h2 class="component-accordion__title">Reglas de Cuentas y Contraseñas</h2>
                <p class="component-accordion__description">Define los límites para nombres de usuario, emails y contraseñas.</p>
            </div>
            <div class="component-accordion__arrow">
                <span class="material-symbols-rounded">expand_more</span>
            </div>
        </div>

        <div class="component-card component-card--column disabled">
            <div class="component-card__content">
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="admin.server.minPasswordLengthTitle"></h2>
                    <p class="component-card__description" data-i18n="admin.server.minPasswordLengthDesc"></p>
                </div>
            </div>
            <div class="component-card__actions">
                <div class="component-stepper component-stepper--multi"
                    style="max-width: 265px;"
                    data-action="update-min-password-length"
                    data-current-value="<?php echo htmlspecialchars($minPasswordLength); ?>"
                    data-min="8"
                    data-max="72"
                    <?php echo ($_SESSION['role'] !== 'founder') ? 'disabled' : ''; ?>>
                    <button type="button" class="stepper-button" data-step-action="decrement-10" <?php echo ($_SESSION['role'] !== 'founder' || $minPasswordLength <= 17) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">keyboard_double_arrow_left</span>
                    </button>
                    <button type="button" class="stepper-button" data-step-action="decrement-1" <?php echo ($_SESSION['role'] !== 'founder' || $minPasswordLength <= 8) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">chevron_left</span>
                    </button>
                    <div class="stepper-value" id="stepper-value-min-password-length">
                        <?php echo htmlspecialchars($minPasswordLength); ?>
                    </div>
                    <button type="button" class="stepper-button" data-step-action="increment-1" <?php echo ($_SESSION['role'] !== 'founder' || $minPasswordLength >= 72) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">chevron_right</span>
                    </button>
                    <button type="button" class="stepper-button" data-step-action="increment-10" <?php echo ($_SESSION['role'] !== 'founder' || $minPasswordLength >= 63) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">keyboard_double_arrow_right</span>
                    </button>
                </div>
            </div>
        </div>
        <div class="component-card component-card--column disabled">
            <div class="component-card__content">
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="admin.server.maxPasswordLengthTitle"></h2>
                    <p class="component-card__description" data-i18n="admin.server.maxPasswordLengthDesc"></p>
                </div>
            </div>
            <div class="component-card__actions">
                <div class="component-stepper component-stepper--multi"
                    style="max-width: 265px;"
                    data-action="update-max-password-length"
                    data-current-value="<?php echo htmlspecialchars($maxPasswordLength); ?>"
                    data-min="8"
                    data-max="72"
                    <?php echo ($_SESSION['role'] !== 'founder') ? 'disabled' : ''; ?>>
                    <button type="button" class="stepper-button" data-step-action="decrement-10" <?php echo ($_SESSION['role'] !== 'founder' || $maxPasswordLength <= 17) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">keyboard_double_arrow_left</span>
                    </button>
                    <button type="button" class="stepper-button" data-step-action="decrement-1" <?php echo ($_SESSION['role'] !== 'founder' || $maxPasswordLength <= 8) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">chevron_left</span>
                    </button>
                    <div class="stepper-value" id="stepper-value-max-password-length">
                        <?php echo htmlspecialchars($maxPasswordLength); ?>
                    </div>
                    <button type="button" class="stepper-button" data-step-action="increment-1" <?php echo ($_SESSION['role'] !== 'founder' || $maxPasswordLength >= 72) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">chevron_right</span>
                    </button>
                    <button type="button" class="stepper-button" data-step-action="increment-10" <?php echo ($_SESSION['role'] !== 'founder' || $maxPasswordLength >= 63) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">keyboard_double_arrow_right</span>
                    </button>
                </div>
            </div>
        </div>
        <div class="component-card component-card--column disabled">
            <div class="component-card__content">
                <div class="component-card__text">
                    <h2 class="component-card__title">Longitud Mínima de Usuario</h2>
                    <p class="component-card__description">El número mínimo de caracteres para un nombre de usuario (mín. 6).</p>
                </div>
            </div>
            <div class="component-card__actions">
                <div class="component-stepper component-stepper--multi"
                    style="max-width: 265px;"
                    data-action="update-min-username-length"
                    data-current-value="<?php echo htmlspecialchars($minUsernameLength); ?>"
                    data-min="6"
                    data-max="32"
                    <?php echo ($_SESSION['role'] !== 'founder') ? 'disabled' : ''; ?>>
                    <button type="button" class="stepper-button" data-step-action="decrement-10" <?php echo ($_SESSION['role'] !== 'founder' || $minUsernameLength <= 15) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">keyboard_double_arrow_left</span>
                    </button>
                    <button type="button" class="stepper-button" data-step-action="decrement-1" <?php echo ($_SESSION['role'] !== 'founder' || $minUsernameLength <= 6) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">chevron_left</span>
                    </button>
                    <div class="stepper-value">
                        <?php echo htmlspecialchars($minUsernameLength); ?>
                    </div>
                    <button type="button" class="stepper-button" data-step-action="increment-1" <?php echo ($_SESSION['role'] !== 'founder' || $minUsernameLength >= 32) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">chevron_right</span>
                    </button>
                    <button type="button" class="stepper-button" data-step-action="increment-10" <?php echo ($_SESSION['role'] !== 'founder' || $minUsernameLength >= 23) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">keyboard_double_arrow_right</span>
                    </button>
                </div>
            </div>
        </div>
        <div class="component-card component-card--column disabled">
            <div class="component-card__content">
                <div class="component-card__text">
                    <h2 class="component-card__title">Longitud Máxima de Usuario</h2>
                    <p class="component-card__description">El número máximo de caracteres para un nombre de usuario (máx. 32).</p>
                </div>
            </div>
            <div class="component-card__actions">
                <div class="component-stepper component-stepper--multi"
                    style="max-width: 265px;"
                    data-action="update-max-username-length"
                    data-current-value="<?php echo htmlspecialchars($maxUsernameLength); ?>"
                    data-min="6"
                    data-max="32"
                    <?php echo ($_SESSION['role'] !== 'founder') ? 'disabled' : ''; ?>>
                    <button type="button" class="stepper-button" data-step-action="decrement-10" <?php echo ($_SESSION['role'] !== 'founder' || $maxUsernameLength <= 15) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">keyboard_double_arrow_left</span>
                    </button>
                    <button type="button" class="stepper-button" data-step-action="decrement-1" <?php echo ($_SESSION['role'] !== 'founder' || $maxUsernameLength <= 6) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">chevron_left</span>
                    </button>
                    <div class="stepper-value">
                        <?php echo htmlspecialchars($maxUsernameLength); ?>
                    </div>
                    <button type="button" class="stepper-button" data-step-action="increment-1" <?php echo ($_SESSION['role'] !== 'founder' || $maxUsernameLength >= 32) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">chevron_right</span>
                    </button>
                    <button type="button" class="stepper-button" data-step-action="increment-10" <?php echo ($_SESSION['role'] !== 'founder' || $maxUsernameLength >= 23) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">keyboard_double_arrow_right</span>
                    </button>
                </div>
            </div>
        </div>
        <div class="component-card component-card--column disabled">
            <div class="component-card__content">
                <div class="component-card__text">
                    <h2 class="component-card__title">Longitud Máxima de Email</h2>
                    <p class="component-card__description">El número máximo de caracteres para un email (mín. 64, máx. 255).</p>
                </div>
            </div>
            <div class="component-card__actions">
                <div class="component-stepper component-stepper--multi"
                    style="max-width: 265px;"
                    data-action="update-max-email-length"
                    data-current-value="<?php echo htmlspecialchars($maxEmailLength); ?>"
                    data-min="64"
                    data-max="255"
                    <?php echo ($_SESSION['role'] !== 'founder') ? 'disabled' : ''; ?>>
                    <button type="button" class="stepper-button" data-step-action="decrement-10" <?php echo ($_SESSION['role'] !== 'founder' || $maxEmailLength <= 73) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">keyboard_double_arrow_left</span>
                    </button>
                    <button type="button" class="stepper-button" data-step-action="decrement-1" <?php echo ($_SESSION['role'] !== 'founder' || $maxEmailLength <= 64) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">chevron_left</span>
                    </button>
                    <div class="stepper-value">
                        <?php echo htmlspecialchars($maxEmailLength); ?>
                    </div>
                    <button type="button" class="stepper-button" data-step-action="increment-1" <?php echo ($_SESSION['role'] !== 'founder' || $maxEmailLength >= 255) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">chevron_right</span>
                    </button>
                    <button type="button" class="stepper-button" data-step-action="increment-10" <?php echo ($_SESSION['role'] !== 'founder' || $maxEmailLength >= 246) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">keyboard_double_arrow_right</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="component-accordion__header" data-action="toggle-accordion">
            <div class="component-accordion__icon">
                <span class="material-symbols-rounded">security</span>
            </div>
            <div class="component-accordion__text">
                <h2 class="component-accordion__title">Seguridad y Bloqueos</h2>
                <p class="component-accordion__description">Configuraciones de reintentos, bloqueos y cooldowns.</p>
            </div>
            <div class="component-accordion__arrow">
                <span class="material-symbols-rounded">expand_more</span>
            </div>
        </div>
        
        <div class="component-card component-card--column disabled">
            <div class="component-card__content">
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="admin.server.maxLoginAttemptsTitle"></h2>
                    <p class="component-card__description" data-i18n="admin.server.maxLoginAttemptsDesc"></p>
                </div>
            </div>
            <div class="component-card__actions">
                <div class="component-stepper component-stepper--multi"
                    style="max-width: 265px;"
                    data-action="update-max-login-attempts"
                    data-current-value="<?php echo htmlspecialchars($maxLoginAttempts); ?>"
                    data-min="3"
                    data-max="20"
                    <?php echo ($_SESSION['role'] !== 'founder') ? 'disabled' : ''; ?>>
                    <button type="button" class="stepper-button" data-step-action="decrement-10" <?php echo ($_SESSION['role'] !== 'founder' || $maxLoginAttempts <= 12) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">keyboard_double_arrow_left</span>
                    </button>
                    <button type="button" class="stepper-button" data-step-action="decrement-1" <?php echo ($_SESSION['role'] !== 'founder' || $maxLoginAttempts <= 3) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">chevron_left</span>
                    </button>
                    <div class="stepper-value" id="stepper-value-max-login-attempts">
                        <?php echo htmlspecialchars($maxLoginAttempts); ?>
                    </div>
                    <button type="button" class="stepper-button" data-step-action="increment-1" <?php echo ($_SESSION['role'] !== 'founder' || $maxLoginAttempts >= 20) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">chevron_right</span>
                    </button>
                    <button type="button" class="stepper-button" data-step-action="increment-10" <?php echo ($_SESSION['role'] !== 'founder' || $maxLoginAttempts >= 11) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">keyboard_double_arrow_right</span>
                    </button>
                </div>
            </div>
        </div>
        <div class="component-card component-card--column disabled">
            <div class="component-card__content">
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="admin.server.lockoutTimeMinutesTitle"></h2>
                    <p class="component-card__description" data-i18n="admin.server.lockoutTimeMinutesDesc"></p>
                </div>
            </div>
            <div class="component-card__actions">
                <div class="component-stepper component-stepper--multi"
                    style="max-width: 265px;"
                    data-action="update-lockout-time-minutes"
                    data-current-value="<?php echo htmlspecialchars($lockoutTimeMinutes); ?>"
                    data-min="1"
                    data-max="60"
                    <?php echo ($_SESSION['role'] !== 'founder') ? 'disabled' : ''; ?>>
                    <button type="button" class="stepper-button" data-step-action="decrement-10" <?php echo ($_SESSION['role'] !== 'founder' || $lockoutTimeMinutes <= 10) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">keyboard_double_arrow_left</span>
                    </button>
                    <button type="button" class="stepper-button" data-step-action="decrement-1" <?php echo ($_SESSION['role'] !== 'founder' || $lockoutTimeMinutes <= 1) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">chevron_left</span>
                    </button>
                    <div class="stepper-value" id="stepper-value-lockout-time-minutes">
                        <?php echo htmlspecialchars($lockoutTimeMinutes); ?>
                    </div>
                    <button type="button" class="stepper-button" data-step-action="increment-1" <?php echo ($_SESSION['role'] !== 'founder' || $lockoutTimeMinutes >= 60) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">chevron_right</span>
                    </button>
                    <button type="button" class="stepper-button" data-step-action="increment-10" <?php echo ($_SESSION['role'] !== 'founder' || $lockoutTimeMinutes >= 51) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">keyboard_double_arrow_right</span>
                    </button>
                </div>
            </div>
        </div>
        <div class="component-card component-card--column disabled">
            <div class="component-card__content">
                <div class="component-card__text">
                    <h2 class="component-card__title">Cooldown de Reenvío de Código (Segundos)</h2>
                    <p class="component-card__description">Segundos que un usuario debe esperar para reenviar un código de verificación (mín. 30).</p>
                </div>
            </div>
            <div class="component-card__actions">
                <div class="component-stepper component-stepper--multi"
                    style="max-width: 265px;"
                    data-action="update-code-resend-cooldown"
                    data-current-value="<?php echo htmlspecialchars($codeResendCooldown); ?>"
                    data-min="30"
                    data-max="300"
                    data-step-1="5"  data-step-10="15" <?php echo ($_SESSION['role'] !== 'founder') ? 'disabled' : ''; ?>>
                    <button type="button" class="stepper-button" data-step-action="decrement-10" <?php echo ($_SESSION['role'] !== 'founder' || $codeResendCooldown <= 44) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">keyboard_double_arrow_left</span>
                    </button>
                    <button type="button" class="stepper-button" data-step-action="decrement-1" <?php echo ($_SESSION['role'] !== 'founder' || $codeResendCooldown <= 30) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">chevron_left</span>
                    </button>
                    <div class="stepper-value">
                        <?php echo htmlspecialchars($codeResendCooldown); ?>
                    </div>
                    <button type="button" class="stepper-button" data-step-action="increment-1" <?php echo ($_SESSION['role'] !== 'founder' || $codeResendCooldown >= 300) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">chevron_right</span>
                    </button>
                    <button type="button" class="stepper-button" data-step-action="increment-10" <?php echo ($_SESSION['role'] !== 'founder' || $codeResendCooldown >= 286) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">keyboard_double_arrow_right</span>
                    </button>
                </div>
            </div>
        </div>
        
        <div class="component-accordion__header" data-action="toggle-accordion">
            <div class="component-accordion__icon">
                <span class="material-symbols-rounded">tune</span>
            </div>
            <div class="component-accordion__text">
                <h2 class="component-accordion__title">Configuración de Funcionalidades</h2>
                <p class="component-accordion__description">Cooldowns de perfil y límites de subida.</p>
            </div>
            <div class="component-accordion__arrow">
                <span class="material-symbols-rounded">expand_more</span>
            </div>
        </div>

        <div class="component-card component-card--column disabled">
            <div class="component-card__content">
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="admin.server.usernameCooldownTitle"></h2>
                    <p class="component-card__description" data-i18n="admin.server.usernameCooldownDesc"></p>
                </div>
            </div>
            <div class="component-card__actions">
                <div class="component-stepper component-stepper--multi"
                    style="max-width: 265px;"
                    data-action="update-username-cooldown"
                    data-current-value="<?php echo htmlspecialchars($usernameCooldown); ?>"
                    data-min="1"
                    data-max="365"
                    <?php echo ($_SESSION['role'] !== 'founder') ? 'disabled' : ''; ?>>
                    <button type="button" class="stepper-button" data-step-action="decrement-10" <?php echo ($_SESSION['role'] !== 'founder' || $usernameCooldown <= 10) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">keyboard_double_arrow_left</span>
                    </button>
                    <button type="button" class="stepper-button" data-step-action="decrement-1" <?php echo ($_SESSION['role'] !== 'founder' || $usernameCooldown <= 1) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">chevron_left</span>
                    </button>
                    <div class="stepper-value" id="stepper-value-username-cooldown">
                        <?php echo htmlspecialchars($usernameCooldown); ?>
                    </div>
                    <button type="button" class="stepper-button" data-step-action="increment-1" <?php echo ($_SESSION['role'] !== 'founder' || $usernameCooldown >= 365) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">chevron_right</span>
                    </button>
                    <button type="button" class="stepper-button" data-step-action="increment-10" <?php echo ($_SESSION['role'] !== 'founder' || $usernameCooldown >= 356) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">keyboard_double_arrow_right</span>
                    </button>
                </div>
            </div>
        </div>
        <div class="component-card component-card--column disabled">
            <div class="component-card__content">
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="admin.server.emailCooldownTitle"></h2>
                    <p class="component-card__description" data-i18n="admin.server.emailCooldownDesc"></p>
                </div>
            </div>
            <div class="component-card__actions">
                <div class="component-stepper component-stepper--multi"
                    style="max-width: 265px;"
                    data-action="update-email-cooldown"
                    data-current-value="<?php echo htmlspecialchars($emailCooldown); ?>"
                    data-min="1"
                    data-max="365"
                    <?php echo ($_SESSION['role'] !== 'founder') ? 'disabled' : ''; ?>>
                    <button type="button" class="stepper-button" data-step-action="decrement-10" <?php echo ($_SESSION['role'] !== 'founder' || $emailCooldown <= 10) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">keyboard_double_arrow_left</span>
                    </button>
                    <button type="button" class="stepper-button" data-step-action="decrement-1" <?php echo ($_SESSION['role'] !== 'founder' || $emailCooldown <= 1) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">chevron_left</span>
                    </button>
                    <div class="stepper-value" id="stepper-value-email-cooldown">
                        <?php echo htmlspecialchars($emailCooldown); ?>
                    </div>
                    <button type="button" class="stepper-button" data-step-action="increment-1" <?php echo ($_SESSION['role'] !== 'founder' || $emailCooldown >= 365) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">chevron_right</span>
                    </button>
                    <button type="button" class="stepper-button" data-step-action="increment-10" <?php echo ($_SESSION['role'] !== 'founder' || $emailCooldown >= 356) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">keyboard_double_arrow_right</span>
                    </button>
                </div>
            </div>
        </div>
        <div class="component-card component-card--column disabled">
            <div class="component-card__content">
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="admin.server.avatarMaxSizeTitle"></h2>
                    <p class="component-card__description" data-i18n="admin.server.avatarMaxSizeDesc"></p>
                </div>
            </div>
            <div class="component-card__actions">
                <div class="component-stepper component-stepper--multi"
                    style="max-width: 265px;"
                    data-action="update-avatar-max-size"
                    data-current-value="<?php echo htmlspecialchars($avatarMaxSize); ?>"
                    data-min="1"
                    data-max="20"
                    <?php echo ($_SESSION['role'] !== 'founder') ? 'disabled' : ''; ?>>
                    <button type="button" class="stepper-button" data-step-action="decrement-10" <?php echo ($_SESSION['role'] !== 'founder' || $avatarMaxSize <= 10) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">keyboard_double_arrow_left</span>
                    </button>
                    <button type="button" class="stepper-button" data-step-action="decrement-1" <?php echo ($_SESSION['role'] !== 'founder' || $avatarMaxSize <= 1) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">chevron_left</span>
                    </button>
                    <div class="stepper-value" id="stepper-value-avatar-max-size">
                        <?php echo htmlspecialchars($avatarMaxSize); ?>
                    </div>
                    <button type="button" class="stepper-button" data-step-action="increment-1" <?php echo ($_SESSION['role'] !== 'founder' || $avatarMaxSize >= 20) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">chevron_right</span>
                    </button>
                    <button type="button" class="stepper-button" data-step-action="increment-10" <?php echo ($_SESSION['role'] !== 'founder' || $avatarMaxSize >= 11) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">keyboard_double_arrow_right</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="component-accordion__header" data-action="toggle-accordion">
            <div class="component-accordion__icon">
                <span class="material-symbols-rounded">mail</span>
            </div>
            <div class="component-accordion__text">
                <h2 class="component-accordion__title">Dominios de Email Permitidos</h2>
                <p class="component-accordion__description">Gestiona qué dominios de email pueden registrarse.</p>
            </div>
            <div class="component-accordion__arrow">
                <span class="material-symbols-rounded">expand_more</span>
            </div>
        </div>
        
        <div class="component-card component-card--column disabled" id="admin-domain-card">
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

    </div>
</div>
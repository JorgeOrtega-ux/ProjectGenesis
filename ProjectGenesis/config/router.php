<?php
// /ProjectGenesis/config/router.php

// --- MODIFICACIÓN 1: INCLUIR CONFIG ---
include '../config/config.php'; // Inicia la sesión

// --- ▼▼▼ INICIO: NUEVA FUNCIÓN DE ERROR DE REGISTRO ▼▼▼ ---
/**
 * Muestra una página de error HTML personalizada para el flujo de registro.
 * Detiene la ejecución del script.
 *
 * @param string $basePath El path base del proyecto (viene de config.php).
 * @param string $messageKey La *clave de traducción* del mensaje principal.
 * @param string $detailsKey La *clave de traducción* de los detalles.
 */
function showRegistrationError($basePath, $messageKey, $detailsKey) {
    // Limpiamos cualquier salida de HTML anterior
    if (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    // Enviamos un código de error HTTP (Bad Request)
    http_response_code(400);

    // Imprimimos la página de error
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
    // Cargamos los CSS existentes para mantener el estilo
    echo '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded">';
    echo '<link rel="stylesheet" type="text/css" href="' . htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') . '/assets/css/styles.css">';
    
    // --- ▼▼▼ MODIFICADO ▼▼▼ ---
    echo '<title data-i18n="page.error.title">Error en el registro</title></head>';
    // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
    
    echo '<body style="background-color: #f5f5fa;">'; // Fondo gris como el resto
    
    // Usamos las clases CSS de 'auth-container' y 'not-found' para el estilo
    echo '<div class="section-content active" style="align-items: center; justify-content: center; height: 100vh;">';
    echo '<div class="auth-container" style="max-width: 460px;">';
    
    // --- ▼▼▼ MODIFICADO ▼▼▼ ---
    echo '<h1 class="auth-title" style="font-size: 36px; margin-bottom: 16px;" data-i18n="page.error.oopsTitle">¡Uy! Faltan datos.</h1>';
    
    echo '<div class="auth-error-message" style="display: block; background-color: #ffffff; border: 1px solid #00000020; color: #1f2937; margin-bottom: 24px; text-align: left; padding: 16px;">';
    echo '<strong style="display: block; font-size: 16px; margin-bottom: 8px; color: #000;" data-i18n="' . htmlspecialchars($messageKey, ENT_QUOTES, 'UTF-8') . '"></strong>';
    echo '<p style="font-size: 14px; margin: 0; color: #6b7280; line-height: 1.5;" data-i18n="' . htmlspecialchars($detailsKey, ENT_QUOTES, 'UTF-8') . '"></p>';
    echo '</div>';
    
    echo '<a href="' . htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') . '/register" class="auth-button" style="text-decoration: none; text-align: center; line-height: 52px; display: block; width: 100%;" data-i18n="page.error.backToRegister">Volver al inicio del registro</a>';
    // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
    
    echo '</div></div>';
    echo '</body></html>';
    
    // NOTA: Esta página de error no cargará i18n-manager.js, por lo que mostrará las claves.
    // Una solución más avanzada implicaría inyectar un <script> que cargue y aplique
    // las traducciones JSON, pero eso está fuera de este alcance.
}
// --- ▲▲▲ FIN: NUEVA FUNCIÓN DE ERROR DE REGISTRO ▲▲▲ ---


// --- ▼▼▼ ¡NUEVA FUNCIÓN DE ERROR DE RESETEO! ▼▼▼ ---
/**
 * Muestra una página de error HTML personalizada para el flujo de reseteo.
 * @param string $basePath
 * @param string $messageKey
 * @param string $detailsKey
 */
function showResetError($basePath, $messageKey, $detailsKey) {
    if (ob_get_level() > 0) ob_end_clean();
    http_response_code(400);

    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=deVice-width, initial-scale=1.0">';
    echo '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded">';
    echo '<link rel="stylesheet" type="text/css" href="' . htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') . '/assets/css/styles.css">';
    // --- ▼▼▼ MODIFICADO ▼▼▼ ---
    echo '<title data-i18n="page.error.titleReset">Error en la recuperación</title></head>';
    echo '<body style="background-color: #f5f5fa;">';
    echo '<div class="section-content active" style="align-items: center; justify-content: center; height: 100vh;">';
    echo '<div class="auth-container" style="max-width: 460px;">';
    echo '<h1 class="auth-title" style="font-size: 36px; margin-bottom: 16px;" data-i18n="page.error.oopsTitle">¡Uy! Faltan datos.</h1>';
    echo '<div class="auth-error-message" style="display: block; background-color: #ffffff; border: 1px solid #00000020; color: #1f2937; margin-bottom: 24px; text-align: left; padding: 16px;">';
    echo '<strong style="display: block; font-size: 16px; margin-bottom: 8px; color: #000;" data-i18n="' . htmlspecialchars($messageKey, ENT_QUOTES, 'UTF-8') . '"></strong>';
    echo '<p style="font-size: 14px; margin: 0; color: #6b7280; line-height: 1.5;" data-i18n="' . htmlspecialchars($detailsKey, ENT_QUOTES, 'UTF-8') . '"></p>';
    echo '</div>';
    echo '<a href="' . htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') . '/reset-password" class="auth-button" style="text-decoration: none; text-align: center; line-height: 52px; display: block; width: 100%;" data-i18n="page.error.backToReset">Volver al inicio de la recuperación</a>';
    // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
    echo '</div></div>';
    echo '</body></html>';
}
// --- ▲▲▲ FIN: NUEVA FUNCIÓN DE ERROR DE RESETEO ▲▲▲ ---


$page = $_GET['page'] ?? 'home';

$CURRENT_SECTION = $page; 

// --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
$allowedPages = [
    'home'     => '../includes/sections/main/home.php',
    'explorer' => '../includes/sections/main/explorer.php',
    'login'    => '../includes/sections/auth/login.php',
    '404'      => '../includes/sections/main/404.php', 

    // Nuevas secciones de Registro
    'register-step1' => '../includes/sections/auth/register.php',
    'register-step2' => '../includes/sections/auth/register.php',
    'register-step3' => '../includes/sections/auth/register.php',

    // Nuevas secciones de Reseteo
    'reset-step1' => '../includes/sections/auth/reset-password.php',
    'reset-step2' => '../includes/sections/auth/reset-password.php',
    'reset-step3' => '../includes/sections/auth/reset-password.php',

    // Nuevas secciones de Configuración
    'settings-profile'       => '../includes/sections/settings/your-profile.php',
    'settings-login'         => '../includes/sections/settings/login-security.php',
    'settings-accessibility' => '../includes/sections/settings/accessibility.php',
    'settings-devices'       => '../includes/sections/settings/device-sessions.php', // <-- AÑADIDO
    
    // ▼▼▼ AÑADIR ESTAS LÍNEAS ▼▼▼
    'account-status-deleted'   => '../includes/sections/auth/account-status.php',
    'account-status-suspended' => '../includes/sections/auth/account-status.php',
];
// --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

// --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
$authPages = ['login'];
$isAuthPage = in_array($page, $authPages) || 
              strpos($page, 'register-') === 0 ||
              strpos($page, 'reset-') === 0 ||
              strpos($page, 'account-status-') === 0; // <-- AÑADIDO ESTO
// --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

$isSettingsPage = strpos($page, 'settings-') === 0;

// --- ▼▼▼ INICIO DE MODIFICACIÓN: DETERMINAR TIPO DE ESTADO DE CUENTA ▼▼▼ ---
$accountStatusType = 'none'; // Valor por defecto
if ($page === 'account-status-deleted') {
    $accountStatusType = 'deleted';
} elseif ($page === 'account-status-suspended') {
    $accountStatusType = 'suspended';
}
// --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

if (!isset($_SESSION['user_id']) && !$isAuthPage && $page !== '404') {
    http_response_code(403); // 403 Forbidden
    $CURRENT_SECTION = '404'; 
    include $allowedPages['404'];
    exit; // Detener script
}

if (array_key_exists($page, $allowedPages)) {

    // --- LÓGICA DE VALIDACIÓN DE PASOS DE REGISTRO ---
    $CURRENT_REGISTER_STEP = 1; // Default
    $initialCooldown = 0; 

    if ($page === 'register-step1') {
        $CURRENT_REGISTER_STEP = 1;
        unset($_SESSION['registration_step']);
        unset($_SESSION['registration_email']); 
        echo '<script>sessionStorage.removeItem("regEmail"); sessionStorage.removeItem("regPass");</script>';

    } elseif ($page === 'register-step2') {
        if (!isset($_SESSION['registration_step']) || $_SESSION['registration_step'] < 2) {
            // --- ▼▼▼ MODIFICADO ▼▼▼ ---
            showRegistrationError($basePath, 'page.error.400title', 'page.error.regStep1');
            // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
            exit; 
        }
        $CURRENT_REGISTER_STEP = 2;

    } elseif ($page === 'register-step3') {
        if (!isset($_SESSION['registration_step']) || $_SESSION['registration_step'] < 3) {
            // --- ▼▼▼ MODIFICADO ▼▼▼ ---
            showRegistrationError($basePath, 'page.error.400title', 'page.error.regStep2');
            // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
            exit;
        }
        $CURRENT_REGISTER_STEP = 3;

        if (isset($_SESSION['registration_email'])) {
            try {
                $email = $_SESSION['registration_email'];
                $stmt = $pdo->prepare("SELECT created_at FROM verification_codes WHERE identifier = ? AND code_type = 'registration' ORDER BY created_at DESC LIMIT 1");
                $stmt->execute([$email]);
                $codeData = $stmt->fetch();

                if ($codeData) {
                    $lastCodeTime = new DateTime($codeData['created_at'], new DateTimeZone('UTC'));
                    $currentTime = new DateTime('now', new DateTimeZone('UTC'));
                    $secondsPassed = $currentTime->getTimestamp() - $lastCodeTime->getTimestamp();
                    $cooldownConstant = 60; 

                    if ($secondsPassed < $cooldownConstant) {
                        $initialCooldown = $cooldownConstant - $secondsPassed;
                    }
                }
            } catch (PDOException $e) {
                logDatabaseError($e, 'router - register-step3-cooldown');
                $initialCooldown = 0; 
            }
        }
    }
    
    // --- ▼▼▼ ¡NUEVA LÓGICA DE VALIDACIÓN DE PASOS DE RESETEO! ▼▼▼ ---
    $CURRENT_RESET_STEP = 1; // Default

    if ($page === 'reset-step1') {
        $CURRENT_RESET_STEP = 1;
        unset($_SESSION['reset_step']);
        unset($_SESSION['reset_email']); // Guardaremos el email aquí
        echo '<script>sessionStorage.removeItem("resetEmail"); sessionStorage.removeItem("resetCode");</script>';

    } elseif ($page === 'reset-step2') {
        if (!isset($_SESSION['reset_step']) || $_SESSION['reset_step'] < 2) {
            // --- ▼▼▼ MODIFICADO ▼▼▼ ---
            showResetError($basePath, 'page.error.400title', 'page.error.resetStep1');
            // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
            exit;
        }
        $CURRENT_RESET_STEP = 2;

    } elseif ($page === 'reset-step3') {
        if (!isset($_SESSION['reset_step']) || $_SESSION['reset_step'] < 3) {
            // --- ▼▼▼ MODIFICADO ▼▼▼ ---
            showResetError($basePath, 'page.error.400title', 'page.error.resetStep2');
            // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
            exit;
        }
        $CURRENT_RESET_STEP = 3;
    }
    // --- ▲▲▲ FIN DE LA LÓGICA DE VALIDACIÓN DE RESETEO ▲▲▲ ---


    // --- LÓGICA DE PRE-PROCESAMIENTO DE PÁGINAS DE SETTINGS ---
    if ($page === 'settings-profile') {
        $defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";
        $profileImageUrl = $_SESSION['profile_image_url'] ?? $defaultAvatar;
        if (empty($profileImageUrl)) $profileImageUrl = $defaultAvatar;
        $isDefaultAvatar = strpos($profileImageUrl, 'ui-avatars.com') !== false || strpos($profileImageUrl, 'user-' . $_SESSION['user_id'] . '.png') !== false;
        $usernameForAlt = $_SESSION['username'] ?? 'Usuario';
        $userRole = $_SESSION['role'] ?? 'user';
        $userEmail = $_SESSION['email'] ?? 'correo@ejemplo.com';
        
        // --- ▼▼▼ ¡INICIO DE MODIFICACIÓN: LEER PREFS DE SESIÓN! ▼▼▼ ---
        $userLanguage = $_SESSION['language'] ?? 'en-us';
        $userUsageType = $_SESSION['usage_type'] ?? 'personal';
        $openLinksInNewTab = (int)($_SESSION['open_links_in_new_tab'] ?? 1); 
        // --- ▲▲▲ ¡FIN DE MODIFICACIÓN! ▲▲▲ ---

    } elseif ($page === 'settings-login') {
        try {
            $stmt_pass_log = $pdo->prepare("SELECT changed_at FROM user_audit_logs WHERE user_id = ? AND change_type = 'password' ORDER BY changed_at DESC LIMIT 1");
            $stmt_pass_log->execute([$_SESSION['user_id']]);
            $lastLog = $stmt_pass_log->fetch();

            if ($lastLog) {
                // --- ▼▼▼ INICIO DE LA MODIFICACIÓN (TEXTO A CLAVE) ▼▼▼ ---
                // ¡Problema! Esta lógica genera texto con datos dinámicos.
                // El JS no podrá traducir "settings.login.lastPassUpdateDate" y reemplazar %date%.
                // La vista (login-security.php) es la que debe tener la clave.
                // Dejamos el texto estático con clave, pero el dinámico se queda como estaba.
                // Esta es una limitación del patrón actual.
                
                if (!class_exists('IntlDateFormatter')) {
                    $date = new DateTime($lastLog['changed_at']);
                    $lastPasswordUpdateText = 'Última actualización de tu contraseña: ' . $date->format('d/m/Y');
                } else {
                    $formatter = new IntlDateFormatter('es_ES', IntlDateFormatter::LONG, IntlDateFormatter::NONE, 'UTC');
                    $timestamp = strtotime($lastLog['changed_at']);
                    $lastPasswordUpdateText = 'Última actualización de tu contraseña: ' . $formatter->format($timestamp);
                }
                // (En una refactorización ideal, pasaríamos $lastLog['changed_at'] a la vista
                // y la vista contendría <span data-i18n="settings.login.lastPassUpdateDate" data-timestamp="...">
                // y el JS haría la traducción con la fecha).
                // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
            } else {
                // --- ▼▼▼ INICIO DE LA MODIFICACIÓN (TEXTO A CLAVE) ▼▼▼ ---
                $lastPasswordUpdateText = 'settings.login.lastPassUpdateNever'; // Esta SÍ se puede cambiar
                // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
            }

            $stmt_2fa = $pdo->prepare("SELECT is_2fa_enabled FROM users WHERE id = ?");
            $stmt_2fa->execute([$_SESSION['user_id']]);
            $is2faEnabled = (int)$stmt_2fa->fetchColumn(); 
        } catch (PDOException $e) {
            logDatabaseError($e, 'router - settings-login');
            // --- ▼▼▼ INICIO DE LA MODIFICACIÓN (TEXTO A CLAVE) ▼▼▼ ---
            $lastPasswordUpdateText = 'settings.login.lastPassUpdateError'; // Esta SÍ se puede cambiar
            // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
            $is2faEnabled = 0; 
        }
    } elseif ($page === 'settings-accessibility') {
        // --- ▼▼▼ ¡INICIO DE MODIFICACIÓN: LEER PREFS DE SESIÓN! ▼▼▼ ---
        $userTheme = $_SESSION['theme'] ?? 'system';
        $increaseMessageDuration = (int)($_SESSION['increase_message_duration'] ?? 0);
        // --- ▲▲▲ ¡FIN DE MODIFICACIÓN! ▲▲▲ ---
    }
    
    // (Añadir aquí la lógica de 'settings-devices' si es necesario, 
    // pero ya la pusimos dentro del propio archivo 'device-sessions.php')
    
    include $allowedPages[$page];

} else {
    http_response_code(404);
    $CURRENT_SECTION = '404'; 
    include $allowedPages['404']; 
}
?>
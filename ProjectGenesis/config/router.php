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
 * @param string $message El mensaje principal del error (ej. "Error 400").
 * @param string $details La explicación del error.
 */
function showRegistrationError($basePath, $message, $details) {
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
    echo '<title>Error en el registro</title></head>';
    echo '<body style="background-color: #f5f5fa;">'; // Fondo gris como el resto
    
    // Usamos las clases CSS de 'auth-container' y 'not-found' para el estilo
    echo '<div class="section-content active" style="align-items: center; justify-content: center; height: 100vh;">';
    echo '<div class="auth-container" style="max-width: 460px;">';
    
    // Título (similar a tu imagen)
    echo '<h1 class="auth-title" style="font-size: 36px; margin-bottom: 16px;">¡Uy! Faltan datos.</h1>';
    
    // Contenedor del error (similar a tu imagen)
    echo '<div class="auth-error-message" style="display: block; background-color: #ffffff; border: 1px solid #00000020; color: #1f2937; margin-bottom: 24px; text-align: left; padding: 16px;">';
    echo '<strong style="display: block; font-size: 16px; margin-bottom: 8px; color: #000;">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</strong>';
    echo '<p style="font-size: 14px; margin: 0; color: #6b7280; line-height: 1.5;">' . htmlspecialchars($details, ENT_QUOTES, 'UTF-8') . '</p>';
    echo '</div>';
    
    // Botón para volver
    echo '<a href="' . htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') . '/register" class="auth-button" style="text-decoration: none; text-align: center; line-height: 52px; display: block; width: 100%;">Volver al inicio del registro</a>';
    
    echo '</div></div>';
    echo '</body></html>';
}
// --- ▲▲▲ FIN: NUEVA FUNCIÓN DE ERROR DE REGISTRO ▲▲▲ ---


$page = $_GET['page'] ?? 'home';

$CURRENT_SECTION = $page; 

// --- ▼▼▼ MODIFICACIÓN: AÑADIR RUTAS DE SETTINGS Y REGISTRO ▼▼▼ ---
$allowedPages = [
    'home'     => '../includes/sections/main/home.php',
    'explorer' => '../includes/sections/main/explorer.php',
    'login'    => '../includes/sections/auth/login.php',
    // 'register' => '../includes/sections/auth/register.php', // <-- Eliminado
    'reset-password' => '../includes/sections/auth/reset-password.php',
    '404'      => '../includes/sections/main/404.php', 

    // Nuevas secciones de Registro
    'register-step1' => '../includes/sections/auth/register.php',
    'register-step2' => '../includes/sections/auth/register.php',
    'register-step3' => '../includes/sections/auth/register.php',

    // Nuevas secciones de Configuración
    'settings-profile'       => '../includes/sections/settings/your-profile.php',
    'settings-login'         => '../includes/sections/settings/login-security.php',
    'settings-accessibility' => '../includes/sections/settings/accessibility.php',
];
// --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---

// --- MODIFICACIÓN 2: PROTEGER EL ROUTER ---
// --- ▼▼▼ MODIFICACIÓN: ACTUALIZAR PÁGINAS DE AUTH ▼▼▼ ---
$authPages = ['login', 'register-step1', 'register-step2', 'register-step3', 'reset-password'];
$isAuthPage = in_array($page, $authPages);
// --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---

// --- ▼▼▼ MODIFICACIÓN: PROTEGER TAMBIÉN LAS PÁGINAS DE SETTINGS ▼▼▼ ---
// Si pide una página protegida (que no es de auth ni 404) Y NO tiene sesión
$isSettingsPage = strpos($page, 'settings-') === 0;

if (!isset($_SESSION['user_id']) && !$isAuthPage && $page !== '404') {
    // No le damos la página.
    http_response_code(403); // 403 Forbidden
    $CURRENT_SECTION = '404'; 
    include $allowedPages['404'];
    exit; // Detener script
}
// --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---


if (array_key_exists($page, $allowedPages)) {

    // --- ▼▼▼ INICIO DE LA LÓGICA DE VALIDACIÓN DE PASOS DE REGISTRO (MODIFICADA) ▼▼▼ ---
    
    $CURRENT_REGISTER_STEP = 1; // Default
    $initialCooldown = 0; // <-- MODIFICACIÓN: Añadir valor por defecto

    if ($page === 'register-step1') {
        $CURRENT_REGISTER_STEP = 1;
        // Si el usuario vuelve al paso 1, se reinicia el proceso
        unset($_SESSION['registration_step']);
        unset($_SESSION['registration_email']); // <-- MODIFICACIÓN: Limpiar email
        // También limpiamos el sessionStorage del cliente para evitar conflictos
        echo '<script>
                try {
                    sessionStorage.removeItem("regEmail");
                    sessionStorage.removeItem("regPass");
                } catch (e) {
                    console.warn("No se pudo limpiar sessionStorage.");
                }
              </script>';

    } elseif ($page === 'register-step2') {
        // Comprobar si tiene permiso para estar en el paso 2
        if (!isset($_SESSION['registration_step']) || $_SESSION['registration_step'] < 2) {
            // No tiene permiso, mostrar error y salir
            showRegistrationError(
                $basePath,
                'Route Error (400 Missing step 1 data):',
                'No has completado el paso 1 (email y contraseña) antes de acceder a esta página.'
            );
            exit; // Detener la ejecución
        }
        $CURRENT_REGISTER_STEP = 2;

    } elseif ($page === 'register-step3') {
        // Comprobar si tiene permiso para estar en el paso 3
        if (!isset($_SESSION['registration_step']) || $_SESSION['registration_step'] < 3) {
            // No tiene permiso, mostrar error y salir
            showRegistrationError(
                $basePath,
                'Route Error (400 Missing step 2 data):',
                'No has completado el paso 2 (nombre de usuario) antes de acceder a esta página.'
            );
            exit; // Detener la ejecución
        }
        $CURRENT_REGISTER_STEP = 3;

        // --- ▼▼▼ INICIO DE NUEVA LÓGICA DE COOLDOWN ▼▼▼ ---
        if (isset($_SESSION['registration_email'])) {
            try {
                $email = $_SESSION['registration_email'];
                $stmt = $pdo->prepare(
                    "SELECT created_at FROM verification_codes 
                     WHERE identifier = ? AND code_type = 'registration' 
                     ORDER BY created_at DESC LIMIT 1"
                );
                $stmt->execute([$email]);
                $codeData = $stmt->fetch();

                if ($codeData) {
                    // Ambas fechas están en UTC (gracias a config.php)
                    $lastCodeTime = new DateTime($codeData['created_at'], new DateTimeZone('UTC'));
                    $currentTime = new DateTime('now', new DateTimeZone('UTC'));
                    
                    $secondsPassed = $currentTime->getTimestamp() - $lastCodeTime->getTimestamp();
                    $cooldownConstant = 60; // Debe coincidir con CODE_RESEND_COOLDOWN_SECONDS

                    if ($secondsPassed < $cooldownConstant) {
                        $initialCooldown = $cooldownConstant - $secondsPassed;
                    }
                    // Si $secondsPassed >= 60, $initialCooldown se queda en 0 (default)
                }
            } catch (PDOException $e) {
                // Si falla la BD, no iniciar el timer por precaución
                logDatabaseError($e, 'router - register-step3-cooldown');
                $initialCooldown = 0; 
            }
        }
        // --- ▲▲▲ FIN DE NUEVA LÓGICA DE COOLDOWN ▲▲▲ ---
    }
    // --- ▲▲▲ FIN DE LA LÓGICA DE VALIDACIÓN ▲▲▲ ---


    // --- ▼▼▼ INICIO DE LA LÓGICA MOVIDA (Y MODIFICADA) ▼▼▼ ---
    // Pre-procesamos las variables solo para la página que las necesita.
    if ($page === 'settings-profile') {
        
        $defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";
        $profileImageUrl = $_SESSION['profile_image_url'] ?? $defaultAvatar;
        if (empty($profileImageUrl)) {
            $profileImageUrl = $defaultAvatar;
        }

        // Comprobar si la URL es un avatar por defecto (generado) o uno subido
        // (La protección de ruta anterior ya asegura que $_SESSION['user_id'] existe)
        $isDefaultAvatar = strpos($profileImageUrl, 'ui-avatars.com') !== false || 
                           strpos($profileImageUrl, 'user-' . $_SESSION['user_id'] . '.png') !== false;

        $usernameForAlt = $_SESSION['username'] ?? 'Usuario';
        $userRole = $_SESSION['role'] ?? 'user';
        $userEmail = $_SESSION['email'] ?? 'correo@ejemplo.com';
        
        // --- ▼▼▼ INICIO DE NUEVA LÓGICA (CARGAR PREFERENCIAS) ▼▼▼ ---
        try {
            $stmt_prefs = $pdo->prepare("SELECT * FROM user_preferences WHERE user_id = ?");
            $stmt_prefs->execute([$_SESSION['user_id']]);
            $userPreferences = $stmt_prefs->fetch(PDO::FETCH_ASSOC);

            // Definir fallbacks por si la fila no existe (ej. usuario antiguo)
            $userLanguage = $userPreferences['language'] ?? 'en-us';
            // --- ▼▼▼ ¡LÍNEA ELIMINADA! ▼▼▼ ---
            // $userTheme = $userPreferences['theme'] ?? 'system'; 
            $userUsageType = $userPreferences['usage_type'] ?? 'personal';

        } catch (PDOException $e) {
            logDatabaseError($e, 'router - settings-profile - preferences');
            // Fallbacks en caso de error de BD
            $userLanguage = 'en-us';
            // --- ▼▼▼ ¡LÍNEA ELIMINADA! ▼▼▼ ---
            // $userTheme = 'system';
            $userUsageType = 'personal';
        }
        // --- ▲▲▲ FIN DE NUEVA LÓGICA (CARGAR PREFERENCIAS) ▲▲▲ ---
        

    // --- ▼▼▼ INICIO DE LA NUEVA LÓGICA (settings-login) ▼▼▼ ---
    } elseif ($page === 'settings-login') {
        
        // --- ▼▼▼ ¡INICIO DE LA MODIFICACIÓN! ▼▼▼ ---
        try {
            // 1. Consultar el último log de cambio de contraseña
            $stmt_pass_log = $pdo->prepare(
                "SELECT changed_at FROM user_audit_logs 
                 WHERE user_id = ? AND change_type = 'password' 
                 ORDER BY changed_at DESC LIMIT 1"
            );
            $stmt_pass_log->execute([$_SESSION['user_id']]);
            $lastLog = $stmt_pass_log->fetch();

            if ($lastLog) {
                // 2. Formatear la fecha
                // Comprobar si la extensión 'intl' está cargada
                if (!class_exists('IntlDateFormatter')) {
                     // Fallback simple si 'intl' no está
                    $date = new DateTime($lastLog['changed_at']);
                    $lastPasswordUpdateText = 'Última actualización: ' . $date->format('d/m/Y');
                } else {
                    // Formato localizado (ej: 30 de septiembre de 2024)
                    $formatter = new IntlDateFormatter(
                        'es_ES', // Locale español
                        IntlDateFormatter::LONG, // Formato de fecha (largo)
                        IntlDateFormatter::NONE, // Formato de hora (ninguno)
                        'UTC' // Zona horaria (la BD guarda en UTC)
                    );
                    $timestamp = strtotime($lastLog['changed_at']);
                    $lastPasswordUpdateText = 'Última actualización: ' . $formatter->format($timestamp);
                }
            } else {
                // 3. Mensaje por defecto si no hay logs
                $lastPasswordUpdateText = 'Nunca se ha actualizado la contraseña.';
            }

            // 4. Obtener el estado de 2FA
            $stmt_2fa = $pdo->prepare("SELECT is_2fa_enabled FROM users WHERE id = ?");
            $stmt_2fa->execute([$_SESSION['user_id']]);
            $is2faEnabled = (int)$stmt_2fa->fetchColumn(); // Cast a int (0 o 1)

        } catch (PDOException $e) {
            // En caso de error de BD (ej. tabla/columna aún no existe), mostrar mensaje genérico
            logDatabaseError($e, 'router - settings-login');
            $lastPasswordUpdateText = 'No se pudo cargar el historial de actualizaciones.';
            $is2faEnabled = 0; // Por defecto 0 si hay error
        }
        // --- ▲▲▲ ¡FIN DE LA MODIFICACIÓN! ▲▲▲ ---
        // --- ▲▲▲ FIN DE LA NUEVA LÓGICA ▲▲▲ ---

    // --- ▼▼▼ ¡INICIO DEL NUEVO BLOQUE! (settings-accessibility) ▼▼▼ ---
    } elseif ($page === 'settings-accessibility') {
        
        // --- ▼▼▼ INICIO DE NUEVA LÓGICA (CARGAR PREFERENCIAS DE TEMA) ▼▼▼ ---
        try {
            $stmt_prefs = $pdo->prepare("SELECT theme FROM user_preferences WHERE user_id = ?");
            $stmt_prefs->execute([$_SESSION['user_id']]);
            $userTheme = $stmt_prefs->fetchColumn(); 

            if ($userTheme === false) { 
                $userTheme = 'system'; 
            }

        } catch (PDOException $e) {
            logDatabaseError($e, 'router - settings-accessibility - preferences');
            // Fallbacks en caso de error de BD
            $userTheme = 'system';
        }
        // --- ▲▲▲ FIN DE NUEVA LÓGICA (CARGAR PREFERENCIAS DE TEMA) ▲▲▲ ---

    }
    // --- ▲▲▲ FIN DE LA LÓGICA MOVIDA ▲▲▲ ---

    include $allowedPages[$page];

} else {
    http_response_code(404);
    $CURRENT_SECTION = '404'; 
    include $allowedPages['404']; 
}
?>
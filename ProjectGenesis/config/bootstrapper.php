<?php
// FILE: config/bootstrapper.php

// Carga la configuración base (BD, sesiones, etc.)
include 'config/config.php';

// Asegura que el token CSRF exista
getCsrfToken(); 

// --- ▼▼▼ INICIO DE MODIFICACIÓN (MODO MANTENIMIENTO) ▼▼▼ ---
// Cargar configuraciones globales del sitio
$GLOBALS['site_settings'] = [];
try {
    // Asumimos que la tabla se llamará 'site_settings'
    $stmt_settings = $pdo->prepare("SELECT setting_key, setting_value FROM site_settings");
    $stmt_settings->execute();
    while ($row = $stmt_settings->fetch()) {
        $GLOBALS['site_settings'][$row['setting_key']] = $row['setting_value'];
    }
    
    // --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
    // Asegurar un fallback si las claves no existen en la BD
    if (!isset($GLOBALS['site_settings']['maintenance_mode'])) {
         $GLOBALS['site_settings']['maintenance_mode'] = '0';
    }
    if (!isset($GLOBALS['site_settings']['allow_new_registrations'])) {
         $GLOBALS['site_settings']['allow_new_registrations'] = '1';
    }
    if (!isset($GLOBALS['site_settings']['username_cooldown_days'])) {
         $GLOBALS['site_settings']['username_cooldown_days'] = '30';
    }
    if (!isset($GLOBALS['site_settings']['email_cooldown_days'])) {
         $GLOBALS['site_settings']['email_cooldown_days'] = '12';
    }
    if (!isset($GLOBALS['site_settings']['avatar_max_size_mb'])) {
         $GLOBALS['site_settings']['avatar_max_size_mb'] = '2';
    }
    // --- ▼▼▼ NUEVAS CLAVES AÑADIDAS ▼▼▼ ---
    if (!isset($GLOBALS['site_settings']['max_login_attempts'])) {
         $GLOBALS['site_settings']['max_login_attempts'] = '5';
    }
    if (!isset($GLOBALS['site_settings']['lockout_time_minutes'])) {
         $GLOBALS['site_settings']['lockout_time_minutes'] = '5';
    }
    if (!isset($GLOBALS['site_settings']['allowed_email_domains'])) {
         $GLOBALS['site_settings']['allowed_email_domains'] = 'gmail.com\noutlook.com\nhotmail.com\nyahoo.com\nicloud.com';
    }
    if (!isset($GLOBALS['site_settings']['min_password_length'])) {
         $GLOBALS['site_settings']['min_password_length'] = '8';
    }
    // --- ▲▲▲ FIN DE NUEVAS CLAVES ▲▲▲ ---
    // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
    
} catch (PDOException $e) {
    // Si la tabla no existe o falla, asumimos valores seguros
    logDatabaseError($e, 'bootstrapper - load site_settings');
    // --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
    $GLOBALS['site_settings']['maintenance_mode'] = '0'; // Fallback seguro
    $GLOBALS['site_settings']['allow_new_registrations'] = '1'; // Fallback seguro
    $GLOBALS['site_settings']['username_cooldown_days'] = '30'; // Fallback seguro
    $GLOBALS['site_settings']['email_cooldown_days'] = '12'; // Fallback seguro
    $GLOBALS['site_settings']['avatar_max_size_mb'] = '2'; // Fallback seguro
    // --- ▼▼▼ NUEVAS CLAVES AÑADIDAS ▼▼▼ ---
    $GLOBALS['site_settings']['max_login_attempts'] = '5'; // Fallback seguro
    $GLOBALS['site_settings']['lockout_time_minutes'] = '5'; // Fallback seguro
    $GLOBALS['site_settings']['allowed_email_domains'] = 'gmail.com\noutlook.com'; // Fallback seguro
    $GLOBALS['site_settings']['min_password_length'] = '8'; // Fallback seguro
    // --- ▲▲▲ FIN DE NUEVAS CLAVES ▲▲▲ ---
    // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
}
// --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---


// Refresca los datos del usuario desde la BD en cada carga de página
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT username, email, profile_image_url, role, auth_token, account_status FROM users WHERE id = ?");
        
        $stmt->execute([$_SESSION['user_id']]);
        $freshUserData = $stmt->fetch();

        if ($freshUserData) {
            
            // Comprobar si la cuenta está suspendida o eliminada
            $accountStatus = $freshUserData['account_status'];
            if ($accountStatus === 'suspended' || $accountStatus === 'deleted') {
                session_unset();
                session_destroy();
                
                $statusPath = ($accountStatus === 'suspended') ? '/account-status/suspended' : '/account-status/deleted';
                header('Location: ' . $basePath . $statusPath);
                exit;
            }

            // Validar el token de autenticación (para "Cerrar sesión en todos los dispositivos")
            $dbAuthToken = $freshUserData['auth_token'];
            $sessionAuthToken = $_SESSION['auth_token'] ?? null;

            if (empty($sessionAuthToken) || empty($dbAuthToken) || !hash_equals($dbAuthToken, $sessionAuthToken)) {
                session_unset();
                session_destroy();
                header('Location: ' . $basePath . '/login');
                exit;
            }

            // Refrescar los datos principales de la sesión
            $_SESSION['username'] = $freshUserData['username'];
            $_SESSION['email'] = $freshUserData['email'];
            $_SESSION['profile_image_url'] = $freshUserData['profile_image_url'];
            $_SESSION['role'] = $freshUserData['role']; 
            
            
            // Refrescar las preferencias del usuario
            $stmt_prefs = $pdo->prepare("SELECT * FROM user_preferences WHERE user_id = ?");
            $stmt_prefs->execute([$_SESSION['user_id']]);
            $prefs = $stmt_prefs->fetch();

            if ($prefs) {
                $_SESSION['language'] = $prefs['language'];
                $_SESSION['theme'] = $prefs['theme'];
                $_SESSION['usage_type'] = $prefs['usage_type'];
                $_SESSION['open_links_in_new_tab'] = (int)$prefs['open_links_in_new_tab'];
                $_SESSION['increase_message_duration'] = (int)$prefs['increase_message_duration'];
            } else {
                // Valores por defecto si no hay preferencias
                $_SESSION['language'] = 'en-us';
                $_SESSION['theme'] = 'system';
                $_SESSION['usage_type'] = 'personal';
                $_SESSION['open_links_in_new_tab'] = 1;
                $_SESSION['increase_message_duration'] = 0;
            }

        } else {
            // Si el ID de usuario en sesión no existe en la BD, destruir sesión
            session_unset();
            session_destroy();
            header('Location: ' . $basePath . '/login');
            exit;
        }
    } catch (PDOException $e) {
        // Log del error pero no detener la app (config.php podría mostrar error de BD)
        logDatabaseError($e, 'bootstrapper - refresh session'); 
    }
}
?>
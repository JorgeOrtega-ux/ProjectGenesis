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
    if (!isset($GLOBALS['site_settings']['max_password_length'])) {
         $GLOBALS['site_settings']['max_password_length'] = '72';
    }
    // --- ▼▼▼ MODIFICACIÓN: Claves añadidas ▼▼▼ ---
    if (!isset($GLOBALS['site_settings']['min_username_length'])) {
         $GLOBALS['site_settings']['min_username_length'] = '6';
    }
    if (!isset($GLOBALS['site_settings']['max_username_length'])) {
         $GLOBALS['site_settings']['max_username_length'] = '32';
    }
    if (!isset($GLOBALS['site_settings']['max_email_length'])) {
         $GLOBALS['site_settings']['max_email_length'] = '255';
    }
    if (!isset($GLOBALS['site_settings']['code_resend_cooldown_seconds'])) {
         $GLOBALS['site_settings']['code_resend_cooldown_seconds'] = '60';
    }
    // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
    // --- ▲▲▲ FIN DE NUEVAS CLAVES ▲▲▲ ---
    
    // --- ▼▼▼ NUEVA CLAVE AÑADIDA ▼▼▼ ---
    if (!isset($GLOBALS['site_settings']['max_concurrent_users'])) {
         $GLOBALS['site_settings']['max_concurrent_users'] = '500'; // Fallback
    }
    // --- ▲▲▲ FIN DE NUEVA CLAVE ▲▲▲ ---

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
    $GLOBALS['site_settings']['max_password_length'] = '72'; // Fallback seguro
    // --- ▼▼▼ MODIFICACIÓN: Claves añadidas ▼▼▼ ---
    $GLOBALS['site_settings']['min_username_length'] = '6'; // Fallback seguro
    $GLOBALS['site_settings']['max_username_length'] = '32'; // Fallback seguro
    $GLOBALS['site_settings']['max_email_length'] = '255'; // Fallback seguro
    $GLOBALS['site_settings']['code_resend_cooldown_seconds'] = '60'; // Fallback seguro
    // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
    // --- ▲▲▲ FIN DE NUEVAS CLAVES ▲▲▲ ---
    
    // --- ▼▼▼ NUEVA CLAVE AÑADIDA ▼▼▼ ---
    $GLOBALS['site_settings']['max_concurrent_users'] = '500'; // Fallback seguro
    // --- ▲▲▲ FIN DE NUEVA CLAVE ▲▲▲ ---

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

            // --- ▼▼▼ INICIO DE NUEVO BLOQUE DE VALIDACIÓN (Paso 3) ▼▼▼ ---
            // Validar que esta sesión individual (metadata_id) esté activa
            if (isset($_SESSION['metadata_id'])) {
                $stmt_meta = $pdo->prepare("SELECT is_active FROM user_metadata WHERE id = ? AND user_id = ?");
                $stmt_meta->execute([$_SESSION['metadata_id'], $_SESSION['user_id']]);
                $sessionMeta = $stmt_meta->fetch();

                if (!$sessionMeta || $sessionMeta['is_active'] == 0) {
                    // Esta sesión específica fue cerrada desde otro dispositivo
                    session_unset();
                    session_destroy();
                    header('Location: ' . $basePath . '/login');
                    exit;
                }
            } else {
                // Es una sesión antigua (creada antes de esta actualización)
                // Forzar un nuevo inicio de sesión para que se genere un metadata_id
                session_unset();
                session_destroy();
                header('Location: ' . $basePath . '/login');
                exit;
            }
            // --- ▲▲▲ FIN DE NUEVO BLOQUE DE VALIDACIÓN ▲▲▲ ---

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

// --- INICIO DE LÓGICA UNIFICADA (antes en includes/router-guard.php) ---
// (CORREGIDO Y MODIFICADO)

// $basePath y $pdo (para rol de admin) se definieron en bootstrapper.php
// $GLOBALS['site_settings'] se definió en bootstrapper.php

// --- ▼▼▼ INICIO DE BLOQUE REORDENADO (PASO 1) ▼▼▼ ---
// 1. Analizar la URL PRIMERO
$requestUri = $_SERVER['REQUEST_URI'];
$requestPath = strtok($requestUri, '?'); 

$path = str_replace($basePath, '', $requestPath);
if (empty($path) || $path === '/') {
    $path = '/';
}

// 2. Mapa de rutas
$pathsToPages = [
    '/'           => 'home',
    '/explorer'   => 'explorer',
    '/login'      => 'login',
    '/maintenance' => 'maintenance', 
    '/server-full' => 'server-full', // <-- ¡NUEVA LÍNEA!
    
    '/register'                 => 'register-step1',
    '/register/additional-data' => 'register-step2',
    '/register/verification-code' => 'register-step3',
    
    '/reset-password'          => 'reset-step1',
    '/reset-password/verify-code'  => 'reset-step2',
    '/reset-password/new-password' => 'reset-step3',
    
    '/settings'                 => 'settings-profile', 
    '/settings/your-profile'    => 'settings-profile',
    '/settings/login-security'  => 'settings-login',
    '/settings/accessibility'   => 'settings-accessibility',
    '/settings/device-sessions' => 'settings-devices', 
    
    '/settings/change-password' => 'settings-change-password',
    '/settings/change-email'    => 'settings-change-email',
    '/settings/toggle-2fa'      => 'settings-toggle-2fa',
    '/settings/delete-account'  => 'settings-delete-account',
    
    '/account-status/deleted'   => 'account-status-deleted',
    '/account-status/suspended' => 'account-status-suspended',
    
    '/admin'                    => 'admin-dashboard',
    '/admin/dashboard'          => 'admin-dashboard',
    '/admin/manage-users'       => 'admin-manage-users', 
    '/admin/create-user'        => 'admin-create-user', 
    '/admin/edit-user'          => 'admin-edit-user', 
    '/admin/server-settings'    => 'admin-server-settings', 
    
    // --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
    '/admin/manage-backups'     => 'admin-manage-backups',
    '/admin/restore-backup'     => 'admin-restore-backup', // <-- ¡AÑADIDA!
    '/admin/manage-logs'        => 'admin-manage-logs', // <-- ¡NUEVA LÍNEA AÑADIDA!
    // --- ▲▲▲ FIN DE MODIFICACIÓN ▼▼▼ ---

    // --- ▼▼▼ INICIO DE NUEVAS RUTAS (HELP) ▼▼▼ ---
    '/help/legal-notice'      => 'help-legal-notice',
    '/help/privacy-policy'    => 'help-privacy-policy',
    '/help/cookies-policy'    => 'help-cookies-policy',
    '/help/terms-conditions'  => 'help-terms-conditions',
    '/help/send-feedback'     => 'help-send-feedback',
    // --- ▲▲▲ FIN DE NUEVAS RUTAS (HELP) ▲▲▲ ---
];

// 3. Determinar la página actual y los tipos de página
$currentPage = $pathsToPages[$path] ?? '404';

// --- ▼▼▼ MODIFICACIÓN: Añadir 'server-full' y páginas de ayuda públicas a $authPages ▼▼▼ ---
$authPages = [
    'login', 
    'maintenance', 
    'server-full',
    'help-legal-notice',
    'help-privacy-policy',
    'help-cookies-policy',
    'help-terms-conditions'
]; 
$isAuthPage = in_array($currentPage, $authPages) || 
              strpos($currentPage, 'register-') === 0 ||
              strpos($currentPage, 'reset-') === 0 ||
              strpos($currentPage, 'account-status-') === 0; 
// --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

$isSettingsPage = strpos($currentPage, 'settings-') === 0;
$isAdminPage = strpos($currentPage, 'admin-') === 0;
$isHelpPage = strpos($currentPage, 'help-') === 0; // <-- ¡NUEVA LÍNEA!
// --- ▲▲▲ FIN DE BLOQUE REORDENADO ▲▲▲ ---


// --- ▼▼▼ INICIO DE MODIFICACIÓN (MODO MANTENIMIENTO) (PASO 2) ▼▼▼ ---
$maintenanceMode = $GLOBALS['site_settings']['maintenance_mode'] ?? '0';
$userRole = $_SESSION['role'] ?? 'user'; // Asumir 'user' si no está logueado
$isPrivilegedUser = in_array($userRole, ['moderator', 'administrator', 'founder']);

// Definir variables de contexto basadas en $currentPage (que ya está definida)
$isMaintenancePage = ($currentPage === 'maintenance');
$isLoginPage = ($currentPage === 'login');
$isApiCall = (strpos($requestUri, '/api/') !== false);
$isConfigCall = (strpos($requestUri, '/config/') !== false); // Permitir logout
$isServerFullPage = ($currentPage === 'server-full');


// Si el modo mantenimiento está ACTIVO
if ($maintenanceMode === '1') {

    // --- ▼▼▼ ¡INICIO DE LA LÓGICA DE REDIRECCIÓN CORREGIDA! ▼▼▼ ---

    // CASO 1: Usuario NO logueado
    if (!isset($_SESSION['user_id'])) {
        // Si no está logueado, CUALQUIER página que no sea de login, mantenimiento,
        // API, config, O AYUDA, debe redirigir a /login.
        if (!$isLoginPage && !$isMaintenancePage && !$isApiCall && !$isConfigCall && !$isHelpPage) {
             header('Location: ' . $basePath . '/login');
             exit;
        }
    } 
    // CASO 2: Usuario SÍ logueado
    else {
        // El usuario está logueado, aplicar la lógica de staff
        // Si NO es staff Y NO está en una página de "gracia" -> redirigir a /maintenance
        if (!$isPrivilegedUser && !$isMaintenancePage && !$isLoginPage && !$isApiCall && !$isConfigCall && !$isServerFullPage && !$isHelpPage) {
            header('Location: ' . $basePath . '/maintenance');
            exit;
        }
    }
    // --- ▲▲▲ FIN DE LA LÓGICA DE REDIRECCIÓN CORREGIDA ▲▲▲ ---
}
// --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---


// 4. Lógica de Autorización y Redirecciones
if ($isAdminPage && isset($_SESSION['user_id'])) {
    $userRole = $_SESSION['role'] ?? 'user';
    if ($userRole !== 'administrator' && $userRole !== 'founder') {
        $isAdminPage = false; // No es un admin
        $currentPage = '404'; // Tratar la página como 404
    }
    
    // --- ▼▼▼ INICIO DE MODIFICACIÓN (SEGURIDAD DE BACKUPS) ▼▼▼ ---
    // Solo los 'founder' pueden ver las páginas de backups y logs
    if (($currentPage === 'admin-manage-backups' || $currentPage === 'admin-restore-backup' || $currentPage === 'admin-manage-logs') && $userRole !== 'founder') {
        $isAdminPage = true; // Sigue siendo admin, pero...
        $currentPage = '404'; // No tiene permiso para esta página específica
    }
    // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

}

// Redirigir a login si no está logueado y no es página de auth
if (!isset($_SESSION['user_id']) && !$isAuthPage) {
    header('Location: ' . $basePath . '/login');
    exit;
}
// Redirigir a home si está logueado e intenta ir a auth
// --- ▼▼▼ MODIFICACIÓN: Añadir 'server-full' y las páginas de ayuda a la exclusión ▼▼▼ ---
if (isset($_SESSION['user_id']) && 
    $isAuthPage && 
    $currentPage !== 'maintenance' && 
    $currentPage !== 'server-full' &&
    $currentPage !== 'help-legal-notice' &&
    $currentPage !== 'help-privacy-policy' &&
    $currentPage !== 'help-cookies-policy' &&
    $currentPage !== 'help-terms-conditions'
) { 
    if ($maintenanceMode !== '1') {
         header('Location: ' . $basePath . '/');
         exit;
    }
}
// --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

// Redirecciones de conveniencia
if ($path === '/settings') {
    header('Location: ' . $basePath . '/settings/your-profile');
    exit;
}

// --- ▼▼▼ ¡AQUÍ ESTÁ LA CORRECCIÓN DEL ERROR DE SINTAXIS! ▼▼▼ ---
if ($path === '/admin') {
    header('Location: ' . $basePath . '/admin/dashboard'); // Se quitó la 'S'
    exit;
}
// --- ▲▲▲ FIN DE LA CORRECCIÓN ▲▲▲ ---

// Las variables $currentPage, $isAuthPage, $isSettingsPage, $isAdminPage, $isHelpPage
// están ahora disponibles globalmente para los scripts que se incluyan después.
?>
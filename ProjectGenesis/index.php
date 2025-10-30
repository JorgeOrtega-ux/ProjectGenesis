<?php
// /ProjectGenesis/index.php

// --- MODIFICACIÓN 1: INCLUIR CONFIG ---
include 'config/config.php';

// --- ¡NUEVA MODIFICACIÓN! GENERAR TOKEN CSRF ---
getCsrfToken(); 

// --- ¡NUEVA MODIFICACIÓN! ACTUALIZAR DATOS DE SESIÓN EN CADA CARGA ---
// --- ▼▼▼ INICIO DE LA MODIFICACIÓN (MANEJO DE ERROR DE BD) ▼▼▼ ---
// Solo intentar refrescar la sesión si el usuario ha iniciado sesión
if (isset($_SESSION['user_id'])) {
// --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
    try {
        // 1. OBTENER DATOS BÁSICOS DEL USUARIO
        // --- ▼▼▼ INICIO DE LA MODIFICACIÓN (SE AÑADIÓ 'account_status') ▼▼▼ ---
        $stmt = $pdo->prepare("SELECT username, email, profile_image_url, role, auth_token, account_status FROM users WHERE id = ?");
        // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
        
        $stmt->execute([$_SESSION['user_id']]);
        $freshUserData = $stmt->fetch();

        if ($freshUserData) {
            
            // --- ▼▼▼ INICIO DE LA MODIFICACIÓN (VALIDACIÓN DE ESTADO) ▼▼▼ ---
            // 1.b. VALIDACIÓN DE ESTADO DE CUENTA
            $accountStatus = $freshUserData['account_status'];
            if ($accountStatus === 'suspended' || $accountStatus === 'deleted') {
                // El estado no es 'active'. Destruir la sesión.
                session_unset();
                session_destroy();
                
                // Redirigir a la página de estado apropiada
                $statusPath = ($accountStatus === 'suspended') ? '/account-status/suspended' : '/account-status/deleted';
                header('Location: ' . $basePath . $statusPath);
                exit;
            }
            // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---


            // 2. VALIDACIÓN DE AUTH_TOKEN
            $dbAuthToken = $freshUserData['auth_token'];
            $sessionAuthToken = $_SESSION['auth_token'] ?? null;

            if (empty($sessionAuthToken) || empty($dbAuthToken) || !hash_equals($dbAuthToken, $sessionAuthToken)) {
                session_unset();
                session_destroy();
                header('Location: ' . $basePath . '/login');
                exit;
            }

            // 3. ACTUALIZAR SESIÓN BÁSICA
            $_SESSION['username'] = $freshUserData['username'];
            $_SESSION['email'] = $freshUserData['email'];
            $_SESSION['profile_image_url'] = $freshUserData['profile_image_url'];
            $_SESSION['role'] = $freshUserData['role']; 
            
            // --- ▼▼▼ ¡INICIO DE MODIFICACIÓN: CARGAR PREFERENCIAS! ▼▼▼ ---
            
            // 4. OBTENER PREFERENCIAS DEL USUARIO
            $stmt_prefs = $pdo->prepare("SELECT * FROM user_preferences WHERE user_id = ?");
            $stmt_prefs->execute([$_SESSION['user_id']]);
            $prefs = $stmt_prefs->fetch();

            if ($prefs) {
                // Si se encuentran, se guardan en la sesión
                $_SESSION['language'] = $prefs['language'];
                $_SESSION['theme'] = $prefs['theme'];
                $_SESSION['usage_type'] = $prefs['usage_type'];
                $_SESSION['open_links_in_new_tab'] = (int)$prefs['open_links_in_new_tab'];
                $_SESSION['increase_message_duration'] = (int)$prefs['increase_message_duration'];
            } else {
                // Si no (ej. usuario nuevo que falló en el registro), usar defaults
                $_SESSION['language'] = 'en-us';
                $_SESSION['theme'] = 'system';
                $_SESSION['usage_type'] = 'personal';
                $_SESSION['open_links_in_new_tab'] = 1;
                $_SESSION['increase_message_duration'] = 0;
            }
            // --- ▲▲▲ ¡FIN DE MODIFICACIÓN! ▲▲▲ ---

        } else {
            session_unset();
            session_destroy();
            header('Location: ' . $basePath . '/login');
            exit;
        }
    } catch (PDOException $e) {
        // Error al refrescar sesión
        logDatabaseError($e, 'index - refresh session'); // Añadido log
    }
// --- ▼▼▼ INICIO DE LA MODIFICACIÓN (MANEJO DE ERROR DE BD) ▼▼▼ ---
}
// Se eliminó el bloque 'elseif ($pdo === null)'
// --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
// --- FIN DE LA NUEVA MODIFICACIÓN ---


// 1. Definir el base path (ya viene de config.php)

// 2. Obtener la ruta de la URL
$requestUri = $_SERVER['REQUEST_URI'];
$requestPath = strtok($requestUri, '?'); 

// 3. Limpiar la ruta
$path = str_replace($basePath, '', $requestPath);
if (empty($path) || $path === '/') {
    $path = '/';
}

// 4. Replicar la lógica de rutas
// --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
$pathsToPages = [
    '/'           => 'home',
    '/explorer'   => 'explorer',
    '/login'      => 'login',
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
    
    // ▼▼▼ ¡AQUÍ ESTÁ LA CORRECCIÓN! ▼▼▼
    '/settings/change-password' => 'settings-change-password',
    '/settings/change-email'    => 'settings-change-email',
    '/settings/toggle-2fa'      => 'settings-toggle-2fa',
    '/settings/delete-account'  => 'settings-delete-account',
    // ▲▲▲ ¡FIN DE LA CORRECCIÓN! ▲▲▲
    
    '/account-status/deleted'   => 'account-status-deleted',
    '/account-status/suspended' => 'account-status-suspended',
];
// --- ▲▲▲ FIN DE MODIFICACIÓN ▼▼▼ ---

$currentPage = $pathsToPages[$path] ?? '404';

// 5. Definir qué páginas NO DEBEN mostrar el header/menu
$authPages = ['login'];
// --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
$isAuthPage = in_array($currentPage, $authPages) || 
              strpos($currentPage, 'register-') === 0 ||
              strpos($currentPage, 'reset-') === 0 ||
              strpos($currentPage, 'account-status-') === 0; // <-- AÑADIR ESTO
// --- ▲▲▲ FIN DE MODIFICACIÓN ▼▼▼ ---

$isSettingsPage = strpos($currentPage, 'settings-') === 0;

// 6. LÓGICA DE PROTECCIÓN DE RUTAS
// --- ▼▼▼ INICIO DE MODIFICACIÓN (MANEJO DE ERROR DE BD) ▼▼▼ ---
// Se eliminaron las comprobaciones de $pdo y $pdo !== null.
// Ahora solo queda la lógica de protección de ruta estándar.
if (!isset($_SESSION['user_id']) && !$isAuthPage) {
    header('Location: ' . $basePath . '/login');
    exit;
}
if (isset($_SESSION['user_id']) && $isAuthPage) {
    header('Location: ' . $basePath . '/');
    exit;
}
// --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---

if ($path === '/settings') {
    header('Location: ' . $basePath . '/settings/your-profile');
    exit;
}

// --- ▼▼▼ ¡INICIO DE MODIFICACIÓN: LÓGICA DE TEMA PARA HTML! ▼▼▼ ---
$themeClass = '';
if (isset($_SESSION['theme'])) {
    if ($_SESSION['theme'] === 'light') {
        $themeClass = 'light-theme';
    } elseif ($_SESSION['theme'] === 'dark') {
        $themeClass = 'dark-theme';
    }
    // Si es 'system', la clase se deja vacía y el JS (app-init.js) se encargará
}
// --- ▲▲▲ ¡FIN DE MODIFICACIÓN! ▲▲▲ ---

// --- ▼▼▼ ¡INICIO DE MODIFICACIÓN: LÓGICA DE IDIOMA PARA HTML! ▼▼▼ ---
// Mapea el código de idioma de la BD a un código estándar HTML
$langMap = [
    'es-latam' => 'es-419',
    'es-mx' => 'es-MX',
    'en-us' => 'en-US',
    'fr-fr' => 'fr-FR'
];

// --- ¡ESTA ES LA LÍNEA CORREGIDA! ---
// 1. Primero, obtenemos el idioma de la sesión (o 'en-us' por defecto si no existe).
$currentLang = $_SESSION['language'] ?? 'en-us'; 

// 2. Luego, usamos esa variable (que ahora sabemos que SÍ existe) para buscar en el map.
$htmlLang = $langMap[$currentLang] ?? 'en'; // Default 'en'
// --- ▲▲▲ ¡FIN DE MODIFICACIÓN! ▲▲▲ ---

// --- ▼▼▼ INICIO: MODIFICACIÓN PASO 2 (Bloque 1) ▼▼▼ ---
// 1. Definir un idioma por defecto como fallback
$jsLanguage = 'en-us'; 

if (isset($_SESSION['language'])) {
    // 2. Si el usuario ESTÁ logueado, usar el idioma de su sesión
    $jsLanguage = $_SESSION['language'];
} else {
    // 3. Si NO está logueado, detectar el idioma del navegador
    //    Usamos la función que acabamos de mover a config.php
    $browserLang = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'en-us';
    $jsLanguage = getPreferredLanguage($browserLang); 
}
// --- ▲▲▲ FIN: MODIFICACIÓN PASO 2 (Bloque 1) ▲▲▲ ---
?>
<!DOCTYPE html>
<html lang="<?php echo $htmlLang; ?>" class="<?php echo $themeClass; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded">
    <link rel="stylesheet" type="text/css" href="<?php echo $basePath; ?>/assets/css/styles.css">
    <title>ProjectGenesis</title>
</head>

<body>

    <div class="page-wrapper">
        <div class="main-content">
            <div class="general-content">
                
                <?php if (!$isAuthPage): ?>
                <div class="general-content-top">
                    <?php include 'includes/layouts/header.php'; ?>
                </div>
                <?php endif; ?>
                
                <div class="general-content-bottom">
                    
                    <?php if (!$isAuthPage): ?>
                    <?php 
                    include 'includes/modules/module-surface.php'; 
                    ?>
                    <?php endif; ?>

                    <div class="general-content-scrolleable overflow-y">
                        
                        <div class="page-loader" id="page-loader">
                            <div class="spinner"></div>
                        </div>
                        <div class="main-sections">
                            </div>
                    
                </div>
            </div>
            
            <div id="alert-container"></div>
            </div>
    </div>

    <script>
        // Definir variables globales de JS
        window.projectBasePath = '<?php echo $basePath; ?>';
        window.csrfToken = '<?php echo $_SESSION['csrf_token'] ?? ''; ?>'; // Añadido ?? '' por si acaso
        
        // --- ▼▼▼ ¡INICIO DE MODIFICACIÓN: INYECTAR PREFERENCIAS! ▼▼▼ ---
        window.userTheme = '<?php echo $_SESSION['theme'] ?? 'system'; ?>';
        window.userIncreaseMessageDuration = <?php echo $_SESSION['increase_message_duration'] ?? 0; ?>;
        
        // --- ▼▼▼ INICIO: MODIFICACIÓN PASO 2 (Bloque 2) ▼▼▼ ---
        // ¡NUEVA MODIFICACIÓN! Inyectar idioma actual (calculado arriba)
        window.userLanguage = '<?php echo $jsLanguage; ?>'; 
        // --- ▲▲▲ FIN: MODIFICACIÓN PASO 2 (Bloque 2) ▲▲▲ ---
        
        // --- ▲▲▲ ¡FIN DE MODIFICACIÓN! ▲▲▲ ---
    </script>
    <script type="module" src="<?php echo $basePath; ?>/assets/js/app-init.js"></script>
    
    </body>

</html>
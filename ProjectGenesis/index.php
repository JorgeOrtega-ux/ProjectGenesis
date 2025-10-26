<?php
// /ProjectGenesis/index.php

// --- MODIFICACIÓN 1: INCLUIR CONFIG ---
include 'config/config.php';

// --- ¡NUEVA MODIFICACIÓN! GENERAR TOKEN CSRF ---
getCsrfToken(); 

// --- ¡NUEVA MODIFICACIÓN! ACTUALIZAR DATOS DE SESIÓN EN CADA CARGA ---
if (isset($_SESSION['user_id'])) {
    try {
        // 1. OBTENER DATOS BÁSICOS DEL USUARIO
        $stmt = $pdo->prepare("SELECT username, email, profile_image_url, role, auth_token FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $freshUserData = $stmt->fetch();

        if ($freshUserData) {
            
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
    }
}
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
    '/settings/device-sessions' => 'settings-devices' // <-- AÑADIDO
];
// --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

$currentPage = $pathsToPages[$path] ?? '404';

// 5. Definir qué páginas NO DEBEN mostrar el header/menu
$authPages = ['login'];
$isAuthPage = in_array($currentPage, $authPages) || 
              strpos($currentPage, 'register-') === 0 ||
              strpos($currentPage, 'reset-') === 0;

$isSettingsPage = strpos($currentPage, 'settings-') === 0;

// 6. LÓGICA DE PROTECCIÓN DE RUTAS
if (!isset($_SESSION['user_id']) && !$isAuthPage) {
    header('Location: ' . $basePath . '/login');
    exit;
}
if (isset($_SESSION['user_id']) && $isAuthPage) {
    header('Location: ' . $basePath . '/');
    exit;
}
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
?>
<!DOCTYPE html>
<html lang="en" class="<?php echo $themeClass; ?>">
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

                    <div class="general-content-scrolleable">
                        <div class="main-sections">
                            </div>
                    
                </div>
            </div>
        </div>
    </div>

    <script>
        // Definir variables globales de JS
        window.projectBasePath = '<?php echo $basePath; ?>';
        window.csrfToken = '<?php echo $_SESSION['csrf_token']; ?>';
        
        // --- ▼▼▼ ¡INICIO DE MODIFICACIÓN: INYECTAR PREFERENCIAS! ▼▼▼ ---
        window.userTheme = '<?php echo $_SESSION['theme'] ?? 'system'; ?>';
        window.userIncreaseMessageDuration = <?php echo $_SESSION['increase_message_duration'] ?? 0; ?>;
        // --- ▲▲▲ ¡FIN DE MODIFICACIÓN! ▲▲▲ ---
    </script>
    <script type="module" src="<?php echo $basePath; ?>/assets/js/app-init.js"></script>
    
    <div id="alert-container"></div>
    </body>

</html>
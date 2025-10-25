<?php
// /ProjectGenesis/index.php

// --- MODIFICACIÓN 1: INCLUIR CONFIG ---
// Incluir config.php ANTES DE CUALQUIER COSA.
// Esto inicia la sesión (session_start()) y conecta a la BD ($pdo).
include 'config/config.php';

// --- ¡NUEVA MODIFICACIÓN! GENERAR TOKEN CSRF ---
// Nos aseguramos de que un token CSRF exista para esta carga de página.
// Las funciones CSRF ahora vienen de config.php
getCsrfToken(); 
// --- FIN DE LA NUEVA MODIFICACIÓN ---


// --- ¡NUEVA MODIFICACIÓN! ACTUALIZAR DATOS DE SESIÓN EN CADA CARGA ---
// Si el usuario ya tiene una sesión iniciada...
if (isset($_SESSION['user_id'])) {
    try {
        // Volvemos a consultar la BD para obtener sus datos más frescos
        // --- ▼▼▼ ¡MODIFICADO! Se añade auth_token a la consulta ▼▼▼ ---
        $stmt = $pdo->prepare("SELECT username, email, profile_image_url, role, auth_token FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $freshUserData = $stmt->fetch();

        if ($freshUserData) {
            
            // --- ▼▼▼ ¡INICIO DE LA VALIDACIÓN DE AUTH_TOKEN! ▼▼▼ ---
            $dbAuthToken = $freshUserData['auth_token'];
            $sessionAuthToken = $_SESSION['auth_token'] ?? null;

            // Si el token de la sesión no existe, o está vacío, o no coincide con el de la BD,
            // es una sesión inválida (ej. se activó "Cerrar sesión en todos").
            if (empty($sessionAuthToken) || empty($dbAuthToken) || !hash_equals($dbAuthToken, $sessionAuthToken)) {
                
                // Forzamos el cierre de sesión
                session_unset();
                session_destroy();
                header('Location: ' . $basePath . '/login');
                exit;
            }
            // --- ▲▲▲ ¡FIN DE LA VALIDACIÓN DE AUTH_TOKEN! ▲▲▲ ---

            // Si la validación pasa, actualizamos la sesión con los datos frescos
            $_SESSION['username'] = $freshUserData['username'];
            $_SESSION['email'] = $freshUserData['email'];
            $_SESSION['profile_image_url'] = $freshUserData['profile_image_url'];
            $_SESSION['role'] = $freshUserData['role']; // <-- El rol se actualiza aquí
            // No es necesario re-setear $_SESSION['auth_token'] porque ya sabemos que es válido
            
        } else {
            // Si el usuario no se encuentra (raro, quizás fue eliminado),
            // forzamos el cierre de sesión.
            session_unset();
            session_destroy();
            header('Location: ' . $basePath . '/login');
            exit;
        }
    } catch (PDOException $e) {
        // Si hay un error de BD, es mejor no hacer nada
        // y seguir con los datos de sesión que ya teníamos.
        // error_log("Error al refrescar sesión: "." . $e->getMessage());
    }
}
// --- FIN DE LA NUEVA MODIFICACIÓN ---


// 1. Definir el base path de tu proyecto
// $basePath = '/ProjectGenesis'; // <- Esta línea ya no es necesaria, viene de config.php

// 2. Obtener la ruta de la URL (sin query string)
$requestUri = $_SERVER['REQUEST_URI'];
$requestPath = strtok($requestUri, '?'); // Elimina query string

// 3. Limpiar la ruta para que coincida con la lógica de JS
$path = str_replace($basePath, '', $requestPath);
if (empty($path) || $path === '/') {
    $path = '/';
}

// 4. Replicar la lógica de rutas para saber la página actual
// --- ▼▼▼ MODIFICACIÓN: AÑADIR RUTAS DE REGISTRO Y SETTINGS ▼▼▼ ---
$pathsToPages = [
    '/'           => 'home',
    '/explorer'   => 'explorer',
    '/login'      => 'login',
    // '/register'   => 'register', // <-- Eliminado
    '/reset-password' => 'reset-password',
    
    // Nuevas rutas de Registro
    '/register'                 => 'register-step1',
    '/register/additional-data' => 'register-step2',
    '/register/verification-code' => 'register-step3',

    // Nuevas rutas de Configuración
    '/settings'                 => 'settings-profile', // Redirige /settings a /settings/your-profile
    '/settings/your-profile'    => 'settings-profile',
    '/settings/login-security'  => 'settings-login',
    '/settings/accessibility'   => 'settings-accessibility'
];
// --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---

$currentPage = $pathsToPages[$path] ?? '404';

// 5. Definir qué páginas NO DEBEN mostrar el header/menu
// --- ▼▼▼ MODIFICACIÓN: ACTUALIZAR DETECCIÓN DE PÁGINAS DE AUTH ▼▼▼ ---
$authPages = ['login', 'reset-password'];
$isAuthPage = in_array($currentPage, $authPages) || strpos($currentPage, 'register-') === 0;
// --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---


// --- ▼▼▼ NUEVA LÓGICA: DETECTAR SI ES PÁGINA DE SETTINGS ▼▼▼ ---
$isSettingsPage = strpos($currentPage, 'settings-') === 0;
// --- ▲▲▲ FIN DE LA NUEVA LÓGICA ▲▲▲ ---


// --- MODIFICACIÓN 2: LÓGICA DE PROTECCIÓN DE RUTAS ---

// A. Si el usuario NO está logueado Y NO está en una página de auth,
//    redirigir forzosamente a /login.
if (!isset($_SESSION['user_id']) && !$isAuthPage) {
    header('Location: ' . $basePath . '/login');
    exit;
}

// B. Si el usuario SÍ está logueado Y trata de visitar login/register,
//    redirigir forzosamente a la página principal (home).
if (isset($_SESSION['user_id']) && $isAuthPage) {
    header('Location: ' . $basePath . '/');
    exit;
}

// --- ▼▼▼ NUEVA LÓGICA: REDIRIGIR /settings A /settings/your-profile ▼▼▼ ---
// Si la ruta solicitada fue exactamente /settings, redirigimos
if ($path === '/settings') {
    header('Location: ' . $basePath . '/settings/your-profile');
    exit;
}
// --- ▲▲▲ FIN DE LA NUEVA LÓGICA ▲▲▲ ---
?>
<!DOCTYPE html>
<html lang="en">

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
                    // Aquí pasamos la variable $isSettingsPage
                    // para que el módulo de superficie sepa qué menú mostrar
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
        // Definir variables globales de JS para que los scripts las usen
        window.projectBasePath = '<?php echo $basePath; ?>';
        
        // --- ¡NUEVA MODIFICACIÓN! Pasar token a JS ---
        // Usamos el token de la sesión que ya nos aseguramos de generar.
        window.csrfToken = '<?php echo $_SESSION['csrf_token']; ?>';
        // --- FIN DE LA NUEVA MODIFICACIÓN ---
    </script>
    <script type="module" src="<?php echo $basePath; ?>/assets/js/app-init.js"></script>
    
    <div id="alert-container"></div>
    </body>

</html>
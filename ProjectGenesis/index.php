<?php
// /ProjectGenesis/index.php

// --- MODIFICACIÓN 1: INCLUIR CONFIG ---
// Incluir config.php ANTES DE CUALQUIER COSA.
// Esto inicia la sesión (session_start()) y conecta a la BD ($pdo).
include 'config.php';

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
$pathsToPages = [
    '/'           => 'home',
    '/explorer'   => 'explorer',
    '/login'      => 'login',
    '/register'   => 'register'
];

$currentPage = $pathsToPages[$path] ?? '404';

// 5. Definir qué páginas NO DEBEN mostrar el header/menu
$authPages = ['login', 'register'];
$isAuthPage = in_array($currentPage, $authPages);


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
// --- FIN DE LA LÓGICA AÑADIDA ---
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
                    <?php include 'includes/modules/module-surface.php'; ?>
                    <?php endif; ?>

                    <div class="general-content-scrolleable">
                        <div class="main-sections">
                            </div>
                    
                </div>
            </div>
        </div>
    </div>

    <script>
        // Definir una variable global de JS para que auth-manager.js la use
        window.projectBasePath = '<?php echo $basePath; ?>';
    </script>
    <script type="module" src="<?php echo $basePath; ?>/assets/js/app-init.js"></script>
</body>

</html>
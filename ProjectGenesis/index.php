<?php
// --- AÑADIR TODA ESTA LÓGICA PHP ---

// 1. Definir el base path de tu proyecto
$basePath = '/ProjectGenesis';

// 2. Obtener la ruta de la URL (sin query string)
$requestUri = $_SERVER['REQUEST_URI'];
$requestPath = strtok($requestUri, '?'); // Elimina query string

// 3. Limpiar la ruta para que coincida con la lógica de JS
$path = str_replace($basePath, '', $requestPath);
if (empty($path) || $path === '/') {
    $path = '/';
}

// 4. Replicar la lógica de rutas para saber la página actual
// (Basado en tu 'paths' y 'routes' de url-manager.js)
$pathsToPages = [
    '/'           => 'home',
    '/explorer'   => 'explorer',
    '/login'      => 'login',
    '/register'   => 'register'
];

$currentPage = $pathsToPages[$path] ?? '404'; // default a 404

// 5. Definir qué páginas NO DEBEN mostrar el header/menu
$authPages = ['login', 'register'];
$isAuthPage = in_array($currentPage, $authPages);

// --- FIN DE LA LÓGICA AÑADIDA ---
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded">
    <link rel="stylesheet" type="text/css" href="/ProjectGenesis/assets/css/styles.css">
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
    </div>

    <script type="module" src="/ProjectGenesis/assets/js/app-init.js"></script>
</body>

</html>
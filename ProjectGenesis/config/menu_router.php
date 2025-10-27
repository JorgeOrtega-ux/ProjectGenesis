<?php
// /config/menu_router.php
// Este script se encarga únicamente de devolver el HTML del menú lateral.

// 1. Incluir configuración para iniciar sesión y $basePath
include 'config.php';

// 2. Verificar que el usuario esté logueado
// Las páginas de autenticación (login, register) no tienen menú,
// así que si no hay un user_id, no devolvemos nada.
if (!isset($_SESSION['user_id'])) {
    http_response_code(403); // Prohibido
    exit;
}

// 3. Determinar qué menú cargar (main o settings)
$type = $_GET['type'] ?? 'main';

// 4. Definir la variable PHP que 'module-surface.php' espera
$isSettingsPage = ($type === 'settings');

// 5. Incluir y devolver el módulo del menú
// Las variables de sesión ($pdo, $_SESSION, $basePath) están disponibles
// gracias a la inclusión de 'config.php'.
include '../includes/modules/module-surface.php';

// No se debe imprimir nada más (ni <html>, ni <body>, etc.)
?>
<?php
$page = $_GET['page'] ?? 'home';

$CURRENT_SECTION = $page; 

$allowedPages = [
    'home'     => 'includes/sections/main/home.php',
    'explorer' => 'includes/sections/main/explorer.php',
    'login'    => 'includes/sections/auth/login.php', // <-- AÑADIR ESTA LÍNEA
    'register' => 'includes/sections/auth/register.php', // <-- AÑADIR ESTA LÍNEA
    '404'      => 'includes/sections/main/404.php', 
];

if (array_key_exists($page, $allowedPages)) {
    include $allowedPages[$page];
} else {
    http_response_code(404);
    $CURRENT_SECTION = '404'; 
    include $allowedPages['404']; 
}
?>
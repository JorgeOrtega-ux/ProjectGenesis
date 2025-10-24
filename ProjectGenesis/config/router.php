<?php
// /ProjectGenesis/router/router.php

// --- MODIFICACIÓN 1: INCLUIR CONFIG ---
include '../config/config.php'; // Inicia la sesión

$page = $_GET['page'] ?? 'home';

$CURRENT_SECTION = $page; 

$allowedPages = [
    'home'     => '../includes/sections/main/home.php',
    'explorer' => '../includes/sections/main/explorer.php',
    'login'    => '../includes/sections/auth/login.php',
    'register' => '../includes/sections/auth/register.php',
    'reset-password' => '../includes/sections/auth/reset-password.php', // <-- AÑADIDO
    '404'      => '../includes/sections/main/404.php', 
];

// --- MODIFICACIÓN 2: PROTEGER EL ROUTER ---
$authPages = ['login', 'register', 'reset-password']; // <-- AÑADIDO
$isAuthPage = in_array($page, $authPages);

// Si pide una página protegida (que no es de auth ni 404) Y NO tiene sesión
if (!isset($_SESSION['user_id']) && !$isAuthPage && $page !== '404') {
    // No le damos la página.
    http_response_code(403); // 403 Forbidden
    $CURRENT_SECTION = '404'; 
    include $allowedPages['404'];
    exit; // Detener script
}
// --- FIN DE LA MODIFICACIÓN ---


if (array_key_exists($page, $allowedPages)) {
    include $allowedPages[$page];
} else {
    http_response_code(404);
    $CURRENT_SECTION = '404'; 
    include $allowedPages['404']; 
}
?>
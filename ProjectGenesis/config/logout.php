<?php
// /ProjectGenesis/logout.php

// Incluimos config.php para tener $basePath y session_start()
include 'config.php'; 

// --- ¡NUEVA MODIFICACIÓN! Validar token CSRF ---
// Como esto es un enlace (GET), validamos el token de $_GET
$submittedToken = $_GET['csrf_token'] ?? '';

if (!validateCsrfToken($submittedToken)) {
    // Si el token no es válido, simplemente morimos o redirigimos.
    // Esto previene que un sitio malicioso cierre la sesión del usuario.
    die('Error de seguridad. Token inválido.');
}
// --- FIN DE LA MODIFICACIÓN ---


// 1. Desarmar todas las variables de sesión
$_SESSION = [];

// 2. Destruir la sesión
session_destroy();

// 3. Redirigir al login
header('Location: ' . $basePath . '/login');
exit;
?>
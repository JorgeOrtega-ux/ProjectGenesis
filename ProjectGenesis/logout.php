<?php
// /ProjectGenesis/logout.php

// Incluimos config.php para tener $basePath y session_start()
include 'config.php'; 

// 1. Desarmar todas las variables de sesión
$_SESSION = [];

// 2. Destruir la sesión
session_destroy();

// 3. Redirigir al login
header('Location: ' . $basePath . '/login');
exit;
?>
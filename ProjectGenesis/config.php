<?php
// /ProjectGenesis/config.php

// 1. INICIAR LA SESIÓN
// Esto debe ir ANTES de CUALQUIER salida HTML.
session_start();

// --- ¡¡¡ESTA ES LA LÍNEA CORREGIDA!!! ---
// Forzar la zona horaria del servidor a UTC.
// Esto soluciona los problemas de expiración de códigos.
date_default_timezone_set('UTC');
// --- FIN DE LA CORRECCIÓN ---


// 2. CONFIGURACIÓN DE LA BASE DE DATOS
define('DB_HOST', 'localhost');
define('DB_NAME', 'project_genesis'); 
define('DB_USER', 'root');
define('DB_PASS', '');

// 3. CREAR CONEXIÓN PDO
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", 
        DB_USER, 
        DB_PASS
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("ERROR: No se pudo conectar a la base de datos. " . $e->getMessage());
}

// 4. BASE PATH (¡ESTA ES LA LÍNEA CRÍTICA!)
// Asegúrate de que esta línea esté correcta.
$basePath = '/ProjectGenesis';

?>
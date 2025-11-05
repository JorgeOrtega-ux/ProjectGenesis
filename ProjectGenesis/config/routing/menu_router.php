<?php
// FILE: config/routing/menu_router.php
// (CÓDIGO MODIFICADO CON RUTAS CORREGIDAS)

// --- ▼▼▼ CAMBIO DE RUTA ▼▼▼ ---
include '../config.php';
// --- ▲▲▲ FIN DE CAMBIO ▲▲▲ ---

// --- ▼▼▼ INICIO DE MODIFICACIÓN (PERMITIR PÚBLICO) ▼▼▼ ---
// Si el tipo es 'help', no requerimos sesión de usuario
$type = $_GET['type'] ?? 'main';

if ($type !== 'help' && !isset($_SESSION['user_id'])) {
    http_response_code(403); 
    exit;
}
// --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

$isSettingsPage = ($type === 'settings');
$isAdminPage = ($type === 'admin'); 
$isHelpPage = ($type === 'help'); // <-- ¡NUEVA LÍNEA!

// --- ▼▼▼ INICIO DE LA MODIFICACIÓN (VALIDACIÓN DE ROL) ▼▼▼ ---
if ($isAdminPage) {
    // Replicar la lógica de seguridad de config/router.php
    $userRole = $_SESSION['role'] ?? 'user';
    if ($userRole !== 'administrator' && $userRole !== 'founder') {
        // Si el usuario no es admin pero la URL es de admin,
        // forzar que no se muestre el menú de admin.
        $isAdminPage = false;
    }
}
// --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---

// --- ▼▼▼ CAMBIO DE RUTA ▼▼▼ ---
include '../../includes/modules/module-surface.php';
// --- ▲▲▲ FIN DE CAMBIO ▲▲▲ ---
?>
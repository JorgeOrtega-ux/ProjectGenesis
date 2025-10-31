<?php

include 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403); 
    exit;
}

$type = $_GET['type'] ?? 'main';

$isSettingsPage = ($type === 'settings');
$isAdminPage = ($type === 'admin'); 

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


include '../includes/modules/module-surface.php';

?>
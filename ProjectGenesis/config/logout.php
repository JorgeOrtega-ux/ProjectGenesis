<?php

include 'config.php'; 

// --- ▼▼▼ MODIFICACIÓN ▼▼▼ ---
// Se cambia de $_GET a $_POST para mayor seguridad
$submittedToken = $_POST['csrf_token'] ?? '';
// --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

if (!validateCsrfToken($submittedToken)) {
    die('logout.invalidSession: Your session has expired or is invalid.');
}


$_SESSION = [];

session_destroy();

header('Location: ' . $basePath . '/login');
exit;
?>
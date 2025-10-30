<?php

include 'config.php'; 

$submittedToken = $_GET['csrf_token'] ?? '';

if (!validateCsrfToken($submittedToken)) {
    die('logout.invalidSession: Your session has expired or is invalid.');
}


$_SESSION = [];

session_destroy();

header('Location: ' . $basePath . '/login');
exit;
?>
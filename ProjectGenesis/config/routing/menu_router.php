<?php

include dirname(__DIR__, 2) . '/config/config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403); 
    exit;
}

$type = $_GET['type'] ?? 'main';

$isSettingsPage = ($type === 'settings');
$isAdminPage = ($type === 'admin'); 

if ($isAdminPage) {
    $userRole = $_SESSION['role'] ?? 'user';
    if ($userRole !== 'administrator' && $userRole !== 'founder') {
        $isAdminPage = false;
    }
}

include dirname(__DIR__, 2) . '/includes/modules/module-surface.php';
?>
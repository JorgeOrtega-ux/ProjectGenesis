<?php

include 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403); 
    exit;
}

$type = $_GET['type'] ?? 'main';

$isSettingsPage = ($type === 'settings');

include '../includes/modules/module-surface.php';

?>
<?php

error_reporting(E_ALL); 
ini_set('display_errors', 0); 
ini_set('log_errors', 1); 

$logPath = dirname(__DIR__) . '/logs/php_errors.log';
ini_set('error_log', $logPath);

ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');

$session_lifetime = 60 * 60 * 24 * 90; 

ini_set('session.cookie_lifetime', $session_lifetime);

session_start();

date_default_timezone_set('UTC');

include 'utilities.php';


define('DB_HOST', 'localhost');
define('DB_NAME', 'project_genesis'); 
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", 
        DB_USER, 
        DB_PASS
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $pdo->exec("SET time_zone = '+00:00'");

} catch (PDOException $e) {
    logDatabaseError($e, 'PDO Connection');
    die("ERROR: No se pudo conectar a la base de datos. " . $e->getMessage());
}


$GLOBALS['site_settings'] = [];
try {
    $stmt_settings = $pdo->prepare("SELECT setting_key, setting_value FROM site_settings");
    $stmt_settings->execute();
    while ($row = $stmt_settings->fetch()) {
        $GLOBALS['site_settings'][$row['setting_key']] = $row['setting_value'];
    }
    
    if (!isset($GLOBALS['site_settings']['maintenance_mode'])) {
         $GLOBALS['site_settings']['maintenance_mode'] = '0';
    }
    if (!isset($GLOBALS['site_settings']['allow_new_registrations'])) {
         $GLOBALS['site_settings']['allow_new_registrations'] = '1'; 
    }
    if (!isset($GLOBALS['site_settings']['messaging_service_enabled'])) {
         $GLOBALS['site_settings']['messaging_service_enabled'] = '1'; 
    }
    
} catch (PDOException $e) {
    logDatabaseError($e, 'config - load site_settings');
    $GLOBALS['site_settings']['maintenance_mode'] = '0'; 
    $GLOBALS['site_settings']['allow_new_registrations'] = '1'; 
    $GLOBALS['site_settings']['messaging_service_enabled'] = '1'; 
}

$basePath = '/ProjectGenesis';

?>
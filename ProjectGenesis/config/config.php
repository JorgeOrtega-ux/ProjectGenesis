<?php

error_reporting(E_ALL); 
ini_set('display_errors', 0); 
ini_set('log_errors', 1); 

$logPath = dirname(__DIR__) . '/logs/php_errors.log';
ini_set('error_log', $logPath);

ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');

// --- ▼▼▼ INICIO DE LA MODIFICACIÓN (SESIÓN 90 DÍAS) ▼▼▼ ---

// 60 seg * 60 min * 24 hr * 90 días = 3 meses (aprox)
$session_lifetime = 60 * 60 * 24 * 90; // 7,776,000 segundos

// Para 6 meses (aprox 180 días):
// $session_lifetime = 60 * 60 * 24 * 180; 

// Para "nunca" (o un tiempo muy largo, ej. 10 años):
// $session_lifetime = 60 * 60 * 24 * 365 * 10;

ini_set('session.cookie_lifetime', $session_lifetime);
// --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---

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
    // --- ▼▼▼ INICIO DE LÍNEA AÑADIDA ▼▼▼ ---
    if (!isset($GLOBALS['site_settings']['messaging_service_enabled'])) {
         $GLOBALS['site_settings']['messaging_service_enabled'] = '1'; 
    }
    // --- ▲▲▲ FIN DE LÍNEA AÑADIDA ▲▲▲ ---
    
} catch (PDOException $e) {
    logDatabaseError($e, 'config - load site_settings');
    $GLOBALS['site_settings']['maintenance_mode'] = '0'; 
    $GLOBALS['site_settings']['allow_new_registrations'] = '1'; 
    // --- ▼▼▼ INICIO DE LÍNEA AÑADIDA ▼▼▼ ---
    $GLOBALS['site_settings']['messaging_service_enabled'] = '1'; 
    // --- ▲▲▲ FIN DE LÍNEA AÑADIDA ▲▲▲ ---
}

$basePath = '/ProjectGenesis';

?>
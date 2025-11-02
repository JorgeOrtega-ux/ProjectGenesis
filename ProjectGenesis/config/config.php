<?php

// --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
// Configuración centralizada de errores
error_reporting(E_ALL); // Reportar todos los errores
ini_set('display_errors', 0); // NO mostrar errores en la salida
ini_set('log_errors', 1); // SÍ registrarlos en el log

// Definir la ruta del log en la carpeta /logs
// dirname(__DIR__) apunta a la raíz del proyecto (ProjectGenesis/)
$logPath = dirname(__DIR__) . '/logs/php_errors.log';
ini_set('error_log', $logPath);
// --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---


ini_set('session.use_only_cookies', 1);

ini_set('session.cookie_httponly', 1);


ini_set('session.cookie_samesite', 'Lax');



session_start();

date_default_timezone_set('UTC');


define('DB_HOST', 'localhost');
define('DB_NAME', 'project_genesis'); 
define('DB_USER', 'root');
define('DB_PASS', '');

// --- ▼▼▼ INICIO DE LA MODIFICACIÓN ▼▼▼ ---
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
    // Volver al mensaje básico de error que detiene todo
    die("ERROR: No se pudo conectar a la base de datos. " . $e->getMessage());
}
// --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---


// --- ▼▼▼ INICIO DE MODIFICACIÓN (MODO MANTENIMIENTO) ▼▼▼ ---
// Cargar configuraciones globales del sitio
$GLOBALS['site_settings'] = [];
try {
    // Asumimos que la tabla se llamará 'site_settings'
    $stmt_settings = $pdo->prepare("SELECT setting_key, setting_value FROM site_settings");
    $stmt_settings->execute();
    while ($row = $stmt_settings->fetch()) {
        $GLOBALS['site_settings'][$row['setting_key']] = $row['setting_value'];
    }
    
    // Asegurar un fallback si la clave no existe en la BD
    if (!isset($GLOBALS['site_settings']['maintenance_mode'])) {
         $GLOBALS['site_settings']['maintenance_mode'] = '0';
    }
    
} catch (PDOException $e) {
    // Si la tabla no existe o falla, asumimos que no hay mantenimiento
    logDatabaseError($e, 'config - load site_settings');
    $GLOBALS['site_settings']['maintenance_mode'] = '0'; // Fallback seguro
}
// --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---


$basePath = '/ProjectGenesis';



function generateCsrfToken() {
    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $token;
    return $token;
}

function getCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        return generateCsrfToken();
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($submittedToken) {
    if (empty($submittedToken) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $submittedToken);
}

function outputCsrfInput() {
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(getCsrfToken()) . '">';
}



define('MAX_LOGIN_ATTEMPTS', 5); 
define('LOCKOUT_TIME_MINUTES', 5); 

// --- ▼▼▼ INICIO DE LA MODIFICACIÓN (CONSTANTES DE SPAM) ▼▼▼ ---
define('MAX_PREFERENCE_CHANGES', 20); // 20 cambios en 60 mins
define('PREFERENCE_LOCKOUT_MINUTES', 60);
// --- ▲▲▲ FIN DE LA MODIFICACIÓN (CONSTANTES DE SPAM) ▲▲▲ ---


function getIpAddress() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    }
}

function checkLockStatus($pdo, $identifier, $ip) {
    try {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM security_logs 
             WHERE (user_identifier = ? OR ip_address = ?) 
             AND created_at > (NOW() - INTERVAL ? MINUTE)"
        );
        $stmt->execute([$identifier, $ip, LOCKOUT_TIME_MINUTES]);
        $attempts = $stmt->fetchColumn();
        
        return $attempts >= MAX_LOGIN_ATTEMPTS;

    } catch (PDOException $e) {
        logDatabaseError($e, 'checkLockStatus');
        return false;
    }
}

function logFailedAttempt($pdo, $identifier, $ip, $actionType) {
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO security_logs (user_identifier, action_type, ip_address) 
             VALUES (?, ?, ?)"
        );
        $stmt->execute([$identifier, $actionType, $ip]);
    } catch (PDOException $e) {
        logDatabaseError($e, 'logFailedAttempt');
    }
}

function clearFailedAttempts($pdo, $identifier) {
    try {
        $stmt = $pdo->prepare(
            "DELETE FROM security_logs 
             WHERE user_identifier = ?"
        );
        $stmt->execute([$identifier]);
    } catch (PDOException $e) {
        logDatabaseError($e, 'clearFailedAttempts');
    }
}



function logDatabaseError(PDOException $e, $context = 'Default') {
    $logDir = dirname(__DIR__) . '/logs';
    $logFile = $logDir . '/database_errors.log';

    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }

    $timestamp = date('Y-m-d H:i:s T'); 
    $errorMessage = sprintf(
        "[%s] [%s] %s (Archivo: %s, Línea: %d)\n",
        $timestamp,
        $context,
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    );

    @file_put_contents($logFile, $errorMessage, FILE_APPEND);
}

function getPreferredLanguage($acceptLanguage) {
    $supportedLanguages = [
        'en-us' => 'en-us',
        'es-mx' => 'es-mx',
        'es-latam' => 'es-latam',
        'fr-fr' => 'fr-fr'
    ];
    
    $primaryLanguageMap = [
        'es' => 'es-latam',
        'en' => 'en-us',
        'fr' => 'fr-fr'
    ];
    
    $defaultLanguage = 'en-us'; 

    if (empty($acceptLanguage)) {
        return $defaultLanguage;
    }

    $langs = [];
    preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i', $acceptLanguage, $matches);

    if (!empty($matches[1])) {
        $langs = array_map('strtolower', $matches[1]);
    }

    $primaryMatch = null;
    foreach ($langs as $lang) {
        if (isset($supportedLanguages[$lang])) {
            return $supportedLanguages[$lang];
        }
        
        $primary = substr($lang, 0, 2);
        if ($primaryMatch === null && isset($primaryLanguageMap[$primary])) {
            $primaryMatch = $primaryLanguageMap[$primary];
        }
    }
    
    if ($primaryMatch !== null) {
        return $primaryMatch;
    }

    return $defaultLanguage;
}
?>
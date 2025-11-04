<?php
// FILE: config/utilities.php
// Contiene funciones de utilidad separadas de la configuración principal.

// --- ▼▼▼ CONSTANTES DE UTILIDAD (MOVIMIENTO) ▼▼▼ ---
define('MAX_PREFERENCE_CHANGES', 20); // 20 cambios en 60 mins
define('PREFERENCE_LOCKOUT_MINUTES', 60);
// --- ▲▲▲ FIN DE CONSTANTES DE UTILIDAD ▲▲▲ ---


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


// --- ▼▼▼ INICIO DE MODIFICACIÓN (CONSTANTES ELIMINADAS DE AQUÍ, AHORA EN BD) ▼▼▼ ---
// define('MAX_LOGIN_ATTEMPTS', 5); // Movido a site_settings
// define('LOCKOUT_TIME_MINUTES', 5); // Movido a site_settings
// --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---


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
        // --- ▼▼▼ INICIO DE MODIFICACIÓN (USAR GLOBALS) ▼▼▼ ---
        $lockout_time = (int)($GLOBALS['site_settings']['lockout_time_minutes'] ?? 5);
        $max_attempts = (int)($GLOBALS['site_settings']['max_login_attempts'] ?? 5);
        
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM security_logs 
             WHERE (user_identifier = ? OR ip_address = ?) 
             AND created_at > (NOW() - INTERVAL ? MINUTE)"
        );
        $stmt->execute([$identifier, $ip, $lockout_time]);
        $attempts = $stmt->fetchColumn();
        
        return $attempts >= $max_attempts;
        // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

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


// --- ▼▼▼ ¡ESTA ES LA LÍNEA CORREGIDA! ▼▼▼ ---
function logDatabaseError(Throwable $e, $context = 'Default') {
// --- ▲▲▲ ¡FIN DE LA CORRECCIÓN! ▲▲▲ ---
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
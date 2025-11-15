<?php

mb_internal_encoding("UTF-8");

define('MAX_PREFERENCE_CHANGES', 20); 
define('PREFERENCE_LOCKOUT_MINUTES', 60);


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


function logDatabaseError(Throwable $e, $context = 'Default') {
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

function truncatePostText($text, $postId, $basePath, $limit = 500) {
    $escapedText = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    $nl2brText = nl2br($escapedText);

    if (mb_strlen($text, 'UTF-8') > $limit) {
        
        $truncated = mb_substr($text, 0, $limit, 'UTF-8');
        $escapedTruncated = htmlspecialchars($truncated, ENT_QUOTES, 'UTF-8');
        $nl2brTruncated = nl2br($escapedTruncated);

        $html = '<div class="post-text-content" data-post-id="' . $postId . '">';
        
        $html .= '<div class="post-text-truncated active">' . $nl2brTruncated . '...</div>';
        
        $html .= '<div class="post-text-full disabled">' . $nl2brText . '</div>';
        
        $html .= '<a href="#" class="post-read-more" data-action="toggle-post-text" data-i18n="js.publication.showMore" data-i18n-more="js.publication.showMore" data-i18n-less="js.publication.showLess">Mostrar más</a>';
        
        $html .= '</div>';
        
        return $html;
    } else {
        return '<div class="post-text-content">' . $nl2brText . '</div>';
    }
}
?>
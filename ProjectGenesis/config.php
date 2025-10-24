<?php
// /ProjectGenesis/config.php

// 1. INICIAR LA SESIÓN
// Esto debe ir ANTES de CUALQUIER salida HTML.
session_start();

// --- ¡¡¡ESTA ES LA LÍNEA CORREGIDA!!! ---
// Forzar la zona horaria del servidor a UTC.
// Esto soluciona los problemas de expiración de códigos.
date_default_timezone_set('UTC');
// --- FIN DE LA CORRECCIÓN ---


// 2. CONFIGURACIÓN DE LA BASE DE DATOS
define('DB_HOST', 'localhost');
define('DB_NAME', 'project_genesis'); 
define('DB_USER', 'root');
define('DB_PASS', '');

// 3. CREAR CONEXIÓN PDO
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", 
        DB_USER, 
        DB_PASS
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("ERROR: No se pudo conectar a la base de datos. " . $e->getMessage());
}

// 4. BASE PATH (¡ESTA ES LA LÍNEA CRÍTICA!)
// Asegúrate de que esta línea esté correcta.
$basePath = '/ProjectGenesis';


// ======================================
// === FUNCIONES CSRF GENERALES ===
// ======================================

/**
 * Genera un nuevo token CSRF, lo almacena en la sesión y lo devuelve.
 * @return string
 */
function generateCsrfToken() {
    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $token;
    return $token;
}

/**
 * Obtiene el token CSRF actual de la sesión. Si no existe, genera uno nuevo.
 * @return string
 */
function getCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        return generateCsrfToken();
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valida un token enviado contra el almacenado en la sesión.
 * @param string $submittedToken El token enviado (ej. desde $_POST o $_GET)
 * @return bool
 */
function validateCsrfToken($submittedToken) {
    if (empty($submittedToken) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $submittedToken);
}

/**
 * Imprime un <input> oculto con el token CSRF actual.
 * Se usa para insertarlo fácilmente en los formularios.
 */
function outputCsrfInput() {
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(getCsrfToken()) . '">';
}


// ======================================
// === ▼▼▼ NUEVAS FUNCIONES DE SEGURIDAD (RATE LIMIT) ▼▼▼ ===
// ======================================

// 1. Definir constantes de seguridad
define('MAX_LOGIN_ATTEMPTS', 5); // Intentos máximos
define('LOCKOUT_TIME_MINUTES', 5); // Minutos de bloqueo

/**
 * Obtiene la dirección IP real del usuario.
 * @return string
 */
function getIpAddress() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    }
}

/**
 * Verifica si un email o IP están bloqueados por intentos excesivos.
 * @param PDO $pdo
 * @param string $identifier El email del usuario.
 * @param string $ip La IP del usuario.
 * @return bool
 */
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
        // Si falla la BD, por seguridad, no bloqueamos.
        return false;
    }
}

/**
 * Registra un intento fallido en la base de datos.
 * @param PDO $pdo
 * @param string $identifier El email del usuario.
 * @param string $ip La IP del usuario.
 * @param string $actionType 'login_fail' or 'reset_fail'
 */
function logFailedAttempt($pdo, $identifier, $ip, $actionType) {
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO security_logs (user_identifier, action_type, ip_address) 
             VALUES (?, ?, ?)"
        );
        $stmt->execute([$identifier, $actionType, $ip]);
    } catch (PDOException $e) {
        // No hacer nada si falla el log
    }
}

/**
 * Limpia los intentos fallidos de un usuario (usado en login/reseteo exitoso).
 * @param PDO $pdo
 * @param string $identifier El email del usuario.
 */
function clearFailedAttempts($pdo, $identifier) {
    try {
        $stmt = $pdo->prepare(
            "DELETE FROM security_logs 
             WHERE user_identifier = ?"
        );
        $stmt->execute([$identifier]);
    } catch (PDOException $e) {
        // No hacer nada si falla la limpieza
    }
}
// --- ▲▲▲ FIN DE NUEVAS FUNCIONES DE SEGURIDAD ▲▲▲ ---
?>
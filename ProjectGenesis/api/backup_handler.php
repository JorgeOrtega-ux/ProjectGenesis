<?php
// FILE: api/backup_handler.php
// (CÓDIGO MODIFICADO CON MÉTODO 2 - PURO PHP)

include '../config/config.php';
// --- ENCABEZADO JSON MOVIDO ---

// --- 1. Validación de Seguridad Estricta ---

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'founder') {
    header('Content-Type: application/json'); // Enviar error como JSON
    $response['message'] = 'js.admin.errorAdminTarget';
    echo json_encode($response);
    exit;
}

$submittedToken = $_POST['csrf_token'] ?? '';
if (!validateCsrfToken($submittedToken)) {
    header('Content-Type: application/json'); // Enviar error como JSON
    $response['message'] = 'js.api.errorSecurityRefresh';
    echo json_encode($response);
    exit;
}

// --- 2. Definición de Rutas y Constantes ---

$backupDir = dirname(__DIR__) . '/backups';

if (!is_dir($backupDir)) {
    if (!@mkdir($backupDir, 0755, true)) {
        header('Content-Type: application/json'); // Enviar error como JSON
        $response['message'] = 'admin.backups.errorDirCreate';
        logDatabaseError(new Exception("API: No se pudo crear el directorio de backups en: " . $backupDir), 'backup_handler');
        echo json_encode($response);
        exit;
    }
}
if (!is_writable($backupDir)) {
    header('Content-Type: application/json'); // Enviar error como JSON
    $response['message'] = 'admin.backups.errorDirWrite';
    logDatabaseError(new Exception("API: El directorio de backups no tiene permisos de escritura: " . $backupDir), 'backup_handler');
    echo json_encode($response);
    exit;
}

// --- ▼▼▼ INICIO DE NUEVAS FUNCIONES (MÉTODO 2) ▼▼▼ ---

/**
 * Crea una copia de seguridad de la base de datos usando solo PDO.
 * @param PDO $pdo Objeto PDO de la conexión.
 * @param string $filepath Ruta completa donde se guardará el archivo .sql.
 * @return bool True si tiene éxito.
 */
function backupDatabasePDO($pdo, $filepath) {
    try {
        $sqlScript = "";
        
        // Obtener todas las tablas
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($tables)) {
            throw new Exception("No se encontraron tablas en la base de datos.");
        }
        
        $sqlScript .= "SET NAMES utf8mb4;\n";
        $sqlScript .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            // --- Estructura de la tabla ---
            $sqlScript .= "-- ----------------------------\n";
            $sqlScript .= "-- Table structure for $table\n";
            $sqlScript .= "-- ----------------------------\n";
            $sqlScript .= "DROP TABLE IF EXISTS `$table`;\n";
            
            $createTable = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
            $sqlScript .= $createTable['Create Table'] . ";\n\n";
            
            // --- Datos de la tabla ---
            $sqlScript .= "-- ----------------------------\n";
            $sqlScript .= "-- Records for $table\n";
            $sqlScript .= "-- ----------------------------\n";
            
            $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($rows as $row) {
                $sqlScript .= "INSERT INTO `$table` VALUES (";
                $values = [];
                foreach ($row as $value) {
                    if (is_null($value)) {
                        $values[] = "NULL";
                    } else {
                        // Usar pdo->quote() para escapar de forma segura todos los caracteres
                        $values[] = $pdo->quote($value);
                    }
                }
                $sqlScript .= implode(", ", $values) . ");\n";
            }
            $sqlScript .= "\n";
        }
        
        $sqlScript .= "SET FOREIGN_KEY_CHECKS=1;\n";
        
        // Escribir el script en el archivo
        if (file_put_contents($filepath, $sqlScript) === false) {
            throw new Exception("No se pudo escribir el archivo de backup en el disco.");
        }
        
        return true;

    } catch (Exception $e) {
        // Re-lanzar la excepción para que el handler principal la capture
        throw new Exception('admin.backups.errorExecCreate' . ' (PHP Method): ' . $e->getMessage());
    }
}

/**
 * Restaura una copia de seguridad desde un archivo .sql usando solo PDO.
 * @param PDO $pdo Objeto PDO de la conexión.
 * @param string $filepath Ruta completa al archivo .sql.
 * @return bool True si tiene éxito.
 */
function restoreDatabasePDO($pdo, $filepath) {
    try {
        // Leer el script SQL completo
        $sqlScript = file_get_contents($filepath);
        if ($sqlScript === false) {
            throw new Exception("No se pudo leer el archivo de backup.");
        }

        // Ejecutar el script completo. PDO::exec() puede manejar múltiples consultas.
        // Desactivar temporalmente la emulación de prepares puede ayudar
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, 0);
        $pdo->exec($sqlScript);
        
        return true;

    } catch (Exception $e) {
        // Re-lanzar la excepción
        throw new Exception('admin.backups.errorExecRestore' . ' (PHP Method): ' . $e->getMessage());
    }
}

// --- ▲▲▲ FIN DE NUEVAS FUNCIONES (MÉTODO 2) ▲▲▲ ---


// --- 3. Manejador de Acciones ---

$action = $_POST['action'] ?? '';

// --- ▼▼▼ INICIO DE MODIFICACIÓN (MANEJO DE DESCARGA) ▼▼▼ ---
if ($action === 'download-backup') {
    try {
        $filename = $_POST['filename'] ?? '';
        $safeFilename = basename($filename); // Sanitización CRÍTICA
        $filepath = $backupDir . '/' . $safeFilename;

        if (empty($safeFilename) || $safeFilename !== $filename || !file_exists($filepath) || !is_readable($filepath)) {
            throw new Exception('admin.backups.errorFileNotFound');
        }

        // Si el archivo existe y es legible, enviar los encabezados de descarga
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream'); // O application/sql
        header('Content-Disposition: attachment; filename="' . $safeFilename . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));
        
        // Limpiar cualquier buffer de salida
        ob_clean();
        flush();
        
        // Leer el archivo y enviarlo al cliente
        readfile($filepath);
        exit; // Terminar el script

    } catch (Exception $e) {
        logDatabaseError($e, 'backup_handler - download-backup');
        http_response_code(404); // Enviar un error 404 si no se encuentra
        die('Error: ' . $e->getMessage()); // Mostrar error simple
    }
}
// --- ▲▲▲ FIN DE MODIFICACIÓN (MANEJO DE DESCARGA) ▲▲▲ ---


// --- Si no es una descarga, es una API JSON normal ---
header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'js.api.invalidAction'];

try {
    if ($action === 'create-backup') {
        $filename = 'backup_' . DB_NAME . '_' . date('Y-m-d_H-i-s') . '.sql';
        $filepath = $backupDir . '/' . $filename;

        backupDatabasePDO($pdo, $filepath);

        // --- ▼▼▼ CORRECCIÓN: Devolver los datos del nuevo archivo ▼▼▼ ---
        $response['success'] = true;
        $response['message'] = 'admin.backups.successCreate';
        $response['newBackup'] = [
            'filename' => $filename,
            'size' => filesize($filepath), // Obtener el tamaño del archivo
            'created_at' => filemtime($filepath) // Obtener la fecha de mod.
        ];
        // --- ▲▲▲ FIN DE CORRECCIÓN ▲▲▲ ---
    
    } elseif ($action === 'restore-backup') {
        $filename = $_POST['filename'] ?? '';
        
        $safeFilename = basename($filename);
        $filepath = $backupDir . '/' . $safeFilename;

        if (empty($safeFilename) || $safeFilename !== $filename || !file_exists($filepath)) {
            throw new Exception('admin.backups.errorFileNotFound');
        }
        
        restoreDatabasePDO($pdo, $filepath);

        $response['success'] = true;
        $response['message'] = 'admin.backups.successRestore';

    } elseif ($action === 'delete-backup') {
        $filename = $_POST['filename'] ?? '';
        
        $safeFilename = basename($filename);
        $filepath = $backupDir . '/' . $safeFilename;

        if (empty($safeFilename) || $safeFilename !== $filename || !file_exists($filepath)) {
            throw new Exception('admin.backups.errorFileNotFound');
        }
        
        if (!@unlink($filepath)) {
             throw new Exception('admin.backups.errorDelete');
        }

        // --- ▼▼▼ CORRECCIÓN: Devolver el nombre del archivo eliminado ▼▼▼ ---
        $response['success'] = true;
        $response['message'] = 'admin.backups.successDelete';
        $response['deletedFilename'] = $safeFilename;
        // --- ▲▲▲ FIN DE CORRECCIÓN ▲▲▲ ---

    } else {
        $response['message'] = 'js.api.invalidAction';
    }

} catch (Exception $e) {
    logDatabaseError(new Exception($e->getMessage()), 'backup_handler - action: ' . $action);
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
exit;
?>
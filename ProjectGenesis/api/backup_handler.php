<?php

include '../config/config.php';


if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'founder') {
    header('Content-Type: application/json'); 
    $response['message'] = 'js.admin.errorAdminTarget';
    echo json_encode($response);
    exit;
}

$submittedToken = $_POST['csrf_token'] ?? '';
if (!validateCsrfToken($submittedToken)) {
    header('Content-Type: application/json'); 
    $response['message'] = 'js.api.errorSecurityRefresh';
    echo json_encode($response);
    exit;
}


$backupDir = dirname(__DIR__) . '/backups';

if (!is_dir($backupDir)) {
    if (!@mkdir($backupDir, 0755, true)) {
        header('Content-Type: application/json'); 
        $response['message'] = 'admin.backups.errorDirCreate';
        logDatabaseError(new Exception("API: No se pudo crear el directorio de backups en: " . $backupDir), 'backup_handler');
        echo json_encode($response);
        exit;
    }
}
if (!is_writable($backupDir)) {
    header('Content-Type: application/json'); 
    $response['message'] = 'admin.backups.errorDirWrite';
    logDatabaseError(new Exception("API: El directorio de backups no tiene permisos de escritura: " . $backupDir), 'backup_handler');
    echo json_encode($response);
    exit;
}


function backupDatabasePDO($pdo, $filepath) {
    try {
        $sqlScript = "";
        
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($tables)) {
            throw new Exception("No se encontraron tablas en la base de datos.");
        }
        
        $sqlScript .= "SET NAMES utf8mb4;\n";
        $sqlScript .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            $sqlScript .= "-- ----------------------------\n";
            $sqlScript .= "-- Table structure for $table\n";
            $sqlScript .= "-- ----------------------------\n";
            $sqlScript .= "DROP TABLE IF EXISTS `$table`;\n";
            
            $createTable = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
            $sqlScript .= $createTable['Create Table'] . ";\n\n";
            
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
                        $values[] = $pdo->quote($value);
                    }
                }
                $sqlScript .= implode(", ", $values) . ");\n";
            }
            $sqlScript .= "\n";
        }
        
        $sqlScript .= "SET FOREIGN_KEY_CHECKS=1;\n";
        
        if (file_put_contents($filepath, $sqlScript) === false) {
            throw new Exception("No se pudo escribir el archivo de backup en el disco.");
        }
        
        return true;

    } catch (Exception $e) {
        throw new Exception('admin.backups.errorExecCreate' . ' (PHP Method): ' . $e->getMessage());
    }
}

function restoreDatabasePDO($pdo, $filepath) {
    try {
        $sqlScript = file_get_contents($filepath);
        if ($sqlScript === false) {
            throw new Exception("No se pudo leer el archivo de backup.");
        }

        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, 0);
        $pdo->exec($sqlScript);
        
        return true;

    } catch (Exception $e) {
        throw new Exception('admin.backups.errorExecRestore' . ' (PHP Method): ' . $e->getMessage());
    }
}




$action = $_POST['action'] ?? '';

if ($action === 'download-backup') {
    try {
        $filename = $_POST['filename'] ?? '';
        $safeFilename = basename($filename); 
        $filepath = $backupDir . '/' . $safeFilename;

        if (empty($safeFilename) || $safeFilename !== $filename || !file_exists($filepath) || !is_readable($filepath)) {
            throw new Exception('admin.backups.errorFileNotFound');
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream'); 
        header('Content-Disposition: attachment; filename="' . $safeFilename . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));
        
        ob_clean();
        flush();
        
        readfile($filepath);
        exit; 

    } catch (Exception $e) {
        logDatabaseError($e, 'backup_handler - download-backup');
        http_response_code(404); 
        die('Error: ' . $e->getMessage()); 
    }
}


header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'js.api.invalidAction'];

try {
    if ($action === 'create-backup') {
        $filename = 'backup_' . DB_NAME . '_' . date('Y-m-d_H-i-s') . '.sql';
        $filepath = $backupDir . '/' . $filename;

        backupDatabasePDO($pdo, $filepath);

        $response['success'] = true;
        $response['message'] = 'admin.backups.successCreate';
        $response['newBackup'] = [
            'filename' => $filename,
            'size' => filesize($filepath), 
            'created_at' => filemtime($filepath) 
        ];
    
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

        $response['success'] = true;
        $response['message'] = 'admin.backups.successDelete';
        $response['deletedFilename'] = $safeFilename;

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
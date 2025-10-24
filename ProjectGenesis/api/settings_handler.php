<?php
// /ProjectGenesis/api/settings_handler.php

include '../config/config.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Acción no válida.'];

// --- VALIDACIÓN DE SESIÓN ---
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Acceso denegado. No has iniciado sesión.';
    echo json_encode($response);
    exit;
}
$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];

// --- FUNCIÓN HELPER PARA GENERAR AVATAR POR DEFECTO ---
// (Esta lógica se ha extraído de api/auth_handler.php para reutilizarla)
function generateDefaultAvatar($pdo, $userId, $username, $basePath) {
    try {
        $savePathDir = dirname(__DIR__) . '/assets/uploads/avatars';
        $fileName = "user-{$userId}.png";
        $fullSavePath = $savePathDir . '/' . $fileName;
        $publicUrl = $basePath . '/assets/uploads/avatars/' . $fileName;

        if (!is_dir($savePathDir)) {
            mkdir($savePathDir, 0755, true);
        }
        
        $avatarColors = ['206BD3', 'D32029', '28A745', 'E91E63', 'F57C00'];
        $selectedColor = $avatarColors[array_rand($avatarColors)];
        $nameParam = urlencode($username);
        
        $apiUrl = "https://ui-avatars.com/api/?name={$nameParam}&size=256&background={$selectedColor}&color=ffffff&bold=true&length=1";
        
        $imageData = @file_get_contents($apiUrl);

        if ($imageData === false) {
            // Fallback si la API falla
            return null;
        }

        file_put_contents($fullSavePath, $imageData);
        return $publicUrl;

    } catch (Exception $e) {
        // No hacer nada si falla el avatar
        return null;
    }
}

// --- FUNCIÓN HELPER PARA ELIMINAR AVATAR ANTIGUO ---
function deleteOldAvatar($oldUrl, $basePath, $userId) {
    // No eliminar si es un avatar por defecto de ui-avatars
    if (strpos($oldUrl, 'ui-avatars.com') !== false) {
        return;
    }
    
    // No eliminar si es el avatar por defecto (user-ID.png)
    if (strpos($oldUrl, 'user-' . $userId . '.png') !== false) {
        return;
    }

    // Convertir URL pública a ruta de servidor
    // URL: /ProjectGenesis/assets/uploads/avatars/user-1-12345.png
    // Ruta: .../ProjectGenesis/assets/uploads/avatars/user-1-12345.png
    $relativePath = str_replace($basePath, '', $oldUrl);
    $serverPath = dirname(__DIR__) . $relativePath;

    if (file_exists($serverPath)) {
        @unlink($serverPath);
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- VALIDACIÓN CSRF ---
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($submittedToken)) {
        $response['message'] = 'Error de seguridad. Por favor, recarga la página.';
        echo json_encode($response);
        exit;
    }

    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        // --- ACCIÓN: SUBIR NUEVO AVATAR ---
        if ($action === 'upload-avatar') {
            try {
                // 1. Validar el archivo subido
                if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
                    throw new Exception('Error al subir el archivo. Código: ' . ($_FILES['avatar']['error'] ?? 'N/A'));
                }

                $file = $_FILES['avatar'];
                $fileSize = $file['size'];
                $fileTmpName = $file['tmp_name'];

                // 2. Validar tamaño (MODIFICADO: max 2MB)
                if ($fileSize > 2 * 1024 * 1024) {
                    throw new Exception('El archivo es demasiado grande (máx 2MB).');
                }

                // 3. Validar tipo de imagen (con GIF y WebP)
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->file($fileTmpName);
                
                $allowedTypes = [
                    'image/png'  => 'png',
                    'image/jpeg' => 'jpg',
                    'image/gif'  => 'gif',
                    'image/webp' => 'webp'
                ];
                
                if (!array_key_exists($mimeType, $allowedTypes)) {
                    throw new Exception('Formato de archivo no válido (solo PNG, JPEG, GIF o WebP).');
                }
                $extension = $allowedTypes[$mimeType]; 

                // 4. Obtener el avatar antiguo para borrarlo después
                $stmt = $pdo->prepare("SELECT profile_image_url FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $oldUrl = $stmt->fetchColumn();

                // 5. Generar nuevo nombre y ruta
                $newFileName = "user-{$userId}-" . time() . "." . $extension;
                $saveDir = dirname(__DIR__) . '/assets/uploads/avatars/';
                $newFilePath = $saveDir . $newFileName;
                $newPublicUrl = $basePath . '/assets/uploads/avatars/' . $newFileName;

                // 6. Mover el archivo
                if (!move_uploaded_file($fileTmpName, $newFilePath)) {
                    throw new Exception('No se pudo guardar el archivo en el servidor.');
                }

                // 7. Actualizar la base de datos
                $stmt = $pdo->prepare("UPDATE users SET profile_image_url = ? WHERE id = ?");
                $stmt->execute([$newPublicUrl, $userId]);

                // 8. Eliminar el avatar antiguo (si era personalizado)
                if ($oldUrl) {
                    deleteOldAvatar($oldUrl, $basePath, $userId);
                }

                $response['success'] = true;
                $response['message'] = 'Avatar actualizado con éxito.';
                $response['newAvatarUrl'] = $newPublicUrl;

            } catch (Exception $e) {
                $response['message'] = $e->getMessage();
            }
        }

        // --- ACCIÓN: ELIMINAR AVATAR PERSONALIZADO ---
        elseif ($action === 'remove-avatar') {
            try {
                // 1. Obtener el avatar antiguo para borrarlo
                $stmt = $pdo->prepare("SELECT profile_image_url FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $oldUrl = $stmt->fetchColumn();

                // 2. Generar un nuevo avatar por defecto (con iniciales)
                // ¡Esto se hace ANTES de borrar el antiguo!
                $newDefaultUrl = generateDefaultAvatar($pdo, $userId, $username, $basePath);

                if (!$newDefaultUrl) {
                    throw new Exception('No se pudo generar el nuevo avatar por defecto desde la API.');
                }

                // 3. Actualizar la base de datos con el NUEVO avatar por defecto
                $stmt = $pdo->prepare("UPDATE users SET profile_image_url = ? WHERE id = ?");
                $stmt->execute([$newDefaultUrl, $userId]);

                // 4. Eliminar el avatar antiguo (si era personalizado)
                if ($oldUrl) {
                    deleteOldAvatar($oldUrl, $basePath, $userId);
                }

                $response['success'] = true;
                $response['message'] = 'Avatar eliminado. Se ha restaurado el avatar por defecto.';
                $response['newAvatarUrl'] = $newDefaultUrl;

            } catch (Exception $e) {
                $response['message'] = $e->getMessage();
            }
        }
    }
}

echo json_encode($response);
exit;
?>
<?php
// FILE: api/chat_handler.php (NUEVO ARCHIVO)

include '../config/config.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'js.api.invalidAction'];
$WS_BROADCAST_URL = 'http://127.0.0.1:8766/broadcast';

// 1. Validar Sesión de Usuario
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'js.settings.errorNoSession';
    echo json_encode($response);
    exit;
}
$userId = (int)$_SESSION['user_id'];
$username = $_SESSION['username'];
$profileImageUrl = $_SESSION['profile_image_url'];

// 2. Validar Método POST y Token CSRF
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'js.api.invalidAction';
    echo json_encode($response);
    exit;
}

$submittedToken = $_POST['csrf_token'] ?? '';
if (!validateCsrfToken($submittedToken)) {
    $response['message'] = 'js.api.errorSecurityRefresh';
    echo json_encode($response);
    exit;
}

$action = $_POST['action'] ?? '';

// ==========================================================
// FUNCIÓN PARA NOTIFICAR AL WEBSOCKET
// ==========================================================
function notifyWebSocketServer($url, $payload) {
    try {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payload)
        ]);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 500);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 500);
        curl_exec($ch);
        curl_close($ch);
        return true;
    } catch (Exception $e) {
        logDatabaseError($e, 'chat_handler - notifyWebSocketServer');
        return false;
    }
}

// ==========================================================
// ACCIÓN PRINCIPAL: ENVIAR MENSAJE
// ==========================================================
if ($action === 'send-message') {
    try {
        $pdo->beginTransaction();

        $groupUuid = $_POST['group_uuid'] ?? null;
        $messageText = trim($_POST['message_text'] ?? '');
        $uploadedImages = $_FILES['images'] ?? [];

        if (empty($groupUuid)) {
            throw new Exception('Error: ID de grupo no proporcionado.');
        }

        if (empty($messageText) && empty($uploadedImages['name'][0])) {
            throw new Exception('Error: No se puede enviar un mensaje vacío.');
        }

        // 1. Validar Grupo y Pertenencia del Usuario
        $stmt_check_group = $pdo->prepare(
            "SELECT g.id 
             FROM groups g
             JOIN user_groups ug ON g.id = ug.group_id
             WHERE g.uuid = ? AND ug.user_id = ?
             LIMIT 1"
        );
        $stmt_check_group->execute([$groupUuid, $userId]);
        $group = $stmt_check_group->fetch();

        if (!$group) {
            throw new Exception('Error: No eres miembro de este grupo o el grupo no existe.');
        }
        $groupId = (int)$group['id'];

        $messagesToSend = []; // Array de mensajes a transmitir

        // 2. Procesar Texto (si existe)
        if (!empty($messageText)) {
            $stmt_insert_text = $pdo->prepare(
                "INSERT INTO group_messages (group_id, user_id, message_type, content, created_at) 
                 VALUES (?, ?, 'text', ?, NOW())"
            );
            $stmt_insert_text->execute([$groupId, $userId, $messageText]);
            
            $messagesToSend[] = [
                "type" => "new_chat_message",
                "message" => [
                    "id" => (int)$pdo->lastInsertId(),
                    "user_id" => $userId,
                    "username" => $username,
                    "profile_image_url" => $profileImageUrl,
                    "message_type" => "text",
                    "content" => htmlspecialchars($messageText),
                    "created_at" => date('Y-m-d H:i:s')
                ]
            ];
        }

        // 3. Procesar Imágenes (si existen)
        if (!empty($uploadedImages['name'][0])) {
            $imageCount = count($uploadedImages['name']);
            // (Límite de 5 imágenes por mensaje)
            if ($imageCount > 5) {
                throw new Exception('No puedes subir más de 5 imágenes a la vez.');
            }

            $uploadDir = dirname(__DIR__) . '/assets/uploads/chat_files';
            $publicBaseUrl = $basePath . '/assets/uploads/chat_files';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $maxSize = 5 * 1024 * 1024; // 5 MB

            for ($i = 0; $i < $imageCount; $i++) {
                $fileError = $uploadedImages['error'][$i];
                $fileSize = $uploadedImages['size'][$i];
                $fileTmpName = $uploadedImages['tmp_name'][$i];
                $fileNameOriginal = $uploadedImages['name'][$i];
                
                if ($fileError !== UPLOAD_ERR_OK) continue;
                if ($fileSize > $maxSize) continue;

                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->file($fileTmpName);
                if (!in_array($mimeType, $allowedTypes)) continue;

                $extension = pathinfo($fileNameOriginal, PATHINFO_EXTENSION);
                $fileNameSystem = uniqid('chat_' . $userId . '_', true) . '.' . $extension;
                $filePath = $uploadDir . '/' . $fileNameSystem;
                $publicUrl = $publicBaseUrl . '/' . $fileNameSystem;

                if (move_uploaded_file($fileTmpName, $filePath)) {
                    // a. Guardar en 'uploaded_files'
                    $stmt_file = $pdo->prepare(
                        "INSERT INTO uploaded_files (user_id, group_id, file_name_system, file_name_original, file_path, public_url, file_type, file_size, created_at)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())"
                    );
                    $stmt_file->execute([$userId, $groupId, $fileNameSystem, $fileNameOriginal, $filePath, $publicUrl, $mimeType, $fileSize]);
                    $fileId = (int)$pdo->lastInsertId();

                    // b. Guardar en 'group_messages'
                    $stmt_insert_img = $pdo->prepare(
                        "INSERT INTO group_messages (group_id, user_id, message_type, content, file_id, created_at) 
                         VALUES (?, ?, 'image', ?, ?, NOW())"
                    );
                    $stmt_insert_img->execute([$groupId, $userId, $publicUrl, $fileId]);
                    
                    $messagesToSend[] = [
                        "type" => "new_chat_message",
                        "message" => [
                            "id" => (int)$pdo->lastInsertId(),
                            "user_id" => $userId,
                            "username" => $username,
                            "profile_image_url" => $profileImageUrl,
                            "message_type" => "image",
                            "content" => htmlspecialchars($publicUrl),
                            "created_at" => date('Y-m-d H:i:s')
                        ]
                    ];
                }
            }
        }
        
        // 4. Commit y Notificar al WebSocket
        $pdo->commit();
        
        if (!empty($messagesToSend)) {
            foreach ($messagesToSend as $msgPayload) {
                $broadcastPayload = json_encode([
                    "group_uuid" => $groupUuid,
                    "message_payload" => json_encode($msgPayload) // El payload interno debe ser un string JSON
                ]);
                notifyWebSocketServer($WS_BROADCAST_URL, $broadcastPayload);
            }
        }

        $response['success'] = true;
        $response['message'] = 'Mensaje enviado.';
        $response['sent_messages'] = count($messagesToSend);

    } catch (PDOException $e) {
        $pdo->rollBack();
        logDatabaseError($e, 'chat_handler - send-message');
        $response['message'] = 'js.api.errorDatabase';
    } catch (Exception $e) {
        $pdo->rollBack();
        $response['message'] = $e->getMessage();
    }
}

echo json_encode($response);
exit;
<?php
// FILE: api/chat_handler.php
// (CÓDIGO MODIFICADO para el nuevo esquema de 3 tablas)

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
$userRole = $_SESSION['role'] ?? 'user';

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
// ACCIÓN PRINCIPAL: ENVIAR MENSAJE (LÓGICA COMPLETAMENTE NUEVA)
// ==========================================================
if ($action === 'send-message') {
    try {
        $pdo->beginTransaction();

        $groupUuid = $_POST['group_uuid'] ?? null;
        $messageText = trim($_POST['message_text'] ?? '');
        $uploadedImages = $_FILES['images'] ?? [];

        // Tratar el texto vacío como NULL
        $messageTextContent = !empty($messageText) ? $messageText : null;
        $hasFiles = !empty($uploadedImages['name'][0]);

        if (empty($groupUuid)) {
            throw new Exception('Error: ID de grupo no proporcionado.');
        }

        // Validar que el mensaje no esté completamente vacío
        if ($messageTextContent === null && !$hasFiles) {
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

        // --- INICIO DE NUEVA LÓGICA ---

        $attachment_file_ids = []; // Almacenará los IDs de `chat_files`
        $attachments_for_payload = []; // Almacenará los datos para el WS

        // 2. Procesar Archivos (si existen)
        if ($hasFiles) {
            $imageCount = count($uploadedImages['name']);
            if ($imageCount > 9) { // Límite de 9 imágenes por mensaje
                throw new Exception('No puedes subir más de 9 imágenes a la vez.');
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

                $extension = strtolower(pathinfo($fileNameOriginal, PATHINFO_EXTENSION));
                // Asegurar extensión válida
                if ($extension === 'jpeg') $extension = 'jpg';
                if (!in_array($mimeType, $allowedTypes)) $extension = 'jpg'; // Fallback
                
                $fileNameSystem = uniqid('chat_' . $userId . '_', true) . '.' . $extension;
                $filePath = $uploadDir . '/' . $fileNameSystem;
                $publicUrl = $publicBaseUrl . '/' . $fileNameSystem;

                if (move_uploaded_file($fileTmpName, $filePath)) {
                    // a. Guardar en 'chat_files'
                    $stmt_file = $pdo->prepare(
                        "INSERT INTO chat_files (user_id, group_id, file_name_system, file_name_original, public_url, file_type, file_size, created_at)
                         VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
                    );
                    $stmt_file->execute([$userId, $groupId, $fileNameSystem, $fileNameOriginal, $publicUrl, $mimeType, $fileSize]);
                    $fileId = (int)$pdo->lastInsertId();
                    
                    $attachment_file_ids[] = $fileId; // Guardar ID para la tabla puente
                    
                    // Guardar datos para el payload del WS
                    $attachments_for_payload[] = [
                        'id' => $fileId,
                        'public_url' => $publicUrl,
                        'file_type' => $mimeType
                    ];
                }
            }
        }

        // 3. Crear la "Burbuja" de Mensaje
        $stmt_insert_msg = $pdo->prepare(
            "INSERT INTO group_messages (group_id, user_id, text_content, created_at) 
             VALUES (?, ?, ?, NOW())"
        );
        // Insertar el texto (o NULL si estaba vacío)
        $stmt_insert_msg->execute([$groupId, $userId, $messageTextContent]);
        $messageId = (int)$pdo->lastInsertId();
        $messageTimestamp = date('Y-m-d H:i:s'); // Usar la hora actual

        // 4. Vincular Archivos al Mensaje (si se subieron)
        if (!empty($attachment_file_ids)) {
            $stmt_link = $pdo->prepare(
                "INSERT INTO message_attachments (message_id, file_id, sort_order) 
                 VALUES (?, ?, ?)"
            );
            foreach ($attachment_file_ids as $index => $fileId) {
                $stmt_link->execute([$messageId, $fileId, $index]);
            }
        }
        
        // --- FIN DE NUEVA LÓGICA ---
        
        // 5. Commit y Notificar al WebSocket (¡SOLO UN MENSAJE!)
        $pdo->commit();
        $messageTimestamp = gmdate('Y-m-d H:i:s');
        // Construir el payload ÚNICO para el WS
        $ws_payload = [
            "type" => "new_chat_message",
            "message" => [
                "id" => $messageId,
                "user_id" => $userId,
                "username" => $username,
                "profile_image_url" => $profileImageUrl,
                "user_role" => $userRole,
                "text_content" => $messageTextContent ? htmlspecialchars($messageTextContent) : null,
                "attachments" => $attachments_for_payload, // Array de archivos
                "created_at" => $messageTimestamp
            ]
        ];

        // Preparar y enviar la notificación
        $broadcastPayload = json_encode([
            "group_uuid" => $groupUuid,
            "message_payload" => json_encode($ws_payload) // El payload interno debe ser un string JSON
        ]);
        notifyWebSocketServer($WS_BROADCAST_URL, $broadcastPayload);

        $response['success'] = true;
        $response['message'] = 'Mensaje enviado.';
        $response['sent_message_id'] = $messageId;

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
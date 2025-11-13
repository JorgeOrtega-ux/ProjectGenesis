<?php
// FILE: api/chat_handler.php
// (MODIFICADO PARA ACEPTAR MÚLTIPLES FOTOS)

include '../config/config.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'js.api.invalidAction'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'js.settings.errorNoSession';
    echo json_encode($response);
    exit;
}

$currentUserId = (int)$_SESSION['user_id'];

// --- ▼▼▼ INICIO DE CONSTANTES DE SUBIDA (MODIFICADO) ▼▼▼ ---
$ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$MAX_SIZE_MB = 5; // Límite de 5MB para fotos en chat
$MAX_SIZE_BYTES = $MAX_SIZE_MB * 1024 * 1024;
$MAX_CHAT_FILES = 4; // Límite de 4 fotos por mensaje
// --- ▲▲▲ FIN DE CONSTANTES DE SUBIDA ▲▲▲ ---

/**
 * Notifica al servidor WebSocket sobre un nuevo mensaje.
 */
function notifyUser($targetUserId, $payload) {
    try {
        $post_data = json_encode([
            'target_user_id' => (int)$targetUserId,
            'payload'        => $payload // El payload ya incluye el 'type'
        ]);

        $ch = curl_init('http://127.0.0.1:8766/notify-user');
        // ... (resto de la función curl sin cambios) ...
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($post_data)
        ]);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 500); 
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 500);        
        curl_exec($ch); 
        curl_close($ch);
        
    } catch (Exception $e) {
        logDatabaseError($e, 'chat_handler - (ws_notify_fail)');
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($submittedToken)) {
        $response['message'] = 'js.api.errorSecurityRefresh';
        echo json_encode($response);
        exit;
    }

    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'get-conversations') {
            
            $defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";

            // --- ▼▼▼ INICIO DE MODIFICACIÓN (SQL CON SUBQUERY A 'chat_message_attachments') ▼▼▼ ---
            $stmt_friends = $pdo->prepare(
                "SELECT 
                    f.friend_id,
                    u.username, u.profile_image_url, u.role, u.last_seen,
                    (SELECT 
                         CASE 
                             WHEN (SELECT 1 FROM chat_message_attachments cma WHERE cma.message_id = cm.id LIMIT 1) IS NOT NULL AND cm.message_text = '' THEN '[Imagen]'
                             WHEN (SELECT 1 FROM chat_message_attachments cma WHERE cma.message_id = cm.id LIMIT 1) IS NOT NULL AND cm.message_text != '' THEN cm.message_text
                             ELSE cm.message_text 
                         END
                     FROM chat_messages cm 
                     WHERE (cm.sender_id = :current_user_id AND cm.receiver_id = f.friend_id) 
                        OR (cm.sender_id = f.friend_id AND cm.receiver_id = :current_user_id)
                     ORDER BY cm.created_at DESC LIMIT 1) AS last_message,
            -- --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
                    (SELECT cm.created_at 
                     FROM chat_messages cm 
                     WHERE (cm.sender_id = :current_user_id AND cm.receiver_id = f.friend_id) 
                        OR (cm.sender_id = f.friend_id AND cm.receiver_id = :current_user_id)
                     ORDER BY cm.created_at DESC LIMIT 1) AS last_message_time,
                    (SELECT COUNT(*) 
                     FROM chat_messages cm 
                     WHERE cm.sender_id = f.friend_id 
                       AND cm.receiver_id = :current_user_id 
                       AND cm.is_read = 0) AS unread_count
                FROM (
                    (SELECT user_id_2 AS friend_id FROM friendships WHERE user_id_1 = :current_user_id AND status = 'accepted')
                    UNION
                    (SELECT user_id_1 AS friend_id FROM friendships WHERE user_id_2 = :current_user_id AND status = 'accepted')
                ) AS f
                JOIN users u ON f.friend_id = u.id
                ORDER BY last_message_time DESC, u.username ASC"
            );
            $stmt_friends->execute([':current_user_id' => $currentUserId]);
            $friends = $stmt_friends->fetchAll();

            foreach ($friends as &$friend) {
                if (empty($friend['profile_image_url'])) {
                    $friend['profile_image_url'] = "https://ui-avatars.com/api/?name=" . urlencode($friend['username']) . "&size=100&background=e0e0e0&color=ffffff";
                }
            }

            $response['success'] = true;
            $response['conversations'] = $friends;
        
        } elseif ($action === 'get-chat-history') {
            
            $targetUserId = (int)($_POST['target_user_id'] ?? 0);
            if ($targetUserId === 0) throw new Exception('js.api.invalidAction');

            // --- ▼▼▼ INICIO DE MODIFICACIÓN (SQL CON GROUP_CONCAT) ▼▼▼ ---
            $stmt_msg = $pdo->prepare(
                "SELECT 
                    cm.*,
                    (SELECT GROUP_CONCAT(cf.public_url SEPARATOR ',') 
                     FROM chat_message_attachments cma
                     JOIN chat_files cf ON cma.file_id = cf.id
                     WHERE cma.message_id = cm.id
                     ORDER BY cma.sort_order ASC
                    ) AS attachment_urls
                 FROM chat_messages cm
                 WHERE (cm.sender_id = :current_user_id AND cm.receiver_id = :target_user_id) 
                    OR (cm.sender_id = :target_user_id AND cm.receiver_id = :current_user_id)
                 GROUP BY cm.id
                 ORDER BY cm.created_at ASC
                 LIMIT 100"
            );
            // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
            
            $stmt_msg->execute([
                ':current_user_id' => $currentUserId,
                ':target_user_id' => $targetUserId
            ]);
            $messages = $stmt_msg->fetchAll();
            
            // 2. Marcar mensajes como leídos
            $stmt_read = $pdo->prepare(
                "UPDATE chat_messages SET is_read = 1 
                 WHERE sender_id = :target_user_id 
                   AND receiver_id = :current_user_id 
                   AND is_read = 0"
            );
            $stmt_read->execute([
                ':target_user_id' => $targetUserId,
                ':current_user_id' => $currentUserId
            ]);

            $response['success'] = true;
            $response['messages'] = $messages;

        } elseif ($action === 'send-message') {
            
            // --- ▼▼▼ INICIO DE LÓGICA DE SUBIDA DE MÚLTIPLES ARCHIVOS ▼▼▼ ---
            
            $receiverId = (int)($_POST['receiver_id'] ?? 0);
            $messageText = trim($_POST['message_text'] ?? '');
            $uploadedFiles = $_FILES['attachments'] ?? [];
            $fileIds = []; // Array para guardar los IDs de los archivos insertados en `chat_files`

            // 1. Validar que no esté vacío (texto O archivos)
            if ($receiverId === 0) {
                throw new Exception('js.api.invalidAction');
            }
            if (empty($messageText) && (empty($uploadedFiles['name'][0]) || $uploadedFiles['error'][0] !== UPLOAD_ERR_OK)) {
                throw new Exception('js.publication.errorEmpty'); // Mensaje vacío
            }
            
            $pdo->beginTransaction();

            // 2. Procesar archivos si existen
            if (!empty($uploadedFiles['name'][0]) && $uploadedFiles['error'][0] === UPLOAD_ERR_OK) {
                
                $fileCount = count($uploadedFiles['name']);
                if ($fileCount > $MAX_CHAT_FILES) {
                    $response['data'] = ['count' => $MAX_CHAT_FILES];
                    throw new Exception('js.publication.errorFileCount'); // Reutilizamos clave i18n
                }

                $uploadDir = dirname(__DIR__) . '/assets/uploads/chat_attachments';
                if (!is_dir($uploadDir)) {
                    if (!@mkdir($uploadDir, 0755, true)) {
                        throw new Exception('js.api.errorServer'); // Error de permisos
                    }
                }
                
                $stmt_insert_file = $pdo->prepare(
                    "INSERT INTO chat_files (uploader_id, file_name_system, file_name_original, public_url, file_type, file_size)
                     VALUES (?, ?, ?, ?, ?, ?)"
                );
                
                // Iterar sobre cada archivo subido
                foreach ($uploadedFiles['error'] as $key => $error) {
                    if ($error !== UPLOAD_ERR_OK) continue;

                    $tmpName = $uploadedFiles['tmp_name'][$key];
                    $originalName = $uploadedFiles['name'][$key];
                    $fileSize = $uploadedFiles['size'][$key];
                    
                    // 2a. Validar tamaño
                    if ($fileSize > $MAX_SIZE_BYTES) {
                        $response['data'] = ['size' => $MAX_SIZE_MB];
                        throw new Exception('js.publication.errorFileSize');
                    }

                    // 2b. Validar tipo
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mimeType = $finfo->file($tmpName);
                    if (!in_array($mimeType, $ALLOWED_TYPES)) {
                        throw new Exception('js.publication.errorFileType');
                    }
                    
                    // 2c. Crear nombre y mover archivo
                    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
                    $systemName = "chat-{$currentUserId}-" . time() . "-" . bin2hex(random_bytes(4)) . "-{$key}." . $extension;
                    $filePath = $uploadDir . '/' . $systemName;
                    $attachmentUrl = $basePath . '/assets/uploads/chat_attachments/' . $systemName;

                    if (!move_uploaded_file($tmpName, $filePath)) {
                        throw new Exception('js.settings.errorAvatarSave');
                    }

                    // 2d. Insertar en `chat_files`
                    $stmt_insert_file->execute([
                        $currentUserId, $systemName, $originalName, $attachmentUrl, $mimeType, $fileSize
                    ]);
                    $fileIds[] = $pdo->lastInsertId(); // Guardar el ID del archivo
                }
            }

            // 3. Guardar el mensaje en `chat_messages`
            $stmt_insert = $pdo->prepare(
                "INSERT INTO chat_messages (sender_id, receiver_id, message_text) 
                 VALUES (?, ?, ?)"
            );
            $stmt_insert->execute([$currentUserId, $receiverId, $messageText]);
            $newMessageId = $pdo->lastInsertId();

            // 4. Vincular archivos al mensaje en `chat_message_attachments`
            if (!empty($fileIds)) {
                $stmt_link_file = $pdo->prepare(
                    "INSERT INTO chat_message_attachments (message_id, file_id, sort_order)
                     VALUES (?, ?, ?)"
                );
                foreach ($fileIds as $index => $fileId) {
                    $stmt_link_file->execute([$newMessageId, $fileId, $index]);
                }
            }

            // 5. Obtener el mensaje completo para enviarlo por WebSocket (usando la consulta con GROUP_CONCAT)
            $stmt_get = $pdo->prepare(
                 "SELECT 
                    cm.*,
                    (SELECT GROUP_CONCAT(cf.public_url SEPARATOR ',') 
                     FROM chat_message_attachments cma
                     JOIN chat_files cf ON cma.file_id = cf.id
                     WHERE cma.message_id = cm.id
                     ORDER BY cma.sort_order ASC
                    ) AS attachment_urls
                 FROM chat_messages cm
                 WHERE cm.id = ?
                 GROUP BY cm.id"
            );
            $stmt_get->execute([$newMessageId]);
            $newMessage = $stmt_get->fetch();

            $pdo->commit();

            // 6. Preparar el payload para el WebSocket
            $payload = [
                'type' => 'new_chat_message',
                'payload' => $newMessage // Enviar el objeto completo del mensaje
            ];

            // 7. Notificar al destinatario
            notifyUser($receiverId, $payload);
            
            // 8. Devolver el mensaje al remitente (para confirmación)
            $response['success'] = true;
            $response['message_sent'] = $newMessage;
            
            // --- ▲▲▲ FIN DE LÓGICA DE SUBIDA DE MÚLTIPLES ARCHIVOS ▲▲▲ ---
        }

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        if ($e instanceof PDOException) {
            logDatabaseError($e, 'chat_handler - ' . $action);
            $response['message'] = 'js.api.errorDatabase';
        } else {
            $response['message'] = $e->getMessage();
            // Pasar datos extra para errores (ej. tamaño o límite de archivos)
            if (!isset($response['data'])) {
                $response['data'] = null;
            }
        }
    }
}

echo json_encode($response);
exit;
?>
<?php
// FILE: api/chat_handler.php
// (MODIFICADO PARA PAGINACIÓN, MÚLTIPLES FOTOS, RESPUESTAS Y ELIMINAR)
// (MODIFICADO OTRA VEZ PARA INCLUIR UUID EN CONVERSACIONES)
// (MODIFICADO OTRA VEZ PARA PRIVACIDAD DE MENSAJES)
// (CORREGIDO: SEPARADA LA LÓGICA DE VER HISTORIAL VS ENVIAR MENSAJES)

include '../config/config.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'js.api.invalidAction'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'js.settings.errorNoSession';
    echo json_encode($response);
    exit;
}

$currentUserId = (int)$_SESSION['user_id'];

// --- Constantes de subida ---
$ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$MAX_SIZE_MB = 5; // Límite de 5MB para fotos en chat
$MAX_SIZE_BYTES = $MAX_SIZE_MB * 1024 * 1024;
$MAX_CHAT_FILES = 4; // Límite de 4 fotos por mensaje
// --- Constante de paginación ---
define('CHAT_PAGE_SIZE', 30); // Número de mensajes a cargar por página
// --- LÍMITE DE TIEMPO PARA ELIMINAR (72 horas) ---
define('CHAT_DELETE_LIMIT_HOURS', 72);


/**
 * Notifica al servidor WebSocket sobre un nuevo mensaje.
 */
function notifyUser($targetUserId, $payload)
{
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


// --- ▼▼▼ INICIO DE MODIFICACIÓN DE PRIVACIDAD ▼▼▼ ---

/**
 * Comprueba si el usuario actual ($currentUserId) tiene permiso para
 * enviar mensajes al usuario receptor ($receiverId).
 * Devuelve true si está permitido, false si no.
 *
 * @param PDO $pdo
 * @param int $currentUserId ID del usuario que inicia la acción
 * @param int $receiverId ID del usuario que recibe la acción
 * @return bool
 */
function canSendMessage($pdo, $currentUserId, $receiverId) {
    // No comprobar si se envían mensajes a sí mismo (guardados)
    if ($currentUserId == $receiverId) {
        return true;
    }

    // Obtener la configuración de privacidad del RECEPTOR
    $stmt_privacy = $pdo->prepare("SELECT message_privacy_level FROM user_preferences WHERE user_id = ?");
    $stmt_privacy->execute([$receiverId]);
    $privacy = $stmt_privacy->fetchColumn();
    
    // Valor por defecto 'all' si no hay fila de preferencias
    if (!$privacy) {
        $privacy = 'all';
    }

    // Opción 1: El receptor no acepta mensajes de nadie.
    if ($privacy === 'none') {
        return false;
    }

    // Opción 2: El receptor solo acepta mensajes de amigos.
    if ($privacy === 'friends') {
        // Comprobar si son amigos
        $userId1 = min($currentUserId, $receiverId);
        $userId2 = max($currentUserId, $receiverId);
        
        $stmt_friend = $pdo->prepare("SELECT status FROM friendships WHERE user_id_1 = ? AND user_id_2 = ? AND status = 'accepted'");
        $stmt_friend->execute([$userId1, $userId2]);
        
        if (!$stmt_friend->fetch()) {
            // No son amigos, bloquear.
            return false;
        }
    }
    
    // Opción 3: 'all'. Permitir.
    return true;
}

/**
 * Lanza una Excepción si la mensajería está bloqueada.
 * (Ahora usa canSendMessage)
 *
 * @param PDO $pdo
 * @param int $currentUserId
 * @param int $receiverId
 * @return void
 * @throws Exception
 */
function checkMessagePrivacy($pdo, $currentUserId, $receiverId) {
    if (!canSendMessage($pdo, $currentUserId, $receiverId)) {
        throw new Exception('js.chat.errorPrivacyBlocked');
    }
    // Si está permitido, no hace nada.
}
// --- ▲▲▲ FIN DE MODIFICACIÓN DE PRIVACIDAD ---


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

            // Consulta que obtiene amigos Y no-amigos con chat
            $stmt_friends = $pdo->prepare(
                "SELECT 
                    f.friend_id,
                    u.username, u.uuid, u.profile_image_url, u.role, u.last_seen,
                    (SELECT 
                         CASE 
                             WHEN (SELECT 1 FROM chat_message_attachments cma WHERE cma.message_id = cm.id LIMIT 1) IS NOT NULL AND cm.message_text = '' THEN '[Imagen]'
                             WHEN (SELECT 1 FROM chat_message_attachments cma WHERE cma.message_id = cm.id LIMIT 1) IS NOT NULL AND cm.message_text != '' THEN cm.message_text
                             ELSE cm.message_text 
                         END
                     FROM chat_messages cm 
                     WHERE ((cm.sender_id = :current_user_id AND cm.receiver_id = f.friend_id) 
                        OR (cm.sender_id = f.friend_id AND cm.receiver_id = :current_user_id))
                     AND cm.status = 'active'
                     ORDER BY cm.created_at DESC LIMIT 1) AS last_message,
                    (SELECT cm.created_at 
                     FROM chat_messages cm 
                     WHERE ((cm.sender_id = :current_user_id AND cm.receiver_id = f.friend_id) 
                        OR (cm.sender_id = f.friend_id AND cm.receiver_id = :current_user_id))
                     AND cm.status = 'active'
                     ORDER BY cm.created_at DESC LIMIT 1) AS last_message_time,
                    (SELECT COUNT(*) 
                     FROM chat_messages cm 
                     WHERE cm.sender_id = f.friend_id 
                       AND cm.receiver_id = :current_user_id 
                       AND cm.is_read = 0
                       AND cm.status = 'active') AS unread_count
                FROM (
                    -- 1. Todos los amigos aceptados
                    SELECT user_id_2 AS friend_id FROM friendships WHERE user_id_1 = :current_user_id AND status = 'accepted'
                    UNION
                    SELECT user_id_1 AS friend_id FROM friendships WHERE user_id_2 = :current_user_id AND status = 'accepted'
                    UNION
                    -- 2. Todas las personas a las que he enviado mensaje
                    SELECT receiver_id AS friend_id FROM chat_messages WHERE sender_id = :current_user_id
                    UNION
                    -- 3. Todas las personas que me han enviado mensaje
                    SELECT sender_id AS friend_id FROM chat_messages WHERE receiver_id = :current_user_id
                ) AS f
                JOIN users u ON f.friend_id = u.id
                WHERE f.friend_id != :current_user_id
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

            // --- ▼▼▼ INICIO DE MODIFICACIÓN DE PRIVACIDAD ▼▼▼ ---
            // ¡Quitamos el bloqueo de aquí!
            // checkMessagePrivacy($pdo, $currentUserId, $targetUserId);
            
            // En su lugar, comprobamos si se puede enviar y lo notificamos al frontend
            $canSendMessage = canSendMessage($pdo, $currentUserId, $targetUserId);
            // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

            $beforeMessageId = (int)($_POST['before_message_id'] ?? 0);

            // (El SQL para obtener mensajes no cambia)
            $sql_select =
                "SELECT 
                    cm.*,
                    (SELECT GROUP_CONCAT(cf.public_url SEPARATOR ',') 
                     FROM chat_message_attachments cma
                     JOIN chat_files cf ON cma.file_id = cf.id
                     WHERE cma.message_id = cm.id
                     ORDER BY cma.sort_order ASC
                    ) AS attachment_urls,
                    replied_msg.message_text AS replied_message_text,
                    replied_user.username AS replied_message_user
                 FROM chat_messages cm
                 LEFT JOIN chat_messages AS replied_msg ON cm.reply_to_message_id = replied_msg.id
                 LEFT JOIN users AS replied_user ON replied_msg.sender_id = replied_user.id
                 WHERE ((cm.sender_id = :current_user_id AND cm.receiver_id = :target_user_id) 
                    OR (cm.sender_id = :target_user_id AND cm.receiver_id = :current_user_id))";

            if ($beforeMessageId > 0) {
                $sql_select .= " AND cm.id < :before_message_id";
            }

            $sql_select .= "
                 GROUP BY cm.id
                 ORDER BY cm.created_at DESC
                 LIMIT " . CHAT_PAGE_SIZE;

            $stmt_msg = $pdo->prepare($sql_select);
            $stmt_msg->bindValue(':current_user_id', $currentUserId, PDO::PARAM_INT);
            $stmt_msg->bindValue(':target_user_id', $targetUserId, PDO::PARAM_INT);
            if ($beforeMessageId > 0) {
                $stmt_msg->bindValue(':before_message_id', $beforeMessageId, PDO::PARAM_INT);
            }
            $stmt_msg->execute();
            $messages = $stmt_msg->fetchAll();

            // (Marcar como leído no cambia)
            if ($beforeMessageId === 0) {
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
            }

            $response['success'] = true;
            $response['messages'] = $messages;
            $response['message_count'] = count($messages);
            $response['limit'] = CHAT_PAGE_SIZE;
            
            // --- ▼▼▼ INICIO DE MODIFICACIÓN DE PRIVACIDAD ▼▼▼ ---
            // Añadimos el nuevo flag a la respuesta
            $response['can_send_message'] = $canSendMessage;
            // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

        } elseif ($action === 'send-message') {

            $receiverId = (int)($_POST['receiver_id'] ?? 0);
            $messageText = trim($_POST['message_text'] ?? '');
            $replyToMessageId = (int)($_POST['reply_to_message_id'] ?? 0);
            $dbReplyToId = ($replyToMessageId > 0) ? $replyToMessageId : null;
            $uploadedFiles = $_FILES['attachments'] ?? [];
            $fileIds = [];

            if ($receiverId === 0) {
                throw new Exception('js.api.invalidAction');
            }
            
            // --- ▼▼▼ COMPROBACIÓN DE PRIVACIDAD ▼▼▼ ---
            // Esta comprobación SÍ se queda aquí. No puedes ENVIAR si está bloqueado.
            checkMessagePrivacy($pdo, $currentUserId, $receiverId);
            // --- ▲▲▲ FIN DE COMPROBACIÓN ▲▲▲ ---

            if (empty($messageText) && (empty($uploadedFiles['name'][0]) || $uploadedFiles['error'][0] !== UPLOAD_ERR_OK)) {
                throw new Exception('js.publication.errorEmpty'); // Mensaje vacío
            }

            $pdo->beginTransaction();

            // (Lógica de subida de archivos no cambia...)
            if (!empty($uploadedFiles['name'][0]) && $uploadedFiles['error'][0] === UPLOAD_ERR_OK) {
                $fileCount = count($uploadedFiles['name']);
                if ($fileCount > $MAX_CHAT_FILES) {
                    $response['data'] = ['count' => $MAX_CHAT_FILES];
                    throw new Exception('js.publication.errorFileCount');
                }
                $uploadDir = dirname(__DIR__) . '/assets/uploads/chat_attachments';
                if (!is_dir($uploadDir)) {
                    if (!@mkdir($uploadDir, 0755, true)) {
                        throw new Exception('js.api.errorServer');
                    }
                }
                $stmt_insert_file = $pdo->prepare(
                    "INSERT INTO chat_files (uploader_id, file_name_system, file_name_original, public_url, file_type, file_size)
                     VALUES (?, ?, ?, ?, ?, ?)"
                );
                foreach ($uploadedFiles['error'] as $key => $error) {
                    if ($error !== UPLOAD_ERR_OK) continue;
                    $tmpName = $uploadedFiles['tmp_name'][$key];
                    $originalName = $uploadedFiles['name'][$key];
                    $fileSize = $uploadedFiles['size'][$key];
                    if ($fileSize > $MAX_SIZE_BYTES) {
                        $response['data'] = ['size' => $MAX_SIZE_MB];
                        throw new Exception('js.publication.errorFileSize');
                    }
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mimeType = $finfo->file($tmpName);
                    if (!in_array($mimeType, $ALLOWED_TYPES)) {
                        throw new Exception('js.publication.errorFileType');
                    }
                    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
                    $systemName = "chat-{$currentUserId}-" . time() . "-" . bin2hex(random_bytes(4)) . "-{$key}." . $extension;
                    $filePath = $uploadDir . '/' . $systemName;
                    $attachmentUrl = $basePath . '/assets/uploads/chat_attachments/' . $systemName;
                    if (!move_uploaded_file($tmpName, $filePath)) {
                        throw new Exception('js.settings.errorAvatarSave');
                    }
                    $stmt_insert_file->execute([
                        $currentUserId,
                        $systemName,
                        $originalName,
                        $attachmentUrl,
                        $mimeType,
                        $fileSize
                    ]);
                    $fileIds[] = $pdo->lastInsertId();
                }
            }

            // (Insertar mensaje no cambia)
            $stmt_insert = $pdo->prepare(
                "INSERT INTO chat_messages (sender_id, receiver_id, message_text, reply_to_message_id) 
                 VALUES (?, ?, ?, ?)"
            );
            $stmt_insert->execute([$currentUserId, $receiverId, $messageText, $dbReplyToId]);
            $newMessageId = $pdo->lastInsertId();

            if (!empty($fileIds)) {
                $stmt_link_file = $pdo->prepare(
                    "INSERT INTO chat_message_attachments (message_id, file_id, sort_order)
                     VALUES (?, ?, ?)"
                );
                foreach ($fileIds as $index => $fileId) {
                    $stmt_link_file->execute([$newMessageId, $fileId, $index]);
                }
            }
            
            // (Obtener mensaje enviado no cambia)
            $stmt_get = $pdo->prepare(
                "SELECT 
                    cm.*,
                    (SELECT GROUP_CONCAT(cf.public_url SEPARATOR ',') 
                     FROM chat_message_attachments cma
                     JOIN chat_files cf ON cma.file_id = cf.id
                     WHERE cma.message_id = cm.id
                     ORDER BY cma.sort_order ASC
                    ) AS attachment_urls,
                    replied_msg.message_text AS replied_message_text,
                    replied_user.username AS replied_message_user
                 FROM chat_messages cm
                 LEFT JOIN chat_messages AS replied_msg ON cm.reply_to_message_id = replied_msg.id
                 LEFT JOIN users AS replied_user ON replied_msg.sender_id = replied_user.id
                 WHERE cm.id = ?
                 GROUP BY cm.id"
            );
            $stmt_get->execute([$newMessageId]);
            $newMessage = $stmt_get->fetch();

            $pdo->commit();

            $payload = [
                'type' => 'new_chat_message',
                'payload' => $newMessage
            ];

            notifyUser($receiverId, $payload);

            $response['success'] = true;
            $response['message_sent'] = $newMessage;

        } elseif ($action === 'delete-message') {

            // (Toda la lógica de delete-message no cambia)
            $messageId = (int)($_POST['message_id'] ?? 0);
            if ($messageId === 0) {
                throw new Exception('js.api.invalidAction');
            }
            $stmt_check = $pdo->prepare(
                "SELECT sender_id, receiver_id, created_at, status 
                 FROM chat_messages WHERE id = ? AND sender_id = ?"
            );
            $stmt_check->execute([$messageId, $currentUserId]);
            $message = $stmt_check->fetch();
            if (!$message) {
                throw new Exception('js.chat.errorNotOwner');
            }
            if ($message['status'] === 'deleted') {
                throw new Exception('js.chat.errorAlreadyDeleted');
            }
            $createdTime = new DateTime($message['created_at'], new DateTimeZone('UTC'));
            $currentTime = new DateTime('now', new DateTimeZone('UTC'));
            $interval = $currentTime->getTimestamp() - $createdTime->getTimestamp();
            $hoursPassed = $interval / 3600;
            if ($hoursPassed > CHAT_DELETE_LIMIT_HOURS) {
                throw new Exception('js.chat.errorDeleteTimeLimit');
            }
            $stmt_update = $pdo->prepare(
                "UPDATE chat_messages SET status = 'deleted', message_text = 'Se eliminó este mensaje' 
                 WHERE id = ?"
            );
            $stmt_update->execute([$messageId]);
            $receiverId = (int)$message['receiver_id'];
            $payload = [
                'type' => 'message_deleted',
                'payload' => [
                    'message_id' => $messageId,
                    'conversation_user_id' => $receiverId
                ]
            ];
            notifyUser($receiverId, $payload);
            notifyUser($currentUserId, $payload);
            $response['success'] = true;
            $response['message'] = 'js.chat.successDeleted';
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
            if (!isset($response['data'])) {
                $response['data'] = null;
            }
        }
    }
}

echo json_encode($response);
exit;
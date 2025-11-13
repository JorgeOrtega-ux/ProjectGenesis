<?php
// FILE: api/chat_handler.php
// (NUEVO ARCHIVO)

include '../config/config.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'js.api.invalidAction'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'js.settings.errorNoSession';
    echo json_encode($response);
    exit;
}

$currentUserId = (int)$_SESSION['user_id'];

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
            // Obtener todos los amigos (similar a friend_handler)
            // Y añadir el último mensaje y el conteo de no leídos
            
            $defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";

            $stmt_friends = $pdo->prepare(
                "SELECT 
                    f.friend_id,
                    u.username, u.profile_image_url, u.role, u.last_seen,
                    (SELECT cm.message_text 
                     FROM chat_messages cm 
                     WHERE (cm.sender_id = :current_user_id AND cm.receiver_id = f.friend_id) 
                        OR (cm.sender_id = f.friend_id AND cm.receiver_id = :current_user_id)
                     ORDER BY cm.created_at DESC LIMIT 1) AS last_message,
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

            // 1. Obtener mensajes
            $stmt_msg = $pdo->prepare(
                "SELECT * FROM chat_messages 
                 WHERE (sender_id = :current_user_id AND receiver_id = :target_user_id) 
                    OR (sender_id = :target_user_id AND receiver_id = :current_user_id)
                 ORDER BY created_at ASC
                 LIMIT 100" // Limitar historial inicial
            );
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
            
            $receiverId = (int)($_POST['receiver_id'] ?? 0);
            $messageText = trim($_POST['message_text'] ?? '');

            if ($receiverId === 0 || empty($messageText)) {
                throw new Exception('js.api.invalidAction');
            }
            
            // 1. Guardar en la base de datos
            $stmt_insert = $pdo->prepare(
                "INSERT INTO chat_messages (sender_id, receiver_id, message_text) 
                 VALUES (?, ?, ?)"
            );
            $stmt_insert->execute([$currentUserId, $receiverId, $messageText]);
            $newMessageId = $pdo->lastInsertId();

            // 2. Obtener el mensaje completo para enviarlo por WebSocket
            $stmt_get = $pdo->prepare("SELECT * FROM chat_messages WHERE id = ?");
            $stmt_get->execute([$newMessageId]);
            $newMessage = $stmt_get->fetch();

            // 3. Preparar el payload para el WebSocket
            $payload = [
                'type' => 'new_chat_message',
                'payload' => $newMessage // Enviar el objeto completo del mensaje
            ];

            // 4. Notificar al destinatario
            notifyUser($receiverId, $payload);
            
            // 5. Devolver el mensaje al remitente (para confirmación)
            $response['success'] = true;
            $response['message_sent'] = $newMessage;
        }

    } catch (Exception $e) {
        if ($e instanceof PDOException) {
            logDatabaseError($e, 'chat_handler - ' . $action);
            $response['message'] = 'js.api.errorDatabase';
        } else {
            $response['message'] = $e->getMessage();
        }
    }
}

echo json_encode($response);
exit;
?>
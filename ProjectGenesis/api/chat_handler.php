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
function notifyReceiverOfNewMessage($targetUserId, $messagePayload) {
    try {
        $post_data = json_encode([
            'target_user_id' => (int)$targetUserId,
            'payload'        => [
                'type' => 'new_chat_message',
                'message' => $messagePayload // El objeto completo del mensaje
            ]
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

/**
 * Obtiene el estado en línea de una lista de amigos.
 */
function getOnlineStatusForFriends($friendIds) {
    if (empty($friendIds)) {
        return [];
    }
    $onlineUserIds = [];
    try {
        $context = stream_context_create(['http' => ['timeout' => 1.0]]);
        $jsonResponse = @file_get_contents('http://127.0.0.1:8766/get-online-users', false, $context);
        if ($jsonResponse !== false) {
            $data = json_decode($jsonResponse, true);
            if (isset($data['status']) && $data['status'] === 'ok' && isset($data['online_users'])) {
                $onlineUserIds = array_flip($data['online_users']);
            }
        }
    } catch (Exception $e) {
        logDatabaseError($e, 'chat_handler - (ws_get_online_fail)');
    }
    
    $statusMap = [];
    foreach ($friendIds as $friendId) {
        $statusMap[$friendId] = isset($onlineUserIds[$friendId]);
    }
    return $statusMap;
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
            
            // 1. Obtener todos los amigos
            $stmt_friends = $pdo->prepare(
                "SELECT 
                    (CASE WHEN f.user_id_1 = ? THEN f.user_id_2 ELSE f.user_id_1 END) AS friend_id,
                    u.username, u.profile_image_url, u.role
                FROM friendships f
                JOIN users u ON (CASE WHEN f.user_id_1 = ? THEN f.user_id_2 ELSE f.user_id_1 END) = u.id
                WHERE (f.user_id_1 = ? OR f.user_id_2 = ?) AND f.status = 'accepted'
                ORDER BY u.username ASC"
            );
            $stmt_friends->execute([$currentUserId, $currentUserId, $currentUserId, $currentUserId]);
            $friends = $stmt_friends->fetchAll();

            $friendIds = array_column($friends, 'friend_id');
            $onlineStatusMap = getOnlineStatusForFriends($friendIds);
            
            // 2. Preparar consultas para el bucle (para obtener último mensaje y conteo de no leídos)
            $stmt_last_msg = $pdo->prepare(
                "SELECT message_text, created_at, sender_id 
                 FROM chat_messages 
                 WHERE (sender_id = :current_user_id AND receiver_id = :friend_id) 
                    OR (sender_id = :friend_id AND receiver_id = :current_user_id)
                 ORDER BY created_at DESC 
                 LIMIT 1"
            );
            
            $stmt_unread = $pdo->prepare(
                "SELECT COUNT(*) 
                 FROM chat_messages 
                 WHERE sender_id = :friend_id AND receiver_id = :current_user_id AND is_read = 0"
            );

            // 3. Iterar y enriquecer los datos de cada amigo
            foreach ($friends as &$friend) {
                $friendId = $friend['friend_id'];
                
                // Añadir estado online
                $friend['is_online'] = $onlineStatusMap[$friendId] ?? false;

                // Añadir avatar por defecto si no tiene
                if (empty($friend['profile_image_url'])) {
                    $friend['profile_image_url'] = "https://ui-avatars.com/api/?name=" . urlencode($friend['username']) . "&size=100&background=e0e0e0&color=ffffff";
                }

                // Obtener último mensaje
                $stmt_last_msg->execute([':current_user_id' => $currentUserId, ':friend_id' => $friendId]);
                $lastMsg = $stmt_last_msg->fetch();
                if ($lastMsg) {
                    $friend['last_message_text'] = ($lastMsg['sender_id'] == $currentUserId ? 'Tú: ' : '') . $lastMsg['message_text'];
                    $friend['last_message_time'] = $lastMsg['created_at'];
                } else {
                    $friend['last_message_text'] = 'No hay mensajes todavía.';
                    $friend['last_message_time'] = null;
                }

                // Obtener conteo de no leídos
                $stmt_unread->execute([':friend_id' => $friendId, ':current_user_id' => $currentUserId]);
                $friend['unread_count'] = (int)$stmt_unread->fetchColumn();
            }

            // Ordenar amigos por la fecha del último mensaje (los más recientes primero)
            usort($friends, function($a, $b) {
                if ($a['last_message_time'] == $b['last_message_time']) {
                    return 0;
                }
                if ($a['last_message_time'] == null) return 1; // Los nulos al final
                if ($b['last_message_time'] == null) return -1; // Los nulos al final
                return ($a['last_message_time'] < $b['last_message_time']) ? 1 : -1;
            });

            $response['success'] = true;
            $response['conversations'] = $friends;
        
        } elseif ($action === 'get-chat-history') {
            
            $targetUserId = (int)($_POST['target_user_id'] ?? 0);
            if (empty($targetUserId)) {
                throw new Exception('js.api.invalidAction');
            }

            // 1. Marcar mensajes como leídos
            $stmt_mark_read = $pdo->prepare(
                "UPDATE chat_messages 
                 SET is_read = 1 
                 WHERE receiver_id = ? AND sender_id = ? AND is_read = 0"
            );
            $stmt_mark_read->execute([$currentUserId, $targetUserId]);
            
            // 2. Obtener historial de chat
            $stmt_history = $pdo->prepare(
                "SELECT * FROM chat_messages 
                 WHERE (sender_id = :current_user_id AND receiver_id = :target_user_id) 
                    OR (sender_id = :target_user_id AND receiver_id = :current_user_id)
                 ORDER BY created_at ASC
                 LIMIT 200" // Limitar a los últimos 200 mensajes
            );
            $stmt_history->execute([
                ':current_user_id' => $currentUserId,
                ':target_user_id' => $targetUserId
            ]);
            $messages = $stmt_history->fetchAll();

            $response['success'] = true;
            $response['messages'] = $messages;

        } elseif ($action === 'send-message') {
            
            $receiverId = (int)($_POST['receiver_id'] ?? 0);
            $messageText = trim($_POST['message_text'] ?? '');

            if (empty($receiverId) || empty($messageText)) {
                throw new Exception('chat.error.emptyMessage'); // TODO: Añadir a i18n
            }
            if (mb_strlen($messageText, 'UTF-8') > 1000) { // Límite de 1000 caracteres
                throw new Exception('chat.error.messageTooLong'); // TODO: Añadir a i18n
            }
            
            // 1. Insertar en la BD
            $stmt_insert = $pdo->prepare(
                "INSERT INTO chat_messages (sender_id, receiver_id, message_text) 
                 VALUES (?, ?, ?)"
            );
            $stmt_insert->execute([$currentUserId, $receiverId, $messageText]);
            $newMessageId = $pdo->lastInsertId();

            // 2. Obtener el mensaje recién insertado para enviarlo de vuelta
            $stmt_get = $pdo->prepare("SELECT * FROM chat_messages WHERE id = ?");
            $stmt_get->execute([$newMessageId]);
            $newMessage = $stmt_get->fetch();

            // 3. Notificar al servidor WebSocket
            notifyReceiverOfNewMessage($receiverId, $newMessage);

            $response['success'] = true;
            $response['newMessage'] = $newMessage;
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
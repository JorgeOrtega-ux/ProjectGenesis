<?php

include '../config/config.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'js.api.invalidAction'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'js.settings.errorNoSession';
    echo json_encode($response);
    exit;
}

$currentUserId = (int)$_SESSION['user_id'];

// --- ▼▼▼ INICIO DE FUNCIÓN HELPER (MODIFICADA) ▼▼▼ ---
/**
 * Envía una notificación PING a un usuario a través del servidor WebSocket.
 *
 * @param int $targetUserId El ID del usuario a notificar.
 */
function notifyUser($targetUserId) {
    try {
        // --- ¡Payload simplificado! Solo un ping. ---
        $post_data = json_encode([
            'target_user_id' => (int)$targetUserId,
            'payload'        => ['type' => 'new_notification_ping']
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
        // Loggear el error de notificación (no detener la ejecución principal)
        logDatabaseError($e, 'friend_handler - (ws_notify_fail)');
    }
}
// --- ▲▲▲ FIN DE FUNCIÓN HELPER (MODIFICADA) ▲▲▲ ---


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($submittedToken)) {
        $response['message'] = 'js.api.errorSecurityRefresh';
        echo json_encode($response);
        exit;
    }

    $action = $_POST['action'] ?? '';
    $targetUserId = (int)($_POST['target_user_id'] ?? 0);

    if ($action !== 'get-friends-list' && $action !== 'get-pending-requests' && ($targetUserId === 0 || $targetUserId === $currentUserId)) {
         $response['message'] = 'js.api.invalidAction';
         echo json_encode($response);
         exit;
    }
    
    $userId1 = min($currentUserId, $targetUserId);
    $userId2 = max($currentUserId, $targetUserId);

    try {
        if ($action === 'get-friends-list') {
            
            // --- ▼▼▼ INICIO DE MODIFICACIÓN (OBTENER ESTADO) ▼▼▼ ---
            
            $onlineUserIds = [];
            try {
                // 1. Consultar al servidor WebSocket quién está en línea
                $context = stream_context_create(['http' => ['timeout' => 1.0]]);
                $jsonResponse = @file_get_contents('http://127.0.0.1:8766/get-online-users', false, $context);
                if ($jsonResponse !== false) {
                    $data = json_decode($jsonResponse, true);
                    if (isset($data['status']) && $data['status'] === 'ok' && isset($data['online_users'])) {
                        // Crear un array asociativo para búsqueda rápida (ej. [123 => true])
                        $onlineUserIds = array_flip($data['online_users']);
                    }
                }
            } catch (Exception $e) {
                logDatabaseError($e, 'friend_handler - (ws_get_online_fail)');
            }
            
            // 2. Modificar SQL para incluir last_seen y u.uuid
            $defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";

            // --- ▼▼▼ ¡LÍNEA MODIFICADA! Se añadió u.uuid a la consulta SELECT. ▼▼▼ ---
            $stmt_friends = $pdo->prepare(
                "SELECT 
                    (CASE WHEN f.user_id_1 = ? THEN f.user_id_2 ELSE f.user_id_1 END) AS friend_id,
                    u.username, u.profile_image_url, u.role, u.last_seen, u.uuid
                FROM friendships f
                JOIN users u ON (CASE WHEN f.user_id_1 = ? THEN f.user_id_2 ELSE f.user_id_1 END) = u.id
                WHERE (f.user_id_1 = ? OR f.user_id_2 = ?) AND f.status = 'accepted'
                ORDER BY u.username ASC"
            );
            $stmt_friends->execute([$currentUserId, $currentUserId, $currentUserId, $currentUserId]);
            $friends = $stmt_friends->fetchAll();
            
            // 3. Combinar datos
            foreach ($friends as &$friend) {
                if (empty($friend['profile_image_url'])) {
                    $friend['profile_image_url'] = "https://ui-avatars.com/api/?name=" . urlencode($friend['username']) . "&size=100&background=e0e0e0&color=ffffff";
                }
                
                // Añadir los nuevos campos al array de respuesta
                $friend['is_online'] = isset($onlineUserIds[$friend['friend_id']]);
                $friend['last_seen'] = $friend['last_seen'];
                // $friend['uuid'] ya está incluido por el SELECT
            }
            // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

            $response['success'] = true;
            $response['friends'] = $friends;
        
        } elseif ($action === 'get-pending-requests') {
            
            // Esta acción ahora es manejada por 'notification_handler.php', 
            // pero la dejamos para no romper JS antiguos si los hubiera.
            $defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";

            $stmt_req = $pdo->prepare(
                "SELECT u.id AS user_id, u.username, u.profile_image_url 
                 FROM friendships f
                 JOIN users u ON f.action_user_id = u.id
                 WHERE (f.user_id_1 = ? OR f.user_id_2 = ?) 
                   AND f.status = 'pending'
                   AND f.action_user_id != ?
                 ORDER BY f.created_at DESC"
            );
            $stmt_req->execute([$currentUserId, $currentUserId, $currentUserId]);
            $requests = $stmt_req->fetchAll();

            foreach ($requests as &$req) {
                if (empty($req['profile_image_url'])) {
                    $req['profile_image_url'] = "https://ui-avatars.com/api/?name=" . urlencode($req['username']) . "&size=100&background=e0e0e0&color=ffffff";
                }
            }
            
            $response['success'] = true;
            $response['requests'] = $requests;
        
        } elseif ($action === 'send-request') {
            $stmt_check = $pdo->prepare("SELECT status FROM friendships WHERE user_id_1 = ? AND user_id_2 = ?");
            $stmt_check->execute([$userId1, $userId2]);
            if ($stmt_check->fetch()) {
                 throw new Exception('js.friends.errorGeneric'); 
            }
            
            $stmt_insert = $pdo->prepare(
                "INSERT INTO friendships (user_id_1, user_id_2, status, action_user_id) VALUES (?, ?, 'pending', ?)"
            );
            $stmt_insert->execute([$userId1, $userId2, $currentUserId]);

            // --- ▼▼▼ INICIO DE MODIFICACIÓN: GUARDAR NOTIFICACIÓN ▼▼▼ ---
            try {
                // Insertar en la nueva tabla de notificaciones
                $stmt_notify = $pdo->prepare(
                    "INSERT INTO user_notifications (user_id, actor_user_id, type, reference_id) 
                     VALUES (?, ?, 'friend_request', ?)"
                );
                // Notificar a $targetUserId, el actor es $currentUserId, la referencia es el ID del actor
                $stmt_notify->execute([$targetUserId, $currentUserId, $currentUserId]);

                // Enviar un PING genérico al WebSocket
                notifyUser($targetUserId);
                
            } catch (Exception $e) {
                logDatabaseError($e, 'friend_handler - send-request (ws_notify_fail)');
            }
            // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
            
            $response['success'] = true;
            $response['message'] = 'js.friends.requestSent';
            $response['newStatus'] = 'pending_sent';

        } elseif ($action === 'cancel-request' || $action === 'decline-request' || $action === 'remove-friend') {
            
            // --- ▼▼▼ INICIO DE MODIFICACIÓN: ELIMINAR NOTIFICACIÓN ▼▼▼ ---
            // Si cancelamos o rechazamos, también eliminamos la notificación pendiente
            if ($action === 'cancel-request' || $action === 'decline-request') {
                $stmt_delete_notify = $pdo->prepare(
                    "DELETE FROM user_notifications 
                     WHERE type = 'friend_request' 
                     AND ((user_id = ? AND actor_user_id = ?) OR (user_id = ? AND actor_user_id = ?))"
                );
                $stmt_delete_notify->execute([$currentUserId, $targetUserId, $targetUserId, $currentUserId]);
            }
            // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
            
            $stmt_delete = $pdo->prepare("DELETE FROM friendships WHERE user_id_1 = ? AND user_id_2 = ?");
            $stmt_delete->execute([$userId1, $userId2]);
            
            if ($stmt_delete->rowCount() > 0) {
                $response['success'] = true;
                if ($action === 'cancel-request') $response['message'] = 'js.friends.requestCanceled';
                elseif ($action === 'remove-friend') $response['message'] = 'js.friends.friendRemoved';
                else $response['message'] = 'js.friends.requestCanceled'; 
                
                $response['newStatus'] = 'not_friends';
            } else {
                 throw new Exception('js.friends.errorGeneric');
            }

        } elseif ($action === 'accept-request') {
            $stmt_check = $pdo->prepare("SELECT status, action_user_id FROM friendships WHERE user_id_1 = ? AND user_id_2 = ?");
            $stmt_check->execute([$userId1, $userId2]);
            $friendship = $stmt_check->fetch();
            
            if (!$friendship || $friendship['status'] !== 'pending' || $friendship['action_user_id'] == $currentUserId) {
                 throw new Exception('js.friends.errorGeneric');
            }
            
            $originalSenderId = (int)$friendship['action_user_id'];
            
            $stmt_update = $pdo->prepare("UPDATE friendships SET status = 'accepted', action_user_id = ? WHERE user_id_1 = ? AND user_id_2 = ?");
            $stmt_update->execute([$currentUserId, $userId1, $userId2]);
            
            // --- ▼▼▼ INICIO DE MODIFICACIÓN: ACTUALIZAR/INSERTAR NOTIFICACIÓN ▼▼▼ ---
            if ($originalSenderId !== $currentUserId) {
                try {
                    // 1. Borrar la notificación de "friend_request" original
                    $stmt_delete_notify = $pdo->prepare(
                        "DELETE FROM user_notifications 
                         WHERE user_id = ? AND actor_user_id = ? AND type = 'friend_request'"
                    );
                    $stmt_delete_notify->execute([$currentUserId, $originalSenderId]);
                    
                    // 2. Insertar una nueva notificación de "friend_accept" para el remitente original
                    $stmt_notify_accept = $pdo->prepare(
                        "INSERT INTO user_notifications (user_id, actor_user_id, type, reference_id)
                         VALUES (?, ?, 'friend_accept', ?)"
                    );
                    // Notificar a $originalSenderId, el actor es $currentUserId
                    $stmt_notify_accept->execute([$originalSenderId, $currentUserId, $currentUserId]);

                    // 3. Enviar un PING genérico al WebSocket
                    notifyUser($originalSenderId);

                } catch (Exception $e) {
                    logDatabaseError($e, 'friend_handler - accept-request (ws_notify_fail)');
                }
            }
            // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

            $response['success'] = true;
            $response['message'] = 'js.friends.requestAccepted';
            $response['newStatus'] = 'friends';
        }

    } catch (Exception $e) {
        if ($e instanceof PDOException) {
            logDatabaseError($e, 'friend_handler - ' . $action);
            $response['message'] = 'js.api.errorDatabase';
        } else {
            $response['message'] = $e->getMessage();
        }
    }
}

echo json_encode($response);
exit;
?>
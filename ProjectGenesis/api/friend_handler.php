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

// --- ▼▼▼ INICIO DE FUNCIÓN HELPER DE NOTIFICACIÓN ▼▼▼ ---
/**
 * Envía una notificación a un usuario a través del servidor WebSocket.
 *
 * @param int $targetUserId El ID del usuario a notificar.
 * @param array $payload El contenido de la notificación.
 */
function notifyUser($targetUserId, $payload) {
    try {
        $post_data = json_encode([
            'target_user_id' => (int)$targetUserId,
            'payload'        => $payload
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
// --- ▲▲▲ FIN DE FUNCIÓN HELPER ▲▲▲ ---


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
            
            $defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";

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
            
            foreach ($friends as &$friend) {
                if (empty($friend['profile_image_url'])) {
                    $friend['profile_image_url'] = "https://ui-avatars.com/api/?name=" . urlencode($friend['username']) . "&size=100&background=e0e0e0&color=ffffff";
                }
            }

            $response['success'] = true;
            $response['friends'] = $friends;
        
        } elseif ($action === 'get-pending-requests') {
            
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

            try {
                $sender_username = $_SESSION['username'] ?? 'Usuario';
                $sender_avatar = $_SESSION['profile_image_url'] ?? '';
                if (empty($sender_avatar)) {
                     $sender_avatar = "https://ui-avatars.com/api/?name=" . urlencode($sender_username) . "&size=100&background=e0e0e0&color=ffffff";
                }

                $ws_payload = [
                    'type' => 'friend_request_received',
                    'payload' => [
                        'user_id' => $currentUserId, 
                        'username' => $sender_username,
                        'profile_image_url' => $sender_avatar
                    ]
                ];
                
                // --- ▼▼▼ INVOCACIÓN DE FUNCIÓN HELPER ▼▼▼ ---
                notifyUser($targetUserId, $ws_payload);
                // --- ▲▲▲ FIN INVOCACIÓN ▲▲▲ ---
                
            } catch (Exception $e) {
                logDatabaseError($e, 'friend_handler - send-request (ws_notify_fail)');
            }
            
            $response['success'] = true;
            $response['message'] = 'js.friends.requestSent';
            $response['newStatus'] = 'pending_sent';

        } elseif ($action === 'cancel-request' || $action === 'decline-request' || $action === 'remove-friend') {
            
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
            
            // --- ▼▼▼ INICIO DE MODIFICACIÓN: NOTIFICAR ACEPTACIÓN ▼▼▼ ---
            $originalSenderId = (int)$friendship['action_user_id'];
            
            $stmt_update = $pdo->prepare("UPDATE friendships SET status = 'accepted', action_user_id = ? WHERE user_id_1 = ? AND user_id_2 = ?");
            $stmt_update->execute([$currentUserId, $userId1, $userId2]);
            
            // Notificar al usuario que envió la solicitud original
            if ($originalSenderId !== $currentUserId) {
                try {
                    $ws_payload = [
                        'type' => 'friend_request_accepted',
                        'payload' => [
                            'user_id'  => $currentUserId, // ID de quien aceptó
                            'username' => $_SESSION['username'] ?? 'Usuario'
                        ]
                    ];
                    notifyUser($originalSenderId, $ws_payload);
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
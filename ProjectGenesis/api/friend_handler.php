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
        logDatabaseError($e, 'friend_handler - (ws_notify_fail)');
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
    $targetUserId = (int)($_POST['target_user_id'] ?? 0);

    if ($action !== 'get-friends-list' && $action !== 'get-pending-requests' && $action !== 'block-user' && $action !== 'unblock-user' && ($targetUserId === 0 || $targetUserId === $currentUserId)) {
         $response['message'] = 'js.api.invalidAction';
         echo json_encode($response);
         exit;
    }
    
    $userId1 = min($currentUserId, $targetUserId);
    $userId2 = max($currentUserId, $targetUserId);

    try {
        if ($action === 'get-friends-list') {
            
            
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
                logDatabaseError($e, 'friend_handler - (ws_get_online_fail)');
            }
            
            $defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";

            $stmt_friends = $pdo->prepare(
                "SELECT 
                    (CASE WHEN f.user_id_1 = ? THEN f.user_id_2 ELSE f.user_id_1 END) AS friend_id,
                    u.username, u.profile_image_url, u.role, u.last_seen, u.uuid,
                    (EXISTS(SELECT 1 FROM user_blocks WHERE blocker_user_id = ? AND blocked_user_id = u.id)) AS is_blocked_by_me
                FROM friendships f
                JOIN users u ON (CASE WHEN f.user_id_1 = ? THEN f.user_id_2 ELSE f.user_id_1 END) = u.id
                WHERE (f.user_id_1 = ? OR f.user_id_2 = ?) AND f.status = 'accepted'
                ORDER BY u.username ASC"
            );
            
            $stmt_friends->execute([$currentUserId, $currentUserId, $currentUserId, $currentUserId, $currentUserId]);
            $friends = $stmt_friends->fetchAll();
            
            foreach ($friends as &$friend) {
                if (empty($friend['profile_image_url'])) {
                    $friend['profile_image_url'] = "https://ui-avatars.com/api/?name=" . urlencode($friend['username']) . "&size=100&background=e0e0e0&color=ffffff";
                }
                
                $friend['is_online'] = isset($onlineUserIds[$friend['friend_id']]);
                $friend['last_seen'] = $friend['last_seen'];
                $friend['is_blocked_by_me'] = (bool)$friend['is_blocked_by_me']; 
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
            
            // --- ▼▼▼ INICIO DE MODIFICACIÓN (TAREA 4) ▼▼▼ ---
            if (isset($_SESSION['restrictions']['CANNOT_SOCIAL'])) {
                throw new Exception('js.api.errorNoSocialPermission'); // Clave de traducción para "No tienes permiso para acciones sociales."
            }
            // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

            $stmt_block_check = $pdo->prepare("SELECT 1 FROM user_blocks WHERE (blocker_user_id = ? AND blocked_user_id = ?) OR (blocker_user_id = ? AND blocked_user_id = ?)");
            $stmt_block_check->execute([$currentUserId, $targetUserId, $targetUserId, $currentUserId]);
            if ($stmt_block_check->fetch()) {
                throw new Exception('js.chat.errorBlocked'); 
            }

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
                $stmt_notify = $pdo->prepare(
                    "INSERT INTO user_notifications (user_id, actor_user_id, type, reference_id) 
                     VALUES (?, ?, 'friend_request', ?)"
                );
                $stmt_notify->execute([$targetUserId, $currentUserId, $currentUserId]);

                $payload = [
                    'type'          => 'friend_status_update',
                    'actor_user_id' => $currentUserId,
                    'new_status'    => 'pending_received'
                ];
                notifyUser($targetUserId, $payload);
                
            } catch (Exception $e) {
                logDatabaseError($e, 'friend_handler - send-request (ws_notify_fail)');
            }
            
            $response['success'] = true;
            $response['message'] = 'js.friends.requestSent';
            $response['newStatus'] = 'pending_sent';

        } elseif ($action === 'cancel-request' || $action === 'decline-request') {
            
            $stmt_delete_notify = $pdo->prepare(
                "DELETE FROM user_notifications 
                 WHERE type = 'friend_request' 
                 AND ((user_id = ? AND actor_user_id = ?) OR (user_id = ? AND actor_user_id = ?))"
            );
            $stmt_delete_notify->execute([$currentUserId, $targetUserId, $targetUserId, $currentUserId]);
            
            $stmt_delete = $pdo->prepare(
                "DELETE FROM friendships 
                 WHERE user_id_1 = ? AND user_id_2 = ? AND status = 'pending'"
            );
            $stmt_delete->execute([$userId1, $userId2]);
            
            if ($stmt_delete->rowCount() > 0) {
                $response['success'] = true;
                $response['message'] = 'js.friends.requestCanceled';
                $response['newStatus'] = 'not_friends';

                $payload = [
                    'type'          => 'friend_status_update',
                    'actor_user_id' => $currentUserId,
                    'new_status'    => 'not_friends'
                ];
                notifyUser($targetUserId, $payload);

            } else {
                 throw new Exception('js.friends.errorGeneric');
            }

        } elseif ($action === 'remove-friend') {

            $stmt_delete = $pdo->prepare(
                "DELETE FROM friendships 
                 WHERE user_id_1 = ? AND user_id_2 = ? AND status = 'accepted'"
            );
            $stmt_delete->execute([$userId1, $userId2]);
            
            if ($stmt_delete->rowCount() > 0) {
                $response['success'] = true;
                $response['message'] = 'js.friends.friendRemoved';
                $response['newStatus'] = 'not_friends';

                $payload = [
                    'type'          => 'friend_status_update',
                    'actor_user_id' => $currentUserId,
                    'new_status'    => 'not_friends'
                ];
                notifyUser($targetUserId, $payload); 

            } else {
                 throw new Exception('js.friends.errorGeneric');
            }

        } elseif ($action === 'accept-request') {
            
            // --- ▼▼▼ INICIO DE MODIFICACIÓN (TAREA 4) ▼▼▼ ---
            if (isset($_SESSION['restrictions']['CANNOT_SOCIAL'])) {
                throw new Exception('js.api.errorNoSocialPermission'); // Clave de traducción para "No tienes permiso para acciones sociales."
            }
            // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

            $stmt_check = $pdo->prepare("SELECT status, action_user_id FROM friendships WHERE user_id_1 = ? AND user_id_2 = ?");
            $stmt_check->execute([$userId1, $userId2]);
            $friendship = $stmt_check->fetch();
            
            if (!$friendship || $friendship['status'] !== 'pending' || $friendship['action_user_id'] == $currentUserId) {
                 throw new Exception('js.friends.errorGeneric');
            }
            
            $originalSenderId = (int)$friendship['action_user_id'];
            
            $stmt_update = $pdo->prepare("UPDATE friendships SET status = 'accepted', action_user_id = ? WHERE user_id_1 = ? AND user_id_2 = ?");
            $stmt_update->execute([$currentUserId, $userId1, $userId2]);
            
            if ($originalSenderId !== $currentUserId) {
                try {
                    $stmt_delete_notify = $pdo->prepare(
                        "DELETE FROM user_notifications 
                         WHERE user_id = ? AND actor_user_id = ? AND type = 'friend_request'"
                    );
                    $stmt_delete_notify->execute([$currentUserId, $originalSenderId]);
                    
                    $stmt_notify_accept = $pdo->prepare(
                        "INSERT INTO user_notifications (user_id, actor_user_id, type, reference_id)
                         VALUES (?, ?, 'friend_accept', ?)"
                    );
                    $stmt_notify_accept->execute([$originalSenderId, $currentUserId, $currentUserId]);

                    $payload = [
                        'type'          => 'friend_status_update',
                        'actor_user_id' => $currentUserId,
                        'new_status'    => 'friends'
                    ];
                    notifyUser($originalSenderId, $payload);

                } catch (Exception $e) {
                    logDatabaseError($e, 'friend_handler - accept-request (ws_notify_fail)');
                }
            }

            $response['success'] = true;
            $response['message'] = 'js.friends.requestAccepted';
            $response['newStatus'] = 'friends';
        
        } elseif ($action === 'block-user') {
            $pdo->beginTransaction();
            
            $stmt_block = $pdo->prepare("INSERT IGNORE INTO user_blocks (blocker_user_id, blocked_user_id) VALUES (?, ?)");
            $stmt_block->execute([$currentUserId, $targetUserId]);
            
            $stmt_delete_friendship = $pdo->prepare(
                "DELETE FROM friendships 
                 WHERE (user_id_1 = ? AND user_id_2 = ?) OR (user_id_1 = ? AND user_id_2 = ?)"
            );
            $stmt_delete_friendship->execute([$currentUserId, $targetUserId, $targetUserId, $currentUserId]);

            $stmt_delete_notify = $pdo->prepare(
                "DELETE FROM user_notifications 
                 WHERE type = 'friend_request' 
                 AND ((user_id = ? AND actor_user_id = ?) OR (user_id = ? AND actor_user_id = ?))"
            );
            $stmt_delete_notify->execute([$currentUserId, $targetUserId, $targetUserId, $currentUserId]);
            
            $pdo->commit();
            
            $payload = [
                'type'          => 'friend_status_update',
                'actor_user_id' => $currentUserId,
                'new_status'    => 'not_friends'
            ];
            notifyUser($targetUserId, $payload);
            
            $response['success'] = true;
            $response['message'] = 'js.chat.userBlocked'; 
        
        } elseif ($action === 'unblock-user') {
            
            $stmt_unblock = $pdo->prepare("DELETE FROM user_blocks WHERE blocker_user_id = ? AND blocked_user_id = ?");
            $stmt_unblock->execute([$currentUserId, $targetUserId]);
            
            if ($stmt_unblock->rowCount() > 0) {
                $response['success'] = true;
                $response['message'] = 'js.chat.userUnblocked'; 
            } else {
                $response['success'] = true; 
                $response['message'] = 'js.chat.userUnblocked';
            }
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
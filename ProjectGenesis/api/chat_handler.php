<?php

include '../config/config.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'js.api.invalidAction'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'js.settings.errorNoSession';
    echo json_encode($response);
    exit;
}

$messagingServiceEnabled = $GLOBALS['site_settings']['messaging_service_enabled'] ?? '1';
$userRole = $_SESSION['role'] ?? 'user';
$isPrivilegedUser = in_array($userRole, ['moderator', 'administrator', 'founder']);

if ($messagingServiceEnabled === '0' && !$isPrivilegedUser) {
    $response['message'] = 'page.messaging_disabled.description'; 
    echo json_encode($response);
    exit; 
}


$currentUserId = (int)$_SESSION['user_id'];

$ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$MAX_SIZE_MB = 5; 
$MAX_SIZE_BYTES = $MAX_SIZE_MB * 1024 * 1024;
$MAX_CHAT_FILES = 4; 
define('CHAT_PAGE_SIZE', 30); 
define('CHAT_DELETE_LIMIT_HOURS', 72);
define('MAX_PINNED_CHATS', 3); 


function notifyUser($targetUserId, $payload)
{
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
        logDatabaseError($e, 'chat_handler - (ws_notify_fail)');
    }
}




function canSendMessage($pdo, $currentUserId, $receiverId) {
    if ($currentUserId == $receiverId) {
        return 'allowed';
    }
    
    // 1. Chequeo de bloqueo
    $stmt_block_check = $pdo->prepare("SELECT 1 FROM user_blocks WHERE (blocker_user_id = :user1 AND blocked_user_id = :user2) OR (blocker_user_id = :user2 AND blocked_user_id = :user1)");
    $stmt_block_check->execute([':user1' => $currentUserId, ':user2' => $receiverId]);
    if ($stmt_block_check->fetch()) {
        return 'blocked'; 
    }

    // 2. Chequear si el *receptor* tiene una restricción de mensajería
    $stmt_restriction = $pdo->prepare(
        "SELECT 1 FROM user_restrictions 
         WHERE user_id = ? 
         AND restriction_type = 'CANNOT_MESSAGE'
         AND (expires_at IS NULL OR expires_at > NOW())
         LIMIT 1"
    );
    $stmt_restriction->execute([$receiverId]);
    if ($stmt_restriction->fetch()) {
        return 'restricted'; 
    }


    // 3. Chequeo de privacidad
    $stmt_privacy = $pdo->prepare("SELECT message_privacy_level FROM user_preferences WHERE user_id = ?");
    $stmt_privacy->execute([$receiverId]);
    $privacy = $stmt_privacy->fetchColumn();
    
    if (!$privacy) {
        $privacy = 'all';
    }

    if ($privacy === 'none') {
        return 'privacy_none';
    }

    if ($privacy === 'friends') {
        $userId1 = min($currentUserId, $receiverId);
        $userId2 = max($currentUserId, $receiverId);
        
        $stmt_friend = $pdo->prepare("SELECT status FROM friendships WHERE user_id_1 = ? AND user_id_2 = ? AND status = 'accepted'");
        $stmt_friend->execute([$userId1, $userId2]);
        
        if (!$stmt_friend->fetch()) {
            return 'privacy_friends_only';
        }
    }
    
    return 'allowed';
}

function checkMessagePrivacy($pdo, $currentUserId, $receiverId) {
    
    // Comprobación de restricción de sesión
    if (isset($_SESSION['restrictions']['CANNOT_MESSAGE'])) {
        throw new Exception('js.api.errorNoMessagePermission'); 
    }

    $stmt_sender_privacy = $pdo->prepare("SELECT COALESCE(message_privacy_level, 'all') FROM user_preferences WHERE user_id = ?");
    $stmt_sender_privacy->execute([$currentUserId]);
    $senderPrivacy = $stmt_sender_privacy->fetchColumn();
    
    if ($senderPrivacy === 'none') {
        throw new Exception('js.chat.errorPrivacySenderBlocked'); 
    }
    
    $canSenderSendStatus = canSendMessage($pdo, $currentUserId, $receiverId);
    if ($canSenderSendStatus !== 'allowed') {
        switch ($canSenderSendStatus) {
            case 'blocked':
                throw new Exception('js.chat.errorBlocked');
            
            case 'restricted': 
                throw new Exception('js.chat.errorRestricted'); 

            case 'privacy_none':
                throw new Exception('js.chat.errorPrivacyNone'); 
            case 'privacy_friends_only':
                throw new Exception('js.chat.errorPrivacyFriendsOnly'); 
            default:
                throw new Exception('js.chat.errorBlocked'); 
        }
    }

    $canReceiverReplyStatus = canSendMessage($pdo, $receiverId, $currentUserId); 
    if ($canReceiverReplyStatus !== 'allowed') {
        throw new Exception('js.chat.errorPrivacyMutualBlocked');
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
                     AND cm.created_at > COALESCE((SELECT deleted_until FROM user_conversation_metadata WHERE user_id = :current_user_id AND conversation_user_id = f.friend_id), '1970-01-01')
                     ORDER BY cm.created_at DESC LIMIT 1) AS last_message,
                    (SELECT cm.created_at 
                     FROM chat_messages cm 
                     WHERE ((cm.sender_id = :current_user_id AND cm.receiver_id = f.friend_id) 
                        OR (cm.sender_id = f.friend_id AND cm.receiver_id = :current_user_id))
                     AND cm.status = 'active'
                     AND cm.created_at > COALESCE((SELECT deleted_until FROM user_conversation_metadata WHERE user_id = :current_user_id AND conversation_user_id = f.friend_id), '1970-01-01')
                     ORDER BY cm.created_at DESC LIMIT 1) AS last_message_time,
                    (SELECT COUNT(*) 
                     FROM chat_messages cm 
                     WHERE cm.sender_id = f.friend_id 
                       AND cm.receiver_id = :current_user_id 
                       AND cm.is_read = 0
                       AND cm.status = 'active'
                       AND cm.created_at > COALESCE((SELECT deleted_until FROM user_conversation_metadata WHERE user_id = :current_user_id AND conversation_user_id = f.friend_id), '1970-01-01')
                       ) AS unread_count,
                    (EXISTS(SELECT 1 FROM user_blocks WHERE (blocker_user_id = :current_user_id AND blocked_user_id = f.friend_id) OR (blocker_user_id = f.friend_id AND blocked_user_id = :current_user_id))) AS is_blocked_globally,
                    (EXISTS(SELECT 1 FROM user_blocks WHERE blocker_user_id = :current_user_id AND blocked_user_id = f.friend_id)) AS is_blocked_by_me,
                    cd.is_favorite,
                    cd.pinned_at,
                    cd.is_archived,
                    cd.deleted_until AS deleted_timestamp
                FROM (
                    SELECT user_id_2 AS friend_id FROM friendships WHERE user_id_1 = :current_user_id AND status = 'accepted'
                    UNION
                    SELECT user_id_1 AS friend_id FROM friendships WHERE user_id_2 = :current_user_id AND status = 'accepted'
                    UNION
                    SELECT receiver_id AS friend_id FROM chat_messages WHERE sender_id = :current_user_id
                    UNION
                    SELECT sender_id AS friend_id FROM chat_messages WHERE receiver_id = :current_user_id
                ) AS f
                JOIN users u ON f.friend_id = u.id
                LEFT JOIN user_conversation_metadata cd ON cd.user_id = :current_user_id AND cd.conversation_user_id = f.friend_id
                WHERE f.friend_id != :current_user_id
                HAVING last_message_time IS NOT NULL OR deleted_timestamp IS NULL
                ORDER BY cd.is_archived ASC, cd.pinned_at DESC, last_message_time DESC, u.username ASC"
            );
            
            $stmt_friends->execute([':current_user_id' => $currentUserId]);
            $friends = $stmt_friends->fetchAll();

            foreach ($friends as &$friend) {
                if (empty($friend['profile_image_url'])) {
                    $friend['profile_image_url'] = "https://ui-avatars.com/api/?name=" . urlencode($friend['username']) . "&size=100&background=e0e0e0&color=ffffff";
                }
                $friend['is_blocked_by_me'] = (bool)$friend['is_blocked_by_me'];
                $friend['is_blocked_globally'] = (bool)$friend['is_blocked_globally'];
                $friend['is_favorite'] = (bool)($friend['is_favorite'] ?? false);
                $friend['pinned_at'] = $friend['pinned_at'] ?? null;
                $friend['is_archived'] = (bool)($friend['is_archived'] ?? false);
            }

            $response['success'] = true;
            $response['conversations'] = $friends;
        

        } elseif ($action === 'get-total-unread-count') {
            
            $stmt_count = $pdo->prepare(
                "SELECT COUNT(cm.id) 
                 FROM chat_messages cm
                 LEFT JOIN user_conversation_metadata ucm ON cm.sender_id = ucm.conversation_user_id AND ucm.user_id = :current_user_id
                 WHERE cm.receiver_id = :current_user_id 
                   AND cm.is_read = 0 
                   AND cm.status = 'active'
                   AND cm.created_at > COALESCE(ucm.deleted_until, '1970-01-01')"
            );
            $stmt_count->execute([':current_user_id' => $currentUserId]);
            $totalUnread = (int)$stmt_count->fetchColumn();


            $response['success'] = true;
            $response['total_unread_count'] = $totalUnread;

        } elseif ($action === 'get-chat-history') {

            
            $beforeMessageId = (int)($_POST['before_message_id'] ?? 0);
            $target_id = (int)($_POST['target_id'] ?? 0);
            
            if ($target_id === 0) throw new Exception('js.api.invalidAction');

            
            $targetUserId = $target_id;
            
            $stmt_sender_privacy = $pdo->prepare("SELECT COALESCE(message_privacy_level, 'all') FROM user_preferences WHERE user_id = ?");
            $stmt_sender_privacy->execute([$currentUserId]);
            $senderPrivacy = $stmt_sender_privacy->fetchColumn();
            $canSenderSend = ($senderPrivacy !== 'none');

            $canSendToReceiverStatus = canSendMessage($pdo, $currentUserId, $targetUserId);
            $canReceiverReplyStatus = canSendMessage($pdo, $targetUserId, $currentUserId); 
            
            $isRestricted = isset($_SESSION['restrictions']['CANNOT_MESSAGE']);
            $canSendMessage = ($canSenderSend && $canSendToReceiverStatus === 'allowed' && $canReceiverReplyStatus === 'allowed' && !$isRestricted);
            $response['can_send_message'] = $canSendMessage;

            $response['send_message_error_key'] = null;
            if (!$canSendMessage) {
                if ($isRestricted) {
                    $response['send_message_error_key'] = 'js.api.errorNoMessagePermission';
                } elseif
                (!$canSenderSend) {
                    $response['send_message_error_key'] = 'js.chat.errorPrivacySenderBlocked';
                } elseif ($canReceiverReplyStatus !== 'allowed') {
                    $response['send_message_error_key'] = 'js.chat.errorPrivacyMutualBlocked';
                } elseif ($canSendToReceiverStatus !== 'allowed') {
                    switch ($canSendToReceiverStatus) {
                        case 'blocked':
                            $response['send_message_error_key'] = 'js.chat.errorBlocked';
                            break;
                        case 'restricted': 
                            $response['send_message_error_key'] = 'js.chat.errorRestricted'; 
                            break;
                        case 'privacy_none':
                            $response['send_message_error_key'] = 'js.chat.errorPrivacyNone';
                            break;
                        case 'privacy_friends_only':
                            $response['send_message_error_key'] = 'js.chat.errorPrivacyFriendsOnly';
                            break;
                        default:
                            $response['send_message_error_key'] = 'js.chat.errorBlocked'; 
                    }
                } else {
                    $response['send_message_error_key'] = 'js.chat.errorBlocked'; 
                }
            }
            
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
                    OR (cm.sender_id = :target_user_id AND cm.receiver_id = :current_user_id))
                 AND cm.created_at > COALESCE((SELECT deleted_until FROM user_conversation_metadata WHERE user_id = :current_user_id AND conversation_user_id = :target_user_id), '1970-01-01')
                 ";

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

            $stmt_total_count = $pdo->prepare(
                "SELECT COUNT(cm.id) 
                 FROM chat_messages cm
                 LEFT JOIN user_conversation_metadata ucm ON cm.sender_id = ucm.conversation_user_id AND ucm.user_id = :current_user_id
                 WHERE cm.receiver_id = :current_user_id 
                   AND cm.is_read = 0 
                   AND cm.status = 'active'
                   AND cm.created_at > COALESCE(ucm.deleted_until, '1970-01-01')"
            );
            $stmt_total_count->execute([':current_user_id' => $currentUserId]);
            $totalUnread = (int)$stmt_total_count->fetchColumn();

            $response['new_total_unread_count'] = $totalUnread; 

            

            $response['success'] = true;
            $response['messages'] = $messages;
            $response['message_count'] = count($messages);
            $response['limit'] = CHAT_PAGE_SIZE;
            

        } elseif ($action === 'send-message') {

            
            $target_id = (int)($_POST['target_id'] ?? 0);
            $messageText = trim($_POST['message_text'] ?? '');
            $replyToMessageId = (int)($_POST['reply_to_message_id'] ?? 0);
            $dbReplyToId = ($replyToMessageId > 0) ? $replyToMessageId : null;
            $uploadedFiles = $_FILES['attachments'] ?? [];
            $fileIds = [];

            if ($target_id === 0) {
                throw new Exception('js.api.invalidAction');
            }
            if (empty($messageText) && (empty($uploadedFiles['name'][0]) || $uploadedFiles['error'][0] !== UPLOAD_ERR_OK)) {
                throw new Exception('js.publication.errorEmpty'); 
            }

            $pdo->beginTransaction();

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
                        $currentUserId, $systemName, $originalName, $attachmentUrl, $mimeType, $fileSize
                    ]);
                    $fileIds[] = $pdo->lastInsertId();
                }
            }
            
            
            $receiverId = $target_id;

            checkMessagePrivacy($pdo, $currentUserId, $receiverId);
            
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
            
            $stmt_unarchive = $pdo->prepare(
                "UPDATE user_conversation_metadata 
                 SET is_archived = 0 
                 WHERE (user_id = :user1 AND conversation_user_id = :user2) 
                    OR (user_id = :user2 AND conversation_user_id = :user1)"
            );
            $stmt_unarchive->execute([':user1' => $currentUserId, ':user2' => $receiverId]);
            
            $stmt_count_unread = $pdo->prepare(
                "SELECT COUNT(cm.id) 
                 FROM chat_messages cm
                 LEFT JOIN user_conversation_metadata ucm ON cm.sender_id = ucm.conversation_user_id AND ucm.user_id = :receiver_id
                 WHERE cm.receiver_id = :receiver_id 
                   AND cm.sender_id = :current_user_id
                   AND cm.is_read = 0 
                   AND cm.status = 'active'
                   AND cm.created_at > COALESCE(ucm.deleted_until, '1970-01-01')"
            );
            $stmt_count_unread->execute([
                ':receiver_id' => $receiverId,
                ':current_user_id' => $currentUserId
            ]);
            $newUnreadCount = (int)$stmt_count_unread->fetchColumn();
            
            $newMessage['new_unread_count_for_receiver'] = $newUnreadCount;

            $payload = [
                'type' => 'new_chat_message',
                'payload' => $newMessage
            ];
            notifyUser($receiverId, $payload);
            
            
            
            $pdo->commit();

            $response['success'] = true;
            $response['message_sent'] = $newMessage;
            

        } elseif ($action === 'delete-message') {
            
            
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
        
        } elseif ($action === 'delete-chat') {
            
            $targetUserId = (int)($_POST['target_user_id'] ?? 0);
            if ($targetUserId === 0) {
                throw new Exception('js.api.invalidAction');
            }

            $stmt_delete = $pdo->prepare(
                "INSERT INTO user_conversation_metadata (user_id, conversation_user_id, deleted_until) 
                 VALUES (:user_id, :conversation_user_id, NOW())
                 ON DUPLICATE KEY UPDATE deleted_until = NOW()"
            );
            $stmt_delete->execute([
                ':user_id' => $currentUserId,
                ':conversation_user_id' => $targetUserId
            ]);
            
            $response['success'] = true;
            $response['message'] = 'js.chat.chatDeleted';
        
        } elseif ($action === 'toggle-favorite') {
            $targetUserId = (int)($_POST['target_user_id'] ?? 0);
            if ($targetUserId === 0) {
                throw new Exception('js.api.invalidAction');
            }

            $stmt_toggle = $pdo->prepare(
                "INSERT INTO user_conversation_metadata (user_id, conversation_user_id, is_favorite)
                 VALUES (:user_id, :conversation_user_id, 1)
                 ON DUPLICATE KEY UPDATE is_favorite = NOT is_favorite"
            );
            $stmt_toggle->execute([
                ':user_id' => $currentUserId,
                ':conversation_user_id' => $targetUserId
            ]);

            $stmt_get = $pdo->prepare("SELECT is_favorite FROM user_conversation_metadata WHERE user_id = ? AND conversation_user_id = ?");
            $stmt_get->execute([$currentUserId, $targetUserId]);
            $newIsFavorite = (bool)$stmt_get->fetchColumn();

            $response['success'] = true;
            $response['message'] = $newIsFavorite ? 'js.chat.favorited' : 'js.chat.unfavorited';
            $response['new_is_favorite'] = $newIsFavorite;

        } elseif ($action === 'toggle-pin-chat') {
            $targetUserId = (int)($_POST['target_user_id'] ?? 0);
            if ($targetUserId === 0) {
                throw new Exception('js.api.invalidAction');
            }

            $pdo->beginTransaction();

            $stmt_check = $pdo->prepare("SELECT pinned_at FROM user_conversation_metadata WHERE user_id = ? AND conversation_user_id = ?");
            $stmt_check->execute([$currentUserId, $targetUserId]);
            $pinnedAt = $stmt_check->fetchColumn();
            
            $is_unpinning = ($pinnedAt !== null);

            if ($is_unpinning) {
                $stmt_pin = $pdo->prepare(
                    "UPDATE user_conversation_metadata SET pinned_at = NULL WHERE user_id = ? AND conversation_user_id = ?"
                );
                $stmt_pin->execute([$currentUserId, $targetUserId]);
                
                $pdo->commit();
                $response['success'] = true;
                $response['message'] = 'js.chat.unpinned';
                $response['new_is_pinned'] = false;

            } else {
                $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM user_conversation_metadata WHERE user_id = ? AND pinned_at IS NOT NULL");
                $stmt_count->execute([$currentUserId]);
                $pinCount = (int)$stmt_count->fetchColumn();

                if ($pinCount >= MAX_PINNED_CHATS) {
                    throw new Exception('js.chat.pinLimitReached');
                }

                $stmt_pin = $pdo->prepare(
                    "INSERT INTO user_conversation_metadata (user_id, conversation_user_id, pinned_at)
                     VALUES (:user_id, :conversation_user_id, NOW())
                     ON DUPLICATE KEY UPDATE pinned_at = NOW()"
                );
                $stmt_pin->execute([
                    ':user_id' => $currentUserId,
                    ':conversation_user_id' => $targetUserId
                ]);
                
                $pdo->commit();
                $response['success'] = true;
                $response['message'] = 'js.chat.pinned';
                $response['new_is_pinned'] = true;
            }
            
        } elseif ($action === 'toggle-archive-chat') {
            $targetUserId = (int)($_POST['target_user_id'] ?? 0);
            if ($targetUserId === 0) {
                throw new Exception('js.api.invalidAction');
            }

            $stmt_toggle = $pdo->prepare(
                "INSERT INTO user_conversation_metadata (user_id, conversation_user_id, is_archived, pinned_at)
                 VALUES (:user_id, :conversation_user_id, 1, NULL)
                 ON DUPLICATE KEY UPDATE 
                    is_archived = NOT is_archived,
                    pinned_at = CASE 
                                    WHEN NOT is_archived = 1 THEN NULL
                                    ELSE pinned_at
                                END"
            );
            $stmt_toggle->execute([
                ':user_id' => $currentUserId,
                ':conversation_user_id' => $targetUserId
            ]);

            $stmt_get = $pdo->prepare("SELECT is_archived FROM user_conversation_metadata WHERE user_id = ? AND conversation_user_id = ?");
            $stmt_get->execute([$currentUserId, $targetUserId]);
            $newIsArchived = (bool)$stmt_get->fetchColumn();

            $response['success'] = true;
            $response['message'] = $newIsArchived ? 'js.chat.archived' : 'js.chat.unarchived';
            $response['new_is_archived'] = $newIsArchived;
        
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        if ($e instanceof PDOException) {
            logDatabaseError($e, 'chat_handler - ' . $action);
            $response['message'] = 'js.api.errorDatabase';
        } else {
            $msg = $e->getMessage();
            $response['message'] = $msg;
            if ($msg === 'js.chat.errorPrivacyNone' || $msg === 'js.chat.errorPrivacyFriendsOnly') {
            }
            elseif (!isset($response['data'])) {
                $response['data'] = null;
            }
        }
    }
}

echo json_encode($response);
exit;
?>
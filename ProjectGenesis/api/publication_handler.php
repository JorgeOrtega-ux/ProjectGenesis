<?php

include '../config/config.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'js.api.invalidAction'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'js.settings.errorNoSession';
    echo json_encode($response);
    exit;
}

$userId = (int)$_SESSION['user_id'];

$MAX_FILES = 4;
$ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$MAX_SIZE_MB = (int)($GLOBALS['site_settings']['avatar_max_size_mb'] ?? 2);
$MAX_SIZE_BYTES = $MAX_SIZE_MB * 1024 * 1024;
define('MAX_POST_LENGTH', (int)($GLOBALS['site_settings']['max_post_length'] ?? 1000));



function notifyUser($targetUserId)
{
    try {
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
        logDatabaseError($e, 'publication_handler - (ws_notify_fail)');
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
        if ($action === 'create-post') {

            // --- ▼▼▼ INICIO DE MODIFICACIÓN (TAREA 4) ▼▼▼ ---
            if (isset($_SESSION['restrictions']['CANNOT_PUBLISH'])) {
                throw new Exception('js.api.errorNoPublishPermission'); // Clave de traducción para "No tienes permiso para publicar."
            }
            // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

            $pdo->beginTransaction();

            $communityId = $_POST['community_id'] ?? null;
            $dbCommunityId = null; 

            if (!empty($communityId) && is_numeric($communityId)) {
                $dbCommunityId = (int)$communityId;

                $stmt_check_member = $pdo->prepare("SELECT id FROM user_communities WHERE user_id = ? AND community_id = ?");
                $stmt_check_member->execute([$userId, $dbCommunityId]);
                if (!$stmt_check_member->fetch()) {
                    throw new Exception('js.api.errorServer'); 
                }
            }

            $postType = $_POST['post_type'] ?? 'post';

            $privacyLevel = $_POST['privacy_level'] ?? 'public';
            $allowedPrivacy = ['public', 'friends', 'private'];
            if (!in_array($privacyLevel, $allowedPrivacy)) {
                $privacyLevel = 'public';
            }

            $title = trim($_POST['title'] ?? '');
            if (empty($title)) {
                $title = null;
            }

            $textContent = trim($_POST['text_content'] ?? '');
            $pollQuestion = trim($_POST['poll_question'] ?? '');
            $pollOptionsJSON = $_POST['poll_options'] ?? '[]';


            $uploadedFiles = $_FILES['attachments'] ?? [];
            $fileIds = [];


            if ($postType === 'poll') {
                $pollOptions = json_decode($pollOptionsJSON, true);
                if (empty($pollQuestion)) {
                    throw new Exception('js.publication.errorPollQuestion');
                }
                if (mb_strlen($pollQuestion, 'UTF-8') > MAX_POST_LENGTH) {
                    throw new Exception('js.publication.errorPollTooLong');
                }
                if (empty($pollOptions) || count($pollOptions) < 2) {
                    throw new Exception('js.publication.errorPollOptions');
                }
                $textContent = $pollQuestion;
                $title = null;
            } elseif ($postType === 'post') {
                if (mb_strlen($textContent, 'UTF-8') > MAX_POST_LENGTH) {
                    throw new Exception('js.publication.errorPostTooLong');
                }
                if (empty($textContent) && empty($uploadedFiles['name'][0]) && empty($title)) {
                    throw new Exception('js.publication.errorEmpty');
                }
            } else {
                throw new Exception('js.api.invalidAction');
            }


            if ($postType === 'post' && !empty($uploadedFiles['name'][0])) {
                $uploadDir = dirname(__DIR__) . '/assets/uploads/publications';
                if (!is_dir($uploadDir)) {
                    if (!@mkdir($uploadDir, 0755, true)) {
                        throw new Exception('js.api.errorServer');
                    }
                }

                $fileCount = count($uploadedFiles['name']);
                if ($fileCount > $MAX_FILES) {
                    throw new Exception('js.publication.errorFileCount');
                }

                $stmt_insert_file = $pdo->prepare(
                    "INSERT INTO publication_files (user_id, community_id, file_name_system, file_name_original, public_url, file_type, file_size)
                     VALUES (?, ?, ?, ?, ?, ?, ?)"
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
                    $safeName = preg_replace("/[^a-zA-Z0-9-_\.]/", '', pathinfo($originalName, PATHINFO_FILENAME));
                    $systemName = "user-{$userId}-" . time() . "-{$safeName}.{$extension}";
                    $filePath = $uploadDir . '/' . $systemName;
                    $publicUrl = $basePath . '/assets/uploads/publications/' . $systemName;

                    if (!move_uploaded_file($tmpName, $filePath)) {
                        throw new Exception('js.api.errorServer');
                    }

                    $stmt_insert_file->execute([
                        $userId,
                        $dbCommunityId,
                        $systemName,
                        $originalName,
                        $publicUrl,
                        $mimeType,
                        $fileSize
                    ]);

                    $fileIds[] = $pdo->lastInsertId();
                }
            }

            $stmt_insert_post = $pdo->prepare(
                "INSERT INTO community_publications (community_id, user_id, title, text_content, post_type, post_status, privacy_level)
                 VALUES (?, ?, ?, ?, ?, 'active', ?)"
            );
            $stmt_insert_post->execute([$dbCommunityId, $userId, $title, $textContent, $postType, $privacyLevel]);

            $publicationId = $pdo->lastInsertId();

            if ($postType === 'post' && !empty($fileIds)) {
                $stmt_link_files = $pdo->prepare(
                    "INSERT INTO publication_attachments (publication_id, file_id, sort_order)
                     VALUES (?, ?, ?)"
                );
                foreach ($fileIds as $index => $fileId) {
                    $stmt_link_files->execute([$publicationId, $fileId, $index]);
                }
            } elseif ($postType === 'poll' && !empty($pollOptions)) {
                $stmt_insert_option = $pdo->prepare(
                    "INSERT INTO poll_options (publication_id, option_text) VALUES (?, ?)"
                );
                foreach ($pollOptions as $optionText) {
                    if (!empty(trim($optionText))) {
                        $stmt_insert_option->execute([$publicationId, trim($optionText)]);
                    }
                }
            }


            $pdo->commit();

            $response['success'] = true;
            $response['message'] = 'js.publication.success';
        } elseif ($action === 'vote-poll') {

            $publicationId = (int)($_POST['publication_id'] ?? 0);
            $optionId = (int)($_POST['poll_option_id'] ?? 0);

            if (empty($publicationId) || empty($optionId)) {
                throw new Exception('js.api.invalidAction');
            }

            $pdo->beginTransaction();

            try {
                $stmt_check_poll = $pdo->prepare("SELECT post_type FROM community_publications WHERE id = ? AND post_status = 'active'");
                $stmt_check_poll->execute([$publicationId]);
                $postType = $stmt_check_poll->fetchColumn();

                if ($postType !== 'poll') {
                    throw new Exception('js.api.invalidAction');
                }

                $stmt_check_option = $pdo->prepare("SELECT id FROM poll_options WHERE id = ? AND publication_id = ?");
                $stmt_check_option->execute([$optionId, $publicationId]);
                if (!$stmt_check_option->fetch()) {
                    throw new Exception('js.api.invalidAction');
                }

                $stmt_check_vote = $pdo->prepare("SELECT id FROM poll_votes WHERE publication_id = ? AND user_id = ?");
                $stmt_check_vote->execute([$publicationId, $userId]);
                if ($stmt_check_vote->fetch()) {
                    throw new Exception('js.publication.errorAlreadyVoted');
                }

                $stmt_insert_vote = $pdo->prepare("INSERT INTO poll_votes (publication_id, poll_option_id, user_id) VALUES (?, ?, ?)");
                $stmt_insert_vote->execute([$publicationId, $optionId, $userId]);

                $pdo->commit();

                $stmt_results = $pdo->prepare(
                    "SELECT 
                        po.id, 
                        po.option_text, 
                        COUNT(pv.id) AS vote_count
                    FROM poll_options po
                    LEFT JOIN poll_votes pv ON po.id = pv.poll_option_id
                    WHERE po.publication_id = ?
                    GROUP BY po.id, po.option_text
                    ORDER BY po.id ASC"
                );
                $stmt_results->execute([$publicationId]);
                $results = $stmt_results->fetchAll();

                $response['success'] = true;
                $response['results'] = $results;
                $response['totalVotes'] = array_sum(array_column($results, 'vote_count'));
            } catch (Exception $e) {
                $pdo->rollBack();
                if ($e->getMessage() === 'js.publication.errorAlreadyVoted') {
                    $response['message'] = $e->getMessage();
                } else {
                    throw $e;
                }
            }
        } elseif ($action === 'like-toggle') {

            $publicationId = (int)($_POST['publication_id'] ?? 0);
            if (empty($publicationId)) {
                throw new Exception('js.api.invalidAction');
            }

            $stmt_check = $pdo->prepare("SELECT id FROM publication_likes WHERE user_id = ? AND publication_id = ?");
            $stmt_check->execute([$userId, $publicationId]);
            $likeExists = $stmt_check->fetch();

            if ($likeExists) {
                $stmt_delete = $pdo->prepare("DELETE FROM publication_likes WHERE id = ?");
                $stmt_delete->execute([$likeExists['id']]);
                $response['userHasLiked'] = false;
            } else {
                $stmt_insert = $pdo->prepare("INSERT INTO publication_likes (user_id, publication_id) VALUES (?, ?)");
                $stmt_insert->execute([$userId, $publicationId]);
                $response['userHasLiked'] = true;

                $stmt_get_author = $pdo->prepare("SELECT user_id FROM community_publications WHERE id = ? AND post_status = 'active'");
                $stmt_get_author->execute([$publicationId]);
                $postAuthorId = (int)$stmt_get_author->fetchColumn();

                if ($postAuthorId > 0 && $postAuthorId !== $userId) {
                    $stmt_notify = $pdo->prepare(
                        "INSERT INTO user_notifications (user_id, actor_user_id, type, reference_id)
                         VALUES (?, ?, 'like', ?)"
                    );
                    $stmt_notify->execute([$postAuthorId, $userId, $publicationId]);
                    notifyUser($postAuthorId);
                }
            }

            $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM publication_likes WHERE publication_id = ?");
            $stmt_count->execute([$publicationId]);
            $response['newLikeCount'] = $stmt_count->fetchColumn();
            $response['success'] = true;
        } elseif ($action === 'bookmark-toggle') {

            $publicationId = (int)($_POST['publication_id'] ?? 0);
            if (empty($publicationId)) {
                throw new Exception('js.api.invalidAction');
            }

            $stmt_check = $pdo->prepare("SELECT id FROM publication_bookmarks WHERE user_id = ? AND publication_id = ?");
            $stmt_check->execute([$userId, $publicationId]);
            $bookmarkExists = $stmt_check->fetch();

            if ($bookmarkExists) {
                $stmt_delete = $pdo->prepare("DELETE FROM publication_bookmarks WHERE id = ?");
                $stmt_delete->execute([$bookmarkExists['id']]);
                $response['userHasBookmarked'] = false;
            } else {
                $stmt_insert = $pdo->prepare("INSERT INTO publication_bookmarks (user_id, publication_id) VALUES (?, ?)");
                $stmt_insert->execute([$userId, $publicationId]);
                $response['userHasBookmarked'] = true;
            }

            $response['success'] = true;
        } elseif ($action === 'post-comment') {

            // --- ▼▼▼ INICIO DE MODIFICACIÓN (TAREA 4) ▼▼▼ ---
            if (isset($_SESSION['restrictions']['CANNOT_COMMENT'])) {
                throw new Exception('js.api.errorNoCommentPermission'); // Clave de traducción para "No tienes permiso para comentar."
            }
            // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

            $publicationId = (int)($_POST['publication_id'] ?? 0);
            $parentCommentId = !empty($_POST['parent_comment_id']) ? (int)$_POST['parent_comment_id'] : null;
            $commentText = trim($_POST['comment_text'] ?? '');

            if (empty($publicationId) || empty($commentText)) {
                throw new Exception('js.publication.errorCommentEmpty');
            }

            $targetUserId = 0;
            $notificationType = 'comment';
            $referenceId = $publicationId;

            if ($parentCommentId) {
                $stmt_check_parent = $pdo->prepare("SELECT user_id, parent_comment_id FROM publication_comments WHERE id = ?");
                $stmt_check_parent->execute([$parentCommentId]);
                $parentComment = $stmt_check_parent->fetch();

                if (!$parentComment) {
                    throw new Exception('js.api.errorServer');
                }
                if ($parentComment['parent_comment_id'] !== null) {
                    throw new Exception('js.publication.errorMaxDepth');
                }
                $targetUserId = (int)$parentComment['user_id'];
                $notificationType = 'reply';
                $referenceId = $publicationId;
            } else {
                $stmt_get_author = $pdo->prepare("SELECT user_id FROM community_publications WHERE id = ? AND post_status = 'active'");
                $stmt_get_author->execute([$publicationId]);
                $targetUserId = (int)$stmt_get_author->fetchColumn();
                $notificationType = 'comment';
                $referenceId = $publicationId;
            }

            $stmt_insert = $pdo->prepare(
                "INSERT INTO publication_comments (user_id, publication_id, parent_comment_id, comment_text) 
                 VALUES (?, ?, ?, ?)"
            );
            $stmt_insert->execute([$userId, $publicationId, $parentCommentId, $commentText]);
            $newCommentId = $pdo->lastInsertId();

            $stmt_get = $pdo->prepare(
                "SELECT c.*, u.username, u.profile_image_url, u.role 
                 FROM publication_comments c 
                 JOIN users u ON c.user_id = u.id 
                 WHERE c.id = ?"
            );
            $stmt_get->execute([$newCommentId]);
            $newComment = $stmt_get->fetch();

            $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM publication_comments WHERE publication_id = ?");
            $stmt_count->execute([$publicationId]);

            if ($targetUserId > 0 && $targetUserId !== $userId) {
                $stmt_notify = $pdo->prepare(
                    "INSERT INTO user_notifications (user_id, actor_user_id, type, reference_id)
                     VALUES (?, ?, ?, ?)"
                );
                $stmt_notify->execute([$targetUserId, $userId, $notificationType, $referenceId]);
                notifyUser($targetUserId);
            }

            $response['success'] = true;
            $response['newComment'] = $newComment;
            $response['newCommentCount'] = $stmt_count->fetchColumn();
        } elseif ($action === 'get-comments') {

            $publicationId = (int)($_POST['publication_id'] ?? 0);
            if (empty($publicationId)) {
                throw new Exception('js.api.invalidAction');
            }

            $stmt_get_l1 = $pdo->prepare(
                "SELECT 
                    c.*, 
                    u.username, 
                    u.profile_image_url,
                    u.role,
                    (SELECT COUNT(*) FROM publication_comments r WHERE r.parent_comment_id = c.id) AS reply_count
                 FROM publication_comments c 
                 JOIN users u ON c.user_id = u.id 
                 WHERE c.publication_id = ? 
                 AND c.parent_comment_id IS NULL 
                 ORDER BY c.created_at ASC"
            );
            $stmt_get_l1->execute([$publicationId]);
            $comments = $stmt_get_l1->fetchAll();

            $response['success'] = true;
            $response['comments'] = $comments;
        } elseif ($action === 'get-replies') {

            $parentCommentId = (int)($_POST['parent_comment_id'] ?? 0);
            if (empty($parentCommentId)) {
                throw new Exception('js.api.invalidAction');
            }

            $stmt_get_l2 = $pdo->prepare(
                "SELECT c.*, u.username, u.profile_image_url, u.role 
                 FROM publication_comments c 
                 JOIN users u ON c.user_id = u.id 
                 WHERE c.parent_comment_id = ? 
                 ORDER BY c.created_at ASC"
            );
            $stmt_get_l2->execute([$parentCommentId]);
            $replies = $stmt_get_l2->fetchAll();

            $response['success'] = true;
            $response['replies'] = $replies;
        } elseif ($action === 'delete-post') {

            $publicationId = (int)($_POST['publication_id'] ?? 0);
            if (empty($publicationId)) {
                throw new Exception('js.api.invalidAction');
            }

            $stmt_delete = $pdo->prepare(
                "UPDATE community_publications 
                 SET post_status = 'deleted' 
                 WHERE id = ? AND user_id = ?"
            );
            $stmt_delete->execute([$publicationId, $userId]);

            if ($stmt_delete->rowCount() > 0) {
                $response['success'] = true;
                $response['message'] = 'js.publication.postDeleted';
            } else {
                throw new Exception('js.api.errorServer');
            }
        } elseif ($action === 'update-post-privacy') {

            $publicationId = (int)($_POST['publication_id'] ?? 0);
            $newPrivacy = $_POST['new_privacy_level'] ?? '';

            if (empty($publicationId)) {
                throw new Exception('js.api.invalidAction');
            }

            $allowedPrivacy = ['public', 'friends', 'private'];
            if (!in_array($newPrivacy, $allowedPrivacy)) {
                throw new Exception('js.api.invalidAction');
            }

            $stmt_update = $pdo->prepare(
                "UPDATE community_publications 
                 SET privacy_level = ? 
                 WHERE id = ? AND user_id = ?"
            );
            $stmt_update->execute([$newPrivacy, $publicationId, $userId]);

            if ($stmt_update->rowCount() > 0) {
                $response['success'] = true;
                $response['message'] = 'js.publication.privacyUpdated';
                $response['newPrivacy'] = $newPrivacy;
            } else {
                throw new Exception('js.api.errorServer');
            }
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        if ($e instanceof PDOException) {
            logDatabaseError($e, 'publication_handler - ' . $action);
            $response['message'] = 'js.api.errorDatabase';
        } else {
            $response['message'] = $e->getMessage();
            if (!isset($response['data'])) {
                $response['data'] = null;
            }
            if ($response['message'] === 'js.publication.errorPostTooLong' || $response['message'] === 'js.publication.errorPollTooLong') {
                $response['data'] = ['length' => MAX_POST_LENGTH];
            }
        }
    }
}
echo json_encode($response);
exit;

?>
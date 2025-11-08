<?php

include '../config/config.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'js.api.invalidAction'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'js.settings.errorNoSession';
    echo json_encode($response);
    exit;
}

$userId = $_SESSION['user_id'];

$MAX_FILES = 4;
$ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$MAX_SIZE_MB = (int)($GLOBALS['site_settings']['avatar_max_size_mb'] ?? 2);
$MAX_SIZE_BYTES = $MAX_SIZE_MB * 1024 * 1024;

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
            
            $pdo->beginTransaction();
            
            $communityId = $_POST['community_id'] ?? null;
            $postType = $_POST['post_type'] ?? 'post'; 
            $textContent = trim($_POST['text_content'] ?? ''); 
            $pollQuestion = trim($_POST['poll_question'] ?? ''); 
            $pollOptionsJSON = $_POST['poll_options'] ?? '[]'; 
            
            $uploadedFiles = $_FILES['attachments'] ?? [];
            $fileIds = [];

            if (empty($communityId)) {
                throw new Exception('js.publication.errorNoCommunity');
            }
            
            $stmt_check_member = $pdo->prepare("SELECT id FROM user_communities WHERE user_id = ? AND community_id = ?");
            $stmt_check_member->execute([$userId, $communityId]);
            if (!$stmt_check_member->fetch()) {
                 throw new Exception('js.api.errorServer'); 
            }
            
            $dbCommunityId = (int)$communityId;

            if ($postType === 'poll') {
                $pollOptions = json_decode($pollOptionsJSON, true);
                if (empty($pollQuestion)) {
                    throw new Exception('js.publication.errorPollQuestion'); 
                }
                if (empty($pollOptions) || count($pollOptions) < 2) {
                     throw new Exception('js.publication.errorPollOptions'); 
                }
                $textContent = $pollQuestion;

            } elseif ($postType === 'post') {
                 if (empty($textContent) && empty($uploadedFiles['name'][0])) {
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
                        $userId, $dbCommunityId, $systemName, $originalName, $publicUrl, $mimeType, $fileSize
                    ]);
                    
                    $fileIds[] = $pdo->lastInsertId();
                }
            }

            $stmt_insert_post = $pdo->prepare(
                "INSERT INTO community_publications (community_id, user_id, text_content, post_type)
                 VALUES (?, ?, ?, ?)"
            );
            $stmt_insert_post->execute([$dbCommunityId, $userId, $textContent, $postType]);
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
                $stmt_check_poll = $pdo->prepare("SELECT post_type FROM community_publications WHERE id = ?");
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
            }
            
            $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM publication_likes WHERE publication_id = ?");
            $stmt_count->execute([$publicationId]);
            $response['newLikeCount'] = $stmt_count->fetchColumn();
            $response['success'] = true;

        // --- ▼▼▼ INICIO DE NUEVO BLOQUE ▼▼▼ ---
        } elseif ($action === 'bookmark-toggle') {
            $publicationId = (int)($_POST['publication_id'] ?? 0);
            if (empty($publicationId)) {
                throw new Exception('js.api.invalidAction');
            }
            
            $stmt_check = $pdo->prepare("SELECT id FROM publication_bookmarks WHERE user_id = ? AND publication_id = ?");
            $stmt_check->execute([$userId, $publicationId]);
            $bookmarkExists = $stmt_check->fetch();
            
            if ($bookmarkExists) {
                // Ya existe, eliminarlo
                $stmt_delete = $pdo->prepare("DELETE FROM publication_bookmarks WHERE id = ?");
                $stmt_delete->execute([$bookmarkExists['id']]);
                $response['userHasBookmarked'] = false;
            } else {
                // No existe, crearlo
                $stmt_insert = $pdo->prepare("INSERT INTO publication_bookmarks (user_id, publication_id) VALUES (?, ?)");
                $stmt_insert->execute([$userId, $publicationId]);
                $response['userHasBookmarked'] = true;
            }
            
            $response['success'] = true;
        // --- ▲▲▲ FIN DE NUEVO BLOQUE ▲▲▲ ---

        } elseif ($action === 'post-comment') {
            $publicationId = (int)($_POST['publication_id'] ?? 0);
            $parentCommentId = !empty($_POST['parent_comment_id']) ? (int)$_POST['parent_comment_id'] : null;
            $commentText = trim($_POST['comment_text'] ?? '');

            if (empty($publicationId) || empty($commentText)) {
                throw new Exception('js.publication.errorCommentEmpty');
            }

            if ($parentCommentId) {
                $stmt_check_parent = $pdo->prepare("SELECT parent_comment_id FROM publication_comments WHERE id = ?");
                $stmt_check_parent->execute([$parentCommentId]);
                $parentParentId = $stmt_check_parent->fetchColumn();
                
                if ($parentParentId !== null) {
                    throw new Exception('js.publication.errorMaxDepth'); 
                }
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
        }
    }
}

echo json_encode($response);
exit;
?>
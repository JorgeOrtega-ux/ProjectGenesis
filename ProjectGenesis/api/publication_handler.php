<?php
// FILE: api/publication_handler.php
// (VERSÍON CORREGIDA Y AMPLIADA PARA ENCUESTAS)

include '../config/config.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'js.api.invalidAction'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'js.settings.errorNoSession';
    echo json_encode($response);
    exit;
}

$userId = $_SESSION['user_id'];

// Constantes de subida
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
            
            // --- 1. Validación de Datos Comunes ---
            $communityId = $_POST['community_id'] ?? null;
            $postType = $_POST['post_type'] ?? 'post'; // 'post' o 'poll'
            $textContent = trim($_POST['text_content'] ?? ''); // Para 'post'
            $pollQuestion = trim($_POST['poll_question'] ?? ''); // Para 'poll'
            $pollOptionsJSON = $_POST['poll_options'] ?? '[]'; // Para 'poll'
            
            $uploadedFiles = $_FILES['attachments'] ?? [];
            $fileIds = [];

            if (empty($communityId)) {
                throw new Exception('js.publication.errorNoCommunity');
            }
            
            // --- 2. Validación de Pertenencia a la Comunidad (¡IMPORTANTE!) ---
            $stmt_check_member = $pdo->prepare("SELECT id FROM user_communities WHERE user_id = ? AND community_id = ?");
            $stmt_check_member->execute([$userId, $communityId]);
            if (!$stmt_check_member->fetch()) {
                 throw new Exception('js.api.errorServer'); // Error genérico, no debería pasar
            }
            
            $dbCommunityId = (int)$communityId;

            // --- 3. Validación Específica por Tipo ---
            if ($postType === 'poll') {
                $pollOptions = json_decode($pollOptionsJSON, true);
                if (empty($pollQuestion)) {
                    throw new Exception('js.publication.errorPollQuestion'); // Nueva clave i18n
                }
                if (empty($pollOptions) || count($pollOptions) < 2) {
                     throw new Exception('js.publication.errorPollOptions'); // Nueva clave i18n
                }
                // Para encuestas, el texto principal es la pregunta
                $textContent = $pollQuestion;

            } elseif ($postType === 'post') {
                 if (empty($textContent) && empty($uploadedFiles['name'][0])) {
                    throw new Exception('js.publication.errorEmpty');
                }
            } else {
                throw new Exception('js.api.invalidAction');
            }


            // --- 4. Procesar Archivos Adjuntos (si existen) ---
            if ($postType === 'post' && !empty($uploadedFiles['name'][0])) {
                $uploadDir = dirname(__DIR__) . '/assets/uploads/publications';
                if (!is_dir($uploadDir)) {
                    if (!@mkdir($uploadDir, 0755, true)) {
                        throw new Exception('js.api.errorServer'); // Error de permisos
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
                        throw new Exception('js.api.errorServer'); // Error al mover archivo
                    }

                    $stmt_insert_file->execute([
                        $userId, $dbCommunityId, $systemName, $originalName, $publicUrl, $mimeType, $fileSize
                    ]);
                    
                    $fileIds[] = $pdo->lastInsertId();
                }
            }

            // --- 5. Guardar la Publicación (Post o Encuesta) ---
            $stmt_insert_post = $pdo->prepare(
                "INSERT INTO community_publications (community_id, user_id, text_content, post_type)
                 VALUES (?, ?, ?, ?)"
            );
            // $textContent se define arriba (sea el post o la pregunta de la encuesta)
            $stmt_insert_post->execute([$dbCommunityId, $userId, $textContent, $postType]);
            $publicationId = $pdo->lastInsertId();

            // --- 6. Vincular Archivos (si es post) o Guardar Opciones (si es encuesta) ---
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

            // --- 7. Confirmar Transacción ---
            $pdo->commit();
            
            $response['success'] = true;
            $response['message'] = 'js.publication.success';
        
        } elseif ($action === 'vote-poll') {
            
            $publicationId = (int)($_POST['publication_id'] ?? 0);
            // --- ▼▼▼ ¡ESTA ES LA LÍNEA CORREGIDA! ▼▼▼ ---
            $optionId = (int)($_POST['poll_option_id'] ?? 0);
            // --- ▲▲▲ ¡FIN DE LA CORRECCIÓN! ▲▲▲ ---

            if (empty($publicationId) || empty($optionId)) {
                throw new Exception('js.api.invalidAction');
            }

            $pdo->beginTransaction();
            
            try {
                // 1. Verificar que la publicación es una encuesta
                $stmt_check_poll = $pdo->prepare("SELECT post_type FROM community_publications WHERE id = ?");
                $stmt_check_poll->execute([$publicationId]);
                $postType = $stmt_check_poll->fetchColumn();
                
                if ($postType !== 'poll') {
                    throw new Exception('js.api.invalidAction'); // No es una encuesta
                }

                // 2. Verificar que la opción pertenece a esa encuesta
                $stmt_check_option = $pdo->prepare("SELECT id FROM poll_options WHERE id = ? AND publication_id = ?");
                $stmt_check_option->execute([$optionId, $publicationId]);
                if (!$stmt_check_option->fetch()) {
                    throw new Exception('js.api.invalidAction'); // Opción no válida
                }

                // 3. Verificar si el usuario ya votó (usando publication_id)
                $stmt_check_vote = $pdo->prepare("SELECT id FROM poll_votes WHERE publication_id = ? AND user_id = ?");
                $stmt_check_vote->execute([$publicationId, $userId]);
                if ($stmt_check_vote->fetch()) {
                    throw new Exception('js.publication.errorAlreadyVoted'); // Ya votó
                }
                
                // 4. Insertar el voto
                $stmt_insert_vote = $pdo->prepare("INSERT INTO poll_votes (publication_id, poll_option_id, user_id) VALUES (?, ?, ?)");
                $stmt_insert_vote->execute([$publicationId, $optionId, $userId]);
                
                // 5. Confirmar transacción
                $pdo->commit();

                // 6. Obtener y devolver los nuevos resultados
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
                // Manejar error de "ya votó"
                if ($e->getMessage() === 'js.publication.errorAlreadyVoted') {
                     $response['message'] = $e->getMessage();
                } else {
                    throw $e; // Re-lanzar otros errores
                }
            }
        }

    } catch (Exception $e) {
        // Revertir todo si algo falló
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        if ($e instanceof PDOException) {
            logDatabaseError($e, 'publication_handler - ' . $action);
            $response['message'] = 'js.api.errorDatabase';
        } else {
            $response['message'] = $e->getMessage();
            if (!isset($response['data'])) {
                $response['data'] = null; // Asegurarse de que 'data' exista
            }
        }
    }
}

echo json_encode($response);
exit;
?>
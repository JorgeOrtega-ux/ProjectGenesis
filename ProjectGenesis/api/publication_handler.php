<?php
// FILE: api/publication_handler.php
// (VERSIÓN MODIFICADA PARA ACEPTAR ARCHIVOS)

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
            
            $textContent = trim($_POST['text_content'] ?? '');
            $communityId = $_POST['community_id'] ?? null;
            $postType = 'post'; // 'poll' se implementará a futuro
            
            $uploadedFiles = $_FILES['attachments'] ?? [];
            $fileIds = []; // Array para guardar los IDs de los archivos guardados en BD

            // --- 1. Validación Principal ---
            if (empty($textContent) && empty($uploadedFiles['name'][0])) {
                throw new Exception('js.publication.errorEmpty');
            }

            // --- 2. Preparar Directorio de Subida ---
            $uploadDir = dirname(__DIR__) . '/assets/uploads/publications';
            if (!is_dir($uploadDir)) {
                if (!@mkdir($uploadDir, 0755, true)) {
                    throw new Exception('js.api.errorServer'); // Error de permisos
                }
            }

            // --- 3. Iniciar Transacción de Base de Datos ---
            $pdo->beginTransaction();

            // --- 4. Procesar Archivos Adjuntos (si existen) ---
            if (!empty($uploadedFiles['name'][0])) {
                $fileCount = count($uploadedFiles['name']);
                if ($fileCount > $MAX_FILES) {
                    throw new Exception('js.publication.errorFileCount');
                }

                $stmt_insert_file = $pdo->prepare(
                    "INSERT INTO publication_files (user_id, community_id, file_name_system, file_name_original, public_url, file_type, file_size)
                     VALUES (?, ?, ?, ?, ?, ?, ?)"
                );

                foreach ($uploadedFiles['error'] as $key => $error) {
                    if ($error !== UPLOAD_ERR_OK) {
                        continue; // Ignorar archivos con errores
                    }

                    $tmpName = $uploadedFiles['tmp_name'][$key];
                    $originalName = $uploadedFiles['name'][$key];
                    $fileSize = $uploadedFiles['size'][$key];
                    
                    // Validar tamaño
                    if ($fileSize > $MAX_SIZE_BYTES) {
                        $response['data'] = ['size' => $MAX_SIZE_MB];
                        throw new Exception('js.publication.errorFileSize');
                    }

                    // Validar tipo
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mimeType = $finfo->file($tmpName);
                    if (!in_array($mimeType, $ALLOWED_TYPES)) {
                        throw new Exception('js.publication.errorFileType');
                    }

                    // Generar nombre de archivo único
                    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
                    $safeName = preg_replace("/[^a-zA-Z0-9-_\.]/", '', pathinfo($originalName, PATHINFO_FILENAME));
                    $systemName = "user-{$userId}-" . time() . "-{$safeName}.{$extension}";
                    $filePath = $uploadDir . '/' . $systemName;
                    $publicUrl = $basePath . '/assets/uploads/publications/' . $systemName;

                    // Mover archivo
                    if (!move_uploaded_file($tmpName, $filePath)) {
                        throw new Exception('js.api.errorServer'); // Error al mover archivo
                    }

                    // Guardar en la tabla 'publication_files'
                    $dbCommunityId = ($communityId !== null && $communityId !== 'main_feed') ? (int)$communityId : null;
                    $stmt_insert_file->execute([
                        $userId, $dbCommunityId, $systemName, $originalName, $publicUrl, $mimeType, $fileSize
                    ]);
                    
                    // Guardar el ID para la tabla de unión
                    $fileIds[] = $pdo->lastInsertId();
                }
            }

            // --- 5. Guardar la Publicación (incluso si solo tiene texto) ---
            $dbCommunityId = ($communityId !== null && $communityId !== 'main_feed') ? (int)$communityId : null;
            
            $stmt_insert_post = $pdo->prepare(
                "INSERT INTO community_publications (community_id, user_id, text_content, post_type)
                 VALUES (?, ?, ?, ?)"
            );
            $stmt_insert_post->execute([$dbCommunityId, $userId, $textContent, $postType]);
            $publicationId = $pdo->lastInsertId();

            // --- 6. Vincular Archivos a la Publicación (si se subieron) ---
            if (!empty($fileIds)) {
                $stmt_link_files = $pdo->prepare(
                    "INSERT INTO publication_attachments (publication_id, file_id, sort_order)
                     VALUES (?, ?, ?)"
                );
                foreach ($fileIds as $index => $fileId) {
                    $stmt_link_files->execute([$publicationId, $fileId, $index]);
                }
            }

            // --- 7. Confirmar Transacción ---
            $pdo->commit();
            
            $response['success'] = true;
            $response['message'] = 'js.publication.success';
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
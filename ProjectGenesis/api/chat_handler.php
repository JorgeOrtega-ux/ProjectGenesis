<?php
// FILE: api/chat_handler.php
// (CÓDIGO MODIFICADO para el nuevo esquema de 3 tablas y CON CARGA DE HISTORIAL)

include '../config/config.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'js.api.invalidAction'];
$WS_BROADCAST_URL = 'http://127.0.0.1:8766/broadcast';

// 1. Validar Sesión de Usuario
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'js.settings.errorNoSession';
    echo json_encode($response);
    exit;
}
$userId = (int)$_SESSION['user_id'];
$username = $_SESSION['username'];
$profileImageUrl = $_SESSION['profile_image_url'];
$userRole = $_SESSION['role'] ?? 'user';

// 2. Validar Método POST y Token CSRF
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'js.api.invalidAction';
    echo json_encode($response);
    exit;
}

$submittedToken = $_POST['csrf_token'] ?? '';
if (!validateCsrfToken($submittedToken)) {
    $response['message'] = 'js.api.errorSecurityRefresh';
    echo json_encode($response);
    exit;
}

$action = $_POST['action'] ?? '';

// ==========================================================
// FUNCIÓN PARA NOTIFICAR AL WEBSOCKET
// ==========================================================
function notifyWebSocketServer($url, $payload) {
    try {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payload)
        ]);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 500);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 500);
        curl_exec($ch);
        curl_close($ch);
        return true;
    } catch (Exception $e) {
        logDatabaseError($e, 'chat_handler - notifyWebSocketServer');
        return false;
    }
}

// ==========================================================
// ACCIÓN PRINCIPAL: ENVIAR MENSAJE (LÓGICA COMPLETAMENTE NUEVA)
// ==========================================================
if ($action === 'send-message') {
    try {
        $pdo->beginTransaction();

        $groupUuid = $_POST['group_uuid'] ?? null;
        $messageText = trim($_POST['message_text'] ?? '');
        $uploadedImages = $_FILES['images'] ?? [];

        // Tratar el texto vacío como NULL
        $messageTextContent = !empty($messageText) ? $messageText : null;
        $hasFiles = !empty($uploadedImages['name'][0]);

        if (empty($groupUuid)) {
            throw new Exception('Error: ID de grupo no proporcionado.');
        }

        // Validar que el mensaje no esté completamente vacío
        if ($messageTextContent === null && !$hasFiles) {
            throw new Exception('Error: No se puede enviar un mensaje vacío.');
        }

        // 1. Validar Grupo y Pertenencia del Usuario
        $stmt_check_group = $pdo->prepare(
            "SELECT g.id 
             FROM groups g
             JOIN user_groups ug ON g.id = ug.group_id
             WHERE g.uuid = ? AND ug.user_id = ?
             LIMIT 1"
        );
        $stmt_check_group->execute([$groupUuid, $userId]);
        $group = $stmt_check_group->fetch();

        if (!$group) {
            throw new Exception('Error: No eres miembro de este grupo o el grupo no existe.');
        }
        $groupId = (int)$group['id'];

        // --- INICIO DE NUEVA LÓGICA ---

        $attachment_file_ids = []; // Almacenará los IDs de `chat_files`
        $attachments_for_payload = []; // Almacenará los datos para el WS

        // 2. Procesar Archivos (si existen)
        if ($hasFiles) {
            $imageCount = count($uploadedImages['name']);
            if ($imageCount > 9) { // Límite de 9 imágenes por mensaje
                throw new Exception('No puedes subir más de 9 imágenes a la vez.');
            }

            $uploadDir = dirname(__DIR__) . '/assets/uploads/chat_files';
            $publicBaseUrl = $basePath . '/assets/uploads/chat_files';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $maxSize = 5 * 1024 * 1024; // 5 MB

            for ($i = 0; $i < $imageCount; $i++) {
                $fileError = $uploadedImages['error'][$i];
                $fileSize = $uploadedImages['size'][$i];
                $fileTmpName = $uploadedImages['tmp_name'][$i];
                $fileNameOriginal = $uploadedImages['name'][$i];
                
                if ($fileError !== UPLOAD_ERR_OK) continue;
                if ($fileSize > $maxSize) continue;

                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->file($fileTmpName);
                if (!in_array($mimeType, $allowedTypes)) continue;

                $extension = strtolower(pathinfo($fileNameOriginal, PATHINFO_EXTENSION));
                // Asegurar extensión válida
                if ($extension === 'jpeg') $extension = 'jpg';
                if (!in_array($mimeType, $allowedTypes)) $extension = 'jpg'; // Fallback
                
                $fileNameSystem = uniqid('chat_' . $userId . '_', true) . '.' . $extension;
                $filePath = $uploadDir . '/' . $fileNameSystem;
                $publicUrl = $publicBaseUrl . '/' . $fileNameSystem;

                if (move_uploaded_file($fileTmpName, $filePath)) {
                    // a. Guardar en 'chat_files'
                    $stmt_file = $pdo->prepare(
                        "INSERT INTO chat_files (user_id, group_id, file_name_system, file_name_original, public_url, file_type, file_size, created_at)
                         VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
                    );
                    $stmt_file->execute([$userId, $groupId, $fileNameSystem, $fileNameOriginal, $publicUrl, $mimeType, $fileSize]);
                    $fileId = (int)$pdo->lastInsertId();
                    
                    $attachment_file_ids[] = $fileId; // Guardar ID para la tabla puente
                    
                    // Guardar datos para el payload del WS
                    $attachments_for_payload[] = [
                        'id' => $fileId,
                        'public_url' => $publicUrl,
                        'file_type' => $mimeType
                    ];
                }
            }
        }

        // 3. Crear la "Burbuja" de Mensaje
        $stmt_insert_msg = $pdo->prepare(
            "INSERT INTO group_messages (group_id, user_id, text_content, created_at) 
             VALUES (?, ?, ?, NOW())"
        );
        // Insertar el texto (o NULL si estaba vacío)
        $stmt_insert_msg->execute([$groupId, $userId, $messageTextContent]);
        $messageId = (int)$pdo->lastInsertId();
        $messageTimestamp = date('Y-m-d H:i:s'); // Usar la hora actual

        // 4. Vincular Archivos al Mensaje (si se subieron)
        if (!empty($attachment_file_ids)) {
            $stmt_link = $pdo->prepare(
                "INSERT INTO message_attachments (message_id, file_id, sort_order) 
                 VALUES (?, ?, ?)"
            );
            foreach ($attachment_file_ids as $index => $fileId) {
                $stmt_link->execute([$messageId, $fileId, $index]);
            }
        }
        
        // --- FIN DE NUEVA LÓGICA ---
        
        // 5. Commit y Notificar al WebSocket (¡SOLO UN MENSAJE!)
        $pdo->commit();
        $messageTimestamp = gmdate('Y-m-d H:i:s');
        // Construir el payload ÚNICO para el WS
        $ws_payload = [
            "type" => "new_chat_message",
            "message" => [
                "id" => $messageId,
                "user_id" => $userId,
                "username" => $username,
                "profile_image_url" => $profileImageUrl,
                "user_role" => $userRole,
                "text_content" => $messageTextContent ? htmlspecialchars($messageTextContent) : null,
                "attachments" => $attachments_for_payload, // Array de archivos
                "created_at" => $messageTimestamp
            ]
        ];

        // Preparar y enviar la notificación
        $broadcastPayload = json_encode([
            "group_uuid" => $groupUuid,
            "message_payload" => json_encode($ws_payload) // El payload interno debe ser un string JSON
        ]);
        notifyWebSocketServer($WS_BROADCAST_URL, $broadcastPayload);

        $response['success'] = true;
        $response['message'] = 'Mensaje enviado.';
        $response['sent_message_id'] = $messageId;

    } catch (PDOException $e) {
        $pdo->rollBack();
        logDatabaseError($e, 'chat_handler - send-message');
        $response['message'] = 'js.api.errorDatabase';
    } catch (Exception $e) {
        $pdo->rollBack();
        $response['message'] = $e->getMessage();
    }
}

// --- ▼▼▼ INICIO DE BLOQUE CORREGIDO ▼▼▼ ---
// ==========================================================
// ACCIÓN: CARGAR HISTORIAL (LAZY LOAD)
// ==========================================================
elseif ($action === 'load-history') {
    try {
        $groupUuid = $_POST['group_uuid'] ?? null;
        $beforeMessageId = (int)($_POST['before_id'] ?? 0);
        $limit = 20; // Cargar en bloques de 20

        // --- ▼▼▼ ¡INICIO DE LA CORRECCIÓN 1! ▼▼▼ ---
        // Se elimina la validación "empty($beforeMessageId)"
        if (empty($groupUuid)) {
            throw new Exception('Faltan parámetros para cargar el historial.');
        }
        // --- ▲▲▲ FIN DE LA CORRECCIÓN 1 ▲▲▲ ---


        // 1. Validar Grupo y Pertenencia
        $stmt_check_group = $pdo->prepare(
            "SELECT g.id 
             FROM groups g
             JOIN user_groups ug ON g.id = ug.group_id
             WHERE g.uuid = ? AND ug.user_id = ?
             LIMIT 1"
        );
        $stmt_check_group->execute([$groupUuid, $userId]);
        $group = $stmt_check_group->fetch();

        if (!$group) {
            throw new Exception('No tienes permiso para ver este historial.');
        }
        $groupId = (int)$group['id'];

        // --- ▼▼▼ ¡INICIO DE LA CORRECCIÓN 2! ▼▼▼ ---
        // 2. Obtener mensajes ANTERIORES al ID proporcionado
        
        // Consulta base
        $sql = "SELECT 
                    m.id, m.user_id, m.text_content, m.created_at,
                    u.username, u.profile_image_url, u.role as user_role
                 FROM group_messages m
                 JOIN users u ON m.user_id = u.id
                 WHERE m.group_id = ? ";

        // Si beforeMessageId es > 0, añadir la condición WHERE
        if ($beforeMessageId > 0) {
            $sql .= " AND m.id < ? ";
        }
        
        $sql .= " ORDER BY m.created_at DESC LIMIT ?";
        
        $stmt_messages = $pdo->prepare($sql);

        // Asignar parámetros dinámicamente
        if ($beforeMessageId > 0) {
            $stmt_messages->bindParam(1, $groupId, PDO::PARAM_INT);
            $stmt_messages->bindParam(2, $beforeMessageId, PDO::PARAM_INT);
            $stmt_messages->bindParam(3, $limit, PDO::PARAM_INT);
        } else {
            // Si beforeMessageId es 0, solo usamos groupId y limit
            $stmt_messages->bindParam(1, $groupId, PDO::PARAM_INT);
            $stmt_messages->bindParam(2, $limit, PDO::PARAM_INT);
        }
        
        $stmt_messages->execute();
        $group_messages_raw = $stmt_messages->fetchAll();
        // --- ▲▲▲ FIN DE LA CORRECCIÓN 2 ▲▲▲ ---


        // 3. Obtener adjuntos (igual que en home.php)
        $stmt_attachments = $pdo->prepare(
            "SELECT cf.public_url, cf.file_type 
             FROM message_attachments ma
             JOIN chat_files cf ON ma.file_id = cf.id
             WHERE ma.message_id = ?
             ORDER BY ma.sort_order ASC
             LIMIT 9"
        );

        $formatted_messages = [];
        foreach($group_messages_raw as $msg) {
            $stmt_attachments->execute([$msg['id']]);
            $msg['attachments'] = $stmt_attachments->fetchAll();
            
            // Damos formato al payload del mensaje
            $formatted_messages[] = [
                "id" => $msg['id'],
                "user_id" => $msg['user_id'],
                "username" => $msg['username'],
                "profile_image_url" => $msg['profile_image_url'],
                "user_role" => $msg['user_role'],
                "text_content" => $msg['text_content'] ? htmlspecialchars($msg['text_content']) : null,
                "attachments" => $msg['attachments'], // Ya vienen de la BD
                "created_at" => gmdate('Y-m-d H:i:s', strtotime($msg['created_at']))
            ];
        }

        $response['success'] = true;
        $response['messages'] = $formatted_messages; // Enviamos los mensajes en orden [Nuevo...Antiguo]
        $response['has_more'] = count($formatted_messages) === $limit; // Informa al JS si quedan más por cargar

    } catch (PDOException $e) {
        logDatabaseError($e, 'chat_handler - load-history');
        $response['message'] = 'js.api.errorDatabase';
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
}
// --- ▲▲▲ FIN DE BLOQUE CORREGIDO ---


echo json_encode($response);
exit;
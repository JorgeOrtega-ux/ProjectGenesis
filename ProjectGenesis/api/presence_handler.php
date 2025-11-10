<?php
// FILE: api/presence_handler.php
// Este endpoint es SOLO para ser llamado por el socket_server.py

include '../config/config.php';

// 1. Seguridad: Solo aceptar peticiones de localhost
$allowed_ips = ['127.0.0.1', '::1'];
if (!in_array($_SERVER['REMOTE_ADDR'], $allowed_ips)) {
    http_response_code(403);
    logDatabaseError(new Exception("Llamada no autorizada a presence_handler.php desde " . $_SERVER['REMOTE_ADDR']), 'presence_handler');
    exit;
}

// 2. Obtener el payload
$data = json_decode(file_get_contents('php://input'), true);
$userId = $data['user_id'] ?? 0;

if ($userId > 0) {
    try {
        // 3. Actualizar la base de datos
        // Esto marca la "última vez visto" en el momento exacto de la desconexión del WebSocket
        $stmt = $pdo->prepare("UPDATE users SET last_seen = NOW() WHERE id = ?");
        $stmt->execute([$userId]);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'user_id' => $userId]);

    } catch (PDOException $e) {
        logDatabaseError($e, 'presence_handler');
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid user_id']);
}
exit;
?>
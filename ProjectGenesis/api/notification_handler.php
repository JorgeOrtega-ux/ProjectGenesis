<?php
// FILE: api/notification_handler.php (MODIFICADO)

include '../config/config.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'js.api.invalidAction'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'js.settings.errorNoSession';
    echo json_encode($response);
    exit;
}

$userId = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($submittedToken)) {
        $response['message'] = 'js.api.errorSecurityRefresh';
        echo json_encode($response);
        exit;
    }

    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'get-notifications') {
            
            // --- ▼▼▼ INICIO DE MODIFICACIÓN: LÓGICA DE DOBLE CONSULTA ▼▼▼ ---

            // Consulta 1: Obtener la LISTA de las 30 notificaciones más recientes (leídas o no)
            $stmt_list = $pdo->prepare(
                "SELECT 
                    n.id, n.type, n.reference_id, n.created_at, n.is_read,
                    u.id as actor_user_id,
                    u.username as actor_username,
                    u.profile_image_url as actor_avatar
                 FROM user_notifications n
                 JOIN users u ON n.actor_user_id = u.id
                 WHERE n.user_id = ?
                 ORDER BY n.created_at DESC
                 LIMIT 30"
            );
            $stmt_list->execute([$userId]);
            $notifications = $stmt_list->fetchAll();
            
            // Consulta 2: Obtener el CONTEO solo de las NO LEÍDAS (para el badge)
            $stmt_count = $pdo->prepare(
                "SELECT COUNT(*) FROM user_notifications WHERE user_id = ? AND is_read = 0"
            );
            $stmt_count->execute([$userId]);
            $unread_count = $stmt_count->fetchColumn();

            // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
            
            $defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";
            foreach ($notifications as &$notification) {
                if (empty($notification['actor_avatar'])) {
                    $notification['actor_avatar'] = "https://ui-avatars.com/api/?name=" . urlencode($notification['actor_username']) . "&size=100&background=e0e0e0&color=ffffff";
                }
            }

            $response['success'] = true;
            $response['notifications'] = $notifications; // La lista completa
            $response['unread_count'] = (int)$unread_count; // El conteo separado

        } elseif ($action === 'mark-all-read') {
            
            // Esta acción sigue igual: pone todo en 'is_read = 1'
            $stmt = $pdo->prepare("UPDATE user_notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
            $stmt->execute([$userId]);
            
            $response['success'] = true;
            $response['message'] = 'Notificaciones marcadas como leídas.';
        
        } else {
            $response['message'] = 'js.api.invalidAction';
        }

    } catch (Exception $e) {
        if ($e instanceof PDOException) {
            logDatabaseError($e, 'notification_handler - ' . $action);
            $response['message'] = 'js.api.errorDatabase';
        } else {
            $response['message'] = $e->getMessage();
        }
    }
}

echo json_encode($response);
exit;
?>
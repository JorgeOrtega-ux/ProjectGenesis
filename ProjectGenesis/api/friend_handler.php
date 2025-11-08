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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($submittedToken)) {
        $response['message'] = 'js.api.errorSecurityRefresh';
        echo json_encode($response);
        exit;
    }

    $action = $_POST['action'] ?? '';
    $targetUserId = (int)($_POST['target_user_id'] ?? 0);

    // --- ▼▼▼ MODIFICACIÓN: Mover validación de targetUserId ▼▼▼ ---
    if ($action !== 'get-friends-list' && ($targetUserId === 0 || $targetUserId === $currentUserId)) {
         $response['message'] = 'js.api.invalidAction';
         echo json_encode($response);
         exit;
    }
    // --- ▲▲▲ FIN MODIFICACIÓN ▲▲▲ ---
    
    // Normalizar IDs para asegurar que user_id_1 < user_id_2
    $userId1 = min($currentUserId, $targetUserId);
    $userId2 = max($currentUserId, $targetUserId);

    try {
        // --- ▼▼▼ INICIO DE NUEVA ACCIÓN ▼▼▼ ---
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
            
            // Formatear la URL del avatar
            foreach ($friends as &$friend) {
                if (empty($friend['profile_image_url'])) {
                    $friend['profile_image_url'] = "https://ui-avatars.com/api/?name=" . urlencode($friend['username']) . "&size=100&background=e0e0e0&color=ffffff";
                }
            }

            $response['success'] = true;
            $response['friends'] = $friends;
        
        } 
        // --- ▲▲▲ FIN DE NUEVA ACCIÓN ▲▲▲ ---
        elseif ($action === 'send-request') {
            // Verificar si ya existe una relación
            $stmt_check = $pdo->prepare("SELECT status FROM friendships WHERE user_id_1 = ? AND user_id_2 = ?");
            $stmt_check->execute([$userId1, $userId2]);
            if ($stmt_check->fetch()) {
                 throw new Exception('js.friends.errorGeneric'); // Ya existe una relación
            }
            
            $stmt_insert = $pdo->prepare(
                "INSERT INTO friendships (user_id_1, user_id_2, status, action_user_id) VALUES (?, ?, 'pending', ?)"
            );
            $stmt_insert->execute([$userId1, $userId2, $currentUserId]);
            
            $response['success'] = true;
            $response['message'] = 'js.friends.requestSent';
            $response['newStatus'] = 'pending_sent';

        } elseif ($action === 'cancel-request' || $action === 'decline-request' || $action === 'remove-friend') {
            // Para cualquiera de estas acciones, simplemente borramos la fila.
            // La lógica de UI se encarga de mostrar el botón correcto, pero en BD es lo mismo: borrar la relación.
            
            $stmt_delete = $pdo->prepare("DELETE FROM friendships WHERE user_id_1 = ? AND user_id_2 = ?");
            $stmt_delete->execute([$userId1, $userId2]);
            
            if ($stmt_delete->rowCount() > 0) {
                $response['success'] = true;
                if ($action === 'cancel-request') $response['message'] = 'js.friends.requestCanceled';
                elseif ($action === 'remove-friend') $response['message'] = 'js.friends.friendRemoved';
                else $response['message'] = 'js.friends.requestCanceled'; // Fallback para decline
                
                $response['newStatus'] = 'not_friends';
            } else {
                 throw new Exception('js.friends.errorGeneric');
            }

        } elseif ($action === 'accept-request') {
            // Solo se puede aceptar si está 'pending' Y el currentUserId NO es quien envió la solicitud.
            $stmt_check = $pdo->prepare("SELECT status, action_user_id FROM friendships WHERE user_id_1 = ? AND user_id_2 = ?");
            $stmt_check->execute([$userId1, $userId2]);
            $friendship = $stmt_check->fetch();
            
            if (!$friendship || $friendship['status'] !== 'pending' || $friendship['action_user_id'] == $currentUserId) {
                 throw new Exception('js.friends.errorGeneric');
            }
            
            $stmt_update = $pdo->prepare("UPDATE friendships SET status = 'accepted', action_user_id = ? WHERE user_id_1 = ? AND user_id_2 = ?");
            $stmt_update->execute([$currentUserId, $userId1, $userId2]);
            
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
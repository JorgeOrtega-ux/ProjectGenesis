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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($submittedToken)) {
        $response['message'] = 'js.api.errorSecurityRefresh';
        echo json_encode($response);
        exit;
    }

    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'get-my-communities') {
            $stmt = $pdo->prepare(
                "SELECT c.id, c.name, c.uuid 
                 FROM communities c
                 JOIN user_communities uc ON c.id = uc.community_id
                 WHERE uc.user_id = ?
                 ORDER BY c.name ASC"
            );
            $stmt->execute([$userId]);
            $communities = $stmt->fetchAll();
            
            $response['success'] = true;
            $response['communities'] = $communities; 

        } elseif ($action === 'join-community') {
            $communityId = (int)($_POST['community_id'] ?? 0);
            if (empty($communityId)) {
                throw new Exception('js.api.invalidAction');
            }

            // --- ▼▼▼ INICIO DE MODIFICACIÓN (COMPROBAR LÍMITE) ▼▼▼ ---
            $stmt_check = $pdo->prepare("SELECT privacy, max_members FROM communities WHERE id = ?");
            $stmt_check->execute([$communityId]);
            $community = $stmt_check->fetch();

            if (!$community) {
                throw new Exception('js.api.errorServer');
            }
            if ($community['privacy'] !== 'public') {
                 throw new Exception('js.api.errorServer'); 
            }
            
            $maxMembers = (int)($community['max_members'] ?? 0);
            if ($maxMembers > 0) {
                // Hay un límite, contemos los miembros actuales
                $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM user_communities WHERE community_id = ?");
                $stmt_count->execute([$communityId]);
                $currentMembers = (int)$stmt_count->fetchColumn();
                
                if ($currentMembers >= $maxMembers) {
                    // ¡Comunidad llena!
                    throw new Exception('js.join_group.communityFull');
                }
            }
            // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

            $stmt_insert = $pdo->prepare("INSERT IGNORE INTO user_communities (user_id, community_id) VALUES (?, ?)");
            $stmt_insert->execute([$userId, $communityId]);

            $response['success'] = true;
            $response['message'] = 'js.join_group.joinSuccess';

        } elseif ($action === 'leave-community') {
            $communityId = (int)($_POST['community_id'] ?? 0);
            if (empty($communityId)) {
                throw new Exception('js.api.invalidAction');
            }
            
            $stmt_delete = $pdo->prepare("DELETE FROM user_communities WHERE user_id = ? AND community_id = ?");
            $stmt_delete->execute([$userId, $communityId]);

            $response['success'] = true;
            $response['message'] = 'js.join_group.leaveSuccess';

        } elseif ($action === 'join-private-community') {
            $accessCode = trim($_POST['join_code'] ?? '');
            if (empty($accessCode)) {
                throw new Exception('js.join_group.invalidCode');
            }

            // --- ▼▼▼ INICIO DE MODIFICACIÓN (Quitar guiones) ▼▼▼ ---
            $accessCodeDB = str_replace('-', '', $accessCode);
            // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

            // --- ▼▼▼ INICIO DE MODIFICACIÓN (COMPROBAR LÍMITE Y USAR accessCodeDB) ▼▼▼ ---
            $stmt_find = $pdo->prepare("SELECT id, name, uuid, max_members FROM communities WHERE access_code = ? AND privacy = 'private'");
            $stmt_find->execute([$accessCodeDB]); // Usar la variable sin guiones
            $community = $stmt_find->fetch();

            if (!$community) {
                throw new Exception('js.join_group.invalidCode');
            }
            
            $communityId = $community['id'];

            $stmt_check_member = $pdo->prepare("SELECT id FROM user_communities WHERE user_id = ? AND community_id = ?");
            $stmt_check_member->execute([$userId, $communityId]);
            if ($stmt_check_member->fetch()) {
                throw new Exception('js.join_group.alreadyMember');
            }
            
            $maxMembers = (int)($community['max_members'] ?? 0);
            if ($maxMembers > 0) {
                // Hay un límite, contemos los miembros actuales
                $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM user_communities WHERE community_id = ?");
                $stmt_count->execute([$communityId]);
                $currentMembers = (int)$stmt_count->fetchColumn();
                
                if ($currentMembers >= $maxMembers) {
                    // ¡Comunidad llena!
                    throw new Exception('js.join_group.communityFull');
                }
            }
            // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

            $stmt_insert = $pdo->prepare("INSERT INTO user_communities (user_id, community_id) VALUES (?, ?)");
            $stmt_insert->execute([$userId, $communityId]);

            $response['success'] = true;
            $response['message'] = 'js.join_group.joinSuccess';
            $response['communityName'] = $community['name'] ?? 'Comunidad'; 
            $response['communityUuid'] = $community['uuid'] ?? ''; 
        
        // --- ▼▼▼ INICIO DE NUEVA ACCIÓN ▼▼▼ ---
        } elseif ($action === 'get-my-community-ids') {
            // Esta acción es para que el WebSocket sepa a qué salas suscribirse
            $stmt = $pdo->prepare("SELECT community_id FROM user_communities WHERE user_id = ?");
            $stmt->execute([$userId]);
            $communityIds = $stmt->fetchAll(PDO::FETCH_COLUMN, 0); // Obtiene solo los IDs
            
            $response['success'] = true;
            $response['community_ids'] = $communityIds;
        // --- ▲▲▲ FIN DE NUEVA ACCIÓN ▲▲▲ ---
        
        }

    } catch (Exception $e) {
        if ($e instanceof PDOException) {
            logDatabaseError($e, 'community_handler - ' . $action);
            $response['message'] = 'js.api.errorDatabase';
        } else {
            $response['message'] = $e->getMessage();
        }
    }
}

echo json_encode($response);
exit;
?>
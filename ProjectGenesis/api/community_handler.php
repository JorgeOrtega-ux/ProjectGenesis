<?php
// FILE: api/community_handler.php

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
                "SELECT c.id, c.name 
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

            // (Opcional) Verificar que sea pública
            $stmt_check = $pdo->prepare("SELECT privacy FROM communities WHERE id = ?");
            $stmt_check->execute([$communityId]);
            $privacy = $stmt_check->fetchColumn();

            if ($privacy !== 'public') {
                 throw new Exception('js.api.errorServer'); // No debería poder unirse a una privada así
            }

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

            $stmt_find = $pdo->prepare("SELECT id FROM communities WHERE access_code = ? AND privacy = 'private'");
            $stmt_find->execute([$accessCode]);
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

            $stmt_insert = $pdo->prepare("INSERT INTO user_communities (user_id, community_id) VALUES (?, ?)");
            $stmt_insert->execute([$userId, $communityId]);

            $response['success'] = true;
            $response['message'] = 'js.join_group.joinSuccess';
            $response['communityName'] = $community['name'] ?? 'Comunidad'; // Opcional
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
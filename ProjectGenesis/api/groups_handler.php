<?php
// FILE: api/groups_handler.php

include '../config/config.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'js.api.invalidAction'];

// 1. Validar Sesión de Usuario
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'js.settings.errorNoSession';
    echo json_encode($response);
    exit;
}
$userId = $_SESSION['user_id'];

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

if ($action === 'join-group') {
    // --- ▼▼▼ MODIFICACIÓN: Limpiar guiones del código ▼▼▼ ---
    $access_code = str_replace('-', '', trim($_POST['access_code'] ?? ''));
    // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

    try {
        // Validación 1: Código no vacío
        if (empty($access_code)) {
            // (Añadir a es-mx.json) groups.join.js.error.codeEmpty
            throw new Exception('groups.join.js.error.codeEmpty');
        }

        // Validación 2: Encontrar el grupo por código de acceso
        $stmt_find_group = $pdo->prepare("SELECT id, privacy FROM groups WHERE access_key = ? LIMIT 1");
        $stmt_find_group->execute([$access_code]);
        $group = $stmt_find_group->fetch();

        if (!$group) {
            // (Añadir a es-mx.json) groups.join.js.error.codeInvalid
            throw new Exception('groups.join.js.error.codeInvalid');
        }
        $groupId = $group['id'];

        // Validación 3: Comprobar que el usuario no sea ya miembro
        $stmt_check_join = $pdo->prepare("SELECT user_id FROM user_groups WHERE user_id = ? AND group_id = ?");
        $stmt_check_join->execute([$userId, $groupId]);
        
        if ($stmt_check_join->fetch()) {
            // (Añadir a es-mx.json) groups.join.js.error.alreadyJoined
            throw new Exception('groups.join.js.error.alreadyJoined');
        }

        // --- ▼▼▼ ¡INICIO DE CORRECCIÓN! ▼▼▼ ---
        // Éxito: Unir al usuario al grupo (sin rol)
        $stmt_join = $pdo->prepare("INSERT INTO user_groups (user_id, group_id) VALUES (?, ?)");
        $stmt_join->execute([$userId, $groupId]);
        // --- ▲▲▲ ¡FIN DE CORRECCIÓN! ▲▲▲ ---

        // (Añadir a es-mx.json) groups.join.js.success.joined
        $response['success'] = true;
        $response['message'] = 'groups.join.js.success.joined';

    } catch (PDOException $e) {
        logDatabaseError($e, 'groups_handler - join-group');
        $response['message'] = 'js.api.errorDatabase';
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
}

echo json_encode($response);
exit;
?>
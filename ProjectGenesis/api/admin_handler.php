<?php
// FILE: api/admin_handler.php

include '../config/config.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'js.api.invalidAction'];

// 1. Validar Sesión de Administrador
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'js.settings.errorNoSession';
    echo json_encode($response);
    exit;
}

$adminUserId = $_SESSION['user_id'];
$adminRole = $_SESSION['role'] ?? 'user';

if ($adminRole !== 'administrator' && $adminRole !== 'founder') {
    $response['message'] = 'js.admin.errorAdminTarget'; // Mensaje genérico de "sin permiso"
    echo json_encode($response);
    exit;
}

// 2. Validar Token CSRF
$submittedToken = $_POST['csrf_token'] ?? '';
if (!validateCsrfToken($submittedToken)) {
    $response['message'] = 'js.api.errorSecurityRefresh';
    echo json_encode($response);
    exit;
}

// 3. Obtener Parámetros
$action = $_POST['action'] ?? '';
$targetUserId = $_POST['target_user_id'] ?? 0;
$newValue = $_POST['new_value'] ?? '';

if (empty($action) || empty($targetUserId) || $newValue === '') { // newValue puede ser '0'
    $response['message'] = 'js.auth.errorCompleteFields';
    echo json_encode($response);
    exit;
}

// 4. Prohibir auto-modificación
if ($targetUserId == $adminUserId) {
    $response['message'] = 'js.admin.errorSelf';
    echo json_encode($response);
    exit;
}

try {
    // 5. Obtener datos del usuario objetivo
    $stmt_target = $pdo->prepare("SELECT role, account_status FROM users WHERE id = ?");
    $stmt_target->execute([$targetUserId]);
    $targetUser = $stmt_target->fetch();

    if (!$targetUser) {
        $response['message'] = 'js.auth.errorUserNotFound';
        echo json_encode($response);
        exit;
    }
    $targetRole = $targetUser['role'];

    // 6. Lógica de Protección de Jerarquía
    $canModify = false;
    if ($adminRole === 'founder') {
        // Un Fundador puede modificar a todos, EXCEPTO a otro Fundador
        if ($targetRole !== 'founder') {
            $canModify = true;
        } else {
            $response['message'] = 'js.admin.errorFounderTarget';
        }
    } elseif ($adminRole === 'administrator') {
        // Un Administrador puede modificar solo a Moderadores y Usuarios
        if ($targetRole === 'user' || $targetRole === 'moderator') {
            $canModify = true;
        } else {
            // No puede tocar a otros Administradores ni a Fundadores
            $response['message'] = 'js.admin.errorAdminTarget';
        }
    }
    
    if (!$canModify) {
        echo json_encode($response);
        exit;
    }

    // 7. Ejecutar la Acción solicitada
    
    if ($action === 'set-role') {
        
        // --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
        $allowedRoles = ['user', 'moderator', 'administrator', 'founder'];
        if (!in_array($newValue, $allowedRoles)) {
            throw new Exception('js.api.invalidAction'); // Valor de rol no válido
        }

        // Regla: Nadie puede ASIGNAR el rol de Fundador a través de la UI.
        // Si el rol nuevo es 'founder' Y el rol actual NO es 'founder' (es una asignación nueva)
        if ($newValue === 'founder' && $targetRole !== 'founder') {
            $response['message'] = 'js.admin.errorFounderAssign';
            echo json_encode($response);
            exit;
        }
        // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---


        // Un Administrador no puede ascender a otros a Administrador
        if ($adminRole === 'administrator' && $newValue === 'administrator') {
            $response['message'] = 'js.admin.errorInvalidRole';
            echo json_encode($response);
            exit;
        }
        
        $stmt_update = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt_update->execute([$newValue, $targetUserId]);
        $response['success'] = true;
        $response['message'] = 'js.admin.successRole';

    } elseif ($action === 'set-status') {
        // ... (esta parte se mantiene igual) ...
        $allowedStatus = ['active', 'suspended', 'deleted'];
        if (!in_array($newValue, $allowedStatus)) {
            throw new Exception('js.api.invalidAction'); // Valor de estado no válido
        }
        
        $stmt_update = $pdo->prepare("UPDATE users SET account_status = ? WHERE id = ?");
        $stmt_update->execute([$newValue, $targetUserId]);
        $response['success'] = true;
        $response['message'] = 'js.admin.successStatus';

    } else {
        $response['message'] = 'js.api.invalidAction';
    }

} catch (PDOException $e) {
    logDatabaseError($e, 'admin_handler');
    $response['message'] = 'js.api.errorDatabase';
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
exit;
?>
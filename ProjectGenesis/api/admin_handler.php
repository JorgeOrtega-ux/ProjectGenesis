<?php
// FILE: api/admin_handler.php
// (CÓDIGO MODIFICADO)

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

// --- INICIO DE MODIFICACIÓN: Lógica de POST y GET ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 2. Validar Token CSRF
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($submittedToken)) {
        $response['message'] = 'js.api.errorSecurityRefresh';
        echo json_encode($response);
        exit;
    }
    
    $action = $_POST['action'] ?? '';

    // --- ▼▼▼ NUEVA ACCIÓN 'get-users' AÑADIDA ▼▼▼ ---
    if ($action === 'get-users') {
        
        try {
            $defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";
            
            // 1. OBTENER PARÁMETROS (AHORA DESDE POST)
            $adminCurrentPage = (int)($_POST['p'] ?? 1);
            if ($adminCurrentPage < 1) $adminCurrentPage = 1;

            $searchQuery = trim($_POST['q'] ?? '');
            $isSearching = !empty($searchQuery);

            $sort_by_param = trim($_POST['s'] ?? '');
            $sort_order_param = trim($_POST['o'] ?? '');

            $allowed_sort = ['created_at', 'username', 'email'];
            $allowed_order = ['ASC', 'DESC'];

            if (!in_array($sort_by_param, $allowed_sort)) $sort_by_param = '';
            if (!in_array($sort_order_param, $allowed_order)) $sort_order_param = '';

            $sort_by_sql = ($sort_by_param === '') ? 'created_at' : $sort_by_param;
            $sort_order_sql = ($sort_order_param === '') ? 'DESC' : $sort_order_param;

            $usersPerPage = 1; // 20 usuarios por página (Debería coincidir con manage-users.php)
            $totalUsers = 0;
            $totalPages = 1;

            // 2. Contar el total de usuarios (con filtro si existe)
            $sqlCount = "SELECT COUNT(*) FROM users";
            if ($isSearching) {
                $sqlCount .= " WHERE (username LIKE :query OR email LIKE :query)";
            }
            $totalUsersStmt = $pdo->prepare($sqlCount);
            if ($isSearching) {
                $searchParam = '%' . $searchQuery . '%';
                $totalUsersStmt->bindParam(':query', $searchParam, PDO::PARAM_STR);
            }
            $totalUsersStmt->execute();
            $totalUsers = (int)$totalUsersStmt->fetchColumn();

            if ($totalUsers > 0) {
                $totalPages = (int)ceil($totalUsers / $usersPerPage);
            } else {
                $totalPages = 1;
            }
            if ($adminCurrentPage > $totalPages) $adminCurrentPage = $totalPages;
            $offset = ($adminCurrentPage - 1) * $usersPerPage;

            // 3. Obtener los usuarios para la página actual
            $sqlSelect = "SELECT id, username, email, profile_image_url, role, created_at, account_status 
                          FROM users";
            if ($isSearching) {
                $sqlSelect .= " WHERE (username LIKE :query OR email LIKE :query)";
            }
            $sqlSelect .= " ORDER BY $sort_by_sql $sort_order_sql LIMIT :limit OFFSET :offset";
            
            $stmt = $pdo->prepare($sqlSelect);
            if ($isSearching) {
                $stmt->bindParam(':query', $searchParam, PDO::PARAM_STR);
            }
            $stmt->bindValue(':limit', $usersPerPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $usersList = $stmt->fetchAll();
            
            // 4. Formatear datos para el JSON
            $formattedUsers = [];
            foreach ($usersList as $user) {
                $avatarUrl = $user['profile_image_url'] ?? $defaultAvatar;
                if (empty($avatarUrl)) {
                    $avatarUrl = "https://ui-avatars.com/api/?name=" . urlencode($user['username']) . "&size=100&background=e0e0e0&color=ffffff";
                }
                
                $formattedUsers[] = [
                    'id' => $user['id'],
                    'username' => htmlspecialchars($user['username']),
                    'email' => htmlspecialchars($user['email']),
                    'avatarUrl' => htmlspecialchars($avatarUrl),
                    'role' => htmlspecialchars($user['role']),
                    'roleDisplay' => htmlspecialchars(ucfirst($user['role'])),
                    'status' => htmlspecialchars($user['account_status']),
                    'statusDisplay' => htmlspecialchars(ucfirst($user['account_status'])),
                    'createdAt' => (new DateTime($user['created_at']))->format('d/m/Y')
                ];
            }

            // 5. Devolver la respuesta JSON
            $response['success'] = true;
            $response['users'] = $formattedUsers;
            $response['totalUsers'] = $totalUsers;
            $response['totalPages'] = $totalPages;
            $response['currentPage'] = $adminCurrentPage;

        } catch (PDOException $e) {
            logDatabaseError($e, 'admin_handler - get-users');
            $response['message'] = 'js.api.errorDatabase';
        }
        
    // --- Lógica existente para 'set-role' y 'set-status' ---
    } elseif ($action === 'set-role' || $action === 'set-status') {

        $targetUserId = $_POST['target_user_id'] ?? 0;
        $newValue = $_POST['new_value'] ?? '';

        if (empty($targetUserId) || $newValue === '') {
            $response['message'] = 'js.auth.errorCompleteFields';
            echo json_encode($response);
            exit;
        }

        if ($targetUserId == $adminUserId) {
            $response['message'] = 'js.admin.errorSelf';
            echo json_encode($response);
            exit;
        }

        try {
            $stmt_target = $pdo->prepare("SELECT role, account_status FROM users WHERE id = ?");
            $stmt_target->execute([$targetUserId]);
            $targetUser = $stmt_target->fetch();

            if (!$targetUser) {
                $response['message'] = 'js.auth.errorUserNotFound';
                echo json_encode($response);
                exit;
            }
            $targetRole = $targetUser['role'];

            $canModify = false;
            if ($adminRole === 'founder') {
                if ($targetRole !== 'founder') {
                    $canModify = true;
                } else {
                    $response['message'] = 'js.admin.errorFounderTarget';
                }
            } elseif ($adminRole === 'administrator') {
                if ($targetRole === 'user' || $targetRole === 'moderator') {
                    $canModify = true;
                } else {
                    $response['message'] = 'js.admin.errorAdminTarget';
                }
            }
            
            if (!$canModify) {
                echo json_encode($response);
                exit;
            }
            
            if ($action === 'set-role') {
                $allowedRoles = ['user', 'moderator', 'administrator', 'founder'];
                if (!in_array($newValue, $allowedRoles)) {
                    throw new Exception('js.api.invalidAction');
                }
                if ($newValue === 'founder' && $targetRole !== 'founder') {
                    $response['message'] = 'js.admin.errorFounderAssign';
                    echo json_encode($response);
                    exit;
                }
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
                $allowedStatus = ['active', 'suspended', 'deleted'];
                if (!in_array($newValue, $allowedStatus)) {
                    throw new Exception('js.api.invalidAction');
                }
                $stmt_update = $pdo->prepare("UPDATE users SET account_status = ? WHERE id = ?");
                $stmt_update->execute([$newValue, $targetUserId]);
                $response['success'] = true;
                $response['message'] = 'js.admin.successStatus';
            }

        } catch (PDOException $e) {
            logDatabaseError($e, 'admin_handler');
            $response['message'] = 'js.api.errorDatabase';
        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }
    }
    // --- Fin de la lógica 'set-role' / 'set-status' ---
    
} else {
    // Si no es POST, se mantiene el error por defecto
    $response['message'] = 'js.api.invalidAction';
}
// --- FIN DE MODIFICACIÓN ---

echo json_encode($response);
exit;
?>
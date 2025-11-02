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

// --- ▼▼▼ ¡NUEVA FUNCIÓN AÑADIDA! (Copiada de settings_handler.php) ▼▼▼ ---
function generateDefaultAvatar($pdo, $userId, $username, $basePath)
{
    try {
        $savePathDir = dirname(__DIR__) . '/assets/uploads/avatars_default'; // Nueva carpeta
        $fileName = "user-{$userId}.png";
        $fullSavePath = $savePathDir . '/' . $fileName;
        $publicUrl = $basePath . '/assets/uploads/avatars_default/' . $fileName; // Nueva carpeta

        if (!is_dir($savePathDir)) {
            mkdir($savePathDir, 0755, true);
        }

        $avatarColors = ['206BD3', 'D32029', '28A745', 'E91E63', 'F57C00'];
        $selectedColor = $avatarColors[array_rand($avatarColors)];
        $nameParam = urlencode($username);

        $apiUrl = "https://ui-avatars.com/api/?name={$nameParam}&size=256&background={$selectedColor}&color=ffffff&bold=true&length=1";

        $imageData = @file_get_contents($apiUrl);

        if ($imageData === false) {
            return null;
        }

        file_put_contents($fullSavePath, $imageData);
        return $publicUrl;
    } catch (Exception $e) {
        return null;
    }
}
// --- ▲▲▲ FIN DE NUEVA FUNCIÓN ▲▲▲ ---

// --- ▼▼▼ ¡NUEVAS CONSTANTES AÑADIDAS! (Copiadas de auth_handler.php) ▼▼▼ ---
define('MIN_PASSWORD_LENGTH', 8);
define('MAX_PASSWORD_LENGTH', 72);
define('MIN_USERNAME_LENGTH', 6);
define('MAX_USERNAME_LENGTH', 32);
define('MAX_EMAIL_LENGTH', 255);
// --- ▲▲▲ FIN DE NUEVAS CONSTANTES ▲▲▲ ---

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
        
    // --- ▼▼▼ ¡NUEVA ACCIÓN 'create-user'! ▼▼▼ ---
    } elseif ($action === 'create-user') {
        try {
            // 1. Obtener datos
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $passwordConfirm = $_POST['password_confirm'] ?? ''; // <-- NUEVA VARIABLE
            $role = $_POST['role'] ?? 'user';

            // 2. Validar Campos Vacíos
            if (empty($username) || empty($email) || empty($password) || empty($passwordConfirm) || empty($role)) { // <-- CAMPO AÑADIDO
                throw new Exception('js.auth.errorCompleteAllFields');
            }

            // 3. Validar Rol
            $allowedRoles = ['user', 'moderator', 'administrator'];
            if (!in_array($role, $allowedRoles)) {
                throw new Exception('admin.create.errorRole'); // Nueva clave i18n
            }
            // (La comprobación de 'founder' no es necesaria, ya que no está en $allowedRoles)

            // 4. Validar Email
            $allowedDomains = ['gmail.com', 'outlook.com', 'hotmail.com', 'yahoo.com', 'icloud.com'];
            $emailDomain = substr($email, strrpos($email, '@') + 1);

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('js.auth.errorInvalidEmail');
            }
            if (strlen($email) > MAX_EMAIL_LENGTH) {
                throw new Exception('js.auth.errorEmailLength');
            }
            if (!in_array(strtolower($emailDomain), $allowedDomains)) {
                throw new Exception('js.auth.errorEmailDomain');
            }
            $stmt_check_email = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt_check_email->execute([$email]);
            if ($stmt_check_email->fetch()) {
                throw new Exception('js.auth.errorEmailInUse');
            }

            // 5. Validar Username
            if (strlen($username) < MIN_USERNAME_LENGTH) {
                throw new Exception('js.auth.errorUsernameMinLength');
            }
            if (strlen($username) > MAX_USERNAME_LENGTH) {
                throw new Exception('js.auth.errorUsernameMaxLength');
            }
            $stmt_check_user = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt_check_user->execute([$username]);
            if ($stmt_check_user->fetch()) {
                throw new Exception('js.auth.errorUsernameInUse');
            }

            // 6. Validar Contraseña
            if (strlen($password) < MIN_PASSWORD_LENGTH) {
                throw new Exception('js.auth.errorPasswordMinLength');
            }
            if (strlen($password) > MAX_PASSWORD_LENGTH) {
                throw new Exception('js.auth.errorPasswordMaxLength');
            }
            // --- ▼▼▼ NUEVA VALIDACIÓN ▼▼▼ ---
            if ($password !== $passwordConfirm) {
                throw new Exception('js.auth.errorPasswordMismatch');
            }
            // --- ▲▲▲ FIN NUEVA VALIDACIÓN ▲▲▲ ---


            // 7. Si todo es válido, crear usuario
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
            $authToken = bin2hex(random_bytes(32));

            $stmt_insert = $pdo->prepare(
                "INSERT INTO users (email, username, password, role, auth_token, account_status) 
                 VALUES (?, ?, ?, ?, ?, 'active')"
            );
            $stmt_insert->execute([$email, $username, $passwordHash, $role, $authToken]);
            $newUserId = $pdo->lastInsertId();

            // 8. Generar Avatar por defecto
            $localAvatarUrl = generateDefaultAvatar($pdo, $newUserId, $username, $basePath);
            if ($localAvatarUrl) {
                $stmt_avatar = $pdo->prepare("UPDATE users SET profile_image_url = ? WHERE id = ?");
                $stmt_avatar->execute([$localAvatarUrl, $newUserId]);
            }

            // 9. Crear Preferencias por defecto
            $preferredLanguage = $_SESSION['language'] ?? 'es-latam'; // Usar el idioma del admin
            $stmt_prefs = $pdo->prepare(
                "INSERT INTO user_preferences (user_id, language, theme, usage_type) 
                 VALUES (?, ?, 'system', 'personal')"
            );
            $stmt_prefs->execute([$newUserId, $preferredLanguage]);

            // 10. Responder con éxito
            $response['success'] = true;
            $response['message'] = 'admin.create.success'; // Nueva clave i18n

        } catch (PDOException $e) {
            logDatabaseError($e, 'admin_handler - create-user');
            $response['message'] = 'js.api.errorDatabase';
        } catch (Exception $e) {
            // Capturar errores de validación
            $response['message'] = $e->getMessage();
            $data = [];
            if ($response['message'] === 'js.auth.errorPasswordMinLength') $data['length'] = MIN_PASSWORD_LENGTH;
            if ($response['message'] === 'js.auth.errorPasswordMaxLength') $data['length'] = MAX_PASSWORD_LENGTH;
            if ($response['message'] === 'js.auth.errorUsernameMinLength') $data['length'] = MIN_USERNAME_LENGTH;
            if ($response['message'] === 'js.auth.errorUsernameMaxLength') $data['length'] = MAX_USERNAME_LENGTH;
            if (!empty($data)) $response['data'] = $data;
        }

    // --- FIN DE 'create-user' ---
        
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
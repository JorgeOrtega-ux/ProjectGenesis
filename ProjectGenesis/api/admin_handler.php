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
        logDatabaseError($e, 'admin_handler - generateDefaultAvatar');
        return null;
    }
}

// --- ▼▼▼ ¡NUEVA FUNCIÓN AÑADIDA! (Copiada de settings_handler.php) ▼▼▼ ---
function deleteOldAvatar($oldUrl, $basePath)
{
    // Solo borrar avatares que están en la carpeta 'avatars_uploaded'
    if (strpos($oldUrl, '/assets/uploads/avatars_uploaded/') === false) {
        // Si no está en esa carpeta, es un avatar por defecto (o de ui-avatars), no lo borramos.
        return;
    }
    
    $relativePath = str_replace($basePath, '', $oldUrl);
    $serverPath = dirname(__DIR__) . $relativePath;

    if (file_exists($serverPath)) {
        @unlink($serverPath);
    }
}

// --- ▼▼▼ ¡NUEVA FUNCIÓN AÑADIDA! (Comprobador de permisos) ▼▼▼ ---
/**
 * Comprueba si un admin ($adminRole) puede modificar a un usuario ($targetRole).
 * @param string $adminRole Rol del admin (administrator, founder)
 * @param string $targetRole Rol del usuario a modificar (user, moderator, administrator, founder)
 * @return bool True si puede modificar, false si no.
 */
function canAdminModifyTarget($adminRole, $targetRole) {
    if ($adminRole === 'founder') {
        // Un fundador puede modificar a todos, excepto a otros fundadores
        return $targetRole !== 'founder';
    }
    if ($adminRole === 'administrator') {
        // Un admin solo puede modificar a usuarios y moderadores
        return $targetRole === 'user' || $targetRole === 'moderator';
    }
    return false;
}
// --- ▲▲▲ FIN DE NUEVA FUNCIÓN ▲▲▲ ---

// --- ▼▼▼ INICIO DE MODIFICACIÓN (CONSTANTES GLOBALES) ▼▼▼ ---
// Cargar valores desde $GLOBALS
$minPasswordLength = (int)($GLOBALS['site_settings']['min_password_length'] ?? 8);
$maxPasswordLength = (int)($GLOBALS['site_settings']['max_password_length'] ?? 72);
$minUsernameLength = (int)($GLOBALS['site_settings']['min_username_length'] ?? 6);
$maxUsernameLength = (int)($GLOBALS['site_settings']['max_username_length'] ?? 32);
$maxEmailLength = (int)($GLOBALS['site_settings']['max_email_length'] ?? 255);
// --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

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
        
    // --- ▼▼▼ ¡INICIO DE MODIFICACIÓN 'create-user'! ▼▼▼ ---
    } elseif ($action === 'create-user') {
        try {
            // 1. Obtener datos
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            // $passwordConfirm = $_POST['password_confirm'] ?? ''; // <-- ELIMINADO
            $role = $_POST['role'] ?? 'user';
            $is_2fa_enabled = isset($_POST['is_2fa_enabled']) && $_POST['is_2fa_enabled'] === '1' ? 1 : 0; // <-- AÑADIDO

            // 2. Validar Campos Vacíos
            if (empty($username) || empty($email) || empty($password) || empty($role)) { // <-- CAMPO ELIMINADO
                throw new Exception('js.auth.errorCompleteAllFields');
            }

            // 3. Validar Rol
            $allowedRoles = ['user', 'moderator', 'administrator'];
            if (!in_array($role, $allowedRoles)) {
                throw new Exception('admin.create.errorRole'); // Nueva clave i18n
            }

            // 4. Validar Email
            $domainsString = $GLOBALS['site_settings']['allowed_email_domains'] ?? '';
            $allowedDomains = preg_split('/[\s,]+/', $domainsString, -1, PREG_SPLIT_NO_EMPTY);
            $emailDomain = substr($email, strrpos($email, '@') + 1);

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('js.auth.errorInvalidEmail');
            }
            if (strlen($email) > $maxEmailLength) {
                throw new Exception('js.auth.errorEmailLength');
            }
            if (!empty($allowedDomains) && !in_array(strtolower($emailDomain), $allowedDomains)) {
                throw new Exception('js.auth.errorEmailDomain');
            }
            $stmt_check_email = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt_check_email->execute([$email]);
            if ($stmt_check_email->fetch()) {
                throw new Exception('js.auth.errorEmailInUse');
            }

            // 5. Validar Username
            if (strlen($username) < $minUsernameLength) {
                throw new Exception('js.auth.errorUsernameMinLength');
            }
            if (strlen($username) > $maxUsernameLength) {
                throw new Exception('js.auth.errorUsernameMaxLength');
            }
            $stmt_check_user = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt_check_user->execute([$username]);
            if ($stmt_check_user->fetch()) {
                throw new Exception('js.auth.errorUsernameInUse');
            }

            // 6. Validar Contraseña (Solo longitud, ya no hay confirmación)
            if (strlen($password) < $minPasswordLength) {
                throw new Exception('js.auth.errorPasswordMinLength');
            }
            if (strlen($password) > $maxPasswordLength) {
                throw new Exception('js.auth.errorPasswordLength');
            }
            // --- ELIMINADA LA VALIDACIÓN DE 'password' y 'passwordConfirm' ---


            // 7. Si todo es válido, crear usuario
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
            $authToken = bin2hex(random_bytes(32));

            $stmt_insert = $pdo->prepare(
                "INSERT INTO users (email, username, password, role, is_2fa_enabled, auth_token, account_status) 
                 VALUES (?, ?, ?, ?, ?, ?, 'active')"
            );
            $stmt_insert->execute([$email, $username, $passwordHash, $role, $is_2fa_enabled, $authToken]);
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
            if ($response['message'] === 'js.auth.errorPasswordMinLength') $data['length'] = $minPasswordLength;
            if ($response['message'] === 'js.auth.errorPasswordLength') $data = ['min' => $minPasswordLength, 'max' => $maxPasswordLength];
            if ($response['message'] === 'js.auth.errorUsernameMinLength') $data['length'] = $minUsernameLength;
            if ($response['message'] === 'js.auth.errorUsernameMaxLength') $data['length'] = $maxUsernameLength;
            if (!empty($data)) $response['data'] = $data;
        }
    // --- ▲▲▲ FIN DE MODIFICACIÓN 'create-user' ▲▲▲ ---
        
    // --- Lógica existente para 'set-role' y 'set-status' ---
    } elseif ($action === 'set-role') {

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

            // === ▼▼▼ LÓGICA DE PERMISOS MODIFICADA ▼▼▼ ===
            if (!canAdminModifyTarget($adminRole, $targetRole)) {
                 $response['message'] = ($targetRole === 'founder') ? 'js.admin.errorFounderTarget' : 'js.admin.errorAdminTarget';
                 echo json_encode($response);
                 exit;
            }
            // === ▲▲▲ FIN LÓGICA DE PERMISOS ▲▲▲ ===
            
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

        } catch (PDOException $e) {
            logDatabaseError($e, 'admin_handler - set-role');
            $response['message'] = 'js.api.errorDatabase';
        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }
    
    // --- ▼▼▼ INICIO DE LA MODIFICACIÓN (set-status) ▼▼▼ ---
    } elseif ($action === 'set-status') {

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

            if (!canAdminModifyTarget($adminRole, $targetRole)) {
                 $response['message'] = ($targetRole === 'founder') ? 'js.admin.errorFounderTarget' : 'js.admin.errorAdminTarget';
                 echo json_encode($response);
                 exit;
            }
            
            $allowedStatus = ['active', 'suspended', 'deleted'];
            if (!in_array($newValue, $allowedStatus)) {
                throw new Exception('js.api.invalidAction');
            }
            
            // 1. Actualizar la base de datos
            $stmt_update = $pdo->prepare("UPDATE users SET account_status = ? WHERE id = ?");
            $stmt_update->execute([$newValue, $targetUserId]);

            // 2. Notificar al servidor WebSocket (Fire and Forget)
            try {
                
                $curl_endpoint = '';
                $curl_payload = [];

                if ($newValue === 'suspended' || $newValue === 'deleted') {
                    // --- CASO 1: SUSPENDER O ELIMINAR ---
                    // Notificar al cliente para que vea la página de estado.
                    $curl_endpoint = 'http://127.0.0.1:8766/update-status';
                    $curl_payload = json_encode([
                        'user_id' => (int)$targetUserId,
                        'status'  => $newValue
                    ]);

                } else {
                    // --- CASO 2: REACTIVAR ---
                    // Forzar un logout de todas las sesiones de ese usuario.
                    
                    // A. Generar nuevo auth_token en la BD
                    $newAuthToken = bin2hex(random_bytes(32));
                    $stmt_token = $pdo->prepare("UPDATE users SET auth_token = ? WHERE id = ?");
                    $stmt_token->execute([$newAuthToken, $targetUserId]);
                    
                    // B. Enviar señal de 'kick' a Python
                    $curl_endpoint = 'http://127.0.0.1:8766/kick';
                    $curl_payload = json_encode([
                        'user_id' => (int)$targetUserId,
                        'exclude_session_id' => 'admin_kick_reactivate' // ID falso para no excluir a nadie
                    ]);
                }

                // Enviar la petición a Python
                $ch = curl_init($curl_endpoint);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $curl_payload);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($curl_payload)
                ]);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 500); // 500ms
                curl_setopt($ch, CURLOPT_TIMEOUT_MS, 500);        // 500ms

                curl_exec($ch); // No nos importa la respuesta, solo enviar
                curl_close($ch);
                
            } catch (Exception $e) {
                // Si el servidor WS falla, no detener la acción del admin.
                logDatabaseError($e, 'admin_handler - set-status (ws_notify_fail)');
            }

            // 3. Responder al admin
            $response['success'] = true;
            $response['message'] = 'js.admin.successStatus';

        } catch (PDOException $e) {
            logDatabaseError($e, 'admin_handler - set-status');
            $response['message'] = 'js.api.errorDatabase';
        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }
    }
    // --- ▲▲▲ FIN DE LA MODIFICACIÓN (set-status) ▲▲▲ ---
    
    // === ▼▼▼ INICIO DE NUEVAS ACCIONES DE EDICIÓN DE ADMIN ▼▼▼ ===

    elseif ($action === 'admin-upload-avatar') {
        try {
            $targetUserId = $_POST['target_user_id'] ?? 0;
            if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('js.settings.errorAvatarUpload');
            }

            $stmt_target = $pdo->prepare("SELECT role, profile_image_url FROM users WHERE id = ?");
            $stmt_target->execute([$targetUserId]);
            $targetUser = $stmt_target->fetch();
            if (!$targetUser) throw new Exception('js.auth.errorUserNotFound');
            if (!canAdminModifyTarget($adminRole, $targetUser['role'])) throw new Exception('js.admin.errorAdminTarget');
            
            $file = $_FILES['avatar'];
            
            // --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
            $maxSizeMB = (int)($GLOBALS['site_settings']['avatar_max_size_mb'] ?? 2);
            if ($file['size'] > $maxSizeMB * 1024 * 1024) {
                $response['data'] = ['size' => $maxSizeMB];
                throw new Exception('js.settings.errorAvatarSize');
            }
            // --- ▲▲▲ FIN DE MODIFICACIÓN ▼▼▼ ---

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file['tmp_name']);
            $allowedTypes = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/gif' => 'gif', 'image/webp' => 'webp'];
            if (!array_key_exists($mimeType, $allowedTypes)) throw new Exception('js.settings.errorAvatarFormat');
            
            $extension = $allowedTypes[$mimeType];
            $oldUrl = $targetUser['profile_image_url'];
            $newFileName = "user-{$targetUserId}-" . time() . "." . $extension;
            $saveDir = dirname(__DIR__) . '/assets/uploads/avatars_uploaded/';
            $newFilePath = $saveDir . $newFileName;
            $newPublicUrl = $basePath . '/assets/uploads/avatars_uploaded/' . $newFileName;

            if (!move_uploaded_file($file['tmp_name'], $newFilePath)) {
                throw new Exception('js.settings.errorAvatarSave');
            }
            $stmt = $pdo->prepare("UPDATE users SET profile_image_url = ? WHERE id = ?");
            $stmt->execute([$newPublicUrl, $targetUserId]);
            if ($oldUrl) {
                deleteOldAvatar($oldUrl, $basePath);
            }
            $response['success'] = true;
            $response['message'] = 'js.settings.successAvatarUpdate';
            $response['newAvatarUrl'] = $newPublicUrl;

        } catch (Exception $e) {
            if ($e instanceof PDOException) logDatabaseError($e, 'admin_handler - admin-upload-avatar');
            $response['message'] = $e->getMessage();
        }
    }

    elseif ($action === 'admin-remove-avatar') {
        try {
            $targetUserId = $_POST['target_user_id'] ?? 0;
            $stmt_target = $pdo->prepare("SELECT role, username, profile_image_url FROM users WHERE id = ?");
            $stmt_target->execute([$targetUserId]);
            $targetUser = $stmt_target->fetch();
            if (!$targetUser) throw new Exception('js.auth.errorUserNotFound');
            if (!canAdminModifyTarget($adminRole, $targetUser['role'])) throw new Exception('js.admin.errorAdminTarget');

            $oldUrl = $targetUser['profile_image_url'];
            $newDefaultUrl = generateDefaultAvatar($pdo, $targetUserId, $targetUser['username'], $basePath);
            if (!$newDefaultUrl) throw new Exception('js.settings.errorAvatarApi');

            $stmt = $pdo->prepare("UPDATE users SET profile_image_url = ? WHERE id = ?");
            $stmt->execute([$newDefaultUrl, $targetUserId]);
            if ($oldUrl) {
                deleteOldAvatar($oldUrl, $basePath);
            }
            $response['success'] = true;
            $response['message'] = 'js.settings.successAvatarRemoved';
            $response['newAvatarUrl'] = $newDefaultUrl;

        } catch (Exception $e) {
            if ($e instanceof PDOException) logDatabaseError($e, 'admin_handler - admin-remove-avatar');
            $response['message'] = $e->getMessage();
        }
    }
    
    elseif ($action === 'admin-update-username') {
        try {
            $targetUserId = $_POST['target_user_id'] ?? 0;
            $newUsername = trim($_POST['username'] ?? '');

            $stmt_target = $pdo->prepare("SELECT role, username, profile_image_url FROM users WHERE id = ?");
            $stmt_target->execute([$targetUserId]);
            $targetUser = $stmt_target->fetch();
            if (!$targetUser) throw new Exception('js.auth.errorUserNotFound');
            if (!canAdminModifyTarget($adminRole, $targetUser['role'])) throw new Exception('js.admin.errorAdminTarget');

            $oldUsername = $targetUser['username'];
            if (empty($newUsername)) throw new Exception('js.settings.errorUsernameEmpty');
            // --- ▼▼▼ MODIFICACIÓN ▼▼▼ ---
            if (strlen($newUsername) < $minUsernameLength) throw new Exception('js.auth.errorUsernameMinLength');
            if (strlen($newUsername) > $maxUsernameLength) throw new Exception('js.auth.errorUsernameMaxLength');
            // --- ▲▲▲ FIN MODIFICACIÓN ▲▲▲ ---
            if ($newUsername === $oldUsername) throw new Exception('js.settings.errorUsernameIsCurrent');

            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$newUsername, $targetUserId]);
            if ($stmt->fetch()) throw new Exception('js.auth.errorUsernameInUse');

            $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
            $stmt->execute([$newUsername, $targetUserId]);
            
            $stmt_log = $pdo->prepare("INSERT INTO user_audit_logs (user_id, change_type, old_value, new_value, changed_by_ip) VALUES (?, 'username', ?, ?, ?)");
            $stmt_log->execute([$targetUserId, $oldUsername, $newUsername, getIpAddress()]);

            $response['success'] = true;
            $response['message'] = 'js.settings.successUsernameUpdate';
            
            // --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
            $response['newUsername'] = $newUsername;
            // Comprobar si el avatar por defecto necesita actualizarse
            $oldUrl = $targetUser['profile_image_url'];
            $isDefaultAvatar = strpos($oldUrl, '/assets/uploads/avatars_uploaded/') === false;
            
            if ($isDefaultAvatar) {
                $oldInitial = mb_substr($oldUsername, 0, 1, 'UTF-8');
                $newInitial = mb_substr($newUsername, 0, 1, 'UTF-8');

                if (strcasecmp($oldInitial, $newInitial) !== 0) {
                    $newAvatarUrl = generateDefaultAvatar($pdo, $targetUserId, $newUsername, $basePath);
                    if ($newAvatarUrl) {
                        $stmt_avatar = $pdo->prepare("UPDATE users SET profile_image_url = ? WHERE id = ?");
                        $stmt_avatar->execute([$newAvatarUrl, $targetUserId]);
                        $response['newAvatarUrl'] = $newAvatarUrl;
                    }
                }
            }
            // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---


        } catch (Exception $e) {
            if ($e instanceof PDOException) logDatabaseError($e, 'admin_handler - admin-update-username');
            $response['message'] = $e->getMessage();
            // --- ▼▼▼ MODIFICACIÓN ▼▼▼ ---
            if ($response['message'] === 'js.auth.errorUsernameMinLength') $response['data'] = ['length' => $minUsernameLength];
            elseif ($response['message'] === 'js.auth.errorUsernameMaxLength') $response['data'] = ['length' => $maxUsernameLength];
            // --- ▲▲▲ FIN MODIFICACIÓN ▲▲▲ ---
        }
    }
    
    elseif ($action === 'admin-update-email') {
        try {
            $targetUserId = $_POST['target_user_id'] ?? 0;
            $newEmail = trim($_POST['email'] ?? '');

            $stmt_target = $pdo->prepare("SELECT role, email FROM users WHERE id = ?");
            $stmt_target->execute([$targetUserId]);
            $targetUser = $stmt_target->fetch();
            if (!$targetUser) throw new Exception('js.auth.errorUserNotFound');
            if (!canAdminModifyTarget($adminRole, $targetUser['role'])) throw new Exception('js.admin.errorAdminTarget');

            $oldEmail = $targetUser['email'];
            if (empty($newEmail) || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) throw new Exception('js.auth.errorInvalidEmail');
            // --- ▼▼▼ MODIFICACIÓN ▼▼▼ ---
            if (strlen($newEmail) > $maxEmailLength) throw new Exception('js.auth.errorEmailLength');
            // --- ▲▲▲ FIN MODIFICACIÓN ▲▲▲ ---
            if ($newEmail === $oldEmail) throw new Exception('js.settings.errorEmailIsCurrent');
            
            // --- ▼▼▼ INICIO DE MODIFICACIÓN (DOMINIOS GLOBALES) ▼▼▼ ---
            $domainsString = $GLOBALS['site_settings']['allowed_email_domains'] ?? '';
            $allowedDomains = preg_split('/[\s,]+/', $domainsString, -1, PREG_SPLIT_NO_EMPTY);
            // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
            $emailDomain = substr($newEmail, strrpos($newEmail, '@') + 1);
            // --- ▼▼▼ INICIO DE MODIFICACIÓN (DOMINIOS GLOBALES) ▼▼▼ ---
            if (!empty($allowedDomains) && !in_array(strtolower($emailDomain), $allowedDomains)) {
                throw new Exception('js.auth.errorEmailDomain');
            }
            // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$newEmail, $targetUserId]);
            if ($stmt->fetch()) throw new Exception('js.auth.errorEmailInUse');

            $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
            $stmt->execute([$newEmail, $targetUserId]);

            $stmt_log_email = $pdo->prepare("INSERT INTO user_audit_logs (user_id, change_type, old_value, new_value, changed_by_ip) VALUES (?, 'email', ?, ?, ?)");
            $stmt_log_email->execute([$targetUserId, $oldEmail, $newEmail, getIpAddress()]);

            $response['success'] = true;
            $response['message'] = 'js.settings.successEmailUpdate';
            $response['newEmail'] = $newEmail; // --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---

        } catch (Exception $e) {
            if ($e instanceof PDOException) logDatabaseError($e, 'admin_handler - admin-update-email');
            $response['message'] = 'js.api.errorDatabase';
        }
    }
    
    elseif ($action === 'admin-update-password') {
        try {
            $targetUserId = $_POST['target_user_id'] ?? 0;
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            $stmt_target = $pdo->prepare("SELECT role, password FROM users WHERE id = ?");
            $stmt_target->execute([$targetUserId]);
            $targetUser = $stmt_target->fetch();
            if (!$targetUser) throw new Exception('js.auth.errorUserNotFound');
            if (!canAdminModifyTarget($adminRole, $targetUser['role'])) throw new Exception('js.admin.errorAdminTarget');
            
            $oldHashedPassword = $targetUser['password'];

            if (empty($newPassword) || empty($confirmPassword)) {
                throw new Exception('admin.edit.errorPassEmpty'); // Nuevo i18n
            }
            // --- ▼▼▼ INICIO DE MODIFICACIÓN (PASS GLOBAL) ▼▼▼ ---
            if (strlen($newPassword) < $minPasswordLength) throw new Exception('js.auth.errorPasswordMinLength');
            if (strlen($newPassword) > $maxPasswordLength) throw new Exception('js.auth.errorPasswordLength');
            // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
            if ($newPassword !== $confirmPassword) throw new Exception('js.auth.errorPasswordMismatch');

            $newHashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$newHashedPassword, $targetUserId]);

            $stmt_log_pass = $pdo->prepare("INSERT INTO user_audit_logs (user_id, change_type, old_value, new_value, changed_by_ip) VALUES (?, 'password', ?, ?, ?)");
            $stmt_log_pass->execute([$targetUserId, $oldHashedPassword, $newHashedPassword, getIpAddress()]);

            $response['success'] = true;
            $response['message'] = 'js.settings.successPassUpdate';
            $response['newPasswordHash'] = $newHashedPassword; // <-- ¡LÍNEA AÑADIDA!

        } catch (Exception $e) {
            if ($e instanceof PDOException) logDatabaseError($e, 'admin_handler - admin-update-password');
            $response['message'] = $e->getMessage();
            // --- ▼▼▼ INICIO DE MODIFICACIÓN (PASS GLOBAL) ▼▼▼ ---
            if ($response['message'] === 'js.auth.errorPasswordMinLength') $response['data'] = ['length' => $minPasswordLength];
            elseif ($response['message'] === 'js.auth.errorPasswordLength') $response['data'] = ['min' => $minPasswordLength, 'max' => $maxPasswordLength];
            // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
        }
    }
    
    // === ▲▲▲ FIN DE NUEVAS ACCIONES DE EDICIÓN DE ADMIN ▲▲▲ ===

    // --- ▼▼▼ INICIO DE MODIFICACIÓN (MODO MANTENIMIENTO) ▼▼▼ ---
    elseif ($action === 'update-maintenance-mode') {
        // Solo los 'founder' pueden cambiar esto.
        if ($adminRole !== 'founder') {
            $response['message'] = 'js.admin.errorAdminTarget'; // Sin permiso
            echo json_encode($response);
            exit;
        }

        try {
            $newValue = $_POST['new_value'] ?? '0';

            // Validar que el valor sea solo 0 o 1
            if ($newValue !== '0' && $newValue !== '1') {
                throw new Exception('js.api.invalidAction');
            }
            
            $pdo->beginTransaction();

            $stmt_maintenance = $pdo->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = 'maintenance_mode'");
            $stmt_maintenance->execute([$newValue]);
            
            $registrationValue = null;
            $kicked_users_count = 0; // Contador para la nueva lógica

            // ¡LÓGICA DE VINCULACIÓN!
            // Si el modo mantenimiento se ACTIVA, forzar el bloqueo de registros Y EXPULSAR USUARIOS.
            if ($newValue === '1') {
                // 1. Forzar bloqueo de registros
                $stmt_registration = $pdo->prepare("UPDATE site_settings SET setting_value = '0' WHERE setting_key = 'allow_new_registrations'");
                $stmt_registration->execute();
                $registrationValue = '0';
                
                // --- ▼▼▼ INICIO DE NUEVA LÓGICA DE EXPULSIÓN MASIVA ▼▼▼ ---
                
                // 2. Obtener IDs de todos los usuarios (no-staff) activos
                $stmt_users_to_kick = $pdo->prepare(
                    "SELECT id FROM users WHERE role = 'user' AND account_status = 'active'"
                );
                $stmt_users_to_kick->execute();
                $userIdsToKick = $stmt_users_to_kick->fetchAll(PDO::FETCH_COLUMN);
                
                if (!empty($userIdsToKick)) {
                    $kicked_users_count = count($userIdsToKick);
                    // 3. Notificar al servidor WebSocket (Fire and Forget)
                    try {
                        $kickPayload = json_encode([
                            'user_ids' => $userIdsToKick // Enviar el array de IDs
                        ]);

                        $ch = curl_init('http://127.0.0.1:8766/kick-bulk'); // Nuevo endpoint
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $kickPayload);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                            'Content-Type: application/json',
                            'Content-Length: ' . strlen($kickPayload)
                        ]);
                        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 500); // 500ms
                        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 500);        // 500ms

                        curl_exec($ch); // No nos importa la respuesta, solo enviar
                        curl_close($ch);
                        
                    } catch (Exception $e) {
                        // Si el servidor WS falla, no detener la acción del admin.
                        logDatabaseError($e, 'admin_handler - maintenance (kick_bulk_ws_fail)');
                    }
                }
                // --- ▲▲▲ FIN DE NUEVA LÓGICA DE EXPULSIÓN MASIVA ▲▲▲ ---
            }
            
            $pdo->commit();

            $response['success'] = true;
            $response['message'] = 'js.admin.maintenanceSuccess'; // Nueva clave i18n
            $response['newValue'] = $newValue;
            if ($registrationValue !== null) {
                $response['registrationValue'] = $registrationValue; // Enviar el valor forzado al JS
            }
            if ($kicked_users_count > 0) {
                 // (Opcional) Informar al admin cuántos usuarios fueron expulsados
                 // $response['message'] .= " (Expulsando a $kicked_users_count usuarios)";
            }

        } catch (Exception $e) {
            $pdo->rollBack();
            if ($e instanceof PDOException) {
                logDatabaseError($e, 'admin_handler - update-maintenance-mode');
                $response['message'] = 'js.api.errorDatabase';
            } else {
                $response['message'] = $e->getMessage();
            }
        }
    }
    // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
    
    // --- ▼▼▼ INICIO DE NUEVA ACCIÓN (BLOQUEAR REGISTROS) ▼▼▼ ---
    elseif ($action === 'update-registration-mode') {
        // Solo los 'founder' pueden cambiar esto.
        if ($adminRole !== 'founder') {
            $response['message'] = 'js.admin.errorAdminTarget';
            echo json_encode($response);
            exit;
        }
        
        try {
            $newValue = $_POST['new_value'] ?? '0';
            if ($newValue !== '0' && $newValue !== '1') {
                throw new Exception('js.api.invalidAction');
            }

            // Comprobar si el modo mantenimiento está activo
            $stmt_check_maintenance = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'maintenance_mode'");
            $stmt_check_maintenance->execute();
            $maintenanceMode = $stmt_check_maintenance->fetchColumn();

            // Si el mantenimiento está activo, NO se puede activar el registro
            if ($maintenanceMode === '1' && $newValue === '1') {
                throw new Exception('js.admin.errorRegInMaintenance'); // Nueva clave i18n
            }
            
            // Si no, proceder con la actualización
            $stmt = $pdo->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = 'allow_new_registrations'");
            $stmt->execute([$newValue]);

            $response['success'] = true;
            $response['message'] = 'js.admin.registrationSuccess'; // Nueva clave i18n
            $response['newValue'] = $newValue;

        } catch (Exception $e) {
            if ($e instanceof PDOException) {
                logDatabaseError($e, 'admin_handler - update-registration-mode');
                $response['message'] = 'js.api.errorDatabase';
            } else {
                $response['message'] = $e->getMessage();
            }
        }
    }
    // --- ▲▲▲ FIN DE NUEVA ACCIÓN ▲▲▲ ---

    // --- ▼▼▼ INICIO DE NUEVAS ACCIONES DE DOMINIO ▼▼▼ ---
    elseif ($action === 'admin-add-domain') {
        if ($adminRole !== 'founder') {
            $response['message'] = 'js.admin.errorAdminTarget';
            echo json_encode($response);
            exit;
        }
        try {
            $newDomain = strtolower(trim($_POST['new_domain'] ?? ''));

            if (empty($newDomain)) {
                throw new Exception('js.admin.domainEmpty');
            }
            if (!preg_match('/^[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,}$/i', $newDomain)) {
                throw new Exception('js.admin.domainInvalid');
            }

            $stmt_get = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'allowed_email_domains'");
            $stmt_get->execute();
            $domainsString = $stmt_get->fetchColumn();
            
            $domains = preg_split('/[\s,]+/', $domainsString, -1, PREG_SPLIT_NO_EMPTY);
            
            if (in_array($newDomain, $domains)) {
                throw new Exception('js.admin.domainExists');
            }

            $domains[] = $newDomain;
            $newDomainsString = implode("\n", $domains);

            $stmt_set = $pdo->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = 'allowed_email_domains'");
            $stmt_set->execute([$newDomainsString]);

            $response['success'] = true;
            $response['message'] = 'js.admin.domainAdded';
            $response['domain'] = $newDomain;

        } catch (Exception $e) {
            if ($e instanceof PDOException) logDatabaseError($e, 'admin_handler - admin-add-domain');
            $response['message'] = $e->getMessage();
        }
    }
    elseif ($action === 'admin-remove-domain') {
        if ($adminRole !== 'founder') {
            $response['message'] = 'js.admin.errorAdminTarget';
            echo json_encode($response);
            exit;
        }
        try {
            $domainToRemove = strtolower(trim($_POST['domain_to_remove'] ?? ''));
            if (empty($domainToRemove)) {
                throw new Exception('js.api.invalidAction');
            }
            
            $stmt_get = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'allowed_email_domains'");
            $stmt_get->execute();
            $domainsString = $stmt_get->fetchColumn();
            
            $domains = preg_split('/[\s,]+/', $domainsString, -1, PREG_SPLIT_NO_EMPTY);
            
            // Filtrar el array (case-insensitive)
            $newDomains = array_filter($domains, function($domain) use ($domainToRemove) {
                return strtolower($domain) !== $domainToRemove;
            });

            $newDomainsString = implode("\n", $newDomains);

            $stmt_set = $pdo->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = 'allowed_email_domains'");
            $stmt_set->execute([$newDomainsString]);

            $response['success'] = true;
            $response['message'] = 'js.admin.domainRemoved';

        } catch (Exception $e) {
            if ($e instanceof PDOException) logDatabaseError($e, 'admin_handler - admin-remove-domain');
            $response['message'] = 'js.admin.domainRemoveError';
        }
    }
    // --- ▲▲▲ FIN DE NUEVAS ACCIONES DE DOMINIO ▲▲▲ ---

    // --- ▼▼▼ INICIO DE MODIFICACIÓN (ACCIÓN 'update-allowed-email-domains' ELIMINADA) ▼▼▼ ---
    elseif (
        $action === 'update-username-cooldown' || $action === 'update-email-cooldown' || $action === 'update-avatar-max-size' ||
        $action === 'update-min-password-length' || $action === 'update-max-login-attempts' || $action === 'update-lockout-time-minutes' ||
        // 'update-allowed-email-domains' se ha quitado de esta lista
        $action === 'update-max-password-length' ||
        // --- ▼▼▼ MODIFICACIÓN: Claves añadidas ▼▼▼ ---
        $action === 'update-min-username-length' || $action === 'update-max-username-length' ||
        $action === 'update-max-email-length' || $action === 'update-code-resend-cooldown' ||
        $action === 'update-max-concurrent-users' // <-- ¡LÍNEA AÑADIDA!
        // --- ▲▲▲ FIN MODIFICACIÓN ▲▲▲ ---
    ) {
    // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
        
        if ($adminRole !== 'founder') {
            $response['message'] = 'js.admin.errorAdminTarget';
            echo json_encode($response);
            exit;
        }

        try {
            $newValue = trim($_POST['new_value'] ?? '');
            
            // Determinar la clave de la BD
            $settingKeyMap = [
                'update-username-cooldown' => 'username_cooldown_days',
                'update-email-cooldown' => 'email_cooldown_days',
                'update-avatar-max-size' => 'avatar_max_size_mb',
                'update-min-password-length' => 'min_password_length',
                'update-max-login-attempts' => 'max_login_attempts',
                'update-lockout-time-minutes' => 'lockout_time_minutes',
                // 'allowed_email_domains' se ha quitado de este mapa
                'update-max-password-length' => 'max_password_length',
                // --- ▼▼▼ MODIFICACIÓN: Claves añadidas ▼▼▼ ---
                'update-min-username-length' => 'min_username_length',
                'update-max-username-length' => 'max_username_length',
                'update-max-email-length' => 'max_email_length',
                'update-code-resend-cooldown' => 'code_resend_cooldown_seconds',
                'update-max-concurrent-users' => 'max_concurrent_users' // <-- ¡LÍNEA AÑADIDA!
                // --- ▲▲▲ FIN MODIFICACIÓN ▲▲▲ ---
            ];
            
            if (!isset($settingKeyMap[$action])) {
                 throw new Exception('js.api.invalidAction');
            }
            
            $dbKey = $settingKeyMap[$action];

            // Validar que el valor sea un número positivo
            // --- ▼▼▼ MODIFICACIÓN: Permitir 0 para cooldowns si se desea ---
            if (!is_numeric($newValue) || (int)$newValue < 0) {
            // --- ▲▲▲ FIN MODIFICACIÓN ▲▲▲ ---
                throw new Exception('js.api.invalidAction'); // Error genérico
            }
            $finalValue = (int)$newValue;
            
            // --- ▼▼▼ MODIFICACIÓN: Añadir validación de rangos ▼▼▼ ---
            if ($dbKey === 'min_password_length' && ($finalValue < 8 || $finalValue > 72)) {
                 throw new Exception('js.api.invalidAction');
            }
            if ($dbKey === 'max_password_length' && ($finalValue < 8 || $finalValue > 72)) {
                 throw new Exception('js.api.invalidAction');
            }
            // (Puedes añadir más validaciones de rango aquí si lo deseas)
            // --- ▲▲▲ FIN MODIFICACIÓN ▲▲▲ ---

            $stmt = $pdo->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$finalValue, $dbKey]);

            $response['success'] = true;
            $response['message'] = 'js.admin.settingUpdateSuccess'; // Mensaje genérico
            $response['newValue'] = $finalValue;
            $response['settingKey'] = $dbKey;

        } catch (Exception $e) {
            if ($e instanceof PDOException) {
                logDatabaseError($e, "admin_handler - $action");
                $response['message'] = 'js.api.errorDatabase';
            } else {
                $response['message'] = $e->getMessage();
            }
        }
    }
    
    // --- ▼▼▼ INICIO DE BLOQUE AÑADIDO (LÓGICA DE BACKUP) ▼▼▼ ---
    
    elseif ($action === 'create-backup' || $action === 'restore-backup' || $action === 'delete-backup') {
        
        // ¡¡¡DOBLE COMPROBACIÓN DE SEGURIDAD!!! SOLO FOUNDER PUEDE HACER ESTO.
        if ($adminRole !== 'founder') {
            $response['message'] = 'js.admin.errorAdminTarget';
            echo json_encode($response);
            exit;
        }
        
        $backupDir = dirname(__DIR__) . '/backups'; // Sube 2 niveles (api/ -> projectgenesis/) y luego a /backups
        if (!is_dir($backupDir)) @mkdir($backupDir, 0755, true);
        
        if (!is_writable($backupDir)) {
             $response['message'] = 'admin.backups.errorDirWrite';
             logDatabaseError(new Exception("API: El directorio de backups no tiene permisos de escritura: " . $backupDir), 'admin_handler - backup');
             echo json_encode($response);
             exit;
        }

        try {
            if ($action === 'create-backup') {
                $filename = 'backup_' . DB_NAME . '_' . date('Y-m-d_H-i-s') . '.sql';
                $filepath = $backupDir . '/' . $filename;
                
                // Usar las constantes definidas en config.php
                $command = sprintf(
                    'mysqldump --host=%s --user=%s --password=%s --result-file=%s %s',
                    escapeshellarg(DB_HOST),
                    escapeshellarg(DB_USER),
                    escapeshellarg(DB_PASS),
                    escapeshellarg($filepath),
                    escapeshellarg(DB_NAME)
                );
                
                exec($command . ' 2>&1', $output, $return_var);
                if ($return_var !== 0) throw new Exception('admin.backups.errorExecCreate' . ' ' . implode('; ', $output));
                
                $response['success'] = true;
                $response['message'] = 'admin.backups.successCreate';

            } elseif ($action === 'restore-backup') {
                $filename = $_POST['filename'] ?? '';
                $safeFilename = basename($filename); // SANITIZACIÓN CRÍTICA
                $filepath = $backupDir . '/' . $safeFilename;

                if (empty($safeFilename) || $safeFilename !== $filename || !file_exists($filepath)) {
                    throw new Exception('admin.backups.errorFileNotFound');
                }
                
                $command = sprintf(
                    'mysql --host=%s --user=%s --password=%s %s < %s',
                    escapeshellarg(DB_HOST),
                    escapeshellarg(DB_USER),
                    escapeshellarg(DB_PASS),
                    escapeshellarg(DB_NAME),
                    escapeshellarg($filepath)
                );
                
                exec($command . ' 2>&1', $output, $return_var);
                if ($return_var !== 0) throw new Exception('admin.backups.errorExecRestore' . ' ' . implode('; ', $output));
                
                $response['success'] = true;
                $response['message'] = 'admin.backups.successRestore';

            } elseif ($action === 'delete-backup') {
                $filename = $_POST['filename'] ?? '';
                $safeFilename = basename($filename); // SANITIZACIÓN CRÍTICA
                $filepath = $backupDir . '/' . $safeFilename;
                
                if (empty($safeFilename) || $safeFilename !== $filename || !file_exists($filepath)) {
                    throw new Exception('admin.backups.errorFileNotFound');
                }
                
                if (!@unlink($filepath)) throw new Exception('admin.backups.errorDelete');
                
                $response['success'] = true;
                $response['message'] = 'admin.backups.successDelete';
            }
        
        } catch (Exception $e) {
            logDatabaseError(new Exception($e->getMessage()), 'admin_handler - ' . $action);
            $response['message'] = $e->getMessage();
        }
    }
    
    // --- ▲▲▲ FIN DE BLOQUE AÑADIDO ---
    
    // --- ▼▼▼ EL BLOQUE 'get-concurrent-users' SE HA ELIMINADO ▼▼▼ ---
    
} else {
    // Si no es POST, se mantiene el error por defecto
    $response['message'] = 'js.api.invalidAction';
}
// --- FIN DE MODIFICACIÓN ---

echo json_encode($response);
exit;
?>
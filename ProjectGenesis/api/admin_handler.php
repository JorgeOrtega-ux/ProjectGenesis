<?php

include '../config/config.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'js.api.invalidAction'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'js.settings.errorNoSession';
    echo json_encode($response);
    exit;
}

$adminUserId = $_SESSION['user_id'];
$adminRole = $_SESSION['role'] ?? 'user';

if ($adminRole !== 'administrator' && $adminRole !== 'founder') {
    $response['message'] = 'js.admin.errorAdminTarget'; 
    echo json_encode($response);
    exit;
}

function generateUuidV4() {
    try {
        $data = random_bytes(16);

        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); 
        
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); 

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    } catch (Exception $e) {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
function generateDefaultAvatar($pdo, $userId, $username, $basePath)
{
    try {
        $savePathDir = dirname(__DIR__) . '/assets/uploads/avatars_default'; 
        $fileName = "user-{$userId}.png";
        $fullSavePath = $savePathDir . '/' . $fileName;
        $publicUrl = $basePath . '/assets/uploads/avatars_default/' . $fileName; 

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

function deleteOldAvatar($oldUrl, $basePath)
{
    if (strpos($oldUrl, '/assets/uploads/avatars_uploaded/') === false) {
        return;
    }
    
    $relativePath = str_replace($basePath, '', $oldUrl);
    $serverPath = dirname(__DIR__) . $relativePath;

    if (file_exists($serverPath)) {
        @unlink($serverPath);
    }
}

function canAdminModifyTarget($adminRole, $targetRole) {
    if ($adminRole === 'founder') {
        return $targetRole !== 'founder';
    }
    if ($adminRole === 'administrator') {
        return $targetRole === 'user' || $targetRole === 'moderator';
    }
    return false;
}

function generateSecurePasswordPhp($length = 16) {
    $lower = 'abcdefghijklmnopqrstuvwxyz';
    $upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $numbers = '0123456789';
    $special = '!@#$%^&*()_+[]{}|;:,.<>?';
    $all = $lower . $upper . $numbers . $special;

    $password = '';
    $password .= $lower[random_int(0, strlen($lower) - 1)];
    $password .= $upper[random_int(0, strlen($upper) - 1)];
    $password .= $numbers[random_int(0, strlen($numbers) - 1)];
    $password .= $special[random_int(0, strlen($special) - 1)];

    for ($i = 4; $i < $length; $i++) {
        $password .= $all[random_int(0, strlen($all) - 1)];
    }

    return str_shuffle($password);
}



function generateDefaultCommunityIcon($pdo, $communityId, $communityName, $basePath) {
    try {
        $savePathDir = dirname(__DIR__) . '/assets/uploads/communities_default'; 
        $fileName = "community-{$communityId}.png";
        $fullSavePath = $savePathDir . '/' . $fileName;
        $publicUrl = $basePath . '/assets/uploads/communities_default/' . $fileName; 

        if (!is_dir($savePathDir)) {
            @mkdir($savePathDir, 0755, true);
        }

        $avatarColors = ['206BD3', 'D32029', '28A745', 'E91E63', 'F57C00', '673AB7', '009688'];
        $selectedColor = $avatarColors[array_rand($avatarColors)];
        $nameParam = urlencode($communityName);

        $apiUrl = "https://ui-avatars.com/api/?name={$nameParam}&size=256&background={$selectedColor}&color=ffffff&bold=true&length=2";
        $imageData = @file_get_contents($apiUrl);

        if ($imageData === false) return null;
        file_put_contents($fullSavePath, $imageData);
        return $publicUrl;
    } catch (Exception $e) {
        logDatabaseError($e, 'admin_handler - generateDefaultCommunityIcon');
        return null;
    }
}

function deleteOldCommunityImage($oldUrl, $basePath) {
    if (strpos($oldUrl, '/assets/uploads/communities_uploaded/') === false) {
        return; 
    }
    $relativePath = str_replace($basePath, '', $oldUrl);
    $serverPath = dirname(__DIR__) . $relativePath;
    if (file_exists($serverPath)) {
        @unlink($serverPath);
    }
}

function generateAccessCode($length = 12) { 
    $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = '';
    $max = strlen($chars) - 1;
    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[random_int(0, $max)];
    }
    return substr($code, 0, 4) . '-' . substr($code, 4, 4) . '-' . substr($code, 8, 4);
}


// --- ▼▼▼ INICIO DE FUNCIÓN MODIFICADA ▼▼▼ ---
/**
 * Calcula un timestamp SQL futuro basado en un número de días.
 * (Función ya no es necesaria para la lógica simplificada, pero se deja por si acaso)
 * @param string $daysString El número de días.
 * @return string|null
 */
function formatExpireDateFromDays($daysString) {
    if (empty($daysString)) {
        return null; // Permanente
    }
    $days = (int)$daysString;
    if ($days <= 0) {
        return null; // Permanente
    }
    try {
        $date = new DateTime('now', new DateTimeZone('UTC'));
        // Añadir P(Period) + X + D(Days)
        $date->add(new DateInterval("P{$days}D")); 
        return $date->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        // En caso de error (ej. días negativos), tratar como permanente
        return null;
    }
}
// --- ▲▲▲ FIN DE FUNCIÓN MODIFICADA ▲▲▲ ---


$minPasswordLength = (int)($GLOBALS['site_settings']['min_password_length'] ?? 8);
$maxPasswordLength = (int)($GLOBALS['site_settings']['max_password_length'] ?? 72);
$minUsernameLength = (int)($GLOBALS['site_settings']['min_username_length'] ?? 6);
$maxUsernameLength = (int)($GLOBALS['site_settings']['max_username_length'] ?? 32);
$maxEmailLength = (int)($GLOBALS['site_settings']['max_email_length'] ?? 255);


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($submittedToken)) {
        $response['message'] = 'js.api.errorSecurityRefresh';
        echo json_encode($response);
        exit;
    }
    
    $action = $_POST['action'] ?? '';

    if ($action === 'get-users') {
        
        try {
            $defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";
            
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

            $usersPerPage = 1; 
            $totalUsers = 0;
            $totalPages = 1;

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

            $response['success'] = true;
            $response['users'] = $formattedUsers;
            $response['totalUsers'] = $totalUsers;
            $response['totalPages'] = $totalPages;
            $response['currentPage'] = $adminCurrentPage;

        } catch (PDOException $e) {
            logDatabaseError($e, 'admin_handler - get-users');
            $response['message'] = 'js.api.errorDatabase';
        }
        
    } elseif ($action === 'create-user') {
        try {
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'user';
            $is_2fa_enabled = isset($_POST['is_2fa_enabled']) && $_POST['is_2fa_enabled'] === '1' ? 1 : 0; 

            if (empty($username) || empty($email) || empty($password) || empty($role)) { 
                throw new Exception('js.auth.errorCompleteAllFields');
            }

            $allowedRoles = ['user', 'moderator', 'administrator'];
            if (!in_array($role, $allowedRoles)) {
                throw new Exception('admin.create.errorRole'); 
            }

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

            if (strlen($password) < $minPasswordLength) {
                throw new Exception('js.auth.errorPasswordMinLength');
            }
            if (strlen($password) > $maxPasswordLength) {
                throw new Exception('js.auth.errorPasswordLength');
            }


            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
            $authToken = bin2hex(random_bytes(32));

            $uuid = generateUuidV4(); 

            $stmt_insert = $pdo->prepare(
                "INSERT INTO users (uuid, email, username, password, role, is_2fa_enabled, auth_token, account_status) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'active')"
            );
            $stmt_insert->execute([$uuid, $email, $username, $passwordHash, $role, $is_2fa_enabled, $authToken]);
            
            $newUserId = $pdo->lastInsertId();

            $localAvatarUrl = generateDefaultAvatar($pdo, $newUserId, $username, $basePath);
            if ($localAvatarUrl) {
                $stmt_avatar = $pdo->prepare("UPDATE users SET profile_image_url = ? WHERE id = ?");
                $stmt_avatar->execute([$localAvatarUrl, $newUserId]);
            }

            $preferredLanguage = $_SESSION['language'] ?? 'es-latam'; 
            $stmt_prefs = $pdo->prepare(
                "INSERT INTO user_preferences (user_id, language, theme, usage_type) 
                 VALUES (?, ?, 'system', 'personal')"
            );
            $stmt_prefs->execute([$newUserId, $preferredLanguage]);

            $response['success'] = true;
            $response['message'] = 'admin.create.success'; 

        } catch (PDOException $e) {
            logDatabaseError($e, 'admin_handler - create-user');
            $response['message'] = 'js.api.errorDatabase';
        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
            $data = [];
            if ($response['message'] === 'js.auth.errorPasswordMinLength') $data['length'] = $minPasswordLength;
            if ($response['message'] === 'js.auth.errorPasswordLength') $data = ['min' => $minPasswordLength, 'max' => $maxPasswordLength];
            if ($response['message'] === 'js.auth.errorUsernameMinLength') $data['length'] = $minUsernameLength;
            if ($response['message'] === 'js.auth.errorUsernameMaxLength') $data['length'] = $maxUsernameLength;
            if (!empty($data)) $response['data'] = $data;
        }
        
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

            if (!canAdminModifyTarget($adminRole, $targetRole)) {
                 $response['message'] = ($targetRole === 'founder') ? 'js.admin.errorFounderTarget' : 'js.admin.errorAdminTarget';
                 echo json_encode($response);
                 exit;
            }
            
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
            
            $allowedStatus = ['active', 'deleted'];
            if (!in_array($newValue, $allowedStatus)) {
                throw new Exception('js.api.invalidAction');
            }
            
            $stmt_update = $pdo->prepare("UPDATE users SET account_status = ? WHERE id = ?");
            $stmt_update->execute([$newValue, $targetUserId]);

            try {
                
                $curl_endpoint = '';
                $curl_payload = [];

                if ($newValue === 'deleted') {
                    $curl_endpoint = 'http://127.0.0.1:8766/update-status';
                    $curl_payload = json_encode([
                        'user_id' => (int)$targetUserId,
                        'status'  => $newValue
                    ]);

                } else {
                    
                    $newAuthToken = bin2hex(random_bytes(32));
                    $stmt_token = $pdo->prepare("UPDATE users SET auth_token = ? WHERE id = ?");
                    $stmt_token->execute([$newAuthToken, $targetUserId]);
                    
                    $curl_endpoint = 'http://127.0.0.1:8766/kick';
                    $curl_payload = json_encode([
                        'user_id' => (int)$targetUserId,
                        'exclude_session_id' => 'admin_kick_reactivate' 
                    ]);
                }

                $ch = curl_init($curl_endpoint);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $curl_payload);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($curl_payload)
                ]);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 500); 
                curl_setopt($ch, CURLOPT_TIMEOUT_MS, 500);        

                curl_exec($ch); 
                curl_close($ch);
                
            } catch (Exception $e) {
                logDatabaseError($e, 'admin_handler - set-status (ws_notify_fail)');
            }

            $response['success'] = true;
            $response['message'] = 'js.admin.successStatus';

        } catch (PDOException $e) {
            logDatabaseError($e, 'admin_handler - set-status');
            $response['message'] = 'js.api.errorDatabase';
        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }
    
    // --- ▼▼▼ INICIO DE MODIFICACIÓN (NUEVA ACCIÓN) ▼▼▼ ---
    } elseif ($action === 'admin-update-restrictions') {
        
        $targetUserId = (int)($_POST['target_user_id'] ?? 0);
        $generalStatus = $_POST['general_status'] ?? 'active';
        
        // --- Variables de días eliminadas ---
        
        $restrictPublish = $_POST['restrict_publish'] ?? 'false';
        $restrictComment = $_POST['restrict_comment'] ?? 'false';
        $restrictMessage = $_POST['restrict_message'] ?? 'false';
        $restrictSocial = $_POST['restrict_social'] ?? 'false';


        // --- Validación y Seguridad ---
        if (empty($targetUserId)) {
            throw new Exception('js.auth.errorCompleteFields');
        }
        if ($targetUserId == $adminUserId) {
            throw new Exception('js.admin.errorSelf');
        }
        if (!in_array($generalStatus, ['active', 'suspended', 'deleted'])) {
            $generalStatus = 'active'; // Default a 'active' si es inválido
        }

        try {
            // Comprobar si el admin puede modificar al objetivo
            $stmt_target = $pdo->prepare("SELECT role FROM users WHERE id = ?");
            $stmt_target->execute([$targetUserId]);
            $targetUser = $stmt_target->fetch();
            if (!$targetUser) {
                throw new Exception('js.auth.errorUserNotFound');
            }
            $targetRole = $targetUser['role'];
            if (!canAdminModifyTarget($adminRole, $targetRole)) {
                 $response['message'] = ($targetRole === 'founder') ? 'js.admin.errorFounderTarget' : 'js.admin.errorAdminTarget';
                 echo json_encode($response);
                 exit;
            }

            // --- Inicio de Transacción ---
            $pdo->beginTransaction();

            // Paso A: Actualizar 'users' table
            // --- Forzar fecha de expiración a NULL (permanente) ---
            $finalStatusExpiresAt = ($generalStatus === 'suspended') ? null : null; 
            
            $stmt_status = $pdo->prepare("UPDATE users SET account_status = ?, status_expires_at = ? WHERE id = ?");
            $stmt_status->execute([$generalStatus, $finalStatusExpiresAt, $targetUserId]);

            // Paso B: Borrar todas las restricciones antiguas
            $stmt_delete = $pdo->prepare("DELETE FROM user_restrictions WHERE user_id = ?");
            $stmt_delete->execute([$targetUserId]);

            // Pasos C y D: Insertar nuevas restricciones
            $stmt_insert = $pdo->prepare(
                "INSERT INTO user_restrictions (user_id, restriction_type, expires_at) 
                 VALUES (?, ?, ?)"
            );
            
            // --- Usar la lógica 'true'/'false' y forzar NULL ---
            if ($restrictPublish === 'true') {
                $stmt_insert->execute([$targetUserId, 'CANNOT_PUBLISH', null]);
            }
            if ($restrictComment === 'true') {
                $stmt_insert->execute([$targetUserId, 'CANNOT_COMMENT', null]);
            }
            if ($restrictMessage === 'true') {
                $stmt_insert->execute([$targetUserId, 'CANNOT_MESSAGE', null]);
            }
            if ($restrictSocial === 'true') {
                $stmt_insert->execute([$targetUserId, 'CANNOT_SOCIAL', null]);
            }
            

            // Paso E: Commit
            $pdo->commit();

            // --- Notificar al WebSocket (similar al antiguo 'set-status') ---
            try {
                $curl_endpoint = '';
                $curl_payload = [];
                
                if ($generalStatus === 'deleted' || $generalStatus === 'suspended') {
                    $curl_endpoint = 'http://127.0.0.1:8766/update-status';
                    $curl_payload = json_encode([
                        'user_id' => (int)$targetUserId,
                        'status'  => $generalStatus
                    ]);
                } else {
                    $curl_endpoint = 'http://127.0.0.1:8766/kick';
                    $curl_payload = json_encode([
                        'user_id' => (int)$targetUserId,
                        'exclude_session_id' => 'admin_kick_restrictions_update' 
                    ]);
                }

                $ch = curl_init($curl_endpoint);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $curl_payload);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($curl_payload)
                ]);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 500); 
                curl_setopt($ch, CURLOPT_TIMEOUT_MS, 500);        
                curl_exec($ch); 
                curl_close($ch);
                
            } catch (Exception $e) {
                logDatabaseError($e, 'admin_handler - admin-update-restrictions (ws_notify_fail)');
            }

            $response['success'] = true;
            $response['message'] = 'js.admin.successStatus'; 

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($e instanceof PDOException) {
                logDatabaseError($e, 'admin_handler - admin-update-restrictions');
                $response['message'] = 'js.api.errorDatabase';
            } else {
                $response['message'] = $e->getMessage();
            }
        }
    // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

    } elseif ($action === 'admin-generate-password') {
        try {
            $newPassword = generateSecurePasswordPhp(16); 
            
            $response['success'] = true;
            $response['password'] = $newPassword;

        } catch (Exception $e) {
            logDatabaseError($e, 'admin_handler - admin-generate-password');
            $response['message'] = 'js.api.errorServer'; 
        }
    }
    

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
            
            $maxSizeMB = (int)($GLOBALS['site_settings']['avatar_max_size_mb'] ?? 2);
            if ($file['size'] > $maxSizeMB * 1024 * 1024) {
                $response['data'] = ['size' => $maxSizeMB];
                throw new Exception('js.settings.errorAvatarSize');
            }

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
            if (strlen($newUsername) < $minUsernameLength) throw new Exception('js.auth.errorUsernameMinLength');
            if (strlen($newUsername) > $maxUsernameLength) throw new Exception('js.auth.errorUsernameMaxLength');
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
            
            $response['newUsername'] = $newUsername;
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


        } catch (Exception $e) {
            if ($e instanceof PDOException) logDatabaseError($e, 'admin_handler - admin-update-username');
            $response['message'] = $e->getMessage();
            if ($response['message'] === 'js.auth.errorUsernameMinLength') $response['data'] = ['length' => $minUsernameLength];
            elseif ($response['message'] === 'js.auth.errorUsernameMaxLength') $response['data'] = ['length' => $maxUsernameLength];
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
            if (strlen($newEmail) > $maxEmailLength) throw new Exception('js.auth.errorEmailLength');
            if ($newEmail === $oldEmail) throw new Exception('js.settings.errorEmailIsCurrent');
            
            $domainsString = $GLOBALS['site_settings']['allowed_email_domains'] ?? '';
            $allowedDomains = preg_split('/[\s,]+/', $domainsString, -1, PREG_SPLIT_NO_EMPTY);
            $emailDomain = substr($newEmail, strrpos($newEmail, '@') + 1);
            if (!empty($allowedDomains) && !in_array(strtolower($emailDomain), $allowedDomains)) {
                throw new Exception('js.auth.errorEmailDomain');
            }

            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$newEmail, $targetUserId]);
            if ($stmt->fetch()) throw new Exception('js.auth.errorEmailInUse');

            $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
            $stmt->execute([$newEmail, $targetUserId]);

            $stmt_log_email = $pdo->prepare("INSERT INTO user_audit_logs (user_id, change_type, old_value, new_value, changed_by_ip) VALUES (?, 'email', ?, ?, ?)");
            $stmt_log_email->execute([$targetUserId, $oldEmail, $newEmail, getIpAddress()]);

            $response['success'] = true;
            $response['message'] = 'js.settings.successEmailUpdate';
            $response['newEmail'] = $newEmail; 

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
                throw new Exception('admin.edit.errorPassEmpty'); 
            }
            if (strlen($newPassword) < $minPasswordLength) throw new Exception('js.auth.errorPasswordMinLength');
            if (strlen($newPassword) > $maxPasswordLength) throw new Exception('js.auth.errorPasswordLength');
            if ($newPassword !== $confirmPassword) throw new Exception('js.auth.errorPasswordMismatch');

            $newHashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$newHashedPassword, $targetUserId]);

            $stmt_log_pass = $pdo->prepare("INSERT INTO user_audit_logs (user_id, change_type, old_value, new_value, changed_by_ip) VALUES (?, 'password', ?, ?, ?)");
            $stmt_log_pass->execute([$targetUserId, $oldHashedPassword, $newHashedPassword, getIpAddress()]);

            $response['success'] = true;
            $response['message'] = 'js.settings.successPassUpdate';
            $response['newPasswordHash'] = $newHashedPassword; 

        } catch (Exception $e) {
            if ($e instanceof PDOException) logDatabaseError($e, 'admin_handler - admin-update-password');
            $response['message'] = $e->getMessage();
            if ($response['message'] === 'js.auth.errorPasswordMinLength') $response['data'] = ['length' => $minPasswordLength];
            elseif ($response['message'] === 'js.auth.errorPasswordLength') $response['data'] = ['min' => $minPasswordLength, 'max' => $maxPasswordLength];
        }
    }
    

    elseif ($action === 'update-maintenance-mode') {
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
            
            $pdo->beginTransaction();

            $stmt_maintenance = $pdo->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = 'maintenance_mode'");
            $stmt_maintenance->execute([$newValue]);
            
            $registrationValue = null;
            $kicked_users_count = 0; 

            if ($newValue === '1') {
                $stmt_registration = $pdo->prepare("UPDATE site_settings SET setting_value = '0' WHERE setting_key = 'allow_new_registrations'");
                $stmt_registration->execute();
                $registrationValue = '0';
                
                
                $stmt_users_to_kick = $pdo->prepare(
                    "SELECT id FROM users WHERE role = 'user' AND account_status = 'active'"
                );
                $stmt_users_to_kick->execute();
                $userIdsToKick = $stmt_users_to_kick->fetchAll(PDO::FETCH_COLUMN);
                
                if (!empty($userIdsToKick)) {
                    $kicked_users_count = count($userIdsToKick);
                    
                    $stmt_token_invalidate = $pdo->prepare("UPDATE users SET auth_token = ? WHERE id = ?");
                    foreach ($userIdsToKick as $userId) {
                        $newAuthToken = bin2hex(random_bytes(32)); 
                        $stmt_token_invalidate->execute([$newAuthToken, $userId]);
                    }

                    try {
                        $kickPayload = json_encode([
                            'user_ids' => $userIdsToKick 
                        ]);

                        $ch = curl_init('http://127.0.0.1:8766/kick-bulk'); 
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $kickPayload);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                            'Content-Type: application/json',
                            'Content-Length: ' . strlen($kickPayload)
                        ]);
                        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 500); 
                        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 500);        

                        curl_exec($ch); 
                        curl_close($ch);
                        
                    } catch (Exception $e) {
                        logDatabaseError($e, 'admin_handler - maintenance (kick_bulk_ws_fail)');
                    }
                }
            }
            
            $pdo->commit();

            $response['success'] = true;
            $response['message'] = 'js.admin.maintenanceSuccess'; 
            $response['newValue'] = $newValue;
            if ($registrationValue !== null) {
                $response['registrationValue'] = $registrationValue; 
            }
            if ($kicked_users_count > 0) {
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
    
    elseif ($action === 'update-registration-mode') {
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

            $stmt_check_maintenance = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'maintenance_mode'");
            $stmt_check_maintenance->execute();
            $maintenanceMode = $stmt_check_maintenance->fetchColumn();

            if ($maintenanceMode === '1' && $newValue === '1') {
                throw new Exception('js.admin.errorRegInMaintenance'); 
            }
            
            $stmt = $pdo->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = 'allow_new_registrations'");
            $stmt->execute([$newValue]);

            $response['success'] = true;
            $response['message'] = 'js.admin.registrationSuccess'; 
            $response['newValue'] = $newValue;

        } catch (Exception $e) {
            if ($e instanceof PDOException) {
                logDatabaseError($e, 'admin_handler - update-registration-mode');
                $response['message'] = 'js.api.errorDatabase';
            } else {
                $response['message'] = $e->getMessage();
            }
        }
    
    } elseif ($action === 'update-messaging-service') {
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
            
            $stmt = $pdo->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = 'messaging_service_enabled'");
            $stmt->execute([$newValue]);

            try {
                $statusPayload = json_encode([
                    'status' => ($newValue === '1') ? 'enabled' : 'disabled'
                ]);
                $ch = curl_init('http://127.0.0.1:8766/broadcast-messaging-status'); 
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $statusPayload);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($statusPayload)
                ]);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 500);
                curl_setopt($ch, CURLOPT_TIMEOUT_MS, 500);
                curl_exec($ch);
                curl_close($ch);
            } catch (Exception $e) {
                logDatabaseError($e, 'admin_handler - (ws_broadcast_messaging_fail)');
            }

            $response['success'] = true;
            $response['message'] = 'js.admin.messagingSuccess'; 
            $response['newValue'] = $newValue;

        } catch (Exception $e) {
            if ($e instanceof PDOException) {
                logDatabaseError($e, 'admin_handler - update-messaging-service');
                $response['message'] = 'js.api.errorDatabase';
            } else {
                $response['message'] = $e->getMessage();
            }
        }

    } elseif ($action === 'admin-add-domain') {
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

    elseif (
        $action === 'update-username-cooldown' || $action === 'update-email-cooldown' || $action === 'update-avatar-max-size' ||
        $action === 'update-min-password-length' || $action === 'update-max-login-attempts' || $action === 'update-lockout-time-minutes' ||
        $action === 'update-max-password-length' ||
        $action === 'update-min-username-length' || $action === 'update-max-username-length' ||
        $action === 'update-max-email-length' || $action === 'update-code-resend-cooldown' ||
        $action === 'update-max-concurrent-users' 
    ) {
        
        if ($adminRole !== 'founder') {
            $response['message'] = 'js.admin.errorAdminTarget';
            echo json_encode($response);
            exit;
        }

        try {
            $newValue = trim($_POST['new_value'] ?? '');
            
            $settingKeyMap = [
                'update-username-cooldown' => 'username_cooldown_days',
                'update-email-cooldown' => 'email_cooldown_days',
                'update-avatar-max-size' => 'avatar_max_size_mb',
                'update-min-password-length' => 'min_password_length',
                'update-max-login-attempts' => 'max_login_attempts',
                'update-lockout-time-minutes' => 'lockout_time_minutes',
                'update-max-password-length' => 'max_password_length',
                'update-min-username-length' => 'min_username_length',
                'update-max-username-length' => 'max_username_length',
                'update-max-email-length' => 'max_email_length',
                'update-code-resend-cooldown' => 'code_resend_cooldown_seconds',
                'update-max-concurrent-users' => 'max_concurrent_users' 
            ];
            
            if (!isset($settingKeyMap[$action])) {
                 throw new Exception('js.api.invalidAction');
            }
            
            $dbKey = $settingKeyMap[$action];

            if (!is_numeric($newValue) || (int)$newValue < 0) {
                throw new Exception('js.api.invalidAction'); 
            }
            $finalValue = (int)$newValue;
            
            if ($dbKey === 'min_password_length' && ($finalValue < 8 || $finalValue > 72)) {
                 throw new Exception('js.api.invalidAction');
            }
            if ($dbKey === 'max_password_length' && ($finalValue < 8 || $finalValue > 72)) {
                 throw new Exception('js.api.invalidAction');
            }

            $stmt = $pdo->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$finalValue, $dbKey]);

            $response['success'] = true;
            $response['message'] = 'js.admin.settingUpdateSuccess'; 
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
    
    
    elseif ($action === 'create-backup' || $action === 'restore-backup' || $action === 'delete-backup') {
        
        if ($adminRole !== 'founder') {
            $response['message'] = 'js.admin.errorAdminTarget';
            echo json_encode($response);
            exit;
        }
        
        $backupDir = dirname(__DIR__) . '/backups'; 
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
                $safeFilename = basename($filename); 
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
                $safeFilename = basename($filename); 
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
    

    elseif ($action === 'admin-create-community') {
        try {
            $name = trim($_POST['name'] ?? '');
            $communityType = ($_POST['community_type'] === 'universidad') ? 'universidad' : 'municipio';
            $privacy = ($_POST['privacy'] === 'private') ? 'private' : 'public';
            
            $accessCode = ($_POST['access_code'] ?? '');
            
            $accessCodeDB = str_replace('-', '', $accessCode);

            $maxMembers = (int)($_POST['max_members'] ?? 0);
            $maxMembersDB = ($maxMembers > 0) ? $maxMembers : null; 

            if (empty($name)) {
                throw new Exception('admin.communities.error.nameRequired');
            }
            if ($privacy === 'private' && empty($accessCodeDB)) {
                $accessCode = generateAccessCode(); 
                $accessCodeDB = str_replace('-', '', $accessCode);
            }
            if ($privacy === 'public') {
                $accessCodeDB = null;
            }

            $uuid = bin2hex(random_bytes(16)); 
            
            $pdo->beginTransaction();

            $stmt_insert = $pdo->prepare(
                "INSERT INTO communities (uuid, name, community_type, privacy, access_code, max_members) 
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt_insert->execute([$uuid, $name, $communityType, $privacy, $accessCodeDB, $maxMembersDB]);
            $newCommunityId = $pdo->lastInsertId();

            $defaultIcon = generateDefaultCommunityIcon($pdo, $newCommunityId, $name, $basePath);
            
            if ($defaultIcon) {
                $stmt_icon = $pdo->prepare("UPDATE communities SET icon_url = ? WHERE id = ?");
                $stmt_icon->execute([$defaultIcon, $newCommunityId]);
            }
            
            $pdo->commit();

            $response['success'] = true;
            $response['message'] = 'admin.communities.success.created';
            $response['new_community_id'] = $newCommunityId;

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            if ($e instanceof PDOException) logDatabaseError($e, 'admin_handler - admin-create-community');
            $response['message'] = $e->getMessage();
        }
    }
    
    elseif ($action === 'admin-update-community-details') {
        try {
            $communityId = (int)($_POST['community_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $communityType = ($_POST['community_type'] === 'universidad') ? 'universidad' : 'municipio';
            
            $maxMembers = (int)($_POST['max_members'] ?? 0);
            $maxMembersDB = ($maxMembers > 0) ? $maxMembers : null; 

            if (empty($communityId) || empty($name)) {
                throw new Exception('admin.communities.error.nameRequired');
            }

            $stmt = $pdo->prepare("UPDATE communities SET name = ?, community_type = ?, max_members = ? WHERE id = ?");
            $stmt->execute([$name, $communityType, $maxMembersDB, $communityId]);

            $response['success'] = true;
            $response['message'] = 'admin.communities.success.updated';
            $response['newName'] = $name;

        } catch (Exception $e) {
            if ($e instanceof PDOException) logDatabaseError($e, 'admin_handler - admin-update-community-details');
            $response['message'] = $e->getMessage();
        }
    }
    
    elseif ($action === 'admin-update-community-privacy') {
        try {
            $communityId = (int)($_POST['community_id'] ?? 0);
            $privacy = ($_POST['privacy'] === 'private') ? 'private' : 'public';
            
            $accessCode = trim($_POST['access_code'] ?? ''); 
            $accessCodeDB = str_replace('-', '', $accessCode); 

            if (empty($communityId)) {
                throw new Exception('js.api.invalidAction');
            }
            
            if ($privacy === 'private' && empty($accessCodeDB)) {
                $accessCode = generateAccessCode(); 
                $accessCodeDB = str_replace('-', '', $accessCode);
            } elseif ($privacy === 'public') {
                $accessCodeDB = null;
                $accessCode = ''; 
            }

            $stmt = $pdo->prepare("UPDATE communities SET privacy = ?, access_code = ? WHERE id = ?");
            $stmt->execute([$privacy, $accessCodeDB, $communityId]);
            
            $response['success'] = true;
            $response['message'] = 'admin.communities.success.privacyUpdated';
            $response['newPrivacy'] = $privacy;
            $response['newAccessCode'] = $accessCode; 

        } catch (Exception $e) {
            if ($e instanceof PDOException) logDatabaseError($e, 'admin_handler - admin-update-community-privacy');
            $response['message'] = $e->getMessage();
        }
    }
    
    elseif ($action === 'admin-generate-access-code') {
         if ($adminRole !== 'founder') { 
            $response['message'] = 'js.admin.errorAdminTarget';
            echo json_encode($response);
            exit;
         }
         $response['success'] = true;
         $response['newAccessCode'] = generateAccessCode(); 
    }
    
    elseif ($action === 'admin-upload-community-image') {
        try {
            $communityId = (int)($_POST['community_id'] ?? 0);
            $imageType = $_POST['image_type'] ?? ''; 

            if (empty($communityId) || ($imageType !== 'icon' && $imageType !== 'banner')) {
                throw new Exception('js.api.invalidAction');
            }
            if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('js.settings.errorAvatarUpload');
            }

            $file = $_FILES['image'];
            $maxSizeMB = (int)($GLOBALS['site_settings']['avatar_max_size_mb'] ?? 2);
            if ($file['size'] > $maxSizeMB * 1024 * 1024) {
                $response['data'] = ['size' => $maxSizeMB];
                throw new Exception('js.settings.errorAvatarSize');
            }

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file['tmp_name']);
            $allowedTypes = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/gif' => 'gif', 'image/webp' => 'webp'];
            if (!array_key_exists($mimeType, $allowedTypes)) throw new Exception('js.settings.errorAvatarFormat');
            
            $extension = $allowedTypes[$mimeType];
            
            $stmt = $pdo->prepare("SELECT icon_url, banner_url FROM communities WHERE id = ?");
            $stmt->execute([$communityId]);
            $urls = $stmt->fetch();
            if (!$urls) throw new Exception('js.api.errorServer');

            $oldUrl = ($imageType === 'icon') ? $urls['icon_url'] : $urls['banner_url'];
            
            $newFileName = "community-{$imageType}-{$communityId}-" . time() . "." . $extension;
            $saveDir = dirname(__DIR__) . '/assets/uploads/communities_uploaded/';
            if (!is_dir($saveDir)) @mkdir($saveDir, 0755, true);
            
            $newFilePath = $saveDir . $newFileName;
            $newPublicUrl = $basePath . '/assets/uploads/communities_uploaded/' . $newFileName;

            if (!move_uploaded_file($file['tmp_name'], $newFilePath)) {
                throw new Exception('js.settings.errorAvatarSave');
            }
            
            $columnToUpdate = ($imageType === 'icon') ? 'icon_url' : 'banner_url';
            $stmt = $pdo->prepare("UPDATE communities SET $columnToUpdate = ? WHERE id = ?");
            $stmt->execute([$newPublicUrl, $communityId]);
            
            if ($oldUrl) {
                deleteOldCommunityImage($oldUrl, $basePath);
            }
            
            $response['success'] = true;
            $response['message'] = 'admin.communities.success.imageUploaded';
            $response['newImageUrl'] = $newPublicUrl;

        } catch (Exception $e) {
            if ($e instanceof PDOException) logDatabaseError($e, 'admin_handler - admin-upload-community-image');
            $response['message'] = $e->getMessage();
        }
    }
    
    elseif ($action === 'admin-remove-community-image') {
        try {
            $communityId = (int)($_POST['community_id'] ?? 0);
            $imageType = $_POST['image_type'] ?? ''; 

            if (empty($communityId) || ($imageType !== 'icon' && $imageType !== 'banner')) {
                throw new Exception('js.api.invalidAction');
            }

            $stmt = $pdo->prepare("SELECT name, icon_url, banner_url FROM communities WHERE id = ?");
            $stmt->execute([$communityId]);
            $community = $stmt->fetch();
            if (!$community) throw new Exception('js.api.errorServer');
            
            $newUrl = null;
            $columnToUpdate = null;
            $oldUrl = null;

            if ($imageType === 'icon') {
                $columnToUpdate = 'icon_url';
                $oldUrl = $community['icon_url'];
                $newUrl = generateDefaultCommunityIcon($pdo, $communityId, $community['name'], $basePath);
                if (!$newUrl) throw new Exception('js.settings.errorAvatarApi');
            } else { 
                $columnToUpdate = 'banner_url';
                $oldUrl = $community['banner_url'];
                $newUrl = null; 
            }
            
            $stmt = $pdo->prepare("UPDATE communities SET $columnToUpdate = ? WHERE id = ?");
            $stmt->execute([$newUrl, $communityId]);
            
            if ($oldUrl) {
                deleteOldCommunityImage($oldUrl, $basePath);
            }
            
            $response['success'] = true;
            $response['message'] = 'admin.communities.success.imageRemoved';
            $response['newImageUrl'] = $newUrl; 

        } catch (Exception $e) {
            if ($e instanceof PDOException) logDatabaseError($e, 'admin_handler - admin-remove-community-image');
            $response['message'] = $e->getMessage();
        }
    }

    
    
} else {
    $response['message'] = 'js.api.invalidAction';
}

echo json_encode($response);
exit;
?>
<?php

include '../config/config.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'js.api.invalidAction'];

$codeResendCooldownSeconds = (int)($GLOBALS['site_settings']['code_resend_cooldown_seconds'] ?? 60);
$minPasswordLength = (int)($GLOBALS['site_settings']['min_password_length'] ?? 8);
$maxPasswordLength = (int)($GLOBALS['site_settings']['max_password_length'] ?? 72);
$minUsernameLength = (int)($GLOBALS['site_settings']['min_username_length'] ?? 6);
$maxUsernameLength = (int)($GLOBALS['site_settings']['max_username_length'] ?? 32);
$maxEmailLength = (int)($GLOBALS['site_settings']['max_email_length'] ?? 255);



function generateVerificationCode() {
    $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = '';
    $max = strlen($chars) - 1;
    for ($i = 0; $i < 12; $i++) {
        $code .= $chars[random_int(0, $max)];
    }
    return substr($code, 0, 4) . '-' . substr($code, 4, 4) . '-' . substr($code, 8, 4);
}


function logUserMetadata($pdo, $userId) {
    try {
        $ip = getIpAddress(); 
        $browserInfo = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        $deviceType = 'Desktop';
        if (preg_match('/(mobile|android|iphone|ipad)/i', strtolower($browserInfo))) {
            $deviceType = 'Mobile';
        }

        $stmt = $pdo->prepare(
            "INSERT INTO user_metadata (user_id, ip_address, device_type, browser_info, is_active) 
             VALUES (?, ?, ?, ?, 1)"
        );
        $stmt->execute([$userId, $ip, $deviceType, $browserInfo]);
        
        return $pdo->lastInsertId(); 

    } catch (PDOException $e) {
        logDatabaseError($e, 'auth_handler - logUserMetadata');
        return null; 
    }
}


function createUserAndLogin($pdo, $basePath, $email, $username, $passwordHash, $userIdFromVerification) {
    
    $authToken = bin2hex(random_bytes(32)); 
    $stmt = $pdo->prepare("INSERT INTO users (email, username, password, is_2fa_enabled, auth_token) VALUES (?, ?, ?, 0, ?)");
    $stmt->execute([$email, $username, $passwordHash, $authToken]);
    $userId = $pdo->lastInsertId();

    $localAvatarUrl = null;
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
        
        $imageData = file_get_contents($apiUrl);

        if ($imageData !== false) {
            file_put_contents($fullSavePath, $imageData);
            $localAvatarUrl = $publicUrl;
        }

    } catch (Exception $e) {
    }

    if ($localAvatarUrl) {
        $stmt = $pdo->prepare("UPDATE users SET profile_image_url = ? WHERE id = ?");
        $stmt->execute([$localAvatarUrl, $userId]);
    }

    try {
        $preferredLanguage = getPreferredLanguage($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'en');

        $stmt_prefs = $pdo->prepare(
            "INSERT INTO user_preferences (user_id, language, theme, usage_type) 
             VALUES (?, ?, 'system', 'personal')"
        );
        $stmt_prefs->execute([$userId, $preferredLanguage]);

    } catch (PDOException $e) {
        logDatabaseError($e, 'auth_handler - createUser - preferences');
    }


    $session_lifetime = 60 * 60 * 24 * 90; 
    session_set_cookie_params($session_lifetime);
    
    session_regenerate_id(true);

    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;
    $_SESSION['profile_image_url'] = $localAvatarUrl;
    $_SESSION['role'] = 'user'; 
    $_SESSION['auth_token'] = $authToken; 
    
    $metadata_id = logUserMetadata($pdo, $userId); 
    $_SESSION['metadata_id'] = $metadata_id; 
    

    $stmt = $pdo->prepare("DELETE FROM verification_codes WHERE id = ?");
    $stmt->execute([$userIdFromVerification]);

    generateCsrfToken();

    unset($_SESSION['registration_step']);
    unset($_SESSION['registration_email']); 

    // --- ▼▼▼ LÍNEA AÑADIDA ▼▼▼ ---
    $_SESSION['show_welcome_modal'] = true; 
    // --- ▲▲▲ FIN DE LÍNEA AÑADIDA ▲▲▲ ---

    return true;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($submittedToken)) {
        $response['success'] = false;
        $response['message'] = 'js.api.errorSecurityRefresh';
        echo json_encode($response);
        exit;
    }


    if (isset($_POST['action'])) {
    
        $action = $_POST['action'];

        if ($action === 'register-check-email') {
            
            if (!isset($GLOBALS['site_settings']['allow_new_registrations']) || $GLOBALS['site_settings']['allow_new_registrations'] !== '1') {
                $response['message'] = 'js.auth.errorRegistrationsDisabled'; 
                echo json_encode($response);
                exit;
            }

            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            
            $domainsString = $GLOBALS['site_settings']['allowed_email_domains'] ?? '';
            $allowedDomains = preg_split('/[\s,]+/', $domainsString, -1, PREG_SPLIT_NO_EMPTY);
            $emailDomain = substr($email, strrpos($email, '@') + 1);

            if (empty($email) || empty($password)) {
                $response['message'] = 'js.auth.errorCompleteEmailPass';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $response['message'] = 'js.auth.errorInvalidEmail';
            } elseif (strlen($email) > $maxEmailLength) {
                $response['message'] = 'js.auth.errorEmailLength';
            } elseif (!empty($allowedDomains) && !in_array(strtolower($emailDomain), $allowedDomains)) {
                $response['message'] = 'js.auth.errorEmailDomain';
            } elseif (strlen($password) < $minPasswordLength) {
                $response['message'] = 'js.auth.errorPasswordMinLength';
                $response['data'] = ['length' => $minPasswordLength];
            } elseif (strlen($password) > $maxPasswordLength) {
                $response['message'] = 'js.auth.errorPasswordLength';
                $response['data'] = ['min' => $minPasswordLength, 'max' => $maxPasswordLength];
            } else {
                try {
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    if ($stmt->fetch()) {
                        $response['message'] = 'js.auth.errorEmailInUse';
                    } else {
                        $_SESSION['registration_step'] = 2;
                        $_SESSION['registration_email'] = $email; 
                        $response['success'] = true;
                    }
                } catch (PDOException $e) {
                    logDatabaseError($e, 'auth_handler - register-check-email');
                    $response['message'] = 'js.api.errorDatabase';
                }
            }
        }

        elseif ($action === 'register-check-username-and-generate-code') {
            
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $username = $_POST['username'] ?? '';

            if (empty($email) || empty($password) || empty($username)) {
                $response['message'] = 'js.auth.errorMissingSteps';
            } elseif (strlen($password) < $minPasswordLength || strlen($password) > $maxPasswordLength) {
                 $response['message'] = 'js.auth.errorPasswordLength';
                 $response['data'] = ['min' => $minPasswordLength, 'max' => $maxPasswordLength];
            } elseif (strlen($username) < $minUsernameLength) {
                $response['message'] = 'js.auth.errorUsernameMinLength';
                $response['data'] = ['length' => $minUsernameLength];
            } elseif (strlen($username) > $maxUsernameLength) {
                $response['message'] = 'js.auth.errorUsernameMaxLength';
                $response['data'] = ['length' => $maxUsernameLength];
            } else {
                try {
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                    $stmt->execute([$username]);
                    if ($stmt->fetch()) {
                        $response['message'] = 'js.auth.errorUsernameInUse';
                    } else {
                        $stmt = $pdo->prepare("DELETE FROM verification_codes WHERE identifier = ? AND code_type = 'registration'");
                        $stmt->execute([$email]);

                        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                        $verificationCode = str_replace('-', '', generateVerificationCode());
                        
                        $payload = json_encode([
                            'username' => $username,
                            'password_hash' => $hashedPassword
                        ]);

                        $stmt = $pdo->prepare(
                            "INSERT INTO verification_codes (identifier, code_type, code, payload) 
                             VALUES (?, 'registration', ?, ?)"
                        );
                        $stmt->execute([$email, $verificationCode, $payload]);

                        $_SESSION['registration_step'] = 3;

                        $response['success'] = true;
                        $response['message'] = 'js.auth.successCodeGenerated';
                    }
                } catch (PDOException $e) {
                    logDatabaseError($e, 'auth_handler - register-check-username');
                    $response['message'] = 'js.api.errorDatabase';
                }
            }
        }

        elseif ($action === 'register-verify') {
            
            if (!isset($GLOBALS['site_settings']['allow_new_registrations']) || $GLOBALS['site_settings']['allow_new_registrations'] !== '1') {
                $response['message'] = 'js.auth.errorRegistrationsDisabled';
                echo json_encode($response);
                exit;
            }

            if (empty($_POST['email']) || empty($_POST['verification_code'])) {
                $response['message'] = 'js.auth.errorMissingEmailOrCode';
            } else {
                $email = $_POST['email'];
                $submittedCode = str_replace('-', '', $_POST['verification_code']); 

                try {
                    $stmt = $pdo->prepare(
                        "SELECT * FROM verification_codes 
                         WHERE identifier = ? 
                         AND code_type = 'registration'
                         AND created_at > (NOW() - INTERVAL 15 MINUTE)"
                    );
                    $stmt->execute([$email]);
                    $pendingUser = $stmt->fetch();

                    if (!$pendingUser) {
                        $response['message'] = 'js.auth.errorCodeExpiredRestart';
                        unset($_SESSION['registration_step']);
                        unset($_SESSION['registration_email']); 
                    
                    } else {
                        if (strtolower($pendingUser['code']) !== strtolower($submittedCode)) {
                            $response['message'] = 'js.auth.errorCodeIncorrect';
                        } else {
                            $payloadData = json_decode($pendingUser['payload'], true);
                            
                            if (!$payloadData || empty($payloadData['username']) || empty($payloadData['password_hash'])) {
                                $response['message'] = 'js.auth.errorCorruptData';
                            } else {
                                createUserAndLogin(
                                    $pdo, 
                                    $basePath, 
                                    $pendingUser['identifier'], 
                                    $payloadData['username'], 
                                    $payloadData['password_hash'], 
                                    $pendingUser['id'] 
                                );
                                
                                $response['success'] = true;
                                $response['message'] = 'js.auth.successRegistration';
                            }
                        }
                    }
                } catch (PDOException $e) {
                    logDatabaseError($e, 'auth_handler - register-verify');
                    
                    if ($e->errorInfo[1] == 1062 || str_contains($e->getMessage(), 'Duplicate entry')) {
                        $response['message'] = 'js.auth.errorSync'; 
                    } else {
                        $response['message'] = 'js.api.errorDatabase';
                    }
                }
            }
        }
        
        elseif ($action === 'register-resend-code') {
            $email = $_POST['email'] ?? '';

            if (empty($email)) {
                $response['message'] = 'js.auth.errorNoEmail';
            } else {
                try {
                    $stmt = $pdo->prepare(
                        "SELECT * FROM verification_codes 
                         WHERE identifier = ? AND code_type = 'registration' 
                         ORDER BY created_at DESC LIMIT 1"
                    );
                    $stmt->execute([$email]);
                    $codeData = $stmt->fetch();

                    if (!$codeData) {
                        throw new Exception('js.auth.errorNoRegistrationData');
                    }

                    $lastCodeTime = new DateTime($codeData['created_at'], new DateTimeZone('UTC'));
                    $currentTime = new DateTime('now', new DateTimeZone('UTC'));
                    $secondsPassed = $currentTime->getTimestamp() - $lastCodeTime->getTimestamp();

                    if ($secondsPassed < $codeResendCooldownSeconds) {
                        $secondsRemaining = $codeResendCooldownSeconds - $secondsPassed;
                        throw new Exception('js.auth.errorCodeCooldown');
                    }

                    $newCode = str_replace('-', '', generateVerificationCode());
                    
                    $stmt = $pdo->prepare(
                        "UPDATE verification_codes SET code = ?, created_at = NOW() WHERE id = ?"
                    );
                    $stmt->execute([$newCode, $codeData['id']]);


                    $response['success'] = true;
                    $response['message'] = 'js.auth.successCodeResent';

                } catch (Exception $e) {
                    if ($e instanceof PDOException) {
                        logDatabaseError($e, 'auth_handler - register-resend-code');
                        $response['message'] = 'js.api.errorDatabase';
                    } else {
                        $response['message'] = $e->getMessage();
                        if ($response['message'] === 'js.auth.errorCodeCooldown') {
                            $response['data'] = ['seconds' => $secondsRemaining ?? $codeResendCooldownSeconds];
                        }
                    }
                }
            }
        }
        
        elseif ($action === 'login-resend-2fa-code') {
            $email = $_POST['email'] ?? '';
            $ip = getIpAddress();

            if (empty($email)) {
                $response['message'] = 'js.auth.errorNoEmail';
            } elseif (checkLockStatus($pdo, $email, $ip)) { 
                $response['message'] = 'js.auth.errorTooManyAttempts';
                $response['data'] = ['minutes' => (int)($GLOBALS['site_settings']['lockout_time_minutes'] ?? 5)];
            } else {
                try {
                    $stmt_check_user = $pdo->prepare("SELECT id, is_2fa_enabled FROM users WHERE email = ?");
                    $stmt_check_user->execute([$email]);
                    $user = $stmt_check_user->fetch();

                    if (!$user) {
                        logFailedAttempt($pdo, $email, $ip, 'login_fail'); 
                        throw new Exception('js.auth.errorUserNotFound'); 
                    }
                    
                    if ($user['is_2fa_enabled'] != 1) {
                         throw new Exception('js.auth.errorUnknown');
                    }
                    
                    $stmt_check_code = $pdo->prepare(
                        "SELECT * FROM verification_codes 
                         WHERE identifier = ? AND code_type = '2fa' 
                         ORDER BY created_at DESC LIMIT 1"
                    );
                    $stmt_check_code->execute([$email]);
                    $codeData = $stmt_check_code->fetch();

                    if ($codeData) {
                        $lastCodeTime = new DateTime($codeData['created_at'], new DateTimeZone('UTC'));
                        $currentTime = new DateTime('now', new DateTimeZone('UTC'));
                        $secondsPassed = $currentTime->getTimestamp() - $lastCodeTime->getTimestamp();

                        if ($secondsPassed < $codeResendCooldownSeconds) {
                            $secondsRemaining = $codeResendCooldownSeconds - $secondsPassed;
                            throw new Exception('js.auth.errorCodeCooldown');
                        }
                    }

                    $stmt_delete = $pdo->prepare("DELETE FROM verification_codes WHERE identifier = ? AND code_type = '2fa'");
                    $stmt_delete->execute([$email]);

                    $newCode = str_replace('-', '', generateVerificationCode());
                    
                    $stmt_insert = $pdo->prepare(
                        "INSERT INTO verification_codes (identifier, code_type, code) 
                         VALUES (?, '2fa', ?)"
                    );
                    $stmt_insert->execute([$email, $newCode]);

                    $response['success'] = true;
                    $response['message'] = 'js.auth.successCodeResent';

                } catch (Exception $e) {
                    if ($e instanceof PDOException) {
                        logDatabaseError($e, 'auth_handler - login-resend-2fa-code');
                        $response['message'] = 'js.api.errorDatabase';
                    } else {
                        $response['message'] = $e->getMessage();
                        if ($response['message'] === 'js.auth.errorCodeCooldown') {
                            $response['data'] = ['seconds' => $secondsRemaining ?? $codeResendCooldownSeconds];
                        }
                    }
                }
            }
        }

        elseif ($action === 'login-check-credentials') {
            if (!empty($_POST['email']) && !empty($_POST['password'])) {
                
                $email = $_POST['email'];
                $password = $_POST['password'];
                $ip = getIpAddress(); 

                if (checkLockStatus($pdo, $email, $ip)) {
                    $response['message'] = 'js.auth.errorTooManyAttempts';
                    $response['data'] = ['minutes' => (int)($GLOBALS['site_settings']['lockout_time_minutes'] ?? 5)];
                    echo json_encode($response);
                    exit;
                }

                try {
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    $user = $stmt->fetch();

                    if ($user && password_verify($password, $user['password'])) {
                        
                        if ($user['account_status'] === 'deleted') {
                            $response['message'] = 'js.auth.errorAccountDeleted'; 
                            $response['redirect_to_status'] = 'deleted'; 
                            echo json_encode($response);
                            exit;
                        }
                        
                        if ($user['account_status'] === 'suspended') {
                            $response['message'] = 'js.auth.errorAccountSuspended';
                            $response['redirect_to_status'] = 'suspended'; 
                            echo json_encode($response);
                            exit;
                        }

                            
                        $userRole = $user['role'];
                        $isPrivilegedUser = in_array($userRole, ['moderator', 'administrator', 'founder']);
                        $maintenanceMode = $GLOBALS['site_settings']['maintenance_mode'] ?? '0';

                        if ($maintenanceMode === '1' && !$isPrivilegedUser) {
                            $response['success'] = false;
                            $response['redirect_to_status'] = 'maintenance';
                            $response['message'] = 'page.maintenance.title'; 
                            echo json_encode($response);
                            exit;
                        }


                        $maxUsers = (int)($GLOBALS['site_settings']['max_concurrent_users'] ?? 500); 

                        $currentUserCount = 0;
                        try {
                            $context = stream_context_create(['http' => ['timeout' => 2.0]]);
                            $jsonResponse = file_get_contents('http://127.0.0.1:8766/count', false, $context); 
                            
                            if ($jsonResponse === false) {
                                logDatabaseError(new Exception("No se pudo contactar al servidor de conteo en http://127.0.0.1:8766/count"), 'auth_handler - concurrent_users_check');
                                throw new Exception('js.api.errorServer'); 
                            }

                            $data = json_decode($jsonResponse, true);
                            if (isset($data['active_users'])) {
                                $currentUserCount = (int)$data['active_users'];
                            }
                        } catch (Exception $e) {
                            logDatabaseError($e, 'auth_handler - concurrent_users_check');
                            $response['message'] = 'js.api.errorServer'; 
                            echo json_encode($response);
                            exit;
                        }

                        if ($currentUserCount >= $maxUsers && !$isPrivilegedUser) {
                            $response['success'] = false;
                            $response['redirect_to_status'] = 'server_full';
                            $response['message'] = 'js.auth.errorServerFull';
                            echo json_encode($response);
                            exit;
                        }
                        

                        clearFailedAttempts($pdo, $email);

                        if ($user['is_2fa_enabled'] == 1) {
                            
                            $stmt = $pdo->prepare("DELETE FROM verification_codes WHERE identifier = ? AND code_type = '2fa'");
                            $stmt->execute([$email]);

                            $verificationCode = str_replace('-', '', generateVerificationCode());
                            $stmt = $pdo->prepare(
                                "INSERT INTO verification_codes (identifier, code_type, code) 
                                 VALUES (?, '2fa', ?)"
                            );
                            $stmt->execute([$email, $verificationCode]);


                            $response['success'] = true;
                            $response['message'] = 'js.auth.info2faRequired';
                            $response['is_2fa_required'] = true; 
                            $response['cooldown'] = $codeResendCooldownSeconds; 

                        } else {
                            $session_lifetime = 60 * 60 * 24 * 90; 
                            session_set_cookie_params($session_lifetime);
                            
                            session_regenerate_id(true);

                            $authToken = $user['auth_token'];
                            if (empty($authToken)) {
                                $authToken = bin2hex(random_bytes(32));
                                $stmt_token = $pdo->prepare("UPDATE users SET auth_token = ? WHERE id = ?");
                                $stmt_token->execute([$authToken, $user['id']]);
                            }

                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['username'] = $user['username'];
                            $_SESSION['email'] = $user['email'];
                            $_SESSION['profile_image_url'] = $user['profile_image_url'];
                            $_SESSION['role'] = $user['role']; 
                            $_SESSION['auth_token'] = $authToken; 
                            
                            $metadata_id = logUserMetadata($pdo, $user['id']);
                            $_SESSION['metadata_id'] = $metadata_id; 

                            generateCsrfToken();

                            $response['success'] = true;
                            $response['message'] = 'js.auth.successLogin';
                            $response['is_2fa_required'] = false; 
                        }
                    
                    } else {
                        logFailedAttempt($pdo, $email, $ip, 'login_fail');
                        $response['message'] = 'js.auth.errorInvalidCredentials';
                    }
                } catch (PDOException $e) {
                    logDatabaseError($e, 'auth_handler - login-check-credentials');
                    $response['message'] = 'js.api.errorDatabase';
                }
            } else {
                $response['message'] = 'js.auth.errorCompleteAllFields';
            }
        }


        elseif ($action === 'login-verify-2fa') {
            $email = $_POST['email'] ?? '';
            $submittedCode = str_replace('-', '', $_POST['verification_code'] ?? '');
            $ip = getIpAddress();

            if (empty($email) || empty($submittedCode)) {
                $response['message'] = 'js.auth.errorMissingVerificationData';
            } else {
                
                if (checkLockStatus($pdo, $email, $ip)) {
                    $response['message'] = 'js.auth.errorTooManyAttempts';
                    $response['data'] = ['minutes' => (int)($GLOBALS['site_settings']['lockout_time_minutes'] ?? 5)];
                    echo json_encode($response);
                    exit;
                }
                
                try {
                    $stmt = $pdo->prepare(
                        "SELECT * FROM verification_codes 
                         WHERE identifier = ? 
                         AND code_type = '2fa'
                         AND created_at > (NOW() - INTERVAL 15 MINUTE)" 
                    );
                    $stmt->execute([$email]);
                    $codeData = $stmt->fetch();

                    if (!$codeData || strtolower($codeData['code']) !== strtolower($submittedCode)) {
                        logFailedAttempt($pdo, $email, $ip, 'login_fail');
                        $response['message'] = 'js.auth.errorCodeExpired';
                    } else {
                        
                        clearFailedAttempts($pdo, $email);

                        $stmt_user = $pdo->prepare("SELECT * FROM users WHERE email = ?");
                        $stmt_user->execute([$email]);
                        $user = $stmt_user->fetch();

                        if ($user) {
                            $session_lifetime = 60 * 60 * 24 * 90; 
                            session_set_cookie_params($session_lifetime);
                            
                            session_regenerate_id(true);

                            $authToken = $user['auth_token'];
                            if (empty($authToken)) {
                                $authToken = bin2hex(random_bytes(32));
                                $stmt_token = $pdo->prepare("UPDATE users SET auth_token = ? WHERE id = ?");
                                $stmt_token->execute([$authToken, $user['id']]);
                            }

                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['username'] = $user['username'];
                            $_SESSION['email'] = $user['email'];
                            $_SESSION['profile_image_url'] = $user['profile_image_url'];
                            $_SESSION['role'] = $user['role']; 
                            $_SESSION['auth_token'] = $authToken; 
                            
                            $metadata_id = logUserMetadata($pdo, $user['id']); 
                            $_SESSION['metadata_id'] = $metadata_id; 
                            
                            generateCsrfToken();

                            $stmt_delete = $pdo->prepare("DELETE FROM verification_codes WHERE id = ?");
                            $stmt_delete->execute([$codeData['id']]);

                            $response['success'] = true;
                            $response['message'] = 'js.auth.successLogin';
                        } else {
                            $response['message'] = 'js.auth.errorUserNotFound';
                        }
                    }
                } catch (PDOException $e) {
                    logDatabaseError($e, 'auth_handler - login-verify-2fa');
                    $response['message'] = 'js.api.errorDatabase';
                }
            }
        }



        elseif ($action === 'reset-check-email') {
            $email = $_POST['email'] ?? '';

            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $response['message'] = 'js.auth.errorInvalidEmail';
            } else {
                try {
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    
                    if (!$stmt->fetch()) {
                        $response['success'] = false;
                        $response['message'] = 'js.auth.errorUserNotFound';

                    } else {
                        $stmt = $pdo->prepare("DELETE FROM verification_codes WHERE identifier = ? AND code_type = 'password_reset'");
                        $stmt->execute([$email]);

                        $verificationCode = str_replace('-', '', generateVerificationCode());

                        $stmt = $pdo->prepare(
                            "INSERT INTO verification_codes (identifier, code_type, code) 
                             VALUES (?, 'password_reset', ?)"
                        );
                        $stmt->execute([$email, $verificationCode]);

                        $response['success'] = true;
                        $response['message'] = 'js.auth.successCodeGenerated';
                        
                        $_SESSION['reset_step'] = 2;
                        $_SESSION['reset_email'] = $email;
                    }
                } catch (PDOException $e) {
                    logDatabaseError($e, 'auth_handler - reset-check-email');
                    $response['message'] = 'js.api.errorDatabase';
                }
            }
        }

        elseif ($action === 'reset-resend-code') {
            $email = $_POST['email'] ?? '';

            if (empty($email)) {
                $response['message'] = 'js.auth.errorNoEmail';
            } else {
                try {
                    $stmt = $pdo->prepare(
                        "SELECT * FROM verification_codes 
                         WHERE identifier = ? AND code_type = 'password_reset' 
                         ORDER BY created_at DESC LIMIT 1"
                    );
                    $stmt->execute([$email]);
                    $codeData = $stmt->fetch();

                    if (!$codeData) {
                        throw new Exception('js.auth.errorNoResetData');
                    }

                    $lastCodeTime = new DateTime($codeData['created_at'], new DateTimeZone('UTC'));
                    $currentTime = new DateTime('now', new DateTimeZone('UTC'));
                    $secondsPassed = $currentTime->getTimestamp() - $lastCodeTime->getTimestamp();

                    if ($secondsPassed < $codeResendCooldownSeconds) {
                        $secondsRemaining = $codeResendCooldownSeconds - $secondsPassed;
                        throw new Exception('js.auth.errorCodeCooldown');
                    }

                    $newCode = str_replace('-', '', generateVerificationCode());
                    
                    $stmt = $pdo->prepare(
                        "UPDATE verification_codes SET code = ?, created_at = NOW() WHERE id = ?"
                    );
                    $stmt->execute([$newCode, $codeData['id']]);

                    $response['success'] = true;
                    $response['message'] = 'js.auth.successCodeResent';

                } catch (Exception $e) {
                    if ($e instanceof PDOException) {
                        logDatabaseError($e, 'auth_handler - reset-resend-code');
                        $response['message'] = 'js.api.errorDatabase';
                    } else {
                        $response['message'] = $e->getMessage();
                        if ($response['message'] === 'js.auth.errorCodeCooldown') {
                            $response['data'] = ['seconds' => $secondsRemaining ?? $codeResendCooldownSeconds];
                        }
                    }
                }
            }
        }

        elseif ($action === 'reset-check-code') {
            $email = $_POST['email'] ?? '';
            $submittedCode = str_replace('-', '', $_POST['verification_code'] ?? '');
            $ip = getIpAddress(); 

            if (empty($email) || empty($submittedCode)) {
                $response['message'] = 'js.auth.errorMissingVerificationData';
            } else {
                
                if (checkLockStatus($pdo, $email, $ip)) {
                    $response['message'] = 'js.auth.errorTooManyAttempts';
                    $response['data'] = ['minutes' => (int)($GLOBALS['site_settings']['lockout_time_minutes'] ?? 5)];
                    echo json_encode($response);
                    exit;
                }
                
                try {
                    $stmt = $pdo->prepare(
                        "SELECT * FROM verification_codes 
                         WHERE identifier = ? 
                         AND code_type = 'password_reset'
                         AND created_at > (NOW() - INTERVAL 15 MINUTE)"
                    );
                    $stmt->execute([$email]);
                    $codeData = $stmt->fetch();

                    if (!$codeData || strtolower($codeData['code']) !== strtolower($submittedCode)) {
                        logFailedAttempt($pdo, $email, $ip, 'reset_fail');
                        $response['message'] = 'js.auth.errorCodeExpired';
                    } else {
                        clearFailedAttempts($pdo, $email);
                        $response['success'] = true;
                        
                        $_SESSION['reset_step'] = 3;
                        $_SESSION['reset_code'] = $submittedCode; 
                    }
                } catch (PDOException $e) {
                    logDatabaseError($e, 'auth_handler - reset-check-code');
                    $response['message'] = 'js.api.errorDatabase';
                }
            }
        }

        elseif ($action === 'reset-update-password') {
            $email = $_SESSION['reset_email'] ?? '';
            $submittedCode = $_SESSION['reset_code'] ?? '';
            
            $newPassword = $_POST['password'] ?? '';
            $ip = getIpAddress(); 

            if (empty($email) || empty($submittedCode) || empty($newPassword)) {
                $response['message'] = 'js.auth.errorMissingDataRestart';
            } elseif (strlen($newPassword) < $minPasswordLength) {
                $response['message'] = 'js.auth.errorPasswordMinLength';
                $response['data'] = ['length' => $minPasswordLength];
            } elseif (strlen($newPassword) > $maxPasswordLength) {
                $response['message'] = 'js.auth.errorPasswordLength';
                $response['data'] = ['min' => $minPasswordLength, 'max' => $maxPasswordLength];
            } else {
                
                if (checkLockStatus($pdo, $email, $ip)) {
                    $response['message'] = 'js.auth.errorTooManyAttempts';
                    $response['data'] = ['minutes' => (int)($GLOBALS['site_settings']['lockout_time_minutes'] ?? 5)];
                    echo json_encode($response);
                    exit;
                }

                try {
                    $stmt = $pdo->prepare(
                        "SELECT * FROM verification_codes 
                         WHERE identifier = ? 
                         AND code_type = 'password_reset'
                         AND code = ?" 
                    );
                    $stmt->execute([$email, $submittedCode]);
                    $codeData = $stmt->fetch();

                    if (!$codeData) {
                        logFailedAttempt($pdo, $email, $ip, 'reset_fail');
                        $response['message'] = 'js.auth.errorInvalidResetSession';
                        
                        unset($_SESSION['reset_step']);
                        unset($_SESSION['reset_email']);
                        unset($_SESSION['reset_code']);
                        
                    } else {
                        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
                        
                        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
                        $stmt->execute([$hashedPassword, $email]);

                        $stmt = $pdo->prepare("DELETE FROM verification_codes WHERE id = ?");
                        $stmt->execute([$codeData['id']]);

                        clearFailedAttempts($pdo, $email);

                        $response['success'] = true;
                        $response['message'] = 'js.auth.successPasswordUpdateRedirect';
                        
                        unset($_SESSION['reset_step']);
                        unset($_SESSION['reset_email']);
                        unset($_SESSION['reset_code']);
                    }
                } catch (PDOException $e) {
                    logDatabaseError($e, 'auth_handler - reset-update-password');
                    $response['message'] = 'js.api.errorDatabase';
                }
            }
        }
        
    }
}

echo json_encode($response);
exit;
?>
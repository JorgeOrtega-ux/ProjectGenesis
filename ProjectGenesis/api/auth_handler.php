<?php
// FILE: api/auth_handler.php

include '../config/config.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'js.api.invalidAction'];

define('CODE_RESEND_COOLDOWN_SECONDS', 60);

// --- ▼▼▼ INICIO DE MODIFICACIÓN (CONSTANTES GLOBALES) ▼▼▼ ---
$minPasswordLength = (int)($GLOBALS['site_settings']['min_password_length'] ?? 8);
// define('MIN_PASSWORD_LENGTH', 8); // <-- ELIMINADO
define('MAX_PASSWORD_LENGTH', 72);
// --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
define('MIN_USERNAME_LENGTH', 6);
define('MAX_USERNAME_LENGTH', 32);
define('MAX_EMAIL_LENGTH', 255);


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
            "INSERT INTO user_metadata (user_id, ip_address, device_type, browser_info) 
             VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$userId, $ip, $deviceType, $browserInfo]);

    } catch (PDOException $e) {
        logDatabaseError($e, 'auth_handler - logUserMetadata');
    }
}


function createUserAndLogin($pdo, $basePath, $email, $username, $passwordHash, $userIdFromVerification) {
    
    $authToken = bin2hex(random_bytes(32)); 
    $stmt = $pdo->prepare("INSERT INTO users (email, username, password, is_2fa_enabled, auth_token) VALUES (?, ?, ?, 0, ?)");
    $stmt->execute([$email, $username, $passwordHash, $authToken]);
    $userId = $pdo->lastInsertId();

    $localAvatarUrl = null;
    try {
        // --- INICIO DE MODIFICACIÓN (Ruta de Avatares) ---
        $savePathDir = dirname(__DIR__) . '/assets/uploads/avatars_default'; // Nueva carpeta
        $fileName = "user-{$userId}.png";
        $fullSavePath = $savePathDir . '/' . $fileName;
        $publicUrl = $basePath . '/assets/uploads/avatars_default/' . $fileName; // Nueva carpeta
        // --- FIN DE MODIFICACIÓN ---

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


    session_regenerate_id(true);

    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;
    $_SESSION['profile_image_url'] = $localAvatarUrl;
    $_SESSION['role'] = 'user'; 
    $_SESSION['auth_token'] = $authToken; 
    
    logUserMetadata($pdo, $userId); 
    

    $stmt = $pdo->prepare("DELETE FROM verification_codes WHERE id = ?");
    $stmt->execute([$userIdFromVerification]);

    generateCsrfToken();

    unset($_SESSION['registration_step']);
    unset($_SESSION['registration_email']); 

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
            
            // --- ▼▼▼ INICIO DE MODIFICACIÓN (BLOQUEAR REGISTRO) ▼▼▼ ---
            if (!isset($GLOBALS['site_settings']['allow_new_registrations']) || $GLOBALS['site_settings']['allow_new_registrations'] !== '1') {
                $response['message'] = 'js.auth.errorRegistrationsDisabled'; // Nueva clave i18n
                echo json_encode($response);
                exit;
            }
            // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            
            // --- ▼▼▼ INICIO DE MODIFICACIÓN (DOMINIOS GLOBALES) ▼▼▼ ---
            $domainsString = $GLOBALS['site_settings']['allowed_email_domains'] ?? '';
            $allowedDomains = preg_split('/[\s,]+/', $domainsString, -1, PREG_SPLIT_NO_EMPTY);
            // $allowedDomains = ['gmail.com', 'outlook.com', 'hotmail.com', 'yahoo.com', 'icloud.com']; // <-- ELIMINADO
            // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
            $emailDomain = substr($email, strrpos($email, '@') + 1);

            if (empty($email) || empty($password)) {
                $response['message'] = 'js.auth.errorCompleteEmailPass';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $response['message'] = 'js.auth.errorInvalidEmail';
            } elseif (strlen($email) > MAX_EMAIL_LENGTH) {
                $response['message'] = 'js.auth.errorEmailLength';
            // --- ▼▼▼ INICIO DE MODIFICACIÓN (DOMINIOS GLOBALES) ▼▼▼ ---
            } elseif (!empty($allowedDomains) && !in_array(strtolower($emailDomain), $allowedDomains)) {
                $response['message'] = 'js.auth.errorEmailDomain';
            // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
            // --- ▼▼▼ INICIO DE MODIFICACIÓN (PASS GLOBAL) ▼▼▼ ---
            } elseif (strlen($password) < $minPasswordLength) {
                $response['message'] = 'js.auth.errorPasswordMinLength';
                $response['data'] = ['length' => $minPasswordLength];
            // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
            } elseif (strlen($password) > MAX_PASSWORD_LENGTH) {
                $response['message'] = 'js.auth.errorPasswordMaxLength';
                $response['data'] = ['length' => MAX_PASSWORD_LENGTH];
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
            // --- ▼▼▼ INICIO DE MODIFICACIÓN (PASS GLOBAL) ▼▼▼ ---
            } elseif (strlen($password) < $minPasswordLength || strlen($password) > MAX_PASSWORD_LENGTH) {
                 $response['message'] = 'js.auth.errorPasswordLength';
                 $response['data'] = ['min' => $minPasswordLength, 'max' => MAX_PASSWORD_LENGTH];
            // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
            } elseif (strlen($username) < MIN_USERNAME_LENGTH) {
                $response['message'] = 'js.auth.errorUsernameMinLength';
                $response['data'] = ['length' => MIN_USERNAME_LENGTH];
            } elseif (strlen($username) > MAX_USERNAME_LENGTH) {
                $response['message'] = 'js.auth.errorUsernameMaxLength';
                $response['data'] = ['length' => MAX_USERNAME_LENGTH];
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
                    $response['message'] = 'js.api.errorDatabase';
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

                    if ($secondsPassed < CODE_RESEND_COOLDOWN_SECONDS) {
                        $secondsRemaining = CODE_RESEND_COOLDOWN_SECONDS - $secondsPassed;
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
                            $response['data'] = ['seconds' => $secondsRemaining ?? CODE_RESEND_COOLDOWN_SECONDS];
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
                // --- ▼▼▼ INICIO DE MODIFICACIÓN (LOCKOUT GLOBAL) ▼▼▼ ---
                $response['message'] = 'js.auth.errorTooManyAttempts';
                $response['data'] = ['minutes' => (int)($GLOBALS['site_settings']['lockout_time_minutes'] ?? 5)];
                // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
            } else {
                try {
                    // 1. Validar que el usuario exista y tenga 2FA activado
                    $stmt_check_user = $pdo->prepare("SELECT id, is_2fa_enabled FROM users WHERE email = ?");
                    $stmt_check_user->execute([$email]);
                    $user = $stmt_check_user->fetch();

                    if (!$user) {
                        // No revelar si el usuario existe o no. Simular éxito.
                        // (Aunque si llegó aquí, es probable que la contraseña fuera correcta)
                        logFailedAttempt($pdo, $email, $ip, 'login_fail'); // Registrar como intento fallido
                        throw new Exception('js.auth.errorUserNotFound'); // Error genérico
                    }
                    
                    if ($user['is_2fa_enabled'] != 1) {
                         // Esto no debería pasar si la lógica de login es correcta, pero por si acaso.
                         throw new Exception('js.auth.errorUnknown');
                    }
                    
                    // 2. Checar cooldown
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

                        if ($secondsPassed < CODE_RESEND_COOLDOWN_SECONDS) {
                            $secondsRemaining = CODE_RESEND_COOLDOWN_SECONDS - $secondsPassed;
                            throw new Exception('js.auth.errorCodeCooldown');
                        }
                    }

                    // 3. Borrar código viejo y crear uno nuevo
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
                            $response['data'] = ['seconds' => $secondsRemaining ?? CODE_RESEND_COOLDOWN_SECONDS];
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
                    // --- ▼▼▼ INICIO DE MODIFICACIÓN (LOCKOUT GLOBAL) ▼▼▼ ---
                    $response['message'] = 'js.auth.errorTooManyAttempts';
                    $response['data'] = ['minutes' => (int)($GLOBALS['site_settings']['lockout_time_minutes'] ?? 5)];
                    // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
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
                            $response['cooldown'] = CODE_RESEND_COOLDOWN_SECONDS; 

                        } else {
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
                            
                            logUserMetadata($pdo, $user['id']); 

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
                    // --- ▼▼▼ INICIO DE MODIFICACIÓN (LOCKOUT GLOBAL) ▼▼▼ ---
                    $response['message'] = 'js.auth.errorTooManyAttempts';
                    $response['data'] = ['minutes' => (int)($GLOBALS['site_settings']['lockout_time_minutes'] ?? 5)];
                    // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
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
                            
                            logUserMetadata($pdo, $user['id']); 
                            
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

                    if ($secondsPassed < CODE_RESEND_COOLDOWN_SECONDS) {
                        $secondsRemaining = CODE_RESEND_COOLDOWN_SECONDS - $secondsPassed;
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
                            $response['data'] = ['seconds' => $secondsRemaining ?? CODE_RESEND_COOLDOWN_SECONDS];
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
                    // --- ▼▼▼ INICIO DE MODIFICACIÓN (LOCKOUT GLOBAL) ▼▼▼ ---
                    $response['message'] = 'js.auth.errorTooManyAttempts';
                    $response['data'] = ['minutes' => (int)($GLOBALS['site_settings']['lockout_time_minutes'] ?? 5)];
                    // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
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
            // --- ▼▼▼ INICIO DE MODIFICACIÓN (PASS GLOBAL) ▼▼▼ ---
            } elseif (strlen($newPassword) < $minPasswordLength) {
                $response['message'] = 'js.auth.errorPasswordMinLength';
                $response['data'] = ['length' => $minPasswordLength];
            // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
            } elseif (strlen($newPassword) > MAX_PASSWORD_LENGTH) {
                $response['message'] = 'js.auth.errorPasswordMaxLength';
                $response['data'] = ['length' => MAX_PASSWORD_LENGTH];
            } else {
                
                if (checkLockStatus($pdo, $email, $ip)) {
                    // --- ▼▼▼ INICIO DE MODIFICACIÓN (LOCKOUT GLOBAL) ▼▼▼ ---
                    $response['message'] = 'js.auth.errorTooManyAttempts';
                    $response['data'] = ['minutes' => (int)($GLOBALS['site_settings']['lockout_time_minutes'] ?? 5)];
                    // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
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
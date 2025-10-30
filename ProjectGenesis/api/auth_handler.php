<?php
include '../config/config.php';
header('Content-Type: application/json');

// --- ▼▼▼ MODIFICADO ▼▼▼ ---
// $response ahora usa claves de traducción.
$response = ['success' => false, 'message' => 'js.api.invalidAction'];
// --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---

define('CODE_RESEND_COOLDOWN_SECONDS', 60);

define('MIN_PASSWORD_LENGTH', 8);
define('MAX_PASSWORD_LENGTH', 72);
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

function getPreferredLanguage($acceptLanguage) {
    $supportedLanguages = [
        'en-us' => 'en-us',
        'es-mx' => 'es-mx',
        'es-latam' => 'es-latam',
        'fr-fr' => 'fr-fr'
    ];
    
    $primaryLanguageMap = [
        'es' => 'es-latam',
        'en' => 'en-us',
        'fr' => 'fr-fr'
    ];
    
    $defaultLanguage = 'en-us';

    if (empty($acceptLanguage)) {
        return $defaultLanguage;
    }

    $langs = [];
    preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i', $acceptLanguage, $matches);

    if (!empty($matches[1])) {
        $langs = array_map('strtolower', $matches[1]);
    }

    $primaryMatch = null;
    foreach ($langs as $lang) {
        if (isset($supportedLanguages[$lang])) {
            return $supportedLanguages[$lang];
        }
        
        $primary = substr($lang, 0, 2);
        if ($primaryMatch === null && isset($primaryLanguageMap[$primary])) {
            $primaryMatch = $primaryLanguageMap[$primary];
        }
    }
    
    if ($primaryMatch !== null) {
        return $primaryMatch;
    }

    return $defaultLanguage;
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
        $savePathDir = dirname(__DIR__) . '/assets/uploads/avatars';
        $fileName = "user-{$userId}.png";
        $fullSavePath = $savePathDir . '/' . $fileName;
        $publicUrl = $basePath . '/assets/uploads/avatars/' . $fileName;

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
        // --- ▼▼▼ MODIFICADO ▼▼▼ ---
        $response['message'] = 'js.api.errorSecurityRefresh';
        // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
        echo json_encode($response);
        exit;
    }


    if (isset($_POST['action'])) {
    
        $action = $_POST['action'];

        if ($action === 'register-check-email') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $allowedDomains = ['gmail.com', 'outlook.com', 'hotmail.com', 'yahoo.com', 'icloud.com'];
            $emailDomain = substr($email, strrpos($email, '@') + 1);

            // --- ▼▼▼ INICIO DE MODIFICACIÓN (CLAVES DE TRADUCCIÓN) ▼▼▼ ---
            if (empty($email) || empty($password)) {
                $response['message'] = 'js.auth.errorCompleteEmailPass';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $response['message'] = 'js.auth.errorInvalidEmail';
            } elseif (strlen($email) > MAX_EMAIL_LENGTH) {
                $response['message'] = 'js.auth.errorEmailLength';
            } elseif (!in_array(strtolower($emailDomain), $allowedDomains)) {
                $response['message'] = 'js.auth.errorEmailDomain';
            } elseif (strlen($password) < MIN_PASSWORD_LENGTH) {
                $response['message'] = 'js.auth.errorPasswordMinLength';
                $response['data'] = ['length' => MIN_PASSWORD_LENGTH];
            } elseif (strlen($password) > MAX_PASSWORD_LENGTH) {
                $response['message'] = 'js.auth.errorPasswordMaxLength';
                $response['data'] = ['length' => MAX_PASSWORD_LENGTH];
            // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
            } else {
                try {
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    if ($stmt->fetch()) {
                        // --- ▼▼▼ MODIFICADO ▼▼▼ ---
                        $response['message'] = 'js.auth.errorEmailInUse';
                        // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
                    } else {
                        $_SESSION['registration_step'] = 2;
                        $_SESSION['registration_email'] = $email; 
                        $response['success'] = true;
                    }
                } catch (PDOException $e) {
                    logDatabaseError($e, 'auth_handler - register-check-email');
                    // --- ▼▼▼ MODIFICADO ▼▼▼ ---
                    $response['message'] = 'js.api.errorDatabase';
                    // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
                }
            }
        }

        elseif ($action === 'register-check-username-and-generate-code') {
            
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $username = $_POST['username'] ?? '';

            // --- ▼▼▼ INICIO DE MODIFICACIÓN (CLAVES DE TRADUCCIÓN) ▼▼▼ ---
            if (empty($email) || empty($password) || empty($username)) {
                $response['message'] = 'js.auth.errorMissingSteps';
            } elseif (strlen($password) < MIN_PASSWORD_LENGTH || strlen($password) > MAX_PASSWORD_LENGTH) {
                 $response['message'] = 'js.auth.errorPasswordLength';
                 $response['data'] = ['min' => MIN_PASSWORD_LENGTH, 'max' => MAX_PASSWORD_LENGTH];
            } elseif (strlen($username) < MIN_USERNAME_LENGTH) {
                $response['message'] = 'js.auth.errorUsernameMinLength';
                $response['data'] = ['length' => MIN_USERNAME_LENGTH];
            } elseif (strlen($username) > MAX_USERNAME_LENGTH) {
                $response['message'] = 'js.auth.errorUsernameMaxLength';
                $response['data'] = ['length' => MAX_USERNAME_LENGTH];
            // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
            } else {
                try {
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                    $stmt->execute([$username]);
                    if ($stmt->fetch()) {
                        // --- ▼▼▼ MODIFICADO ▼▼▼ ---
                        $response['message'] = 'js.auth.errorUsernameInUse';
                        // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
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
                        // --- ▼▼▼ MODIFICADO ▼▼▼ ---
                        $response['message'] = 'js.auth.successCodeGenerated';
                        // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
                    }
                } catch (PDOException $e) {
                    logDatabaseError($e, 'auth_handler - register-check-username');
                    // --- ▼▼▼ MODIFICADO ▼▼▼ ---
                    $response['message'] = 'js.api.errorDatabase';
                    // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
                }
            }
        }

        elseif ($action === 'register-verify') {
            // --- ▼▼▼ MODIFICADO ▼▼▼ ---
            if (empty($_POST['email']) || empty($_POST['verification_code'])) {
                $response['message'] = 'js.auth.errorMissingEmailOrCode';
            // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
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
                        // --- ▼▼▼ MODIFICADO ▼▼▼ ---
                        $response['message'] = 'js.auth.errorCodeExpiredRestart';
                        // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
                        unset($_SESSION['registration_step']);
                        unset($_SESSION['registration_email']); 
                    
                    } else {
                        if (strtolower($pendingUser['code']) !== strtolower($submittedCode)) {
                            // --- ▼▼▼ MODIFICADO ▼▼▼ ---
                            $response['message'] = 'js.auth.errorCodeIncorrect';
                            // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
                        } else {
                            $payloadData = json_decode($pendingUser['payload'], true);
                            
                            if (!$payloadData || empty($payloadData['username']) || empty($payloadData['password_hash'])) {
                                // --- ▼▼▼ MODIFICADO ▼▼▼ ---
                                $response['message'] = 'js.auth.errorCorruptData';
                                // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
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
                                // --- ▼▼▼ MODIFICADO ▼▼▼ ---
                                $response['message'] = 'js.auth.successRegistration';
                                // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
                            }
                        }
                    }
                } catch (PDOException $e) {
                    logDatabaseError($e, 'auth_handler - register-verify');
                    // --- ▼▼▼ MODIFICADO ▼▼▼ ---
                    $response['message'] = 'js.api.errorDatabase';
                    // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
                }
            }
        }
        
        elseif ($action === 'register-resend-code') {
            $email = $_POST['email'] ?? '';

            if (empty($email)) {
                // --- ▼▼▼ MODIFICADO ▼▼▼ ---
                $response['message'] = 'js.auth.errorNoEmail';
                // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
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
                        // --- ▼▼▼ MODIFICADO ▼▼▼ ---
                        throw new Exception('js.auth.errorNoRegistrationData');
                        // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
                    }

                    $lastCodeTime = new DateTime($codeData['created_at'], new DateTimeZone('UTC'));
                    $currentTime = new DateTime('now', new DateTimeZone('UTC'));
                    $secondsPassed = $currentTime->getTimestamp() - $lastCodeTime->getTimestamp();

                    if ($secondsPassed < CODE_RESEND_COOLDOWN_SECONDS) {
                        $secondsRemaining = CODE_RESEND_COOLDOWN_SECONDS - $secondsPassed;
                        // --- ▼▼▼ MODIFICADO ▼▼▼ ---
                        // Lanzamos la clave de traducción. El catch la procesará.
                        throw new Exception('js.auth.errorCodeCooldown');
                        // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
                    }

                    $newCode = str_replace('-', '', generateVerificationCode());
                    
                    $stmt = $pdo->prepare(
                        "UPDATE verification_codes SET code = ?, created_at = NOW() WHERE id = ?"
                    );
                    $stmt->execute([$newCode, $codeData['id']]);


                    $response['success'] = true;
                    // --- ▼▼▼ MODIFICADO ▼▼▼ ---
                    $response['message'] = 'js.auth.successCodeResent';
                    // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---

                } catch (Exception $e) {
                    if ($e instanceof PDOException) {
                        logDatabaseError($e, 'auth_handler - register-resend-code');
                        // --- ▼▼▼ MODIFICADO ▼▼▼ ---
                        $response['message'] = 'js.api.errorDatabase';
                        // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
                    } else {
                        // --- ▼▼▼ MODIFICADO ▼▼▼ ---
                        // Asigna la clave de traducción desde la excepción
                        $response['message'] = $e->getMessage();
                        // Pasamos los datos dinámicos si la clave es la de cooldown
                        if ($response['message'] === 'js.auth.errorCodeCooldown') {
                            $response['data'] = ['seconds' => $secondsRemaining ?? CODE_RESEND_COOLDOWN_SECONDS];
                        }
                        // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
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
                    // --- ▼▼▼ MODIFICADO ▼▼▼ ---
                    $response['message'] = 'js.auth.errorTooManyAttempts';
                    $response['data'] = ['minutes' => LOCKOUT_TIME_MINUTES];
                    // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
                    echo json_encode($response);
                    exit;
                }

                try {
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    $user = $stmt->fetch();

                    if ($user && password_verify($password, $user['password'])) {
                        
                        // --- ▼▼▼ INICIO DE LA MODIFICACIÓN: CHEQUEO DE ESTADO DE CUENTA ▼▼▼ ---
                        if ($user['account_status'] === 'deleted') {
                            $response['message'] = 'js.auth.errorAccountDeleted'; // Aún útil como fallback
                            $response['redirect_to_status'] = 'deleted'; // ¡La clave mágica!
                            echo json_encode($response);
                            exit;
                        }
                        
                        if ($user['account_status'] === 'suspended') {
                            $response['message'] = 'js.auth.errorAccountSuspended';
                            $response['redirect_to_status'] = 'suspended'; // ¡La clave mágica!
                            echo json_encode($response);
                            exit;
                        }
                        // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---

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
                            // --- ▼▼▼ MODIFICADO ▼▼▼ ---
                            $response['message'] = 'js.auth.info2faRequired';
                            // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
                            $response['is_2fa_required'] = true; 

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
                            // --- ▼▼▼ MODIFICADO ▼▼▼ ---
                            $response['message'] = 'js.auth.successLogin';
                            // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
                            $response['is_2fa_required'] = false; 
                        }
                    
                    } else {
                        logFailedAttempt($pdo, $email, $ip, 'login_fail');
                        // --- ▼▼▼ MODIFICADO ▼▼▼ ---
                        $response['message'] = 'js.auth.errorInvalidCredentials';
                        // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
                    }
                } catch (PDOException $e) {
                    logDatabaseError($e, 'auth_handler - login-check-credentials');
                    // --- ▼▼▼ MODIFICADO ▼▼▼ ---
                    $response['message'] = 'js.api.errorDatabase';
                    // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
                }
            } else {
                // --- ▼▼▼ MODIFICADO ▼▼▼ ---
                $response['message'] = 'js.auth.errorCompleteAllFields';
                // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
            }
        }


        elseif ($action === 'login-verify-2fa') {
            $email = $_POST['email'] ?? '';
            $submittedCode = str_replace('-', '', $_POST['verification_code'] ?? '');
            $ip = getIpAddress();

            if (empty($email) || empty($submittedCode)) {
                // --- ▼▼▼ MODIFICADO ▼▼▼ ---
                $response['message'] = 'js.auth.errorMissingVerificationData';
                // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
            } else {
                
                if (checkLockStatus($pdo, $email, $ip)) {
                    // --- ▼▼▼ MODIFICADO ▼▼▼ ---
                    $response['message'] = 'js.auth.errorTooManyAttempts';
                    $response['data'] = ['minutes' => LOCKOUT_TIME_MINUTES];
                    // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
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
                        // --- ▼▼▼ MODIFICADO ▼▼▼ ---
                        $response['message'] = 'js.auth.errorCodeExpired';
                        // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
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
                            // --- ▼▼▼ MODIFICADO ▼▼▼ ---
                            $response['message'] = 'js.auth.successLogin';
                            // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
                        } else {
                            // --- ▼▼▼ MODIFICADO ▼▼▼ ---
                            $response['message'] = 'js.auth.errorUserNotFound';
                            // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
                        }
                    }
                } catch (PDOException $e) {
                    logDatabaseError($e, 'auth_handler - login-verify-2fa');
                    // --- ▼▼▼ MODIFICADO ▼▼▼ ---
                    $response['message'] = 'js.api.errorDatabase';
                    // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
                }
            }
        }



        elseif ($action === 'reset-check-email') {
            $email = $_POST['email'] ?? '';

            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                // --- ▼▼▼ MODIFICADO ▼▼▼ ---
                $response['message'] = 'js.auth.errorInvalidEmail';
                // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
            } else {
                try {
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    if (!$stmt->fetch()) {
                        $response['success'] = true; 
                        // --- ▼▼▼ MODIFICADO ▼▼▼ ---
                        $response['message'] = 'js.auth.infoCodeSentIfExists';
                        // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
                        
                        $_SESSION['reset_step'] = 2;
                        $_SESSION['reset_email'] = $email;
                        
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
                        // --- ▼▼▼ MODIFICADO ▼▼▼ ---
                        $response['message'] = 'js.auth.successCodeGenerated';
                        // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
                        
                        $_SESSION['reset_step'] = 2;
                        $_SESSION['reset_email'] = $email;
                    }
                } catch (PDOException $e) {
                    logDatabaseError($e, 'auth_handler - reset-check-email');
                    // --- ▼▼▼ MODIFICADO ▼▼▼ ---
                    $response['message'] = 'js.api.errorDatabase';
                    // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
                }
            }
        }

        elseif ($action === 'reset-resend-code') {
            $email = $_POST['email'] ?? '';

            if (empty($email)) {
                // --- ▼▼▼ MODIFICADO ▼▼▼ ---
                $response['message'] = 'js.auth.errorNoEmail';
                // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
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
                        // --- ▼▼▼ MODIFICADO ▼▼▼ ---
                        throw new Exception('js.auth.errorNoResetData');
                        // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
                    }

                    $lastCodeTime = new DateTime($codeData['created_at'], new DateTimeZone('UTC'));
                    $currentTime = new DateTime('now', new DateTimeZone('UTC'));
                    $secondsPassed = $currentTime->getTimestamp() - $lastCodeTime->getTimestamp();

                    if ($secondsPassed < CODE_RESEND_COOLDOWN_SECONDS) {
                        $secondsRemaining = CODE_RESEND_COOLDOWN_SECONDS - $secondsPassed;
                        // --- ▼▼▼ MODIFICADO ▼▼▼ ---
                        throw new Exception('js.auth.errorCodeCooldown');
                        // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
                    }

                    $newCode = str_replace('-', '', generateVerificationCode());
                    
                    $stmt = $pdo->prepare(
                        "UPDATE verification_codes SET code = ?, created_at = NOW() WHERE id = ?"
                    );
                    $stmt->execute([$newCode, $codeData['id']]);

                    $response['success'] = true;
                    // --- ▼▼▼ MODIFICADO ▼▼▼ ---
                    $response['message'] = 'js.auth.successCodeResent';
                    // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---

                } catch (Exception $e) {
                    if ($e instanceof PDOException) {
                        logDatabaseError($e, 'auth_handler - reset-resend-code');
                        // --- ▼▼▼ MODIFICADO ▼▼▼ ---
                        $response['message'] = 'js.api.errorDatabase';
                        // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
                    } else {
                        // --- ▼▼▼ MODIFICADO ▼▼▼ ---
                        $response['message'] = $e->getMessage();
                        if ($response['message'] === 'js.auth.errorCodeCooldown') {
                            $response['data'] = ['seconds' => $secondsRemaining ?? CODE_RESEND_COOLDOWN_SECONDS];
                        }
                        // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
                    }
                }
            }
        }

        elseif ($action === 'reset-check-code') {
            $email = $_POST['email'] ?? '';
            $submittedCode = str_replace('-', '', $_POST['verification_code'] ?? '');
            $ip = getIpAddress(); 

            if (empty($email) || empty($submittedCode)) {
                // --- ▼▼▼ MODIFICADO ▼▼▼ ---
                $response['message'] = 'js.auth.errorMissingVerificationData';
                // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
            } else {
                
                if (checkLockStatus($pdo, $email, $ip)) {
                    // --- ▼▼▼ MODIFICADO ▼▼▼ ---
                    $response['message'] = 'js.auth.errorTooManyAttempts';
                    $response['data'] = ['minutes' => LOCKOUT_TIME_MINUTES];
                    // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
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
                        // --- ▼▼▼ MODIFICADO ▼▼▼ ---
                        $response['message'] = 'js.auth.errorCodeExpired';
                        // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
                    } else {
                        clearFailedAttempts($pdo, $email);
                        $response['success'] = true;
                        
                        $_SESSION['reset_step'] = 3;
                        $_SESSION['reset_code'] = $submittedCode; 
                    }
                } catch (PDOException $e) {
                    logDatabaseError($e, 'auth_handler - reset-check-code');
                    // --- ▼▼▼ MODIFICADO ▼▼▼ ---
                    $response['message'] = 'js.api.errorDatabase';
                    // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
                }
            }
        }

        elseif ($action === 'reset-update-password') {
            $email = $_SESSION['reset_email'] ?? '';
            $submittedCode = $_SESSION['reset_code'] ?? '';
            
            $newPassword = $_POST['password'] ?? '';
            $ip = getIpAddress(); 

            // --- ▼▼▼ INICIO DE MODIFICACIÓN (CLAVES DE TRADUCCIÓN) ▼▼▼ ---
            if (empty($email) || empty($submittedCode) || empty($newPassword)) {
                $response['message'] = 'js.auth.errorMissingDataRestart';
            } elseif (strlen($newPassword) < MIN_PASSWORD_LENGTH) {
                $response['message'] = 'js.auth.errorPasswordMinLength';
                $response['data'] = ['length' => MIN_PASSWORD_LENGTH];
            } elseif (strlen($newPassword) > MAX_PASSWORD_LENGTH) {
                $response['message'] = 'js.auth.errorPasswordMaxLength';
                $response['data'] = ['length' => MAX_PASSWORD_LENGTH];
            // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
            } else {
                
                if (checkLockStatus($pdo, $email, $ip)) {
                    // --- ▼▼▼ MODIFICADO ▼▼▼ ---
                    $response['message'] = 'js.auth.errorTooManyAttempts';
                    $response['data'] = ['minutes' => LOCKOUT_TIME_MINUTES];
                    // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
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
                        // --- ▼▼▼ MODIFICADO ▼▼▼ ---
                        $response['message'] = 'js.auth.errorInvalidResetSession';
                        // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
                        
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
                        // --- ▼▼▼ MODIFICADO ▼▼▼ ---
                        $response['message'] = 'js.auth.successPasswordUpdateRedirect';
                        // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
                        
                        unset($_SESSION['reset_step']);
                        unset($_SESSION['reset_email']);
                        unset($_SESSION['reset_code']);
                    }
                } catch (PDOException $e) {
                    logDatabaseError($e, 'auth_handler - reset-update-password');
                    // --- ▼▼▼ MODIFICADO ▼▼▼ ---
                    $response['message'] = 'js.api.errorDatabase';
                    // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
                }
            }
        }
        
    }
}

echo json_encode($response);
exit;
?>
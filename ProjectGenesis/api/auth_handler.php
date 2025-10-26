<?php

include '../config/config.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Acción no válida.'];

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
        $response['message'] = 'Error de seguridad. Por favor, recarga la página e inténtalo de nuevo.';
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

            if (empty($email) || empty($password)) {
                $response['message'] = 'Por favor, completa email y contraseña.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $response['message'] = 'El formato de correo no es válido.';
            } elseif (strlen($email) > MAX_EMAIL_LENGTH) {
                $response['message'] = 'El correo no puede tener más de ' . MAX_EMAIL_LENGTH . ' caracteres.';
            } elseif (!in_array(strtolower($emailDomain), $allowedDomains)) {
                $response['message'] = 'Solo se permiten correos @gmail, @outlook, @hotmail, @yahoo o @icloud.';
            } elseif (strlen($password) < MIN_PASSWORD_LENGTH) {
                $response['message'] = 'La contraseña debe tener al menos ' . MIN_PASSWORD_LENGTH . ' caracteres.';
            } elseif (strlen($password) > MAX_PASSWORD_LENGTH) {
                $response['message'] = 'La contraseña no puede tener más de ' . MAX_PASSWORD_LENGTH . ' caracteres.';
            } else {
                try {
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    if ($stmt->fetch()) {
                        $response['message'] = 'Este correo electrónico ya está en uso.';
                    } else {
                        $_SESSION['registration_step'] = 2;
                        $_SESSION['registration_email'] = $email; 
                        $response['success'] = true;
                    }
                } catch (PDOException $e) {
                    logDatabaseError($e, 'auth_handler - register-check-email');
                    $response['message'] = 'Error en la base de datos.';
                }
            }
        }

        elseif ($action === 'register-check-username-and-generate-code') {
            
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $username = $_POST['username'] ?? '';

            if (empty($email) || empty($password) || empty($username)) {
                $response['message'] = 'Faltan datos de los pasos anteriores.';
            } elseif (strlen($password) < MIN_PASSWORD_LENGTH || strlen($password) > MAX_PASSWORD_LENGTH) {
                 $response['message'] = 'La contraseña no cumple con los límites (debe tener entre ' . MIN_PASSWORD_LENGTH . ' y ' . MAX_PASSWORD_LENGTH . ' caracteres).';
            } elseif (strlen($username) < MIN_USERNAME_LENGTH) {
                $response['message'] = 'El nombre de usuario debe tener al menos ' . MIN_USERNAME_LENGTH . ' caracteres.';
            } elseif (strlen($username) > MAX_USERNAME_LENGTH) {
                $response['message'] = 'El nombre de usuario no puede tener más de ' . MAX_USERNAME_LENGTH . ' caracteres.';
            } else {
                try {
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                    $stmt->execute([$username]);
                    if ($stmt->fetch()) {
                        $response['message'] = 'Ese nombre de usuario ya está en uso.';
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
                        $response['message'] = 'Código de verificación generado.';
                    }
                } catch (PDOException $e) {
                    logDatabaseError($e, 'auth_handler - register-check-username');
                    $response['message'] = 'Error en la base de datos.';
                }
            }
        }

        elseif ($action === 'register-verify') {
            if (empty($_POST['email']) || empty($_POST['verification_code'])) {
                $response['message'] = 'Faltan el correo o el código de verificación.';
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
                        $response['message'] = 'El código de verificación es incorrecto o ha expirado. Vuelve a empezar.';
                        unset($_SESSION['registration_step']);
                        unset($_SESSION['registration_email']); 
                    
                    } else {
                        if (strtolower($pendingUser['code']) !== strtolower($submittedCode)) {
                            $response['message'] = 'El código de verificación es incorrecto.';
                        } else {
                            $payloadData = json_decode($pendingUser['payload'], true);
                            
                            if (!$payloadData || empty($payloadData['username']) || empty($payloadData['password_hash'])) {
                                $response['message'] = 'Error al procesar el registro. Datos corruptos.';
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
                                $response['message'] = '¡Registro completado! Iniciando sesión...';
                            }
                        }
                    }
                } catch (PDOException $e) {
                    logDatabaseError($e, 'auth_handler - register-verify');
                    $response['message'] = 'Error en la base de datos.';
                }
            }
        }
        
        elseif ($action === 'register-resend-code') {
            $email = $_POST['email'] ?? '';

            if (empty($email)) {
                $response['message'] = 'No se ha proporcionado un email.';
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
                        throw new Exception('No se encontraron datos de registro. Por favor, vuelve a empezar.');
                    }

                    $lastCodeTime = new DateTime($codeData['created_at'], new DateTimeZone('UTC'));
                    $currentTime = new DateTime('now', new DateTimeZone('UTC'));
                    $secondsPassed = $currentTime->getTimestamp() - $lastCodeTime->getTimestamp();

                    if ($secondsPassed < CODE_RESEND_COOLDOWN_SECONDS) {
                        $secondsRemaining = CODE_RESEND_COOLDOWN_SECONDS - $secondsPassed;
                        throw new Exception("Debes esperar {$secondsRemaining} segundos más para reenviar el código.");
                    }

                    $newCode = str_replace('-', '', generateVerificationCode());
                    
                    $stmt = $pdo->prepare(
                        "UPDATE verification_codes SET code = ?, created_at = NOW() WHERE id = ?"
                    );
                    $stmt->execute([$newCode, $codeData['id']]);


                    $response['success'] = true;
                    $response['message'] = 'Se ha reenviado un nuevo código de verificación.';

                } catch (Exception $e) {
                    if ($e instanceof PDOException) {
                        logDatabaseError($e, 'auth_handler - register-resend-code');
                        $response['message'] = 'Error en la base de datos.';
                    } else {
                        $response['message'] = $e->getMessage();
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
                    $response['message'] = 'Demasiados intentos fallidos. Por favor, inténtalo de nuevo en ' . LOCKOUT_TIME_MINUTES . ' minutos.';
                    echo json_encode($response);
                    exit;
                }

                try {
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    $user = $stmt->fetch();

                    if ($user && password_verify($password, $user['password'])) {
                        
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
                            $response['message'] = 'Verificación de dos pasos requerida.';
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
                            $response['message'] = 'Inicio de sesión correcto.';
                            $response['is_2fa_required'] = false; 
                        }
                    
                    } else {
                        logFailedAttempt($pdo, $email, $ip, 'login_fail');
                        $response['message'] = 'Correo o contraseña incorrectos.';
                    }
                } catch (PDOException $e) {
                    logDatabaseError($e, 'auth_handler - login-check-credentials');
                    $response['message'] = 'Error en la base de datos.';
                }
            } else {
                $response['message'] = 'Por favor, completa todos los campos.';
            }
        }


        elseif ($action === 'login-verify-2fa') {
            $email = $_POST['email'] ?? '';
            $submittedCode = str_replace('-', '', $_POST['verification_code'] ?? '');
            $ip = getIpAddress();

            if (empty($email) || empty($submittedCode)) {
                $response['message'] = 'Faltan datos de verificación.';
            } else {
                
                if (checkLockStatus($pdo, $email, $ip)) {
                    $response['message'] = 'Demasiados intentos fallidos. Por favor, inténtalo de nuevo en ' . LOCKOUT_TIME_MINUTES . ' minutos.';
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
                        $response['message'] = 'El código es incorrecto o ha expirado.';
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
                            $response['message'] = 'Inicio de sesión correcto.';
                        } else {
                            $response['message'] = 'Error: No se pudo encontrar el usuario.';
                        }
                    }
                } catch (PDOException $e) {
                    logDatabaseError($e, 'auth_handler - login-verify-2fa');
                    $response['message'] = 'Error en la base de datos.';
                }
            }
        }



        elseif ($action === 'reset-check-email') {
            $email = $_POST['email'] ?? '';

            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $response['message'] = 'Por favor, introduce un correo válido.';
            } else {
                try {
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    if (!$stmt->fetch()) {
                        $response['success'] = true; 
                        $response['message'] = 'Si el correo existe, se enviará un código.';
                        
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
                        $response['message'] = 'Código de recuperación generado.';
                        
                        $_SESSION['reset_step'] = 2;
                        $_SESSION['reset_email'] = $email;
                    }
                } catch (PDOException $e) {
                    logDatabaseError($e, 'auth_handler - reset-check-email');
                    $response['message'] = 'Error en la base de datos.';
                }
            }
        }

        elseif ($action === 'reset-resend-code') {
            $email = $_POST['email'] ?? '';

            if (empty($email)) {
                $response['message'] = 'No se ha proporcionado un email.';
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
                        throw new Exception('No se encontraron datos de reseteo. Por favor, vuelve a empezar.');
                    }

                    $lastCodeTime = new DateTime($codeData['created_at'], new DateTimeZone('UTC'));
                    $currentTime = new DateTime('now', new DateTimeZone('UTC'));
                    $secondsPassed = $currentTime->getTimestamp() - $lastCodeTime->getTimestamp();

                    if ($secondsPassed < CODE_RESEND_COOLDOWN_SECONDS) {
                        $secondsRemaining = CODE_RESEND_COOLDOWN_SECONDS - $secondsPassed;
                        throw new Exception("Debes esperar {$secondsRemaining} segundos más para reenviar el código.");
                    }

                    $newCode = str_replace('-', '', generateVerificationCode());
                    
                    $stmt = $pdo->prepare(
                        "UPDATE verification_codes SET code = ?, created_at = NOW() WHERE id = ?"
                    );
                    $stmt->execute([$newCode, $codeData['id']]);

                    $response['success'] = true;
                    $response['message'] = 'Se ha reenviado un nuevo código de verificación.';

                } catch (Exception $e) {
                    if ($e instanceof PDOException) {
                        logDatabaseError($e, 'auth_handler - reset-resend-code');
                        $response['message'] = 'Error en la base de datos.';
                    } else {
                        $response['message'] = $e->getMessage();
                    }
                }
            }
        }

        elseif ($action === 'reset-check-code') {
            $email = $_POST['email'] ?? '';
            $submittedCode = str_replace('-', '', $_POST['verification_code'] ?? '');
            $ip = getIpAddress(); 

            if (empty($email) || empty($submittedCode)) {
                $response['message'] = 'Faltan datos de verificación.';
            } else {
                
                if (checkLockStatus($pdo, $email, $ip)) {
                    $response['message'] = 'Demasiados intentos fallidos. Por favor, inténtalo de nuevo en ' . LOCKOUT_TIME_MINUTES . ' minutos.';
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
                        $response['message'] = 'El código es incorrecto o ha expirado.';
                    } else {
                        clearFailedAttempts($pdo, $email);
                        $response['success'] = true;
                        
                        $_SESSION['reset_step'] = 3;
                        $_SESSION['reset_code'] = $submittedCode; 
                    }
                } catch (PDOException $e) {
                    logDatabaseError($e, 'auth_handler - reset-check-code');
                    $response['message'] = 'Error en la base de datos.';
                }
            }
        }

        elseif ($action === 'reset-update-password') {
            $email = $_SESSION['reset_email'] ?? '';
            $submittedCode = $_SESSION['reset_code'] ?? '';
            
            $newPassword = $_POST['password'] ?? '';
            $ip = getIpAddress(); 

            if (empty($email) || empty($submittedCode) || empty($newPassword)) {
                $response['message'] = 'Faltan datos. Por favor, vuelve a empezar.';
            } elseif (strlen($newPassword) < MIN_PASSWORD_LENGTH) {
                $response['message'] = 'La nueva contraseña debe tener al menos ' . MIN_PASSWORD_LENGTH . ' caracteres.';
            } elseif (strlen($newPassword) > MAX_PASSWORD_LENGTH) {
                $response['message'] = 'La nueva contraseña no puede tener más de ' . MAX_PASSWORD_LENGTH . ' caracteres.';
            } else {
                
                if (checkLockStatus($pdo, $email, $ip)) {
                    $response['message'] = 'Demasiados intentos fallidos. Por favor, inténtalo de nuevo en ' . LOCKOUT_TIME_MINUTES . ' minutos.';
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
                        $response['message'] = 'La sesión de reseteo es inválida. Vuelve a empezar.';
                        
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
                        $response['message'] = 'Contraseña actualizada. Serás redirigido.';
                        
                        unset($_SESSION['reset_step']);
                        unset($_SESSION['reset_email']);
                        unset($_SESSION['reset_code']);
                    }
                } catch (PDOException $e) {
                    logDatabaseError($e, 'auth_handler - reset-update-password');
                    $response['message'] = 'Error en la base de datos.';
                }
            }
        }
        
    }
}

echo json_encode($response);
exit;
?>
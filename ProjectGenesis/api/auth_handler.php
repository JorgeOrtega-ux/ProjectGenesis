<?php
// /ProjectGenesis/api/auth_handler.php

include '../config/config.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Acción no válida.'];

// --- FUNCIÓN Generador de Código (Alfanumérico) ---
function generateVerificationCode() {
    $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = '';
    $max = strlen($chars) - 1;
    for ($i = 0; $i < 12; $i++) {
        $code .= $chars[random_int(0, $max)];
    }
    return substr($code, 0, 4) . '-' . substr($code, 4, 4) . '-' . substr($code, 8, 4);
}

// --- FUNCIÓN Creación de Usuario (MODIFICADA) ---
function createUserAndLogin($pdo, $basePath, $email, $username, $passwordHash, $userIdFromVerification) {
    
    // 1. Insertar usuario final en la tabla 'users'
    // --- MODIFICADO: Se incluye is_2fa_enabled (aunque el default 0 es de la BD) ---
    $stmt = $pdo->prepare("INSERT INTO users (email, username, password, is_2fa_enabled) VALUES (?, ?, ?, 0)");
    $stmt->execute([$email, $username, $passwordHash]);
    $userId = $pdo->lastInsertId();

    // 2. Generar y guardar avatar localmente
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
        // No hacer nada si falla el avatar
    }

    // 3. Actualizar al usuario con la URL del avatar
    if ($localAvatarUrl) {
        $stmt = $pdo->prepare("UPDATE users SET profile_image_url = ? WHERE id = ?");
        $stmt->execute([$localAvatarUrl, $userId]);
    }

    // --- ¡¡¡INICIO DE LA SOLUCIÓN DE SEGURIDAD!!! ---
    // 4. Regenerar el ID de sesión para prevenir Session Fixation
    session_regenerate_id(true);
    // --- ¡¡¡FIN DE LA SOLUCIÓN DE SEGURIDAD!!! ---

    // 5. Iniciar sesión automáticamente
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;
    $_SESSION['profile_image_url'] = $localAvatarUrl;
    $_SESSION['role'] = 'user'; // Rol por defecto
    // $_SESSION['is_2fa_enabled'] no es necesario aquí, se lee de la BD

    // 6. Limpiar el código de verificación
    $stmt = $pdo->prepare("DELETE FROM verification_codes WHERE id = ?");
    $stmt->execute([$userIdFromVerification]);

    // 7. Regenerar el token CSRF después de un cambio de sesión (login)
    generateCsrfToken();

    return true;
}
// --- FIN DE NUEVAS FUNCIONES ---


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- ¡NUEVA MODIFICACIÓN! VALIDACIÓN CSRF ---
    // Validamos el token en TODAS las peticiones POST a este handler.
    // Las funciones CSRF vienen de config.php
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($submittedToken)) {
        // Si el token no es válido, rechazamos la petición.
        $response['success'] = false;
        $response['message'] = 'Error de seguridad. Por favor, recarga la página e inténtalo de nuevo.';
        echo json_encode($response);
        exit;
    }
    // --- FIN DE LA NUEVA MODIFICACIÓN ---


    if (isset($_POST['action'])) {
    
        $action = $_POST['action'];

        // --- LÓGICA DE REGISTRO PASO 1 (Sin cambios) ---
        if ($action === 'register-check-email') {
            // (Validaciones de email, contraseña y dominio)
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $allowedDomains = ['gmail.com', 'outlook.com', 'hotmail.com', 'yahoo.com', 'icloud.com'];
            $emailDomain = substr($email, strrpos($email, '@') + 1);

            if (empty($email) || empty($password)) {
                $response['message'] = 'Por favor, completa email y contraseña.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $response['message'] = 'El formato de correo no es válido.';
            } elseif (!in_array(strtolower($emailDomain), $allowedDomains)) {
                $response['message'] = 'Solo se permiten correos @gmail, @outlook, @hotmail, @yahoo o @icloud.';
            } elseif (strlen($password) < 8) {
                $response['message'] = 'La contraseña debe tener al menos 8 caracteres.';
            } else {
                try {
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    if ($stmt->fetch()) {
                        $response['message'] = 'Este correo electrónico ya está en uso.';
                    } else {
                        $response['success'] = true;
                    }
                } catch (PDOException $e) {
                    $response['message'] = 'Error en la base de datos.';
                }
            }
        }

        // --- LÓGICA DE REGISTRO PASO 2 (Sin cambios) ---
        elseif ($action === 'register-check-username-and-generate-code') {
            
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $username = $_POST['username'] ?? '';

            if (empty($email) || empty($password) || empty($username)) {
                $response['message'] = 'Faltan datos de los pasos anteriores.';
            } elseif (strlen($username) < 6) {
                $response['message'] = 'El nombre de usuario debe tener al menos 6 caracteres.';
            } else {
                try {
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                    $stmt->execute([$username]);
                    if ($stmt->fetch()) {
                        $response['message'] = 'Ese nombre de usuario ya está en uso.';
                    } else {
                        // 1. Limpiar códigos de 'registration' viejos para este email
                        $stmt = $pdo->prepare("DELETE FROM verification_codes WHERE identifier = ? AND code_type = 'registration'");
                        $stmt->execute([$email]);

                        // 2. Preparar los datos
                        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                        $verificationCode = str_replace('-', '', generateVerificationCode());
                        
                        // 3. Crear el payload JSON con los datos temporales
                        $payload = json_encode([
                            'username' => $username,
                            'password_hash' => $hashedPassword
                        ]);

                        // 4. Insertar en la nueva estructura de tabla
                        $stmt = $pdo->prepare(
                            "INSERT INTO verification_codes (identifier, code_type, code, payload) 
                             VALUES (?, 'registration', ?, ?)"
                        );
                        $stmt->execute([$email, $verificationCode, $payload]);

                        $response['success'] = true;
                        $response['message'] = 'Código de verificación generado.';
                    }
                } catch (PDOException $e) {
                    $response['message'] = 'Error en la base de datos: ' . $e->getMessage();
                }
            }
        }

        // --- LÓGICA DE REGISTRO PASO 3 (Sin cambios) ---
        elseif ($action === 'register-verify') {
            if (empty($_POST['email']) || empty($_POST['verification_code'])) {
                $response['message'] = 'Faltan el correo o el código de verificación.';
            } else {
                $email = $_POST['email'];
                $submittedCode = str_replace('-', '', $_POST['verification_code']); 

                try {
                    // 1. Buscar el código por 'identifier' (email) y 'code_type'
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
                    
                    } else {
                        // 2. Comparar el código
                        if (strtolower($pendingUser['code']) !== strtolower($submittedCode)) {
                            $response['message'] = 'El código de verificación es incorrecto.';
                        } else {
                            // 3. ¡Éxito! Decodificar el payload
                            $payloadData = json_decode($pendingUser['payload'], true);
                            
                            if (!$payloadData || empty($payloadData['username']) || empty($payloadData['password_hash'])) {
                                $response['message'] = 'Error al procesar el registro. Datos corruptos.';
                            } else {
                                // 4. Llamar a la función de creación con los datos del payload
                                // (Esta función ya está corregida arriba)
                                createUserAndLogin(
                                    $pdo, 
                                    $basePath, 
                                    $pendingUser['identifier'], // Este es el email
                                    $payloadData['username'], 
                                    $payloadData['password_hash'], 
                                    $pendingUser['id'] // ID de la fila para borrarla
                                );
                                
                                $response['success'] = true;
                                $response['message'] = '¡Registro completado! Iniciando sesión...';
                            }
                        }
                    }
                } catch (PDOException $e) {
                    $response['message'] = 'Error en la base de datos: ' . $e->getMessage();
                }
            }
        }

        // --- ▼▼▼ LÓGICA DE LOGIN PASO 1 (MODIFICADA) ▼▼▼ ---
        elseif ($action === 'login-check-credentials') {
            if (!empty($_POST['email']) && !empty($_POST['password'])) {
                
                $email = $_POST['email'];
                $password = $_POST['password'];
                $ip = getIpAddress(); 

                // 1. VERIFICAR BLOQUEO
                if (checkLockStatus($pdo, $email, $ip)) {
                    $response['message'] = 'Demasiados intentos fallidos. Por favor, inténtalo de nuevo en ' . LOCKOUT_TIME_MINUTES . ' minutos.';
                    echo json_encode($response);
                    exit;
                }

                try {
                    // 2. OBTENER USUARIO (incluyendo el campo 2FA)
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    $user = $stmt->fetch();

                    // 3. VERIFICAR CONTRASEÑA
                    if ($user && password_verify($password, $user['password'])) {
                        
                        // Contraseña correcta, limpiar intentos fallidos
                        clearFailedAttempts($pdo, $email);

                        // 4. VERIFICAR SI 2FA ESTÁ ACTIVO
                        if ($user['is_2fa_enabled'] == 1) {
                            // 2FA ESTÁ ACTIVO: Generar código y pedir Paso 2
                            
                            // Limpiar códigos 2FA viejos
                            $stmt = $pdo->prepare("DELETE FROM verification_codes WHERE identifier = ? AND code_type = '2fa'");
                            $stmt->execute([$email]);

                            // Generar y guardar nuevo código 2FA
                            $verificationCode = str_replace('-', '', generateVerificationCode());
                            $stmt = $pdo->prepare(
                                "INSERT INTO verification_codes (identifier, code_type, code) 
                                 VALUES (?, '2fa', ?)"
                            );
                            $stmt->execute([$email, $verificationCode]);

                            // (Aquí iría la lógica de envío de email, que está pendiente)

                            $response['success'] = true;
                            $response['message'] = 'Verificación de dos pasos requerida.';
                            $response['is_2fa_required'] = true; // Flag para el JS

                        } else {
                            // 2FA ESTÁ INACTIVO: Iniciar sesión directamente
                            session_regenerate_id(true);

                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['username'] = $user['username'];
                            $_SESSION['email'] = $user['email'];
                            $_SESSION['profile_image_url'] = $user['profile_image_url'];
                            $_SESSION['role'] = $user['role']; 

                            generateCsrfToken();

                            $response['success'] = true;
                            $response['message'] = 'Inicio de sesión correcto.';
                            $response['is_2fa_required'] = false; // Flag para el JS
                        }
                    
                    } else {
                        // Contraseña incorrecta
                        logFailedAttempt($pdo, $email, $ip, 'login_fail');
                        $response['message'] = 'Correo o contraseña incorrectos.';
                    }
                } catch (PDOException $e) {
                    $response['message'] = 'Error en la base de datos: ' . $e->getMessage();
                }
            } else {
                $response['message'] = 'Por favor, completa todos los campos.';
            }
        }
        // --- ▲▲▲ FIN LÓGICA LOGIN PASO 1 ▲▲▲ ---


        // --- ▼▼▼ NUEVA LÓGICA LOGIN PASO 2 (VERIFICAR 2FA) ▼▼▼ ---
        elseif ($action === 'login-verify-2fa') {
            $email = $_POST['email'] ?? '';
            $submittedCode = str_replace('-', '', $_POST['verification_code'] ?? '');
            $ip = getIpAddress();

            if (empty($email) || empty($submittedCode)) {
                $response['message'] = 'Faltan datos de verificación.';
            } else {
                
                // 1. VERIFICAR BLOQUEO
                if (checkLockStatus($pdo, $email, $ip)) {
                    $response['message'] = 'Demasiados intentos fallidos. Por favor, inténtalo de nuevo en ' . LOCKOUT_TIME_MINUTES . ' minutos.';
                    echo json_encode($response);
                    exit;
                }
                
                try {
                    // 2. BUSCAR EL CÓDIGO 2FA VÁLIDO
                    $stmt = $pdo->prepare(
                        "SELECT * FROM verification_codes 
                         WHERE identifier = ? 
                         AND code_type = '2fa'
                         AND created_at > (NOW() - INTERVAL 15 MINUTE)" // 15 min de validez
                    );
                    $stmt->execute([$email]);
                    $codeData = $stmt->fetch();

                    // 3. COMPARAR CÓDIGO
                    if (!$codeData || strtolower($codeData['code']) !== strtolower($submittedCode)) {
                        // Código incorrecto o expirado
                        logFailedAttempt($pdo, $email, $ip, 'login_fail');
                        $response['message'] = 'El código es incorrecto o ha expirado.';
                    } else {
                        // ¡ÉXITO 2FA!
                        
                        // Limpiar todos los intentos fallidos
                        clearFailedAttempts($pdo, $email);

                        // Buscar los datos del usuario para iniciar sesión
                        $stmt_user = $pdo->prepare("SELECT * FROM users WHERE email = ?");
                        $stmt_user->execute([$email]);
                        $user = $stmt_user->fetch();

                        if ($user) {
                            // Iniciar sesión
                            session_regenerate_id(true);
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['username'] = $user['username'];
                            $_SESSION['email'] = $user['email'];
                            $_SESSION['profile_image_url'] = $user['profile_image_url'];
                            $_SESSION['role'] = $user['role']; 
                            generateCsrfToken();

                            // Limpiar el código 2FA que ya se usó
                            $stmt_delete = $pdo->prepare("DELETE FROM verification_codes WHERE id = ?");
                            $stmt_delete->execute([$codeData['id']]);

                            $response['success'] = true;
                            $response['message'] = 'Inicio de sesión correcto.';
                        } else {
                            // Caso raro: el usuario fue eliminado entre el paso 1 y 2
                            $response['message'] = 'Error: No se pudo encontrar el usuario.';
                        }
                    }
                } catch (PDOException $e) {
                    $response['message'] = 'Error en la base de datos.';
                }
            }
        }
        // --- ▲▲▲ FIN LÓGICA LOGIN PASO 2 ▲▲▲ ---


        // --- LÓGICA PARA RESETEO DE CONTRASEÑA ---

        // PASO 1: Verificar email y generar código (Sin cambios)
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
                    }
                } catch (PDOException $e) {
                    $response['message'] = 'Error en la base de datos.';
                }
            }
        }

        // PASO 2: Verificar el código de reseteo (Sin cambios)
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
                    }
                } catch (PDOException $e) {
                    $response['message'] = 'Error en la base de datos.';
                }
            }
        }

        // PASO 3: Verificar todo y actualizar la contraseña (Sin cambios)
        elseif ($action === 'reset-update-password') {
            $email = $_POST['email'] ?? '';
            $submittedCode = str_replace('-', '', $_POST['verification_code'] ?? '');
            $newPassword = $_POST['password'] ?? '';
            $ip = getIpAddress(); 

            if (empty($email) || empty($submittedCode) || empty($newPassword)) {
                $response['message'] = 'Faltan datos. Por favor, vuelve a empezar.';
            } elseif (strlen($newPassword) < 8) {
                $response['message'] = 'La nueva contraseña debe tener al menos 8 caracteres.';
            } else {
                
                if (checkLockStatus($pdo, $email, $ip)) {
                    $response['message'] = 'Demasiados intentos fallidos. Por favor, inténtalo de nuevo en ' . LOCKOUT_TIME_MINUTES . ' minutos.';
                    echo json_encode($response);
                    exit;
                }

                try {
                    // Re-verificamos el código por seguridad
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
                        $response['message'] = 'El código es incorrecto o ha expirado. Vuelve a empezar.';
                    } else {
                        // ¡Éxito! Hashear nueva contraseña y actualizar usuario
                        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
                        
                        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
                        $stmt->execute([$hashedPassword, $email]);

                        // Limpiar el código usado
                        $stmt = $pdo->prepare("DELETE FROM verification_codes WHERE id = ?");
                        $stmt->execute([$codeData['id']]);

                        // Limpiar logs en éxito
                        clearFailedAttempts($pdo, $email);

                        $response['success'] = true;
                        $response['message'] = 'Contraseña actualizada. Serás redirigido.';
                    }
                } catch (PDOException $e) {
                    $response['message'] = 'Error en la base de datos.';
                }
            }
        }
        
    }
}

echo json_encode($response);
exit;
?>
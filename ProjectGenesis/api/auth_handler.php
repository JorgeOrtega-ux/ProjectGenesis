<?php
// /ProjectGenesis/api/auth_handler.php

include '../config/config.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Acción no válida.'];

// --- Constante de Cooldown (NUEVO) ---
define('CODE_RESEND_COOLDOWN_SECONDS', 60);

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

// --- ▼▼▼ INICIO DE NUEVA FUNCIÓN (DETECCIÓN DE IDIOMA) ▼▼▼ ---
/**
 * Analiza la cabecera HTTP_ACCEPT_LANGUAGE para encontrar el idioma compatible preferido.
 * @param string $acceptLanguage La cabecera $_SERVER['HTTP_ACCEPT_LANGUAGE']
 * @return string El código de idioma compatible (ej. 'es-mx', 'es-latam', 'en-us')
 */
function getPreferredLanguage($acceptLanguage) {
    // 1. Lista de idiomas compatibles en tu sitio
    $supportedLanguages = [
        'en-us' => 'en-us',
        'es-mx' => 'es-mx',
        'es-latam' => 'es-latam',
        'fr-fr' => 'fr-fr'
    ];
    
    // 2. Mapeos de idiomas principales (ej. 'es' general -> 'es-latam')
    $primaryLanguageMap = [
        'es' => 'es-latam',
        'en' => 'en-us',
        'fr' => 'fr-fr'
    ];
    
    // 3. Fallback por defecto
    $defaultLanguage = 'en-us';

    if (empty($acceptLanguage)) {
        return $defaultLanguage;
    }

    // 4. Analizar la cabecera (ej. "es-MX,es;q=0.9,en;q=0.8,fr-FR;q=0.7")
    $langs = [];
    preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i', $acceptLanguage, $matches);

    if (!empty($matches[1])) {
        $langs = array_map('strtolower', $matches[1]);
    }

    // 5. Buscar coincidencias
    $primaryMatch = null;
    foreach ($langs as $lang) {
        // 5a. Buscar coincidencia exacta (ej. 'es-mx' == 'es-mx')
        if (isset($supportedLanguages[$lang])) {
            return $supportedLanguages[$lang];
        }
        
        // 5b. Buscar coincidencia de idioma principal (ej. 'es' de 'es-ar')
        $primary = substr($lang, 0, 2);
        if ($primaryMatch === null && isset($primaryLanguageMap[$primary])) {
            $primaryMatch = $primaryLanguageMap[$primary];
        }
    }
    
    // 6. Usar la coincidencia principal si se encontró
    if ($primaryMatch !== null) {
        return $primaryMatch;
    }

    // 7. Usar el idioma por defecto
    return $defaultLanguage;
}
// --- ▲▲▲ FIN DE NUEVA FUNCIÓN ▲▲▲ ---


// --- ▼▼▼ NUEVA FUNCIÓN HELPER PARA METADATA ▼▼▼ ---
/**
 * Registra los metadatos de un inicio de sesión (IP, Dispositivo).
 * @param PDO $pdo
 * @param int $userId El ID del usuario que inicia sesión.
 */
function logUserMetadata($pdo, $userId) {
    try {
        // getIpAddress() ya está definida en config.php
        $ip = getIpAddress(); 
        $browserInfo = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        // Lógica simple para device_type, se puede expandir en el futuro
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
        // --- ▼▼▼ MODIFICACIÓN DE SEGURIDAD (LOG) ▼▼▼ ---
        logDatabaseError($e, 'auth_handler - logUserMetadata');
        // No hacer nada si falla el log de metadata.
        // No queremos que un fallo aquí impida el inicio de sesión.
        // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
    }
}
// --- ▲▲▲ FIN NUEVA FUNCIÓN HELPER ▲▲▲ ---


// --- FUNCIÓN Creación de Usuario (MODIFICADA) ---
function createUserAndLogin($pdo, $basePath, $email, $username, $passwordHash, $userIdFromVerification) {
    
    // 1. Insertar usuario final en la tabla 'users'
    // --- MODIFICADO: Se incluye is_2fa_enabled (aunque el default 0 es de la BD) ---
    // --- ¡MODIFICADO OTRA VEZ! Se añade auth_token ---
    $authToken = bin2hex(random_bytes(32)); // Generar token
    $stmt = $pdo->prepare("INSERT INTO users (email, username, password, is_2fa_enabled, auth_token) VALUES (?, ?, ?, 0, ?)");
    $stmt->execute([$email, $username, $passwordHash, $authToken]);
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

    // --- ▼▼▼ INICIO DE NUEVA LÓGICA (GUARDAR PREFERENCIAS) ▼▼▼ ---
    try {
        // 4. Obtener el idioma preferido del navegador
        $preferredLanguage = getPreferredLanguage($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'en');

        // 5. Insertar las preferencias por defecto en la nueva tabla
        $stmt_prefs = $pdo->prepare(
            "INSERT INTO user_preferences (user_id, language, theme, usage_type) 
             VALUES (?, ?, 'system', 'personal')"
        );
        $stmt_prefs->execute([$userId, $preferredLanguage]);

    } catch (PDOException $e) {
        // Si falla la inserción de preferencias, no detener el registro
        logDatabaseError($e, 'auth_handler - createUser - preferences');
    }
    // --- ▲▲▲ FIN DE NUEVA LÓGICA ▲▲▲ ---


    // --- ¡¡¡INICIO DE LA SOLUCIÓN DE SEGURIDAD!!! ---
    // 6. Regenerar el ID de sesión para prevenir Session Fixation
    session_regenerate_id(true);
    // --- ¡¡¡FIN DE LA SOLUCIÓN DE SEGURIDAD!!! ---

    // 7. Iniciar sesión automáticamente
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;
    $_SESSION['profile_image_url'] = $localAvatarUrl;
    $_SESSION['role'] = 'user'; // Rol por defecto
    $_SESSION['auth_token'] = $authToken; // <-- ¡NUEVA LÍNEA!
    
    // --- ▼▼▼ NUEVA LÍNEA ▼▼▼ ---
    logUserMetadata($pdo, $userId); // Registrar metadatos en el primer login (registro)
    // --- ▲▲▲ FIN NUEVA LÍNEA ▲▲▲ ---
    
    // $_SESSION['is_2fa_enabled'] no es necesario aquí, se lee de la BD

    // 8. Limpiar el código de verificación
    $stmt = $pdo->prepare("DELETE FROM verification_codes WHERE id = ?");
    $stmt->execute([$userIdFromVerification]);

    // 9. Regenerar el token CSRF después de un cambio de sesión (login)
    generateCsrfToken();

    // --- ▼▼▼ INICIO: MODIFICACIÓN (LIMPIAR FLAG DE REGISTRO) ▼▼▼ ---
    // 10. Limpiamos el flag de progreso de registro al completar exitosamente
    unset($_SESSION['registration_step']);
    unset($_SESSION['registration_email']); // <-- MODIFICACIÓN
    // --- ▲▲▲ FIN: MODIFICACIÓN ▲▲▲ ---

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

        // --- LÓGICA DE REGISTRO PASO 1 (Modificado) ---
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
                        // --- ▼▼▼ INICIO: MODIFICACIÓN (AÑADIR FLAG DE PASO 1) ▼▼▼ ---
                        // El usuario ha pasado el paso 1, le damos permiso para el paso 2
                        $_SESSION['registration_step'] = 2;
                        $_SESSION['registration_email'] = $email; // <-- MODIFICACIÓN
                        // --- ▲▲▲ FIN: MODIFICACIÓN ▲▲▲ ---
                        $response['success'] = true;
                    }
                } catch (PDOException $e) {
                    // --- ▼▼▼ MODIFICACIÓN DE SEGURIDAD (LOG) ▼▼▼ ---
                    logDatabaseError($e, 'auth_handler - register-check-email');
                    $response['message'] = 'Error en la base de datos.';
                    // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
                }
            }
        }

        // --- LÓGICA DE REGISTRO PASO 2 (Modificado) ---
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

                        // --- ▼▼▼ INICIO: MODIFICACIÓN (AÑADIR FLAG DE PASO 2) ▼▼▼ ---
                        // El usuario ha pasado el paso 2, le damos permiso para el paso 3
                        $_SESSION['registration_step'] = 3;
                        // --- ▲▲▲ FIN: MODIFICACIÓN ▲▲▲ ---

                        $response['success'] = true;
                        $response['message'] = 'Código de verificación generado.';
                    }
                } catch (PDOException $e) {
                    // --- ▼▼▼ MODIFICACIÓN DE SEGURIDAD (LOG) ▼▼▼ ---
                    logDatabaseError($e, 'auth_handler - register-check-username');
                    $response['message'] = 'Error en la base de datos.';
                    // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
                }
            }
        }

        // --- LÓGICA DE REGISTRO PASO 3 (Modificado) ---
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
                        // --- ▼▼▼ INICIO: MODIFICACIÓN (LIMPIAR FLAG DE REGISTRO) ▼▼▼ ---
                        // Si el código falla o expira, forzamos el reinicio del flujo
                        unset($_SESSION['registration_step']);
                        unset($_SESSION['registration_email']); // <-- MODIFICACIÓN
                        // --- ▲▲▲ FIN: MODIFICACIÓN ▲▲▲ ---
                    
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
                    // --- ▼▼▼ MODIFICACIÓN DE SEGURIDAD (LOG) ▼▼▼ ---
                    logDatabaseError($e, 'auth_handler - register-verify');
                    $response['message'] = 'Error en la base de datos.';
                    // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
                }
            }
        }
        
        // --- ▼▼▼ INICIO: NUEVA ACCIÓN (REENVIAR CÓDIGO DE REGISTRO) ▼▼▼ ---
        elseif ($action === 'register-resend-code') {
            $email = $_POST['email'] ?? '';

            if (empty($email)) {
                $response['message'] = 'No se ha proporcionado un email.';
            } else {
                try {
                    // 1. Buscar el código de registro MÁS RECIENTE para este email
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

                    // 2. Comprobar el Cooldown (Servidor)
                    $lastCodeTime = new DateTime($codeData['created_at'], new DateTimeZone('UTC'));
                    $currentTime = new DateTime('now', new DateTimeZone('UTC'));
                    $secondsPassed = $currentTime->getTimestamp() - $lastCodeTime->getTimestamp();

                    if ($secondsPassed < CODE_RESEND_COOLDOWN_SECONDS) {
                        $secondsRemaining = CODE_RESEND_COOLDOWN_SECONDS - $secondsPassed;
                        throw new Exception("Debes esperar {$secondsRemaining} segundos más para reenviar el código.");
                    }

                    // 3. Generar y ACTUALIZAR el código (reutilizando el payload)
                    $newCode = str_replace('-', '', generateVerificationCode());
                    
                    // Actualizamos el código y la marca de tiempo
                    $stmt = $pdo->prepare(
                        "UPDATE verification_codes SET code = ?, created_at = NOW() WHERE id = ?"
                    );
                    $stmt->execute([$newCode, $codeData['id']]);

                    // (Aquí iría la lógica de envío de email simulado)

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
        // --- ▲▲▲ FIN: NUEVA ACCIÓN (REENVIAR CÓDIGO DE REGISTRO) ▲▲▲ ---

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
                    // 2. OBTENER USUARIO (incluyendo el campo 2FA y auth_token)
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

                            // --- ▼▼▼ ¡INICIO MODIFICACIÓN AUTH_TOKEN! ▼▼▼ ---
                            $authToken = $user['auth_token'];
                            if (empty($authToken)) {
                                $authToken = bin2hex(random_bytes(32));
                                $stmt_token = $pdo->prepare("UPDATE users SET auth_token = ? WHERE id = ?");
                                $stmt_token->execute([$authToken, $user['id']]);
                            }
                            // --- ▲▲▲ ¡FIN MODIFICACIÓN AUTH_TOKEN! ▲▲▲ ---

                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['username'] = $user['username'];
                            $_SESSION['email'] = $user['email'];
                            $_SESSION['profile_image_url'] = $user['profile_image_url'];
                            $_SESSION['role'] = $user['role']; 
                            $_SESSION['auth_token'] = $authToken; // <-- ¡NUEVA LÍNEA!
                            
                            // --- ▼▼▼ NUEVA LÍNEA ▼▼▼ ---
                            logUserMetadata($pdo, $user['id']); // Registrar metadatos en el login
                            // --- ▲▲▲ FIN NUEVA LÍNEA ▲▲▲ ---

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
                    // --- ▼▼▼ MODIFICACIÓN DE SEGURIDAD (LOG) ▼▼▼ ---
                    logDatabaseError($e, 'auth_handler - login-check-credentials');
                    $response['message'] = 'Error en la base de datos.';
                    // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
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

                            // --- ▼▼▼ ¡INICIO MODIFICACIÓN AUTH_TOKEN! ▼▼▼ ---
                            $authToken = $user['auth_token'];
                            if (empty($authToken)) {
                                $authToken = bin2hex(random_bytes(32));
                                $stmt_token = $pdo->prepare("UPDATE users SET auth_token = ? WHERE id = ?");
                                $stmt_token->execute([$authToken, $user['id']]);
                            }
                            // --- ▲▲▲ ¡FIN MODIFICACIÓN AUTH_TOKEN! ▲▲▲ ---

                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['username'] = $user['username'];
                            $_SESSION['email'] = $user['email'];
                            $_SESSION['profile_image_url'] = $user['profile_image_url'];
                            $_SESSION['role'] = $user['role']; 
                            $_SESSION['auth_token'] = $authToken; // <-- ¡NUEVA LÍNEA!
                            
                            // --- ▼▼▼ NUEVA LÍNEA ▼▼▼ ---
                            logUserMetadata($pdo, $user['id']); // Registrar metadatos en el login 2FA
                            // --- ▲▲▲ FIN NUEVA LÍNEA ▲▲▲ ---
                            
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
                    // --- ▼▼▼ MODIFICACIÓN DE SEGURIDAD (LOG) ▼▼▼ ---
                    logDatabaseError($e, 'auth_handler - login-verify-2fa');
                    $response['message'] = 'Error en la base de datos.';
                    // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
                }
            }
        }
        // --- ▲▲▲ FIN LÓGICA LOGIN PASO 2 ▲▲▲ ---


        // --- LÓGICA PARA RESETEO DE CONTRASEÑA ---

        // PASO 1: Verificar email y generar código
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
                        
                        // --- ▼▼▼ ¡INICIO DE MODIFICACIÓN! ▼▼▼ ---
                        // Preparamos los flags de sesión incluso si el email no existe
                        // para evitar que un atacante sepa qué emails están registrados.
                        $_SESSION['reset_step'] = 2;
                        $_SESSION['reset_email'] = $email;
                        // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
                        
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
                        
                        // --- ▼▼▼ ¡INICIO DE MODIFICACIÓN! ▼▼▼ ---
                        $_SESSION['reset_step'] = 2;
                        $_SESSION['reset_email'] = $email;
                        // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
                    }
                } catch (PDOException $e) {
                    logDatabaseError($e, 'auth_handler - reset-check-email');
                    $response['message'] = 'Error en la base de datos.';
                }
            }
        }

        // PASO 1.5: Reenviar código de reseteo
        elseif ($action === 'reset-resend-code') {
            $email = $_POST['email'] ?? '';

            if (empty($email)) {
                $response['message'] = 'No se ha proporcionado un email.';
            } else {
                try {
                    // 1. Buscar el código de reseteo MÁS RECIENTE para este email
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

                    // 2. Comprobar el Cooldown (Servidor)
                    $lastCodeTime = new DateTime($codeData['created_at'], new DateTimeZone('UTC'));
                    $currentTime = new DateTime('now', new DateTimeZone('UTC'));
                    $secondsPassed = $currentTime->getTimestamp() - $lastCodeTime->getTimestamp();

                    if ($secondsPassed < CODE_RESEND_COOLDOWN_SECONDS) {
                        $secondsRemaining = CODE_RESEND_COOLDOWN_SECONDS - $secondsPassed;
                        throw new Exception("Debes esperar {$secondsRemaining} segundos más para reenviar el código.");
                    }

                    // 3. Generar y ACTUALIZAR el código
                    $newCode = str_replace('-', '', generateVerificationCode());
                    
                    // Actualizamos el código y la marca de tiempo
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

        // PASO 2: Verificar el código de reseteo
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
                        
                        // --- ▼▼▼ ¡INICIO DE MODIFICACIÓN! ▼▼▼ ---
                        $_SESSION['reset_step'] = 3;
                        // Guardamos el código verificado para el paso 3
                        $_SESSION['reset_code'] = $submittedCode; 
                        // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
                    }
                } catch (PDOException $e) {
                    logDatabaseError($e, 'auth_handler - reset-check-code');
                    $response['message'] = 'Error en la base de datos.';
                }
            }
        }

        // PASO 3: Verificar todo y actualizar la contraseña
        elseif ($action === 'reset-update-password') {
            // --- ▼▼▼ ¡INICIO DE MODIFICACIÓN! ▼▼▼ ---
            // El email y el código ahora vienen de la SESIÓN, no del POST
            $email = $_SESSION['reset_email'] ?? '';
            $submittedCode = $_SESSION['reset_code'] ?? '';
            // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
            
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
                         AND code = ?" // Comprobamos el código exacto
                    );
                    $stmt->execute([$email, $submittedCode]);
                    $codeData = $stmt->fetch();

                    if (!$codeData) {
                        logFailedAttempt($pdo, $email, $ip, 'reset_fail');
                        $response['message'] = 'La sesión de reseteo es inválida. Vuelve a empezar.';
                        
                        // --- ▼▼▼ ¡INICIO DE MODIFICACIÓN! ▼▼▼ ---
                        unset($_SESSION['reset_step']);
                        unset($_SESSION['reset_email']);
                        unset($_SESSION['reset_code']);
                        // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
                        
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
                        
                        // --- ▼▼▼ ¡INICIO DE MODIFICACIÓN! ▼▼▼ ---
                        unset($_SESSION['reset_step']);
                        unset($_SESSION['reset_email']);
                        unset($_SESSION['reset_code']);
                        // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
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
<?php
// /ProjectGenesis/auth_handler.php

include 'config.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Acción no válida.'];

// --- FUNCIÓN Generador de Código (Sin cambios) ---
function generateVerificationCode() {
    $bytes = random_bytes(6); // 6 bytes = 12 hex chars
    $code = bin2hex($bytes); 
    return substr($code, 0, 4) . '-' . substr($code, 4, 4) . '-' . substr($code, 8, 4);
}

// --- FUNCIÓN Creación de Usuario (Sin cambios) ---
function createUserAndLogin($pdo, $basePath, $email, $username, $passwordHash, $userIdFromVerification) {
    
    // 1. Insertar usuario final en la tabla 'users'
    $stmt = $pdo->prepare("INSERT INTO users (email, username, password) VALUES (?, ?, ?)");
    $stmt->execute([$email, $username, $passwordHash]);
    $userId = $pdo->lastInsertId();

    // 2. Generar y guardar avatar localmente
    $localAvatarUrl = null;
    try {
        $savePathDir = __DIR__ . '/assets/uploads/avatars';
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

    // 4. Iniciar sesión automáticamente
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;
    $_SESSION['profile_image_url'] = $localAvatarUrl;
    $_SESSION['role'] = 'user'; // Rol por defecto

    // 5. Limpiar el código de verificación
    $stmt = $pdo->prepare("DELETE FROM verification_codes WHERE id = ?");
    $stmt->execute([$userIdFromVerification]);

    return true;
}
// --- FIN DE NUEVAS FUNCIONES ---


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    $action = $_POST['action'];

    // --- LÓGICA DE REGISTRO PASO 1 (Sin cambios) ---
    if ($action === 'register-check-email') {
        if (empty($_POST['email']) || empty($_POST['password'])) {
            $response['message'] = 'Por favor, completa email y contraseña.';
        } elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $response['message'] = 'El formato de correo no es válido.';
        } else {
            $email = $_POST['email'];
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
        if (empty($_POST['email']) || empty($_POST['password']) || empty($_POST['username'])) {
            $response['message'] = 'Faltan datos de los pasos anteriores.';
        } else {
            $email = $_POST['email'];
            $username = $_POST['username'];
            $password = $_POST['password'];

            try {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->fetch()) {
                    $response['message'] = 'Ese nombre de usuario ya está en uso.';
                } else {
                    $stmt = $pdo->prepare("DELETE FROM verification_codes WHERE email = ?");
                    $stmt->execute([$email]);

                    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                    $verificationCode = str_replace('-', '', generateVerificationCode());

                    $stmt = $pdo->prepare("INSERT INTO verification_codes (email, username, password_hash, code) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$email, $username, $hashedPassword, $verificationCode]);

                    $response['success'] = true;
                    $response['message'] = 'Código de verificación generado.';
                }
            } catch (PDOException $e) {
                $response['message'] = 'Error en la base de datos: ' . $e->getMessage();
            }
        }
    }

    // --- ¡¡¡LÓGICA DE REGISTRO PASO 3 CORREGIDA!!! ---
    elseif ($action === 'register-verify') {
        if (empty($_POST['email']) || empty($_POST['verification_code'])) {
            $response['message'] = 'Faltan el correo o el código de verificación.';
        } else {
            $email = $_POST['email'];
            $submittedCode = str_replace('-', '', $_POST['verification_code']); 

            try {
                // --- ¡ESTA ES LA CORRECCIÓN CLAVE! ---
                // 1. Buscar el registro PENDIENTE Y VÁLIDO (menos de 15 min)
                // Usamos NOW() de MySQL para que la BD haga la comparación de tiempo.
                $stmt = $pdo->prepare(
                    "SELECT * FROM verification_codes 
                     WHERE email = ? 
                     AND created_at > (NOW() - INTERVAL 15 MINUTE)"
                );
                // --- FIN DE LA CORRECCIÓN CLAVE ---

                $stmt->execute([$email]);
                $pendingUser = $stmt->fetch();

                if (!$pendingUser) {
                    // Si no se encuentra, es porque el email no existe O
                    // el código SÍ EXISTÍA pero tenía más de 15 minutos (y la consulta no lo devolvió).
                    $response['message'] = 'El código de verificación es incorrecto o ha expirado. Vuelve a empezar.';
                
                } else {
                    // 2. Encontramos un registro VÁLIDO (no expirado). 
                    // Ahora SÍ comparamos el código.
                    if (strtolower($pendingUser['code']) !== strtolower($submittedCode)) {
                        $response['message'] = 'El código de verificación es incorrecto.';
                    } else {
                        // 3. ¡ÉXITO! El código es correcto Y no ha expirado.
                        createUserAndLogin(
                            $pdo, 
                            $basePath, 
                            $pendingUser['email'], 
                            $pendingUser['username'], 
                            $pendingUser['password_hash'], 
                            $pendingUser['id']
                        );
                        
                        $response['success'] = true;
                        $response['message'] = '¡Registro completado! Iniciando sesión...';
                    }
                }
            } catch (PDOException $e) {
                $response['message'] = 'Error en la base de datos: ' . $e->getMessage();
            }
        }
    }

    // --- LÓGICA DE LOGIN (Sin cambios) ---
    elseif ($action === 'login') {
        if (!empty($_POST['email']) && !empty($_POST['password'])) {
            
            $email = $_POST['email'];
            $password = $_POST['password'];

            try {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['profile_image_url'] = $user['profile_image_url'];
                    $_SESSION['role'] = $user['role']; 

                    $response['success'] = true;
                    $response['message'] = 'Inicio de sesión correcto.';
                
                } else {
                    $response['message'] = 'Correo o contraseña incorrectos.';
                }
            } catch (PDOException $e) {
                $response['message'] = 'Error en la base de datos: ' . $e->getMessage();
            }
        } else {
            $response['message'] = 'Por favor, completa todos los campos.';
        }
    }
}

echo json_encode($response);
exit;
?>
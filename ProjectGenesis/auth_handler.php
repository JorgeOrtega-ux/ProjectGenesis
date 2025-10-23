<?php
// /ProjectGenesis/auth_handler.php

// Incluir la configuración de sesión y BD
include 'config.php';

// Establecer la cabecera de respuesta como JSON
header('Content-Type: application/json');

// --- LÍNEA CORREGIDA ---
$response = ['success' => false, 'message' => 'Acción no válida.'];

// Asegurarse de que se está recibiendo un POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    $action = $_POST['action'];

    // --- LÓGICA DE REGISTRO ---
    if ($action === 'register') {
        if (!empty($_POST['email']) && !empty($_POST['username']) && !empty($_POST['password'])) {
            
            $email = $_POST['email'];
            $username = $_POST['username'];
            $password = $_POST['password'];

            // 1. Validar formato de email (básico)
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $response['message'] = 'El formato de correo no es válido.';
            } else {
                try {
                    // 2. Comprobar si el email o usuario ya existen
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
                    $stmt->execute([$email, $username]);
                    
                    if ($stmt->fetch()) {
                        $response['message'] = 'El correo o nombre de usuario ya están en uso.';
                    } else {
                        
                        // 3. Hashear la contraseña
                        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

                        // 4. Insertar nuevo usuario (SIN la URL del avatar todavía)
                        $stmt = $pdo->prepare("INSERT INTO users (email, username, password) VALUES (?, ?, ?)");
                        $stmt->execute([$email, $username, $hashedPassword]);

                        // 5. Obtener el ID del usuario recién creado
                        $userId = $pdo->lastInsertId();

                        // --- MODIFICACIÓN: GENERAR Y GUARDAR AVATAR LOCALMENTE ---
                        
                        $localAvatarUrl = null;
                        
                        try {
                            // Definir la ruta de guardado (física, en el servidor)
                            // __DIR__ es el directorio de este archivo (/ProjectGenesis)
                            $savePathDir = __DIR__ . '/assets/uploads/avatars';
                            $fileName = "user-{$userId}.png";
                            $fullSavePath = $savePathDir . '/' . $fileName;

                            // Definir la URL de acceso (pública, para la web)
                            // $basePath viene de config.php
                            $publicUrl = $basePath . '/assets/uploads/avatars/' . $fileName;

                            // 1. Crear el directorio de guardado si no existe
                            if (!is_dir($savePathDir)) {
                                // 0755 da permisos de escritura, recursivo = true
                                mkdir($savePathDir, 0755, true); 
                            }

                            // --- MODIFICACIÓN: PALETA DE COLORES PERSONALIZADA ---
                        
                            // 1. Definir la paleta de colores (sin el #)
                            // Colores con una tonalidad similar al azul que mencionaste.
                            $avatarColors = [
                                '206BD3', // Azul (El que pediste)
                                'D32029', // Rojo
                                '28A745', // Verde
                                'E91E63', // Rosa
                                'F57C00'  // Naranja
                            ];
                            
                            // 2. Elegir un color al azar
                            $randomColorKey = array_rand($avatarColors);
                            $selectedColor = $avatarColors[$randomColorKey];

                            // 3. Generar la URL de la API (usando el color seleccionado)
                            $nameParam = urlencode($username);
                            // --- MODIFICACIÓN: Aumentar tamaño a 256 ---
                            $apiUrl = "https://ui-avatars.com/api/?name={$nameParam}&size=256&background={$selectedColor}&color=ffffff&bold=true";
                            
                            // --- FIN DE LA MODIFICACIÓN ---

                            // 4. Obtener el contenido (los bytes) de la imagen
                            $imageData = file_get_contents($apiUrl);

                            // 5. Guardar la imagen localmente
                            if ($imageData !== false) {
                                file_put_contents($fullSavePath, $imageData);
                                $localAvatarUrl = $publicUrl; // ¡Éxito!
                            }

                        } catch (Exception $e) {
                            // Si algo falla (API caída, permisos), no rompemos el registro
                            // $localAvatarUrl se quedará como null
                            // (Aquí podrías loggear el error: error_log($e->getMessage());)
                        }

                        // 6. Actualizar al usuario con la nueva URL local (si se generó)
                        if ($localAvatarUrl) {
                            $stmt = $pdo->prepare("UPDATE users SET profile_image_url = ? WHERE id = ?");
                            $stmt->execute([$localAvatarUrl, $userId]);
                        }
                        
                        // --- FIN DE LA MODIFICACIÓN (GENERAR AVATAR) ---

                        
                        // --- MODIFICACIÓN: INICIAR SESIÓN AUTOMÁTICAMENTE ---
                        $_SESSION['user_id'] = $userId;
                        $_SESSION['username'] = $username;
                        $_SESSION['email'] = $email;
                        $_SESSION['profile_image_url'] = $localAvatarUrl; // Usar la URL que acabamos de generar

                        $response['success'] = true;
                        $response['message'] = '¡Registro completado! Iniciando sesión...';
                        // --- FIN DE LA MODIFICACIÓN ---
                    }
                } catch (PDOException $e) {
                    $response['message'] = 'Error en la base de datos: ' . $e->getMessage();
                }
            }
        } else {
            $response['message'] = 'Por favor, completa todos los campos requeridos.';
        }
    }

    // --- LÓGICA DE LOGIN ---
    elseif ($action === 'login') {
        if (!empty($_POST['email']) && !empty($_POST['password'])) {
            
            $email = $_POST['email'];
            $password = $_POST['password'];

            try {
                // 1. Buscar al usuario por email
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                // 2. Verificar si el usuario existe y la contraseña es correcta
                if ($user && password_verify($password, $user['password'])) {
                    
                    // 3. ¡Éxito! Establecer variables de sesión
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['profile_image_url'] = $user['profile_image_url'];

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

// Enviar la respuesta JSON final
echo json_encode($response);
exit;
?>
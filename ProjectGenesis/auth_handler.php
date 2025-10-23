<?php
// /ProjectGenesis/auth_handler.php

// Incluir la configuración de sesión y BD
include 'config.php';

// Establecer la cabecera de respuesta como JSON
header('Content-Type: application/json');

// Preparar el array de respuesta
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

                        // 4. Insertar nuevo usuario
                        $stmt = $pdo->prepare("INSERT INTO users (email, username, password) VALUES (?, ?, ?)");
                        $stmt->execute([$email, $username, $hashedPassword]);

                        $response['success'] = true;
                        $response['message'] = '¡Registro completado! Ahora puedes iniciar sesión.';
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
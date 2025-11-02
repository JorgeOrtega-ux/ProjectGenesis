<?php
// FILE: config/bootstrapper.php

// Carga la configuración base (BD, sesiones, etc.)
include 'config/config.php';

// Asegura que el token CSRF exista
getCsrfToken(); 

// Refresca los datos del usuario desde la BD en cada carga de página
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT username, email, profile_image_url, role, auth_token, account_status FROM users WHERE id = ?");
        
        $stmt->execute([$_SESSION['user_id']]);
        $freshUserData = $stmt->fetch();

        if ($freshUserData) {
            
            // Comprobar si la cuenta está suspendida o eliminada
            $accountStatus = $freshUserData['account_status'];
            if ($accountStatus === 'suspended' || $accountStatus === 'deleted') {
                session_unset();
                session_destroy();
                
                $statusPath = ($accountStatus === 'suspended') ? '/account-status/suspended' : '/account-status/deleted';
                header('Location: ' . $basePath . $statusPath);
                exit;
            }

            // Validar el token de autenticación (para "Cerrar sesión en todos los dispositivos")
            $dbAuthToken = $freshUserData['auth_token'];
            $sessionAuthToken = $_SESSION['auth_token'] ?? null;

            if (empty($sessionAuthToken) || empty($dbAuthToken) || !hash_equals($dbAuthToken, $sessionAuthToken)) {
                session_unset();
                session_destroy();
                header('Location: ' . $basePath . '/login');
                exit;
            }

            // Refrescar los datos principales de la sesión
            $_SESSION['username'] = $freshUserData['username'];
            $_SESSION['email'] = $freshUserData['email'];
            $_SESSION['profile_image_url'] = $freshUserData['profile_image_url'];
            $_SESSION['role'] = $freshUserData['role']; 
            
            
            // Refrescar las preferencias del usuario
            $stmt_prefs = $pdo->prepare("SELECT * FROM user_preferences WHERE user_id = ?");
            $stmt_prefs->execute([$_SESSION['user_id']]);
            $prefs = $stmt_prefs->fetch();

            if ($prefs) {
                $_SESSION['language'] = $prefs['language'];
                $_SESSION['theme'] = $prefs['theme'];
                $_SESSION['usage_type'] = $prefs['usage_type'];
                $_SESSION['open_links_in_new_tab'] = (int)$prefs['open_links_in_new_tab'];
                $_SESSION['increase_message_duration'] = (int)$prefs['increase_message_duration'];
            } else {
                // Valores por defecto si no hay preferencias
                $_SESSION['language'] = 'en-us';
                $_SESSION['theme'] = 'system';
                $_SESSION['usage_type'] = 'personal';
                $_SESSION['open_links_in_new_tab'] = 1;
                $_SESSION['increase_message_duration'] = 0;
            }

        } else {
            // Si el ID de usuario en sesión no existe en la BD, destruir sesión
            session_unset();
            session_destroy();
            header('Location: ' . $basePath . '/login');
            exit;
        }
    } catch (PDOException $e) {
        // Log del error pero no detener la app (config.php podría mostrar error de BD)
        logDatabaseError($e, 'bootstrapper - refresh session'); 
    }
}
?>
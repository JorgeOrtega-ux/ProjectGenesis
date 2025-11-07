<?php
// FILE: config/routing/router.php
// (CÓDIGO MODIFICADO CON RUTAS CORREGIDAS)

// --- ▼▼▼ CAMBIO DE RUTA (Línea 5) ▼▼▼ ---
include '../config.php'; 
// --- ▲▲▲ FIN DE CAMBIO ▲▲▲ ---

// --- ▼▼▼ INICIO DE LA MODIFICACIÓN ▼▼▼ ---
// El bloque que comprobaba si ($pdo === null) se ha eliminado.
// Si config.php falla, la ejecución se detendrá antes de llegar aquí.
// --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---


function showRegistrationError($basePath, $messageKey, $detailsKey) {
    if (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    http_response_code(400);

    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded">';
    echo '<link rel="stylesheet" type="text/css" href="' . htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') . '/assets/css/styles.css">';
    
    echo '<title data-i18n="page.error.title">Error en el registro</title></head>';
    
    echo '<body style="background-color: #f5f5fa;">'; 
    
    echo '<div class="section-content active" style="align-items: center; justify-content: center; height: 100vh;">';
    echo '<div class="auth-container" style="max-width: 460px;">';
    
    echo '<h1 class="auth-title" style="font-size: 36px; margin-bottom: 16px;" data-i18n="page.error.oopsTitle">¡Uy! Faltan datos.</h1>';
    
    echo '<div class="auth-error-message" style="display: block; background-color: #ffffff; border: 1px solid #00000020; color: #1f2937; margin-bottom: 24px; text-align: left; padding: 16px;">';
    echo '<strong style="display: block; font-size: 16px; margin-bottom: 8px; color: #000;" data-i18n="' . htmlspecialchars($messageKey, ENT_QUOTES, 'UTF-8') . '"></strong>';
    echo '<p style="font-size: 14px; margin: 0; color: #6b7280; line-height: 1.5;" data-i18n="' . htmlspecialchars($detailsKey, ENT_QUOTES, 'UTF-8') . '"></p>';
    echo '</div>';
    
    echo '<a href="' . htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') . '/register" class="auth-button" style="text-decoration: none; text-align: center; line-height: 52px; display: block; width: 100%;" data-i18n="page.error.backToRegister">Volver al inicio del registro</a>';
    
    echo '</div></div>';
    echo '</body></html>';
    
}


function showResetError($basePath, $messageKey, $detailsKey) {
    if (ob_get_level() > 0) ob_end_clean();
    http_response_code(400);

    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=deVice-width, initial-scale=1.0">';
    echo '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded">';
    echo '<link rel="stylesheet" type="text/css" href="' . htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') . '/assets/css/styles.css">';
    echo '<title data-i18n="page.error.titleReset">Error en la recuperación</title></head>';
    echo '<body style="background-color: #f5f5fa;">';
    echo '<div class="section-content active" style="align-items: center; justify-content: center; height: 100vh;">';
    echo '<div class="auth-container" style="max-width: 460px;">';
    echo '<h1 class="auth-title" style="font-size: 36px; margin-bottom: 16px;" data-i18n="page.error.oopsTitle">¡Uy! Faltan datos.</h1>';
    echo '<div class="auth-error-message" style="display: block; background-color: #ffffff; border: 1px solid #00000020; color: #1f2937; margin-bottom: 24px; text-align: left; padding: 16px;">';
    echo '<strong style="display: block; font-size: 16px; margin-bottom: 8px; color: #000;" data-i18n="' . htmlspecialchars($messageKey, ENT_QUOTES, 'UTF-8') . '"></strong>';
    echo '<p style="font-size: 14px; margin: 0; color: #6b7280; line-height: 1.5;" data-i18n="' . htmlspecialchars($detailsKey, ENT_QUOTES, 'UTF-8') . '"></p>';
    echo '</div>';
    echo '<a href="' . htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') . '/reset-password" class="auth-button" style="text-decoration: none; text-align: center; line-height: 52px; display: block; width: 100%;" data-i18n="page.error.backToReset">Volver al inicio de la recuperación</a>';
    echo '</div></div>';
    echo '</body></html>';
}

// --- ▼▼▼ INICIO DE FUNCIONES HELPER (COPIADAS DE MANAGE-BACKUPS.PHP) ▼▼▼ ---
/**
 * Formatea bytes a un tamaño legible (KB, MB, GB)
 */
function formatBackupSize($bytes) {
    if ($bytes < 1024) return $bytes . ' B';
    $kb = $bytes / 1024;
    if ($kb < 1024) return round($kb, 2) . ' KB';
    $mb = $kb / 1024;
    if ($mb < 1024) return round($mb, 2) . ' MB';
    $gb = $mb / 1024;
    return round($gb, 2) . ' GB';
}
/**
 * Formatea un timestamp a fecha legible
 */
function formatBackupDate($timestamp) {
     return date('d/m/Y H:i:s', $timestamp);
}
// --- ▲▲▲ FIN DE FUNCIONES HELPER ▲▲▲ ---


$page = $_GET['page'] ?? 'home';

$CURRENT_SECTION = $page; 

// --- ▼▼▼ CAMBIO DE RUTA (TODOS LOS ../includes A ../../includes) ▼▼▼ ---
$allowedPages = [
    'home'     => '../../includes/sections/main/home.php',
    'explorer' => '../../includes/sections/main/explorer.php',
    'login'    => '../../includes/sections/auth/login.php',
    '404'      => '../../includes/sections/main/404.php', 
    'db-error' => '../../includes/sections/main/db-error.php', 
    
    // --- ▼▼▼ INICIO DE LA MODIFICACIÓN ▼▼▼ ---
    // Las siguientes 4 rutas ahora apuntan al NUEVO archivo unificado
    'maintenance' => '../../includes/sections/main/status-page.php',
    'server-full' => '../../includes/sections/main/status-page.php',
    'account-status-deleted'   => '../../includes/sections/main/status-page.php',
    'account-status-suspended' => '../../includes/sections/main/status-page.php',
    // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---

    // --- ▼▼▼ INICIO DE LÍNEA AÑADIDA ▼▼▼ ---
    'join-group' => '../../includes/sections/main/join-group.php',
    'create-publication' => '../../includes/sections/main/create-publication.php', // <-- NUEVA
    'create-poll' => '../../includes/sections/main/create-publication.php', // <-- NUEVA (apunta al mismo archivo)
    // --- ▲▲▲ FIN DE LÍNEA AÑADIDA ▲▲▲ ---

    'register-step1' => '../../includes/sections/auth/register.php',
    'register-step2' => '../../includes/sections/auth/register.php',
    'register-step3' => '../../includes/sections/auth/register.php',

    'reset-step1' => '../../includes/sections/auth/reset-password.php',
    'reset-step2' => '../../includes/sections/auth/reset-password.php',
    'reset-step3' => '../../includes/sections/auth/reset-password.php',

    'settings-profile'       => '../../includes/sections/settings/your-profile.php',
    'settings-login'         => '../../includes/sections/settings/login-security.php',
    'settings-accessibility' => '../../includes/sections/settings/accessibility.php',
    'settings-devices'       => '../../includes/sections/settings/device-sessions.php', 
    
    'settings-change-password' => '../../includes/sections/settings/actions/change-password.php',
    'settings-change-email'    => '../../includes/sections/settings/actions/change-email.php',
    'settings-toggle-2fa'      => '../../includes/sections/settings/actions/toggle-2fa.php',
    'settings-delete-account'  => '../../includes/sections/settings/actions/delete-account.php',
    
    // --- ▼▼▼ PÁGINAS DE ADMIN MODIFICADAS ▼▼▼ ---
    'admin-dashboard'          => '../../includes/sections/admin/dashboard.php',
    'admin-manage-users'       => '../../includes/sections/admin/manage-users.php', // <--- CLAVE MODIFICADA
    'admin-create-user'        => '../../includes/sections/admin/create-user.php', // <--- ¡NUEVA LÍNEA!
    'admin-edit-user'          => '../../includes/sections/admin/admin-edit-user.php', // <--- ¡NUEVA LÍNEA!
    'admin-server-settings'    => '../../includes/sections/admin/server-settings.php', // <--- ¡NUEVA LÍNEA!
    
    // --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
    'admin-manage-backups'     => '../../includes/sections/admin/manage-backups.php',
    // 'admin-restore-backup'     => '../../includes/sections/admin/restore-backup.php', // <-- ¡ELIMINADA!
    'admin-manage-logs'        => '../../includes/sections/admin/manage-logs.php', // <-- ¡NUEVA LÍNEA AÑADIDA!
    // --- ▲▲▲ FIN DE MODIFICACIÓN ▼▼▼ ---

    // --- ▲▲▲ FIN DE PÁGINAS DE ADMIN ▲▲▲ ---
];
// --- ▲▲▲ FIN DE CAMBIO DE RUTA ▲▲▲ ---

// --- ▼▼▼ INICIO DE MODIFICACIÓN (MODO MANTENIMIENTO) ▼▼▼ ---
$authPages = ['login', 'maintenance', 'server-full']; // 'maintenance' y 'server-full' se tratan como páginas de auth
// --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

$isAuthPage = in_array($page, $authPages) || 
              strpos($page, 'register-') === 0 ||
              strpos($page, 'reset-') === 0 ||
              strpos($page, 'account-status-') === 0; 

$isSettingsPage = strpos($page, 'settings-') === 0;
// --- ▼▼▼ NUEVA LÍNEA ▼▼▼ ---
$isAdminPage = strpos($page, 'admin-') === 0;

// --- ▼▼▼ BLOQUE ELIMINADO ▼▼▼ ---
// La variable $accountStatusType ya no es necesaria aquí,
// porque el nuevo 'status-page.php' usa $CURRENT_SECTION.
// --- ▲▲▲ FIN BLOQUE ELIMINADO ▲▲▲ ---

if (!isset($_SESSION['user_id']) && !$isAuthPage) { // <-- Modificación: NO permitir join-group sin auth
    http_response_code(403); 
    $CURRENT_SECTION = '404'; 
    include $allowedPages['404'];
    exit; 
}

// --- ▼▼▼ ¡NUEVO BLOQUE DE SEGURIDAD PARA ADMIN! ▼▼▼ ---
// --- ▼▼▼ ¡NUEVO BLOQUE DE SEGURIDAD PARA ADMIN! ▼▼▼ ---
if ($isAdminPage && isset($_SESSION['user_id'])) {
    $userRole = $_SESSION['role'] ?? 'user';
    if ($userRole !== 'administrator' && $userRole !== 'founder') {
        // Si no es admin o founder, no se le niega el acceso con 403,
        // simplemente se le cambia la página a '404'.
        $page = '404';
        $CURRENT_SECTION = '404';
        // ¡Ya no hay http_response_code(403) ni exit!
    }
}
// --- ▲▲▲ FIN DEL BLOQUE DE SEGURIDAD ▲▲▲ ---
// --- ▲▲▲ FIN DEL BLOQUE DE SEGURIDAD ▲▲▲ ---


if (array_key_exists($page, $allowedPages)) {

    $CURRENT_REGISTER_STEP = 1; 
    $initialCooldown = 0; 

    if ($page === 'register-step1') {
        $CURRENT_REGISTER_STEP = 1;
        unset($_SESSION['registration_step']);
        unset($_SESSION['registration_email']); 
        echo '<script>sessionStorage.removeItem("regEmail"); sessionStorage.removeItem("regPass");</script>';

    } elseif ($page === 'register-step2') {
        if (!isset($_SESSION['registration_step']) || $_SESSION['registration_step'] < 2) {
            showRegistrationError($basePath, 'page.error.400title', 'page.error.regStep1');
            exit; 
        }
        $CURRENT_REGISTER_STEP = 2;

    } elseif ($page === 'register-step3') {
        if (!isset($_SESSION['registration_step']) || $_SESSION['registration_step'] < 3) {
            showRegistrationError($basePath, 'page.error.400title', 'page.error.regStep2');
            exit;
        }
        $CURRENT_REGISTER_STEP = 3;

        if (isset($_SESSION['registration_email'])) {
            try {
                $email = $_SESSION['registration_email'];
                $stmt = $pdo->prepare("SELECT created_at FROM verification_codes WHERE identifier = ? AND code_type = 'registration' ORDER BY created_at DESC LIMIT 1");
                $stmt->execute([$email]);
                $codeData = $stmt->fetch();

                if ($codeData) {
                    $lastCodeTime = new DateTime($codeData['created_at'], new DateTimeZone('UTC'));
                    $currentTime = new DateTime('now', new DateTimeZone('UTC'));
                    $secondsPassed = $currentTime->getTimestamp() - $lastCodeTime->getTimestamp();
                    $cooldownConstant = (int)($GLOBALS['site_settings']['code_resend_cooldown_seconds'] ?? 60); 

                    if ($secondsPassed < $cooldownConstant) {
                        $initialCooldown = $cooldownConstant - $secondsPassed;
                    }
                }
            } catch (PDOException $e) {
                logDatabaseError($e, 'router - register-step3-cooldown');
                $initialCooldown = 0; 
            }
        }
    }
    
    $CURRENT_RESET_STEP = 1; 

    if ($page === 'reset-step1') {
        $CURRENT_RESET_STEP = 1;
        unset($_SESSION['reset_step']);
        unset($_SESSION['reset_email']); 
        echo '<script>sessionStorage.removeItem("resetEmail"); sessionStorage.removeItem("resetCode");</script>';

    } elseif ($page === 'reset-step2') {
        if (!isset($_SESSION['reset_step']) || $_SESSION['reset_step'] < 2) {
            showResetError($basePath, 'page.error.400title', 'page.error.resetStep1');
            exit;
        }
        $CURRENT_RESET_STEP = 2;

        if (isset($_SESSION['reset_email'])) {
            try {
                $email = $_SESSION['reset_email'];
                $stmt = $pdo->prepare("SELECT created_at FROM verification_codes WHERE identifier = ? AND code_type = 'password_reset' ORDER BY created_at DESC LIMIT 1");
                $stmt->execute([$email]);
                $codeData = $stmt->fetch();

                if ($codeData) {
                    $lastCodeTime = new DateTime($codeData['created_at'], new DateTimeZone('UTC'));
                    $currentTime = new DateTime('now', new DateTimeZone('UTC'));
                    $secondsPassed = $currentTime->getTimestamp() - $lastCodeTime->getTimestamp();
                    $cooldownConstant = (int)($GLOBALS['site_settings']['code_resend_cooldown_seconds'] ?? 60);

                    if ($secondsPassed < $cooldownConstant) {
                        $initialCooldown = $cooldownConstant - $secondsPassed;
                    }
                }
            } catch (PDOException $e) {
                logDatabaseError($e, 'router - reset-step2-cooldown');
                $initialCooldown = 0; 
            }
        }

    } elseif ($page === 'reset-step3') {
        if (!isset($_SESSION['reset_step']) || $_SESSION['reset_step'] < 3) {
            showResetError($basePath, 'page.error.400title', 'page.error.resetStep2');
            exit;
        }
        $CURRENT_RESET_STEP = 3;
    }


    if ($page === 'settings-profile') {
        $defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";
        $profileImageUrl = $_SESSION['profile_image_url'] ?? $defaultAvatar;
        if (empty($profileImageUrl)) $profileImageUrl = $defaultAvatar;
        $isDefaultAvatar = strpos($profileImageUrl, 'ui-avatars.com') !== false || strpos($profileImageUrl, 'user-' . $_SESSION['user_id'] . '.png') !== false;
        $usernameForAlt = $_SESSION['username'] ?? 'Usuario';
        $userRole = $_SESSION['role'] ?? 'user';
        $userEmail = $_SESSION['email'] ?? 'correo@ejemplo.com';
        
        $userLanguage = $_SESSION['language'] ?? 'en-us';
        $userUsageType = $_SESSION['usage_type'] ?? 'personal';
        $openLinksInNewTab = (int)($_SESSION['open_links_in_new_tab'] ?? 1); 

    } elseif ($page === 'settings-login') {
        try {
            $stmt_user = $pdo->prepare("SELECT is_2fa_enabled, created_at FROM users WHERE id = ?");
            $stmt_user->execute([$_SESSION['user_id']]);
            $userData = $stmt_user->fetch();
            $is2faEnabled = $userData ? (int)$userData['is_2fa_enabled'] : 0;
            $accountCreatedDate = $userData ? $userData['created_at'] : null;

            $stmt_pass_log = $pdo->prepare("SELECT changed_at FROM user_audit_logs WHERE user_id = ? AND change_type = 'password' ORDER BY changed_at DESC LIMIT 1");
            $stmt_pass_log->execute([$_SESSION['user_id']]);
            $lastLog = $stmt_pass_log->fetch();

            if ($lastLog) {
                $lastPasswordUpdateText = 'Última actualización: ' . (new DateTime($lastLog['changed_at']))->format('d/m/Y H:i');
            } else {
                $lastPasswordUpdateText = 'settings.login.lastPassUpdateNever'; 
            }

            $accountCreationDateText = '';
            if ($accountCreatedDate) {
                 $accountCreationDateText = 'Cuenta creada el ' . (new DateTime($accountCreatedDate))->format('d/m/Y');
            }
            $deleteAccountDescText = 'settings.login.deleteAccountDesc'; 

        } catch (PDOException $e) {
            logDatabaseError($e, 'router - settings-login');
            $is2faEnabled = 0;
            $lastPasswordUpdateText = 'settings.login.lastPassUpdateError'; 
            $deleteAccountDescText = 'settings.login.deleteAccountDesc';
            $accountCreationDateText = '';
        }

    } elseif ($page === 'settings-accessibility') {
        $userTheme = $_SESSION['theme'] ?? 'system';
        $increaseMessageDuration = (int)($_SESSION['increase_message_duration'] ?? 0);
    
    } elseif ($page === 'settings-change-email') {
         $userEmail = $_SESSION['email'] ?? 'correo@ejemplo.com';
         $initialEmailCooldown = 0;
         $cooldownConstant = (int)($GLOBALS['site_settings']['code_resend_cooldown_seconds'] ?? 60);
         $identifier = $_SESSION['user_id'];
         $codeType = 'email_change';

         try {
            $stmt = $pdo->prepare("SELECT created_at FROM verification_codes WHERE identifier = ? AND code_type = ? ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([$identifier, $codeType]);
            $codeData = $stmt->fetch();

            $secondsPassed = -1;

            if ($codeData) {
                $lastCodeTime = new DateTime($codeData['created_at'], new DateTimeZone('UTC'));
                $currentTime = new DateTime('now', new DateTimeZone('UTC'));
                $secondsPassed = $currentTime->getTimestamp() - $lastCodeTime->getTimestamp();
            }

            if (!$codeData || $secondsPassed === -1 || $secondsPassed >= $cooldownConstant) {
                $stmt_delete = $pdo->prepare("DELETE FROM verification_codes WHERE identifier = ? AND code_type = ?");
                $stmt_delete->execute([$identifier, $codeType]);

                $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                $code = '';
                $max = strlen($chars) - 1;
                for ($i = 0; $i < 12; $i++) { $code .= $chars[random_int(0, $max)]; }
                $verificationCode = substr($code, 0, 4) . '-' . substr($code, 4, 4) . '-' . substr($code, 8, 4);
                $verificationCode = str_replace('-', '', $verificationCode); 

                $stmt_insert = $pdo->prepare(
                    "INSERT INTO verification_codes (identifier, code_type, code) 
                     VALUES (?, ?, ?)"
                );
                $stmt_insert->execute([$identifier, $codeType, $verificationCode]);

                $initialEmailCooldown = $cooldownConstant;

            } else {
                $initialEmailCooldown = $cooldownConstant - $secondsPassed;
            }

        } catch (PDOException $e) {
            logDatabaseError($e, 'router - settings-change-email-cooldown');
            $initialEmailCooldown = 0; 
        }

    } elseif ($page === 'settings-toggle-2fa') {
         try {
            $stmt_2fa = $pdo->prepare("SELECT is_2fa_enabled FROM users WHERE id = ?");
            $stmt_2fa->execute([$_SESSION['user_id']]);
            $is2faEnabled = (int)$stmt_2fa->fetchColumn(); 
         } catch (PDOException $e) {
             logDatabaseError($e, 'router - settings-toggle-2fa');
             $is2faEnabled = 0;
         }
    } elseif ($page === 'settings-delete-account') {
         $userEmail = $_SESSION['email'] ?? 'correo@ejemplo.com';
         $defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";
         $profileImageUrl = $_SESSION['profile_image_url'] ?? $defaultAvatar;
         if (empty($profileImageUrl)) $profileImageUrl = $defaultAvatar;
    
    // --- ▼▼▼ INICIO DE BLOQUE AÑADIDO (join-group) ▼▼▼ ---
    } elseif ($page === 'join-group') {
        try {
            $stmt_public = $pdo->prepare("SELECT id, name FROM communities WHERE privacy = 'public' ORDER BY name ASC");
            $stmt_public->execute();
            $publicCommunities = $stmt_public->fetchAll();
            
            $stmt_joined = $pdo->prepare("SELECT community_id FROM user_communities WHERE user_id = ?");
            $stmt_joined->execute([$_SESSION['user_id']]);
            $joinedCommunityIds = $stmt_joined->fetchAll(PDO::FETCH_COLUMN);
            
        } catch (PDOException $e) {
            logDatabaseError($e, 'router - join-group');
            $publicCommunities = [];
            $joinedCommunityIds = [];
        }
    // --- ▲▲▲ FIN DE BLOQUE AÑADIDO ▲▲▲ ---

    } elseif ($page === 'admin-manage-users') {
        $adminCurrentPage = (int)($_GET['p'] ?? 1);
        if ($adminCurrentPage < 1) $adminCurrentPage = 1;
    }
    elseif ($page === 'admin-edit-user') {
        $targetUserId = (int)($_GET['id'] ?? 0);
        if ($targetUserId === 0) {
            $page = '404';
            $CURRENT_SECTION = '404';
        } else {
            try {
                $stmt_user = $pdo->prepare("SELECT id, username, email, password, profile_image_url, role FROM users WHERE id = ?");
                $stmt_user->execute([$targetUserId]);
                $editUser = $stmt_user->fetch();

                if (!$editUser) {
                    $page = '404';
                    $CURRENT_SECTION = '404';
                }

                $adminRole = $_SESSION['role'] ?? 'user';
                if ($editUser['role'] === 'founder' && $adminRole !== 'founder') {
                    $page = '404'; 
                    $CURRENT_SECTION = '404';
                }
                
                $editUser['password_hash'] = $editUser['password'];
                unset($editUser['password']);
                
                $defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";
                $profileImageUrl = $editUser['profile_image_url'] ?? $defaultAvatar;
                if (empty($profileImageUrl)) $profileImageUrl = $defaultAvatar;
                $isDefaultAvatar = strpos($profileImageUrl, 'ui-avatars.com') !== false || strpos($profileImageUrl, 'user-' . $editUser['id'] . '.png') !== false;

            } catch (PDOException $e) {
                logDatabaseError($e, 'router - admin-edit-user');
                $page = '404';
                $CURRENT_SECTION = '404';
            }
        }
    }
    elseif ($page === 'admin-server-settings') {
        $maintenanceModeStatus = $GLOBALS['site_settings']['maintenance_mode'] ?? '0';
        $allowRegistrationStatus = $GLOBALS['site_settings']['allow_new_registrations'] ?? '1';
    }
    elseif ($isAdminPage) {
    }
    
    
    include $allowedPages[$page];

} else {
    http_response_code(404);
    $CURRENT_SECTION = '404'; 
    include $allowedPages['404']; 
}
?>
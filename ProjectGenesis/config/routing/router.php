<?php
// FILE: config/routing/router.php
// (CÓDIGO MODIFICADO CON RUTAS CORREGIDAS Y LÓGICA DE GRUPO)

// --- ▼▼▼ CAMBIO DE RUTA (Línea 5) ▼▼▼ ---
include '../config.php'; 
// --- ▲▲▲ FIN DE CAMBIO ▲▲▲ ---

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

function formatBackupSize($bytes) {
    if ($bytes < 1024) return $bytes . ' B';
    $kb = $bytes / 1024;
    if ($kb < 1024) return round($kb, 2) . ' KB';
    $mb = $kb / 1024;
    if ($mb < 1024) return round($mb, 2) . ' MB';
    $gb = $mb / 1024;
    return round($gb, 2) . ' GB';
}
function formatBackupDate($timestamp) {
     return date('d/m/Y H:i:s', $timestamp);
}


$page = $_GET['page'] ?? 'home';

$CURRENT_SECTION = $page; 

$allowedPages = [
    'home'     => '../../includes/sections/main/home.php',
    'explorer' => '../../includes/sections/main/explorer.php',
    'join-group' => '../../includes/sections/main/join-group.php',
    'my-groups'  => '../../includes/sections/main/my-groups.php',
    'login'    => '../../includes/sections/auth/login.php',
    '404'      => '../../includes/sections/main/404.php', 
    'db-error' => '../../includes/sections/main/db-error.php', 
    
    'maintenance' => '../../includes/sections/main/status-page.php',
    'server-full' => '../../includes/sections/main/status-page.php',
    'account-status-deleted'   => '../../includes/sections/main/status-page.php',
    'account-status-suspended' => '../../includes/sections/main/status-page.php',

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
    
    'admin-dashboard'          => '../../includes/sections/admin/dashboard.php',
    'admin-manage-users'       => '../../includes/sections/admin/manage-users.php',
    'admin-create-user'        => '../../includes/sections/admin/create-user.php',
    'admin-edit-user'          => '../../includes/sections/admin/admin-edit-user.php',
    'admin-server-settings'    => '../../includes/sections/admin/server-settings.php',
    
    'admin-manage-backups'     => '../../includes/sections/admin/manage-backups.php',
    'admin-manage-logs'        => '../../includes/sections/admin/manage-logs.php',
    'admin-manage-groups'      => '../../includes/sections/admin/manage-groups.php',
    
    // --- ▼▼▼ INICIO DE NUEVA LÍNEA ▼▼▼ ---
    'admin-edit-group'         => '../../includes/sections/admin/admin-edit-group.php',
    // --- ▲▲▲ FIN DE NUEVA LÍNEA ▲▲▲ ---

    'help-legal-notice'      => '../../includes/sections/help/legal-notice.php',
    'help-privacy-policy'    => '../../includes/sections/help/privacy-policy.php',
    'help-cookies-policy'    => '../../includes/sections/help/cookies-policy.php',
    'help-terms-conditions'  => '../../includes/sections/help/terms-conditions.php',
    'help-send-feedback'     => '../../includes/sections/help/send-feedback.php',
];

$authPages = [
    'login', 
    'maintenance', 
    'server-full',
    'help-legal-notice',
    'help-privacy-policy',
    'help-cookies-policy',
    'help-terms-conditions'
];

$isAuthPage = in_array($page, $authPages) || 
              strpos($page, 'register-') === 0 ||
              strpos($page, 'reset-') === 0 ||
              strpos($page, 'account-status-') === 0; 

$isSettingsPage = strpos($page, 'settings-') === 0;
$isAdminPage = strpos($page, 'admin-') === 0;
$isHelpPage = strpos($page, 'help-') === 0;

if (!isset($_SESSION['user_id']) && !$isAuthPage && $page !== '404') {
    http_response_code(403); 
    $CURRENT_SECTION = '404'; 
    include $allowedPages['404'];
    exit; 
}

if ($isAdminPage && isset($_SESSION['user_id'])) {
    $userRole = $_SESSION['role'] ?? 'user';
    if ($userRole !== 'administrator' && $userRole !== 'founder') {
        $page = '404';
        $CURRENT_SECTION = '404';
    }
}


if (array_key_exists($page, $allowedPages)) {

    $CURRENT_REGISTER_STEP = 1; 
    $initialCooldown = 0; 

    if ($page === 'register-step1') {
        // ... (lógica de register-step1 sin cambios)
        $CURRENT_REGISTER_STEP = 1;
        unset($_SESSION['registration_step']);
        unset($_SESSION['registration_email']); 
        echo '<script>sessionStorage.removeItem("regEmail"); sessionStorage.removeItem("regPass");</script>';

    } elseif ($page === 'register-step2') {
        // ... (lógica de register-step2 sin cambios)
        if (!isset($_SESSION['registration_step']) || $_SESSION['registration_step'] < 2) {
            showRegistrationError($basePath, 'page.error.400title', 'page.error.regStep1');
            exit; 
        }
        $CURRENT_REGISTER_STEP = 2;

    } elseif ($page === 'register-step3') {
        // ... (lógica de register-step3 sin cambios)
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
        // ... (lógica de reset-step1 sin cambios)
        $CURRENT_RESET_STEP = 1;
        unset($_SESSION['reset_step']);
        unset($_SESSION['reset_email']); 
        echo '<script>sessionStorage.removeItem("resetEmail"); sessionStorage.removeItem("resetCode");</script>';

    } elseif ($page === 'reset-step2') {
        // ... (lógica de reset-step2 sin cambios)
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
        // ... (lógica de reset-step3 sin cambios)
        if (!isset($_SESSION['reset_step']) || $_SESSION['reset_step'] < 3) {
            showResetError($basePath, 'page.error.400title', 'page.error.resetStep2');
            exit;
        }
        $CURRENT_RESET_STEP = 3;
    }


    $currentGroupUuid = null;
    $current_group_info = null;
    
    if ($page === 'home' && isset($_GET['uuid'])) {
        // ... (lógica de home/uuid sin cambios)
        if (preg_match('/^[a-f0-9\-]{36}$/i', $_GET['uuid'])) {
            $currentGroupUuid = $_GET['uuid'];
            
            if (isset($_SESSION['user_id'], $pdo)) {
                 try {
                    $stmt_current = $pdo->prepare(
                        "SELECT g.id, g.name, g.uuid
                         FROM groups g
                         JOIN user_groups ug ON g.id = ug.group_id
                         WHERE g.uuid = ? AND ug.user_id = ?
                         LIMIT 1"
                    );
                    $stmt_current->execute([$currentGroupUuid, $_SESSION['user_id']]);
                    $current_group_info = $stmt_current->fetch();
                } catch (PDOException $e) {
                    logDatabaseError($e, 'router.php - load home group from uuid');
                    $current_group_info = null;
                }
            }
        }
    }


    if ($page === 'settings-profile') {
        // ... (lógica de settings-profile sin cambios)
        $defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";
        $profileImageUrl = $_SESSION['profile_image_url'] ?? $defaultAvatar;
        if (empty($profileImageUrl)) $profileImageUrl = $defaultAvatar;
        $isDefaultAvatar = strpos($profileImageUrl, '/assets/uploads/avatars_uploaded/') === false;
        $usernameForAlt = $_SESSION['username'] ?? 'Usuario';
        $userRole = $_SESSION['role'] ?? 'user';
        $userEmail = $_SESSION['email'] ?? 'correo@ejemplo.com';
        
        $userLanguage = $_SESSION['language'] ?? 'en-us';
        $userUsageType = $_SESSION['usage_type'] ?? 'personal';
        $openLinksInNewTab = (int)($_SESSION['open_links_in_new_tab'] ?? 1); 

    } elseif ($page === 'settings-login') {
        // ... (lógica de settings-login sin cambios)
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
                if (!class_exists('IntlDateFormatter')) {
                    $date = new DateTime($lastLog['changed_at'], new DateTimeZone('UTC'));
                    $date->setTimezone(new DateTimeZone('America/Chicago'));
                    $lastPasswordUpdateText = 'Última actualización: ' . $date->format('d/m/Y \a \l\a\s H:i');
                } else {
                    $formatter = new IntlDateFormatter('es_ES', IntlDateFormatter::LONG, IntlDateFormatter::SHORT, 'America/Chicago');
                    $timestamp = strtotime($lastLog['changed_at']);
                    $lastPasswordUpdateText = 'Última actualización: ' . $formatter->format($timestamp);
                }
            } else {
                $lastPasswordUpdateText = 'settings.login.lastPassUpdateNever'; 
            }

            $accountCreationDateText = '';
            if ($accountCreatedDate) {
                if (!class_exists('IntlDateFormatter')) {
                     $date = new DateTime($accountCreatedDate, new DateTimeZone('UTC'));
                     $date->setTimezone(new DateTimeZone('America/Chicago'));
                     $accountCreationDateText = 'Cuenta creada el ' . $date->format('d/m/Y');
                } else {
                    $formatter = new IntlDateFormatter('es_ES', IntlDateFormatter::LONG, IntlDateFormatter::NONE, 'America/Chicago');
                    $timestamp = strtotime($accountCreatedDate);
                    $accountCreationDateText = 'Cuenta creada el ' . $formatter->format($timestamp);
                }
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
        // ... (lógica de settings-accessibility sin cambios)
        $userTheme = $_SESSION['theme'] ?? 'system';
        $increaseMessageDuration = (int)($_SESSION['increase_message_duration'] ?? 0);
    
    } elseif ($page === 'settings-change-email') {
        // ... (lógica de settings-change-email sin cambios)
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
                $stmt_insert = $pdo->prepare("INSERT INTO verification_codes (identifier, code_type, code) VALUES (?, ?, ?)");
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
        // ... (lógica de settings-toggle-2fa sin cambios)
         try {
            $stmt_2fa = $pdo->prepare("SELECT is_2fa_enabled FROM users WHERE id = ?");
            $stmt_2fa->execute([$_SESSION['user_id']]);
            $is2faEnabled = (int)$stmt_2fa->fetchColumn(); 
         } catch (PDOException $e) {
             logDatabaseError($e, 'router - settings-toggle-2fa');
             $is2faEnabled = 0;
         }
    } elseif ($page === 'settings-delete-account') {
        // ... (lógica de settings-delete-account sin cambios)
         $userEmail = $_SESSION['email'] ?? 'correo@ejemplo.com';
         $defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";
         $profileImageUrl = $_SESSION['profile_image_url'] ?? $defaultAvatar;
         if (empty($profileImageUrl)) $profileImageUrl = $defaultAvatar;
    }
    
    elseif ($page === 'admin-manage-users') { 
        // ... (lógica de admin-manage-users sin cambios)
        $adminCurrentPage = (int)($_GET['p'] ?? 1);
        if ($adminCurrentPage < 1) $adminCurrentPage = 1;
    }
    elseif ($page === 'admin-edit-user') {
        // ... (lógica de admin-edit-user sin cambios)
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
                $isDefaultAvatar = strpos($profileImageUrl, '/assets/uploads/avatars_uploaded/') === false;
            } catch (PDOException $e) {
                logDatabaseError($e, 'router - admin-edit-user');
                $page = '404';
                $CURRENT_SECTION = '404';
            }
        }
    
    // --- ▼▼▼ INICIO DE NUEVA LÓGICA (PARA admin-edit-group) ▼▼▼ ---
    } elseif ($page === 'admin-edit-group') {
        $targetGroupId = (int)($_GET['id'] ?? 0);
        
        // 1. Validar que el admin sea 'founder'
        if ($_SESSION['role'] !== 'founder') {
             $page = '404';
             $CURRENT_SECTION = '404';
        } 
        // 2. Validar que el ID exista
        elseif ($targetGroupId === 0) {
            $page = '404';
            $CURRENT_SECTION = '404';
        } 
        // 3. Obtener datos del grupo
        else {
            try {
                $stmt_group = $pdo->prepare("SELECT id, name, group_type, privacy, access_key FROM `groups` WHERE id = ?");
                $stmt_group->execute([$targetGroupId]);
                $editGroup = $stmt_group->fetch();

                if (!$editGroup) {
                    // El grupo no existe
                    $page = '404';
                    $CURRENT_SECTION = '404';
                }
                // Si existe, la variable $editGroup estará disponible para el include
            } catch (PDOException $e) {
                logDatabaseError($e, 'router - admin-edit-group');
                $page = '404';
                $CURRENT_SECTION = '404';
            }
        }
    // --- ▲▲▲ FIN DE NUEVA LÓGICA ▲▲▲ ---
    
    } elseif ($page === 'admin-server-settings') {
        // ... (lógica de admin-server-settings sin cambios)
        $maintenanceModeStatus = $GLOBALS['site_settings']['maintenance_mode'] ?? '0';
        $allowRegistrationStatus = $GLOBALS['site_settings']['allow_new_registrations'] ?? '1';
    }
    elseif ($isAdminPage) {
        // Lógica general para otras páginas de admin si es necesario
    }
    
    
    include $allowedPages[$page];

} else {
    http_response_code(404);
    $CURRENT_SECTION = '404'; 
    include $allowedPages['404']; 
}
?>
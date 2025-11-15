<?php

include 'config.php';

getCsrfToken();

$GLOBALS['site_settings'] = [];
try {
    $stmt_settings = $pdo->prepare("SELECT setting_key, setting_value FROM site_settings");
    $stmt_settings->execute();
    while ($row = $stmt_settings->fetch()) {
        $GLOBALS['site_settings'][$row['setting_key']] = $row['setting_value'];
    }

    if (!isset($GLOBALS['site_settings']['maintenance_mode'])) {
        $GLOBALS['site_settings']['maintenance_mode'] = '0';
    }
    if (!isset($GLOBALS['site_settings']['allow_new_registrations'])) {
        $GLOBALS['site_settings']['allow_new_registrations'] = '1';
    }
    if (!isset($GLOBALS['site_settings']['username_cooldown_days'])) {
        $GLOBALS['site_settings']['username_cooldown_days'] = '30';
    }
    if (!isset($GLOBALS['site_settings']['email_cooldown_days'])) {
        $GLOBALS['site_settings']['email_cooldown_days'] = '12';
    }
    if (!isset($GLOBALS['site_settings']['avatar_max_size_mb'])) {
        $GLOBALS['site_settings']['avatar_max_size_mb'] = '2';
    }
    if (!isset($GLOBALS['site_settings']['max_login_attempts'])) {
        $GLOBALS['site_settings']['max_login_attempts'] = '5';
    }
    if (!isset($GLOBALS['site_settings']['lockout_time_minutes'])) {
        $GLOBALS['site_settings']['lockout_time_minutes'] = '5';
    }
    if (!isset($GLOBALS['site_settings']['allowed_email_domains'])) {
        $GLOBALS['site_settings']['allowed_email_domains'] = 'gmail.com\noutlook.com\nhotmail.com\nyahoo.com\nicloud.com';
    }
    if (!isset($GLOBALS['site_settings']['min_password_length'])) {
        $GLOBALS['site_settings']['min_password_length'] = '8';
    }
    if (!isset($GLOBALS['site_settings']['max_password_length'])) {
        $GLOBALS['site_settings']['max_password_length'] = '72';
    }
    if (!isset($GLOBALS['site_settings']['min_username_length'])) {
        $GLOBALS['site_settings']['min_username_length'] = '6';
    }
    if (!isset($GLOBALS['site_settings']['max_username_length'])) {
        $GLOBALS['site_settings']['max_username_length'] = '32';
    }
    if (!isset($GLOBALS['site_settings']['max_email_length'])) {
        $GLOBALS['site_settings']['max_email_length'] = '255';
    }
    if (!isset($GLOBALS['site_settings']['code_resend_cooldown_seconds'])) {
        $GLOBALS['site_settings']['code_resend_cooldown_seconds'] = '60';
    }

    if (!isset($GLOBALS['site_settings']['max_concurrent_users'])) {
        $GLOBALS['site_settings']['max_concurrent_users'] = '500';
    }

    if (!isset($GLOBALS['site_settings']['max_post_length'])) {
        $GLOBALS['site_settings']['max_post_length'] = '1000';
    }

    if (!isset($GLOBALS['site_settings']['messaging_service_enabled'])) {
        $GLOBALS['site_settings']['messaging_service_enabled'] = '1';
    }
} catch (PDOException $e) {
    logDatabaseError($e, 'bootstrapper - load site_settings');
    $GLOBALS['site_settings']['maintenance_mode'] = '0';
    $GLOBALS['site_settings']['allow_new_registrations'] = '1';
    $GLOBALS['site_settings']['username_cooldown_days'] = '30';
    $GLOBALS['site_settings']['email_cooldown_days'] = '12';
    $GLOBALS['site_settings']['avatar_max_size_mb'] = '2';
    $GLOBALS['site_settings']['max_login_attempts'] = '5';
    $GLOBALS['site_settings']['lockout_time_minutes'] = '5';
    $GLOBALS['site_settings']['allowed_email_domains'] = 'gmail.com\noutlook.com';
    $GLOBALS['site_settings']['min_password_length'] = '8';
    $GLOBALS['site_settings']['max_password_length'] = '72';
    $GLOBALS['site_settings']['min_username_length'] = '6';
    $GLOBALS['site_settings']['max_username_length'] = '32';
    $GLOBALS['site_settings']['max_email_length'] = '255';
    $GLOBALS['site_settings']['code_resend_cooldown_seconds'] = '60';

    $GLOBALS['site_settings']['max_concurrent_users'] = '500';

    $GLOBALS['site_settings']['max_post_length'] = '1000';

    $GLOBALS['site_settings']['messaging_service_enabled'] = '1';
}


if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("
            SELECT u.username, u.email, u.profile_image_url, u.banner_url, u.role, u.auth_token, u.account_status, u.status_expires_at, u.bio,
                   p.language, p.theme, p.usage_type, p.open_links_in_new_tab,
                   p.increase_message_duration, p.is_friend_list_private, p.is_email_public,
                   p.employment, p.education, p.message_privacy_level
            FROM users u
            LEFT JOIN user_preferences p ON u.id = p.user_id
            WHERE u.id = ?
        ");

        $stmt->execute([$_SESSION['user_id']]);
        $freshUserData = $stmt->fetch();

        if ($freshUserData) {

            $accountStatus = $freshUserData['account_status'];
            if ($accountStatus === 'deleted' || $accountStatus === 'suspended') {
                
                // --- ▼▼▼ INICIO DE CORRECCIÓN ▼▼▼ ---
                // 1. Guarda la fecha de expiración ANTES de destruir la sesión.
                $suspension_date = null;
                if ($accountStatus === 'suspended' && !empty($freshUserData['status_expires_at'])) {
                    $suspension_date = $freshUserData['status_expires_at'];
                }

                // 2. Destruye la sesión de inicio de sesión actual.
                session_unset();
                session_destroy();

                // 3. Inicia una sesión nueva y limpia.
                session_start(); // ¡IMPORTANTE! Iniciar nueva sesión
                
                // 4. Almacena SOLAMENTE la fecha en esta nueva sesión.
                if ($suspension_date) {
                    $_SESSION['suspension_expires_at'] = $suspension_date;
                }
                // --- ▲▲▲ FIN DE CORRECCIÓN ▲▲▲ ---
                
                $statusPath = ($accountStatus === 'deleted') ? '/account-status/deleted' : '/account-status/suspended';
                header('Location: ' . $basePath . $statusPath);
                exit;
            }

            $dbAuthToken = $freshUserData['auth_token'];
            $sessionAuthToken = $_SESSION['auth_token'] ?? null;

            if (empty($sessionAuthToken) || empty($dbAuthToken) || !hash_equals($dbAuthToken, $sessionAuthToken)) {
                session_unset();
                session_destroy();
                header('Location: ' . $basePath . '/login');
                exit;
            }

            if (isset($_SESSION['metadata_id'])) {
                $stmt_meta = $pdo->prepare("SELECT is_active FROM user_metadata WHERE id = ? AND user_id = ?");
                $stmt_meta->execute([$_SESSION['metadata_id'], $_SESSION['user_id']]);
                $sessionMeta = $stmt_meta->fetch();

                if (!$sessionMeta || $sessionMeta['is_active'] == 0) {
                    session_unset();
                    session_destroy();
                    header('Location: ' . $basePath . '/login');
                    exit;
                }
            } else {
                session_unset();
                session_destroy();
                header('Location: ' . $basePath . '/login');
                exit;
            }

            try {
                $stmt_presence = $pdo->prepare(
                    "UPDATE users SET last_seen = NOW() WHERE id = ?"
                );
                $stmt_presence->execute([$_SESSION['user_id']]);
            } catch (PDOException $e) {
                logDatabaseError($e, 'bootstrapper - update presence');
            }

            $_SESSION['username'] = $freshUserData['username'];
            $_SESSION['email'] = $freshUserData['email'];
            $_SESSION['profile_image_url'] = $freshUserData['profile_image_url'];
            $_SESSION['banner_url'] = $freshUserData['banner_url'];
            $_SESSION['bio'] = $freshUserData['bio'];
            $_SESSION['role'] = $freshUserData['role'];


            if ($freshUserData['language'] !== null) {
                $_SESSION['language'] = $freshUserData['language'];
                $_SESSION['theme'] = $freshUserData['theme'];
                $_SESSION['usage_type'] = $freshUserData['usage_type'];
                $_SESSION['open_links_in_new_tab'] = (int)$freshUserData['open_links_in_new_tab'];
                $_SESSION['increase_message_duration'] = (int)$freshUserData['increase_message_duration'];
                $_SESSION['is_friend_list_private'] = (int)$freshUserData['is_friend_list_private'];
                $_SESSION['is_email_public'] = (int)$freshUserData['is_email_public'];
                $_SESSION['employment'] = $freshUserData['employment'] ?? 'none';
                $_SESSION['education'] = $freshUserData['education'] ?? 'none';
                $_SESSION['message_privacy_level'] = $freshUserData['message_privacy_level'] ?? 'all';
            } else {
                $_SESSION['language'] = 'en-us';
                $_SESSION['theme'] = 'system';
                $_SESSION['usage_type'] = 'personal';
                $_SESSION['open_links_in_new_tab'] = 1;
                $_SESSION['increase_message_duration'] = 0;
                $_SESSION['is_friend_list_private'] = 1;
                $_SESSION['is_email_public'] = 0;
                $_SESSION['employment'] = 'none';
                $_SESSION['education'] = 'none';
                $_SESSION['message_privacy_level'] = 'all';
            }

            try {
                $stmt_restrictions = $pdo->prepare(
                    "SELECT restriction_type, expires_at -- <-- MODIFICADO
                     FROM user_restrictions 
                     WHERE user_id = ? 
                     AND (expires_at IS NULL OR expires_at > NOW())"
                );
                $stmt_restrictions->execute([$_SESSION['user_id']]);
                
                // --- ▼▼▼ INICIO DE MODIFICACIÓN (TAREA) ▼▼▼ ---
                $_SESSION['restrictions'] = [];
                // Limpiamos la variable de sesión anterior por si acaso
                if(isset($_SESSION['restriction_expires_at'])) {
                    unset($_SESSION['restriction_expires_at']);
                }

                while ($restriction = $stmt_restrictions->fetch()) {
                    $type = $restriction['restriction_type'];
                    $_SESSION['restrictions'][$type] = true;
                    
                    // Si esta es la restricción de mensajería, guardar su fecha de expiración
                    if ($type === 'CANNOT_MESSAGE' && !empty($restriction['expires_at'])) {
                        $_SESSION['restriction_expires_at'] = $restriction['expires_at'];
                    }
                    // (Se podría extender para otras restricciones si es necesario)
                }
                // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
                
            } catch (PDOException $e) {
                $_SESSION['restrictions'] = [];
                logDatabaseError($e, 'bootstrapper - load user_restrictions');
            }

        } else {
            session_unset();
            session_destroy();
            header('Location: ' . $basePath . '/login');
            exit;
        }
    } catch (PDOException $e) {
        logDatabaseError($e, 'bootstrapper - refresh session');
    }
}



$maintenanceMode = $GLOBALS['site_settings']['maintenance_mode'] ?? '0';
$userRole = $_SESSION['role'] ?? 'user';
$isPrivilegedUser = in_array($userRole, ['moderator', 'administrator', 'founder']);

$requestUri = $_SERVER['REQUEST_URI'];
$isMaintenancePage = (strpos($requestUri, '/maintenance') !== false);
$isLoginPage = (strpos($requestUri, '/login') !== false);
$isApiCall = (strpos($requestUri, '/api/') !== false);
$isConfigCall = (strpos($requestUri, '/config/') !== false);

$isServerFullPage = (strpos($requestUri, '/server-full') !== false);


if ($maintenanceMode === '1') {


    if (!isset($_SESSION['user_id'])) {
        if (!$isLoginPage && !$isMaintenancePage && !$isApiCall && !$isConfigCall) {
            header('Location: ' . $basePath . '/login');
            exit;
        }
    } else {
        if (!$isPrivilegedUser && !$isMaintenancePage && !$isLoginPage && !$isApiCall && !$isConfigCall && !$isServerFullPage) {
            header('Location: ' . $basePath . '/maintenance');
            exit;
        }
    }
}

$messagingServiceEnabled = $GLOBALS['site_settings']['messaging_service_enabled'] ?? '1';
$isMessagingPage = (strpos($requestUri, '/messages') !== false);
$isMessagingDisabledPage = (strpos($requestUri, '/messaging-disabled') !== false);

// --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
// Añadimos la nueva página a la lógica de exclusión
$isMessagingRestrictedPage = (strpos($requestUri, '/messaging-restricted') !== false);

if ($messagingServiceEnabled === '0' && !$isPrivilegedUser && $isMessagingPage && !$isMessagingDisabledPage && !$isMessagingRestrictedPage && !$isApiCall && !$isConfigCall) {
    header('Location: ' . $basePath . '/messaging-disabled');
    exit;
}

$isRestrictedFromMessaging = isset($_SESSION['restrictions']['CANNOT_MESSAGE']);

if ($isRestrictedFromMessaging && !$isPrivilegedUser && $isMessagingPage && !$isMessagingDisabledPage && !$isMessagingRestrictedPage && !$isApiCall && !$isConfigCall) {
    // Redirige a la NUEVA página de restricción
    header('Location: ' . $basePath . '/messaging-restricted');
    exit;
}
// --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---


// ===================================================================
// --- [INICIO DEL BLOQUE MOVIDO] ---
// Esta lógica ahora se ejecuta ANTES de usar $currentPage y $authLayoutPages
// ===================================================================

$requestPath = strtok($requestUri, '?');

$path = rtrim(str_replace($basePath, '', $requestPath), '/');
if (empty($path)) {
    $path = '/';
}

unset($_SESSION['initial_community_id']);
unset($_SESSION['initial_community_name']);
unset($_SESSION['initial_community_uuid']);

if (preg_match('/^\/c\/([a-fA-F0-9\-]{36})$/i', $path, $matches)) {
    $communityUuid = $matches[1];

    try {
        $stmt = $pdo->prepare("SELECT id, name FROM communities WHERE uuid = ?");
        $stmt->execute([$communityUuid]);
        $community = $stmt->fetch();

        if ($community) {
            $_SESSION['initial_community_id'] = $community['id'];
            $_SESSION['initial_community_name'] = $community['name'];
            $_SESSION['initial_community_uuid'] = $communityUuid;
        }
    } catch (PDOException $e) {
        logDatabaseError($e, 'bootstrapper - community-uuid-lookup');
    }

    $path = '/c/uuid-placeholder';
} elseif (preg_match('/^\/post\/(\d+)$/i', $path, $matches)) {
    $postId = $matches[1];
    $_GET['post_id'] = $postId;
    $path = '/post/id-placeholder';
} elseif (preg_match('/^\/profile\/([a-zA-Z0-9_]+)(?:\/(posts|likes|bookmarks|info|amigos|fotos))?$/i', $path, $matches)) {
    $username = $matches[1];
    $tab = $matches[2] ?? 'posts';
    $_GET['username'] = $username;
    $_GET['tab'] = $tab;

    $path = '/profile/username-placeholder';
} elseif (preg_match('/^\/search$/i', $path, $matches)) {
    $path = '/search';
} elseif (preg_match('/^\/messages\/([a-fA-F0-9\-]{36})$/i', $path, $matches)) {
    $userUuid = $matches[1];
    $_GET['user_uuid'] = $userUuid;
    $path = '/messages/uuid-placeholder';
} elseif ($path === '/messages') {
    $path = '/messages';
}


$pathsToPages = [
    '/'           => 'home',
    '/c/uuid-placeholder' => 'home',
    '/post/id-placeholder' => 'post-view',
    '/profile/username-placeholder' => 'view-profile',

    '/search' => 'search-results',

    '/trends' => 'trends',

    '/messages' => 'messages',
    '/messages/uuid-placeholder' => 'messages',

    '/explorer'   => 'explorer',
    '/login'      => 'login',
    '/maintenance' => 'maintenance',
    '/server-full' => 'server-full',

    '/register'                 => 'register-step1',
    '/register/additional-data' => 'register-step2',
    '/register/verification-code' => 'register-step3',

    '/reset-password'          => 'reset-step1',
    '/reset-password/verify-code'  => 'reset-step2',
    '/reset-password/new-password' => 'reset-step3',

    '/settings'                 => 'settings-profile',
    '/settings/your-profile'    => 'settings-profile',
    '/settings/login-security'  => 'settings-login',
    '/settings/accessibility'   => 'settings-accessibility',
    '/settings/device-sessions' => 'settings-devices',

    '/settings/privacy'         => 'settings-privacy',

    '/settings/change-password' => 'settings-change-password',
    '/settings/change-email'    => 'settings-change-email',
    '/settings/toggle-2fa'      => 'settings-toggle-2fa',
    '/settings/delete-account'  => 'settings-delete-account',

    '/account-status/deleted'   => 'account-status-deleted',
    '/account-status/suspended' => 'account-status-suspended',

    '/messaging-disabled'       => 'messaging-disabled',
    // --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
    '/messaging-restricted'     => 'messaging-restricted', // <-- NUEVA RUTA
    // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

    '/admin'                    => 'admin-dashboard',
    '/admin/dashboard'          => 'admin-dashboard',
    '/admin/manage-users'       => 'admin-manage-users',
    '/admin/create-user'        => 'admin-create-user',
    '/admin/edit-user'          => 'admin-edit-user',
    '/admin/server-settings'    => 'admin-server-settings',

    '/admin/manage-backups'     => 'admin-manage-backups',
    '/admin/restore-backup'     => 'admin-restore-backup',
    '/admin/manage-logs'        => 'admin-manage-logs',

    '/admin/manage-communities' => 'admin-manage-communities',
    '/admin/edit-community'     => 'admin-edit-community',

    // --- ▼▼▼ LÍNEA AÑADIDA ▼▼▼ ---
    '/admin/manage-status'      => 'admin-manage-status',
    // --- ▲▲▲ LÍNEA AÑADIDA ▲▲▲ ---
];

// --- Definición de $currentPage y $authLayoutPages ---
$isAuthPage = false;
$authLayoutPages = []; // <-- INICIALIZAR COMO ARRAY

if (array_key_exists($path, $pathsToPages)) {
    $currentPage = $pathsToPages[$path];
    $authLayoutPages = [
        'login',
        'register-step1',
        'register-step2',
        'register-step3',
        'reset-step1',
        'reset-step2',
        'reset-step3'
    ];
    $isAuthPage = in_array($currentPage, $authLayoutPages) ||
        strpos($currentPage, 'register-') === 0 ||
        strpos($currentPage, 'reset-') === 0;
} else {
    $currentPage = '404';
}


// ===================================================================
// --- [FIN DEL BLOQUE MOVIDO] ---
// ===================================================================


// ===================================================================
// --- [INICIO DEL BLOQUE QUE AHORA FUNCIONA] ---
// (Originalmente líneas 389-406)
// ===================================================================

// --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
// Añadimos la nueva página a la lista de "páginas de estado"
$statusPages = ['maintenance', 'server-full', 'account-status-deleted', 'account-status-suspended', 'messaging-disabled', 'messaging-restricted'];
// --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
$isStatusPage = in_array($currentPage, $statusPages);

$authAndStatusPagesForRedirect = array_merge($authLayoutPages, $statusPages);
$isAuthPageForRedirect = in_array($currentPage, $authAndStatusPagesForRedirect) ||
    strpos($currentPage, 'register-') === 0 ||
    strpos($currentPage, 'reset-') === 0;

$isSettingsPage = strpos($currentPage, 'settings-') === 0;
$isAdminPage = strpos($currentPage, 'admin-') === 0;

// ===================================================================
// --- [FIN DEL BLOQUE QUE AHORA FUNCIONA] ---
// ===================================================================


if ($isAdminPage && isset($_SESSION['user_id'])) {
    $userRole = $_SESSION['role'] ?? 'user';
    if ($userRole !== 'administrator' && $userRole !== 'founder') {
        $isAdminPage = false;
        $currentPage = '404';
        $isAuthPage = false;
    }

    if (($currentPage === 'admin-manage-backups' || $currentPage === 'admin-restore-backup' || $currentPage === 'admin-manage-logs') && $userRole !== 'founder') {
        $isAdminPage = true;
        $currentPage = '404';
        $isAuthPage = false;
    }
}

if (!isset($_SESSION['user_id']) && !$isAuthPageForRedirect) {
    header('Location: ' . $basePath . '/login');
    exit;
}

if (isset($_SESSION['user_id']) && $isAuthPage && !$isStatusPage) {
    if ($maintenanceMode !== '1') {
        header('Location: ' . $basePath . '/');
        exit;
    }
}


if ($path === '/settings') {
    header('Location: ' . $basePath . '/settings/your-profile');
    exit;
}

if ($path === '/admin') {
    header('Location: ' . $basePath . '/admin/dashboard');
    exit;
}
?>
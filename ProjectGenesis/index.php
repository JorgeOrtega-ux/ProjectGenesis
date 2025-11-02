<?php

include 'config/config.php';

getCsrfToken(); 

if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT username, email, profile_image_url, role, auth_token, account_status FROM users WHERE id = ?");
        
        $stmt->execute([$_SESSION['user_id']]);
        $freshUserData = $stmt->fetch();

        if ($freshUserData) {
            
            $accountStatus = $freshUserData['account_status'];
            if ($accountStatus === 'suspended' || $accountStatus === 'deleted') {
                session_unset();
                session_destroy();
                
                $statusPath = ($accountStatus === 'suspended') ? '/account-status/suspended' : '/account-status/deleted';
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

            $_SESSION['username'] = $freshUserData['username'];
            $_SESSION['email'] = $freshUserData['email'];
            $_SESSION['profile_image_url'] = $freshUserData['profile_image_url'];
            $_SESSION['role'] = $freshUserData['role']; 
            
            
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
                $_SESSION['language'] = 'en-us';
                $_SESSION['theme'] = 'system';
                $_SESSION['usage_type'] = 'personal';
                $_SESSION['open_links_in_new_tab'] = 1;
                $_SESSION['increase_message_duration'] = 0;
            }

        } else {
            session_unset();
            session_destroy();
            header('Location: ' . $basePath . '/login');
            exit;
        }
    } catch (PDOException $e) {
        logDatabaseError($e, 'index - refresh session'); 
    }
}



$requestUri = $_SERVER['REQUEST_URI'];
$requestPath = strtok($requestUri, '?'); 

$path = str_replace($basePath, '', $requestPath);
if (empty($path) || $path === '/') {
    $path = '/';
}

$pathsToPages = [
    '/'           => 'home',
    '/explorer'   => 'explorer',
    '/login'      => 'login',
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
    
    '/settings/change-password' => 'settings-change-password',
    '/settings/change-email'    => 'settings-change-email',
    '/settings/toggle-2fa'      => 'settings-toggle-2fa',
    '/settings/delete-account'  => 'settings-delete-account',
    
    '/account-status/deleted'   => 'account-status-deleted',
    '/account-status/suspended' => 'account-status-suspended',
    
    '/admin'                    => 'admin-dashboard',
    '/admin/dashboard'          => 'admin-dashboard',
    '/admin/manage-users'       => 'admin-manage-users', 
];

$currentPage = $pathsToPages[$path] ?? '404';

$authPages = ['login'];
$isAuthPage = in_array($currentPage, $authPages) || 
              strpos($currentPage, 'register-') === 0 ||
              strpos($currentPage, 'reset-') === 0 ||
              strpos($currentPage, 'account-status-') === 0; 

$isSettingsPage = strpos($currentPage, 'settings-') === 0;
$isAdminPage = strpos($currentPage, 'admin-') === 0;

if ($isAdminPage && isset($_SESSION['user_id'])) {
    $userRole = $_SESSION['role'] ?? 'user';
    if ($userRole !== 'administrator' && $userRole !== 'founder') {
        $isAdminPage = false; 
        $currentPage = '404'; 
    }
}

if (!isset($_SESSION['user_id']) && !$isAuthPage) {
    header('Location: ' . $basePath . '/login');
    exit;
}
if (isset($_SESSION['user_id']) && $isAuthPage) {
    header('Location: ' . $basePath . '/');
    exit;
}

if ($path === '/settings') {
    header('Location: ' . $basePath . '/settings/your-profile');
    exit;
}
if ($path === '/admin') {
    header('Location: ' . $basePath . '/admin/dashboard');
    exit;
}

$themeClass = '';
if (isset($_SESSION['theme'])) {
    if ($_SESSION['theme'] === 'light') {
        $themeClass = 'light-theme';
    } elseif ($_SESSION['theme'] === 'dark') {
        $themeClass = 'dark-theme';
    }
}

$langMap = [
    'es-latam' => 'es-419',
    'es-mx' => 'es-MX',
    'en-us' => 'en-US',
    'fr-fr' => 'fr-FR'
];

$currentLang = $_SESSION['language'] ?? 'en-us'; 

$htmlLang = $langMap[$currentLang] ?? 'en'; 

$jsLanguage = 'en-us'; 

if (isset($_SESSION['language'])) {
    $jsLanguage = $_SESSION['language'];
} else {
    $browserLang = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'en-us';
    $jsLanguage = getPreferredLanguage($browserLang); 
}
?>
<!DOCTYPE html>
<html lang="<?php echo $htmlLang; ?>" class="<?php echo $themeClass; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded">
    <link rel="stylesheet" type="text/css" href="<?php echo $basePath; ?>/assets/css/styles.css">
    <title>ProjectGenesis</title>
</head>

<body>

    <div class="page-wrapper">
        <div class="main-content">
            <div class="general-content">
                
                <?php if (!$isAuthPage): ?>
                <div class="general-content-top">
                    <?php include 'includes/layouts/header.php'; ?>
                </div>
                <?php endif; ?>
                
                <div class="general-content-bottom">
                    
                    <?php if (!$isAuthPage): ?>
                    <?php 
                    include 'includes/modules/module-surface.php'; 
                    ?>
                    <?php endif; ?>

                    <div class="general-content-scrolleable">
                        
                        <div class="page-loader" id="page-loader">
                            <div class="spinner"></div>
                        </div>
                        <div class="main-sections">
                            </div>
                    
                </div>
            </div>
            
            <div id="alert-container"></div>
            </div>
    </div>

    <script>
        // Definir variables globales de JS
        window.projectBasePath = '<?php echo $basePath; ?>';
        window.csrfToken = '<?php echo $_SESSION['csrf_token'] ?? ''; ?>'; // Añadido ?? '' por si acaso
        
        // --- ▼▼▼ ¡INICIO DE MODIFICACIÓN: INYECTAR PREFERENCIAS! ▼▼▼ ---
        window.userTheme = '<?php echo $_SESSION['theme'] ?? 'system'; ?>';
        window.userIncreaseMessageDuration = <?php echo $_SESSION['increase_message_duration'] ?? 0; ?>;
        
        // --- ▼▼▼ INICIO: MODIFICACIÓN PASO 2 (Bloque 2) ▼▼▼ ---
        // ¡NUEVA MODIFICACIÓN! Inyectar idioma actual (calculado arriba)
        window.userLanguage = '<?php echo $jsLanguage; ?>'; 
        // --- ▲▲▲ FIN: MODIFICACIÓN PASO 2 (Bloque 2) ▲▲▲ ---
        
        // --- ▲▲▲ ¡FIN DE MODIFICACIÓN! ▲▲▲ ---
    </script>
    <script type="module" src="<?php echo $basePath; ?>/assets/js/app-init.js"></script>
    
    </body>

</html>
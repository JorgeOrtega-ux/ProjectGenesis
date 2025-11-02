<?php
// FILE: includes/router-guard.php

// $basePath y $pdo (para rol de admin) se definieron en bootstrapper.php
// $GLOBALS['site_settings'] se definió en bootstrapper.php

// --- ▼▼▼ INICIO DE MODIFICACIÓN (MODO MANTENIMIENTO) ▼▼▼ ---
$maintenanceMode = $GLOBALS['site_settings']['maintenance_mode'] ?? '0';
$userRole = $_SESSION['role'] ?? 'user'; // Asumir 'user' si no está logueado
$isPrivilegedUser = in_array($userRole, ['moderator', 'administrator', 'founder']);

$requestUri = $_SERVER['REQUEST_URI'];
$isMaintenancePage = (strpos($requestUri, '/maintenance') !== false);
$isLoginPage = (strpos($requestUri, '/login') !== false);
$isApiCall = (strpos($requestUri, '/api/') !== false);
$isConfigCall = (strpos($requestUri, '/config/') !== false); // Permitir logout

// Si el modo mantenimiento está ACTIVO
if ($maintenanceMode === '1') {
    // Y el usuario NO es privilegiado Y NO está intentando acceder a una página permitida
    if (!$isPrivilegedUser && !$isMaintenancePage && !$isLoginPage && !$isApiCall && !$isConfigCall) {
        header('Location: ' . $basePath . '/maintenance');
        exit;
    }
}
// --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---


// 1. Analizar la URL
$requestPath = strtok($requestUri, '?'); 

$path = str_replace($basePath, '', $requestPath);
if (empty($path) || $path === '/') {
    $path = '/';
}

// 2. Mapa de rutas
$pathsToPages = [
    '/'           => 'home',
    '/explorer'   => 'explorer',
    '/login'      => 'login',
    '/maintenance' => 'maintenance', // <--- ¡NUEVA LÍNEA!
    
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
    '/admin/create-user'        => 'admin-create-user', // <--- ¡NUEVA LÍNEA!
    '/admin/edit-user'          => 'admin-edit-user', // <--- ¡NUEVA LÍNEA!
    '/admin/server-settings'    => 'admin-server-settings', // <--- ¡NUEVA LÍNEA!
];

// 3. Determinar la página actual y los tipos de página
$currentPage = $pathsToPages[$path] ?? '404';

// --- ▼▼▼ INICIO DE MODIFICACIÓN (MODO MANTENIMIENTO) ▼▼▼ ---
$authPages = ['login', 'maintenance']; // 'maintenance' se trata como una página de auth
// --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
$isAuthPage = in_array($currentPage, $authPages) || 
              strpos($currentPage, 'register-') === 0 ||
              strpos($currentPage, 'reset-') === 0 ||
              strpos($currentPage, 'account-status-') === 0; 

$isSettingsPage = strpos($currentPage, 'settings-') === 0;
$isAdminPage = strpos($currentPage, 'admin-') === 0;

// 4. Lógica de Autorización y Redirecciones
if ($isAdminPage && isset($_SESSION['user_id'])) {
    $userRole = $_SESSION['role'] ?? 'user';
    if ($userRole !== 'administrator' && $userRole !== 'founder') {
        $isAdminPage = false; // No es un admin
        $currentPage = '404'; // Tratar la página como 404
    }
}

// Redirigir a login si no está logueado y no es página de auth
if (!isset($_SESSION['user_id']) && !$isAuthPage) {
    header('Location: ' . $basePath . '/login');
    exit;
}
// Redirigir a home si está logueado e intenta ir a auth
if (isset($_SESSION['user_id']) && $isAuthPage && $currentPage !== 'maintenance') { // <-- MODIFICADO
    // --- ▼▼▼ INICIO DE MODIFICACIÓN (MODO MANTENIMIENTO) ▼▼▼ ---
    // Si el modo mantenimiento está activo, no redirigir a /home,
    // el chequeo de al inicio de este archivo ya lo habrá enviado a /maintenance si es 'user'
    // o le permitirá ver la página de login/registro si es admin.
    if ($maintenanceMode !== '1') {
         header('Location: ' . $basePath . '/');
         exit;
    }
    // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
}

// Redirecciones de conveniencia
if ($path === '/settings') {
    header('Location: ' . $basePath . '/settings/your-profile');
    exit;
}
if ($path === '/admin') {
    header('Location: ' . $basePath . '/admin/dashboard');
    exit;
}

// Las variables $currentPage, $isAuthPage, $isSettingsPage, $isAdminPage
// están ahora disponibles globalmente para los scripts que se incluyan después.
?>
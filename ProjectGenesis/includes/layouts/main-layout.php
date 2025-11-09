<?php
// FILE: includes/layouts/main-layout.php
?>
<!DOCTYPE html>
<html lang="<?php echo $htmlLang; ?>" class="<?php echo $themeClass; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded">
    <link rel="stylesheet" type="text/css" href="<?php echo $basePath; ?>/assets/css/styles.css">
    <link rel="stylesheet" type="text/css" href="<?php echo $basePath; ?>/assets/css/components.css">
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
                        <?php include 'includes/modules/module-surface.php'; // Menú Izquierdo ?>
                    <?php endif; ?>

                    <div class="general-content-scrolleable">

                        <div class="page-loader" id="page-loader">
                            <div class="spinner"></div>
                        </div>
                        <div class="main-sections">
                            <?php ?>
                        </div>

                    </div>
                    
                    <?php 
                    // --- ▼▼▼ INICIO DE LA MODIFICACIÓN (Mostrar amigos solo en 'home') ▼▼▼ ---
                    // Usamos $currentPage (definido en bootstrapper.php) para la carga inicial.
                    // $currentPage es 'home' para / y /c/uuid...
                    if (!$isAuthPage && $currentPage === 'home'): 
                    // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
                    ?>
                        <?php include 'includes/modules/module-friend-list.php'; // Incluir el módulo de amigos directamente ?>
                    <?php endif; ?>
                </div>

                <div id="alert-container"></div>
            </div>
            
            <?php
                /* --- El div de amigos se movió adentro de general-content-bottom --- */
            ?>
            </div>
    </div>

    <script>
        window.projectBasePath = '<?php echo $basePath; ?>';
        window.csrfToken = '<?php echo $_SESSION['csrf_token'] ?? ''; ?>';

        // --- ▼▼▼ ¡LÍNEA AÑADIDA! ▼▼▼ ---
        window.userId = <?php echo $_SESSION['user_id'] ?? 0; ?>;
        // --- ▼▼▼ ¡NUEVA LÍNEA AÑADIDA! ▼▼▼ ---
        window.userRole = '<?php echo $_SESSION['role'] ?? 'user'; ?>';
        // --- ▲▲▲ ¡FIN DE LA LÍNEA AÑADIDA! ▲▲▲ ---

        // --- ▼▼▼ LÍNEA AÑADIDA ▼▼▼ ---
        // Esta es la IP o dominio (ej. 192.168.1.100) que el navegador usó para cargar la página.
        window.wsHost = '<?php echo $_SERVER['HTTP_HOST']; ?>';
        // --- ▲▲▲ FIN DE LÍNEA AÑADIDA ▲▲▲ ---

        // --- ▼▼▼ INICIO DE LA MODIFICACIÓN ▼▼▼ ---
        // Esta variable SÍ nos dice si el usuario está logueado
        window.isUserLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
        // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---

        window.userTheme = '<?php echo $_SESSION['theme'] ?? 'system'; ?>';
        window.userIncreaseMessageDuration = <?php echo $_SESSION['increase_message_duration'] ?? 0; ?>;

        // --- ▼▼▼ LÍNEA AÑADIDA ▼▼▼ ---
        window.avatarMaxSizeMB = <?php echo $GLOBALS['site_settings']['avatar_max_size_mb'] ?? 2; ?>;

        // --- ▼▼▼ ¡LÍNEAS MODIFICADAS/AÑADIDAS! ▼▼▼ ---
        window.minPasswordLength = <?php echo $GLOBALS['site_settings']['min_password_length'] ?? 8; ?>;
        window.maxPasswordLength = <?php echo $GLOBALS['site_settings']['max_password_length'] ?? 72; ?>;
        // --- ▼▼▼ MODIFICACIÓN: Claves añadidas ▼▼▼ ---
        window.minUsernameLength = <?php echo $GLOBALS['site_settings']['min_username_length'] ?? 6; ?>;
        window.maxUsernameLength = <?php echo $GLOBALS['site_settings']['max_username_length'] ?? 32; ?>;
        window.maxEmailLength = <?php echo $GLOBALS['site_settings']['max_email_length'] ?? 255; ?>;
        window.codeResendCooldownSeconds = <?php echo $GLOBALS['site_settings']['code_resend_cooldown_seconds'] ?? 60; ?>;
        // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
        // --- ▲▲▲ FIN DE LÍNEA AÑADIDA ▲▲▲ ---
        
        // --- ▼▼▼ INICIO DE NUEVO BLOQUE (INYECCIÓN DE DATOS DE URL) ▼▼▼ ---
        window.initialCommunityId = <?php echo json_encode($_SESSION['initial_community_id'] ?? null); ?>;
        window.initialCommunityName = <?php echo json_encode($_SESSION['initial_community_name'] ?? null); ?>;
        window.initialCommunityUuid = <?php echo json_encode($_SESSION['initial_community_uuid'] ?? null); ?>;
        <?php
            // Limpiar las variables de sesión para que no persistan en la navegación SPA
            unset($_SESSION['initial_community_id']);
            unset($_SESSION['initial_community_name']);
            unset($_SESSION['initial_community_uuid']);
        ?>
        // --- ▲▲▲ FIN DE NUEVO BLOQUE ▲▲▲ ---

        window.userLanguage = '<?php echo $jsLanguage; ?>';
    </script>
    <script type="module" src="<?php echo $basePath; ?>/assets/js/app-init.js"></script>

</body>

</html>
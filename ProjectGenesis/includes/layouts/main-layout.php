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
                        <?php include 'includes/modules/module-surface.php'; ?>
                    <?php endif; ?>

                    <div class="general-content-scrolleable">

                        <div class="page-loader" id="page-loader">
                            <div class="spinner"></div>
                        </div>
                        <div class="main-sections">
                            <?php ?>
                        </div>

                    </div>
                </div>

                <div id="alert-container"></div>
            </div>
        </div>

        <script>
            window.projectBasePath = '<?php echo $basePath; ?>';
            window.csrfToken = '<?php echo $_SESSION['csrf_token'] ?? ''; ?>';

            // --- ▼▼▼ ¡LÍNEA AÑADIDA! ▼▼▼ ---
            window.userId = <?php echo $_SESSION['user_id'] ?? 0; ?>;
            // --- ▲▲▲ ¡FIN DE LÍNEA AÑADIDA! ▲▲▲ ---

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

            window.userLanguage = '<?php echo $jsLanguage; ?>';
        </script>
        <script type="module" src="<?php echo $basePath; ?>/assets/js/app-init.js"></script>

</body>

</html>
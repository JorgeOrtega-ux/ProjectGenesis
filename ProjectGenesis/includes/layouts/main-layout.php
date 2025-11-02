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

            window.userTheme = '<?php echo $_SESSION['theme'] ?? 'system'; ?>';
            window.userIncreaseMessageDuration = <?php echo $_SESSION['increase_message_duration'] ?? 0; ?>;

            // --- ▼▼▼ LÍNEA AÑADIDA ▼▼▼ ---
            window.avatarMaxSizeMB = <?php echo $GLOBALS['site_settings']['avatar_max_size_mb'] ?? 2; ?>;
            // --- ▲▲▲ FIN DE LÍNEA AÑADIDA ▲▲▲ ---

            window.userLanguage = '<?php echo $jsLanguage; ?>';
        </script>
        <script type="module" src="<?php echo $basePath; ?>/assets/js/app-init.js"></script>

</body>

</html>
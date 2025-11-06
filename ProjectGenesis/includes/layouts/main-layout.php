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

                <?php 
                // --- ▼▼▼ ¡AQUÍ ESTÁ LA CORRECCIÓN! ▼▼▼ ---
                // En lugar de (!$isAuthPage), comprobamos si la sesión existe.
                // El header SÓLO debe mostrarse si el usuario está logueado.
                if (isset($_SESSION['user_id'])): 
                // --- ▲▲▲ FIN DE LA CORRECCIÓN ▲▲▲ ---
                ?>
                    <div class="general-content-top">
                        <?php include 'includes/layouts/header.php'; ?>
                    </div>
                <?php endif; ?>

                <div class="general-content-bottom">

                    <?php 
                    // --- ▼▼▼ ¡AQUÍ ESTÁ LA OTRA CORRECCIÓN! ▼▼▼ ---
                    // El menú lateral también debe mostrarse solo si el usuario está logueado.
                    if (isset($_SESSION['user_id'])): 
                    // --- ▲▲▲ FIN DE LA CORRECCIÓN ▲▲▲ ---
                    ?>
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

        <!-- ▼▼▼ INICIO DE LÓGICA Y HTML DEL MODAL DE GRUPOS ▼▼▼ -->
        <?php
        // Esta lógica solo se ejecuta si el usuario está logueado
        if (isset($_SESSION['user_id'], $pdo)) {
            $user_groups = [];
            try {
                // Reutilizamos la lógica de 'my-groups.php' para obtener los grupos
                $stmt = $pdo->prepare(
                    "SELECT 
                        g.id,
                        g.name
                     FROM groups g
                     JOIN user_groups ug_main ON g.id = ug_main.group_id
                     WHERE ug_main.user_id = ?
                     GROUP BY g.id, g.name
                     ORDER BY g.name"
                );
                $stmt->execute([$_SESSION['user_id']]);
                $user_groups = $stmt->fetchAll();
            } catch (PDOException $e) {
                logDatabaseError($e, 'main-layout.php - load user groups for modal');
            }
        ?>
        <div class="modal-overlay disabled" id="group-select-modal" data-action="closeGroupSelectModal">
            <div class="modal-content" style="max-width: 500px;">
                <div class="modal__header">
                    <h2 class="modal__title" data-i18n="modals.selectGroup.title">Seleccionar Grupo</h2>
                    <button type="button" class="modal-close-btn" data-action="closeGroupSelectModal">
                        <span class="material-symbols-rounded">close</span>
                    </button>
                </div>
                
                <div class="modal__body" id="group-select-modal-body">
                    <?php if (empty($user_groups)): ?>
                        <div class="component-card component-card--column" style="text-align: center; padding: 32px; box-shadow: none; border: none;">
                            <div class="component-card__icon" style="background-color: transparent; width: 60px; height: 60px; margin-bottom: 16px; border: none;">
                                <span class="material-symbols-rounded" style="font-size: 60px; color: #6b7280;">groups</span>
                            </div>
                            <h2 class="component-card__title" style="font-size: 20px;" data-i18n="modals.selectGroup.noGroups">
                                No perteneces a ningún grupo.
                            </h2>
                            <div class="component-card__actions" style="margin-top: 24px; gap: 12px; width: 100%; justify-content: center; display: flex;">
                                <button type="button" 
                                   class="component-action-button component-action-button--primary" 
                                   data-action="toggleSectionJoinGroup" 
                                   data-i18n="modals.selectGroup.joinButton">
                                   Unirme a un grupo
                                </button>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="group-modal-list">
                            <?php foreach ($user_groups as $group): ?>
                                <a href="#" class="group-modal-item" 
                                   data-group-id="<?php echo $group['id']; ?>" 
                                   data-group-name="<?php echo htmlspecialchars($group['name']); ?>">
                                    <span class="material-symbols-rounded">label</span>
                                    <span class="group-modal-item-text"><?php echo htmlspecialchars($group['name']); ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        } // Fin del if (isset($_SESSION['user_id']))
        ?>
        <!-- ▲▲▲ FIN DEL MODAL DE GRUPOS ▲▲▲ -->


        <script>
            window.projectBasePath = '<?php echo $basePath; ?>';
            window.csrfToken = '<?php echo $_SESSION['csrf_token'] ?? ''; ?>';

            // --- ▼▼▼ ¡LÍNEA AÑADIDA! ▼▼▼ ---
            window.userId = <?php echo $_SESSION['user_id'] ?? 0; ?>;
            // --- ▲▲▲ ¡FIN DE LÍNEA AÑADIDA! ▼▼▼ ---

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
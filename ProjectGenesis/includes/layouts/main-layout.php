<?php
?>
<!DOCTYPE html>
<html lang="<?php echo $htmlLang; ?>" class="<?php echo $themeClass; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded">
    <link rel="stylesheet" type="text/css" href="<?php echo $basePath; ?>/assets/css/styles.css">
    <link rel="stylesheet" type="text/css" href="<?php echo $basePath; ?>/assets/css/components.css">
    
    <link rel="stylesheet" type="text/css" href="<?php echo $basePath; ?>/assets/css/messaging.css">
    <title>ProjectGenesis</title>

    <?php ?>
    <style>
        .photo-viewer-overlay {
            display: none; /* Oculto por defecto */
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.8);
            z-index: 2000; /* Por encima de todo lo demás */
            flex-direction: column;
            animation: modalFadeIn 0.2s ease-out forwards;
        }

        .photo-viewer-overlay.active {
            display: flex;
        }

        .photo-viewer-header {
            height: 50px;
            flex-shrink: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 12px;
            color: #ffffff;
        }

        .viewer-header-user {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0; /* Para que text-overflow funcione */
        }

        .viewer-user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            overflow: hidden;
            flex-shrink: 0;
        }

        .viewer-user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .viewer-user-name {
            font-size: 16px;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .viewer-header-controls {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .viewer-control-btn {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .viewer-control-btn:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .viewer-control-btn:disabled {
            opacity: 0.3;
            cursor: not-allowed;
            background-color: rgba(255, 255, 255, 0.1);
        }

        .photo-viewer-content {
            flex-grow: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            padding: 16px;
        }

        .viewer-image-wrapper {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #viewer-image {
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
            border-radius: 12px; /* Bordes redondos */
        }
    </style>
    <?php ?>
</head>

<body>

    <div class="page-wrapper">
        <div class="main-content">

           <div class="general-content">

    <?php if (!$isAuthPage && !$isStatusPage): ?> 
        <div class="general-content-top">
            <?php include 'includes/layouts/header.php'; ?>
        </div>
    <?php endif; ?>

    <div class="general-content-bottom">

        <?php if (!$isAuthPage && !$isStatusPage): ?> 
            <?php include 'includes/modules/module-surface.php'; ?>
        <?php endif; ?>

        <div class="general-content-scrolleable">
            <div class="page-loader" id="page-loader">
                <div class="spinner"></div>
            </div>
            <div class="main-sections">
                <?php /* El contenido de router.php se carga aquí */ ?>
            </div>
        </div>
        
        <?php
        if (!$isAuthPage && !$isStatusPage): // <--- También aplica la lógica aquí
        ?>
            <div id="friend-list-wrapper">
                <?php
                if ($currentPage === 'home'):
                    include 'includes/modules/module-friend-list.php';
                endif;
                ?>
            </div>
        <?php endif; ?>
        
    </div>

    <div id="alert-container"></div>
</div>
            
            <?php
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

        // --- ▼▼▼ INICIO DE LÍNEA A AÑADIR (LÓGICA DE CHAT DESHABILITADO) ▼▼▼ ---
        window.isMessagingEnabled = <?php echo ($GLOBALS['site_settings']['messaging_service_enabled'] ?? '1') === '1' ? 'true' : 'false'; ?>;
        // --- ▲▲▲ FIN DE LÍNEA AÑADIDA ▲▲▲ ---
        
        // --- ▼▼▼ INICIO DE NUEVA LÍNEA (BUG FIX) ▼▼▼ ---
        window.isMessagingRestricted = <?php echo isset($_SESSION['restrictions']['CANNOT_MESSAGE']) ? 'true' : 'false'; ?>;
        // --- ▲▲▲ FIN DE NUEVA LÍNEA (BUG FIX) ▲▲▲ ---

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
            unset($_SESSION['initial_community_id']);
            unset($_SESSION['initial_community_name']);
            unset($_SESSION['initial_community_uuid']);
        ?>
        // --- ▲▲▲ FIN DE NUEVO BLOQUE ▲▲▲ ---

        window.userLanguage = '<?php echo $jsLanguage; ?>';
    </script>
    <script type="module" src="<?php echo $basePath; ?>/assets/js/app-init.js"></script>

    <audio id="chat-notification-sound" 
           src="<?php echo $basePath; ?>/assets/sounds/notification.mp3" 
           preload="auto">
    </audio>
    
    <?php ?>
    <div class="photo-viewer-overlay" id="photo-viewer-modal">
        <div class="photo-viewer-header">
            <div class="viewer-header-user">
                <div class="viewer-user-avatar">
                    <img id="viewer-user-avatar" src="" alt="Avatar de usuario">
                </div>
                <span class="viewer-user-name" id="viewer-user-name">Usuario</span>
            </div>
            <div class="viewer-header-controls">
                <button type="button" class="viewer-control-btn" id="viewer-btn-prev" title="Anterior">
                    <span class="material-symbols-rounded">arrow_back_ios_new</span>
                </button>
                <button type="button" class="viewer-control-btn" id="viewer-btn-next" title="Siguiente">
                    <span class="material-symbols-rounded">arrow_forward_ios</span>
                </button>
                <button type="button" class="viewer-control-btn" id="viewer-btn-close" title="Cerrar (Esc)">
                    <span class="material-symbols-rounded">close</span>
                </button>
            </div>
        </div>
        <div class="photo-viewer-content">
            <div class="viewer-image-wrapper">
                <img src="" alt="Visor de imagen" id="viewer-image">
            </div>
        </div>
    </div>
    <?php ?>
    
</body>

</html>
<?php
// --- NUEVO BLOQUE PHP ---
// Lógica para obtener la URL de la imagen de perfil

// Definimos una URL de avatar por defecto
$defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";

// Obtenemos la URL de la sesión. Si no existe, usamos la de por defecto.
$profileImageUrl = $_SESSION['profile_image_url'] ?? $defaultAvatar;

// Asegurarnos de que no esté vacía (por si en la BD se guardó un NULL)
if (empty($profileImageUrl)) {
    $profileImageUrl = $defaultAvatar;
}

// --- MODIFICACIÓN: Obtener username para el ALT text ---
$usernameForAlt = $_SESSION['username'] ?? 'Usuario';

// --- ¡NUEVO BLOQUE PARA EL ROL! ---
// Obtenemos el rol de la sesión, si no existe, 'user' por defecto
$userRole = $_SESSION['role'] ?? 'user';
// --- FIN DEL NUEVO BLOQUE ---

?>
<div class="header">
    <div class="header-left">
        <div class="header-item">
            <div class="header-button"
                data-action="toggleModuleSurface"
                data-tooltip="header.buttons.menu">
                <span class="material-symbols-rounded">menu</span>
            </div>
        </div>
    </div>

    <div class="header-center">
        <div class="header-search-container">
            <div class="header-search-icon">
                <span class="material-symbols-rounded">search</span>
            </div>
            <input type="text"
                class="header-search-input"
                id="header-search-input"
                placeholder="Buscar personas, publicaciones..."
                data-i18n-placeholder="header.search.placeholder"
                autocomplete="off">

            <div class="popover-module popover-module--anchor-width body-title disabled"
                data-module="moduleSearch"
                id="search-results-popover"
                style="top: calc(100% + 4px);">
                <div class="menu-content" id="search-results-content">
                    <div class="search-placeholder">
                        <span>Busca para encontrar resultados.</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <div class="header-right">
        <div class="header-item">

            <div class="header-button header-notification-btn"
                data-action="toggleModuleNotifications"
                data-tooltip="header.buttons.notifications"
                style="position: relative;">

                <span class="material-symbols-rounded">notifications</span>

                <span class="notification-badge disabled" id="notification-badge-count">0</span>
            </div>
            <div class="header-button header-profile"
                data-action="toggleModuleSelect"
                data-role="<?php echo htmlspecialchars($userRole); ?>"
                data-tooltip="header.buttons.profile">

                <img src="<?php echo htmlspecialchars($profileImageUrl); ?>"
                    alt="<?php echo htmlspecialchars($usernameForAlt); ?>"
                    class="header-profile-image"
                    data-i18n-alt-prefix="header.profile.altPrefix">
            </div>
        </div>
    </div>

    <div class="popover-module popover-module--anchor-right body-title disabled" data-module="moduleSelect">
        <div class="menu-content">
            <div class="menu-list">

                <a class="menu-link"
                    href="<?php echo $basePath; ?>/profile/<?php echo htmlspecialchars($usernameForAlt); ?>">
                    <div class="menu-link-icon">
                        <span class="material-symbols-rounded">account_circle</span>
                    </div>
                    <div class="menu-link-text">
                        <span>Mi Perfil</span>
                    </div>
                </a>
                <?php
                // --- ▼▼▼ INICIO DE LA MODIFICACIÓN ▼▼▼ ---
                // Mostrar solo si el rol es administrador o fundador
                if (isset($userRole) && ($userRole === 'administrator' || $userRole === 'founder')):
                ?>
                    <div class="menu-link" data-action="toggleSectionAdminDashboard">
                        <div class="menu-link-icon">
                            <span class="material-symbols-rounded">admin_panel_settings</span>
                        </div>
                        <div class="menu-link-text">
                            <span data-i18n="header.profile.adminPanel">Panel de Administración</span>
                        </div>
                    </div>
                <?php
                endif;
                // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
                ?>

                <div class="menu-link" data-action="toggleSectionSettingsProfile">
                    <div class="menu-link-icon">
                        <span class="material-symbols-rounded">settings</span>
                    </div>
                    <div class="menu-link-text">
                        <span data-i18n="header.profile.settings"></span>
                    </div>
                </div>
                <div class="menu-link">
                    <div class="menu-link-icon">
                        <span class="material-symbols-rounded">help</span>
                    </div>
                    <div class="menu-link-text">
                        <span data-i18n="header.profile.help"></span>
                    </div>
                </div>

                <div class="menu-link" data-action="logout">
                    <div class="menu-link-icon">
                        <span class="material-symbols-rounded">logout</span>
                    </div>
                    <div class="menu-link-text">
                        <span data-i18n="header.profile.logout"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="popover-module popover-module--anchor-right popover-module--notifications body-title disabled" data-module="moduleNotifications">
        <div class="menu-content">
            <div class="notification-header">
                <h3 class="notification-title" data-i18n="notifications.title">Notificaciones</h3>
                
                <div class="notification-header-actions">
                    <?php // --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ --- ?>
                    <button class="notification-mark-all" id="notification-mark-all-btn" disabled>
                        Marca todas como leídas
                    </button>
                    <?php // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ --- ?>
                </div>
                
            </div>

            <div class="menu-list notification-list" id="notification-list-items">
                <div class="notification-placeholder" id="notification-placeholder">
                    <span class="material-symbols-rounded">notifications_off</span>
                    <span data-i18n="notifications.empty">No tienes notificaciones nuevas.</span>
                </div>

            </div>
        </div>
    </div>
    </div>
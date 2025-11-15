<?php


$defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";

$profileImageUrl = $_SESSION['profile_image_url'] ?? $defaultAvatar;

if (empty($profileImageUrl)) {
    $profileImageUrl = $defaultAvatar;
}

$usernameForAlt = $_SESSION['username'] ?? 'Usuario';

$userRole = $_SESSION['role'] ?? 'user';

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

            <?php ?>
            <div class="header-button header-notification-btn"
                data-action="toggleSectionMessages"
                data-tooltip="Mensajes"
                style="position: relative;">
                <span class="material-symbols-rounded">chat</span>
                
                <?php ?>
                <span class="notification-badge disabled" id="message-badge-count">0</span>
            </div>
            <?php ?>
            
            <div class="header-button"
                data-action="toggleModuleCreatePost" 
                data-tooltip="home.toolbar.createPost">
                <span class="material-symbols-rounded">add</span>
            </div>

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

                <?php ?>
                <div class="menu-link"
                     data-nav-js="true" 
                     data-href="<?php echo $basePath; ?>/profile/<?php echo htmlspecialchars($usernameForAlt); ?>">
                    <div class="menu-link-icon">
                        <span class="material-symbols-rounded">account_circle</span>
                    </div>
                    <div class="menu-link-text">
                        <span data-i18n="header.profile.myProfile">Mi Perfil</span>
                    </div>
                </div>
                <?php ?>
                
                <?php
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
                    <?php ?>
                    <button class="notification-mark-all" id="notification-mark-all-btn" disabled>
                        Marca todas como leídas
                    </button>
                    <?php ?>
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

    <?php ?>
    <div class="popover-module popover-module--anchor-right body-title disabled" data-module="moduleCreatePost">
        <div class="menu-content">
            <div class="menu-list">
                <div class="menu-link" data-action="toggleSectionCreatePublication">
                    <div class="menu-link-icon">
                        <span class="material-symbols-rounded">post_add</span>
                    </div>
                    <div class="menu-link-text">
                        <span data-i18n="home.popover.newPost">Crear publicación</span>
                    </div>
                </div>
                <div class="menu-link" data-action="toggleSectionCreatePoll">
                    <div class="menu-link-icon">
                        <span class="material-symbols-rounded">poll</span>
                    </div>
                    <div class="menu-link-text">
                        <span data-i18n="home.popover.newPoll">Crear encuesta</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php ?>
</div>
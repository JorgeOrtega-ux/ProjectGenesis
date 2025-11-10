<?php
// FILE: includes/sections/main/view-profile.php
// (MODIFICADO PARA SER UNA PLANTILLA DE PESTAÑAS)

global $pdo, $basePath;
$defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";
$userAvatar = $_SESSION['profile_image_url'] ?? $defaultAvatar;
$userId = $_SESSION['user_id'];

// $viewProfileData se carga desde config/routing/router.php
if (!isset($viewProfileData) || empty($viewProfileData)) {
    include dirname(__DIR__, 1) . '/main/404.php';
    return;
}

$profile = $viewProfileData;
$friendshipStatus = $viewProfileData['friendship_status'] ?? 'not_friends';
$currentTab = $viewProfileData['current_tab'] ?? 'posts';
$friendCount = $viewProfileData['friend_count'] ?? 0;

$roleIconMap = [
    'user' => 'person',
    'moderator' => 'shield_person',
    'administrator' => 'admin_panel_settings',
    'founder' => 'star'
];
$profileRoleIcon = $roleIconMap[$profile['role']] ?? 'person';

$isOwnProfile = ($profile['id'] == $userId);
$targetUserId = $profile['id'];

// --- ▼▼▼ INICIO DE NUEVO BLOQUE (BANNER) ▼▼▼ ---
$defaultBanner = $basePath . '/assets/images/default_banner.png'; // Asumiendo que tienes un banner por defecto
$profileBannerUrl = $profile['banner_url'] ?? $defaultBanner;
if (empty($profileBannerUrl)) $profileBannerUrl = $defaultBanner;
$isDefaultBanner = ($profileBannerUrl === $defaultBanner);
// --- ▲▲▲ FIN DE NUEVO BLOQUE (BANNER) ▲▲▲ ---


// --- Lógica de Estado (Online/Offline) ---
$is_actually_online = false;
try {
    $context = stream_context_create(['http' => ['timeout' => 0.5]]); 
    $jsonResponse = @file_get_contents('http://127.0.0.1:8766/get-online-users', false, $context);
    
    if ($jsonResponse !== false) {
        $data = json_decode($jsonResponse, true);
        if (isset($data['status']) && $data['status'] === 'ok' && isset($data['online_users'])) {
            $is_actually_online = in_array($profile['id'], $data['online_users']);
        }
    }
} catch (Exception $e) {
    logDatabaseError($e, 'view-profile - (ws_get_online_fail)');
}

$statusBadgeHtml = '';
if ($is_actually_online) {
    $statusBadgeHtml = '<div class="profile-status-badge online" data-user-id="' . htmlspecialchars($profile['id']) . '"><span class="status-dot"></span>Activo ahora</div>';
} elseif (!empty($profile['last_seen'])) {
    $lastSeenTime = new DateTime($profile['last_seen'], new DateTimeZone('UTC'));
    $currentTime = new DateTime('now', new DateTimeZone('UTC'));
    $interval = $currentTime->diff($lastSeenTime);

    $timeAgo = '';
    if ($interval->y > 0) { $timeAgo = ($interval->y == 1) ? '1 año' : $interval->y . ' años'; }
    elseif ($interval->m > 0) { $timeAgo = ($interval->m == 1) ? '1 mes' : $interval->m . ' meses'; }
    elseif ($interval->d > 0) { $timeAgo = ($interval->d == 1) ? '1 día' : $interval->d . ' días'; }
    elseif ($interval->h > 0) { $timeAgo = ($interval->h == 1) ? '1 h' : $interval->h . ' h'; }
    elseif ($interval->i > 0) { $timeAgo = ($interval->i == 1) ? '1 min' : $interval->i . ' min'; }
    else { $timeAgo = 'unos segundos'; }
    
    $statusText = ($timeAgo === 'unos segundos') ? 'Activo hace unos momentos' : "Activo hace $timeAgo";
    $statusBadgeHtml = '<div class="profile-status-badge offline" data-user-id="' . htmlspecialchars($profile['id']) . '">' . htmlspecialchars($statusText) . '</div>';
}
// --- Fin Lógica de Estado ---

?>
<style>
/* --- ESTILOS TEMPORALES (SOLO PARA ESTA DEMO, MOVER A CSS) --- */
.profile-banner {
    position: relative; /* Necesario para los botones */
}
.profile-banner-actions {
    position: absolute;
    top: 16px;
    right: 16px;
    z-index: 2;
    display: flex;
    gap: 8px;
}
.profile-banner-actions .component-button {
    background-color: #ffffff;
    border-color: #00000020;
    color: #000000;
    display: flex; /* Para mostrar icono y texto */
    align-items: center;
    gap: 8px;
    padding: 0 16px; /* Ajuste para botones con icono */
}
.profile-banner-actions .component-button .material-symbols-rounded {
    font-size: 20px;
}
.profile-banner-actions .component-button.danger {
    background-color: #fbebee;
    border-color: #ef9a9a;
    color: #c62828;
}
.profile-banner-actions .component-button.danger:hover {
    background-color: #f8e0e0;
}
.profile-banner-actions .component-button:hover {
    background-color: #f5f5fa;
}
.profile-banner-actions > div {
    display: flex;
    gap: 8px;
}
.profile-banner-actions > div.disabled {
    display: none;
}
.profile-banner-actions > div.active {
    display: flex;
}
/* --- FIN ESTILOS TEMPORALES --- */


.profile-info-layout {
    display: flex;
    flex-direction: row;
    width: 100%;
    gap: 16px;
    padding: 16px;
    min-height: 285px;
    background-color: #ffffff;
    border: 1px solid #00000020;
    border-radius: 12px;
}
.profile-info-menu {
    flex: 0 0 240px; /* Base de 240px, no crece, no se encoge */
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding: 8px;
    border-right: 1px solid #00000020;
}
.profile-info-menu h3 {
    font-size: 18px;
    font-weight: 700;
    color: #1f2937;
    padding: 8px 12px;
}
.profile-info-button {
    display: flex;
    align-items: center;
    width: 100%;
    height: 40px;
    padding: 0 12px;
    border: none;
    background-color: transparent;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 600;
    color: #1f2937;
    cursor: pointer;
    text-align: left;
    transition: background-color 0.2s;
    text-decoration: none; /* Añadido para <a> */
}
.profile-info-button:hover {
    background-color: #f5f5fa;
}
.profile-info-button.active {
    background-color: #000;
    color: #ffffff;
}
.profile-info-content {
    flex: 1 1 auto; /* Crece y se encoge */
    padding: 16px;
    min-width: 0; /* Permite que se encoja */
}
.profile-info-content [data-info-tab] {
    display: none;
}
.profile-info-content [data-info-tab].active {
    display: block;
}
.info-row {
    margin-bottom: 16px;
}
.info-row-label {
    font-size: 13px;
    font-weight: 600;
    color: #6b7280;
    margin-bottom: 4px;
    text-transform: uppercase;
}
.info-row-value {
    font-size: 15px;
    color: #1f2937;
    font-weight: 500;
}

@media (max-width: 768px) {
    .profile-info-layout {
        flex-direction: column;
    }
    .profile-info-menu {
        flex-basis: auto; /* Resetea la base */
        width: 100%;
        border-right: none;
        border-bottom: 1px solid #00000020;
        padding: 0 0 8px 0;
    }
    .profile-info-content {
        padding: 8px 0 0 0;
    }
}

/* --- ▼▼▼ INICIO DE CORRECCIONES CSS ▼▼▼ --- */
            
/* 1. Unificar altura de badges de rol y estado */
.profile-role-badge,
.profile-status-badge {
    min-height: 27px; /* Altura mínima (padding 4*2 + font ~16px + border 1*2) */
    box-sizing: border-box; /* Incluir padding y borde en la altura */
    gap: 6px; /* Estandarizar el 'gap' */
    margin-top: 8px; /* Asegurar que ambos tengan el mismo margen */
    padding: 4px 8px; /* Asegurar padding uniforme */
}

/* 2. Crear badges transparentes para meta-datos */
.profile-meta-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 500; /* Un poco menos que 'bold' para diferenciar */
    padding: 4px 8px;
    border-radius: 50px;
    margin-top: 8px;
    background-color: transparent;
    color: #6b7280; /* Usar el color del texto meta */
    border: 1px solid #00000020;
    min-height: 27px; /* Misma altura que los otros badges */
    box-sizing: border-box;
}
.profile-meta-badge .material-symbols-rounded {
    font-size: 16px;
}
.profile-meta-badge strong {
    font-weight: 700; /* Hacer el número de amigos más grueso */
    color: #1f2937;
}
/* --- ▲▲▲ FIN DE CORRECCIONES CSS ▲▲▲ --- */
</style>

<div class="section-content overflow-y <?php echo ($CURRENT_SECTION === 'view-profile') ? 'active' : 'disabled'; ?>" data-section="view-profile">

    <div class="page-toolbar-container" id="view-profile-toolbar-container">
        <div class="page-toolbar-floating">
            <div class="toolbar-action-default">
                <div class="page-toolbar-left">
                    <button type="button"
                        class="page-toolbar-button"
                        data-action="toggleSectionHome" 
                        data-tooltip="create_publication.backTooltip">
                        <span class="material-symbols-rounded">arrow_back</span>
                    </button>
                </div>
                
                <div class="page-toolbar-right profile-actions" data-user-id="<?php echo $profile['id']; ?>">
                    <?php if ($isOwnProfile): ?>
                        <button type="button"
                            class="page-toolbar-button"
                            data-action="toggleSectionSettingsProfile" 
                            data-tooltip="header.profile.settings">
                            <span class="material-symbols-rounded">edit</span>
                        </button>
                    <?php else: ?>
                        <?php if ($friendshipStatus === 'not_friends'): ?>
                            <button type="button" class="component-button component-button--primary" data-action="friend-send-request" data-user-id="<?php echo $profile['id']; ?>">
                                <span class="material-symbols-rounded">person_add</span>
                                <span data-i18n="friends.sendRequest">Agregar</span>
                            </button>
                        <?php elseif ($friendshipStatus === 'pending_sent'): ?>
                            <button type="button" class="component-button" data-action="friend-cancel-request" data-user-id="<?php echo $profile['id']; ?>">
                                <span class="material-symbols-rounded">close</span>
                                <span data-i18n="friends.cancelRequest">Cancelar</span>
                            </button>
                        <?php elseif ($friendshipStatus === 'pending_received'): ?>
                            <button type="button" class="component-button component-button--primary" data-action="friend-accept-request" data-user-id="<?php echo $profile['id']; ?>">
                                <span class="material-symbols-rounded">check</span>
                                <span data-i18n="friends.acceptRequest">Aceptar</span>
                            </button>
                            <button type="button" class="component-button" data-action="friend-decline-request" data-user-id="<?php echo $profile['id']; ?>">
                                <span class="material-symbols-rounded">close</span>
                                <span data-i18n="friends.declineRequest">Rechazar</span>
                            </button>
                        <?php elseif ($friendshipStatus === 'friends'): ?>
                            <button type="button" class="component-button" data-action="friend-remove" data-user-id="<?php echo $profile['id']; ?>">
                                <span class="material-symbols-rounded">person_remove</span>
                                <span data-i18n="friends.removeFriend">Eliminar</span>
                            </button>
                        <?php endif; ?>

                    <?php endif; ?>
                </div>
                </div>
        </div>
    </div>
    
    <div class="component-wrapper">

        <div class="profile-header-card" id="profile-banner-section">
            
            <div class="profile-banner" id="profile-banner-preview" style="background-image: url('<?php echo htmlspecialchars($profileBannerUrl); ?>');">
                <?php if ($isOwnProfile): ?>
                    <input type="file" class="visually-hidden" id="profile-banner-upload-input" name="banner" accept="image/png, image/jpeg, image/gif, image/webp">
                    
                    <div class="profile-banner-actions">
                        
                        <div id="banner-actions-default" class="<?php echo $isDefaultBanner ? 'active' : 'disabled'; ?>">
                            <button type="button" class="component-button" id="profile-banner-upload-trigger">
                                <span class="material-symbols-rounded">photo_camera</span>
                                <span>Subir banner</span>
                            </button>
                        </div>
                        
                        <div id="banner-actions-custom" class="<?php echo !$isDefaultBanner ? 'active' : 'disabled'; ?>">
                            <button type="button" class="component-button danger" id="profile-banner-remove-trigger">
                                <span class="material-symbols-rounded">delete</span>
                                <span>Eliminar</span>
                            </button>
                            <button type="button" class="component-button" id="profile-banner-change-trigger">
                                <span class="material-symbols-rounded">edit</span>
                                <span>Cambiar</span>
                            </button>
                        </div>
                        
                        <div id="banner-actions-preview" class="disabled">
                            <button type="button" class="component-button" id="profile-banner-cancel-trigger">
                                <span>Cancelar</span>
                            </button>
                            <button type="button" class="component-button" id="profile-banner-save-trigger-btn">
                                <span>Guardar</span>
                            </button>
                        </div>
                        
                    </div>
                <?php endif; ?>
            </div>

            <div class="profile-header-content">
        <div class="profile-avatar-container">
                    <div class="component-card__avatar" data-role="<?php echo htmlspecialchars($profile['role']); ?>">
                        <img src="<?php echo htmlspecialchars($profile['profile_image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($profile['username']); ?>" 
                             class="component-card__avatar-image">
                    </div>
                </div>

                <div class="profile-info">
                    <h1 class="profile-username"><?php echo htmlspecialchars($profile['username']); ?></h1>
                    
                    <div>
                        <div class="profile-role-badge" data-role="<?php echo htmlspecialchars($profile['role']); ?>">
                            <span class="material-symbols-rounded"><?php echo $profileRoleIcon; ?></span>
                            <span><?php echo htmlspecialchars(ucfirst($profile['role'])); ?></span>
                        </div>

                        <?php 
                        echo $statusBadgeHtml; 
                        ?>
                    </div>

                    <div class="profile-meta" style="display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-top: 0;">
                        <div class="profile-meta-badge">
                            <span class="material-symbols-rounded">calendar_today</span>
                            <span>Se unió el <?php echo date('d/m/Y', strtotime($profile['created_at'])); ?></span>
                        </div>
                        <div class="profile-meta-badge">
                            <span class="material-symbols-rounded">group</span>
                            <span><strong><?php echo $friendCount; ?></strong> Amigos</span>
                        </div>
                    </div>
                    </div>
                
                </div>
        </div>

        <?php // --- ▼▼▼ INICIO DE MODIFICACIÓN (Barra de Navegación) ▼▼▼ --- ?>
        <div class="profile-nav-bar">
            <div class="profile-nav-left">
                <?php
                    $usernameUrl = $basePath . '/profile/' . htmlspecialchars($profile['username']);
                    $postsUrl = $usernameUrl; // URL base es "posts"
                    $infoUrl = $usernameUrl . '/info';
                    $amigosUrl = $usernameUrl . '/amigos';
                    $fotosUrl = $usernameUrl . '/fotos';
                    $likesUrl = $usernameUrl . '/likes';
                    $bookmarksUrl = $usernameUrl . '/bookmarks';
                ?>
                <div data-href="<?php echo $postsUrl; ?>" 
                   class="profile-nav-button <?php echo ($currentTab === 'posts') ? 'active' : ''; ?>" 
                   data-nav-js="true">
                    Publicaciones
                </div>
                <div data-href="<?php echo $infoUrl; ?>" 
                   class="profile-nav-button <?php echo ($currentTab === 'info') ? 'active' : ''; ?>" 
                   data-nav-js="true">
                    Informacion
                </div>
                <div data-href="<?php echo $amigosUrl; ?>" 
                   class="profile-nav-button <?php echo ($currentTab === 'amigos') ? 'active' : ''; ?>" 
                   data-nav-js="true">
                    Amigos
                </div>
                <div data-href="<?php echo $fotosUrl; ?>" 
                   class="profile-nav-button <?php echo ($currentTab === 'fotos') ? 'active' : ''; ?>" 
                   data-nav-js="true">
                    Fotos
                </div>
            </div>
            <div class="profile-nav-right">
                <button type="button" class="header-button" data-action="toggleModuleProfileMore">
                    <span class="material-symbols-rounded">more_vert</span>
                </button>
                
                <div class="popover-module popover-module--anchor-right body-title disabled" data-module="moduleProfileMore">
                    <div class="menu-content">
                        <div class="menu-list">
                            <?php if ($isOwnProfile): // Mostrar solo en el perfil propio ?>
                                <a class="menu-link <?php echo ($currentTab === 'likes') ? 'active' : ''; ?>" href="<?php echo $likesUrl; ?>" data-nav-js="true">
                                    <div class="menu-link-icon"><span class="material-symbols-rounded">favorite</span></div>
                                    <div class="menu-link-text"><span data-i18n="profile.tabs.likes">Favoritos</span></div>
                                </a>
                                <a class="menu-link <?php echo ($currentTab === 'bookmarks') ? 'active' : ''; ?>" href="<?php echo $bookmarksUrl; ?>" data-nav-js="true">
                                    <div class="menu-link-icon"><span class="material-symbols-rounded">bookmark</span></div>
                                    <div class="menu-link-text"><span data-i18n="profile.tabs.bookmarks">Guardados</span></div>
                                </a>
                            <?php endif; ?>
                            
                            </div>
                    </div>
                </div>
            </div>
        </div>
        <?php // --- ▲▲▲ FIN DE MODIFICACIÓN (Barra de Navegación) ▲▲▲ --- ?>


        <div class="profile-content-container">
            <?php
            // $currentTab es definido en router.php
            // $viewProfileData (que contiene todo) se pasa a los sub-archivos
            
            $tabBasePath = dirname(__FILE__) . '/profile-tabs/';

            switch ($currentTab) {
                case 'info':
                    $tabFile = $tabBasePath . 'view-profile-information.php';
                    break;
                case 'amigos':
                    $tabFile = $tabBasePath . 'view-profile-friends.php';
                    break;
                case 'fotos':
                    $tabFile = $tabBasePath . 'view-profile-photos.php';
                    break;
                case 'likes':
                case 'bookmarks':
                case 'posts':
                default:
                    // 'posts', 'likes', 'bookmarks' usan el mismo layout de 2 columnas
                    $tabFile = $tabBasePath . 'view-profile-posts.php';
                    break;
            }
            
            if (file_exists($tabFile)) {
                include $tabFile;
            } else {
                // Fallback por si el archivo de la pestaña no existe
                echo '<div class="component-card"><p>Error: No se pudo cargar el contenido de la pestaña.</p></div>';
            }
            ?>
        </div>
        
        </div>
</div>
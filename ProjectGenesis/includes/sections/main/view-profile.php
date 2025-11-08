<?php
// FILE: includes/sections/main/view-profile.php

// $viewProfileData se carga desde config/routing/router.php con DATOS REALES.
global $pdo, $basePath;
$defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";
$userAvatar = $_SESSION['profile_image_url'] ?? $defaultAvatar;
$userId = $_SESSION['user_id'];

if (!isset($viewProfileData) || empty($viewProfileData)) {
    include dirname(__DIR__, 1) . '/main/404.php';
    return;
}

$profile = $viewProfileData;
$publications = $viewProfileData['publications'];
$friendshipStatus = $viewProfileData['friendship_status'] ?? 'not_friends'; // Cargado en router.php

$roleIconMap = [
    'user' => 'person',
    'moderator' => 'shield_person',
    'administrator' => 'admin_panel_settings',
    'founder' => 'star'
];
$profileRoleIcon = $roleIconMap[$profile['role']] ?? 'person';

$isOwnProfile = ($profile['id'] == $userId);
?>
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
                
                <!-- --- ▼▼▼ INICIO DE MODIFICACIÓN (Botones de Acción) ▼▼▼ --- -->
                <div class="page-toolbar-right profile-actions" data-user-id="<?php echo $profile['id']; ?>" style="display: flex; gap: 8px;">
                    <?php if ($isOwnProfile): ?>
                        <button type="button"
                            class="page-toolbar-button"
                            data-action="toggleSectionSettingsProfile" 
                            data-tooltip="header.profile.settings">
                            <span class="material-symbols-rounded">edit</span>
                        </button>
                    <?php else: ?>
                        <!-- Lógica de botones de amistad -->
                        <?php if ($friendshipStatus === 'not_friends'): ?>
                            <button type="button" class="component-button component-button--primary" data-action="friend-send-request" data-user-id="<?php echo $profile['id']; ?>" style="height: 36px; padding: 0 12px; gap: 6px; display: flex; align-items: center;">
                                <span class="material-symbols-rounded" style="font-size: 20px;">person_add</span>
                                <span data-i18n="friends.sendRequest" style="font-size: 13px;">Agregar</span>
                            </button>
                        <?php elseif ($friendshipStatus === 'pending_sent'): ?>
                            <button type="button" class="component-button" data-action="friend-cancel-request" data-user-id="<?php echo $profile['id']; ?>" style="height: 36px; padding: 0 12px; gap: 6px; display: flex; align-items: center;">
                                <span class="material-symbols-rounded" style="font-size: 20px;">close</span>
                                <span data-i18n="friends.cancelRequest" style="font-size: 13px;">Cancelar</span>
                            </button>
                        <?php elseif ($friendshipStatus === 'pending_received'): ?>
                            <button type="button" class="component-button component-button--primary" data-action="friend-accept-request" data-user-id="<?php echo $profile['id']; ?>" style="height: 36px; padding: 0 12px; gap: 6px; display: flex; align-items: center;">
                                <span class="material-symbols-rounded" style="font-size: 20px;">check</span>
                                <span data-i18n="friends.acceptRequest" style="font-size: 13px;">Aceptar</span>
                            </button>
                            <button type="button" class="component-button" data-action="friend-decline-request" data-user-id="<?php echo $profile['id']; ?>" style="height: 36px; padding: 0 12px; gap: 6px; display: flex; align-items: center;">
                                <span class="material-symbols-rounded" style="font-size: 20px;">close</span>
                                <span data-i18n="friends.declineRequest" style="font-size: 13px;">Rechazar</span>
                            </button>
                        <?php elseif ($friendshipStatus === 'friends'): ?>
                            <button type="button" class="component-button" data-action="friend-remove" data-user-id="<?php echo $profile['id']; ?>" style="height: 36px; padding: 0 12px; gap: 6px; display: flex; align-items: center;">
                                <span class="material-symbols-rounded" style="font-size: 20px;">person_remove</span>
                                <span data-i18n="friends.removeFriend" style="font-size: 13px;">Eliminar</span>
                            </button>
                        <?php endif; ?>

                    <?php endif; ?>
                </div>
                <!-- --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ --- -->
            </div>
        </div>
    </div>
    
    <div class="component-wrapper" style="padding-top: 82px;">

        <div class="profile-header-card">
            <div class="profile-banner"></div>
            <div class="profile-header-content">
                <div class="profile-avatar-container">
                    <div class="component-card__avatar" data-role="<?php echo htmlspecialchars($profile['role']); ?>" style="width: 100%; height: 100%;">
                        <img src="<?php echo htmlspecialchars($profile['profile_image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($profile['username']); ?>" 
                             class="component-card__avatar-image">
                    </div>
                </div>

                <div class="profile-info">
                    <h1 class="profile-username"><?php echo htmlspecialchars($profile['username']); ?></h1>
                    
                    <div class="profile-role-badge" data-role="<?php echo htmlspecialchars($profile['role']); ?>">
                        <span class="material-symbols-rounded"><?php echo $profileRoleIcon; ?></span>
                        <span><?php echo htmlspecialchars(ucfirst($profile['role'])); ?></span>
                    </div>

                    <p class="profile-meta">
                        Se unió el <?php echo date('d/m/Y', strtotime($profile['created_at'])); ?>
                    </p>
                </div>
                
                <div class="profile-tabs">
                    <div class="profile-tab active">
                        <span data-i18n="create_publication.post">Publicaciones</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-list-container" style="display: flex; flex-direction: column; gap: 16px;">
            
            <?php if (!empty($publications)): ?>
                <?php foreach ($publications as $post): ?>
                    <?php
                    include dirname(__DIR__, 2) . '/components/publication-card.php';
                    ?>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="component-card">
                    <div class="component-card__content">
                        <div class="component-card__icon">
                            <span class="material-symbols-rounded">feed</span>
                        </div>
                        <div class="component-card__text">
                            <h2 class="component-card__title">Sin publicaciones</h2>
                            <?php if ($isOwnProfile): ?>
                                <p class="component-card__description">Aún no has publicado nada. ¡Comparte algo con la comunidad!</p>
                            <?php else: ?>
                                <p class="component-card__description"><?php echo htmlspecialchars($profile['username']); ?> aún no ha publicado nada.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>
<?php
// FILE: includes/sections/main/view-profile.php (NUEVO ARCHIVO)

// $viewProfileData (con datos falsos) se carga desde config/routing/router.php
global $pdo, $basePath;
$defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";
$userAvatar = $_SESSION['profile_image_url'] ?? $defaultAvatar;
$userId = $_SESSION['user_id'];

// Verificar si los datos del perfil existen (cargados por el router)
if (!isset($viewProfileData) || empty($viewProfileData)) {
    // Si no se encontraron datos (ej. en el futuro, si el usuario no existe), mostrar 404
    include dirname(__DIR__, 1) . '/main/404.php';
    return; // Detener la ejecución
}

$profile = $viewProfileData;
$publications = $viewProfileData['publications'];

// Lógica de íconos de roles (para la insignia)
$roleIconMap = [
    'user' => 'person',
    'moderator' => 'shield_person',
    'administrator' => 'admin_panel_settings',
    'founder' => 'star'
];
$profileRoleIcon = $roleIconMap[$profile['role']] ?? 'person';
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
            </div>
        </div>
    </div>
    
    <div class="component-wrapper" style="padding-top: 82px;">

        <div class="profile-header-card">
            <div class="profile-banner">
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
                    // Incluimos el componente reutilizable
                    // Asume que $post, $defaultAvatar, $userId, $userAvatar están definidos
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
                            <p class="component-card__description"><?php echo htmlspecialchars($profile['username']); ?> aún no ha publicado nada.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>
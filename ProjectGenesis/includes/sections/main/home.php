<?php
// FILE: includes/sections/main/home.php

// (Se asume que $pdo y $_SESSION['user_id'] están disponibles desde config.php y bootstrapper.php)

$user_groups = [];
try {
    if (isset($_SESSION['user_id'], $pdo)) {
        $stmt = $pdo->prepare(
            "SELECT g.name 
             FROM groups g
             JOIN user_groups ug ON g.id = ug.group_id
             WHERE ug.user_id = ?"
        );
        $stmt->execute([$_SESSION['user_id']]);
        $user_groups = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    logDatabaseError($e, 'home.php - load user groups');
    // $user_groups se mantendrá vacío y se mostrará el mensaje de "sin grupos"
}

?>
<div class="section-content overflow-y <?php echo ($CURRENT_SECTION === 'home') ? 'active' : 'disabled'; ?>" data-section="home">
    
    <div class="component-wrapper">
        <?php if (empty($user_groups)): ?>
            <!-- Estado 1: El usuario NO tiene grupos -->
            <div class="component-card component-card--column" style="margin-top: 24px; align-items: center; text-align: center; padding: 32px;">
                <div class="component-card__icon" style="background-color: transparent; width: 60px; height: 60px; margin-bottom: 16px; border: none;">
                    <span class="material-symbols-rounded" style="font-size: 60px; color: #6b7280;">groups</span>
                </div>
                
                <!-- (Añadir a es-mx.json) home.noGroups.title -->
                <h2 class="component-card__title" style="font-size: 20px;" data-i18n="home.noGroups.title">Aún no estás en ningún grupo</h2>
                
                <!-- (Añadir a es-mx.json) home.noGroups.description -->
                <p class="component-card__description" style="max-width: 300px; margin-top: 8px;" data-i18n="home.noGroups.description">
                    Únete a un grupo con un código de invitación o explora grupos públicos.
                </p>

                <div class="component-card__actions" style="margin-top: 24px; gap: 12px; width: 100%; justify-content: center;">
                    <!-- (Añadir a es-mx.json) home.noGroups.joinButton -->
                    <button type="button" 
                       class="component-action-button component-action-button--primary" 
                       data-action="toggleSectionJoinGroup" 
                       data-i18n="home.noGroups.joinButton">
                       Unirme a un grupo
                    </button>
                    <!-- (Añadir a es-mx.json) home.noGroups.exploreButton -->
                    <button type="button" 
                       class="component-action-button component-action-button--secondary" 
                       data-action="toggleSectionExplorer" 
                       data-i18n="home.noGroups.exploreButton">
                       Explorar grupos
                    </button>
                </div>
            </div>

        <?php else: ?>
            <!-- Estado 2: El usuario SÍ tiene grupos -->
            <div class="component-header-card">
                <!-- (Añadir a es-mx.json) home.myGroups.title -->
                <h1 class="component-page-title" data-i18n="home.myGroups.title">Mis Grupos</h1>
                <!-- (Añadir a es-mx.json) home.myGroups.description -->
                <p class="component-page-description" data-i18n="home.myGroups.description">Esta es tu página principal. Aquí verás la actividad de tus grupos.</p>
            </div>

            <!-- Lista de tarjetas de grupo -->
            <div class="card-list-container" style="margin-top: 16px; display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px;">
                <?php foreach ($user_groups as $group): ?>
                    <div class="component-card" style="padding: 16px;">
                        <div class="component-card__content">
                            <div class="component-card__icon" style="background-color: #f5f5fa;">
                                <span class="material-symbols-rounded">group</span>
                            </div>
                            <div class="component-card__text">
                                <h2 class="component-card__title" style="font-size: 16px;"><?php echo htmlspecialchars($group['name']); ?></h2>
                                <p class="component-card__description">Ver publicaciones...</p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>
    </div>

</div>
<?php
// FILE: includes/sections/main/my-groups.php
// (Este es un ARCHIVO NUEVO)

// 1. Lógica para obtener los grupos del usuario
$user_groups = [];
try {
    if (isset($_SESSION['user_id'], $pdo)) {
        $stmt = $pdo->prepare(
            "SELECT g.name, g.group_type 
             FROM groups g
             JOIN user_groups ug ON g.id = ug.group_id
             WHERE ug.user_id = ?
             ORDER BY g.name"
        );
        $stmt->execute([$_SESSION['user_id']]);
        $user_groups = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    logDatabaseError($e, 'my-groups.php - load user groups');
    // $user_groups se mantendrá vacío y se mostrará el mensaje de "sin grupos"
}

// 2. Mapa de iconos para los tipos de grupo
$groupIconMap = [
    'municipio' => 'account_balance',
    'universidad' => 'school',
    'default' => 'group'
];

?>

<div class="section-content overflow-y <?php echo ($CURRENT_SECTION === 'my-groups') ? 'active' : 'disabled'; ?>" data-section="my-groups">
    
    <div class="component-wrapper">

        <div class="component-header-card">
            <h1 class="component-page-title" data-i18n="mygroups.title">Mis Grupos</h1>
            <p class="component-page-description" data-i18n="mygroups.description">
                Aquí puedes ver y gestionar todos los grupos a los que perteneces.
            </p>
        </div>

        <?php if (empty($user_groups)): ?>
            
            <div class="component-card component-card--column" style="margin-top: 16px; align-items: center; text-align: center; padding: 32px;">
                <div class="component-card__icon" style="background-color: transparent; width: 60px; height: 60px; margin-bottom: 16px; border: none;">
                    <span class="material-symbols-rounded" style="font-size: 60px; color: #6b7280;">groups</span>
                </div>
                <h2 class="component-card__title" style="font-size: 20px;" data-i18n="mygroups.noGroups.title">
                    Aún no estás en ningún grupo
                </h2>
                <p class="component-card__description" style="max-width: 300px; margin-top: 8px;" data-i18n="mygroups.noGroups.description">
                    Únete a un grupo usando el botón de 'Unirme a un grupo' en el encabezado.
                </p>
                <div class="component-card__actions" style="margin-top: 24px; gap: 12px; width: 100%; justify-content: center; display: flex;">
                    <button type="button" 
                       class="component-action-button component-action-button--primary" 
                       data-action="toggleSectionJoinGroup" 
                       data-i18n="mygroups.noGroups.joinButton">
                       Unirme a un grupo
                    </button>
                </div>
            </div>

        <?php else: ?>
            
            <div class="card-list-container" style="padding-top: 16px;">
                
                <?php foreach ($user_groups as $group): ?>
                    <?php
                        $iconType = $group['group_type'] ?? 'default';
                        $iconName = $groupIconMap[$iconType] ?? $groupIconMap['default'];
                    ?>
                    <div class="home-community-card">
                        <div class="home-community-icon">
                            <span class="material-symbols-rounded"><?php echo $iconName; ?></span>
                        </div>
                        <div class="home-community-text">
                            <h4><?php echo htmlspecialchars($group['name']); ?></h4>
                            <p data-i18n="mygroups.card.view">Ver publicaciones...</p>
                        </div>
                    </div>
                <?php endforeach; ?>

            </div> <?php endif; ?>

    </div> </div>
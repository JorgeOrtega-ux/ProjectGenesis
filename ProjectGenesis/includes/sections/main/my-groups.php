<?php
// FILE: includes/sections/main/my-groups.php
// (Este es un ARCHIVO MODIFICADO)

// 1. Lógica para obtener los grupos del usuario
$user_groups = [];
try {
    if (isset($_SESSION['user_id'], $pdo)) {
        
        // --- ▼▼▼ INICIO DE MODIFICACIÓN (Consulta SQL) ▼▼▼ ---
        // Ahora también obtenemos el conteo de miembros y la privacidad
        $stmt = $pdo->prepare(
            "SELECT 
                g.id,
                g.name, 
                g.group_type,
                g.privacy,
                (SELECT COUNT(ug_inner.user_id) 
                 FROM user_groups ug_inner 
                 WHERE ug_inner.group_id = g.id) AS member_count
             FROM groups g
             JOIN user_groups ug_main ON g.id = ug_main.group_id
             WHERE ug_main.user_id = ?
             GROUP BY g.id, g.name, g.group_type, g.privacy
             ORDER BY g.name"
        );
        $stmt->execute([$_SESSION['user_id']]);
        $user_groups = $stmt->fetchAll();
        // --- ▲▲▲ FIN DE MODIFICACIÓN (Consulta SQL) ▲▲▲ ---
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
            
            <!-- --- ▼▼▼ INICIO DE MODIFICACIÓN (Estructura de Tarjeta) ▼▼▼ --- -->
            <div class="card-list-container">
                
                <?php foreach ($user_groups as $group): ?>
                    <?php
                        // Lógica para iconos y texto
                        $iconType = $group['group_type'] ?? 'default';
                        $iconName = $groupIconMap[$iconType] ?? $groupIconMap['default'];
                        $privacyIcon = ($group['privacy'] === 'publico') ? 'public' : 'lock';
                        $memberTextKey = ($group['member_count'] == 1) ? 'mygroups.card.member' : 'mygroups.card.members';
                        $privacyTextKey = 'mygroups.card.privacy' . ucfirst($group['privacy']);
                    ?>
                    
                    <!-- INICIO DE LA NUEVA TARJETA (basada en tu ejemplo) -->
                    <div class="card-item" style="gap: 16px; padding: 16px; cursor: pointer;" data-group-id="<?php echo $group['id']; ?>">
                        
                        <!-- Icono del Grupo -->
                        <div class="component-card__icon" style="width: 50px; height: 50px; flex-shrink: 0; background-color: #f5f5fa;">
                            <span class="material-symbols-rounded" style="font-size: 28px;"><?php echo $iconName; ?></span>
                        </div>

                        <!-- Detalles (Título y Badges) -->
                        <div class="card-item-details">

                            <!-- Título (usando el estilo de tu ejemplo) -->
                            <div class="card-detail-item card-detail-item--full" style="border: none; padding: 0; background: none;">
                                <span class="card-detail-value" style="font-size: 16px; font-weight: 600;"><?php echo htmlspecialchars($group['name']); ?></span>
                            </div>

                            <!-- Badge de Miembros -->
                            <div class="card-detail-item">
                                <span class="material-symbols-rounded" style="font-size: 16px; color: #6b7280;">group</span>
                                <span class="card-detail-value"><?php echo htmlspecialchars($group['member_count']); ?> <span data-i18n="<?php echo $memberTextKey; ?>"></span></span>
                            </div>
                            
                            <!-- Badge de Privacidad -->
                            <div class="card-detail-item">
                                <span class="material-symbols-rounded" style="font-size: 16px; color: #6b7280;"><?php echo $privacyIcon; ?></span>
                                <span class="card-detail-value" data-i18n="<?php echo $privacyTextKey; ?>"></span>
                            </div>
                        </div>
                    </div>
                    <!-- FIN DE LA NUEVA TARJETA -->

                <?php endforeach; ?>

            </div>
            <!-- --- ▲▲▲ FIN DE MODIFICACIÓN (Estructura de Tarjeta) ▲▲▲ --- -->
        <?php endif; ?>

    </div>
</div>
<?php
// FILE: includes/sections/main/explorer.php
// (Sección actualizada para mostrar comunidades públicas como tarjetas)

global $pdo, $basePath; // Aseguramos acceso a $pdo y $basePath
$publicCommunities = [];
$joinedCommunityIds = [];
$userId = $_SESSION['user_id'] ?? 0; // Obtener el ID del usuario actual

try {
    // 1. Obtener todas las comunidades públicas
    $stmt_public = $pdo->prepare("SELECT id, name FROM communities WHERE privacy = 'public' ORDER BY name ASC");
    $stmt_public->execute();
    $publicCommunities = $stmt_public->fetchAll();

    // 2. Obtener los IDs de las comunidades a las que ya pertenece el usuario
    if ($userId > 0) {
        $stmt_joined = $pdo->prepare("SELECT community_id FROM user_communities WHERE user_id = ?");
        $stmt_joined->execute([$userId]);
        $joinedCommunityIds = $stmt_joined->fetchAll(PDO::FETCH_COLUMN);
    }
    
} catch (PDOException $e) {
    logDatabaseError($e, 'router - explorer.php');
    // Dejar los arrays vacíos en caso de error
}
?>

<div class="section-content overflow-y <?php echo ($CURRENT_SECTION === 'explorer') ? 'active' : 'disabled'; ?>" data-section="explorer">
    
    <div class="component-wrapper">

        <div class="component-header-card">
            <h1 class="component-page-title" data-i18n="sidebar.main.explore">Explorar Comunidades</h1>
            <p class="component-page-description" data-i18n="join_group.publicDesc">Únete a una comunidad abierta.</p>
        </div>

        <?php if (empty($publicCommunities)): ?>
            <div class="component-card">
                <div class="component-card__content">
                    <div class="component-card__icon">
                        <span class="material-symbols-rounded">search_off</span>
                    </div>
                    <div class="component-card__text">
                        <h2 class="component-card__title" data-i18n="join_group.noPublic">No hay comunidades públicas</h2>
                        <p class="component-card__description">No hay comunidades públicas disponibles en este momento.</p>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="card-list-container">
                <?php foreach ($publicCommunities as $community): ?>
                    <?php
                        // Comprobar si el usuario ya es miembro
                        $isMember = in_array($community['id'], $joinedCommunityIds);
                    ?>
                    <div class="component-card">
                        <div class="component-card__content">
                            <div class="component-card__icon">
                                <span class="material-symbols-rounded">group</span>
                            </div>
                            <div class="component-card__text">
                                <h2 class="component-card__title"><?php echo htmlspecialchars($community['name']); ?></h2>
                                </div>
                        </div>
                        <div class="component-card__actions">
                            <?php if ($isMember): ?>
                                <button type="button" 
                                        class="component-button danger" 
                                        data-action="leave-community" 
                                        data-community-id="<?php echo $community['id']; ?>"
                                        data-i18n="join_group.leave">
                                    Abandonar
                                </button>
                            <?php else: ?>
                                <button type="button" 
                                        class="component-button" 
                                        data-action="join-community" 
                                        data-community-id="<?php echo $community['id']; ?>"
                                        data-i18n="join_group.join">
                                    Unirme
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</div>
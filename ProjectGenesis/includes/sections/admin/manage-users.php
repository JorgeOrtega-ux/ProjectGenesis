<?php
// FILE: includes/sections/admin/manage-users.php

// --- ▼▼▼ INICIO DE LÓGICA PHP DE PAGINACIÓN (SIN CAMBIOS) ▼▼▼ ---
$usersList = [];
$defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";

// $adminCurrentPage es definido por config/router.php
$usersPerPage = 1; // 20 usuarios por página
$totalUsers = 0;
$totalPages = 1;

try {
    // 1. Contar el total de usuarios
    $totalUsersStmt = $pdo->prepare("SELECT COUNT(*) FROM users");
    $totalUsersStmt->execute();
    $totalUsers = (int)$totalUsersStmt->fetchColumn();
    
    if ($totalUsers > 0) {
        $totalPages = (int)ceil($totalUsers / $usersPerPage);
    }
    
    // 2. Asegurarse de que la página actual es válida
    if ($adminCurrentPage < 1) {
        $adminCurrentPage = 1;
    }
    if ($adminCurrentPage > $totalPages) {
        $adminCurrentPage = $totalPages;
    }
    
    // 3. Calcular el OFFSET
    $offset = ($adminCurrentPage - 1) * $usersPerPage;

    // 4. Obtener solo los usuarios para la página actual
    $stmt = $pdo->prepare(
        "SELECT id, username, email, profile_image_url, role, created_at, account_status 
         FROM users 
         ORDER BY created_at DESC
         LIMIT :limit OFFSET :offset"
    );
    
    // Usamos bindValue para asegurar que los tipos de datos son correctos
    $stmt->bindValue(':limit', $usersPerPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $usersList = $stmt->fetchAll();

} catch (PDOException $e) {
    logDatabaseError($e, 'admin - manage-users');
    // $usersList se quedará vacío y mostrará el mensaje de error de abajo.
}
// --- ▲▲▲ FIN DE LÓGICA PHP DE PAGINACIÓN ▲▲▲ ---
?>
<div class="section-content overflow-y <?php echo ($CURRENT_SECTION === 'admin-users') ? 'active' : 'disabled'; ?>" data-section="admin-users">
    
    <div class="admin-toolbar-floating" 
         data-current-page="<?php echo $adminCurrentPage; ?>" 
         data-total-pages="<?php echo $totalPages; ?>">
        
        <div class="admin-toolbar-left">
            <button type="button" class="admin-toolbar-button" disabled>
                <span class="material-symbols-rounded">search</span>
            </button>
            <button type="button" class="admin-toolbar-button" disabled>
                <span class="material-symbols-rounded">filter_list</span>
            </button>
        </div>
        
        <div class="admin-toolbar-right">
            <div class="admin-toolbar-pagination">
                <button type="button" class="admin-toolbar-button" 
                        data-action="admin-page-prev" 
                        <?php echo ($adminCurrentPage <= 1) ? 'disabled' : ''; ?>>
                    <span class="material-symbols-rounded">chevron_left</span>
                </button>
                
                <span class="admin-toolbar-page-text">
                    <?php echo $adminCurrentPage; ?> / <?php echo $totalPages; ?>
                </span>
                
                <button type="button" class="admin-toolbar-button" 
                        data-action="admin-page-next"
                        <?php echo ($adminCurrentPage >= $totalPages) ? 'disabled' : ''; ?>>
                    <span class="material-symbols-rounded">chevron_right</span>
                </button>
            </div>
        </div>
    </div>
    <div class="component-wrapper">

        <div class="component-header-card">
            <h1 class="component-page-title" data-i18n="admin.users.title">Gestionar Usuarios</h1>
            <p class="component-page-description" data-i18n="admin.users.description">Busca, edita o gestiona los roles de los usuarios.</p>
        </div>

        <?php // --- ▼▼▼ INICIO DEL BUCLE DE USUARIOS ▼▼▼ --- ?>
        <?php if (empty($usersList)): ?>
            
            <div class="component-card">
                <div class="component-card__content">
                    <div class="component-card__icon">
                        <span class="material-symbols-rounded">person_off</span>
                    </div>
                    <div class="component-card__text">
                        <h2 class="component-card__title">No se encontraron usuarios</h2>
                        <p class="component-card__description">No hay usuarios registrados en esta página o hubo un error al cargarlos.</p>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <div class="user-list-container"> <?php // Contenedor para la lista ?>
                <?php foreach ($usersList as $user): ?>
                    <?php
                        // --- ▼▼▼ LÓGICA DE AVATAR CORREGIDA ▼▼▼ ---
                        $avatarUrl = $user['profile_image_url'] ?? $defaultAvatar;
                        // Si está vacío (null o "") usamos el default
                        if (empty($avatarUrl)) { 
                            $avatarUrl = "https://ui-avatars.com/api/?name=" . urlencode($user['username']) . "&size=100&background=e0e0e0&color=ffffff";
                        }
                        // --- ▲▲▲ FIN DE LÓGICA DE AVATAR CORREGIDA ▲▲▲ ---
                    ?>
                    <div class="user-card-item"> <?php // Nueva clase para cada tarjeta de usuario ?>
                        
                        <div class="component-card__avatar" style="width: 50px; height: 50px; flex-shrink: 0;" data-role="<?php echo htmlspecialchars($user['role']); ?>">
                            <img src="<?php echo htmlspecialchars($avatarUrl); ?>"
                                 alt="<?php echo htmlspecialchars($user['username']); ?>"
                                 class="component-card__avatar-image">
                        </div>

                        <div class="user-card-details">
                            
                            <?php // --- ▼▼▼ ITEM CON CLASE "full" AÑADIDA ▼▼▼ --- ?>
                            <div class="user-card-detail-item user-card-detail-item--full">
                                <span class="user-card-detail-label">Nombre del usuario</span>
                                <span class="user-card-detail-value"><?php echo htmlspecialchars($user['username']); ?></span>
                            </div>
                            
                            <div class="user-card-detail-item">
                                <span class="user-card-detail-label">Rol</span>
                                <span class="user-card-detail-value"><?php echo htmlspecialchars(ucfirst($user['role'])); ?></span>
                            </div>
                            <div class="user-card-detail-item">
                                <span class="user-card-detail-label">Fecha de creación</span>
                                <span class="user-card-detail-value"><?php echo (new DateTime($user['created_at']))->format('d/m/Y'); ?></span>
                            </div>
                            
                            <?php if ($user['email']): ?>
                                <?php // --- ▼▼▼ ITEM CON CLASE "full" AÑADIDA ▼▼▼ --- ?>
                                <div class="user-card-detail-item user-card-detail-item--full">
                                    <span class="user-card-detail-label">Email</span>
                                    <span class="user-card-detail-value"><?php echo htmlspecialchars($user['email']); ?></span>
                                </div>
                            <?php endif; ?>

                            <div class="user-card-detail-item">
                                <span class="user-card-detail-label">Estado de la cuenta</span>
                                <span class="user-card-detail-value"><?php echo htmlspecialchars(ucfirst($user['account_status'])); ?></span>
                            </div>
                        </div>
                        
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php // --- ▲▲▲ FIN DEL BUCLE DE USUARIOS ▲▲▲ --- ?>

    </div>
</div>
<?php
// FILE: includes/sections/admin/manage-users.php

// --- ▼▼▼ INICIO DE LÓGICA PHP MODIFICADA ▼▼▼ ---
$usersList = [];
$defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";

// 1. OBTENER PARÁMETROS DE URL
$adminCurrentPage = (int)($_GET['p'] ?? 1);
if ($adminCurrentPage < 1) $adminCurrentPage = 1;

$searchQuery = trim($_GET['q'] ?? '');
$isSearching = !empty($searchQuery);
// --- ▲▲▲ FIN DE LÓGICA PHP MODIFICADA ▲▲▲ ---

$usersPerPage = 1; // 20 usuarios por página
$totalUsers = 0;
$totalPages = 1;

try {
    // --- ▼▼▼ INICIO DE SQL MODIFICADO ▼▼▼ ---
    
    // 2. Contar el total de usuarios (con filtro si existe)
    $sqlCount = "SELECT COUNT(*) FROM users";
    if ($isSearching) {
        $sqlCount .= " WHERE (username LIKE :query OR email LIKE :query)";
    }
    
    $totalUsersStmt = $pdo->prepare($sqlCount);
    
    if ($isSearching) {
        $searchParam = '%' . $searchQuery . '%';
        $totalUsersStmt->bindParam(':query', $searchParam, PDO::PARAM_STR);
    }
    
    $totalUsersStmt->execute();
    $totalUsers = (int)$totalUsersStmt->fetchColumn();
    // --- ▲▲▲ FIN DE SQL MODIFICADO ▲▲▲ ---
    
    if ($totalUsers > 0) {
        $totalPages = (int)ceil($totalUsers / $usersPerPage);
    } else {
        $totalPages = 1; // Si no hay usuarios, seguimos en la página 1
    }
    
    // 2. Asegurarse de que la página actual es válida
    if ($adminCurrentPage > $totalPages) {
        $adminCurrentPage = $totalPages;
    }
    
    // 3. Calcular el OFFSET
    $offset = ($adminCurrentPage - 1) * $usersPerPage;

    // --- ▼▼▼ INICIO DE SQL MODIFICADO ▼▼▼ ---
    // 4. Obtener los usuarios para la página actual (con filtro si existe)
    $sqlSelect = "SELECT id, username, email, profile_image_url, role, created_at, account_status 
                  FROM users";
    if ($isSearching) {
        $sqlSelect .= " WHERE (username LIKE :query OR email LIKE :query)";
    }
    $sqlSelect .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sqlSelect);
    
    if ($isSearching) {
        $stmt->bindParam(':query', $searchParam, PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $usersPerPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $usersList = $stmt->fetchAll();
    // --- ▲▲▲ FIN DE SQL MODIFICADO ▲▲▲ ---

} catch (PDOException $e) {
    logDatabaseError($e, 'admin - manage-users');
}
?>
<div class="section-content overflow-y <?php echo ($CURRENT_SECTION === 'admin-users') ? 'active' : 'disabled'; ?>" data-section="admin-users">
    
    <div class="admin-toolbar-container">
        <div class="admin-toolbar-floating" 
             data-current-page="<?php echo $adminCurrentPage; ?>" 
             data-total-pages="<?php echo $totalPages; ?>">
            
            <div class="admin-toolbar-left">
                <button type="button" 
                        class="admin-toolbar-button <?php echo $isSearching ? 'active' : ''; ?>" 
                        data-action="admin-toggle-search">
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
                        <?php 
                        if ($totalUsers == 0) { // Si no hay usuarios (sea búsqueda o no)
                            echo '--';
                        } else {
                            echo $adminCurrentPage . ' / ' . $totalPages;
                        }
                        ?>
                    </span>
                    <button type="button" class="admin-toolbar-button" 
                            data-action="admin-page-next"
                            <?php echo ($adminCurrentPage >= $totalPages) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">chevron_right</span>
                    </button>
                </div>
            </div>
        </div>
        
        <div class="admin-search-bar" id="admin-search-bar" 
             style="display: <?php echo $isSearching ? 'flex' : 'none'; ?>;">
            <span class="material-symbols-rounded">search</span>
            <input type="text" class="admin-search-input" 
                   placeholder="Buscar usuario por nombre, email..." 
                   value="<?php echo htmlspecialchars($searchQuery); ?>">
        </div>

    </div>


    <div class="component-wrapper">

        <div class="component-header-card">
            <h1 class="component-page-title" data-i18n="admin.users.title">Gestionar Usuarios</h1>
            <p class="component-page-description" data-i18n="admin.users.description">Busca, edita o gestiona los roles de los usuarios.</p>
        </div>

        <?php if (empty($usersList)): ?>
            
            <div class="component-card">
                <div class="component-card__content">
                    <div class="component-card__icon">
                        <span class="material-symbols-rounded"><?php echo $isSearching ? 'person_search' : 'person_off'; ?></span>
                    </div>
                    <div class="component-card__text">
                        <h2 class="component-card__title"><?php echo $isSearching ? 'Sin resultados' : 'No se encontraron usuarios'; ?></h2>
                        <p class="component-card__description"><?php echo $isSearching ? 'No hay usuarios que coincidan con tu término de búsqueda.' : 'No hay usuarios registrados en esta página o hubo un error al cargarlos.'; ?></p>
                    </div>
                </div>
            </div>

        <?php else: ?>
        <div class="user-list-container">
                <?php foreach ($usersList as $user): ?>
                    <?php
                        $avatarUrl = $user['profile_image_url'] ?? $defaultAvatar;
                        if (empty($avatarUrl)) { 
                            $avatarUrl = "https://ui-avatars.com/api/?name=" . urlencode($user['username']) . "&size=100&background=e0e0e0&color=ffffff";
                        }
                    ?>
                    <div class="user-card-item">
                        
                        <div class="component-card__avatar" style="width: 50px; height: 50px; flex-shrink: 0;" data-role="<?php echo htmlspecialchars($user['role']); ?>">
                            <img src="<?php echo htmlspecialchars($avatarUrl); ?>"
                                 alt="<?php echo htmlspecialchars($user['username']); ?>"
                                 class="component-card__avatar-image">
                        </div>

                        <div class="user-card-details">
                            
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

    </div>
</div>
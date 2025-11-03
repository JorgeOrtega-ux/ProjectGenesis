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

// ¡NUEVO! OBTENER PARÁMETROS DE ORDEN (o string vacío si no existen)
$sort_by_param = trim($_GET['s'] ?? '');
$sort_order_param = trim($_GET['o'] ?? '');

// ¡NUEVO! Lista blanca para seguridad
$allowed_sort = ['created_at', 'username', 'email'];
$allowed_order = ['ASC', 'DESC'];

// ¡NUEVO! Validar parámetros. Si son inválidos, se tratan como vacíos (default)
if (!in_array($sort_by_param, $allowed_sort)) {
    $sort_by_param = '';
}
if (!in_array($sort_order_param, $allowed_order)) {
    $sort_order_param = '';
}

// ¡NUEVO! Definir los valores REALES para la consulta SQL
// Si los parámetros están vacíos, usamos el orden por defecto.
$sort_by_sql = ($sort_by_param === '') ? 'created_at' : $sort_by_param;
$sort_order_sql = ($sort_order_param === '') ? 'DESC' : $sort_order_param;

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
    
    // ¡MODIFICADO! Usar las variables SQL seguras
    $sqlSelect .= " ORDER BY $sort_by_sql $sort_order_sql LIMIT :limit OFFSET :offset";

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
<div class="section-content overflow-y <?php echo ($CURRENT_SECTION === 'admin-manage-users') ? 'active' : 'disabled'; ?>" data-section="admin-users">

    <div class="page-toolbar-container">

        <div class="page-toolbar-floating"
            data-current-page="<?php echo $adminCurrentPage; ?>"
            data-total-pages="<?php echo $totalPages; ?>">

            <div class="toolbar-action-default">
                <div class="page-toolbar-left">
                    <button type="button"
                        class="page-toolbar-button <?php echo $isSearching ? 'active' : ''; ?>"
                        data-action="admin-toggle-search"
                        data-tooltip="admin.users.search">
                        <span class="material-symbols-rounded">search</span>
                    </button>
                    
                    <button type="button"
                        class="page-toolbar-button <?php echo ($sort_by_param !== '') ? 'active' : ''; ?>"
                        data-action="toggleModulePageFilter"
                        data-tooltip="admin.users.filter">
                        <span class="material-symbols-rounded">filter_list</span>
                    </button>
                    
                    <button type="button"
                        class="page-toolbar-button"
                        data-action="toggleSectionAdminCreateUser" 
                        data-tooltip="admin.users.createUser">
                        <span class="material-symbols-rounded">person_add</span>
                    </button>
                </div>
                
                <div class="page-toolbar-right">
                    <div class="page-toolbar-pagination">
                        <button type="button" class="page-toolbar-button"
                            data-action="admin-page-prev"
                            data-tooltip="admin.users.prevPage"
                            <?php echo ($adminCurrentPage <= 1) ? 'disabled' : ''; ?>>
                            <span class="material-symbols-rounded">chevron_left</span>
                        </button>

                        <span class="page-toolbar-page-text">
                            <?php
                            if ($totalUsers == 0) {
                                echo '--';
                            } else {
                                echo $adminCurrentPage . ' / ' . $totalPages;
                            }
                            ?>
                        </span>
                        <button type="button" class="page-toolbar-button"
                            data-action="admin-page-next"
                            data-tooltip="admin.users.nextPage"
                            <?php echo ($adminCurrentPage >= $totalPages) ? 'disabled' : ''; ?>>
                            <span class="material-symbols-rounded">chevron_right</span>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="toolbar-action-selection">
                <div class="page-toolbar-left">
                    <button type="button"
                        class="page-toolbar-button"
                        data-action="toggleSectionAdminEditUser"
                        data-tooltip="admin.users.editUserTooltip" disabled>
                        <span class="material-symbols-rounded">edit</span>
                    </button>
                    <button type="button"
                        class="page-toolbar-button"
                        data-action="toggleModuleAdminRole"
                        data-tooltip="admin.users.manageRole" disabled>
                        <span class="material-symbols-rounded">manage_accounts</span>
                    </button>
                    <button type="button"
                        class="page-toolbar-button"
                        data-action="toggleModuleAdminStatus"
                        data-tooltip="admin.users.manageStatus" disabled>
                        <span class="material-symbols-rounded">toggle_on</span>
                    </button>
                </div>

                <div class="page-toolbar-right">
                    <button type="button"
                        class="page-toolbar-button"
                        data-action="admin-clear-selection"
                        data-tooltip="admin.users.clearSelection" disabled>
                        <span class="material-symbols-rounded">close</span>
                    </button>
                </div>
            </div>
            
        </div>

        <div class="popover-module body-title disabled" data-module="modulePageFilter">
            <div class="menu-content">
                <div class="menu-list">
                    <div class="menu-header" data-i18n="admin.users.sortBy">Ordenar por</div>
                    
                    <div class="menu-link <?php echo ($sort_by_param === '') ? 'active' : ''; ?>"
                         data-action="admin-set-filter" data-sort="" data-order="">
                        <div class="menu-link-icon">
                            <span class="material-symbols-rounded">clear_all</span>
                        </div>
                        <div class="menu-link-text">
                            <span data-i18n="admin.users.sortDefault"></span>
                        </div>
                        <div class="menu-link-check-icon">
                            <?php if ($sort_by_param === ''): ?><span class="material-symbols-rounded">check</span><?php endif; ?>
                        </div>
                    </div>

                    <div class="menu-link <?php echo ($sort_by_param === 'created_at' && $sort_order_param === 'DESC') ? 'active' : ''; ?>"
                         data-action="admin-set-filter" data-sort="created_at" data-order="DESC">
                        <div class="menu-link-icon">
                            <span class="material-symbols-rounded">calendar_today</span>
                        </div>
                        <div class="menu-link-text">
                            <span data-i18n="admin.users.sortDateNew"></span>
                        </div>
                        <div class="menu-link-check-icon">
                            <?php if ($sort_by_param === 'created_at' && $sort_order_param === 'DESC'): ?><span class="material-symbols-rounded">check</span><?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="menu-link <?php echo ($sort_by_param === 'created_at' && $sort_order_param === 'ASC') ? 'active' : ''; ?>"
                         data-action="admin-set-filter" data-sort="created_at" data-order="ASC">
                        <div class="menu-link-icon">
                            <span class="material-symbols-rounded">calendar_today</span>
                        </div>
                        <div class="menu-link-text">
                            <span data-i18n="admin.users.sortDateOld"></span>
                        </div>
                        <div class="menu-link-check-icon">
                            <?php if ($sort_by_param === 'created_at' && $sort_order_param === 'ASC'): ?><span class="material-symbols-rounded">check</span><?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="menu-link <?php echo ($sort_by_param === 'username' && $sort_order_param === 'ASC') ? 'active' : ''; ?>"
                         data-action="admin-set-filter" data-sort="username" data-order="ASC">
                        <div class="menu-link-icon">
                            <span class="material-symbols-rounded">sort_by_alpha</span>
                        </div>
                        <div class="menu-link-text">
                            <span data-i18n="admin.users.sortNameAZ"></span>
                        </div>
                        <div class="menu-link-check-icon">
                            <?php if ($sort_by_param === 'username' && $sort_order_param === 'ASC'): ?><span class="material-symbols-rounded">check</span><?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="menu-link <?php echo ($sort_by_param === 'username' && $sort_order_param === 'DESC') ? 'active' : ''; ?>"
                         data-action="admin-set-filter" data-sort="username" data-order="DESC">
                        <div class="menu-link-icon">
                            <span class="material-symbols-rounded">sort_by_alpha</span>
                        </div>
                        <div class="menu-link-text">
                            <span data-i18n="admin.users.sortNameZA"></span>
                        </div>
                        <div class="menu-link-check-icon">
                            <?php if ($sort_by_param === 'username' && $sort_order_param === 'DESC'): ?><span class="material-symbols-rounded">check</span><?php endif; ?>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        
        <div class="popover-module body-title disabled" data-module="moduleAdminRole">
            <div class="menu-content">
                <div class="menu-list">
                    <div class="menu-header" data-i18n="admin.users.role">Rol</div>
                    
                    <div class="menu-link" data-action="admin-set-role" data-value="user">
                        <div class="menu-link-icon"><span class="material-symbols-rounded">person</span></div>
                        <div class="menu-link-text"><span data-i18n="admin.users.roleUser"></span></div>
                        <div class="menu-link-check-icon"></div>
                    </div>
                    <div class="menu-link" data-action="admin-set-role" data-value="moderator">
                        <div class="menu-link-icon"><span class="material-symbols-rounded">shield_person</span></div>
                        <div class="menu-link-text"><span data-i18n="admin.users.roleModerator"></span></div>
                        <div class="menu-link-check-icon"></div>
                    </div>
                    <div class="menu-link" data-action="admin-set-role" data-value="administrator">
                        <div class="menu-link-icon"><span class="material-symbols-rounded">admin_panel_settings</span></div>
                        <div class="menu-link-text"><span data-i18n="admin.users.roleAdministrator"></span></div>
                        <div class="menu-link-check-icon"></div>
                    </div>
                    <div class="menu-link" data-action="admin-set-role" data-value="founder">
                        <div class="menu-link-icon"><span class="material-symbols-rounded">star</span></div>
                        <div class="menu-link-text"><span data-i18n="admin.users.roleFounder"></span></div>
                        <div class="menu-link-check-icon"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="popover-module body-title disabled" data-module="moduleAdminStatus">
            <div class="menu-content">
                <div class="menu-list">
                    <div class="menu-header" data-i18n="admin.users.status">Estado</div>
                    
                    <div class="menu-link" data-action="admin-set-status" data-value="active">
                        <div class="menu-link-icon"><span class="material-symbols-rounded">check_circle</span></div>
                        <div class="menu-link-text"><span data-i18n="admin.users.statusActive"></span></div>
                        <div class="menu-link-check-icon"></div>
                    </div>
                    <div class="menu-link" data-action="admin-set-status" data-value="suspended">
                        <div class="menu-link-icon"><span class="material-symbols-rounded">pause_circle</span></div>
                        <div class="menu-link-text"><span data-i18n="admin.users.statusSuspended"></span></div>
                        <div class="menu-link-check-icon"></div>
                    </div>
                    <div class="menu-link" data-action="admin-set-status" data-value="deleted">
                        <div class="menu-link-icon"><span class="material-symbols-rounded">remove_circle</span></div>
                        <div class="menu-link-text"><span data-i18n="admin.users.statusDeleted"></span></div>
                        <div class="menu-link-check-icon"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="page-toolbar-floating <?php echo $isSearching ? 'active' : 'disabled'; ?>" id="page-search-bar-container">
            
            <div class="page-search-bar active" id="page-search-bar">
        <span class="material-symbols-rounded">search</span>
                <input type="text" class="page-search-input" 
                       placeholder="Buscar usuario por nombre, email..." 
                       value="<?php echo htmlspecialchars($searchQuery); ?>">
            </div>
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
                        <h2 class="component-card__title" data-i18n="<?php echo $isSearching ? 'admin.users.noResultsTitle' : 'admin.users.noUsersTitle'; ?>"></h2>
                        <p class="component-card__description" data-i18n="<?php echo $isSearching ? 'admin.users.noResultsDesc' : 'admin.users.noUsersDesc'; ?>"></p>
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
                    <div class="user-card-item" 
                         data-user-id="<?php echo $user['id']; ?>"
                         data-user-role="<?php echo htmlspecialchars($user['role']); ?>"
                         data-user-status="<?php echo htmlspecialchars($user['account_status']); ?>"
                    >
                    <div class="component-card__avatar" style="width: 50px; height: 50px; flex-shrink: 0;" data-role="<?php echo htmlspecialchars($user['role']); ?>">
                            <img src="<?php echo htmlspecialchars($avatarUrl); ?>"
                                alt="<?php echo htmlspecialchars($user['username']); ?>"
                                class="component-card__avatar-image">
                        </div>

                        <div class="user-card-details">

                            <div class="user-card-detail-item user-card-detail-item--full">
                                <span class="user-card-detail-label" data-i18n="admin.users.labelUsername"></span>
                                <span class="user-card-detail-value"><?php echo htmlspecialchars($user['username']); ?></span>
                            </div>

                            <div class="user-card-detail-item">
                                <span class="user-card-detail-label" data-i18n="admin.users.labelRole"></span>
                                <span class="user-card-detail-value"><?php echo htmlspecialchars(ucfirst($user['role'])); ?></span>
                            </div>
                            <div class="user-card-detail-item">
                                <span class="user-card-detail-label" data-i18n="admin.users.labelCreated"></span>
                                <span class="user-card-detail-value"><?php echo (new DateTime($user['created_at']))->format('d/m/Y'); ?></span>
                            </div>

                            <?php if ($user['email']): ?>
                                <div class="user-card-detail-item user-card-detail-item--full">
                                    <span class="user-card-detail-label" data-i18n="admin.users.labelEmail"></span>
                                    <span class="user-card-detail-value"><?php echo htmlspecialchars($user['email']); ?></span>
                                </div>
                            <?php endif; ?>

                            <div class="user-card-detail-item">
                                <span class="user-card-detail-label" data-i18n="admin.users.labelStatus"></span>
                                <span class="user-card-detail-value"><?php echo htmlspecialchars(ucfirst($user['account_status'])); ?></span>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</div>
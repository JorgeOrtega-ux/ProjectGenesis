<?php
// FILE: includes/sections/admin/manage-groups.php
// (VERSIÓN CORREGIDA CON LÓGICA DE BD + BARRA DE SELECCIÓN)

// --- Lógica de Búsqueda y Paginación ---
$searchQuery = trim($_GET['q'] ?? '');
$isSearching = !empty($searchQuery);

$adminCurrentPage = (int)($_GET['p'] ?? 1);
if ($adminCurrentPage < 1) $adminCurrentPage = 1;

$sort_by_sql = 'name';
$sort_order_sql = 'ASC';

$groupsList = [];
$groupsPerPage = 20;
$totalGroups = 0;
$totalPages = 1;

$groupIconMap = [
    'municipio' => 'account_balance',
    'universidad' => 'school',
    'default' => 'group'
];

try {
    // 1. Contar el total de grupos
    $sqlCount = "SELECT COUNT(*) FROM `groups`"; 
    if ($isSearching) {
        $sqlCount .= " WHERE (name LIKE :query OR access_key LIKE :query)";
    }

    $totalGroupsStmt = $pdo->prepare($sqlCount);

    if ($isSearching) {
        $searchParam = '%' . $searchQuery . '%';
        $totalGroupsStmt->bindParam(':query', $searchParam, PDO::PARAM_STR);
    }

    $totalGroupsStmt->execute();
    $totalGroups = (int)$totalGroupsStmt->fetchColumn();

    if ($totalGroups > 0) {
        $totalPages = (int)ceil($totalGroups / $groupsPerPage);
    } else {
        $totalPages = 1; 
    }

    if ($adminCurrentPage > $totalPages) {
        $adminCurrentPage = $totalPages;
    }

    $offset = ($adminCurrentPage - 1) * $groupsPerPage;

    // 4. Obtener los grupos para la página actual
    $sqlSelect = "SELECT 
                    g.id, g.name, g.group_type, g.privacy, g.created_at, g.access_key,
                    (SELECT COUNT(ug.user_id) FROM user_groups ug WHERE ug.group_id = g.id) AS member_count
                  FROM `groups` g";
    
    if ($isSearching) {
        $sqlSelect .= " WHERE (g.name LIKE :query OR g.access_key LIKE :query)";
    }
    
    $sqlSelect .= " ORDER BY g.$sort_by_sql $sort_order_sql LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sqlSelect);

    if ($isSearching) {
        $stmt->bindParam(':query', $searchParam, PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $groupsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $groupsList = $stmt->fetchAll();

} catch (PDOException $e) {
    logDatabaseError($e, 'admin - manage-groups');
}

?>
<div class="section-content overflow-y <?php echo ($CURRENT_SECTION === 'admin-manage-groups') ? 'active' : 'disabled'; ?>" data-section="admin-manage-groups">

    <div class="page-toolbar-container" id="group-toolbar-container">
    <div class="page-toolbar-floating"
            data-current-page="<?php echo $adminCurrentPage; ?>"
            data-total-pages="<?php echo $totalPages; ?>">

            <div class="toolbar-action-default">
                <div class="page-toolbar-left">
                    <button type="button"
                        class="page-toolbar-button <?php echo $isSearching ? 'active' : ''; ?>"
                        data-action="admin-toggle-search"
                        data-tooltip="admin.groups.search"> <span class="material-symbols-rounded">search</span>
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
                            if ($totalGroups == 0) {
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
                        data-action="toggleSectionAdminEditGroup"
                        data-tooltip="admin.groups.editGroupTooltip" disabled>
                        <span class="material-symbols-rounded">edit</span>
                    </button>
                </div>

                <div class="page-toolbar-right">
                    <button type="button"
                        class="page-toolbar-button"
                        data-action="admin-group-clear-selection"
                        data-tooltip="admin.users.clearSelection" disabled>
                        <span class="material-symbols-rounded">close</span>
                    </button>
                </div>
            </div>
            </div>

        <div class="page-toolbar-floating <?php echo $isSearching ? 'active' : 'disabled'; ?>" id="page-search-bar-container">
            
            <div class="page-search-bar active" id="page-search-bar">
                <span class="material-symbols-rounded">search</span>
                <input type="text" class="page-search-input" 
                       placeholder="Buscar grupo por nombre o clave..." value="<?php echo htmlspecialchars($searchQuery); ?>">
            </div>
        </div>
    </div>
    <div class="component-wrapper">

        <?php outputCsrfInput(); ?>

        <div class="component-header-card">
            <h1 class="component-page-title" data-i18n="admin.groups.title">Gestionar Grupos</h1> <p class="component-page-description" data-i18n="admin.groups.description">Busca, edita o gestiona los grupos del sistema.</p> </div>

        <?php if (empty($groupsList)): ?>

            <div class="component-card">
                <div class="component-card__content">
                    <div class="component-card__icon">
                        <span class="material-symbols-rounded"><?php echo $isSearching ? 'search_off' : 'groups'; ?></span>
                    </div>
                    <div class="component-card__text">
                        <h2 class="component-card__title" data-i18n="<?php echo $isSearching ? 'admin.groups.noResultsTitle' : 'admin.groups.noGroupsTitle'; ?>"></h2>
                        <p class="component-card__description" data-i18n="<?php echo $isSearching ? 'admin.groups.noResultsDesc' : 'admin.groups.noGroupsDesc'; ?>"></p>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <div class="card-list-container">
                
                <?php foreach ($groupsList as $group): ?>
                    <?php
                        $iconType = $group['group_type'] ?? 'default';
                        $iconName = $groupIconMap[$iconType] ?? $groupIconMap['default'];
                        $privacyIcon = ($group['privacy'] === 'publico') ? 'public' : 'lock';
                        $memberTextKey = ($group['member_count'] == 1) ? 'mygroups.card.member' : 'mygroups.card.members';
                        $privacyTextKey = 'mygroups.card.privacy' . ucfirst($group['privacy']);
                    ?>
                    
                    <div class="card-item" 
                         data-group-id="<?php echo $group['id']; ?>"
                         style="gap: 16px; padding: 16px;">
                    <div class="component-card__icon" style="width: 50px; height: 50px; flex-shrink: 0; background-color: #f5f5fa;">
                            <span class="material-symbols-rounded" style="font-size: 28px;"><?php echo $iconName; ?></span>
                        </div>

                        <div class="card-item-details">

                            <div class="card-detail-item card-detail-item--full" style="border: none; padding: 0; background: none;">
                                <span class="card-detail-value" style="font-size: 16px; font-weight: 600;"><?php echo htmlspecialchars($group['name']); ?></span>
                            </div>

                            <div class="card-detail-item">
                                <span class="material-symbols-rounded" style="font-size: 16px; color: #6b7280;">key</span>
                                <span class="card-detail-value" style="font-family: monospace;"><?php echo htmlspecialchars($group['access_key']); ?></span>
                            </div>

                            <div class="card-detail-item">
                                <span class="material-symbols-rounded" style="font-size: 16px; color: #6b7280;">group</span>
                                <span class="card-detail-value"><?php echo htmlspecialchars($group['member_count']); ?> <span data-i18n="<?php echo $memberTextKey; ?>"></span></span>
                            </div>
                            
                            <div class="card-detail-item">
                                <span class="material-symbols-rounded" style="font-size: 16px; color: #6b7280;"><?php echo $privacyIcon; ?></span>
                                <span class="card-detail-value" data-i18n="<?php echo $privacyTextKey; ?>"></span>
                            </div>
                            
                            <div class="card-detail-item">
                                <span class="card-detail-label" data-i18n="admin.users.labelCreated"></span>
                                <span class="card-detail-value"><?php echo (new DateTime($group['created_at']))->format('d/m/Y'); ?></span>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>
                
            </div>
        <?php endif; ?>

    </div>
</div>
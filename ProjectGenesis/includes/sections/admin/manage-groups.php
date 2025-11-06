<?php
// FILE: includes/sections/admin/manage-groups.php
// (NUEVA SECCIÓN DE GESTIÓN DE GRUPOS)

// --- Lógica de Búsqueda (Similar a manage-users) ---
$searchQuery = trim($_GET['q'] ?? '');
$isSearching = !empty($searchQuery);

// (Aquí irá la lógica de paginación y carga de datos en el futuro)
$groupsList = []; // Por ahora, la dejamos vacía
$adminCurrentPage = 1;
$totalPages = 1;
$totalGroups = 0;

?>
<div class="section-content overflow-y <?php echo ($CURRENT_SECTION === 'admin-manage-groups') ? 'active' : 'disabled'; ?>" data-section="admin-groups">

    <div class="page-toolbar-container">

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
                    </div>
            </div>
            
            </div>

        <div class="page-toolbar-floating <?php echo $isSearching ? 'active' : 'disabled'; ?>" id="page-search-bar-container">
            
            <div class="page-search-bar active" id="page-search-bar">
                <span class="material-symbols-rounded">search</span>
                <input type="text" class="page-search-input" 
                       placeholder="Buscar grupo por nombre..." value="<?php echo htmlspecialchars($searchQuery); ?>">
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
                </div>
        <?php endif; ?>

    </div>
</div>
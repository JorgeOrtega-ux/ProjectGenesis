<?php
// FILE: includes/sections/admin/manage-users.php
// (LÓGICA PHP MOVIDA A 'admin_users_fetcher.php')

// Las variables $usersList, $adminCurrentPage, $totalPages, $isSearching,
// $searchQuery, $sort_by_param, $sort_order_param son
// cargadas por config/routing/router.php
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
                        <button type="button"
                        class="page-toolbar-button"
                        data-action="toggleModuleAdminExport"
                        data-tooltip="admin.users.exportTooltip"> <span class="material-symbols-rounded">upload_file</span>
                    </button>
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
                        data-action="toggleSectionAdminManageStatus"
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
        
        <div class="popover-module body-title disabled" data-module="moduleAdminExport">
                        <div class="menu-content">
                            <div class="menu-list">
                                <div class="menu-header" data-i18n="admin.users.exportAs">Exportar como...</div>

                                <div class="menu-link" data-action="admin-export-as" data-format="json">
                                    <div class="menu-link-icon"><span class="material-symbols-rounded">data_object</span></div>
                                    <div class="menu-link-text"><span>JSON</span></div>
                                </div>
                                <div class="menu-link" data-action="admin-export-as" data-format="csv">
                                    <div class="menu-link-icon"><span class="material-symbols-rounded">description</span></div>
                                    <div class="menu-link-text"><span>CSV</span></div>
                                </div>
                                <div class="menu-link" data-action="admin-export-as" data-format="excel">
                                    <div class="menu-link-icon"><span class="material-symbols-rounded">spreadsheet</span></div>
                                    <div class="menu-link-text"><span>Excel (XLSX)</span></div>
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
            <div class="card-list-container">
                <?php foreach ($usersList as $user): ?>
                    <?php
                    $avatarUrl = $user['profile_image_url'] ?? $defaultAvatar;
                    if (empty($avatarUrl)) {
                        $avatarUrl = "https://ui-avatars.com/api/?name=" . urlencode($user['username']) . "&size=100&background=e0e0e0&color=ffffff";
                    }
                    ?>
                    <div class="card-item" 
                         data-user-id="<?php echo $user['id']; ?>"
                         data-user-role="<?php echo htmlspecialchars($user['role']); ?>"
                         data-user-status="<?php echo htmlspecialchars($user['account_status']); ?>"
                         data-restrictions="<?php echo htmlspecialchars($user['restrictions'] ?? ''); ?>"
                    >
                    <div class="component-card__avatar" style="width: 50px; height: 50px; flex-shrink: 0;" data-role="<?php echo htmlspecialchars($user['role']); ?>">
                            <img src="<?php echo htmlspecialchars($avatarUrl); ?>"
                                alt="<?php echo htmlspecialchars($user['username']); ?>"
                                class="component-card__avatar-image">
                        </div>

                        <div class="card-item-details">

                            <div class="card-detail-item card-detail-item--full">
                                <span class="card-detail-label" data-i18n="admin.users.labelUsername"></span>
                                <span class="card-detail-value"><?php echo htmlspecialchars($user['username']); ?></span>
                            </div>

                            <div class="card-detail-item">
                                <span class="card-detail-label" data-i18n="admin.users.labelRole"></span>
                                <span class="card-detail-value"><?php echo htmlspecialchars(ucfirst($user['role'])); ?></span>
                            </div>
                            <div class="card-detail-item">
                                <span class="card-detail-label" data-i18n="admin.users.labelCreated"></span>
                                <span class="card-detail-value"><?php echo (new DateTime($user['created_at']))->format('d/m/Y'); ?></span>
                            </div>

                            <?php if ($user['email']): ?>
                                <div class="card-detail-item card-detail-item--full">
                                    <span class="card-detail-label" data-i18n="admin.users.labelEmail"></span>
                                    <span class="card-detail-value"><?php echo htmlspecialchars($user['email']); ?></span>
                                </div>
                            <?php endif; ?>

                            <div class="card-detail-item">
                                <span class="card-detail-label" data-i18n="admin.users.labelStatus"></span>
                                <span class="card-detail-value"><?php echo htmlspecialchars(ucfirst($user['account_status'])); ?></span>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</div>
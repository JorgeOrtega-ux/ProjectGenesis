<?php
// FILE: includes/sections/admin/dashboard.php
?>
<div class="section-content overflow-y <?php echo ($CURRENT_SECTION === 'admin-dashboard') ? 'active' : 'disabled'; ?>" data-section="admin-dashboard">
    
    <div class="page-toolbar-container" id="dashboard-toolbar-container">
        <div class="page-toolbar-floating">
            <div class="toolbar-action-default">
                <div class="page-toolbar-left">
                    <button type="button"
                        class="page-toolbar-button"
                        data-action="toggleSectionAdminManageLogs"
                        data-tooltip="admin.dashboard.manageLogs"
                        >
                        <span class="material-symbols-rounded">history</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="component-wrapper">

        <div class="component-header-card">
            <h1 class="component-page-title" data-i18n="admin.dashboard.title"></h1>
            <p class="component-page-description" data-i18n="admin.dashboard.description"></p>
        </div>

        </div>
</div>
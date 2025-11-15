<?php
?>
<div class="module-content module-surface body-title disabled" data-module="moduleSurface">
    <div class="menu-content">
        
        <div class="menu-layout">
            <div class="menu-layout__top">
                <div class="menu-list">
                    
                    <?php 
                    if (isset($isSettingsPage) && $isSettingsPage): 
                    ?>
                        <div class="menu-link" data-action="toggleSectionHome">
                            <div class="menu-link-icon">
                                <span class="material-symbols-rounded">arrow_back</span>
                            </div>
                            <div class="menu-link-text">
                                <span data-i18n="sidebar.settings.backToHome"></span>
                            </div>
                        </div>
                        
                        <div class="menu-link" data-action="toggleSectionSettingsProfile">
                            <div class="menu-link-icon">
                                <span class="material-symbols-rounded">account_circle</span>
                            </div>
                            <div class="menu-link-text">
                                <span data-i18n="sidebar.settings.yourProfile"></span>
                            </div>
                        </div>

                        <div class="menu-link" data-action="toggleSectionSettingsLogin">
                            <div class="menu-link-icon">
                                <span class="material-symbols-rounded">security</span>
                            </div>
                            <div class="menu-link-text">
                                <span data-i18n="sidebar.settings.loginSecurity"></span>
                            </div>
                        </div>

                        <div class="menu-link" data-action="toggleSectionSettingsAccess">
                            <div class="menu-link-icon">
                                <span class="material-symbols-rounded">accessibility</span>
                            </div>
                            <div class="menu-link-text">
                                <span data-i18n="sidebar.settings.accessibility"></span>
                            </div>
                        </div>
                        
                        <?php ?>
                        <div class="menu-link" data-action="toggleSectionSettingsPrivacy">
                            <div class="menu-link-icon">
                                <span class="material-symbols-rounded">privacy_tip</span>
                            </div>
                            <div class="menu-link-text">
                                <span data-i18n="sidebar.settings.privacy"></span>
                            </div>
                        </div>
                        <?php ?>
                        
                    <?php 
                    elseif (isset($isAdminPage) && $isAdminPage): 
                    ?>
                        <div class="menu-link" data-action="toggleSectionHome">
                            <div class="menu-link-icon">
                                <span class="material-symbols-rounded">arrow_back</span>
                            </div>
                            <div class="menu-link-text">
                                <span data-i18n="sidebar.admin.backToHome"></span>
                            </div>
                        </div>

                        <div class="menu-link" data-action="toggleSectionAdminDashboard">
                            <div class="menu-link-icon">
                                <span class="material-symbols-rounded">dashboard</span>
                            </div>
                            <div class="menu-link-text">
                                <span data-i18n="sidebar.admin.dashboard"></span>
                            </div>
                        </div>

                        <div class="menu-link" data-action="toggleSectionAdminManageUsers">
                            <div class="menu-link-icon">
                                <span class="material-symbols-rounded">manage_accounts</span>
                            </div>
                            <div class="menu-link-text">
                                <span data-i18n="sidebar.admin.manageUsers"></span>
                            </div>
                        </div>

                        <div class="menu-link" data-action="toggleSectionAdminManageCommunities">
                            <div class="menu-link-icon">
                                <span class="material-symbols-rounded">groups</span>
                            </div>
                            <div class="menu-link-text">
                                <span data-i18n="sidebar.admin.manageCommunities">Gestionar Comunidades</span>
                            </div>
                        </div>
                        <?php 
                    else: 
                    ?>
                        <div class="menu-link active" data-action="toggleSectionHome">
                            <div class="menu-link-icon">
                                <span class="material-symbols-rounded">home</span>
                            </div>
                            <div class="menu-link-text">
                                <span data-i18n="sidebar.main.home"></span>
                            </div>
                        </div>
                        
                        <?php ?>

                        <div class="menu-link" data-action="toggleSectionExplorer">
                            <div class="menu-link-icon">
                                <span class="material-symbols-rounded">groups</span>
                            </div>
                            <div class="menu-link-text">
                                <span data-i18n="sidebar.main.explore"></span>
                            </div>
                        </div>
                        
                        <?php ?>
                        <?php ?>
                        <?php 
                    endif; 
                    ?>
                </div>
            </div>

            <div class="menu-layout__bottom">
                <div class="menu-list">
                    <?php 
                    if (isset($isAdminPage) && $isAdminPage): 
                    ?>
                        <?php ?>
                        <?php if ($_SESSION['role'] === 'founder'): ?>
                            <div class="menu-link" data-action="toggleSectionAdminManageBackups">
                                <div class="menu-link-icon">
                                    <span class="material-symbols-rounded">backup</span>
                                </div>
                                <div class="menu-link-text">
                                    <span data-i18n="sidebar.admin.manageBackups">Gestionar Copias</span> 
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="menu-link" data-action="toggleSectionAdminServerSettings">
                            <div class="menu-link-icon">
                                <span class="material-symbols-rounded">dns</span>
                            </div>
                            <div class="menu-link-text">
                                <span data-i18n="sidebar.admin.serverSettings">Config. del Servidor</span> 
                            </div>
                        </div>
                        <?php ?>

                    <?php 
                    endif; 
                    ?>
                </div>
            </div>
        </div>
        </div>
</div>
<?php
// FILE: includes/sections/main/home.php
?>
<div class="section-content overflow-y <?php echo ($CURRENT_SECTION === 'home') ? 'active' : 'disabled'; ?>" data-section="home">
    
    <div class="page-toolbar-container" id="home-toolbar-container">
        <div class="page-toolbar-floating">
            <div class="toolbar-action-default">
                
                <div class="page-toolbar-left">
                    
                    <button type="button"
                        class="page-toolbar-button"
                        data-action="home-select-group"
                        data-tooltip="home.toolbar.selectGroup">
                        <span class="material-symbols-rounded">group</span>
                    </button>
                    
                    <button type="button"
                        class="page-toolbar-button"
                        data-action="toggleSectionJoinGroup" 
                        data-tooltip="home.toolbar.joinGroup">
                    <span class="material-symbols-rounded">group_add</span>
                    </button>
                    
                </div>
                
                </div>

            </div>
    </div>

    <div class="component-wrapper">

        <div class="component-header-card">
            <h1 class="component-page-title" data-i18n="home.main.title"></h1>
            <p class="component-page-description" data-i18n="home.main.description"></p>
        </div>

        <div class="component-card">
            <div class="component-card__content">
                <div class="component-card__text">
                    <h2 class="component-card__title">Contenido de Ejemplo</h2>
                    <p class="component-card__description">
                        1
                    </p>
                </div>
            </div>
        </div>

    </div>
</div>
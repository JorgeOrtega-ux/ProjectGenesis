<?php
// FILE: includes/sections/main/create-publication.php
// (VERSÍON CORREGIDA Y AMPLIADA PARA ENCUESTAS)

// Determina qué pestaña está activa basada en la sección actual
$isPollActive = ($CURRENT_SECTION === 'create-poll');
$isPostActive = !$isPollActive;

// $userCommunitiesForPost es cargada por config/routing/router.php
$hasCommunities = isset($userCommunitiesForPost) && !empty($userCommunitiesForPost);
?>
<div class="section-content overflow-y <?php echo (strpos($CURRENT_SECTION, 'create-') === 0) ? 'active' : 'disabled'; ?>" data-section="<?php echo htmlspecialchars($CURRENT_SECTION); ?>">

    <div class="page-toolbar-container" id="create-post-toolbar-container">
        <div class="page-toolbar-floating">
            <div class="toolbar-action-default">
                <div class="page-toolbar-left">
                    <button type="button"
                        class="page-toolbar-button"
                        data-action="toggleSectionHome" 
                        data-tooltip="create_publication.backTooltip">
                        <span class="material-symbols-rounded">arrow_back</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="component-wrapper" style="padding-top: 82px;"> <div class="component-header-card">
            <h1 class="component-page-title" data-i18n="create_publication.title"></h1>
            <p class="component-page-description" data-i18n="create_publication.description"></p>
        </div>

        <?php outputCsrfInput(); ?>
        
        <input type="file" id="publication-file-input" class="visually-hidden" 
               accept="image/png, image/jpeg, image/gif, image/webp" multiple>

        <?php if (!$hasCommunities): ?>
            <div class="component-card">
                <div class="component-card__content">
                    <div class="component-card__icon">
                        <span class="material-symbols-rounded">group_off</span>
                    </div>
                    <div class="component-card__text">
                        <h2 class="component-card__title" data-i18n="create_publication.noCommunitiesTitle">No estás en ninguna comunidad</h2>
                        <p class="component-card__description" data-i18n="create_publication.noCommunitiesDesc">Debes unirte a una comunidad antes de poder publicar.</p>
                    </div>
                </div>
                <div class="component-card__actions">
                    <button type="button" class="component-button" data-action="toggleSectionJoinGroup" data-i18n="home.toolbar.joinGroup"></button>
                </div>
            </div>

        <?php else: ?>
            <div class="component-card component-card--action" id="create-post-form" style="gap: 16px;">
            
                <div class="component-toggle-tabs" id="post-type-toggle">
                    <button type="button" class="component-toggle-tab <?php echo $isPostActive ? 'active' : ''; ?>" data-type="post">
                        <span class="material-symbols-rounded">post_add</span>
                        <span data-i18n="create_publication.post"></span>
                    </button>
                    <button type="button" class="component-toggle-tab <?php echo $isPollActive ? 'active' : ''; ?>" data-type="poll">
                        <span class="material-symbols-rounded">poll</span>
                        <span data-i18n="create_publication.poll"></span>
                    </button>
                </div>

                <div class="component-card__content" style="width: 100%; padding-bottom: 0;">
                    <div class="component-card__text" style="width: 100%;">
                        <h2 class="component-card__title" data-i18n="create_publication.destination" style="margin-bottom: 8px;">Publicar en:</h2>
                        
                        <div class="trigger-select-wrapper" style="width: 100%;">
                            <div class="trigger-selector" 
                                 id="publication-community-trigger" 
                                 data-action="toggleModuleCommunitySelect"
                                 style="height: 52px; padding: 0 12px;">
                                
                                <div class="trigger-select-icon">
                                    <span class="material-symbols-rounded" id="publication-community-icon">public</span>
                                </div>
                                <div class="trigger-select-text">
                                    <span data-i18n="create_publication.selectCommunity" id="publication-community-text">Seleccione una comunidad...</span>
                                </div>
                                <div class="trigger-select-arrow">
                                    <span class="material-symbols-rounded">arrow_drop_down</span>
                                </div>
                            </div>

                            <div class="popover-module popover-module--anchor-width body-title disabled"
                                 data-module="moduleCommunitySelect"
                                 style="top: calc(100% + 4px);">
                                <div class="menu-content">
                                    <div class="menu-list">
                                        <?php foreach ($userCommunitiesForPost as $community): ?>
                                            <div class="menu-link" 
                                                 data-value="<?php echo htmlspecialchars($community['id']); ?>"
                                                 data-text="<?php echo htmlspecialchars($community['name']); ?>">
                                                <div class="menu-link-icon">
                                                    <span class="material-symbols-rounded">group</span>
                                                </div>
                                                <div class="menu-link-text">
                                                    <span><?php echo htmlspecialchars($community['name']); ?></span>
                                                </div>
                                                <div class="menu-link-check-icon">
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div id="post-content-area" class="<?php echo $isPostActive ? 'active' : 'disabled'; ?>" style="width: 100%; display: <?php echo $isPostActive ? 'flex' : 'none'; ?>; flex-direction: column; gap: 8px;">
                    <div class="component-input-group">
                        <textarea id="publication-text" class="component-input" rows="5" placeholder=" " style="height: 120px; resize: vertical; padding-top: 16px;" maxlength="1000"></textarea>
                        <label for="publication-text" data-i18n="create_publication.placeholder"></label>
                    </div>
                    <div class="publication-preview-container" id="publication-preview-container">
                    </div>
                </div>

                <div id="poll-content-area" class="<?php echo $isPollActive ? 'active' : 'disabled'; ?>" style="width: 100%; display: <?php echo $isPollActive ? 'flex' : 'none'; ?>; flex-direction: column; gap: 12px;">
                    <div class="component-input-group">
                        <input type="text" id="poll-question" class="component-input" placeholder=" " maxlength="1000">
                        <label for="poll-question" data-i18n="create_publication.pollQuestionLabel">Escribe tu pregunta...</label>
                    </div>
                    
                    <div id="poll-options-container" style="display: flex; flex-direction: column; gap: 8px;">
                        </div>
                    
                    <button type="button" class="component-action-button component-action-button--secondary" id="add-poll-option-btn" style="height: 40px; justify-content: flex-start; gap: 8px;">
                        <span class="material-symbols-rounded">add_circle</span>
                        <span data-i18n="create_publication.pollAddOption">Añadir opción</span>
                    </button>
                </div>
                
                <div class="component-card__actions" style="width: 100%; justify-content: space-between;">
                    
                    <button type="button" class="component-action-button component-action-button--secondary" 
                            id="attach-files-btn" 
                            data-tooltip="create_publication.attachTooltip"
                            style="<?php echo $isPollActive ? 'display: none;' : 'display: flex;'; // Ocultar si es encuesta ?>">
                        <span class="material-symbols-rounded">attach_file</span>
                    </button>
                    
                    <div id="attach-files-spacer" style="<?php echo $isPollActive ? 'display: block; flex-grow: 1;' : 'display: none;'; ?>"></div>

                    <button type="button" class="component-action-button component-action-button--primary" id="publish-post-btn" data-i18n="create_publication.publish" disabled>
                        Publicar
                    </button>
                </div>

            </div>
        <?php endif; ?>
        </div>
</div>
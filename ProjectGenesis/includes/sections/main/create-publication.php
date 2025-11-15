<?php
// FILE: includes/sections/main/create-publication.php
// (VERSÍON CORREGIDA Y AMPLIADA PARA ENCUESTAS Y PRIVACIDAD)
// --- ▼▼▼ MODIFICACIÓN (ELIMINADO 'Hashtags') ▼▼▼ ---

// Determina qué pestaña está activa basada en la sección actual
$isPollActive = ($CURRENT_SECTION === 'create-poll');
$isPostActive = !$isPollActive;

// $userCommunitiesForPost es cargada por config/routing/router.php
$hasCommunities = isset($userCommunitiesForPost) && !empty($userCommunitiesForPost);

// --- (Bloque de privacidad sin cambios) ---
$privacyMap = [
    'public' => 'post.privacy.public',
    'friends' => 'post.privacy.friends',
    'private' => 'post.privacy.private'
];
$privacyIconMap = [
    'public' => 'public',
    'friends' => 'group',
    'private' => 'lock'
];
$defaultPrivacy = 'public';
$currentPrivacyKey = $privacyMap[$defaultPrivacy];
$currentPrivacyIcon = $privacyIconMap[$defaultPrivacy];
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

                <div class="page-toolbar-right">
                    <button type="button"
                        class="page-toolbar-button"
                        data-action="toggleModuleCreatePost" 
                        data-tooltip="home.toolbar.createPost">
                        <span class="material-symbols-rounded">add</span>
                    </button>
                </div>

            </div>
        </div>

        <div class="popover-module popover-module--anchor-right body-title disabled" data-module="moduleCreatePost">
            <div class="menu-content">
                <div class="menu-list">
                    <div class="menu-link" data-action="toggleSectionCreatePublication">
                        <div class="menu-link-icon">
                            <span class="material-symbols-rounded">post_add</span>
                        </div>
                        <div class="menu-link-text">
                            <span data-i18n="home.popover.newPost">Crear publicación</span>
                        </div>
                    </div>
                    <div class="menu-link" data-action="toggleSectionCreatePoll">
                        <div class="menu-link-icon">
                            <span class="material-symbols-rounded">poll</span>
                        </div>
                        <div class="menu-link-text">
                            <span data-i18n="home.popover.newPoll">Crear encuesta</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="component-wrapper">
        <div class="component-header-card">
            <h1 class="component-page-title" data-i18n="<?php echo $isPollActive ? 'create_publication.poll' : 'create_publication.post'; ?>"></h1>
            <p class="component-page-description" data-i18n="create_publication.description"></p>
        </div>

        <?php outputCsrfInput(); ?>
        
        <input type="file" id="publication-file-input" class="visually-hidden" 
               accept="image/png, image/jpeg, image/gif, image/webp" multiple>

        <?php // --- ▼▼▼ INICIO DE MODIFICACIÓN (Lógica $hasCommunities) ▼▼▼ --- ?>
        <?php if (false): // Esta lógica se mueve adentro del formulario para no bloquearlo ?>
            <div class="component-card">
                ... (Este bloque ya no se usa aquí) ...
            </div>
        <?php endif; ?>
            
        <div class="component-card component-card--action" id="create-post-form">
        
            <div class="component-card__content">
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="create_publication.destination">Publicar en:</h2>
                    
                    <div class="trigger-select-wrapper">
                        <div class="trigger-selector" 
                             id="publication-community-trigger" 
                             data-action="toggleModuleCommunitySelect">
                            
                            <div class="trigger-select-icon">
                                <span class="material-symbols-rounded" id="publication-community-icon">person</span>
                            </div>
                            <div class="trigger-select-text">
                                <span data-i18n="create_publication.myProfile" id="publication-community-text">Mi Perfil</span>
                            </div>
                            <div class="trigger-select-arrow">
                                <span class="material-symbols-rounded">arrow_drop_down</span>
                            </div>
                        </div>

                        <div class="popover-module popover-module--anchor-width body-title disabled"
                             data-module="moduleCommunitySelect">
                            <div class="menu-content">
                                <div class="menu-list">
                                    
                                    <div class="menu-link active" 
                                         data-value="profile"
                                         data-text-key="create_publication.myProfile"
                                         data-icon="person">
                                        <div class="menu-link-icon">
                                            <span class="material-symbols-rounded">person</span>
                                        </div>
                                        <div class="menu-link-text">
                                            <span data-i18n="create_publication.myProfile">Mi Perfil</span>
                                        </div>
                                        <div class="menu-link-check-icon">
                                            <span class="material-symbols-rounded">check</span>
                                        </div>
                                    </div>

                                    <?php if (!$hasCommunities): ?>
                                        <div class="menu-link" style="opacity: 0.6; pointer-events: none;">
                                            <div class="menu-link-icon">
                                                <span class="material-symbols-rounded">group_off</span>
                                            </div>
                                            <div class="menu-link-text">
                                                <span data-i18n="create_publication.noCommunitiesJoin">Únete a un grupo...</span>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($userCommunitiesForPost as $community): ?>
                                            <div class="menu-link" 
                                                 data-value="<?php echo htmlspecialchars($community['id']); ?>"
                                                 data-text-key="<?php echo htmlspecialchars($community['name']); ?>"
                                                 data-icon="group">
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
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ --- ?>

            <div class="component-card__content">
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="post.options.privacyTitle">Quién puede ver esto:</h2>
                    
                    <div class="trigger-select-wrapper">
                        <div class="trigger-selector" 
                             id="publication-privacy-trigger" 
                             data-action="toggleModulePrivacySelect">
                            
                            <div class="trigger-select-icon">
                                <span class="material-symbols-rounded" id="publication-privacy-icon"><?php echo $currentPrivacyIcon; ?></span>
                            </div>
                            <div class="trigger-select-text">
                                <span data-i18n="<?php echo htmlspecialchars($currentPrivacyKey); ?>" id="publication-privacy-text"></span>
                            </div>
                            <div class="trigger-select-arrow">
                                <span class="material-symbols-rounded">arrow_drop_down</span>
                            </div>
                        </div>

                        <div class="popover-module popover-module--anchor-width body-title disabled"
                             data-module="modulePrivacySelect">
                            <div class="menu-content">
                                <div class="menu-list">
                                    <?php foreach ($privacyMap as $key => $textKey): 
                                        $isActive = ($key === $defaultPrivacy);
                                        $iconName = $privacyIconMap[$key];
                                    ?>
                                        <div class="menu-link <?php echo $isActive ? 'active' : ''; ?>" 
                                             data-value="<?php echo htmlspecialchars($key); ?>"
                                             data-text-key="<?php echo htmlspecialchars($textKey); ?>"
                                             data-icon="<?php echo htmlspecialchars($iconName); ?>">
                                            <div class="menu-link-icon">
                                                <span class="material-symbols-rounded"><?php echo $iconName; ?></span>
                                            </div>
                                            <div class="menu-link-text">
                                                <span data-i18n="<?php echo htmlspecialchars($textKey); ?>"></span>
                                            </div>
                                            <div class="menu-link-check-icon">
                                                <?php if ($isActive): ?>
                                                    <span class="material-symbols-rounded">check</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php // --- ▼▼▼ INICIO DE MODIFICACIÓN (id -> class) ▼▼▼ --- ?>
            <div class="post-content-area <?php echo $isPostActive ? 'active' : 'disabled'; ?>">
            <?php // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ --- ?>
                
                <div class="component-input-group">
                    <input type="text" id="publication-title" class="component-input" placeholder=" " maxlength="255">
                    <label for="publication-title" data-i18n="create_publication.titleLabel">Título (Opcional)</label>
                </div>
                <div class="component-input-group">
                    <textarea id="publication-text" class="component-input" rows="5" placeholder=" " maxlength="1000"></textarea>
                    <label for="publication-text" data-i18n="create_publication.placeholder"></label>
                </div>
                
                <?php // --- ▼▼▼ BLOQUE ELIMINADO ▼▼▼ --- ?>
                <?php // --- ▲▲▲ BLOQUE ELIMINADO ▲▲▲ --- ?>
                <div class="publication-preview-container" id="publication-preview-container">
                </div>
            </div>

            <?php // --- ▼▼▼ INICIO DE MODIFICACIÓN (id -> class) ▼▼▼ --- ?>
            <div class="poll-content-area <?php echo $isPollActive ? 'active' : 'disabled'; ?>">
            <?php // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ --- ?>
                <div class="component-input-group">
                    <input type="text" id="poll-question" class="component-input" placeholder=" " maxlength="1000">
                    <label for="poll-question" data-i18n="create_publication.pollQuestionLabel">Escribe tu pregunta...</label>
                </div>
                
                <?php // --- ▼▼▼ BLOQUE ELIMINADO ▼▼▼ --- ?>
                <?php // --- ▲▲▲ BLOQUE ELIMINADO ▲▲▲ --- ?>
                <div id="poll-options-container">
                    </div>
                
                <button type="button" class="component-action-button component-action-button--secondary" id="add-poll-option-btn">
                    <span class="material-symbols-rounded">add_circle</span>
                    <span data-i18n="create_publication.pollAddOption">Añadir opción</span>
                </button>
            </div>

            <div class="component-card__error" id="create-post-error-div" style="display: none; width: 100%; margin-bottom: 16px;"></div>
            <div class="component-card__actions" id="create-post-actions-footer">
                
                <button type="button" class="component-action-button--icon-square <?php echo $isPollActive ? 'disabled' : 'active'; ?>" 
                        id="attach-files-btn" 
                        data-tooltip="create_publication.attachTooltip">
                    <span class="material-symbols-rounded">attach_file</span>
                </button>
                
                <div id="attach-files-spacer" class="<?php echo $isPollActive ? 'active' : 'disabled'; ?>"></div>

                <button type="button" class="component-action-button--icon-square primary" id="publish-post-btn" data-tooltip="create_publication.publish" disabled>
                    <span class="material-symbols-rounded">send</span>
                </button>
            </div>

        </div> <?php // Cierre de component-card-action ?>
        
    </div>
</div>
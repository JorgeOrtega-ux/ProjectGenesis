<?php
// FILE: includes/sections/main/join-group.php
// $publicCommunities y $joinedCommunityIds son cargados por router.php
?>
<div class="section-content overflow-y <?php echo ($CURRENT_SECTION === 'join-group') ? 'active' : 'disabled'; ?>" data-section="join-group">

    <div class="page-toolbar-container" id="join-group-toolbar-container">
        <div class="page-toolbar-floating">
            <div class="toolbar-action-default">
                <div class="page-toolbar-left">
                    <button type="button"
                        class="page-toolbar-button"
                        data-action="toggleSectionHome" 
                        data-tooltip="join_group.backTooltip">
                        <span class="material-symbols-rounded">arrow_back</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="component-wrapper" style="padding-top: 82px;"> <!-- Añadido padding-top -->

        <div class="component-header-card">
            <h1 class="component-page-title" data-i18n="join_group.title"></h1>
            <p class="component-page-description" data-i18n="join_group.description"></p>
        </div>

        <?php outputCsrfInput(); ?>

        <!-- Formulario de Código Privado -->
        <div class="component-card component-card--action" id="join-group-form" style="gap: 16px;">
        
            <div class="component-card__content">
                <div class="component-card__icon">
                    <span class="material-symbols-rounded">key</span>
                </div>
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="join_group.formTitle"></h2>
                    <p class="component-card__description" data-i18n="join_group.formDescription"></p>
                </div>
            </div>
            
            <div class="component-input-group">
                <input type="text" id="join-code" name="join_code" class="component-input" required placeholder=" " maxlength="14" style="text-transform: uppercase;">
                <label for="join-code" data-i18n="join_group.codeLabel"></label>
            </div>

            <div class="component-card__error disabled" style="width: 100%;"></div>

            <div class="component-card__content" style="width: 100%; padding-top: 0;">
                <p class="component-card__description" style="font-size: 12px; text-align: center; width: 100%;" data-i18n="join_group.legal"></p>
            </div>

            <div class="component-card__actions" style="width: 100%;">
                <button type="button" class="component-action-button component-action-button--secondary" data-action="toggleSectionHome" data-i18n="join_group.cancelButton"></button>
                <button type="button" class="component-action-button component-action-button--primary" id="join-group-submit" data-auth-action="submit-join-code" data-i18n="join_group.joinButton"></button>
            </div>

        </div>
        
        <!-- --- ▼▼▼ INICIO DE NUEVA SECCIÓN (Comunidades Públicas) ▼▼▼ --- -->
        <div class="component-card component-card--column">
            <div class="component-card__content">
                <div class="component-card__icon"><span class="material-symbols-rounded">public</span></div>
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="join_group.publicTitle">Comunidades Públicas</h2>
                    <p class="component-card__description" data-i18n="join_group.publicDesc">Únete a una comunidad abierta.</p>
                </div>
            </div>
            <div class="card-list-container" id="public-community-list" style="width: 100%; gap: 8px; padding-top: 8px; display: flex; flex-direction: column;">
                <?php if (empty($publicCommunities)): ?>
                    <p class="component-card__description" data-i18n="join_group.noPublic" style="padding: 0 8px;">No hay comunidades públicas disponibles.</p>
                <?php else: ?>
                    <?php foreach ($publicCommunities as $community): ?>
                        <?php $isJoined = in_array($community['id'], $joinedCommunityIds); ?>
                        <div class="component-card" style="width: 100%; padding: 16px;">
                            <div class="component-card__content">
                                <div class="component-card__text">
                                    <h2 class="component-card__title" style="font-size: 16px;"><?php echo htmlspecialchars($community['name']); ?></h2>
                                </div>
                            </div>
                            <div class="component-card__actions">
                                <button type="button"
                                    class="component-button <?php echo $isJoined ? 'danger' : ''; ?>"
                                    data-action="<?php echo $isJoined ? 'leave-community' : 'join-community'; ?>"
                                    data-community-id="<?php echo $community['id']; ?>"
                                    data-i18n="join_group.<?php echo $isJoined ? 'leave' : 'join'; ?>">
                                    <?php echo $isJoined ? 'Abandonar' : 'Unirme'; ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <!-- --- ▲▲▲ FIN DE NUEVA SECCIÓN ▲▲▲ --- -->

    </div>
</div>
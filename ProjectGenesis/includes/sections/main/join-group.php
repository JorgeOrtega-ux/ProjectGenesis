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

    <div class="component-wrapper">
        <div class="component-header-card">
            <h1 class="component-page-title" data-i18n="join_group.title"></h1>
            <p class="component-page-description" data-i18n="join_group.description"></p>
        </div>

        <?php outputCsrfInput(); ?>

        <div class="component-card component-card--action" id="join-group-form">

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
                <input type="text" id="join-code" name="join_code" class="component-input" required placeholder=" " maxlength="14">
                <label for="join-code" data-i18n="join_group.codeLabel"></label>
            </div>

            <div class="component-card__error disabled"></div>

            <div class="component-card__content">
                <p class="component-card__description" data-i18n="join_group.legal"></p>
            </div>

            <div class="component-card__actions">
                <button type="button" class="component-action-button component-action-button--secondary" data-action="toggleSectionHome" data-i18n="join_group.cancelButton"></button>
                <button type="button" class="component-action-button component-action-button--primary" id="join-group-submit" data-auth-action="submit-join-code" data-i18n="join_group.joinButton"></button>
            </div>

        </div>

        <div class="component-card component-card--column">
            <div class="component-card__content">
                <div class="component-card__icon"><span class="material-symbols-rounded">public</span></div>
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="join_group.publicTitle">Comunidades Públicas</h2>
                    <p class="component-card__description" data-i18n="join_group.publicDesc">Únete a una comunidad abierta.</p>
                </div>
            </div>
            <div class="card-list-container" id="public-community-list">
                <?php if (empty($publicCommunities)): ?>
                    <p class="component-card__description" data-i18n="join_group.noPublic">No hay comunidades públicas disponibles.</p>
                <?php else: ?>
                    <?php foreach ($publicCommunities as $community): ?>
                        <?php $isJoined = in_array($community['id'], $joinedCommunityIds); ?>
                        <div class="component-card">
                            <div class="component-card__content">
                                <div class="component-card__text">
                                    <h2 class="component-card__title"><?php echo htmlspecialchars($community['name']); ?></h2>
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
    </div>
</div>
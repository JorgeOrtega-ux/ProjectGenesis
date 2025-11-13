<?php
// --- [HASTAGS] --- NUEVO ARCHIVO ---
// FILE: includes/sections/main/trends.php

global $pdo, $basePath;

// Esta variable $trendingHashtags es cargada por config/routing/router.php
if (!isset($trendingHashtags)) {
    $trendingHashtags = [];
}
?>

<style>
    .trends-list-container {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }
    .trend-item-card {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 16px;
        border: 1px solid #00000020;
        border-radius: 12px;
        background-color: #ffffff;
        text-decoration: none;
        transition: background-color 0.2s, border-color 0.2s;
    }
    .trend-item-card:hover {
        background-color: #f5f5fa;
        border-color: #00000040;
    }
    .trend-item__rank {
        font-size: 20px;
        font-weight: 700;
        color: #6b7280;
        width: 32px;
        text-align: right;
    }
    .trend-item__icon {
        width: 48px;
        height: 48px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        background-color: #f0f5fa;
        color: #0056b3;
    }
    .trend-item__icon .material-symbols-rounded {
        font-size: 24px;
        font-variation-settings: 'FILL' 1;
    }
    .trend-item__info {
        flex-grow: 1;
        min-width: 0;
    }
    .trend-item__tag {
        font-size: 16px;
        font-weight: 700;
        color: #1f2937;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .trend-item__count {
        font-size: 14px;
        font-weight: 500;
        color: #6b7280;
    }
</style>
<div class="section-content overflow-y <?php echo ($CURRENT_SECTION === 'trends') ? 'active' : 'disabled'; ?>" data-section="trends">

    <?php
    // --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
    // El div .page-toolbar-container ha sido eliminado
    // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
    ?>
    
    <div class="component-wrapper">

        <div class="component-header-card">
            <h1 class="component-page-title" data-i18n="trends.title">Tendencias</h1>
            <p class="component-page-description" data-i18n="trends.description">Descubre los hashtags más populares del momento.</p>
        </div>

        <div class="trends-list-container">
            <?php if (empty($trendingHashtags)): ?>
                <div class="component-card component-card--column">
                    <div class="component-card__content">
                        <div class="component-card__icon">
                            <span class="material-symbols-rounded">trending_down</span>
                        </div>
                        <div class="component-card__text">
                            <h2 class="component-card__title" data-i18n="trends.noTrends.title">No hay tendencias</h2>
                            <p class="component-card__description" data-i18n="trends.noTrends.desc">Aún no hay suficientes publicaciones con hashtags para mostrar tendencias.</p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($trendingHashtags as $index => $hashtag): ?>
                    <?php
                        $rank = $index + 1;
                        $tag = htmlspecialchars($hashtag['tag']);
                        $urlTag = urlencode('#' . $tag);
                        $count = (int)$hashtag['use_count'];
                    ?>
                    <a href="<?php echo $basePath . '/search?q=' . $urlTag; ?>" 
                       class="trend-item-card"
                       data-nav-js="true"
                       title="Buscar #<?php echo $tag; ?>">
                        
                        <span class="trend-item__rank"><?php echo $rank; ?>.</span>
                        
                        <div class="trend-item__icon">
                            <span class="material-symbols-rounded">tag</span>
                        </div>
                        
                        <div class="trend-item__info">
                            <div class="trend-item__tag">#<?php echo $tag; ?></div>
                            <div class="trend-item__count" data-i18n="trends.postCount" data-count="<?php echo $count; ?>">
                                <?php echo number_format($count); ?> publicaciones
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
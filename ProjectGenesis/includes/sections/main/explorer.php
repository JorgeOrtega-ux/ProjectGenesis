<?php
// FILE: includes/sections/main/explorer.php
// (Sección actualizada para mostrar comunidades como tarjetas estilo "Behance")
// (Versión 3: Con Toolbar, sin header, botón negro)

global $pdo, $basePath; // Aseguramos acceso a $basePath
$userId = $_SESSION['user_id'] ?? 0;

/*
 * NOTA: La lógica PHP para obtener comunidades de la BD se ha 
 * deshabilitado temporalmente para mostrar esta maqueta con datos de prueba.
*/

// --- INICIO DE CSS DE PRUEBA (CSS EN LÍNEA COMO SE SOLICITÓ) ---
?>
<style>
    /* * =============================================
     * ESTILOS DE PRUEBA PARA EXPLORER.PHP (Versión 3)
     * =============================================
    */

    /* Contenedor principal para 100% de ancho */
    .explorer-full-width-container {
        width: 100%;
        height: 100%;
        padding: 82px 24px 24px 24px; /* 82px para el header/toolbar fijo */
        box-sizing: border-box;
    }

    /* Grid responsivo para las tarjetas */
    .explorer-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 24px;
        width: 100%;
    }

    /* La tarjeta de comunidad (con padding) */
    .community-card-preview {
        border-radius: 12px;
        background-color: #ffffff;
        border: 1px solid #00000020;
        padding: 12px;
        box-shadow: 0 2px 4px #00000010;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    
    .community-card-preview:hover {
        transform: translateY(-4px);
        box-shadow: 0 6px 12px #00000015;
    }

    /* El banner superior (con border-radius) */
    .community-card__banner {
        height: 120px;
        background-size: cover;
        background-position: center;
        background-color: #f5f5fa;
        border-radius: 8px; /* Esquinas redondeadas para el banner */
    }

    /* Contenedor para todo el contenido debajo del banner */
    .community-card__content {
        padding: 0; 
    }

    /* Contenedor para el ícono (que se superpone) */
    .community-card__header {
        margin-top: -40px; 
        margin-left: 12px; 
        height: 64px; 
    }

    /* El ícono (cuadrado redondeado) */
    .community-card__icon {
        width: 64px;
        height: 64px;
        border-radius: 16px; /* Cuadrado redondeado */
        border: 4px solid #ffffff;
        background-color: #f0f0f0;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        box-shadow: 0 2px 4px #00000020;
        flex-shrink: 0;
    }

    .community-card__icon .material-symbols-rounded {
        font-size: 32px;
        color: #6b7280;
    }
    
    /* Contenedor para nombre y descripción */
    .community-card__info {
        padding-top: 0px; 
        padding-bottom: 16px;
        padding-left: 12px; 
        padding-right: 12px;
    }

    .community-card__name {
        font-weight: 700;
        font-size: 18px;
        color: #1f2937;
        margin-bottom: 4px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .community-card__description {
        font-size: 14px;
        color: #6b7280;
        line-height: 1.5;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
        min-height: 42px;
    }

    /* --- ▼▼▼ INICIO DE CAMBIOS (BOTÓN NEGRO) ▼▼▼ --- */
    
    /* Botón de unirse (Ahora negro) */
    .community-card__join-btn {
        width: 100%;
        height: 40px;
        background-color: #000; /* CAMBIADO a negro */
        color: #ffffff;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: background-color 0.2s ease;
    }
    
    .community-card__join-btn:hover {
        background-color: #333; /* CAMBIADO a gris oscuro */
    }

    /* --- ▲▲▲ FIN DE CAMBIOS (BOTÓN NEGRO) ▲▲▲ --- */


    /* Contenedor de estadísticas (con margen para alinear) */
    .community-card__stats {
        display: flex;
        gap: 16px;
        margin: 16px 12px 0 12px; 
        padding-top: 16px;
        border-top: 1px solid #00000015;
    }

    .community-card__stat-item {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
        font-weight: 500;
        color: #6b7280;
    }
    
    .community-card__stat-item .material-symbols-rounded {
        font-size: 16px;
    }
</style>
<?php // --- FIN DE CSS DE PRUEBA --- ?>


<div class="section-content overflow-y <?php echo ($CURRENT_SECTION === 'explorer') ? 'active' : 'disabled'; ?>" data-section="explorer">
    
    <?php // --- ▼▼▼ INICIO DE TOOLBAR AÑADIDO ▼▼▼ --- ?>
    <div class="page-toolbar-container" id="explorer-toolbar-container">
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
                        data-action="explorer-search-dummy" <?php // Acción sin función ?>
                        data-tooltip="admin.users.search">
                        <span class="material-symbols-rounded">search</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php // --- ▲▲▲ FIN DE TOOLBAR AÑADIDO ▲▲▲ --- ?>

    <?php // Contenedor principal de 100% de ancho (alineado al top) ?>
    <div class="explorer-full-width-container">
    
        <?php // --- ENCABEZADO ELIMINADO --- ?>

        <?php // Grid para las tarjetas ?>
        <div class="explorer-grid">

            <?php // --- INICIO DE TARJETA DE PRUEBA 1 --- ?>
            <div class="community-card-preview">
                <div class="community-card__banner" style="background-image: url('https://picsum.photos/400/120?random=1');"></div>
                
                <div class="community-card__content">
                    <div class="community-card__header">
                        <div class="community-card__icon" style="background-color: #0057FF;">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M19 12.875H13.875V11.125H19C19 10.159 18.216 9.375 17.25 9.375H13.875V7.625H17.625C17.625 6.659 16.841 5.875 15.875 5.875H12.125V12.875H19ZM17.25 14.625H13.875V18.125H17.25C18.216 18.125 19 17.341 19 16.375C19 15.409 18.216 14.625 17.25 14.625Z" fill="white"/>
                                <path d="M10.375 12.875H5V11.125H10.375V12.875Z" fill="white"/>
                            </svg>
                        </div>
                    </div>
                    
                    <div class="community-card__info">
                        <h3 class="community-card__name">Diseñadores Behance (Prueba)</h3>
                        <p class="community-card__description">Una comunidad de artistas que se unen para colaborar, compartir ideas y mostrar su trabajo.</p>
                    </div>

                    <button class="community-card__join-btn" data-i18n="join_group.join">Unirme</button>

                    <div class="community-card__stats">
                        <div class="community-card__stat-item">
                            <span class="material-symbols-rounded">group</span>
                            <span>124K Miembros</span>
                        </div>
                        <div class="community-card__stat-item">
                            <span class="material-symbols-rounded">shield_person</span>
                            <span>4 Admins</span>
                        </div>
                    </div>
                </div>
            </div>
            <?php // --- FIN DE TARJETA DE PRUEBA 1 --- ?>

            <?php // --- INICIO DE TARJETA DE PRUEBA 2 --- ?>
            <div class="community-card-preview">
                <div class="community-card__banner" style="background-image: url('https://picsum.photos/400/120?random=2');"></div>
                <div class="community-card__content">
                    <div class="community-card__header">
                        <div class="community-card__icon" style="background-color: #FAFAFA;">
                            <span class="material-symbols-rounded" style="color: #333;">code</span>
                        </div>
                    </div>
                    
                    <div class="community-card__info">
                        <h3 class="community-card__name">Desarrolladores Web</h3>
                        <p class="community-card__description">Todo sobre HTML, CSS, JavaScript, React, PHP y más. Resuelve dudas y comparte proyectos.</p>
                    </div>

                    <button class="community-card__join-btn" data-i18n="join_group.join">Unirme</button>

                    <div class="community-card__stats">
                        <div class="community-card__stat-item">
                            <span class="material-symbols-rounded">group</span>
                            <span>88K Miembros</span>
                        </div>
                        <div class="community-card__stat-item">
                            <span class="material-symbols-rounded">map</span>
                            <span>México</span>
                        </div>
                    </div>
                </div>
            </div>
            <?php // --- FIN DE TARJETA DE PRUEBA 2 --- ?>

            <?php // --- INICIO DE TARJETA DE PRUEBA 3 --- ?>
            <div class="community-card-preview">
                <div class="community-card__banner" style="background-image: url('https://picsum.photos/400/120?random=3');"></div>
                <div class="community-card__content">
                    <div class="community-card__header">
                        <div class="community-card__icon" style="background-color: #1f2937;">
                            <span class="material-symbols-rounded" style="color: white;">brush</span>
                        </div>
                    </div>
                    
                    <div class="community-card__info">
                        <h3 class="community-card__name">Ilustración Digital</h3>
                        <p class="community-card__description">Fans de Procreate, Photoshop e Illustrator. Comparte tus últimos trabajos y obtén feedback.</p>
                    </div>

                    <button class="community-card__join-btn" data-i18n="join_group.join">Unirme</button>

                    <div class="community-card__stats">
                        <div class="community-card__stat-item">
                            <span class="material-symbols-rounded">group</span>
                            <span>45K Miembros</span>
                        </div>
                        <div class="community-card__stat-item">
                            <span class="material-symbols-rounded">public</span>
                            <span>Latinoamérica</span>
                        </div>
                    </div>
                </div>
            </div>
            <?php // --- FIN DE TARJETA DE PRUEBA 3 --- ?>
            
            <?php // --- INICIO DE TARJETA DE PRUEBA 4 --- ?>
            <div class="community-card-preview">
                <div class="community-card__banner" style="background-image: url('https://picsum.photos/400/120?random=4');"></div>
                <div class="community-card__content">
                    <div class="community-card__header">
                        <div class="community-card__icon" style="background-color: #28a745;">
                            <span class="material-symbols-rounded" style="color: white;">restaurant</span>
                        </div>
                    </div>
                    
                    <div class="community-card__info">
                        <h3 class="community-card__name">Cocina Fácil</h3>
                        <p class="community-card__description">Recetas rápidas y fáciles para el día a día. ¡No necesitas ser un chef!</p>
                    </div>

                    <button class="community-card__join-btn" data-i18n="join_group.join">Unirme</button>

                    <div class="community-card__stats">
                        <div class="community-card__stat-item">
                            <span class="material-symbols-rounded">group</span>
                            <span>210K Miembros</span>
                        </div>
                    </div>
                </div>
            </div>
            <?php // --- FIN DE TARJETA DE PRUEBA 4 --- ?>

        </div> <?php // Fin de .explorer-grid ?>
    
    </div> <?php // Fin de .explorer-full-width-container ?>
</div>


<?php
/*
// --- INICIO DE LÓGICA ORIGINAL (COMENTADA) ---
global $pdo, $basePath; 
$publicCommunities = [];
$joinedCommunityIds = [];
$userId = $_SESSION['user_id'] ?? 0; 
// ... (resto de la lógica PHP original) ...
?>

<div class="section-content overflow-y <?php echo ($CURRENT_SECTION === 'explorer') ? 'active' : 'disabled'; ?>" data-section="explorer">
    <div class="component-wrapper">
        // ... (HTML original) ...
    </div>
</div>
*/
?>
<?php
// FILE: includes/sections/main/profile-tabs/view-profile-photos.php
// (NUEVO ARCHIVO)

// --- Variables globales y de sesión ---
global $pdo, $basePath;

// --- Datos cargados por router.php ---
$photos = $viewProfileData['photos'] ?? [];
$isOwnProfile = ($viewProfileData['friendship_status'] === 'self');
$username = $viewProfileData['username'] ?? 'Este usuario';

?>

<style>
    /* --- ESTILOS TEMPORALES (Mover a components.css) --- */
    .profile-photos-grid {
        display: grid;
        /* Crea una cuadrícula flexible que se ajusta */
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 8px;
        padding: 24px; /* Padding dentro de la tarjeta */
    }
    
    .photo-grid-item {
        display: block;
        width: 100%;
        padding-top: 100%; /* Truco para aspect-ratio 1:1 */
        position: relative;
        border-radius: 8px;
        overflow: hidden;
        background-color: #f5f5fa; /* Color de fondo mientras carga */
        border: 1px solid #00000015;
    }
    
    .photo-grid-item .photo-grid-image {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover; /* Asegura que la imagen llene el espacio */
        transition: transform 0.2s ease;
    }
    
    .photo-grid-item:hover .photo-grid-image {
        transform: scale(1.05); /* Efecto de zoom sutil */
    }
    
    /* Estilo para el placeholder de "Sin fotos" */
    .profile-photos-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 40px 24px;
        text-align: center;
        gap: 16px;
        min-height: 200px;
    }
    .profile-photos-empty .material-symbols-rounded {
        font-size: 48px;
        color: #6b7280;
    }
    /* --- FIN ESTILOS TEMPORALES --- */
</style>

<div class="profile-main-content active" data-profile-tab-content="fotos">
    
    <div class="component-card component-card--column">
        <div class="profile-content-header">
            <h2>Fotos</h2>
            <span class="profile-friends-count"><?php echo count($photos); ?></span>
        </div>

        <?php if (empty($photos)): ?>
            
            <div class="profile-photos-empty">
                <span class="material-symbols-rounded">photo_library</span>
                <div class="component-card__text">
                    <h2 class="component-card__title">Sin fotos</h2>
                    <p class="component-card__description">
                        <?php if ($isOwnProfile): ?>
                            Las fotos que subas en tus publicaciones aparecerán aquí.
                        <?php else: ?>
                            <?php echo htmlspecialchars($username); ?> no ha publicado ninguna foto.
                        <?php endif; ?>
                    </p>
                </div>
            </div>

        <?php else: ?>

            <div class="profile-photos-grid">
                <?php foreach ($photos as $photo): ?>
                    <a href="<?php echo $basePath . '/post/' . htmlspecialchars($photo['publication_id']); ?>" 
                       data-nav-js="true" 
                       class="photo-grid-item"
                       title="Ver publicación">
                        
                        <img src="<?php echo htmlspecialchars($photo['public_url']); ?>" 
                             alt="Foto de la publicación" 
                             class="photo-grid-image"
                             loading="lazy">
                    </a>
                <?php endforeach; ?>
            </div>
            
        <?php endif; ?>
        
    </div>
</div>
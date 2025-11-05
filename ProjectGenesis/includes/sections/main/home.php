<?php
// FILE: includes/sections/main/home.php

// --- Lógica de Grupos (Re-integrada) ---
$user_groups = [];
try {
    if (isset($_SESSION['user_id'], $pdo)) {
        $stmt = $pdo->prepare(
            "SELECT g.name 
             FROM groups g
             JOIN user_groups ug ON g.id = ug.group_id
             WHERE ug.user_id = ?"
        );
        $stmt->execute([$_SESSION['user_id']]);
        $user_groups = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    logDatabaseError($e, 'home.php - load user groups');
    // $user_groups se mantendrá vacío y se mostrará el mensaje de "sin grupos"
}

// --- Lógica de Avatar (Comentada - No se usa en esta versión) ---
/*
$defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";
$profileImageUrl = $_SESSION['profile_image_url'] ?? $defaultAvatar;
if (empty($profileImageUrl)) {
    $profileImageUrl = $defaultAvatar;
}
$usernameForAlt = $_SESSION['username'] ?? 'Usuario';
*/

?>

<!-- Estilos CSS específicos para esta nueva vista de home.php -->
<style>
    /* --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ --- */
    .section-content[data-section="home"] {
        /* Esto hace que el wrapper comience arriba y a la izquierda */
        align-items: flex-start;
        justify-content: flex-start;
    }
    /* --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ --- */

    /* Contenedor principal que permite el 100% de ancho */
    .home-wrapper-full-width {
        width: 100%;
        max-width: 100%; /* Anula el max-width de component-wrapper */
        margin: 0; /* ANULADO: margin: 0 auto; */
        padding: 16px; /* Mantenemos un padding general */
        display: flex;
        flex-direction: column;
        gap: 16px;
    box-sizing: border-box; /* Asegura que el padding no desborde */
}

/* 1. Caja para "Agregar un texto" (ELIMINADA) */
/*
.home-post-box {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 12px 16px;
    background-color: #ffffff;
    border: 1px solid #00000020; 
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05); 
}

.home-post-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
}

.home-post-input {
    flex-grow: 1; 
    border: none;
    outline: none;
    font-size: 18px; 
    font-weight: 500; 
    color: #1f2937;
    background-color: transparent;
    padding: 8px 0;
}

.home-post-input::placeholder {
    color: #6b7280; 
    font-weight: 500;
}
*/

    /* --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ --- */
    /* 1. NUEVO: Encabezado estático (como el de la foto) */
    .home-static-header {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 16px; /* Padding más generoso para un encabezado */
        background-color: #ffffff;
        border: 1px solid #00000020; /* Borde sutil como otros componentes */
        border-radius: 12px;
    }

    .home-header-icon {
        width: 40px; /* Tamaño del icono como el avatar de la foto */
        height: 40px;
        border-radius: 50%; /* Redondo como en la foto */
        background-color: #f5f5fa; /* Fondo gris claro */
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .home-header-icon .material-symbols-rounded {
        font-size: 24px;
        color: #333;
    }

    .home-header-title {
        flex-grow: 1;
        border: none;
        outline: none;
        font-size: 20px; /* Tamaño de fuente de encabezado */
        font-weight: 700; /* Negrita */
        color: #1f2937;
        background-color: transparent;
    }
    /* --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ --- */


    /* NUEVO: Estilo para el título "Mis Grupos" (Eliminado, ya no se necesita) */
    /*
    .home-section-title {
        font-size: 20px;
        font-weight: 700;
        color: #1f2937;
        margin: 16px 0 0 0; 
        padding-bottom: 8px;
        border-bottom: 1px solid #00000020;
    }
    */

    /* NUEVO: Contenedor de grid para las tarjetas de grupo */
    .home-community-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); /* Un poco más pequeñas para que quepan más */
        gap: 16px;
        /* padding-top: 16px; <-- Quitado, el gap del wrapper principal se encarga */
    }

    /* 2. Tarjeta de "Comunidad Pública" (reutilizada para todos los grupos) */
    .home-community-card {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 16px;
        background-color: #ffffff;
        border: 1px solid #00000020;
        border-radius: 12px;
        /* max-width: 280px; <-- quitamos el max-width para que se ajuste al grid */
        cursor: pointer;
        transition: background-color 0.2s ease, box-shadow 0.2s ease;
    }

    .home-community-card:hover {
        background-color: #f5f5fa; /* Feedback al pasar el mouse */
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .home-community-icon {
        width: 48px;
        height: 48px;
        border-radius: 8px;
        background-color: #f5f5fa; /* Fondo gris claro para el icono */
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .home-community-icon .material-symbols-rounded {
        font-size: 24px;
        color: #333;
    }

    .home-community-text h4 {
        font-size: 16px;
        font-weight: 600;
        color: #1f2937;
        margin: 0;
        line-height: 1.3;
    }

    .home-community-text p {
        font-size: 14px;
        color: #6b7280;
        margin: 0;
        line-height: 1.3;
    }
    
    /* NUEVO: Estilos para la tarjeta de "Sin Grupos" (tomados del original) */
    .home-no-groups-card {
        /* margin-top: 24px; <-- Quitado, el gap del wrapper principal se encarga */
        align-items: center;
        text-align: center;
        padding: 32px;
        border: 1px solid #00000020;
        border-radius: 12px;
        background: #fff;
    }
    .home-no-groups-card .component-card__icon {
        background-color: transparent;
        width: 60px;
        height: 60px;
        margin-bottom: 16px;
        border: none;
    }
    .home-no-groups-card .component-card__icon .material-symbols-rounded {
        font-size: 60px;
        color: #6b7280;
    }
    .home-no-groups-card .component-card__title {
        font-size: 20px;
    }
    .home-no-groups-card .component-card__description {
        max-width: 300px;
        margin-top: 8px;
    }
    .home-no-groups-card .component-card__actions {
        margin-top: 24px;
        gap: 12px;
        width: 100%;
        justify-content: center;
        display: flex; /* Asegura que se muestre */
    }

    /* Adaptación para móviles */
    @media (max-width: 768px) {
    .home-wrapper-full-width {
        padding: 12px; /* Menos padding en móvil */
    }

    /* --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ --- */
    .home-header-title {
        font-size: 18px; /* Fuente más pequeña en móvil */
    }
    /* --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ --- */
    
    /*
    .home-post-input {
        font-size: 16px; 
    }
    */
    
    .home-no-groups-card .component-card__actions {
            flex-direction: column;
            width: 100%;
            max-width: 300px;
        }
    }
</style>

<div class="section-content overflow-y <?php echo ($CURRENT_SECTION === 'home') ? 'active' : 'disabled'; ?>" data-section="home">
    
    <!-- Wrapper personalizado para 100% de ancho -->
    <div class="home-wrapper-full-width">

        <!-- --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ --- */
        <!-- 1. Encabezado estático (como en la foto) -->
        <div class="home-static-header">
            <div class="home-header-icon">
                <!-- Icono de "grupos" -->
                <span class="material-symbols-rounded">groups</span>
            </div>
            <!-- Título "Mis Grupos" (usando la clave i18n que ya teníamos) -->
            <div class="home-header-title" data-i18n="home.myGroups.title">
                Mis Grupos
            </div>
        </div>
        <!-- --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ --- */

        <!-- 2. Lógica de Grupos (Re-integrada y estilizada) -->
        <?php if (empty($user_groups)): ?>
            <!-- Estado 1: El usuario NO tiene grupos (Estilo del original) -->
            <div class="home-no-groups-card">
                <div class="component-card__icon">
                    <span class="material-symbols-rounded">groups</span> <!-- Icono de "groups" -->
                </div>
                <h2 class="component-card__title" data-i18n="home.noGroups.title">Aún no estás en ningún grupo</h2>
                <p class="component-card__description" data-i18n="home.noGroups.description">
                    Únete a un grupo con un código de invitación o explora grupos públicos.
                </p>
                <div class="component-card__actions">
                    <button type="button" 
                       class="component-action-button component-action-button--primary" 
                       data-action="toggleSectionJoinGroup" 
                       data-i18n="home.noGroups.joinButton">
                       Unirme a un grupo
                    </button>
                    <button type="button" 
                       class="component-action-button component-action-button--secondary" 
                       data-action="toggleSectionExplorer" 
                       data-i18n="home.noGroups.exploreButton">
                       Explorar grupos
                    </button>
                </div>
            </div>

        <?php else: ?>
            <!-- Estado 2: El usuario SÍ tiene grupos -->
            
            <!-- Título "Mis Grupos" (Eliminado, ahora está en el encabezado de arriba) -->
            <!-- <h2 class="home-section-title" data-i18n="home.myGroups.title">Mis Grupos</h2> -->

            <div class="home-community-grid">
                <?php foreach ($user_groups as $group): ?>
                    <!-- Tarjeta de grupo (como la de "Comunidad Pública") -->
                    <div class="home-community-card">
                        <div class="home-community-icon">
                            <span class="material-symbols-rounded">group</span> <!-- Icono de "group" -->
                        </div>
                        <div class="home-community-text">
                            <h4><?php echo htmlspecialchars($group['name']); ?></h4>
                            <p>ver publicaciones...</p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>

    </div>

</div>
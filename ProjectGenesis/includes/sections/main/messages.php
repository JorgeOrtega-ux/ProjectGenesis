<?php
// FILE: includes/sections/main/messages.php
// (MODIFICADO - Ahora acepta un usuario pre-cargado desde el router)
// (MODIFICADO OTRA VEZ - Para manejar $chatErrorType)
// --- ▼▼▼ MODIFICACIÓN (ELIMINADO BOTÓN DE FILTRO "COMUNIDADES") ▼▼▼ ---
global $basePath;
$defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";
$userAvatar = $_SESSION['profile_image_url'] ?? $defaultAvatar;

// --- ▼▼▼ INICIO DE FUNCIÓN HELPER AÑADIDA (LÓGICA DE 'view-profile.php') ▼▼▼ ---
/**
 * Genera el string "Activo hace X"
 * @param string $dateTimeString El timestamp UTC de la BD
 * @return string
 */
function getChatTimeAgo($dateTimeString)
{
    if (empty($dateTimeString)) {
        return 'Desconectado'; // Fallback
    }
    try {
        $lastSeenTime = new DateTime($dateTimeString, new DateTimeZone('UTC'));
        $currentTime = new DateTime('now', new DateTimeZone('UTC'));
        $interval = $currentTime->diff($lastSeenTime);

        $timeAgo = '';
        if ($interval->y > 0) {
            $timeAgo = ($interval->y == 1) ? '1 año' : $interval->y . ' años';
        } elseif ($interval->m > 0) {
            $timeAgo = ($interval->m == 1) ? '1 mes' : $interval->m . ' meses';
        } elseif ($interval->d > 0) {
            $timeAgo = ($interval->d == 1) ? '1 día' : $interval->d . ' días';
        } elseif ($interval->h > 0) {
            $timeAgo = ($interval->h == 1) ? '1 h' : $interval->h . ' h';
        } elseif ($interval->i > 0) {
            $timeAgo = ($interval->i == 1) ? '1 min' : $interval->i . ' min';
        } else {
            $timeAgo = 'unos segundos';
        }

        // Devolver el texto traducible (el JS se encargará de las claves i18n si esto falla)
        return ($timeAgo === 'unos segundos') ? 'Activo hace unos momentos' : "Activo hace $timeAgo";
    } catch (Exception $e) {
        return 'Desconectado'; // Fallback en caso de error
    }
}
// --- ▲▲▲ FIN DE FUNCIÓN HELPER AÑADIDA ▲▲▲ ---
?>
<style>
    /* Estilos específicos para el chat */
    .chat-layout-container {
        display: flex;
        width: 100%;
        height: 100%;
        padding: 12px;
        gap: 12px;
        overflow: hidden;
    }

    /* --- Panel Izquierdo (Lista de Amigos) --- */
    .chat-sidebar-left {
        width: 360px;
        height: 100%;
        box-shadow: 0 4px 12px #00000020;
        border-radius: 12px;
        display: flex;
        flex-direction: column;
        flex-shrink: 0;
    }

    /* ... (Estilos de .chat-sidebar-header, .chat-sidebar-search, .chat-sidebar-list, .chat-conversation-item, etc. sin cambios)... */
    .chat-sidebar-header {
        padding: 12px 16px;
        border-bottom: 1px solid #00000020;
        flex-shrink: 0;
    }

    .chat-sidebar-header .component-page-title {
        font-size: 24px;
        text-align: left;
        margin-bottom: 16px;
    }

    .chat-sidebar-search {
        position: relative;
    }

    .chat-sidebar-search .search-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #6b7280;
        pointer-events: none;
    }

    .chat-sidebar-search-input {
        width: 100%;
        height: 40px;
        border-radius: 50px;
        border: 1px solid #00000020;
        background-color: transparent;
        padding: 0 12px 0 44px;
        font-size: 15px;
        font-family: "Roboto Condensed", sans-serif;
        font-weight: 500;
        color: #000;
        outline: none;
    }

    .chat-sidebar-search-input:focus {
        background-color: #ffffff;
        border-color: #000;
    }

    .chat-sidebar-list {
        flex-grow: 1;
        overflow-y: auto;
        overflow-x: hidden;
        padding: 8px;
    }

    .chat-conversation-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px;
        border-radius: 8px;
        cursor: pointer;
        transition: background-color 0.2s;
        text-decoration: none;
        position: relative;
    }

    .chat-conversation-item:hover {
        background-color: #f5f5fa;
    }

    .chat-conversation-item.active {
        background-color: #f5f5fa;
    }

    .chat-item-avatar {
        width: 48px;
        height: 48px;
        flex-shrink: 0;
        position: relative;
    }

    .chat-item-avatar img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
    }

    .chat-item-status {
        position: absolute;
        bottom: 0;
        right: 0;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        border: 2px solid #ffffff;
        background-color: #ccc;
    }

    .chat-item-status.online {
        background-color: #28a745;
    }

    .chat-item-info {
        flex-grow: 1;
        min-width: 0;
    }

    .chat-item-info-header {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
    }

    .chat-item-username {
        font-size: 16px;
        font-weight: 700;
        color: #1f2937;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .chat-item-timestamp {
        font-size: 12px;
        color: #6b7280;
        flex-shrink: 0;
    }

    .chat-item-snippet-wrapper {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 4px;
    }

    .chat-item-snippet {
        font-size: 14px;
        color: #6b7280;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* --- ▼▼▼ INICIO DE NUEVOS ESTILOS (Indicadores Pin/Fav) ▼▼▼ --- */
    .chat-item-indicators {
        display: flex;
        align-items: center;
        gap: 4px;
        /* Se alinea con el unread-badge gracias al flex-shrink: 0 */
        flex-shrink: 0;
    }

    .chat-item-indicator {
        display: none;
        /* Oculto por defecto */
        color: #6b7280;
    }

    .chat-item-indicator .material-symbols-rounded {
        font-size: 16px;
        font-variation-settings: 'FILL' 1;
    }

    /* El JS (chat-manager.js) añadirá 'data-is-pinned="true"' al 'chat-conversation-item' */
    .chat-conversation-item[data-is-pinned="true"] .chat-item-indicator.pinned {
        display: inline-block;
        color: #F57C00;
        /* Naranja para el pin */
    }

    .chat-conversation-item[data-is-favorite="true"] .chat-item-indicator.favorite {
        display: inline-block;
        color: #206BD3;
        /* Azul para el favorito */
    }

    /* --- ▲▲▲ FIN DE NUEVOS ESTILOS (Indicadores Pin/Fav) ▲▲▲ --- */

    .chat-item-unread-badge {
        background-color: #c62828;
        color: #ffffff;
        font-size: 11px;
        font-weight: 600;
        padding: 2px 6px;
        border-radius: 50px;
        flex-shrink: 0;
    }

    .chat-list-placeholder {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 24px;
        text-align: center;
        color: #6b7280;
        gap: 16px;
        flex-direction: column;
    }

    /* --- ▼▼▼ INICIO DE NUEVOS ESTILOS (Context Menu) ▼▼▼ --- */
    .chat-item-actions {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        display: none;
        /* Oculto por defecto */
        z-index: 2;
    }

    /* Mostrar en hover sobre el item */
    .chat-conversation-item:hover .chat-item-actions {
        display: block;
    }

    /* Ocultar si el popover está activo (para que no se superponga) */
    .chat-item-actions.popover-active {
        display: none;
    }

    .chat-item-action-btn {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #f5f5fa;
        /* Color del hover del item */
        color: #1f2937;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        transition: background-color 0.2s;
    }

    .chat-item-action-btn:hover {
        background-color: #e0e0e0;
        /* Un poco más oscuro */
    }

    /* Estilo para el popover (se usará el CSS de .popover-module de components.css) */
    #chat-context-menu {
        width: 240px;
        z-index: 1000;
    }

    /* --- ▲▲▲ FIN DE NUEVOS ESTILOS --- */


    /* --- Panel Derecho (Chat Activo) --- */
    .chat-content-right {
        flex-grow: 1;
        height: 100%;
        display: flex;
        flex-direction: column;
        background-color: #ffffff;
           box-shadow: 0 4px 12px #00000020;
        border-radius: 12px;
    }

    .chat-content-placeholder {
        flex-grow: 1;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: #6b7280;
        gap: 16px;
    }

    .chat-content-placeholder .material-symbols-rounded {
        font-size: 64px;
    }

    .chat-content-placeholder span {
        font-size: 18px;
        font-weight: 500;
    }

    .chat-content-main {
        flex-grow: 1;
        overflow: auto;
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    .chat-content-main.disabled {
        display: none;
    }

    .chat-content-header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        border-bottom: 1px solid #00000020;
        flex-shrink: 0;
    }

    .chat-header-avatar {
        width: 40px;
        height: 40px;
    }

    .chat-header-avatar img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
    }

    .chat-header-info {
        flex-grow: 1;
    }

    .chat-header-username {
        font-size: 18px;
        font-weight: 700;
        color: #1f2937;
    }

    .chat-header-status {
        font-size: 13px;
        color: #6b7280;
    }

    .chat-header-status.online {
        color: #28a745;
        width: max-content;
        border-radius: 50px;
        padding: 0 8px;
        border: 1px solid #28a745;
    }

    .chat-header-status-typing {
        display: none;
        align-items: center;
        gap: 3px;
        font-size: 13px;
        font-weight: 600;
        color: #0056b3;
    }

    .chat-header-status-typing.active {
        display: flex;
    }

    .chat-header-status-typing .typing-dot {
        width: 4px;
        height: 4px;
        background-color: #0056b3;
        border-radius: 50%;
        animation: typing-bounce 1.2s infinite ease-in-out;
    }

    .chat-header-status-typing .typing-dot:nth-child(2) {
        animation-delay: 0.2s;
    }

    .chat-header-status-typing .typing-dot:nth-child(3) {
        animation-delay: 0.4s;
    }

    @keyframes typing-bounce {

        0%,
        80%,
        100% {
            transform: translateY(0);
        }

        40% {
            transform: translateY(-4px);
        }
    }

    .chat-header-status.disabled {
        display: none;
    }

    .chat-header-status-typing.disabled {
        display: none;
    }

    .chat-message-list {
        flex-grow: 1;
        padding: 16px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .chat-bubble {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        max-width: 75%;
    }

    .chat-bubble-avatar {
        width: 32px;
        height: 32px;
        flex-shrink: 0;
        margin-top: auto;
    }

    .chat-bubble-avatar img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
    }

    /* --- ▼▼▼ INICIO DE ESTILOS MODIFICADOS (Burbuja de Chat) ▼▼▼ --- */
    .chat-bubble-main-content {
        display: flex;
        flex-direction: column;
        gap: 8px;
        /* Espacio entre el texto y la cuadrícula de fotos */
        min-width: 0;
    }

    .chat-bubble-content {
        background-color: #f5f5fa;
        border-radius: 12px;
        padding: 12px;
        font-size: 15px;
        line-height: 1.5;
        color: #1f2937;
        word-break: break-word;
    }

    /* Ocultar la burbuja de texto si está vacía (solo fotos) */
    .chat-bubble-content:empty {
        display: none;
    }

    .chat-bubble.sent .chat-bubble-content {
        background-color: #000;
        color: #ffffff;
    }

    /* --- Cuadrícula de fotos en el chat --- */
    .chat-attachments-container {
        display: grid;
        gap: 4px;
        border-radius: 12px;
        overflow: hidden;
        max-width: 300px;
        /* Límite de ancho para la cuadrícula */
    }

    .chat-attachments-container[data-count="1"] {
        grid-template-columns: 1fr;
    }

    .chat-attachments-container[data-count="2"] {
        grid-template-columns: 1fr 1fr;
    }

    .chat-attachments-container[data-count="3"] {
        grid-template-columns: 1fr 1fr;
    }

    .chat-attachments-container[data-count="4"] {
        grid-template-columns: 1fr 1fr;
    }

    .chat-attachment-item {
        width: 100%;
        position: relative;
        background-color: #f5f5fa;
        padding-top: 100%;
        /* Forzar relación 1:1 */
        cursor: pointer;
    }

    .chat-attachment-item img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* Ajuste para 3 imágenes */
    .chat-attachments-container[data-count="3"] .chat-attachment-item:first-child {
        grid-column: 1 / 3;
        /* La primera ocupa 2 columnas */
        padding-top: 50%;
        /* Relación 2:1 */
    }

    /* --- ▲▲▲ FIN DE ESTILOS MODIFICADOS --- */

    .chat-bubble.sent {
        margin-left: auto;
        flex-direction: row-reverse;
    }

    .chat-bubble.sent .chat-bubble-avatar {
        display: none;
    }

    .chat-bubble.received {
        margin-right: auto;
    }

    /* --- Formulario de entrada --- */
    .chat-message-input-form {
        padding: 16px;
        border-top: 1px solid #00000020;
        display: flex;
        flex-direction: column;
        /* Cambiado a columna para la previsualización */
        gap: 12px;
        flex-shrink: 0;
    }

    /* --- ▼▼▼ INICIO DE NUEVOS ESTILOS (Previsualización) ▼▼▼ --- */
    .chat-attachment-preview-container {
        display: flex;
        flex-wrap: wrap;
        /* Permitir que las miniaturas pasen a la siguiente línea */
        gap: 8px;
        width: 100%;
    }

    .chat-attachment-preview-item {
        position: relative;
        width: 64px;
        height: 64px;
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid #00000020;
        flex-shrink: 0;
    }

    .chat-attachment-preview-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .chat-preview-remove-btn {
        position: absolute;
        top: 4px;
        right: 4px;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background-color: rgba(0, 0, 0, 0.7);
        color: #ffffff;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        transition: background-color 0.2s;
    }

    .chat-preview-remove-btn:hover {
        background-color: rgba(0, 0, 0, 0.9);
    }

    .chat-preview-remove-btn .material-symbols-rounded {
        font-size: 14px;
        font-variation-settings: 'FILL' 1;
    }

    .chat-input-main-row {
        display: flex;
        width: 100%;
        align-items: center;
        gap: 12px;
    }

    /* --- ▲▲▲ FIN DE NUEVOS ESTILOS (Previsualización) --- */

    .chat-input-field {
        flex-grow: 1;
        height: 44px;
        border: 1px solid #00000020;
        background-color: #f5f5fa;
        border-radius: 22px;
        padding: 0 16px;
        font-size: 15px;
        outline: none;
        transition: background-color 0.2s, border-color 0.2s;
    }

    .chat-input-field:focus {
        background-color: #ffffff;
        border-color: #000;
    }

    .chat-send-button {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        border: none;
        background-color: #000;
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background-color 0.2s;
        flex-shrink: 0;
    }

    .chat-send-button:disabled {
        background-color: #f5f5fa;
        color: #adb5bd;
        cursor: not-allowed;
    }

    .chat-send-button:not(:disabled):hover {
        background-color: #333;
    }

    .chat-send-button .material-symbols-rounded {
        font-size: 24px;
    }

    .chat-attach-button {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        border: 1px solid #00000020;
        background-color: #f5f5fa;
        color: #000;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background-color 0.2s;
        flex-shrink: 0;
    }

    .chat-attach-button:hover {
        background-color: #e9ecef;
    }

    @media (max-width: 768px) {

        /* ... (Estilos responsive de @media sin cambios) ... */
        .chat-sidebar-left {
            width: 100%;
            position: absolute;
            z-index: 10;
            transition: transform 0.3s ease-in-out;
        }

        .chat-content-right {
            width: 100%;
            position: absolute;
            z-index: 9;
        }

        .chat-layout-container.show-chat .chat-sidebar-left {
            transform: translateX(-100%);
        }

        .chat-layout-container:not(.show-chat) .chat-content-right {
            transform: translateX(100%);
        }

        .chat-content-header {
            padding: 12px 8px 12px 16px;
        }

        .chat-back-button {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border: none;
            background-color: transparent;
            border-radius: 50%;
            cursor: pointer;
        }

        .chat-back-button:hover {
            background-color: #f5f5fa;
        }
    }

    @media (min-width: 769px) {
        .chat-back-button {
            display: none;
        }
    }

    /* --- ▼▼▼ INICIO DE ESTILOS FALTANTES (AÑADIR) ▼▼▼ --- */

    /* 1. Estilos para el CONTEXTO DE RESPUESTA (dentro de la burbuja) - DISEÑO MODIFICADO */
    .chat-reply-context {
        position: relative;
        /* Para la barra lateral */
        padding: 8px 12px 8px 16px;
        /* Espacio para la barra */
        background-color: rgba(0, 0, 0, 0.05);
        /* Fondo gris claro */
        border-radius: 8px;
        /* Totalmente redondeado */
        max-width: 300px;
        /* Límite de ancho */
    }

    /* La barra vertical (como en la imagen) */
    .chat-reply-context::before {
        content: '';
        position: absolute;
        left: 4px;
        /* Separación de la barra */
        top: 4px;
        /* Separación superior */
        bottom: 4px;
        /* Separación inferior */
        width: 4px;
        /* Grosor de la barra */
        background-color: #000;
        /* Color de la barra (recibida) */
        border-radius: 2px;
    }

    .sent .chat-reply-context {
        background-color: rgba(255, 255, 255, 0.1);
    }

    /* Color de la barra en mensajes enviados (blanca sobre fondo negro) */
    .sent .chat-reply-context::before {
        background-color: #fff;
    }

    .chat-reply-context-user {
        font-size: 13px;
        font-weight: 700;
        color: #0056b3;
    }

    .sent .chat-reply-context-user {
        color: #aed6f1;
        /* Un azul más claro sobre fondo oscuro */
    }

    .chat-reply-context-text {
        font-size: 14px;
        color: inherit;
        opacity: 0.8;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* 2. Estilos para la VISTA PREVIA (sobre el input) */
    .chat-reply-preview-container {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 8px 12px;
        background-color: #f5f5fa;
        /* Color de fondo claro */
        border-radius: 8px;
        width: 100%;
        border-left: 4px solid #0056b3;
        /* Línea azul para indicar respuesta */
    }

    .chat-reply-preview-content {
        flex-grow: 1;
        min-width: 0;
        /* Para que el text-overflow funcione */
    }

    .chat-reply-preview-user {
        font-size: 13px;
        font-weight: 700;
        color: #0056b3;
        /* Azul para el usuario */
    }

    .chat-reply-preview-text {
        font-size: 14px;
        color: #1f2937;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .chat-reply-preview-close {
        width: 24px;
        height: 24px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: transparent;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        color: #6b7280;
        padding: 0;
        margin-left: 8px;
        transition: background-color 0.2s;
    }

    .chat-reply-preview-close:hover {
        background-color: #e0e0e0;
    }

    .chat-reply-preview-close .material-symbols-rounded {
        font-size: 18px;
    }

    /* --- ▼▼▼ INICIO DE CORRECCIÓN (Filtros) ▼▼▼ --- */
    .chat-sidebar-filters {
        padding: 12px;
        border-bottom: 1px solid #00000020;
        flex-shrink: 0;
        display: flex;
        gap: 8px;
        overflow-x: auto;
        /* <-- VUELTO A 'auto' */
        -ms-overflow-style: none;
        /* <-- AÑADIDO (IE/Edge) */
        scrollbar-width: none;
        /* <-- AÑADIDO (Firefox) */
    }

    .chat-sidebar-filters::-webkit-scrollbar {
        display: none;
        /* <-- AÑADIDO (Chrome/Safari) */
    }

    .chat-filter-badge {
        padding: 6px 16px;
        border-radius: 50px;
        background-color: transparent;
        border: 1px solid #00000020;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: background-color 0.2s, color 0.2s, border-color 0.2s;
        flex-shrink: 0;
        /* <-- VUELTO A '0' */
        user-select: none;
    }

    /* --- ▲▲▲ FIN DE CORRECCIÓN (Filtros) ▲▲▲ --- */

    /* --- ▲▲▲ FIN DE ESTILOS FALTANTES --- */
</style>

<?php
// FILE: includes/sections/main/messages.php
// (MODIFICADO - Ahora acepta un usuario pre-cargado desde el router)
// (MODIFICADO OTRA VEZ - Para manejar $chatErrorType)
global $basePath;
$defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";
$userAvatar = $_SESSION['profile_image_url'] ?? $defaultAvatar;

// --- ▼▼▼ INICIO DE NUEVA LÓGICA DE PRE-CARGA Y ERROR ▼▼▼ ---

// $preloadedChatUser es inyectado por router.php si la URL es /messages/uuid
// $chatErrorType es inyectado por router.php si la privacidad lo bloquea
$hasPreloadedUser = isset($preloadedChatUser) && $preloadedChatUser;
$hasChatError = isset($chatErrorType) && $chatErrorType; // <-- NUEVO

// Ocultar la barra lateral y mostrar el panel derecho si hay un chat O un error
$chatSidebarClass = ($hasPreloadedUser || $hasChatError) ? 'disabled' : 'active'; // <-- MODIFICADO
// Ocultar el placeholder por defecto si hay un chat O un error
$chatContentPlaceholderClass = ($hasPreloadedUser || $hasChatError) ? 'disabled' : 'active'; // <-- MODIFICADO

// Mostrar el chat principal solo si hay un usuario pre-cargado (sin error)
$chatContentMainClass = $hasPreloadedUser ? 'active' : 'disabled';

// Forzar la vista de chat en móvil si hay un chat O un error
$chatLayoutClass = ($hasPreloadedUser || $hasChatError) ? 'show-chat' : ''; // <-- MODIFICADO

$preloadedReceiverId = '';
$preloadedAvatar = $defaultAvatar;
$preloadedUsername = '...';
$preloadedStatusText = 'Offline';
$preloadedStatusClass = 'offline';
$preloadedChatType = 'dm'; // <-- TIPO DE CHAT POR DEFECTO

// Esta lógica solo se ejecuta si el chat se cargó con éxito
if ($hasPreloadedUser) {
    $preloadedReceiverId = htmlspecialchars($preloadedChatUser['id']);
    $preloadedAvatar = htmlspecialchars($preloadedChatUser['profile_image_url'] ?? $defaultAvatar);
    if (empty($preloadedAvatar)) $preloadedAvatar = "https://ui-avatars.com/api/?name=" . urlencode($preloadedChatUser['username']) . "&size=100&background=e0e0e0&color=ffffff";
    $preloadedUsername = htmlspecialchars($preloadedChatUser['username']);

    // --- ▼▼▼ INICIO DE MODIFICACIÓN (LÓGICA DE ROL/ESTADO) ▼▼▼ ---
    if ($preloadedChatUser['role'] === 'community') {
        $preloadedStatusText = 'Chat Grupal'; // O una clave i18n
        $preloadedStatusClass = 'active'; // Visible, pero sin "online"
    } elseif ($is_actually_online) { // $is_actually_online solo se define para DMs
        $preloadedStatusText = 'Online';
        $preloadedStatusClass = 'online active';
    } else {
        $preloadedStatusText = getChatTimeAgo($preloadedChatUser['last_seen']);
        $preloadedStatusClass = 'active'; // 'active' (visible), pero sin clase 'online'
    }

    // --- ▼▼▼ INICIO DE MODIFICACIÓN (PASAR TIPO DE CHAT) ▼▼▼ ---
    $preloadedChatType = $chatType ?? 'dm'; // $chatType es definido en router.php
    // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
}
// --- ▲▲▲ FIN DE NUEVA LÓGICA DE PRE-CARGA Y ERROR ▲▲▲ ---
?>

<div class="section-content <?php echo ($CURRENT_SECTION === 'messages') ? 'active' : 'disabled'; ?>" data-section="messages" style="overflow-y: hidden;">

    <div class="chat-layout-container <?php echo $chatLayoutClass; ?>" id="chat-layout-container">

        <div class="chat-sidebar-left <?php echo $chatSidebarClass; ?>" id="chat-sidebar-left">
            <div class="chat-sidebar-header">

                <div class="chat-sidebar-search">
                    <span class="material-symbols-rounded search-icon">search</span>

                    <input type="text" class="chat-sidebar-search-input" id="chat-friend-search" placeholder="Buscar conversación..." data-i18n-placeholder="chat.searchPlaceholder">
                </div>
            </div>

            <div class="chat-sidebar-filters" id="chat-sidebar-filters">
                <button type="button" class="chat-filter-badge active" data-filter="all" data-i18n="chat.filter.all">Todo</button>

                <button type="button" class="chat-filter-badge" data-filter="favorites" data-i18n="chat.filter.favorites">Favoritos</button>
                <button type="button" class="chat-filter-badge" data-filter="unread" data-i18n="chat.filter.unread">No leídos</button>
                <button type="button" class="chat-filter-badge" data-filter="archived" data-i18n="chat.filter.archived">Archivados</button>
            </div>
            <div class="chat-sidebar-list">

                <div class="chat-list-placeholder" id="chat-list-loader">
                    <span class="logout-spinner" style="width: 32px; height: 32px; border-width: 3px;"></span>
                    <span data-i18n="friends.list.loading">Cargando...</span>
                </div>

                <div class="chat-list-placeholder" id="chat-list-empty" style="display: none;">
                    <span class="material-symbols-rounded" id="chat-list-empty-icon">chat</span>
                    <span id="chat-list-empty-text" data-i1x8n="chat.empty.all">Inicia una conversación.</span>
                </div>
                <div id="chat-conversation-list">
                </div>

            </div>
        </div>

        <div class="chat-content-right" id="chat-content-right">

            <div class="chat-content-placeholder <?php echo $chatContentPlaceholderClass; ?>" id="chat-content-placeholder">
                <span class="material-symbols-rounded">chat</span>
                <span data-i18n="chat.selectConversation">Selecciona una conversación para empezar</span>
            </div>

            <?php if ($hasChatError): ?>
                <div class="chat-content-placeholder active" id="chat-content-error-placeholder">
                    <span class="material-symbols-rounded">lock</span>
                    <?php if ($chatErrorType === 'friends_only'): ?>
                        <span data-i18n="chat.error.friendsOnly">Este usuario solo acepta mensajes de amigos.</span>
                    <?php elseif ($chatErrorType === 'none'): ?>
                        <span data-i18n="chat.error.none">Este usuario no acepta mensajes privados.</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="chat-content-main <?php echo $chatContentMainClass; ?>"
                id="chat-content-main"
                data-autoload-chat="<?php echo $hasPreloadedUser ? 'true' : 'false'; ?>">

                <div class="chat-content-header">
                    <button type="button" class="chat-back-button" id="chat-back-button">
                        <span class="material-symbols-rounded">arrow_back</span>
                    </button>
                    <div class="chat-header-avatar" style="<?php echo ($preloadedChatUser['role'] === 'community') ? 'border-radius: 8px;' : ''; ?>">
                        <img src="<?php echo $preloadedAvatar; ?>" id="chat-header-avatar" alt="Avatar" style="<?php echo ($preloadedChatUser['role'] === 'community') ? 'border-radius: 8px;' : ''; ?>">
                    </div>
                    <div class="chat-header-info" id="chat-header-info">
                        <div class="chat-header-username" id="chat-header-username"><?php echo $preloadedUsername; ?></div>
                        <div class="chat-header-status <?php echo $preloadedStatusClass; ?>" id="chat-header-status" data-i18n-offline="chat.offline"><?php echo $preloadedStatusText; ?></div>
                        <div class="chat-header-status-typing disabled" id="chat-header-typing">
                            <span class="typing-dot"></span>
                            <span class="typing-dot"></span>
                            <span class="typing-dot"></span>
                            <span data-i18n="chat.typing">Escribiendo</span>
                        </div>
                    </div>
                </div>

                <div class="chat-message-list overflow-y" id="chat-message-list">
                </div>

                <form class="chat-message-input-form" id="chat-message-input-form" action="#">
                    <?php outputCsrfInput(); ?>
                    <input type="hidden" id="chat-target-id" value="<?php echo $preloadedReceiverId; ?>">

                    <input type="hidden" id="chat-type" value="<?php echo $preloadedChatType; ?>">
                    <input type="file" id="chat-attachment-input" class="visually-hidden"
                        accept="image/png, image/jpeg, image/gif, image/webp"
                        multiple>

                    <div class="chat-reply-preview-container" id="chat-reply-preview-container" style="display: none;"></div>

                    <div class="chat-input-pill-wrapper">

                        <div class="chat-attachment-preview-container" id="chat-attachment-preview-container">
                        </div>

                        <div class="chat-input-main-row">
                            <button type="button" class="chat-attach-button" id="chat-attach-button">
                                <span class="material-symbols-rounded">add_photo_alternate</span>
                            </button>

                            <input type="text" class="chat-input-field" id="chat-message-input" placeholder="Escribe tu mensaje..." data-i18n-placeholder="chat.messagePlaceholder" autocomplete="off" <?php echo ($hasPreloadedUser && !$hasChatError) ? '' : 'disabled'; ?>>

                            <button type="submit" class="chat-send-button" id="chat-send-button" disabled>
                                <span class="material-symbols-rounded">send</span>
                            </button>
                        </div>

                    </div>
                </form>
            </div>
        </div>

        <?php // --- ▼▼▼ INICIO DE MODIFICACIÓN (Menú Contextual) ▼▼▼ 
        ?>
        <div class="popover-module body-title disabled"
            data-module="moduleChatContext"
            id="chat-context-menu">
            <div class="menu-content">
                <div class="menu-list">

                    <div class="menu-link" data-action="pin-chat">
                        <div class="menu-link-icon"><span class="material-symbols-rounded">push_pin</span></div>
                        <div class="menu-link-text"><span data-i18n="chat.context.pinChat">Fijar chat</span></div>
                    </div>
                    <div class="menu-link" data-action="unpin-chat" style="display: none;">
                        <div class="menu-link-icon"><span class="material-symbols-rounded" style="font-variation-settings: 'FILL' 0;">push_pin</span></div>
                        <div class="menu-link-text"><span data-i18n="chat.context.unpinChat">Desfijar chat</span></div>
                    </div>

                    <div class="menu-link" data-action="add-favorites">
                        <div class="menu-link-icon"><span class="material-symbols-rounded">star_outline</span></div>
                        <div class="menu-link-text"><span data-i18n="chat.context.addFavorite">Añadir a favoritos</span></div>
                    </div>
                    <div class="menu-link" data-action="remove-favorites" style="display: none;">
                        <div class="menu-link-icon"><span class="material-symbols-rounded" style="color: #206BD3;">star</span></div>
                        <div class="menu-link-text"><span data-i18n="chat.context.removeFavorite" style="color: #206BD3;">Quitar de favoritos</span></div>
                    </div>

                    <div class="menu-link" data-action="archive-chat">
                        <div class="menu-link-icon"><span class="material-symbols-rounded">archive</span></div>
                        <div class="menu-link-text"><span data-i18n="chat.context.archiveChat">Archivar chat</span></div>
                    </div>
                    <div class="menu-link" data-action="unarchive-chat" style="display: none;">
                        <div class="menu-link-icon"><span class="material-symbols-rounded">unarchive</span></div>
                        <div class="menu-link-text"><span data-i18n="chat.context.unarchiveChat">Desarchivar chat</span></div>
                    </div>

                    <div style="height: 1px; background-color: #00000020; margin: 4px 8px;"></div>

                    <a class="menu-link"
                        data-action="friend-menu-profile"
                        data-nav-js="true"
                        href="#">
                        <div class="menu-link-icon">
                            <span class="material-symbols-rounded">person</span>
                        </div>
                        <div class="menu-link-text">
                            <span>Ver Perfil</span>
                        </div>
                    </a>

                    <div class="menu-link" data-action="block-user">
                        <div class="menu-link-icon"><span class="material-symbols-rounded">block</span></div>
                        <div class="menu-link-text"><span data-i18n="chat.context.blockUser">Bloquear</span></div>
                    </div>
                    <div class="menu-link" data-action="unblock-user" style="display: none;">
                        <div class="menu-link-icon"><span class="material-symbols-rounded">lock_open</span></div>
                        <div class="menu-link-text"><span data-i18n="chat.context.unblockUser">Desbloquear</span></div>
                    </div>

                    <div class="menu-link" data-action="delete-chat">
                        <div class="menu-link-icon" style="color: #c62828;"><span class="material-symbols-rounded">delete</span></div>
                        <div class="menu-link-text"><span data-i18n="chat.context.deleteChat" style="color: #c62828;">Eliminar chat</span></div>
                    </div>
                </div>
            </div>
        </div>
        <?php // --- ▲▲▲ FIN DE MODIFICACIÓN (Menú Contextual) ▲▲▲ 
        ?>

    </div>
</div>
<?php
// FILE: includes/sections/main/messages.php
// (MODIFICADO PARA MÚLTIPLES FOTOS)
global $basePath;
$defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";
$userAvatar = $_SESSION['profile_image_url'] ?? $defaultAvatar;
?>
<style>
/* Estilos específicos para el chat */
.chat-layout-container {
    display: flex;
    width: 100%;
    height: 100%;
    overflow: hidden;
}

/* --- Panel Izquierdo (Lista de Amigos) --- */
.chat-sidebar-left {
    width: 360px;
    height: 100%;
    border-right: 1px solid #00000020;
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
    background-color: #ffffff;
}
/* ... (Estilos de .chat-sidebar-header, .chat-sidebar-search, .chat-sidebar-list, .chat-conversation-item, etc. sin cambios)... */
.chat-sidebar-header { padding: 16px; border-bottom: 1px solid #00000020; flex-shrink: 0; }
.chat-sidebar-header .component-page-title { font-size: 24px; text-align: left; margin-bottom: 16px; }
.chat-sidebar-search { position: relative; }
.chat-sidebar-search .search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #6b7280; pointer-events: none; }
.chat-sidebar-search-input { width: 100%; height: 40px; border-radius: 8px; border: 1px solid #00000020; background-color: #f5f5fa; padding: 0 12px 0 44px; font-size: 15px; font-family: "Roboto Condensed", sans-serif; font-weight: 500; color: #000; outline: none; }
.chat-sidebar-search-input:focus { background-color: #ffffff; border-color: #000; }
.chat-sidebar-list { flex-grow: 1; overflow-y: auto; overflow-x: hidden; padding: 8px; }
.chat-conversation-item { display: flex; align-items: center; gap: 12px; padding: 12px; border-radius: 8px; cursor: pointer; transition: background-color 0.2s; text-decoration: none; position: relative; }
.chat-conversation-item:hover { background-color: #f5f5fa; }
.chat-conversation-item.active { background-color: #f5f5fa; }
.chat-item-avatar { width: 48px; height: 48px; flex-shrink: 0; position: relative; }
.chat-item-avatar img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; }
.chat-item-status { position: absolute; bottom: 0; right: 0; width: 12px; height: 12px; border-radius: 50%; border: 2px solid #ffffff; background-color: #ccc; }
.chat-item-status.online { background-color: #28a745; }
.chat-item-info { flex-grow: 1; min-width: 0; }
.chat-item-info-header { display: flex; justify-content: space-between; align-items: baseline; }
.chat-item-username { font-size: 16px; font-weight: 700; color: #1f2937; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.chat-item-timestamp { font-size: 12px; color: #6b7280; flex-shrink: 0; }
.chat-item-snippet-wrapper { display: flex; justify-content: space-between; align-items: center; margin-top: 4px; }
.chat-item-snippet { font-size: 14px; color: #6b7280; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.chat-item-unread-badge { background-color: #c62828; color: #ffffff; font-size: 11px; font-weight: 600; padding: 2px 6px; border-radius: 50px; flex-shrink: 0; }
.chat-list-placeholder { display: flex; align-items: center; justify-content: center; padding: 40px 24px; text-align: center; color: #6b7280; gap: 16px; flex-direction: column; }

/* --- Panel Derecho (Chat Activo) --- */
.chat-content-right { flex-grow: 1; height: 100%; display: flex; flex-direction: column; background-color: #ffffff; }
.chat-content-placeholder { flex-grow: 1; flex-direction: column; align-items: center; justify-content: center; color: #6b7280; gap: 16px; }
.chat-content-placeholder .material-symbols-rounded { font-size: 64px; }
.chat-content-placeholder span { font-size: 18px; font-weight: 500; }
.chat-content-main { flex-grow: 1; overflow: auto; display: flex; flex-direction: column; height: 100%; }
.chat-content-main.disabled { display: none; }
.chat-content-header { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-bottom: 1px solid #00000020; flex-shrink: 0; }
.chat-header-avatar { width: 40px; height: 40px; }
.chat-header-avatar img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; }
.chat-header-info { flex-grow: 1; }
.chat-header-username { font-size: 18px; font-weight: 700; color: #1f2937; }
.chat-header-status { font-size: 13px; color: #6b7280; }
.chat-header-status.online { color: #28a745; }
.chat-header-status-typing { display: none; align-items: center; gap: 3px; font-size: 13px; font-weight: 600; color: #0056b3; }
.chat-header-status-typing.active { display: flex; }
.chat-header-status-typing .typing-dot { width: 4px; height: 4px; background-color: #0056b3; border-radius: 50%; animation: typing-bounce 1.2s infinite ease-in-out; }
.chat-header-status-typing .typing-dot:nth-child(2) { animation-delay: 0.2s; }
.chat-header-status-typing .typing-dot:nth-child(3) { animation-delay: 0.4s; }
@keyframes typing-bounce { 0%, 80%, 100% { transform: translateY(0); } 40% { transform: translateY(-4px); } }
.chat-header-status.disabled { display: none; }
.chat-header-status-typing.disabled { display: none; }

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
    gap: 8px; /* Espacio entre el texto y la cuadrícula de fotos */
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
    max-width: 300px; /* Límite de ancho para la cuadrícula */
}
.chat-attachments-container[data-count="1"] { grid-template-columns: 1fr; }
.chat-attachments-container[data-count="2"] { grid-template-columns: 1fr 1fr; }
.chat-attachments-container[data-count="3"] { grid-template-columns: 1fr 1fr; }
.chat-attachments-container[data-count="4"] { grid-template-columns: 1fr 1fr; }

.chat-attachment-item {
    width: 100%;
    position: relative;
    background-color: #f5f5fa;
    padding-top: 100%; /* Forzar relación 1:1 */
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
    grid-column: 1 / 3; /* La primera ocupa 2 columnas */
    padding-top: 50%; /* Relación 2:1 */
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
    flex-direction: column; /* Cambiado a columna para la previsualización */
    gap: 12px;
    background-color: #ffffff;
    flex-shrink: 0;
}

/* --- ▼▼▼ INICIO DE NUEVOS ESTILOS (Previsualización) ▼▼▼ --- */
.chat-attachment-preview-container {
    display: flex;
    flex-wrap: wrap; /* Permitir que las miniaturas pasen a la siguiente línea */
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
.chat-preview-remove-btn:hover { background-color: rgba(0, 0, 0, 0.9); }
.chat-preview-remove-btn .material-symbols-rounded { font-size: 14px; font-variation-settings: 'FILL' 1; }

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
.chat-send-button:not(:disabled):hover { background-color: #333; }
.chat-send-button .material-symbols-rounded { font-size: 24px; }
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
.chat-attach-button:hover { background-color: #e9ecef; }

@media (max-width: 768px) {
    /* ... (Estilos responsive de @media sin cambios) ... */
    .chat-sidebar-left { width: 100%; position: absolute; z-index: 10; transition: transform 0.3s ease-in-out; }
    .chat-content-right { width: 100%; position: absolute; z-index: 9; }
    .chat-layout-container.show-chat .chat-sidebar-left { transform: translateX(-100%); }
    .chat-layout-container:not(.show-chat) .chat-content-right { transform: translateX(100%); }
    .chat-content-header { padding: 12px 8px 12px 16px; }
    .chat-back-button { display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border: none; background-color: transparent; border-radius: 50%; cursor: pointer; }
    .chat-back-button:hover { background-color: #f5f5fa; }
}
@media (min-width: 769px) {
    .chat-back-button { display: none; }
}

/* --- ▼▼▼ INICIO DE ESTILOS FALTANTES (AÑADIR) ▼▼▼ --- */

/* 1. Estilos para el CONTEXTO DE RESPUESTA (dentro de la burbuja) - DISEÑO MODIFICADO */
.chat-reply-context {
    position: relative; /* Para la barra lateral */
    padding: 8px 12px 8px 16px; /* Espacio para la barra */
    background-color: rgba(0, 0, 0, 0.05); /* Fondo gris claro */
    border-radius: 8px; /* Totalmente redondeado */
    max-width: 300px; /* Límite de ancho */
}

/* La barra vertical (como en la imagen) */
.chat-reply-context::before {
    content: '';
    position: absolute;
    left: 4px; /* Separación de la barra */
    top: 4px;  /* Separación superior */
    bottom: 4px; /* Separación inferior */
    width: 4px;  /* Grosor de la barra */
    background-color: #000; /* Color de la barra (recibida) */
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
    color: #aed6f1; /* Un azul más claro sobre fondo oscuro */
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
    background-color: #f5f5fa; /* Color de fondo claro */
    border-radius: 8px;
    width: 100%;
    border-left: 4px solid #0056b3; /* Línea azul para indicar respuesta */
}

.chat-reply-preview-content {
    flex-grow: 1;
    min-width: 0; /* Para que el text-overflow funcione */
}

.chat-reply-preview-user {
    font-size: 13px;
    font-weight: 700;
    color: #0056b3; /* Azul para el usuario */
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
/* --- ▲▲▲ FIN DE ESTILOS FALTANTES --- */
</style>

<?php
// FILE: includes/sections/main/messages.php
// (MODIFICADO - Ahora acepta un usuario pre-cargado desde el router)
global $basePath;
$defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";
$userAvatar = $_SESSION['profile_image_url'] ?? $defaultAvatar;

// --- ▼▼▼ INICIO DE NUEVA LÓGICA DE PRE-CARGA ▼▼▼ ---

// $preloadedChatUser es inyectado por router.php si la URL es /messages/username
$hasPreloadedUser = isset($preloadedChatUser) && $preloadedChatUser;

$chatSidebarClass = $hasPreloadedUser ? 'disabled' : 'active';
$chatContentPlaceholderClass = $hasPreloadedUser ? 'disabled' : 'active';
$chatContentMainClass = $hasPreloadedUser ? 'active' : 'disabled';
$chatLayoutClass = $hasPreloadedUser ? 'show-chat' : ''; // Para móvil

$preloadedReceiverId = '';
$preloadedAvatar = $defaultAvatar;
$preloadedUsername = '...';
$preloadedStatusText = 'Offline';
$preloadedStatusClass = 'offline';

if ($hasPreloadedUser) {
    $preloadedReceiverId = htmlspecialchars($preloadedChatUser['id']);
    $preloadedAvatar = htmlspecialchars($preloadedChatUser['profile_image_url'] ?? $defaultAvatar);
    if (empty($preloadedAvatar)) $preloadedAvatar = "https://ui-avatars.com/api/?name=" . urlencode($preloadedChatUser['username']) . "&size=100&background=e0e0e0&color=ffffff";
    $preloadedUsername = htmlspecialchars($preloadedChatUser['username']);
    
    // (Lógica de estado online/offline copiada de view-profile.php)
    $is_actually_online = false;
    try {
        $context = stream_context_create(['http' => ['timeout' => 0.5]]); 
        $jsonResponse = @file_get_contents('http://127.0.0.1:8766/get-online-users', false, $context);
        if ($jsonResponse !== false) {
            $data = json_decode($jsonResponse, true);
            if (isset($data['status']) && $data['status'] === 'ok' && isset($data['online_users'])) {
                $is_actually_online = in_array($preloadedChatUser['id'], $data['online_users']);
            }
        }
    } catch (Exception $e) { /* Fallo de WS, se asume offline */ }
    
    if ($is_actually_online) {
        $preloadedStatusText = 'Online';
        $preloadedStatusClass = 'online active';
    } else {
        $preloadedStatusText = 'Desconectado';
        $preloadedStatusClass = 'active'; // 'active' (visible), pero sin clase 'online'
    }
}
// --- ▲▲▲ FIN DE NUEVA LÓGICA DE PRE-CARGA ▲▲▲ ---
?>

<div class="section-content <?php echo ($CURRENT_SECTION === 'messages') ? 'active' : 'disabled'; ?>" data-section="messages" style="overflow-y: hidden;">
    
    <div class="chat-layout-container <?php echo $chatLayoutClass; ?>" id="chat-layout-container">

        <div class="chat-sidebar-left <?php echo $chatSidebarClass; ?>" id="chat-sidebar-left">
            <div class="chat-sidebar-header">
                <h1 class="component-page-title" data-i18n="chat.title">Mensajes</h1>
                <div class="chat-sidebar-search">
                    <span class="material-symbols-rounded search-icon">search</span>
                    <input type="text" class="chat-sidebar-search-input" id="chat-friend-search" placeholder="Buscar amigos..." data-i18n-placeholder="chat.searchPlaceholder">
                </div>
            </div>
            
            <div class="chat-sidebar-list" id="chat-conversation-list">
                <div class="chat-list-placeholder" id="chat-list-loader">
                    <span class="logout-spinner" style="width: 32px; height: 32px; border-width: 3px;"></span>
                    <span data-i18n="friends.list.loading">Cargando...</span>
                </div>
                <div class="chat-list-placeholder" id="chat-list-empty" style="display: none;">
                    <span class="material-symbols-rounded">chat</span>
                    <span>Inicia una conversación con un amigo.</span>
                </div>
            </div>
        </div>

        <div class="chat-content-right" id="chat-content-right">

            <div class="chat-content-placeholder <?php echo $chatContentPlaceholderClass; ?>" id="chat-content-placeholder">
                <span class="material-symbols-rounded">chat</span>
                <span data-i18n="chat.selectConversation">Selecciona una conversación para empezar</span>
            </div>

            <div class="chat-content-main <?php echo $chatContentMainClass; ?>" 
                 id="chat-content-main" 
                 data-autoload-chat="<?php echo $hasPreloadedUser ? 'true' : 'false'; ?>">
                
                <div class="chat-content-header">
                    <button type="button" class="chat-back-button" id="chat-back-button">
                        <span class="material-symbols-rounded">arrow_back</span>
                    </button>
                    <div class="chat-header-avatar">
                        <img src="<?php echo $preloadedAvatar; ?>" id="chat-header-avatar" alt="Avatar">
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

                <div class="chat-message-list" id="chat-message-list">
                    </div>

                <form class="chat-message-input-form" id="chat-message-input-form" action="#">
                    <?php outputCsrfInput(); ?>
                    <input type="hidden" id="chat-receiver-id" value="<?php echo $preloadedReceiverId; ?>">
                    
                    <input type="file" id="chat-attachment-input" class="visually-hidden" 
                           accept="image/png, image/jpeg, image/gif, image/webp" 
                           multiple> 

                    <div class="chat-reply-preview-container" id="chat-reply-preview-container" style="display: none;"></div>
                    <div class="chat-attachment-preview-container" id="chat-attachment-preview-container">
                        </div>
                    
                    <div class="chat-input-main-row">
                        <button type="button" class="chat-attach-button" id="chat-attach-button">
                             <span class="material-symbols-rounded">add_photo_alternate</span>
                        </button>
                        
                        <input type="text" class="chat-input-field" id="chat-message-input" placeholder="Escribe tu mensaje..." data-i18n-placeholder="chat.messagePlaceholder" autocomplete="off" <?php echo $hasPreloadedUser ? '' : 'disabled'; ?>>
                        
                        <button type="submit" class="chat-send-button" id="chat-send-button" disabled>
                            <span class="material-symbols-rounded">send</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
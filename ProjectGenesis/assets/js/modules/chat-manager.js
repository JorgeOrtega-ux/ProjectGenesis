// FILE: assets/js/modules/chat-manager.js
// (MODIFICADO PARA PAGINACIÓN, RESPUESTAS Y ELIMINAR)
// (MODIFICADO OTRA VEZ PARA USAR UUID EN URLS)
// (MODIFICADO DE NUEVO PARA ARREGLAR LA RECARGA DE LA LISTA DEL REMITENTE)
// (MODIFICADO CON CONSOLE.LOGS PARA DEPURACIÓN)
// (CORREGIDO: Lógica de filtrado de lista y bloqueo de input por privacidad)

import { callChatApi, callFriendApi } from '../services/api-service.js';
import { getTranslation } from '../services/i18n-manager.js';
import { showAlert } from '../services/alert-manager.js';

let currentChatUserId = null;
let friendCache = [];
const defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";

let selectedAttachments = [];
const MAX_CHAT_FILES = 4;

let isLoadingOlderMessages = false; 
let allMessagesLoaded = false;      
const CHAT_PAGE_SIZE = 30;          

// --- ▼▼▼ INICIO DE NUEVAS VARIABLES GLObales ▼▼▼ ---
let currentReplyMessageId = null; // Almacena el ID del mensaje al que se está respondiendo
let typingTimer;
let isTyping = false;
// --- ▲▲▲ FIN DE NUEVAS VARIABLES GLObales ▲▲▲ ---


/**
 * Escapa HTML simple para evitar XSS.
 */
function escapeHTML(str) {
    if (!str) return '';
    return str.replace(/[&<>"']/g, (m) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[m]));
}

/**
 * Formatea la hora de un timestamp (ej: "10:30 AM")
 */
function formatTime(dateString) {
    if (!dateString) return '';
    try {
        const date = new Date(dateString.includes('Z') ? dateString : dateString + 'Z');
        return date.toLocaleTimeString(window.userLanguage || 'es-ES', {
            hour: 'numeric',
            minute: '2-digit'
        });
    } catch (e) { return ''; }
}

/**
 * Renderiza la lista de conversaciones en el panel izquierdo.
 * @param {Array} conversations - La lista de conversaciones a renderizar.
 */
function renderConversationList(conversations) {
    console.log(`%c[RENDER] renderConversationList() -> Renderizando ${conversations.length} conversaciones.`, 'color: purple; font-weight: bold;');
    
    const listContainer = document.getElementById('chat-conversation-list');
    const loader = document.getElementById('chat-list-loader');
    const emptyEl = document.getElementById('chat-list-empty');
    if (!listContainer || !loader || !emptyEl) {
        console.error("[RENDER] Faltan elementos clave del DOM (listContainer, loader, emptyEl).");
        return;
    }
    
    loader.style.display = 'none';

    if (!conversations || conversations.length === 0) {
        console.log("[RENDER] No hay conversaciones para mostrar, mostrando 'emptyEl'.");
        emptyEl.style.display = 'flex';
        listContainer.innerHTML = ''; // Limpiar por si acaso
        return;
    }
    
    emptyEl.style.display = 'none';
    listContainer.innerHTML = ''; // Limpiar
    let html = '';

    conversations.forEach(friend => {
        const avatar = friend.profile_image_url || defaultAvatar;
        const statusClass = friend.is_online ? 'online' : 'offline';
        const timestamp = friend.last_message_time ? formatTime(friend.last_message_time) : '';
        
        let snippet = '...';
        if (friend.last_message === '[Imagen]') {
            snippet = `<span data-i18n="chat.snippet.image">[Imagen]</span>`;
        } else if (friend.last_message === 'Se eliminó este mensaje') {
            snippet = `<i data-i18n="chat.snippet.deleted">[Mensaje eliminado]</i>`;
        } else if (friend.last_message) {
            snippet = escapeHTML(friend.last_message);
        }
        
        const unreadCount = parseInt(friend.unread_count, 10);
        const unreadBadge = unreadCount > 0 ? `<span class="chat-item-unread-badge">${unreadCount}</span>` : '';
        
        // --- ▼▼▼ INICIO DE MODIFICACIÓN (Convertir DIV a A) ▼▼▼ ---
        // Asumiendo que friend.uuid ahora viene de la API
        const chatUrl = `${window.projectBasePath}/messages/${friend.uuid}`; 

        html += `
            <a class="chat-conversation-item" 
               href="${chatUrl}"
               data-nav-js="true"
               data-user-id="${friend.friend_id}" 
               data-username="${escapeHTML(friend.username)}" 
               data-avatar="${escapeHTML(avatar)}" 
               data-role="${escapeHTML(friend.role)}">
                
                <div class="chat-item-avatar" data-role="${escapeHTML(friend.role)}">
                    <img src="${escapeHTML(avatar)}" alt="${escapeHTML(friend.username)}">
                    <span class="chat-item-status ${statusClass}" id="chat-status-dot-${friend.friend_id}"></span>
                </div>
                <div class="chat-item-info">
                    <div class="chat-item-info-header">
                        <span class="chat-item-username">${escapeHTML(friend.username)}</span>
                        <span class="chat-item-timestamp">${timestamp}</span>
                    </div>
                    <div class="chat-item-snippet-wrapper">
                        <span class="chat-item-snippet">${snippet}</span>
                        ${unreadBadge}
                    </div>
                </div>
            </a>
        `;
        // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
    });
    listContainer.innerHTML = html;
    console.log("[RENDER] Renderización completada.");
}

/**
 * Carga la lista de amigos/conversaciones inicial.
 */
async function loadConversations() {
    console.groupCollapsed("%c[LOAD CONVERSATIONS] 🔄 loadConversations() iniciada...", "color: blue; font-weight: bold;");
    
    let onlineUserIds = {};
    try {
        const formData = new FormData();
        formData.append('action', 'get-friends-list');
        const friendResult = await callFriendApi(formData);
        if (friendResult.success) {
            friendResult.friends.forEach(friend => {
                if (friend.is_online) {
                    onlineUserIds[friend.friend_id] = true;
                }
            });
        }
    } catch (e) {
        console.error("[LOAD CONVERSATIONS] Error al obtener estado online de amigos:", e);
    }

    try {
        const formData = new FormData();
        formData.append('action', 'get-conversations');
        console.log("[LOAD CONVERSATIONS] Llamando a callChatApi('get-conversations')...");
        const result = await callChatApi(formData);
        console.log("[LOAD CONVERSATIONS] Respuesta de 'get-conversations':", result);

        if (result.success) {
            console.info(`[LOAD CONVERSATIONS] API Success. ${result.conversations.length} conversaciones recibidas.`);
            
            result.conversations.forEach(convo => {
                convo.is_online = !!onlineUserIds[convo.friend_id];
            });
            
            friendCache = result.conversations;
            console.log("[LOAD CONVERSATIONS] friendCache actualizado:", friendCache);
            
            const searchInput = document.getElementById('chat-friend-search');
            const currentQuery = searchInput ? searchInput.value : '';
            console.log(`[LOAD CONVERSATIONS] Query actual del input: "${currentQuery}"`);
            
            console.log("[LOAD CONVERSATIONS] Llamando a filterConversationList...");
            filterConversationList(currentQuery);
            
        } else {
            console.error("[LOAD CONVERSATIONS] La API reportó un fallo:", result.message);
            const listContainer = document.getElementById('chat-conversation-list');
            if (listContainer) listContainer.innerHTML = '<div class="chat-list-placeholder">Error al cargar.</div>';
        }
    } catch (e) {
        console.error("[LOAD CONVERSATIONS] Error de red o excepción:", e);
    }
    console.groupEnd();
}

// --- ▼▼▼ INICIO DE FUNCIÓN CORREGIDA (filterConversationList) ▼▼▼ ---
/**
 * Filtra la lista de amigos en el panel izquierdo.
 * (MODIFICADO: Muestra a TODOS los amigos. Los chats con historial
 * aparecen primero, y el input busca en todos los amigos.)
 */
function filterConversationList(query) {
    console.log(`%c[FILTER] filterConversationList() -> Query: "${query}"`, 'color: orange; font-weight: bold;');
    
    query = query.toLowerCase().trim();
    
    let conversationsToShow = [];

    if (!query) {
        // 1. SIN BÚSQUEDA: Mostrar TODOS los amigos del cache
        // Usamos [...friendCache] para crear una copia y no modificar el original
        conversationsToShow = [...friendCache]; 
        console.log(`[FILTER] Query vacía. Mostrando TODOS los ${conversationsToShow.length} amigos.`);
        
    } else {
        // 2. CON BÚSQUEDA: Filtrar de TODOS los amigos del cache
        conversationsToShow = friendCache.filter(friend => 
            friend.username.toLowerCase().includes(query)
        );
        console.log(`[FILTER] Query presente. Filtrando de *TODOS* ${friendCache.length} amigos... ${conversationsToShow.length} coinciden.`);
    }
    
    // 3. Ordenar la lista resultante (con o sin filtro)
    // Lógica de ordenación:
    //  - Amigos con mensajes (timeB) van antes que amigos sin mensajes (timeA == null)
    //  - Amigos con mensajes se ordenan por el más reciente (timeB - timeA)
    //  - Amigos sin mensajes se ordenan alfabéticamente (a.username.localeCompare(b.username))
    
    conversationsToShow.sort((a, b) => {
        const timeA = a.last_message_time ? new Date(a.last_message_time) : null;
        const timeB = b.last_message_time ? new Date(b.last_message_time) : null;

        // Caso 1: Ambos tienen historial. Ordenar por fecha.
        if (timeA && timeB) {
            if (isNaN(timeA) || isNaN(timeB)) return 0;
            return timeB - timeA; // El más reciente (B) primero
        }
        
        // Caso 2: B tiene historial, A no. B va primero.
        if (!timeA && timeB) {
            return 1;
        }
        
        // Caso 3: A tiene historial, B no. A va primero.
        if (timeA && !timeB) {
            return -1;
        }
        
        // Caso 4: Ninguno tiene historial. Ordenar alfabéticamente.
        return a.username.localeCompare(b.username);
    });

    console.log(`[FILTER] Ordenación completada. Se mostrarán ${conversationsToShow.length} chats.`);

    // 4. Renderizar la lista final
    console.log("[FILTER] Llamando a renderConversationList...");
    renderConversationList(conversationsToShow);
}
// --- ▲▲▲ FIN DE FUNCIÓN CORREGIDA (filterConversationList) ---


/**
 * Desplaza el contenedor de mensajes hasta el final.
 */
function scrollToBottom() {
    const msgList = document.getElementById('chat-message-list');
    if (msgList) {
        setTimeout(() => {
            msgList.scrollTop = msgList.scrollHeight;
        }, 0);
    }
}

// --- ▼▼▼ INICIO DE NUEVA FUNCIÓN (enableChatInput) ▼▼▼ ---
/**
 * Habilita o deshabilita la barra de input del chat.
 * @param {boolean} allow - true para permitir, false para bloquear.
 */
function enableChatInput(allow) {
    const input = document.getElementById('chat-message-input');
    const attachBtn = document.getElementById('chat-attach-button');
    const sendBtn = document.getElementById('chat-send-button');
    
    if (!input || !attachBtn || !sendBtn) return;

    if (allow) {
        input.disabled = false;
        attachBtn.disabled = false;
        input.placeholder = getTranslation('chat.placeholder', 'Escribe un mensaje...');
        validateSendButton(); // Re-validar el botón de envío
    } else {
        input.disabled = true;
        attachBtn.disabled = true;
        sendBtn.disabled = true; // Forzar deshabilitado
        input.value = ''; // Limpiar por si acaso
        input.placeholder = getTranslation('chat.privacyBlocked', 'No puedes enviar mensajes a este usuario.');
        
        // Limpiar adjuntos y previsualización de respuesta
        selectedAttachments = [];
        const previewContainer = document.getElementById('chat-attachment-preview-container');
        const fileInput = document.getElementById('chat-attachment-input');
        if (previewContainer) previewContainer.innerHTML = '';
        if (fileInput) fileInput.value = '';
        hideReplyPreview();
    }
}
// --- ▲▲▲ FIN DE NUEVA FUNCIÓN (enableChatInput) ---


// --- ▼▼▼ INICIO DE FUNCIÓN MODIFICADA (createMessageBubbleHtml) ▼▼▼ ---
/**
 * Crea y añade una burbuja de mensaje (enviado o recibido) al DOM.
 * @param {object} msg - El objeto del mensaje (debe tener message_text, attachment_urls, sender_id, id, status, reply_to...).
 * @param {boolean} isSent - true si es un mensaje enviado, false si es recibido.
 * @returns {string} El HTML de la burbuja.
 */
function createMessageBubbleHtml(msg, isSent) {
    const myUserId = parseInt(window.userId, 10);
    const myAvatar = document.querySelector('.header-profile-image')?.src || defaultAvatar;
    const myRole = window.userRole || 'user';
    
    let avatar, role;
    const bubbleClass = isSent ? 'sent' : 'received';

    if (isSent) {
        avatar = myAvatar;
        role = myRole;
    } else {
        const friendItem = document.querySelector(`.chat-conversation-item[data-user-id="${msg.sender_id}"]`);
        if (friendItem) {
            avatar = friendItem.dataset.avatar;
            role = friendItem.dataset.role;
        } else {
            avatar = document.getElementById('chat-header-avatar').src;
            role = 'user'; 
        }
    }
    
    // 1. Crear el menú de acciones (solo si el mensaje no está eliminado)
    let actionsMenuHtml = '';
    if (msg.status !== 'deleted') {
         actionsMenuHtml = `
            <div class="chat-bubble-actions">
                <button type="button" class="chat-action-btn" data-action="msg-reply" title="Responder">
                    <span class="material-symbols-rounded">reply</span>
                </button>
                <button type="button" class="chat-action-btn" data-action="msg-copy" title="Copiar">
                    <span class="material-symbols-rounded">content_copy</span>
                </button>
                ${isSent ? `
                <button type="button" class="chat-action-btn chat-action-btn--danger" data-action="msg-delete" title="Eliminar mensaje">
                    <span class="material-symbols-rounded">delete</span>
                </button>
                ` : ''}
            </div>
        `;
    }

    // 2. Crear parte de respuesta (si existe y el mensaje no está eliminado)
    let replyContextHtml = '';
    if (msg.reply_to_message_id && msg.status !== 'deleted') {
        const repliedUser = msg.replied_message_user || 'Usuario';
        let repliedText = msg.replied_message_text || '';
        
        // Comprobar si el mensaje al que se respondió fue eliminado
        if (repliedText === 'Se eliminó este mensaje') {
            repliedText = `<i>${escapeHTML(repliedText)}</i>`;
        } else {
            repliedText = escapeHTML(repliedText);
        }

        replyContextHtml = `
            <div class="chat-reply-context">
                <div class="chat-reply-context-user">${escapeHTML(repliedUser)}</div>
                <div class="chat-reply-context-text">${repliedText}</div>
            </div>
        `;
    }
    
    // 3. Crear parte de texto
    let textHtml = '';
    if (msg.status === 'deleted') {
        textHtml = `<div class="chat-bubble-content"><i>${escapeHTML(msg.message_text)}</i></div>`;
    } else {
        textHtml = `<div class="chat-bubble-content">${escapeHTML(msg.message_text)}</div>`;
    }

    // 4. Crear parte de adjuntos (solo si el mensaje no está eliminado)
    let attachmentsHtml = '';
    const attachments = (msg.attachment_urls && msg.status !== 'deleted') ? msg.attachment_urls.split(',') : [];
    
    if (attachments.length > 0) {
        let itemsHtml = '';
        attachments.forEach(url => {
            itemsHtml += `
                <div class="chat-attachment-item">
                    <img src="${escapeHTML(url)}" alt="Adjunto de chat" loading="lazy">
                </div>
            `;
        });
        
        attachmentsHtml = `
            <div class="chat-attachments-container" data-count="${attachments.length}">
                ${itemsHtml}
            </div>
        `;
    }

    // 5. Ensamblar burbuja
    const deletedClass = (msg.status === 'deleted') ? 'deleted' : '';
    const bubbleHtml = `
        <div class="chat-bubble ${bubbleClass} ${deletedClass}" data-message-id="${msg.id}" data-text-content="${escapeHTML(msg.message_text)}">
            <div class="chat-bubble-avatar" data-role="${escapeHTML(role)}">
                <img src="${escapeHTML(avatar)}" alt="Avatar">
            </div>
            <div class="chat-bubble-main-content">
                ${replyContextHtml}
                ${textHtml}
                ${attachmentsHtml}
            </div>
            ${actionsMenuHtml}
        </div>
    `;
    
    return bubbleHtml;
}
// --- ▲▲▲ FIN DE FUNCIÓN MODIFICADA (createMessageBubbleHtml) ---


/**
 * Renderiza la *primera página* del historial de chat.
 * @param {Array} messages - Array de mensajes (debe venir en orden DESC de la API).
 */
function renderChatHistory(messages) {
    const msgList = document.getElementById('chat-message-list');
    if (!msgList) return;

    msgList.innerHTML = '';
    const myUserId = parseInt(window.userId, 10);
    
    messages.reverse();
    
    let bubblesHtml = '';
    messages.forEach(msg => {
        const isSent = parseInt(msg.sender_id, 10) === myUserId;
        bubblesHtml += createMessageBubbleHtml(msg, isSent);
    });
    
    msgList.innerHTML = bubblesHtml;
    scrollToBottom();
}

/**
 * Carga y *antepone* mensajes más antiguos al chat.
 * @param {Array} messages - Array de mensajes (debe venir en orden DESC de la API).
 */
function prependChatHistory(messages) {
    const msgList = document.getElementById('chat-message-list');
    if (!msgList || messages.length === 0) return;

    const myUserId = parseInt(window.userId, 10);
    
    const oldScrollHeight = msgList.scrollHeight;
    
    messages.reverse();
    
    let bubblesHtml = '';
    messages.forEach(msg => {
        const isSent = parseInt(msg.sender_id, 10) === myUserId;
        bubblesHtml += createMessageBubbleHtml(msg, isSent);
    });
    
    msgList.insertAdjacentHTML('afterbegin', bubblesHtml);
    
    const newScrollHeight = msgList.scrollHeight;
    msgList.scrollTop = newScrollHeight - oldScrollHeight;
}

/**
 * Muestra u oculta el spinner de carga en la parte superior del chat.
 * @param {boolean} show - true para mostrar, false para ocultar.
 */
function showHistoryLoader(show) {
    const msgList = document.getElementById('chat-message-list');
    if (!msgList) return;

    let loader = document.getElementById('chat-history-loader');
    if (show) {
        if (!loader) {
            loader = document.createElement('div');
            loader.id = 'chat-history-loader';
            loader.className = 'chat-list-placeholder';
            loader.innerHTML = `<span class="logout-spinner" style="width: 24px; height: 24px; border-width: 3px;"></span>`;
            msgList.prepend(loader);
        }
    } else {
        if (loader) {
            loader.remove();
        }
    }
}

// --- ▼▼▼ INICIO DE FUNCIÓN MODIFICADA (loadChatHistory) ▼▼▼ ---
/**
 * Llama a la API para obtener el historial de chat, opcionalmente antes de un ID.
 * @param {number} friendId - El ID del amigo.
 * @param {number|null} beforeId - El ID del mensaje más antiguo (para paginación).
 */
async function loadChatHistory(friendId, beforeId = null) {
    const msgList = document.getElementById('chat-message-list');
    const isPaginating = beforeId !== null;

    if (isPaginating) {
        isLoadingOlderMessages = true;
        showHistoryLoader(true);
    } else {
        allMessagesLoaded = false;
        isLoadingOlderMessages = false;
        msgList.innerHTML = '<div class="chat-list-placeholder" id="chat-list-loader"><span class="logout-spinner" style="width: 32px; height: 32px; border-width: 3px;"></span></div>';
    }

    const formData = new FormData();
    formData.append('action', 'get-chat-history');
    formData.append('target_user_id', friendId);
    if (isPaginating) {
        formData.append('before_message_id', beforeId);
    }

    try {
        const result = await callChatApi(formData);
        
        if (isPaginating) showHistoryLoader(false);

        if (result.success) {
            if (result.messages.length < result.limit) {
                allMessagesLoaded = true;
            }

            if (isPaginating) {
                prependChatHistory(result.messages);
            } else {
                renderChatHistory(result.messages);
                
                // --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
                // Leemos el nuevo flag 'can_send_message' de la API
                if (result.can_send_message) {
                    enableChatInput(true); // Permitir escribir
                } else {
                    enableChatInput(false); // Bloquear
                }
                // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
            }
            
        } else {
            // --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
            // Si la API falla (ej. por privacidad en un futuro), bloqueamos el input
            if (!isPaginating) {
                msgList.innerHTML = `<div class="chat-list-placeholder">${getTranslation(result.message || 'js.api.errorServer')}</div>`;
                enableChatInput(false); // Bloquear input en caso de error al cargar
            } else {
                showAlert(getTranslation(result.message || 'js.api.errorServer'), 'error');
            }
             // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
        }
    } catch (e) {
        if (isPaginating) showHistoryLoader(false);
        if (!isPaginating) {
            msgList.innerHTML = '<div class="chat-list-placeholder">Error de conexión.</div>';
            enableChatInput(false); // Bloquear input en caso de error de red
        } else {
            showAlert(getTranslation('js.api.errorConnection'), 'error');
        }
    } finally {
        if (isPaginating) {
            isLoadingOlderMessages = false;
        }
    }
}
// --- ▲▲▲ FIN DE FUNCIÓN MODIFICADA (loadChatHistory) ---


// --- ▼▼▼ INICIO DE FUNCIÓN MODIFICADA (openChat) ▼▼▼ ---
/**
 * Carga el historial de chat con un amigo específico.
 */
async function openChat(friendId, username, avatar, role, isOnline) {
    const placeholder = document.getElementById('chat-content-placeholder');
    const chatMain = document.getElementById('chat-content-main');
    if (!chatMain || !placeholder) return; 

    // --- ▼▼▼ INICIO DE NUEVA LÓGICA (Limpiar respuesta) ▼▼▼ ---
    hideReplyPreview();
    // --- ▲▲▲ FIN DE NUEVA LÓGICA ▲▲▲ ---

    placeholder.classList.remove('active');
    placeholder.classList.add('disabled');
    chatMain.classList.remove('disabled');
    chatMain.classList.add('active');
    
    document.getElementById('chat-header-avatar').src = avatar;
    document.getElementById('chat-header-username').textContent = username;
    const statusEl = document.getElementById('chat-header-status');
    statusEl.textContent = isOnline ? getTranslation('chat.online', 'Online') : getTranslation('chat.offline', 'Offline');
    statusEl.className = isOnline ? 'chat-header-status online active' : 'chat-header-status active';
    
    const typingEl = document.getElementById('chat-header-typing');
    if (typingEl) typingEl.classList.add('disabled');
    
    document.getElementById('chat-message-input').disabled = true;
    document.getElementById('chat-send-button').disabled = true;

    // --- ▼▼▼ LÍNEA AÑADIDA ▼▼▼ ---
    // Resetear el placeholder por si el chat anterior estaba bloqueado
    const input = document.getElementById('chat-message-input');
    if (input) {
        input.placeholder = getTranslation('chat.placeholder', 'Escribe un mensaje...');
    }
    // --- ▲▲▲ FIN DE LÍNEA AÑADIDA ▲▲▲ ---

    document.getElementById('chat-attachment-preview-container').innerHTML = '';
    selectedAttachments = [];
    document.getElementById('chat-attachment-input').value = ''; 

    document.getElementById('chat-receiver-id').value = friendId;
    currentChatUserId = parseInt(friendId, 10);
    
    document.querySelectorAll('.chat-conversation-item').forEach(item => {
        item.classList.remove('active');
    });
    document.querySelector(`.chat-conversation-item[data-user-id="${friendId}"]`)?.classList.add('active');

    await loadChatHistory(friendId, null);
}
// --- ▲▲▲ FIN DE FUNCIÓN MODIFICADA (openChat) ---

/**
 * Habilita o deshabilita el botón de enviar.
 */
function validateSendButton() {
    const input = document.getElementById('chat-message-input');
    const sendBtn = document.getElementById('chat-send-button');
    if (!input || !sendBtn) return;

    // --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
    // No habilitar el botón si el input está deshabilitado (por privacidad)
    if (input.disabled) {
        sendBtn.disabled = true;
        return;
    }
    // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
    
    const hasText = input.value.trim().length > 0;
    const hasFiles = selectedAttachments.length > 0;
    
    sendBtn.disabled = !hasText && !hasFiles;
}

/**
 * Crea una miniatura de previsualización en el área de input.
 * @param {File} file - El archivo a previsualizar.
 */
function createAttachmentPreview(file) {
    const container = document.getElementById('chat-attachment-preview-container');
    if (!container) return;

    const previewDiv = document.createElement('div');
    previewDiv.className = 'chat-attachment-preview-item';
    
    const reader = new FileReader();
    reader.onload = (e) => {
        previewDiv.innerHTML = `
            <img src="${e.target.result}" alt="${escapeHTML(file.name)}">
            <button type="button" class="chat-preview-remove-btn">
                <span class="material-symbols-rounded">close</span>
            </button>
        `;
        
        previewDiv.querySelector('.chat-preview-remove-btn').addEventListener('click', () => {
            selectedAttachments = selectedAttachments.filter(f => f !== file);
            previewDiv.remove();
            document.getElementById('chat-attachment-input').value = '';
            validateSendButton();
        });
    };
    reader.readAsDataURL(file);
    
    container.appendChild(previewDiv);
}

/**
 * Maneja la selección de uno o más archivos.
 * @param {Event} e - El evento 'change' del input.
 */
function handleAttachmentChange(e) {
    const files = e.target.files;
    if (!files) return;

    const currentCount = selectedAttachments.length;
    const allowedNewCount = MAX_CHAT_FILES - currentCount;

    if (files.length > allowedNewCount) {
        showAlert(getTranslation('js.publication.errorFileCount', 'No puedes subir más de 4 archivos.').replace('4', MAX_CHAT_FILES), 'error');
    }

    const filesToProcess = Array.from(files).slice(0, allowedNewCount);

    const MAX_SIZE_MB = 5;
    const MAX_SIZE_BYTES = MAX_SIZE_MB * 1024 * 1024;
    const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    for (const file of filesToProcess) {
        if (!ALLOWED_TYPES.includes(file.type)) {
            showAlert(getTranslation('js.publication.errorFileType'), 'error');
            continue; 
        }
        
        if (file.size > MAX_SIZE_BYTES) {
            showAlert(getTranslation('js.publication.errorFileSize').replace('%size%', MAX_SIZE_MB), 'error');
            continue; 
        }

        selectedAttachments.push(file);
        createAttachmentPreview(file);
    }
    
    e.target.value = '';
    validateSendButton();
}

// --- ▼▼▼ INICIO DE NUEVAS FUNCIONES (Reply Preview) ▼▼▼ ---
/**
 * Muestra la vista previa de respuesta sobre el campo de texto.
 */
function showReplyPreview(messageId, username, text) {
    const container = document.getElementById('chat-reply-preview-container');
    if (!container) return;
    
    // --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
    // No permitir responder si el input está deshabilitado
    const input = document.getElementById('chat-message-input');
    if (input && input.disabled) {
        return;
    }
    // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

    // Truncar texto si es muy largo
    const snippet = text.length > 100 ? text.substring(0, 100) + '...' : text;

    container.innerHTML = `
        <div class="chat-reply-preview-content">
            <div class="chat-reply-preview-user">${escapeHTML(username)}</div>
            <div class="chat-reply-preview-text">${escapeHTML(snippet)}</div>
        </div>
        <button type="button" class="chat-reply-preview-close" id="chat-reply-preview-close">
            <span class="material-symbols-rounded">close</span>
        </button>
    `;
    container.style.display = 'flex';
    currentReplyMessageId = messageId;
    
    document.getElementById('chat-reply-preview-close').addEventListener('click', hideReplyPreview);
    document.getElementById('chat-message-input')?.focus();
}

/**
 * Oculta y limpia la vista previa de respuesta.
 */
function hideReplyPreview() {
    const container = document.getElementById('chat-reply-preview-container');
    if (container) {
        container.style.display = 'none';
        container.innerHTML = '';
    }
    currentReplyMessageId = null;
}
// --- ▲▲▲ FIN DE NUEVAS FUNCIONES (Reply Preview) ▲▲▲ ---


/**
 * Envía un mensaje de chat (texto y/o archivos).
 */
async function sendMessage() {
    console.log(`%c[SENDER] 🚀 sendMessage() iniciada...`, 'color: green; font-weight: bold;');
    
    const input = document.getElementById('chat-message-input');
    const sendBtn = document.getElementById('chat-send-button');
    const receiverId = document.getElementById('chat-receiver-id').value;
    const messageText = input.value.trim();

    if (!receiverId || sendBtn.disabled) {
        console.warn("[SENDER] Envío cancelado: receiverId vacío o botón deshabilitado.");
        return;
    }
    if (!messageText && selectedAttachments.length === 0) {
        console.warn("[SENDER] Envío cancelado: Mensaje y adjuntos vacíos.");
        return;
    }
    
    sendBtn.disabled = true;
    input.disabled = true;
    document.getElementById('chat-attach-button').disabled = true;

    const formData = new FormData();
    formData.append('action', 'send-message');
    formData.append('receiver_id', receiverId);
    formData.append('message_text', messageText);
    
    // --- ▼▼▼ INICIO DE NUEVA LÓGICA (Enviar reply_id) ▼▼▼ ---
    if (currentReplyMessageId) {
        formData.append('reply_to_message_id', currentReplyMessageId);
    }
    // --- ▲▲▲ FIN DE NUEVA LÓGICA ▲▲▲ ---
    
    for (const file of selectedAttachments) {
        formData.append('attachments[]', file, file.name);
    }

    try {
        console.log("[SENDER] Llamando a callChatApi('send-message')...");
        const result = await callChatApi(formData);
        console.log("[SENDER] Respuesta de 'send-message':", result);

        if (result.success && result.message_sent) {
            console.info("[SENDER] API Success. Mensaje enviado.");
            const bubbleHtml = createMessageBubbleHtml(result.message_sent, true);
            document.getElementById('chat-message-list').insertAdjacentHTML('beforeend', bubbleHtml);
            scrollToBottom();
            
            // --- ▼▼▼ ¡ESTA ES LA LÍNEA AÑADIDA! ▼▼▼ ---
            console.log("%c[SENDER] Mensaje enviado. Llamando a loadConversations() para actualizar la lista...", "color: green; font-weight: bold;");
            await loadConversations();
            console.log("%c[SENDER] loadConversations() completada.", "color: green; font-weight: bold;");
            // --- ▲▲▲ ¡FIN DE LA LÍNEA AÑADIDA! ▲▲▲ ---
            
            const friendItem = document.querySelector(`.chat-conversation-item[data-user-id="${receiverId}"]`);
            if (friendItem) {
                document.querySelectorAll('.chat-conversation-item').forEach(item => item.classList.remove('active'));
                friendItem.classList.add('active');
            }
            
            input.value = '';
            selectedAttachments = [];
            document.getElementById('chat-attachment-preview-container').innerHTML = '';
            document.getElementById('chat-attachment-input').value = '';
            // --- ▼▼▼ INICIO DE NUEVA LÓGICA (Limpiar reply) ▼▼▼ ---
            hideReplyPreview();
            // --- ▲▲▲ FIN DE NUEVA LÓGICA ▲▲▲ ---
            input.focus();
            
        } else {
            console.error("[SENDER] La API reportó un fallo al enviar el mensaje:", result.message);
            showAlert(getTranslation(result.message || 'js.api.errorServer'), 'error');
            
            // --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
            // Si el error es por privacidad, bloquear el input
            if (result.message === 'js.chat.errorPrivacyBlocked') {
                enableChatInput(false);
            }
            // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
        }
    } catch (e) {
        console.error("[SENDER] Error de red o excepción al enviar mensaje:", e);
        showAlert(getTranslation('js.api.errorConnection'), 'error');
    } finally {
        // --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
        // Solo re-habilitar el input si no está bloqueado por privacidad
        const currentInput = document.getElementById('chat-message-input');
        if (currentInput && !currentInput.disabled) {
             enableChatInput(true); // Re-habilita los 3 controles
        } else if (currentInput) {
             // Si está deshabilitado (por privacidad), asegurarse de que todo siga bloqueado
             enableChatInput(false);
        }
        // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
        console.log("[SENDER] Controles re-evaluados.");
    }
}

/**
 * Maneja un mensaje de chat entrante desde el WebSocket.
 */
export function handleChatMessageReceived(message) {
    console.log(`%c[WEBSOCKET] 📩 handleChatMessageReceived() -> Mensaje recibido:`, 'color: #00_80_80; font-weight: bold;', message);
    
    if (!message || !message.sender_id) {
        console.warn("[WEBSOCKET] Mensaje inválido o sin sender_id, ignorando.");
        return;
    }
    
    const senderId = parseInt(message.sender_id, 10);
    
    // Actualizar la lista de conversaciones (siempre)
    console.log("[WEBSOCKET] Llamando a loadConversations() para actualizar la lista del receptor...");
    loadConversations();
    
    // Si el chat está abierto, añade la burbuja
    if (senderId === currentChatUserId) {
        console.log("[WEBSOCKET] El chat está abierto, añadiendo burbuja.");
        const bubbleHtml = createMessageBubbleHtml(message, false);
        document.getElementById('chat-message-list').insertAdjacentHTML('beforeend', bubbleHtml);
        scrollToBottom();
    } else {
        console.log("[WEBSOCKET] El chat con este usuario NO está abierto. La lista se actualizará en segundo plano.");
    }
    // (La lógica de notificación de insignia se maneja en loadConversations)
}

// --- ▼▼▼ INICIO DE NUEVAS FUNCIONES (Manejo de WS) ▼▼▼ ---
/**
 * Transforma una burbuja de chat existente al estado "eliminado".
 * @param {HTMLElement} bubbleEl - El elemento DOM de la burbuja.
 */
function renderDeletedMessage(bubbleEl) {
    if (!bubbleEl) return;
    bubbleEl.classList.add('deleted');
    
    const mainContent = bubbleEl.querySelector('.chat-bubble-main-content');
    if (mainContent) {
        mainContent.innerHTML = `<div class="chat-bubble-content"><i>${getTranslation('chat.messageDeleted', 'Se eliminó este mensaje')}</i></div>`;
    }
    
    const actions = bubbleEl.querySelector('.chat-bubble-actions');
    if (actions) actions.remove();
}

/**
 * Maneja un evento de eliminación de mensaje desde WebSocket.
 * @param {object} payload - El payload del evento ({ message_id: ... }).
 */
export function handleMessageDeleted(payload) {
    console.log(`%c[WEBSOCKET] 🗑️ handleMessageDeleted() -> Payload:`, 'color: #00_80_80; font-weight: bold;', payload);
    
    if (!payload || !payload.message_id) return;
    
    const messageId = payload.message_id;
    const bubble = document.querySelector(`.chat-bubble[data-message-id="${messageId}"]`);
    
    if (bubble) {
        renderDeletedMessage(bubble);
    }
    
    // Actualizar la lista de conversaciones
    console.log("[WEBSOCKET] Mensaje eliminado. Llamando a loadConversations() para actualizar snippet...");
    loadConversations();
}
// --- ▲▲▲ FIN DE NUEVAS FUNCIONES (Manejo de WS) ▲▲▲ ---


/**
 * Muestra u oculta el indicador "escribiendo..."
 */
export function handleTypingEvent(senderId, isTyping) {
    if (parseInt(senderId, 10) !== currentChatUserId) {
        return; 
    }
    const statusEl = document.getElementById('chat-header-status');
    const typingEl = document.getElementById('chat-header-typing');
    if (statusEl && typingEl) {
        if (isTyping) {
            statusEl.classList.remove('active');
            statusEl.classList.add('disabled');
            typingEl.classList.add('active');
            typingEl.classList.remove('disabled');
        } else {
            statusEl.classList.add('active');
            statusEl.classList.remove('disabled');
            typingEl.classList.remove('active');
            typingEl.classList.add('disabled');
        }
    }
}

/**
 * Inicializa todos los listeners para la página de chat.
 */
export function initChatManager() {
    
    console.log("🏁 initChatManager() -> Inicializando listeners de chat.");
    
    const sectionsContainer = document.querySelector('.main-sections');
    if (sectionsContainer) {
        const observer = new MutationObserver((mutations) => {
            for (let mutation of mutations) {
                if (mutation.type === 'childList') {
                    const messagesSection = document.querySelector('[data-section="messages"]');
                    if (messagesSection) {
                        
                        console.log("👀 Observer: Detectada sección 'messages'.");
                        console.log("[INIT] Llamando a loadConversations() por primera vez.");
                        loadConversations();
                        document.dispatchEvent(new CustomEvent('request-friend-list-presence-update'));
                        
                        const chatMain = messagesSection.querySelector('#chat-content-main[data-autoload-chat="true"]');
                        if (chatMain) {
                            console.log("...Detectado 'data-autoload-chat', abriendo chat...");
                            
                            const headerInfo = messagesSection.querySelector('#chat-header-info');
                            const avatarImg = messagesSection.querySelector('#chat-header-avatar');
                            const receiverIdInput = messagesSection.querySelector('#chat-receiver-id');
                            const statusEl = messagesSection.querySelector('#chat-header-status');
                            
                            if (headerInfo && avatarImg && receiverIdInput && statusEl) {
                                const friendId = receiverIdInput.value;
                                const username = headerInfo.querySelector('#chat-header-username').textContent;
                                const avatar = avatarImg.src;
                                const isOnline = statusEl.classList.contains('online');
                                
                                openChat(friendId, username, avatar, 'user', isOnline);
                                document.getElementById('chat-layout-container')?.classList.add('show-chat');
                                chatMain.dataset.autoloadChat = 'false';
                            }
                        }

                    } else {
                        currentChatUserId = null; 
                    }
                }
            }
        });
        observer.observe(sectionsContainer, { childList: true });
    }

    document.body.addEventListener('click', async (e) => {
        const chatSection = e.target.closest('[data-section="messages"]');
        if (!chatSection) return;
        
        // --- ▼▼▼ INICIO DE MODIFICACIÓN (Listener de Clic Eliminado) ▼▼▼ ---
        // El click en 'chat-conversation-item' ahora es manejado por el router
        // (data-nav-js="true")
        // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
        
        const backBtn = e.target.closest('#chat-back-button');
        if (backBtn) {
            e.preventDefault();
            document.getElementById('chat-layout-container')?.classList.remove('show-chat');
            currentChatUserId = null;
            console.log("[UI] Botón 'Atrás' presionado. Llamando a loadConversations().");
            loadConversations(); 
            return;
        }

        const attachBtn = e.target.closest('#chat-attach-button');
        if (attachBtn) {
            e.preventDefault();
            if (selectedAttachments.length >= MAX_CHAT_FILES) {
                showAlert(getTranslation('js.publication.errorFileCount', 'No puedes subir más de 4 archivos.').replace('4', MAX_CHAT_FILES), 'error');
                return;
            }
            document.getElementById('chat-attachment-input')?.click();
            return;
        }

        // --- ▼▼▼ INICIO DE NUEVA LÓGICA (Acciones de burbuja) ▼▼▼ ---
        const actionBtn = e.target.closest('.chat-action-btn[data-action]');
        if (actionBtn) {
            const action = actionBtn.dataset.action;
            const messageBubble = actionBtn.closest('.chat-bubble');
            const messageId = messageBubble?.dataset.messageId;
            if (!messageId) return;

            if (action === 'msg-copy') {
                const textContent = messageBubble.dataset.textContent;
                if (textContent) {
                    try {
                        await navigator.clipboard.writeText(textContent);
                        showAlert(getTranslation('js.chat.copied', 'Mensaje copiado'), 'success');
                    } catch (err) {
                        showAlert(getTranslation('js.chat.copyError', 'Error al copiar'), 'error');
                    }
                }
            } 
            
            else if (action === 'msg-reply') {
                const username = messageBubble.classList.contains('sent') 
                    ? getTranslation('js.chat.replyToSelf', 'a ti mismo') 
                    : document.getElementById('chat-header-username').textContent;
                const textContent = messageBubble.dataset.textContent;
                
                showReplyPreview(messageId, username, textContent);
            } 
            
            else if (action === 'msg-delete') {
                if (!confirm(getTranslation('js.chat.confirmDelete', '¿Eliminar este mensaje? Esta acción no se puede deshacer.'))) {
                    return;
                }
                
                actionBtn.disabled = true;
                const formData = new FormData();
                formData.append('action', 'delete-message');
                formData.append('message_id', messageId);
                
                const result = await callChatApi(formData);
                if (result.success) {
                    // El WebSocket se encargará de actualizar la UI
                    // (Tanto para este cliente como para el receptor)
                    showAlert(getTranslation('js.chat.successDeleted', 'Mensaje eliminado'), 'info');
                } else {
                    showAlert(getTranslation(result.message || 'js.api.errorServer'), 'error');
                    actionBtn.disabled = false;
                }
            } 
            
            else if (action === 'msg-info') {
                // Función de información (futuro)
                showAlert(`Info para msg ID: ${messageId} (no implementado)`, 'info');
            }
            return;
        }
        // --- ▲▲▲ FIN DE NUEVA LÓGICA ▲▲▲ ---
    });
    
    document.body.addEventListener('submit', (e) => {
        const chatForm = e.target.closest('#chat-message-input-form');
        if (chatForm) {
            e.preventDefault();
            sendMessage();
            return;
        }
    });

    document.body.addEventListener('input', (e) => {
        const chatInput = e.target.closest('#chat-message-input');
        if (chatInput) {
            validateSendButton();
            
            const receiverId = document.getElementById('chat-receiver-id').value;
            if (receiverId && window.ws && window.ws.readyState === WebSocket.OPEN) {
                if (!isTyping) {
                    isTyping = true;
                    window.ws.send(JSON.stringify({
                        type: 'typing_start',
                        recipient_id: parseInt(receiverId, 10)
                    }));
                }
                clearTimeout(typingTimer);
                typingTimer = setTimeout(() => {
                    if (window.ws && window.ws.readyState === WebSocket.OPEN) {
                        window.ws.send(JSON.stringify({
                            type: 'typing_stop',
                            recipient_id: parseInt(receiverId, 10)
                        }));
                    }
                    isTyping = false;
                }, 2000); 
            }
        }
        
        const searchInput = e.target.closest('#chat-friend-search');
        if (searchInput) {
            // ¡Importante! El listener de 'input' llama a filterConversationList
            filterConversationList(searchInput.value);
        }
    });
    
    document.body.addEventListener('change', (e) => {
        const fileInput = e.target.closest('#chat-attachment-input');
        if (fileInput) {
            handleAttachmentChange(e);
        }
    });

    document.body.addEventListener('scroll', (e) => {
        const msgList = e.target.closest('#chat-message-list');
        if (!msgList) return;

        if (msgList.scrollTop === 0 && !isLoadingOlderMessages && !allMessagesLoaded) {
            console.log("Scroll en la parte superior, cargando mensajes antiguos...");
            
            const firstMessageEl = msgList.querySelector('.chat-bubble[data-message-id]');
            if (!firstMessageEl) return; 
            
            const beforeId = firstMessageEl.dataset.messageId;
            const friendId = document.getElementById('chat-receiver-id').value;
            
            if (friendId && beforeId) {
                loadChatHistory(friendId, beforeId);
            }
        }
    }, true); 

    document.addEventListener('user-presence-changed', (e) => {
        const { userId, status } = e.detail; 
        const chatItem = document.querySelector(`.chat-conversation-item[data-user-id="${userId}"]`);
        if (chatItem) {
            const dot = chatItem.querySelector('.chat-item-status');
            if (dot) {
                dot.classList.remove('online', 'offline');
                dot.classList.add(status); 
            }
        }
        if (parseInt(userId, 10) === currentChatUserId) {
            const statusEl = document.getElementById('chat-header-status');
            if (statusEl && statusEl.classList.contains('active')) {
                statusEl.textContent = status === 'online' ? getTranslation('chat.online', 'Online') : getTranslation('chat.offline', 'Offline');
                statusEl.className = status === 'online' ? 'chat-header-status online active' : 'chat-header-status active';
            }
        }
    });
}
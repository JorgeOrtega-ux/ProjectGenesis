// FILE: assets/js/modules/chat-manager.js
// (MODIFICADO PARA PAGINACIÓN, RESPUESTAS Y ELIMINAR)
// (MODIFICADO OTRA VEZ PARA USAR UUID EN URLS)

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
    const listContainer = document.getElementById('chat-conversation-list');
    const loader = document.getElementById('chat-list-loader');
    const emptyEl = document.getElementById('chat-list-empty');
    if (!listContainer || !loader || !emptyEl) return;
    
    loader.style.display = 'none';

    if (!conversations || conversations.length === 0) {
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
}

/**
 * Carga la lista de amigos/conversaciones inicial.
 */
async function loadConversations() {
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
        console.error("Error al obtener estado online de amigos:", e);
    }

    try {
        const formData = new FormData();
        formData.append('action', 'get-conversations');
        const result = await callChatApi(formData);

        if (result.success) {
            result.conversations.forEach(convo => {
                convo.is_online = !!onlineUserIds[convo.friend_id];
            });
            
            friendCache = result.conversations;
            
            const searchInput = document.getElementById('chat-friend-search');
            const currentQuery = searchInput ? searchInput.value : '';
            
            filterConversationList(currentQuery);
            
        } else {
            const listContainer = document.getElementById('chat-conversation-list');
            if (listContainer) listContainer.innerHTML = '<div class="chat-list-placeholder">Error al cargar.</div>';
        }
    } catch (e) {
        console.error("Error al cargar conversaciones:", e);
    }
}

/**
 * Filtra la lista de amigos en el panel izquierdo.
 */
function filterConversationList(query) {
    query = query.toLowerCase().trim();
    if (!query) {
        const conversationsWithHistory = friendCache.filter(convo => convo.last_message_time !== null);
        renderConversationList(conversationsWithHistory);
        return;
    }
    const filtered = friendCache.filter(friend => 
        friend.username.toLowerCase().includes(query)
    );
    renderConversationList(filtered);
}

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
                document.getElementById('chat-message-input').disabled = false;
            }
            
        } else {
            if (!isPaginating) {
                msgList.innerHTML = '<div class="chat-list-placeholder">Error al cargar mensajes.</div>';
            } else {
                showAlert(getTranslation('js.api.errorServer'), 'error');
            }
        }
    } catch (e) {
        if (isPaginating) showHistoryLoader(false);
        if (!isPaginating) {
            msgList.innerHTML = '<div class="chat-list-placeholder">Error de conexión.</div>';
        } else {
            showAlert(getTranslation('js.api.errorConnection'), 'error');
        }
    } finally {
        if (isPaginating) {
            isLoadingOlderMessages = false;
        }
    }
}


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

/**
 * Habilita o deshabilita el botón de enviar.
 */
function validateSendButton() {
    const input = document.getElementById('chat-message-input');
    const sendBtn = document.getElementById('chat-send-button');
    if (!input || !sendBtn) return;
    
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
    const input = document.getElementById('chat-message-input');
    const sendBtn = document.getElementById('chat-send-button');
    const receiverId = document.getElementById('chat-receiver-id').value;
    const messageText = input.value.trim();

    if (!receiverId || sendBtn.disabled) return;
    if (!messageText && selectedAttachments.length === 0) return;
    
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
        const result = await callChatApi(formData);
        if (result.success && result.message_sent) {
            const bubbleHtml = createMessageBubbleHtml(result.message_sent, true);
            document.getElementById('chat-message-list').insertAdjacentHTML('beforeend', bubbleHtml);
            scrollToBottom();
            
            await loadConversations();
            
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
            showAlert(getTranslation(result.message || 'js.api.errorServer'), 'error');
        }
    } catch (e) {
        showAlert(getTranslation('js.api.errorConnection'), 'error');
    } finally {
        input.disabled = false;
        document.getElementById('chat-attach-button').disabled = false;
        validateSendButton(); 
    }
}

/**
 * Maneja un mensaje de chat entrante desde el WebSocket.
 */
export function handleChatMessageReceived(message) {
    if (!message || !message.sender_id) return;
    
    const senderId = parseInt(message.sender_id, 10);
    
    // Actualizar la lista de conversaciones (siempre)
    loadConversations();
    
    // Si el chat está abierto, añade la burbuja
    if (senderId === currentChatUserId) {
        const bubbleHtml = createMessageBubbleHtml(message, false);
        document.getElementById('chat-message-list').insertAdjacentHTML('beforeend', bubbleHtml);
        scrollToBottom();
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
    if (!payload || !payload.message_id) return;
    
    const messageId = payload.message_id;
    const bubble = document.querySelector(`.chat-bubble[data-message-id="${messageId}"]`);
    
    if (bubble) {
        renderDeletedMessage(bubble);
    }
    
    // Actualizar la lista de conversaciones
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
    
    const sectionsContainer = document.querySelector('.main-sections');
    if (sectionsContainer) {
        const observer = new MutationObserver((mutations) => {
            for (let mutation of mutations) {
                if (mutation.type === 'childList') {
                    const messagesSection = document.querySelector('[data-section="messages"]');
                    if (messagesSection) {
                        
                        loadConversations();
                        document.dispatchEvent(new CustomEvent('request-friend-list-presence-update'));
                        
                        const chatMain = messagesSection.querySelector('#chat-content-main[data-autoload-chat="true"]');
                        if (chatMain) {
                            console.log("Detectado 'data-autoload-chat', abriendo chat...");
                            
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
        // const friendItem = e.target.closest('.chat-conversation-item');
        // if (friendItem) {
        //     ... (ESTE BLOQUE SE HA ELIMINADO) ...
        //     return;
        // }
        // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
        
        const backBtn = e.target.closest('#chat-back-button');
        if (backBtn) {
            e.preventDefault();
            document.getElementById('chat-layout-container')?.classList.remove('show-chat');
            currentChatUserId = null;
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
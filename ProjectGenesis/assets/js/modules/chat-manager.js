// FILE: assets/js/modules/chat-manager.js
// (MODIFICADO PARA MÚLTIPLES FOTOS)

import { callChatApi, callFriendApi } from '../services/api-service.js';
import { getTranslation } from '../services/i18n-manager.js';
import { showAlert } from '../services/alert-manager.js';

let currentChatUserId = null;
let friendCache = []; // Almacena la lista de amigos para el filtrado
const defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";

// --- ▼▼▼ INICIO DE MODIFICACIÓN (Estado de adjuntos) ▼▼▼ ---
let selectedAttachments = []; // Cambiado de null a array
const MAX_CHAT_FILES = 4;
// --- ▲▲▲ FIN DE MODIFICACIÓN ---

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
        const snippet = friend.last_message ? escapeHTML(friend.last_message) : '...';
        const unreadCount = parseInt(friend.unread_count, 10);
        const unreadBadge = unreadCount > 0 ? `<span class="chat-item-unread-badge">${unreadCount}</span>` : '';
        
        html += `
            <div class="chat-conversation-item" data-user-id="${friend.friend_id}" data-username="${escapeHTML(friend.username)}" data-avatar="${escapeHTML(avatar)}" data-role="${escapeHTML(friend.role)}">
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
            </div>
        `;
    });
    listContainer.innerHTML = html;
}

/**
 * Carga la lista de amigos/conversaciones inicial.
 */
async function loadConversations() {
    // ... (Esta función no necesita cambios, `callFriendApi` y `callChatApi` siguen siendo válidos) ...
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
            renderConversationList(friendCache);
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
        renderConversationList(friendCache);
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
        msgList.scrollTop = msgList.scrollHeight;
    }
}


// --- ▼▼▼ INICIO DE FUNCIÓN NUEVA/MODIFICADA (Renderizado de Burbuja) ▼▼▼ ---

/**
 * Crea y añade una burbuja de mensaje (enviado o recibido) al DOM.
 * @param {object} msg - El objeto del mensaje (debe tener message_text, attachment_urls, sender_id).
 * @param {boolean} isSent - true si es un mensaje enviado, false si es recibido.
 */
function renderMessageBubble(msg, isSent) {
    const msgList = document.getElementById('chat-message-list');
    if (!msgList) return;

    const myUserId = parseInt(window.userId, 10);
    const myAvatar = window.profile_image_url || defaultAvatar;
    const myRole = window.userRole || 'user';
    
    let avatar, role;
    const bubbleClass = isSent ? 'sent' : 'received';

    if (isSent) {
        avatar = myAvatar;
        role = myRole;
    } else {
        const friendItem = document.querySelector(`.chat-conversation-item.active`);
        if (!friendItem) {
             console.error("No se puede renderizar burbuja recibida, no hay chat activo.");
             return;
        }
        avatar = friendItem.dataset.avatar;
        role = friendItem.dataset.role;
    }
    
    // 1. Crear parte de texto (se oculta con CSS si está vacío)
    const textHtml = `
        <div class="chat-bubble-content">
            ${escapeHTML(msg.message_text)}
        </div>
    `;
    
    // 2. Crear parte de adjuntos
    let attachmentsHtml = '';
    const attachments = msg.attachment_urls ? msg.attachment_urls.split(',') : [];
    
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

    // 3. Ensamblar burbuja
    const bubbleHtml = `
        <div class="chat-bubble ${bubbleClass}">
            <div class="chat-bubble-avatar" data-role="${escapeHTML(role)}">
                <img src="${escapeHTML(avatar)}" alt="Avatar">
            </div>
            <div class="chat-bubble-main-content">
                ${textHtml}
                ${attachmentsHtml}
            </div>
        </div>
    `;
    
    msgList.insertAdjacentHTML('beforeend', bubbleHtml);
}
// --- ▲▲▲ FIN DE FUNCIÓN NUEVA/MODIFICADA ---


/**
 * Renderiza las burbujas de chat en el panel derecho.
 */
function renderChatHistory(messages) {
    const msgList = document.getElementById('chat-message-list');
    if (!msgList) return;

    msgList.innerHTML = '';
    const myUserId = parseInt(window.userId, 10);
    
    messages.forEach(msg => {
        const isSent = parseInt(msg.sender_id, 10) === myUserId;
        renderMessageBubble(msg, isSent); // Usar la nueva función
    });
    
    scrollToBottom();
}

/**
 * Carga el historial de chat con un amigo específico.
 */
async function openChat(friendId, username, avatar, role, isOnline) {
    const placeholder = document.getElementById('chat-content-placeholder');
    const chatMain = document.getElementById('chat-content-main');
    if (!chatMain || !placeholder) return; 

    // Actualizar UI
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
    
    document.getElementById('chat-message-list').innerHTML = '<div class="chat-list-placeholder" id="chat-list-loader"><span class="logout-spinner" style="width: 32px; height: 32px; border-width: 3px;"></span></div>';
    document.getElementById('chat-message-input').disabled = true;
    
    // --- ▼▼▼ INICIO DE MODIFICACIÓN (Resetear adjuntos) ▼▼▼ ---
    document.getElementById('chat-send-button').disabled = true;
    document.getElementById('chat-attachment-preview-container').innerHTML = '';
    selectedAttachments = [];
    document.getElementById('chat-attachment-input').value = ''; // Limpiar el input
    // --- ▲▲▲ FIN DE MODIFICACIÓN ---

    document.getElementById('chat-receiver-id').value = friendId;
    currentChatUserId = parseInt(friendId, 10);
    
    // Marcar como activo en la lista
    document.querySelectorAll('.chat-conversation-item').forEach(item => {
        item.classList.remove('active');
    });
    document.querySelector(`.chat-conversation-item[data-user-id="${friendId}"]`)?.classList.add('active');

    // Cargar historial
    const formData = new FormData();
    formData.append('action', 'get-chat-history');
    formData.append('target_user_id', friendId);
    
    try {
        const result = await callChatApi(formData);
        if (result.success) {
            renderChatHistory(result.messages); // Función actualizada
            document.getElementById('chat-message-input').disabled = false;
        } else {
            document.getElementById('chat-message-list').innerHTML = '<div class="chat-list-placeholder">Error al cargar mensajes.</div>';
        }
    } catch (e) {
        document.getElementById('chat-message-list').innerHTML = '<div class="chat-list-placeholder">Error de conexión.</div>';
    }
}


// --- ▼▼▼ INICIO DE FUNCIONES MODIFICADAS (Manejo de adjuntos) ▼▼▼ ---

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
        
        // Añadir listener al botón de eliminar
        previewDiv.querySelector('.chat-preview-remove-btn').addEventListener('click', () => {
            // Eliminar este archivo del array
            selectedAttachments = selectedAttachments.filter(f => f !== file);
            // Eliminar este elemento del DOM
            previewDiv.remove();
            // Resetear el input de archivo (para permitir volver a seleccionarlo si se elimina)
            document.getElementById('chat-attachment-input').value = '';
            // Re-validar el botón de envío
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

    // Tomar solo los archivos permitidos
    const filesToProcess = Array.from(files).slice(0, allowedNewCount);

    const MAX_SIZE_MB = 5; // Debería coincidir con el backend
    const MAX_SIZE_BYTES = MAX_SIZE_MB * 1024 * 1024;
    const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    for (const file of filesToProcess) {
        // Validar tipo
        if (!ALLOWED_TYPES.includes(file.type)) {
            showAlert(getTranslation('js.publication.errorFileType'), 'error');
            continue; // Saltar este archivo
        }
        
        // Validar tamaño
        if (file.size > MAX_SIZE_BYTES) {
            showAlert(getTranslation('js.publication.errorFileSize').replace('%size%', MAX_SIZE_MB), 'error');
            continue; // Saltar este archivo
        }

        // Si es válido, añadir al array y crear previsualización
        selectedAttachments.push(file);
        createAttachmentPreview(file);
    }
    
    // Resetear el input para permitir volver a seleccionar
    e.target.value = '';
    
    validateSendButton();
}
// --- ▲▲▲ FIN DE FUNCIONES MODIFICADAS ---


/**
 * Envía un mensaje de chat (texto y/o archivos).
 */
async function sendMessage() {
    const input = document.getElementById('chat-message-input');
    const sendBtn = document.getElementById('chat-send-button');
    const receiverId = document.getElementById('chat-receiver-id').value;
    const messageText = input.value.trim();

    // Validación movida a validateSendButton(), pero doble check aquí
    if (!messageText && selectedAttachments.length === 0) return;
    if (!receiverId || sendBtn.disabled) return;
    
    sendBtn.disabled = true;
    input.disabled = true;
    document.getElementById('chat-attach-button').disabled = true;

    const formData = new FormData();
    formData.append('action', 'send-message');
    formData.append('receiver_id', receiverId);
    formData.append('message_text', messageText);
    
    // Adjuntar todos los archivos
    for (const file of selectedAttachments) {
        formData.append('attachments[]', file, file.name);
    }

    try {
        const result = await callChatApi(formData);
        if (result.success && result.message_sent) {
            // Optimistic UI: renderizar el mensaje enviado
            renderMessageBubble(result.message_sent, true); // Usar la nueva función
            scrollToBottom();
            
            // Limpiar todo
            input.value = '';
            selectedAttachments = [];
            document.getElementById('chat-attachment-preview-container').innerHTML = '';
            document.getElementById('chat-attachment-input').value = '';
            input.focus();
            
        } else {
            showAlert(getTranslation(result.message || 'js.api.errorServer'), 'error');
        }
    } catch (e) {
        showAlert(getTranslation('js.api.errorConnection'), 'error');
    } finally {
        sendBtn.disabled = true; // Se mantiene deshabilitado hasta que se escriba texto
        input.disabled = false;
        document.getElementById('chat-attach-button').disabled = false;
        validateSendButton(); // Re-validar (debería deshabilitarlo)
    }
}

/**
 * Maneja un mensaje de chat entrante desde el WebSocket.
 */
export function handleChatMessageReceived(message) {
    if (!message || !message.sender_id) return;
    
    const senderId = parseInt(message.sender_id, 10);
    
    // Si el chat de esta persona está abierto, renderiza el mensaje
    if (senderId === currentChatUserId) {
        renderMessageBubble(message, false); // Usar la nueva función
        scrollToBottom();
        
        // TODO: Enviar un 'ack' al servidor para marcar como leído
        
    } else {
        // Si el chat no está abierto, actualizar la lista de conversaciones
        const friendItem = document.querySelector(`.chat-conversation-item[data-user-id="${senderId}"]`);
        if (friendItem) {
            
            // --- ▼▼▼ INICIO DE LÓGICA DE SNIPPET MODIFICADA ▼▼▼ ---
            let snippet = '...';
            if (message.attachment_urls && !message.message_text) {
                snippet = '[Imagen]';
            } else {
                snippet = escapeHTML(message.message_text);
            }
            friendItem.querySelector('.chat-item-snippet').textContent = snippet;
            // --- ▲▲▲ FIN DE LÓGICA DE SNIPPET MODIFICADA ---
            
            friendItem.querySelector('.chat-item-timestamp').textContent = formatTime(message.created_at);
            
            let badge = friendItem.querySelector('.chat-item-unread-badge');
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'chat-item-unread-badge';
                friendItem.querySelector('.chat-item-snippet-wrapper').appendChild(badge);
            }
            const newCount = (parseInt(badge.textContent) || 0) + 1;
            badge.textContent = newCount;
            
            friendItem.parentElement.prepend(friendItem);
        }
    }
}

/**
 * Muestra u oculta el indicador "escribiendo..."
 */
export function handleTypingEvent(senderId, isTyping) {
    // ... (Esta función no necesita cambios) ...
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
    
    // Observer para cargar la lista al entrar a la sección
    const sectionsContainer = document.querySelector('.main-sections');
    if (sectionsContainer) {
        const observer = new MutationObserver((mutations) => {
            for (let mutation of mutations) {
                if (mutation.type === 'childList') {
                    const messagesSection = document.querySelector('[data-section="messages"]');
                    if (messagesSection) {
                        loadConversations();
                        document.dispatchEvent(new CustomEvent('request-friend-list-presence-update'));
                    } else {
                        currentChatUserId = null; 
                    }
                }
            }
        });
        observer.observe(sectionsContainer, { childList: true });
    }

    // Listeners de clics
    document.body.addEventListener('click', (e) => {
        const chatSection = e.target.closest('[data-section="messages"]');
        if (!chatSection) return;
        
        // Clic en un amigo de la lista
        const friendItem = e.target.closest('.chat-conversation-item');
        if (friendItem) {
            e.preventDefault();
            const friendId = friendItem.dataset.userId;
            const username = friendItem.dataset.username;
            const avatar = friendItem.dataset.avatar;
            const role = friendItem.dataset.role;
            const isOnline = friendItem.querySelector('.chat-item-status')?.classList.contains('online');
            
            openChat(friendId, username, avatar, role, isOnline);
            document.getElementById('chat-layout-container')?.classList.add('show-chat');
            friendItem.querySelector('.chat-item-unread-badge')?.remove();
            return;
        }
        
        // Clic en el botón "Atrás" (móvil)
        const backBtn = e.target.closest('#chat-back-button');
        if (backBtn) {
            e.preventDefault();
            document.getElementById('chat-layout-container')?.classList.remove('show-chat');
            currentChatUserId = null;
            loadConversations(); // Recargar lista
            return;
        }

        // --- ▼▼▼ INICIO DE LISTENER MODIFICADO (Botón de adjuntar) ▼▼▼ ---
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
        // --- ▲▲▲ FIN DE LISTENER MODIFICADO ---
    });
    
    // Listener para el formulario de envío
    document.body.addEventListener('submit', (e) => {
        const chatForm = e.target.closest('#chat-message-input-form');
        if (chatForm) {
            e.preventDefault();
            sendMessage();
            return;
        }
    });

    let typingTimer;
    let isTyping = false;

    // Listener para input (texto y filtro)
    document.body.addEventListener('input', (e) => {
        const chatInput = e.target.closest('#chat-message-input');
        if (chatInput) {
            validateSendButton(); // Validar botón de envío
            
            // Lógica de "Escribiendo..."
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
    
    // --- ▼▼▼ INICIO DE LISTENER NUEVO (Detección de archivos) ▼▼▼ ---
    document.body.addEventListener('change', (e) => {
        const fileInput = e.target.closest('#chat-attachment-input');
        if (fileInput) {
            handleAttachmentChange(e);
        }
    });
    // --- ▲▲▲ FIN DE LISTENER NUEVO ---

    // Listener para el evento de presencia
    document.addEventListener('user-presence-changed', (e) => {
        // ... (Esta función no necesita cambios) ...
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
            if (statusEl && statusEl.classList.contains('active')) { // Solo actualizar si 'escribiendo' no está activo
                statusEl.textContent = status === 'online' ? getTranslation('chat.online', 'Online') : getTranslation('chat.offline', 'Offline');
                statusEl.className = status === 'online' ? 'chat-header-status online active' : 'chat-header-status active';
            }
        }
    });
}
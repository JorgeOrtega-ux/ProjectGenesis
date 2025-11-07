// ARCHIVO: assets/js/modules/chat-manager.js
// (Versión corregida con LAZY LOAD funcionando)

import { callChatApi } from '../services/api-service.js';
import { showAlert } from '../services/alert-manager.js';
import { getTranslation } from '../services/i18n-manager.js';
import { hideTooltip } from '../services/tooltip-manager.js';

const attachedFiles = new Map();
let isSending = false;

// --- Variables de estado para Lazy Load ---
let isLoadingHistory = false;
let hasMoreHistory = true;
let oldestMessageId = 0;


/**
 * Limpia el input de chat, borra los archivos adjuntos y elimina las vistas previas.
 */
function clearChatInput() {
    const textInput = document.getElementById('chat-input-text-area');
    if (textInput) {
        textInput.innerHTML = '';
    }

    const previewContainer = document.getElementById('chat-preview-container');
    if (previewContainer) {
        previewContainer.innerHTML = ''; // Borra todos los items
        removePreviewContainer(previewContainer); // Elimina el contenedor
    }
    
    attachedFiles.clear();
}

/**
 * Elimina el contenedor de preview y quita la clase del wrapper.
 */
function removePreviewContainer(previewContainer) {
    const inputWrapper = document.getElementById('chat-input-wrapper');
    if (inputWrapper) {
        inputWrapper.classList.remove('has-previews');
    }
    if (previewContainer && previewContainer.parentNode) {
        previewContainer.parentNode.removeChild(previewContainer);
    }
}

/**
 * Elimina una vista previa específica y su archivo.
 */
function removePreview(fileId) {
    attachedFiles.delete(fileId);

    const previewContainer = document.getElementById('chat-preview-container');
    if (!previewContainer) return;

    const previewItem = previewContainer.querySelector(`.chat-preview-item[data-file-id="${fileId}"]`);
    if (previewItem) {
        previewItem.remove();
    }

    if (previewContainer.children.length === 0) {
        removePreviewContainer(previewContainer);
    }
}

/**
 * Crea la vista previa de la imagen y la añade al DOM.
 */
function createPreview(file, fileId, previewContainer, inputWrapper) {
    const reader = new FileReader();
    
    reader.onload = (e) => {
        const dataUrl = e.target.result;

        const previewItem = document.createElement('div');
        previewItem.className = 'chat-preview-item';
        previewItem.dataset.fileId = fileId;

        previewItem.innerHTML = `
            <img src="${dataUrl}" alt="${file.name}" class="chat-preview-image">
            <button type"button" class="chat-preview-remove">
                <span class="material-symbols-rounded">close</span>
            </button>
        `;

        previewItem.querySelector('.chat-preview-remove').addEventListener('click', () => {
            removePreview(fileId);
        });

        previewContainer.appendChild(previewItem);
        inputWrapper.classList.add('has-previews');
    };

    reader.readAsDataURL(file);
}

/**
 * Formatea un timestamp (ej. "2025-11-06 20:43:00") a "HH:MM".
 */
function formatMessageTime(timestamp) {
    try {
        const date = new Date(timestamp.replace(' ', 'T') + 'Z');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        return `${hours}:${minutes}`;
    } catch (e) {
        console.error("Error formateando fecha de chat:", e);
        return "--:--";
    }
}


/**
 * Crea el HTML de una burbuja de chat (sin añadirla al DOM).
 * @param {object} msgData El objeto del mensaje.
 * @returns {HTMLElement} El elemento de la burbuja.
 */
function createBubbleElement(msgData) {
    const isOwnMessage = msgData.user_id === window.userId;
    const avatarUrl = msgData.profile_image_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(msgData.username)}&size=100&background=e0e0e0&color=ffffff`;
    const time = formatMessageTime(msgData.created_at);
    const msgTimestamp = new Date(msgData.created_at.replace(' ', 'T') + 'Z').getTime();

    const bubble = document.createElement('div');
    bubble.className = 'chat-bubble';
    if (isOwnMessage) {
        bubble.classList.add('is-own');
    }
    bubble.dataset.userId = msgData.user_id;
    bubble.dataset.timestamp = msgTimestamp;
    bubble.dataset.messageId = msgData.id; // <-- ¡Importante!

    let bodyContent = '';
    if (msgData.text_content) {
        bodyContent += `<div class="chat-bubble-text">${msgData.text_content}</div>`;
    }
    if (msgData.attachments && msgData.attachments.length > 0) {
        const attachments = msgData.attachments;
        const attachment_count = attachments.length;
        let attachmentsHtml = `<div class="chat-bubble-attachments" data-count="${attachment_count}">`;
        for (let i = 0; i < Math.min(attachment_count, 4); i++) {
            const attachment = attachments[i];
            attachmentsHtml += `<div class="chat-bubble-image"><img src="${attachment.public_url}" alt="Imagen adjunta" loading="lazy">`;
            if (i === 3 && attachment_count > 4) {
                const remaining = attachment_count - 4;
                attachmentsHtml += `<div class="chat-image-overlay">+${remaining}</div>`;
            }
            attachmentsHtml += `</div>`;
        }
        attachmentsHtml += `</div>`;
        bodyContent += attachmentsHtml;
    }

    bubble.innerHTML = `
        <div class="chat-bubble-avatar" data-role="${msgData.user_role || 'user'}">
            <img src="${avatarUrl}" alt="${msgData.username}">
        </div>
        <div class="chat-bubble-content">
            <div class="chat-bubble-header">
                <span class="chat-bubble-username">${msgData.username}</span>
            </div>
            <div class="chat-bubble-body">
                ${bodyContent}
            </div>
            <div class="chat-bubble-footer">
                <span class="chat-bubble-time">${time}</span>
            </div>
        </div>
    `;
    return bubble;
}


/**
 * Renderiza un nuevo mensaje en la UI del chat (mensajes en vivo).
 * @param {object} msgData El objeto del mensaje (de la API/WS).
 */
export function renderIncomingMessage(msgData) {
    const chatHistory = document.getElementById('chat-history-container');
    if (!chatHistory) return;

    // 1. Reutilizar la lógica de creación de burbujas
    const bubble = createBubbleElement(msgData);

    // 2. Comprobar si el usuario está cerca del fondo (visual)
    // En column-reverse, el fondo visual es scrollTop = 0.
    const isScrolledToBottom = chatHistory.scrollTop < 100;

    // 3. Añadir al DOM
    // `prepend` lo añade al inicio del HTML, que es el fondo visual.
    chatHistory.prepend(bubble);
    
    // 4. Actualizar el ID más antiguo si es el primer mensaje
    if (oldestMessageId === 0) {
        oldestMessageId = msgData.id;
        hasMoreHistory = true; // Acaba de empezar el chat
        chatHistory.dataset.oldestMessageId = oldestMessageId;
        chatHistory.dataset.hasMoreHistory = "true";
    }

    // 5. Auto-scroll
    if (isScrolledToBottom) {
        chatHistory.scrollTop = 0; // Scroll al fondo visual
    }
}


/**
 * Maneja el envío del formulario de chat.
 */
async function handleSendMessage() {
    if (isSending) return;

    const textInput = document.getElementById('chat-input-text-area');
    const sendButton = document.getElementById('chat-send-button');
    const currentGroupUuid = document.body.dataset.currentGroupUuid || '';
    const csrfToken = window.csrfToken || '';

    if (!currentGroupUuid) {
        showAlert(getTranslation('home.chat.error.noGroup'), 'error');
        return;
    }

    const messageText = textInput ? textInput.innerText.trim() : '';
    const hasFiles = attachedFiles.size > 0;

    if (!messageText && !hasFiles) {
        return; // No enviar nada si está vacío
    }

    isSending = true;
    if(sendButton) sendButton.disabled = true;
    hideTooltip();

    // 1. Preparar FormData
    const formData = new FormData();
    formData.append('action', 'send-message');
    formData.append('csrf_token', csrfToken);
    formData.append('group_uuid', currentGroupUuid);
    formData.append('message_text', messageText);

    // 2. Adjuntar archivos
    attachedFiles.forEach((file) => {
        formData.append('images[]', file, file.name);
    });

    try {
        // 3. Llamar a la API de Chat
        const result = await callChatApi(formData);

        if (result.success) {
            // 4. Éxito: Limpiar el input.
            // El mensaje se renderizará cuando vuelva por el WebSocket.
            clearChatInput();
        } else {
            // 5. Error: Mostrar alerta
            showAlert(getTranslation(result.message || 'js.api.errorServer'), 'error');
        }

    } catch (error) {
        console.error("Error al enviar mensaje:", error);
        showAlert(getTranslation('js.api.errorConnection'), 'error');
    } finally {
        isSending = false;
        if(sendButton) sendButton.disabled = false;
    }
}


// --- ▼▼▼ INICIO DE FUNCIÓN CORREGIDA (loadMoreHistory) ▼▼▼ ---
/**
 * Carga mensajes más antiguos cuando el usuario llega al tope del scroll.
 */
async function loadMoreHistory() {
    const chatHistory = document.getElementById('chat-history-container');
    if (!chatHistory || isLoadingHistory || !hasMoreHistory || oldestMessageId === 0) {
        return;
    }

    isLoadingHistory = true;
    console.log("Cargando historial anterior a:", oldestMessageId);

    // --- ¡CORRECCIÓN! ---
    // 1. Guardar la altura y posición de scroll *antes* de añadir/quitar nada.
    const oldScrollHeight = chatHistory.scrollHeight;
    const oldScrollTop = chatHistory.scrollTop;
    
    // (Spinner eliminado para simplificar el cálculo de scroll)

    try {
        const formData = new FormData();
        formData.append('action', 'load-history');
        formData.append('csrf_token', window.csrfToken || '');
        formData.append('group_uuid', document.body.dataset.currentGroupUuid || '');
        formData.append('before_id', oldestMessageId);

        const result = await callChatApi(formData);

        if (result.success && result.messages) {
            if (result.messages.length > 0) {
                // Usamos un Fragment para añadir todo de una vez
                const fragment = document.createDocumentFragment();
                
                result.messages.forEach(msgData => {
                    const bubble = createBubbleElement(msgData);
                    fragment.appendChild(bubble); // Añadir al fragment
                });
                
                // Añadir todos los mensajes antiguos al final del HTML (tope visual)
                chatHistory.appendChild(fragment);

                // Actualizar el ID más antiguo
                oldestMessageId = result.messages[result.messages.length - 1].id;
                chatHistory.dataset.oldestMessageId = oldestMessageId;
                
                hasMoreHistory = result.has_more;
                chatHistory.dataset.hasMoreHistory = hasMoreHistory;

                // --- ¡CORRECCIÓN DE SCROLL! ---
                // 2. Calcular la nueva altura
                const newScrollHeight = chatHistory.scrollHeight;
                // 3. Calcular la altura que añadimos
                const heightAdded = newScrollHeight - oldScrollHeight;
                // 4. Restaurar la posición de scroll anterior + la altura añadida
                chatHistory.scrollTop = oldScrollTop + heightAdded;
                
            } else {
                hasMoreHistory = false; // No hay más mensajes
                chatHistory.dataset.hasMoreHistory = "false";
            }
        } else {
            showAlert(getTranslation(result.message || 'js.api.errorServer'), 'error');
        }

    } catch (error) {
        console.error("Error cargando historial:", error);
        showAlert(getTranslation('js.api.errorConnection'), 'error');
    } finally {
        isLoadingHistory = false;
    }
}
// --- ▲▲▲ FIN DE FUNCIÓN CORREGIDA (loadMoreHistory) ▲▲▲ ---


/**
 * Inicializa los listeners para el input de chat.
 */
export function initChatManager() {
    
    const chatHistory = document.getElementById('chat-history-container');
    if (chatHistory) {
        // Inicializar estado
        oldestMessageId = parseInt(chatHistory.dataset.oldestMessageId || '0', 10);
        hasMoreHistory = (chatHistory.dataset.hasMoreHistory === 'true');
        isLoadingHistory = false;
        
        // Listener de Scroll para lazy loading
        chatHistory.addEventListener('scroll', () => {
            if (isLoadingHistory || !hasMoreHistory) return;

            // --- ¡CORRECCIÓN DEL DETECTOR DE SCROLL! ---
            // "Llegar al tope" en column-reverse significa que
            // scrollTop está cerca de su valor MÁXIMO (scrollHeight - clientHeight).
            const scrollBuffer = 200; // 200px antes de llegar
            const maxScrollTop = chatHistory.scrollHeight - chatHistory.clientHeight;
            const isAtTop = chatHistory.scrollTop >= (maxScrollTop - scrollBuffer);

            if (isAtTop) {
                loadMoreHistory();
            }
        });
    }

    document.body.addEventListener('click', (event) => {
        // ... (resto del listener sin cambios)
        const target = event.target;
        
        const attachButton = target.closest('#chat-attach-button');
        if (attachButton) {
            const fileInput = document.getElementById('chat-file-input');
            if (fileInput) {
                fileInput.click(); 
            }
            return;
        }
        
        const sendButton = target.closest('#chat-send-button');
        if (sendButton) {
            handleSendMessage();
            return;
        }
    });

    document.body.addEventListener('change', (event) => {
        // ... (resto del listener sin cambios)
        const fileInput = event.target.closest('#chat-file-input');
        
        if (fileInput) {
            const files = fileInput.files;
            if (!files) return;

            if (attachedFiles.size + files.length > 9) {
                showAlert("No puedes adjuntar más de 9 imágenes.", 'error'); 
                fileInput.value = ''; // Limpiar
                return;
            }

            const inputWrapper = document.getElementById('chat-input-wrapper');
            if (!inputWrapper) {
                console.error("No se encontró #chat-input-wrapper.");
                return;
            }

            let previewContainer = document.getElementById('chat-preview-container');
            if (!previewContainer) {
                previewContainer = document.createElement('div');
                previewContainer.className = 'chat-input__previews';
                previewContainer.id = 'chat-preview-container';
                
                const textArea = inputWrapper.querySelector('.chat-input__text-area');
                if (textArea) {
                    inputWrapper.insertBefore(previewContainer, textArea);
                } else {
                    inputWrapper.prepend(previewContainer); 
                }
            }

            for (const file of files) {
                if (file.type.startsWith('image/')) {
                    if (attachedFiles.size >= 9) {
                        showAlert("No puedes adjuntar más de 9 imágenes.", 'error');
                        break; // Salir del bucle
                    }
                    const fileId = `file-${Date.now()}-${Math.random()}`;
                    attachedFiles.set(fileId, file);
                    createPreview(file, fileId, previewContainer, inputWrapper);
                } else {
                    showAlert(getTranslation('home.chat.error.onlyImages'), 'error');
                }
            }

            fileInput.value = '';
        }
    });

    document.body.addEventListener('keydown', (event) => {
        // ... (resto del listener sin cambios)
        const textInput = event.target.closest('#chat-input-text-area');
        if (textInput && event.key === 'Enter') {
            if (!event.shiftKey) {
                event.preventDefault(); 
                handleSendMessage();
            }
        }
    });
}
// ARCHIVO: assets/js/modules/chat-manager.js
// (Versión modificada para NUEVO PAYLOAD y SIN AGRUPACIÓN DE 60s)

import { callChatApi } from '../services/api-service.js';
import { showAlert } from '../services/alert-manager.js';
import { getTranslation } from '../services/i18n-manager.js';
import { hideTooltip } from '../services/tooltip-manager.js';

const attachedFiles = new Map();
let isSending = false;
// const MAX_GROUPING_TIME_MS = 60 * 1000; // <-- ELIMINADO

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


// --- ▼▼▼ ¡FUNCIÓN RENDERINCOMINGMESSAGE MODIFICADA! ▼▼▼ ---
/**
 * Renderiza un nuevo mensaje en la UI del chat.
 * ¡YA NO AGRUPA MENSAJES! CADA MENSAJE ES UNA NUEVA BURBUJA.
 * @param {object} msgData El objeto del mensaje (de la API/WS).
 */
export function renderIncomingMessage(msgData) {
    const chatHistory = document.getElementById('chat-history-container');
    if (!chatHistory) return;

    const isOwnMessage = msgData.user_id === window.userId;
    const avatarUrl = msgData.profile_image_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(msgData.username)}&size=100&background=e0e0e0&color=ffffff`;
    const time = formatMessageTime(msgData.created_at);
    const msgTimestamp = new Date(msgData.created_at.replace(' ', 'T') + 'Z').getTime();

    // --- LÓGICA DE AGRUPACIÓN (isContinuation) ELIMINADA ---
    // ya no se comprueba el lastBubble

    // --- 3. CREAR NUEVA BURBUJA (Ahora se ejecuta siempre) ---
    const bubble = document.createElement('div');
    bubble.className = 'chat-bubble';
    if (isOwnMessage) {
        bubble.classList.add('is-own');
    }
    bubble.dataset.userId = msgData.user_id;
    bubble.dataset.timestamp = msgTimestamp;

    // --- LÓGICA DE RENDERIZADO DE CUERPO (NUEVA) ---
    let bodyContent = '';

    // 1. Renderizar texto (si existe)
    if (msgData.text_content) {
        bodyContent += `<div class="chat-bubble-text">${msgData.text_content}</div>`;
    }

    // 2. Renderizar adjuntos (si existen)
    if (msgData.attachments && msgData.attachments.length > 0) {
        const attachments = msgData.attachments;
        const attachment_count = attachments.length;
        
        let attachmentsHtml = `<div class="chat-bubble-attachments" data-count="${attachment_count}">`;

        // Iterar solo hasta 4
        for (let i = 0; i < Math.min(attachment_count, 4); i++) {
            const attachment = attachments[i];
            attachmentsHtml += `
                <div class="chat-bubble-image">
                    <img src="${attachment.public_url}" alt="Imagen adjunta" loading="lazy">
            `;

            // Si es el 4to item Y hay más de 4...
            if (i === 3 && attachment_count > 4) {
                const remaining = attachment_count - 4;
                attachmentsHtml += `<div class="chat-image-overlay">+${remaining}</div>`;
            }

            attachmentsHtml += `</div>`; // Cierra chat-bubble-image
        }

        attachmentsHtml += `</div>`; // Cierra chat-bubble-attachments
        bodyContent += attachmentsHtml;
    }
    // --- FIN LÓGICA DE RENDERIZADO DE CUERPO ---

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
    
    
    // --- ▼▼▼ INICIO DE LA CORRECCIÓN DE SCROLL (appendChild -> prepend) ▼▼▼ ---

    // 4. Hacer scroll al final (solo si el usuario ya está al final)
    // En modo column-reverse, "estar al final" significa scrollTop es cercano a 0.
    const isScrolledToBottom = chatHistory.scrollTop < 100; // 100px de margen
    
    // ¡LA CORRECCIÓN ESTÁ AQUÍ! Usamos prepend()
    chatHistory.prepend(bubble);
    
    if (isScrolledToBottom) {
        chatHistory.scrollTop = 0; // Scroll al fondo visual
    }
    
    // --- ▲▲▲ FIN DE LA CORRECCIÓN DE SCROLL (appendChild -> prepend) ▲▲▲ ---
}
// --- ▲▲▲ FIN DE FUNCIÓN MODIFICADA ▲▲▲ ---


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

/**
 * Inicializa los listeners para el input de chat.
 */
export function initChatManager() {
    
    document.body.addEventListener('click', (event) => {
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
        const fileInput = event.target.closest('#chat-file-input');
        
        if (fileInput) {
            const files = fileInput.files;
            if (!files) return;

            // --- ▼▼▼ INICIO DE MODIFICACIÓN (Límite de 9 archivos) ▼▼▼ ---
            if (attachedFiles.size + files.length > 9) {
                // (Deberías crear una clave i18n nueva para esto)
                showAlert("No puedes adjuntar más de 9 imágenes.", 'error'); 
                fileInput.value = ''; // Limpiar
                return;
            }
            // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---


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
                    // Limitar a 9 archivos en total
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
        const textInput = event.target.closest('#chat-input-text-area');
        if (textInput && event.key === 'Enter') {
            if (!event.shiftKey) {
                event.preventDefault(); 
                handleSendMessage();
            }
        }
    });
}
// ARCHIVO: assets/js/modules/chat-manager.js
// (Versión corregida usando Event Delegation)

// Almacenará los archivos seleccionados.
const attachedFiles = new Map();

/**
 * Inicializa los listeners para el input de chat usando delegación de eventos.
 */
export function initChatManager() {
    
    // 1. Listener para el clic en el botón de adjuntar (+)
    // Escucha en 'document.body' en lugar de en el botón directamente.
    document.body.addEventListener('click', (event) => {
        const attachButton = event.target.closest('#chat-attach-button');
        
        // Si el elemento clickeado (o uno de sus padres) es el botón:
        if (attachButton) {
            const fileInput = document.getElementById('chat-file-input');
            if (fileInput) {
                fileInput.click(); // Dispara el input de archivo oculto
            }
        }
    });

    // 2. Listener para cuando se seleccionan archivos
    // También delegado al 'document.body'.
    document.body.addEventListener('change', (event) => {
        const fileInput = event.target.closest('#chat-file-input');
        
        // Si el evento 'change' vino de nuestro input de archivo:
        if (fileInput) {
            const files = fileInput.files;
            if (!files) return;

            const previewContainer = document.getElementById('chat-preview-container');
            const inputWrapper = document.getElementById('chat-input-wrapper');

            if (!previewContainer || !inputWrapper) {
                console.error("No se encontraron los elementos del preview del chat.");
                return;
            }

            for (const file of files) {
                if (file.type.startsWith('image/')) {
                    const fileId = `file-${Date.now()}-${Math.random()}`;
                    attachedFiles.set(fileId, file);
                    createPreview(file, fileId, previewContainer, inputWrapper);
                }
            }

            // Limpiar el input para permitir seleccionar el mismo archivo de nuevo
            fileInput.value = '';
        }
    });
}

/**
 * Crea la vista previa de la imagen y la añade al DOM.
 * (Esta función no necesita cambios)
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
            <button class="chat-preview-remove">
                <span class="material-symbols-rounded">close</span>
            </button>
        `;

        previewItem.querySelector('.chat-preview-remove').addEventListener('click', () => {
            removePreview(fileId, previewContainer, inputWrapper);
        });

        previewContainer.appendChild(previewItem);
        inputWrapper.classList.add('has-previews');
    };

    reader.readAsDataURL(file);
}

/**
 * Elimina una vista previa del DOM y el archivo del Map.
 * (Esta función no necesita cambios)
 */
function removePreview(fileId, previewContainer, inputWrapper) {
    attachedFiles.delete(fileId);

    const previewItem = previewContainer.querySelector(`.chat-preview-item[data-file-id="${fileId}"]`);
    if (previewItem) {
        previewItem.remove();
    }

    if (previewContainer.children.length === 0) {
        inputWrapper.classList.remove('has-previews');
    }
}
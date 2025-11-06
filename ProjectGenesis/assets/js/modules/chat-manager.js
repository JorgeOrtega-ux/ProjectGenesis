// ARCHIVO: assets/js/modules/chat-manager.js
// (Versión corregida usando Event Delegation y creación/eliminación dinámica)

// Almacenará los archivos seleccionados.
const attachedFiles = new Map();

/**
 * Inicializa los listeners para el input de chat usando delegación de eventos.
 */
export function initChatManager() {
    
    // 1. Listener para el clic en el botón de adjuntar (+)
    document.body.addEventListener('click', (event) => {
        const attachButton = event.target.closest('#chat-attach-button');
        
        if (attachButton) {
            const fileInput = document.getElementById('chat-file-input');
            if (fileInput) {
                fileInput.click(); 
            }
        }
    });

    // 2. Listener para cuando se seleccionan archivos
    document.body.addEventListener('change', (event) => {
        const fileInput = event.target.closest('#chat-file-input');
        
        if (fileInput) {
            const files = fileInput.files;
            if (!files) return;

            // --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
            
            // 1. Encontrar el 'wrapper' principal
            const inputWrapper = document.getElementById('chat-input-wrapper');
            if (!inputWrapper) {
                console.error("No se encontró #chat-input-wrapper.");
                return;
            }

            // 2. Buscar si el contenedor de preview YA existe
            let previewContainer = document.getElementById('chat-preview-container');

            // 3. Si NO existe, crearlo
            if (!previewContainer) {
                previewContainer = document.createElement('div');
                previewContainer.className = 'chat-input__previews';
                previewContainer.id = 'chat-preview-container';
                
                // 4. Insertarlo en el lugar correcto (antes del área de texto)
                const textArea = inputWrapper.querySelector('.chat-input__text-area');
                if (textArea) {
                    inputWrapper.insertBefore(previewContainer, textArea);
                } else {
                    // Fallback por si el text-area no está
                    inputWrapper.prepend(previewContainer); 
                }
            }
            
            // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---


            for (const file of files) {
                if (file.type.startsWith('image/')) {
                    const fileId = `file-${Date.now()}-${Math.random()}`;
                    attachedFiles.set(fileId, file);
                    
                    // 5. Pasamos el contenedor (existente o recién creado) a la función
                    createPreview(file, fileId, previewContainer, inputWrapper);
                }
            }

            // Limpiar el input
            fileInput.value = '';
        }
    });
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
            <button class="chat-preview-remove">
                <span class="material-symbols-rounded">close</span>
            </button>
        `;

        // Importante: El listener se añade aquí, capturando 'previewContainer'
        // y 'inputWrapper' del scope de esta función.
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
 * Si el contenedor queda vacío, lo elimina.
 */
function removePreview(fileId, previewContainer, inputWrapper) {
    attachedFiles.delete(fileId);

    const previewItem = previewContainer.querySelector(`.chat-preview-item[data-file-id="${fileId}"]`);
    if (previewItem) {
        previewItem.remove();
    }

    // --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
    
    // 1. Comprobar si el contenedor quedó vacío
    if (previewContainer.children.length === 0) {
        
        // 2. Quitar la clase del wrapper principal
        inputWrapper.classList.remove('has-previews');
        
        // 3. Eliminar el contenedor de preview del DOM
        if (previewContainer.parentNode) {
            previewContainer.parentNode.removeChild(previewContainer);
        }
    }
    // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
}
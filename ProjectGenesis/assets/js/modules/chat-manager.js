// ARCHIVO: assets/js/modules/chat-manager.js
// (Debes CREAR este archivo en esta ruta)

// Almacenará los archivos seleccionados.
// Usamos un Map para poder eliminar archivos fácilmente por su ID.
const attachedFiles = new Map();

/**
 * Inicializa los listeners para el input de chat.
 */
export function initChatManager() {
    const attachButton = document.getElementById('chat-attach-button');
    const fileInput = document.getElementById('chat-file-input');
    const previewContainer = document.getElementById('chat-preview-container');
    const inputWrapper = document.getElementById('chat-input-wrapper');

    if (!attachButton || !fileInput || !previewContainer || !inputWrapper) {
        // Si no estamos en la página 'home' (o hay un error), no hace nada.
        return;
    }

    // 1. Al hacer clic en el botón (+), disparar el input de archivo
    attachButton.addEventListener('click', () => {
        fileInput.click();
    });

    // 2. Cuando se seleccionan archivos en el input
    fileInput.addEventListener('change', (event) => {
        const files = event.target.files;
        if (!files) return;

        for (const file of files) {
            if (file.type.startsWith('image/')) {
                // Generar un ID único para este archivo
                const fileId = `file-${Date.now()}-${Math.random()}`;
                
                // Guardar el archivo real en el Map
                attachedFiles.set(fileId, file);
                
                // Crear la vista previa visual
                createPreview(file, fileId, previewContainer, inputWrapper);
            }
        }

        // Limpiar el input para permitir seleccionar el mismo archivo de nuevo
        fileInput.value = '';
    });
}

/**
 * Crea la vista previa de la imagen y la añade al DOM.
 */
function createPreview(file, fileId, previewContainer, inputWrapper) {
    const reader = new FileReader();
    
    reader.onload = (e) => {
        const dataUrl = e.target.result;

        // Crear el elemento de la vista previa
        const previewItem = document.createElement('div');
        previewItem.className = 'chat-preview-item';
        previewItem.dataset.fileId = fileId; // Vincular al ID del archivo

        previewItem.innerHTML = `
            <img src="${dataUrl}" alt="${file.name}" class="chat-preview-image">
            <button class="chat-preview-remove">
                <span class="material-symbols-rounded">close</span>
            </button>
        `;

        // Añadir listener al botón de eliminar (X)
        previewItem.querySelector('.chat-preview-remove').addEventListener('click', () => {
            removePreview(fileId, previewContainer, inputWrapper);
        });

        previewContainer.appendChild(previewItem);
        
        // Añadir clase al contenedor principal para cambiar el border-radius
        inputWrapper.classList.add('has-previews');
    };

    reader.readAsDataURL(file);
}

/**
 * Elimina una vista previa del DOM y el archivo del Map.
 */
function removePreview(fileId, previewContainer, inputWrapper) {
    // Eliminar del Map
    attachedFiles.delete(fileId);

    // Eliminar del DOM
    const previewItem = previewContainer.querySelector(`.chat-preview-item[data-file-id="${fileId}"]`);
    if (previewItem) {
        previewItem.remove();
    }

    // Si el contenedor de vistas previas está vacío, quitar la clase
    if (previewContainer.children.length === 0) {
        inputWrapper.classList.remove('has-previews');
    }
}
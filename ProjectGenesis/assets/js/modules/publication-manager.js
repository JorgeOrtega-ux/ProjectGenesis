// FILE: assets/js/modules/publication-manager.js
// (VERSIÓN CORREGIDA - Valida el selector de comunidad)

import { callPublicationApi } from '../services/api-service.js';
import { getTranslation } from '../services/i18n-manager.js';
import { showAlert } from '../services/alert-manager.js';

const MAX_FILES = 4;
let selectedFiles = []; // Array para guardar los objetos File

/**
 * Muestra/Oculta un spinner en el botón de Publicar.
 */
function togglePublishSpinner(button, isLoading) {
    if (!button) return;
    button.disabled = isLoading;
    if (isLoading) {
        button.dataset.originalText = button.innerHTML;
        button.innerHTML = `<span class"logout-spinner" style="width: 20px; height: 20px; border-width: 2px; margin: 0 auto; border-top-color: #ffffff; border-left-color: #ffffff20; border-bottom-color: #ffffff20; border-right-color: #ffffff20;"></span>`;
    } else {
        if (button.dataset.originalText) {
            button.innerHTML = button.dataset.originalText;
        }
    }
}

/**
 * Valida el estado actual (texto O archivos) y habilita/deshabilita el botón de publicar.
 */
function validatePublicationState() {
    const textInput = document.getElementById('publication-text');
    const publishButton = document.getElementById('publish-post-btn');
    // --- ▼▼▼ LÍNEA AÑADIDA ▼▼▼ ---
    const communitySelect = document.getElementById('publication-community-select');
    // --- ▲▲▲ FIN LÍNEA AÑADIDA ▲▲▲ ---
    
    if (!textInput || !publishButton || !communitySelect) {
        // Si no existe el select (p.ej. porque no hay comunidades), el botón debe estar deshabilitado
        if(publishButton) publishButton.disabled = true;
        return;
    }

    const hasText = textInput.value.trim().length > 0;
    const hasFiles = selectedFiles.length > 0;
    // --- ▼▼▼ LÍNEA AÑADIDA ▼▼▼ ---
    const hasCommunity = communitySelect.value !== ''; // Comprueba que no sea la opción "Seleccione..."
    // --- ▲▲▲ FIN LÍNEA AÑADIDA ▲▲▲ ---
    
    // Habilita el botón si hay (texto O archivos) Y si hay una comunidad seleccionada
    // --- ▼▼▼ LÍNEA MODIFICADA ▼▼▼ ---
    publishButton.disabled = (!hasText && !hasFiles) || !hasCommunity;
    // --- ▲▲▲ FIN LÍNEA MODIFICADA ▲▲▲ ---
}

/**
 * Crea el elemento DOM para la vista previa de una imagen.
 * @param {File} file - El objeto archivo.
 * @param {string} src - La URL de datos (data URL) de la imagen.
 */
function createPreviewElement(file, src) {
    const container = document.getElementById('publication-preview-container');
    if (!container) return;

    const previewItem = document.createElement('div');
    previewItem.className = 'preview-item';

    const img = document.createElement('img');
    img.src = src;
    img.alt = file.name;
    previewItem.appendChild(img);

    const removeBtn = document.createElement('button');
    removeBtn.type = 'button';
    removeBtn.className = 'preview-remove-btn';
    removeBtn.innerHTML = '<span class="material-symbols-rounded">close</span>';
    
    // Añadir listener para eliminar este archivo
    removeBtn.addEventListener('click', () => {
        removeFilePreview(previewItem, file);
    });
    
    previewItem.appendChild(removeBtn);
    container.appendChild(previewItem);
}

/**
 * Elimina una imagen de la vista previa y del array de subida.
 * @param {HTMLElement} previewItem - El elemento div.preview-item a eliminar.
 * @param {File} file - El objeto archivo a eliminar.
 */
function removeFilePreview(previewItem, file) {
    // Eliminar del array
    selectedFiles = selectedFiles.filter(f => f !== file);
    
    // Eliminar del DOM
    previewItem.remove();
    
    // Actualizar el input (para permitir volver a seleccionar el mismo archivo)
    const fileInput = document.getElementById('publication-file-input');
    if (fileInput) fileInput.value = ''; // Resetea el input
    
    // Re-validar el botón de publicar
    validatePublicationState();
}

/**
 * Maneja la selección de archivos del input.
 * @param {Event} event - El evento 'change' del input.
 */
function handleFileSelection(event) {
    const files = event.target.files;
    const previewContainer = document.getElementById('publication-preview-container');
    if (!files || !previewContainer) return;

    const MAX_SIZE_MB = window.avatarMaxSizeMB || 2;
    const MAX_SIZE_BYTES = MAX_SIZE_MB * 1024 * 1024;

    if (selectedFiles.length + files.length > MAX_FILES) {
        showAlert(getTranslation('js.publication.errorFileCount'), 'error');
        return;
    }

    for (const file of files) {
        // Validar tipo (ya lo hace el input, pero doble chequeo)
        if (!['image/jpeg', 'image/png', 'image/gif', 'image/webp'].includes(file.type)) {
            showAlert(getTranslation('js.publication.errorFileType'), 'error');
            continue;
        }

        // Validar tamaño
        if (file.size > MAX_SIZE_BYTES) {
            showAlert(getTranslation('js.publication.errorFileSize').replace('%size%', MAX_SIZE_MB), 'error');
            continue;
        }

        // Añadir al array
        selectedFiles.push(file);

        // Generar vista previa
        const reader = new FileReader();
        reader.onload = (e) => {
            createPreviewElement(file, e.target.result);
        };
        reader.readAsDataURL(file);
    }
    
    // Re-validar el botón de publicar
    validatePublicationState();
}


/**
 * Manejador para el envío de la publicación (con archivos).
 */
async function handlePublishSubmit() {
    const publishButton = document.getElementById('publish-post-btn');
    const textInput = document.getElementById('publication-text');
    const previewContainer = document.getElementById('publication-preview-container');
    const communitySelect = document.getElementById('publication-community-select');

    if (!publishButton || !textInput || !communitySelect) return; 

    const textContent = textInput.value.trim();
    
    // --- ▼▼▼ LÓGICA DE VALIDACIÓN MODIFICADA ▼▼▼ ---
    let communityId = communitySelect.value;

    // Esta validación doble es por si acaso, aunque 'validatePublicationState' ya lo hace
    if (!textContent && selectedFiles.length === 0) {
        showAlert(getTranslation('js.publication.errorEmpty'), 'error');
        return;
    }
    if (communityId === '') {
        showAlert(getTranslation('js.publication.errorNoCommunity'), 'error');
        return;
    }
    // --- ▲▲▲ FIN LÓGICA DE VALIDACIÓN ▲▲▲ ---
    
    togglePublishSpinner(publishButton, true);

    const formData = new FormData();
    formData.append('action', 'create-post');
    formData.append('text_content', textContent);
    formData.append('community_id', communityId); // <--- Ahora siempre se envía un ID
    
    for (const file of selectedFiles) {
        formData.append('attachments[]', file, file.name);
    }

    try {
        const result = await callPublicationApi(formData);

        if (result.success) {
            showAlert(getTranslation(result.message || 'js.publication.success'), 'success');
            
            // Limpiar todo
            textInput.value = '';
            selectedFiles = [];
            if (previewContainer) previewContainer.innerHTML = '';
            communitySelect.value = ''; // Resetear el select a "Seleccione..."
            validatePublicationState(); // Deshabilitar el botón

            // Navegar a Home
            const link = document.createElement('a');
            link.href = window.projectBasePath + '/';
            link.setAttribute('data-nav-js', 'true');
            document.body.appendChild(link);
            link.click();
            link.remove();
            
        } else {
            showAlert(getTranslation(result.message || 'js.api.errorServer', result.data), 'error');
            togglePublishSpinner(publishButton, false);
        }

    } catch (error) {
        showAlert(getTranslation('js.api.errorConnection'), 'error');
        togglePublishSpinner(publishButton, false);
    }
}


/**
 * Gestiona la lógica de la página de creación de publicaciones.
 */
export function initPublicationManager() {
    
    // Resetear archivos al iniciar (por si el usuario navega de vuelta)
    selectedFiles = [];
    const previewContainer = document.getElementById('publication-preview-container');
    if (previewContainer) previewContainer.innerHTML = '';
    const fileInput = document.getElementById('publication-file-input');
    if (fileInput) fileInput.value = '';
    // --- ▼▼▼ LÍNEA AÑADIDA ▼▼▼ ---
    const communitySelect = document.getElementById('publication-community-select');
    if (communitySelect) communitySelect.value = ''; // Resetear el select
    // --- ▲▲▲ FIN LÍNEA AÑADIDA ▲▲▲ ---


    // --- LÓGICA DE PESTAÑAS (Sin cambios) ---
    document.body.addEventListener('click', (e) => {
        
        const toggleButton = e.target.closest('#post-type-toggle .component-toggle-tab');
        const section = e.target.closest('[data-section*="create-"]');

        if (!toggleButton || !section) {
            return;
        }
        
        e.preventDefault();

        if (toggleButton.classList.contains('active')) {
            return;
        }
        
        const newType = toggleButton.dataset.type;
        const postArea = document.getElementById('post-content-area');
        const pollArea = document.getElementById('poll-content-area');
        const toggleContainer = document.getElementById('post-type-toggle');

        toggleContainer.querySelectorAll('.component-toggle-tab').forEach(btn => {
            btn.classList.remove('active');
        });
        
        toggleButton.classList.add('active');

        if (newType === 'poll') {
            if (pollArea) {
                pollArea.classList.add('active');
                pollArea.classList.remove('disabled');
                pollArea.style.display = 'flex';
            }
            history.pushState(null, '', window.projectBasePath + '/create-poll');

        } else { // 'post'
            if (pollArea) {
                pollArea.classList.remove('active');
                pollArea.classList.add('disabled');
                pollArea.style.display = 'none';
            }
            history.pushState(null, '', window.projectBasePath + '/create-publication');
        }
        
        validatePublicationState();
    });

    // --- LÓGICA DE PUBLICACIÓN (MODIFICADA) ---

    // Listener para el textarea
    document.body.addEventListener('input', (e) => {
        const section = e.target.closest('[data-section*="create-"]');
        if (e.target.id === 'publication-text' && section) {
            validatePublicationState();
        }
    });
    
    // --- ▼▼▼ LISTENER AÑADIDO PARA EL SELECT ▼▼▼ ---
    // Listener para el selector de comunidad
    document.body.addEventListener('change', (e) => {
        const section = e.target.closest('[data-section*="create-"]');
        if (e.target.id === 'publication-community-select' && section) {
            validatePublicationState();
        }
    });
    // --- ▲▲▲ FIN LISTENER AÑADIDO ▲▲▲ ---

    // Listener para el botón de publicar
    document.body.addEventListener('click', (e) => {
        const section = e.target.closest('[data-section*="create-"]');
        if (e.target.id === 'publish-post-btn' && section) {
            e.preventDefault();
            handlePublishSubmit();
        }
    });
    
    // Listener para el botón de adjuntar
    document.body.addEventListener('click', (e) => {
        const section = e.target.closest('[data-section*="create-"]');
        if (e.target.id === 'attach-files-btn' && section) {
            e.preventDefault();
            document.getElementById('publication-file-input')?.click();
        }
    });
    
    // Listener para el input de archivos
    document.body.addEventListener('change', (e) => {
        const section = e.target.closest('[data-section*="create-"]');
         if (e.target.id === 'publication-file-input' && section) {
            handleFileSelection(e);
        }
    });
    
    // Validar estado al cargar la página
    if (document.querySelector('[data-section*="create-"].active')) {
         validatePublicationState();
    }
}
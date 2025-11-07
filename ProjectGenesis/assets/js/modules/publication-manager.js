// FILE: assets/js/modules/publication-manager.js
// (VERSIÓN ACTUALIZADA - Usa el trigger-selector en lugar de <select>)

import { callPublicationApi } from '../services/api-service.js';
import { getTranslation } from '../services/i18n-manager.js';
import { showAlert } from '../services/alert-manager.js';
// --- ▼▼▼ LÍNEA AÑADIDA ▼▼▼ ---
import { deactivateAllModules } from '../app/main-controller.js';
// --- ▲▲▲ FIN LÍNEA AÑADIDA ▲▲▲ ---

const MAX_FILES = 4;
let selectedFiles = []; // Array para guardar los objetos File
// --- ▼▼▼ LÍNEA AÑADIDA ▼▼▼ ---
let selectedCommunityId = null; // Guardará el ID de la comunidad
// --- ▲▲▲ FIN LÍNEA AÑADIDA ▲▲▲ ---

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
    
    if (!textInput || !publishButton) {
        // Si no existe el select (p.ej. porque no hay comunidades), el botón debe estar deshabilitado
        if(publishButton) publishButton.disabled = true;
        return;
    }

    const hasText = textInput.value.trim().length > 0;
    const hasFiles = selectedFiles.length > 0;
    // --- ▼▼▼ LÍNEA MODIFICADA ▼▼▼ ---
    const hasCommunity = selectedCommunityId !== null; // Comprueba que se haya seleccionado un ID
    // --- ▲▲▲ FIN LÍNEA MODIFICADA ▲▲▲ ---
    
    // Habilita el botón si hay (texto O archivos) Y si hay una comunidad seleccionada
    // --- ▼▼▼ LÍNEA MODIFICADA ▼▼▼ ---
    publishButton.disabled = (!hasText && !hasFiles) || !hasCommunity;
    // --- ▲▲▲ FIN LÍNEA MODIFICADA ▲▲▲ ---
}

// ... (Las funciones createPreviewElement, removeFilePreview y handleFileSelection permanecen sin cambios) ...

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

    // --- ▼▼▼ LÍNEA MODIFICADA (ya no necesitamos el select) ▼▼▼ ---
    if (!publishButton || !textInput) return; 

    const textContent = textInput.value.trim();
    
    // --- ▼▼▼ LÓGICA DE VALIDACIÓN MODIFICADA ▼▼▼ ---
    let communityId = selectedCommunityId; // Usar la variable global

    // Esta validación doble es por si acaso, aunque 'validatePublicationState' ya lo hace
    if (!textContent && selectedFiles.length === 0) {
        showAlert(getTranslation('js.publication.errorEmpty'), 'error');
        return;
    }
    if (communityId === null) { // Comprobar si es null
        showAlert(getTranslation('js.publication.errorNoCommunity'), 'error');
        return;
    }
    // --- ▲▲▲ FIN LÓGICA DE VALIDACIÓN ▲▲▲ ---
    
    togglePublishSpinner(publishButton, true);

    const formData = new FormData();
    formData.append('action', 'create-post');
    formData.append('text_content', textContent);
    formData.append('community_id', communityId); // <--- Ahora se envía el ID guardado
    
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
            // --- ▼▼▼ INICIO DE BLOQUE MODIFICADO/AÑADIDO ▼▼▼ ---
            selectedCommunityId = null; 
            if (previewContainer) previewContainer.innerHTML = '';
            
            // Resetear el trigger
            const triggerText = document.getElementById('publication-community-text');
            const triggerIcon = document.getElementById('publication-community-icon');
            if (triggerText) {
                triggerText.textContent = getTranslation('create_publication.selectCommunity');
                triggerText.removeAttribute('data-i18n'); // Quitar i18n para que no se traduzca
            }
            if (triggerIcon) {
                triggerIcon.textContent = 'public'; // Icono por defecto
            }
            // Resetear el popover
            const popover = document.querySelector('[data-module="moduleCommunitySelect"]');
            if (popover) {
                popover.querySelectorAll('.menu-link').forEach(link => link.classList.remove('active'));
                popover.querySelectorAll('.menu-link-check-icon').forEach(icon => icon.innerHTML = '');
            }
            // --- ▲▲▲ FIN DE BLOQUE MODIFICADO/AÑADIDO ▲▲▲ ---
            
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
 * Resetea el trigger de comunidad a su estado inicial
 */
function resetCommunityTrigger() {
    selectedCommunityId = null;
    const triggerText = document.getElementById('publication-community-text');
    const triggerIcon = document.getElementById('publication-community-icon');
    
    if (triggerText) {
        triggerText.textContent = getTranslation('create_publication.selectCommunity');
        triggerText.setAttribute('data-i18n', 'create_publication.selectCommunity'); // Poner i18n
    }
    if (triggerIcon) {
        triggerIcon.textContent = 'public'; // Icono por defecto
    }

    const popover = document.querySelector('[data-module="moduleCommunitySelect"]');
    if (popover) {
        popover.querySelectorAll('.menu-link').forEach(link => link.classList.remove('active'));
        popover.querySelectorAll('.menu-link-check-icon').forEach(icon => icon.innerHTML = '');
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
    
    // --- ▼▼▼ LÍNEA MODIFICADA ▼▼▼ ---
    resetCommunityTrigger(); // Resetea la variable y el trigger visual
    // --- ▲▲▲ FIN LÍNEA MODIFICADA ▲▲▲ ---


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
    
    // --- ▼▼▼ LISTENER MODIFICADO (ahora es 'click' en lugar de 'change') ▼▼▼ ---
    document.body.addEventListener('click', (e) => {
        const section = e.target.closest('[data-section*="create-"]');
        if (!section) return; // Salir si no estamos en la sección de crear

        // 1. Manejar clic en el Trigger
        const trigger = e.target.closest('#publication-community-trigger[data-action="toggleModuleCommunitySelect"]');
        if (trigger) {
            e.preventDefault();
            e.stopPropagation(); // Detener para que el main-controller no lo cierre
            
            const module = document.querySelector('[data-module="moduleCommunitySelect"]');
            if (module) {
                deactivateAllModules(module); // Cerrar otros
                module.classList.toggle('disabled');
                module.classList.toggle('active');
            }
            return;
        }

        // 2. Manejar clic en un Link del Popover
        const menuLink = e.target.closest('[data-module="moduleCommunitySelect"] .menu-link[data-value]');
        if (menuLink) {
            e.preventDefault();
            
            // Obtener datos del link
            const newId = menuLink.dataset.value;
            const newText = menuLink.dataset.text;
            
            // Guardar el ID
            selectedCommunityId = newId;

            // Actualizar el Trigger
            const triggerText = document.getElementById('publication-community-text');
            const triggerIcon = document.getElementById('publication-community-icon');
            
            if (triggerText) {
                triggerText.textContent = newText;
                triggerText.removeAttribute('data-i18n'); // Es un nombre propio, no una clave
            }
            if (triggerIcon) {
                triggerIcon.textContent = 'group'; // Icono de comunidad
            }

            // Actualizar estado 'active' en el popover
            const menuList = menuLink.closest('.menu-list');
            if (menuList) {
                menuList.querySelectorAll('.menu-link').forEach(link => link.classList.remove('active'));
                menuList.querySelectorAll('.menu-link-check-icon').forEach(icon => icon.innerHTML = '');
            }
            
            menuLink.classList.add('active');
            const checkIcon = menuLink.querySelector('.menu-link-check-icon');
            if (checkIcon) checkIcon.innerHTML = '<span class="material-symbols-rounded">check</span>';

            // Cerrar el popover
            deactivateAllModules();
            
            // Re-validar el botón de publicar
            validatePublicationState();
            return;
        }
        
        // 3. Manejar clic en el botón de Publicar
        if (e.target.id === 'publish-post-btn') {
            e.preventDefault();
            handlePublishSubmit();
            return;
        }
        
        // 4. Manejar clic en el botón de Adjuntar
        if (e.target.id === 'attach-files-btn') {
            e.preventDefault();
            document.getElementById('publication-file-input')?.click();
            return;
        }
    });
    // --- ▲▲▲ FIN LISTENER MODIFICADO ▲▲▲ ---
    
    // Listener para el input de archivos (sin cambios)
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
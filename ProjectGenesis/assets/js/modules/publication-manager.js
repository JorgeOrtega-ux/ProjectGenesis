// FILE: assets/js/modules/publication-manager.js
// (VERSIÓN ACTUALIZADA - Soporta trigger-select y creación de encuestas)

import { callPublicationApi } from '../services/api-service.js';
import { getTranslation } from '../services/i18n-manager.js';
import { showAlert } from '../services/alert-manager.js';
import { deactivateAllModules } from '../app/main-controller.js';

const MAX_FILES = 4;
const MAX_POLL_OPTIONS = 6;
let selectedFiles = []; // Array para guardar los objetos File
let selectedCommunityId = null; // Guardará el ID de la comunidad
let currentPostType = 'post'; // 'post' o 'poll'

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
 * Valida el estado actual y habilita/deshabilita el botón de publicar.
 */
function validatePublicationState() {
    const publishButton = document.getElementById('publish-post-btn');
    if (!publishButton) return;

    const hasCommunity = selectedCommunityId !== null;
    let isContentValid = false;

    if (currentPostType === 'post') {
        const textInput = document.getElementById('publication-text');
        const hasText = textInput ? textInput.value.trim().length > 0 : false;
        const hasFiles = selectedFiles.length > 0;
        isContentValid = hasText || hasFiles;
    } else { // 'poll'
        const questionInput = document.getElementById('poll-question');
        const options = document.querySelectorAll('#poll-options-container .component-input-group');
        const hasQuestion = questionInput ? questionInput.value.trim().length > 0 : false;
        const hasMinOptions = options.length >= 2;
        // Opcional: verificar que las opciones no estén vacías
        const allOptionsFilled = Array.from(options).every(opt => opt.querySelector('input').value.trim().length > 0);
        
        isContentValid = hasQuestion && hasMinOptions && allOptionsFilled;
    }
    
    // Habilita el botón si el contenido es válido Y hay una comunidad seleccionada
    publishButton.disabled = !isContentValid || !hasCommunity;
}

// --- Gestión de Vistas Previas de Archivos (POST) ---

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
    
    removeBtn.addEventListener('click', () => {
        removeFilePreview(previewItem, file);
    });
    
    previewItem.appendChild(removeBtn);
    container.appendChild(previewItem);
}

function removeFilePreview(previewItem, file) {
    selectedFiles = selectedFiles.filter(f => f !== file);
    previewItem.remove();
    const fileInput = document.getElementById('publication-file-input');
    if (fileInput) fileInput.value = ''; 
    validatePublicationState();
}

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
        if (!['image/jpeg', 'image/png', 'image/gif', 'image/webp'].includes(file.type)) {
            showAlert(getTranslation('js.publication.errorFileType'), 'error');
            continue;
        }
        if (file.size > MAX_SIZE_BYTES) {
            showAlert(getTranslation('js.publication.errorFileSize').replace('%size%', MAX_SIZE_MB), 'error');
            continue;
        }
        selectedFiles.push(file);
        const reader = new FileReader();
        reader.onload = (e) => {
            createPreviewElement(file, e.target.result);
        };
        reader.readAsDataURL(file);
    }
    validatePublicationState();
}

// --- Gestión de Opciones de Encuesta (POLL) ---

function addPollOption(focusNew = true) {
    const container = document.getElementById('poll-options-container');
    if (!container) return;

    const optionCount = container.querySelectorAll('.component-input-group').length;
    if (optionCount >= MAX_POLL_OPTIONS) {
        showAlert(getTranslation('js.publication.errorPollMaxOptions'), 'info'); // Nueva clave i18n
        return;
    }
    
    const newOptionIndex = optionCount + 1;
    const optionDiv = document.createElement('div');
    optionDiv.className = 'component-input-group';
    optionDiv.innerHTML = `
        <input type="text" id="poll-option-${newOptionIndex}" class="component-input" placeholder=" " maxlength="100">
        <label for="poll-option-${newOptionIndex}">${getTranslation('create_publication.pollOptionLabel')} ${newOptionIndex}</label>
        <button type="button" class="auth-toggle-password" data-action="remove-poll-option" title="${getTranslation('create_publication.pollRemoveOption')}">
            <span class="material-symbols-rounded">remove_circle</span>
        </button>
    `;

    container.appendChild(optionDiv);
    
    if (focusNew) {
        optionDiv.querySelector('input').focus();
    }
    
    // Deshabilitar el botón de añadir si llegamos al límite
    const addBtn = document.getElementById('add-poll-option-btn');
    if (addBtn && (optionCount + 1) >= MAX_POLL_OPTIONS) {
        addBtn.disabled = true;
    }
    
    validatePublicationState();
}

function removePollOption(button) {
    const optionDiv = button.closest('.component-input-group');
    if (!optionDiv) return;
    
    optionDiv.remove();
    
    // Re-etiquetar las opciones restantes
    const container = document.getElementById('poll-options-container');
    container.querySelectorAll('.component-input-group').forEach((opt, index) => {
        const newIndex = index + 1;
        const input = opt.querySelector('input');
        const label = opt.querySelector('label');
        if (input) input.id = `poll-option-${newIndex}`;
        if (label) {
            label.htmlFor = `poll-option-${newIndex}`;
            label.textContent = `${getTranslation('create_publication.pollOptionLabel')} ${newIndex}`;
        }
    });
    
    // Habilitar el botón de añadir si bajamos del límite
    const addBtn = document.getElementById('add-poll-option-btn');
    if (addBtn) {
        addBtn.disabled = false;
    }
    
    validatePublicationState();
}

/**
 * Resetea el formulario al estado inicial
 */
function resetForm() {
    // Limpiar post
    const textInput = document.getElementById('publication-text');
    if (textInput) textInput.value = '';
    selectedFiles = [];
    const previewContainer = document.getElementById('publication-preview-container');
    if (previewContainer) previewContainer.innerHTML = '';
    const fileInput = document.getElementById('publication-file-input');
    if (fileInput) fileInput.value = '';
    
    // Limpiar encuesta
    const pollQuestion = document.getElementById('poll-question');
    if (pollQuestion) pollQuestion.value = '';
    const pollOptions = document.getElementById('poll-options-container');
    if (pollOptions) pollOptions.innerHTML = '';
    
    // Limpiar comunidad
    selectedCommunityId = null; 
    const triggerText = document.getElementById('publication-community-text');
    const triggerIcon = document.getElementById('publication-community-icon');
    if (triggerText) {
        triggerText.textContent = getTranslation('create_publication.selectCommunity');
        triggerText.setAttribute('data-i18n', 'create_publication.selectCommunity');
    }
    if (triggerIcon) {
        triggerIcon.textContent = 'public'; 
    }
    const popover = document.querySelector('[data-module="moduleCommunitySelect"]');
    if (popover) {
        popover.querySelectorAll('.menu-link').forEach(link => link.classList.remove('active'));
        popover.querySelectorAll('.menu-link-check-icon').forEach(icon => icon.innerHTML = '');
    }
    
    // Resetear estado
    validatePublicationState();
}

/**
 * Manejador para el envío de la publicación (post o encuesta).
 */
async function handlePublishSubmit() {
    const publishButton = document.getElementById('publish-post-btn');
    if (!publishButton) return;

    let communityId = selectedCommunityId;
    if (communityId === null) {
        showAlert(getTranslation('js.publication.errorNoCommunity'), 'error');
        return;
    }

    togglePublishSpinner(publishButton, true);

    const formData = new FormData();
    formData.append('action', 'create-post'); // Usamos la misma acción
    formData.append('community_id', communityId);
    formData.append('post_type', currentPostType);

    try {
        if (currentPostType === 'post') {
            const textContent = document.getElementById('publication-text').value.trim();
            if (!textContent && selectedFiles.length === 0) {
                throw new Error('js.publication.errorEmpty');
            }
            formData.append('text_content', textContent);
            for (const file of selectedFiles) {
                formData.append('attachments[]', file, file.name);
            }
        } else { // 'poll'
            const question = document.getElementById('poll-question').value.trim();
            const options = Array.from(document.querySelectorAll('#poll-options-container input'))
                                 .map(input => input.value.trim())
                                 .filter(text => text.length > 0);
            
            if (question.length === 0) {
                throw new Error('js.publication.errorPollQuestion');
            }
            if (options.length < 2) {
                throw new Error('js.publication.errorPollOptions');
            }
            
            formData.append('poll_question', question);
            formData.append('poll_options', JSON.stringify(options));
        }

        const result = await callPublicationApi(formData);

        if (result.success) {
            showAlert(getTranslation(result.message || 'js.publication.success'), 'success');
            
            resetForm(); 

            // Navegar a Home
            const link = document.createElement('a');
            link.href = window.projectBasePath + '/';
            link.setAttribute('data-nav-js', 'true');
            document.body.appendChild(link);
            link.click();
            link.remove();
            
        } else {
            throw new Error(result.message || 'js.api.errorServer');
        }

    } catch (error) {
        showAlert(getTranslation(error.message || 'js.api.errorConnection'), 'error');
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
        triggerText.setAttribute('data-i18n', 'create_publication.selectCommunity');
    }
    if (triggerIcon) {
        triggerIcon.textContent = 'public'; 
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
    
    // Resetear todo al iniciar
    resetForm();
    currentPostType = document.querySelector('.component-toggle-tab.active')?.dataset.type || 'post';

    // Añadir 2 opciones por defecto si es una encuesta y no hay opciones
    if (currentPostType === 'poll') {
        const optionsContainer = document.getElementById('poll-options-container');
        if (optionsContainer && optionsContainer.children.length === 0) {
            addPollOption(false);
            addPollOption(false);
        }
    }


    // --- LÓGICA DE PESTAÑAS (Post / Poll) ---
    document.body.addEventListener('click', (e) => {
        const toggleButton = e.target.closest('#post-type-toggle .component-toggle-tab');
        const section = e.target.closest('[data-section*="create-"]');
        if (!toggleButton || !section) return;
        e.preventDefault();
        if (toggleButton.classList.contains('active')) return;
        
        const newType = toggleButton.dataset.type;
        currentPostType = newType; // Actualizar estado global
        
        const postArea = document.getElementById('post-content-area');
        const pollArea = document.getElementById('poll-content-area');
        const attachBtn = document.getElementById('attach-files-btn');
        const attachSpacer = document.getElementById('attach-files-spacer');
        const toggleContainer = document.getElementById('post-type-toggle');

        toggleContainer.querySelectorAll('.component-toggle-tab').forEach(btn => {
            btn.classList.remove('active');
        });
        toggleButton.classList.add('active');

        if (newType === 'poll') {
            if (postArea) { postArea.style.display = 'none'; postArea.classList.remove('active'); postArea.classList.add('disabled'); }
            if (pollArea) { pollArea.style.display = 'flex'; pollArea.classList.add('active'); pollArea.classList.remove('disabled'); }
            if (attachBtn) attachBtn.style.display = 'none';
            if (attachSpacer) attachSpacer.style.display = 'block';
            history.pushState(null, '', window.projectBasePath + '/create-poll');
            
            // Añadir 2 opciones por defecto si no hay
            const optionsContainer = document.getElementById('poll-options-container');
            if (optionsContainer && optionsContainer.children.length === 0) {
                addPollOption(false);
                addPollOption(false);
            }

        } else { // 'post'
            if (postArea) { postArea.style.display = 'flex'; postArea.classList.add('active'); postArea.classList.remove('disabled'); }
            if (pollArea) { pollArea.style.display = 'none'; pollArea.classList.remove('active'); pollArea.classList.add('disabled'); }
            if (attachBtn) attachBtn.style.display = 'flex';
            if (attachSpacer) attachSpacer.style.display = 'none';
            history.pushState(null, '', window.projectBasePath + '/create-publication');
        }
        
        validatePublicationState();
    });

    // --- LÓGICA DE INPUTS Y CLICKS ---
    document.body.addEventListener('input', (e) => {
        const section = e.target.closest('[data-section*="create-"]');
        if (!section) return;

        if (e.target.id === 'publication-text' || e.target.id === 'poll-question' || e.target.closest('#poll-options-container')) {
            validatePublicationState();
        }
    });
    
    document.body.addEventListener('click', (e) => {
        const section = e.target.closest('[data-section*="create-"]');
        if (!section) return; 

        // 1. Manejar clic en el Trigger de Comunidad
        const trigger = e.target.closest('#publication-community-trigger[data-action="toggleModuleCommunitySelect"]');
        if (trigger) {
            e.preventDefault();
            e.stopPropagation();
            const module = document.querySelector('[data-module="moduleCommunitySelect"]');
            if (module) {
                deactivateAllModules(module); 
                module.classList.toggle('disabled');
                module.classList.toggle('active');
            }
            return;
        }

        // 2. Manejar clic en un Link del Popover de Comunidad
        const menuLink = e.target.closest('[data-module="moduleCommunitySelect"] .menu-link[data-value]');
        if (menuLink) {
            e.preventDefault();
            const newId = menuLink.dataset.value;
            const newText = menuLink.dataset.text;
            
            selectedCommunityId = newId;

            const triggerText = document.getElementById('publication-community-text');
            const triggerIcon = document.getElementById('publication-community-icon');
            
            if (triggerText) {
                triggerText.textContent = newText;
                triggerText.removeAttribute('data-i18n'); 
            }
            if (triggerIcon) {
                triggerIcon.textContent = 'group'; 
            }

            const menuList = menuLink.closest('.menu-list');
            if (menuList) {
                menuList.querySelectorAll('.menu-link').forEach(link => link.classList.remove('active'));
                menuList.querySelectorAll('.menu-link-check-icon').forEach(icon => icon.innerHTML = '');
            }
            
            menuLink.classList.add('active');
            const checkIcon = menuLink.querySelector('.menu-link-check-icon');
            if (checkIcon) checkIcon.innerHTML = '<span class="material-symbols-rounded">check</span>';

            deactivateAllModules();
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
        
        // 5. Manejar clic en Añadir Opción de Encuesta
        if (e.target.id === 'add-poll-option-btn' || e.target.closest('#add-poll-option-btn')) {
            e.preventDefault();
            addPollOption(true);
            return;
        }
        
        // 6. Manejar clic en Quitar Opción de Encuesta
        const removeOptionBtn = e.target.closest('[data-action="remove-poll-option"]');
        if (removeOptionBtn) {
            e.preventDefault();
            removePollOption(removeOptionBtn);
            return;
        }
    });
    
    // Listener para el input de archivos
    document.body.addEventListener('change', (e) => {
        const section = e.target.closest('[data-section*="create-"]');
         if (e.target.id === 'publication-file-input' && section) {
            handleFileSelection(e);
        }
    });
    
    // Validar estado al cargar
    if (document.querySelector('[data-section*="create-"].active')) {
         validatePublicationState();
    }
}
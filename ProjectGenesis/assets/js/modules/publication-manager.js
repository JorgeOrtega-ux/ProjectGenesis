// FILE: assets/js/modules/publication-manager.js

import { callPublicationApi } from '../services/api-service.js';
import { getTranslation } from '../services/i18n-manager.js';
import { showAlert } from '../services/alert-manager.js';
import { deactivateAllModules } from '../app/main-controller.js';

const MAX_FILES = 4;
const MAX_POLL_OPTIONS = 6;
const MAX_HASHTAGS = 5;
let selectedFiles = []; 
let selectedCommunityId = 'profile'; 
let selectedPrivacyLevel = 'public'; 
let currentPostType = 'post'; // Valor por defecto
const defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";

// --- ▼▼▼ INICIO DE NUEVAS VARIABLES GLOBALES PARA EL VISOR ▼▼▼ ---
let viewerModal = null;
let viewerImage = null;
let viewerAvatar = null;
let viewerName = null;
let viewerBtnPrev = null;
let viewerBtnNext = null;
let viewerBtnClose = null;

let currentViewerImages = []; // Array de URLs de las imágenes en el post actual
let currentViewerIndex = 0;   // Índice de la imagen que se está viendo
// --- ▲▲▲ FIN DE NUEVAS VARIABLES GLOBALES PARA EL VISOR ▲▲▲ ---


// --- (FUNCIONES HELPER - SIN CAMBIOS) ---
function togglePrimaryButtonSpinner(button, isLoading) {
    if (!button) return;
    button.disabled = isLoading;
    if (isLoading) {
        button.dataset.originalText = button.innerHTML;
        button.innerHTML = `<span class="logout-spinner" style="width: 20px; height: 20px; border-width: 2px; margin: 0 auto; border-top-color: #ffffff; border-left-color: #ffffff20; border-bottom-color: #ffffff20; border-right-color: #ffffff20;"></span>`;
    } else {
        if (button.dataset.originalText) {
            button.innerHTML = button.dataset.originalText;
        }
    }
}
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
function showValidationError(messageKey) {
    const errorDiv = document.getElementById('create-post-error-div');
    if (errorDiv) {
        errorDiv.textContent = getTranslation(messageKey);
        errorDiv.style.display = 'block';
    }
}
function hideValidationError() {
     const errorDiv = document.getElementById('create-post-error-div');
    if (errorDiv) {
        errorDiv.style.display = 'none';
    }
}
function getHashtags() {
    const postHashtagInput = document.getElementById('publication-hashtags');
    const pollHashtagInput = document.getElementById('poll-hashtags');
    let hashtagInput = null;

    if (currentPostType === 'post' && postHashtagInput) {
        hashtagInput = postHashtagInput;
    } else if (currentPostType === 'poll' && pollHashtagInput) {
        hashtagInput = pollHashtagInput;
    }

    if (!hashtagInput) return { valid: true, tags: [] }; 

    const rawValue = hashtagInput.value.trim();
    if (rawValue.length === 0) {
        return { valid: true, tags: [] }; 
    }

    const tags = rawValue.split(/[\s,]+/) 
                         .map(tag => tag.startsWith('#') ? tag.substring(1) : tag) 
                         .map(tag => tag.trim())
                         .filter(tag => tag.length > 0) 
                         .filter((value, index, self) => self.indexOf(value) === index); 

    if (tags.length > MAX_HASHTAGS) {
        return { valid: false, tags: [], error: 'js.publication.errorHashtagLimit' }; 
    }
    
    const MAX_TAG_LENGTH = 50;
    for (const tag of tags) {
        if (tag.length > MAX_TAG_LENGTH) {
            return { valid: false, tags: [], error: 'js.publication.errorHashtagLength' }; 
        }
    }

    return { valid: true, tags: tags };
}

function validatePublicationState() {
    
    const publishButton = document.getElementById('publish-post-btn');
    if (!publishButton) {
        return;
    }
    
    hideValidationError(); 

    const hasDestination = selectedCommunityId !== null && selectedCommunityId !== '';
    
    let isContentValid = false;
    const hashtagValidation = getHashtags(); 


    if (currentPostType === 'post') {
        const textInput = document.getElementById('publication-text');
        const titleInput = document.getElementById('publication-title'); 
        const hasText = textInput ? textInput.value.trim().length > 0 : false;
        const hasTitle = titleInput ? titleInput.value.trim().length > 0 : false; 
        const hasFiles = selectedFiles.length > 0;
        isContentValid = hasText || hasFiles || hasTitle || (hashtagValidation.tags.length > 0); 
        
        
    } else { // Asume 'poll'
        const questionInput = document.getElementById('poll-question');
        const options = document.querySelectorAll('#poll-options-container .component-input-group');
        const hasQuestion = questionInput ? questionInput.value.trim().length > 0 : false;
        const hasMinOptions = options.length >= 2;
        const allOptionsFilled = Array.from(options).every(opt => opt.querySelector('input').value.trim().length > 0);
        
        isContentValid = hasQuestion && hasMinOptions && allOptionsFilled;

    }
    
    if (!hashtagValidation.valid) {
        showValidationError(hashtagValidation.error);
        publishButton.disabled = true;
        return;
    }
    
    publishButton.disabled = !isContentValid || !hasDestination;
}

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
function addPollOption(focusNew = true) {
    const container = document.getElementById('poll-options-container');
    if (!container) return;
    const optionCount = container.querySelectorAll('.component-input-group').length;
    if (optionCount >= MAX_POLL_OPTIONS) {
        showAlert(getTranslation('js.publication.errorPollMaxOptions'), 'info'); 
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
    const addBtn = document.getElementById('add-poll-option-btn');
    if (addBtn) {
        addBtn.disabled = false;
    }
    validatePublicationState();
}
function resetForm() {
    const titleInput = document.getElementById('publication-title');
    if (titleInput) titleInput.value = '';
    const textInput = document.getElementById('publication-text');
    if (textInput) textInput.value = '';
    selectedFiles = [];
    const previewContainer = document.getElementById('publication-preview-container');
    if (previewContainer) previewContainer.innerHTML = '';
    const fileInput = document.getElementById('publication-file-input');
    if (fileInput) fileInput.value = '';
    const pollQuestion = document.getElementById('poll-question');
    if (pollQuestion) pollQuestion.value = '';
    const pollOptions = document.getElementById('poll-options-container');
    if (pollOptions) pollOptions.innerHTML = '';
    const postHashtags = document.getElementById('publication-hashtags');
    if (postHashtags) postHashtags.value = '';
    const pollHashtags = document.getElementById('poll-hashtags');
    if (pollHashtags) pollHashtags.value = '';
    hideValidationError();
    selectedCommunityId = 'profile'; 
    const triggerText = document.getElementById('publication-community-text');
    const triggerIcon = document.getElementById('publication-community-icon');
    const myProfileText = getTranslation('create_publication.myProfile') || 'Mi Perfil';
    if (triggerText) {
        triggerText.textContent = myProfileText;
        triggerText.setAttribute('data-i18n', 'create_publication.myProfile');
    }
    if (triggerIcon) {
        triggerIcon.textContent = 'person'; 
    }
    const popover = document.querySelector('[data-module="moduleCommunitySelect"]');
    if (popover) {
        popover.querySelectorAll('.menu-link').forEach(link => {
            const isDefault = link.dataset.value === 'profile';
            link.classList.toggle('active', isDefault);
            const icon = link.querySelector('.menu-link-check-icon');
            if (icon) {
                icon.innerHTML = isDefault ? '<span class="material-symbols-rounded">check</span>' : '';
            }
        });
    }
    selectedPrivacyLevel = 'public';
    const privacyTriggerText = document.getElementById('publication-privacy-text');
    const privacyTriggerIcon = document.getElementById('publication-privacy-icon');
    if (privacyTriggerText) {
        privacyTriggerText.textContent = getTranslation('post.privacy.public');
        privacyTriggerText.setAttribute('data-i18n', 'post.privacy.public');
    }
    if (privacyTriggerIcon) {
        privacyTriggerIcon.textContent = 'public'; 
    }
    const privacyPopover = document.querySelector('[data-module="modulePrivacySelect"]');
    if (privacyPopover) {
        privacyPopover.querySelectorAll('.menu-link').forEach(link => {
            const isDefault = link.dataset.value === 'public';
            link.classList.toggle('active', isDefault);
            const icon = link.querySelector('.menu-link-check-icon');
            if (icon) {
                icon.innerHTML = isDefault ? '<span class="material-symbols-rounded">check</span>' : '';
            }
        });
    }
    // No llamamos a validatePublicationState() aquí, porque initPublicationForm() lo hará
}
async function handlePublishSubmit() {
    
    const publishButton = document.getElementById('publish-post-btn');
    if (!publishButton) return;

    const hashtagValidation = getHashtags();
    if (!hashtagValidation.valid) {
        showValidationError(hashtagValidation.error);
        return;
    }

    let communityId = selectedCommunityId;
    if (communityId === 'profile') {
        communityId = '';
    } else if (communityId === null || communityId === undefined) {
        showAlert(getTranslation('js.publication.errorNoCommunity'), 'error'); 
        return;
    }

    togglePublishSpinner(publishButton, true);

    const formData = new FormData();
    formData.append('action', 'create-post'); 
    formData.append('community_id', communityId); 
    formData.append('post_type', currentPostType); // <-- ¡¡VALOR CLAVE!!
    formData.append('privacy_level', selectedPrivacyLevel);
    formData.append('hashtags', JSON.stringify(hashtagValidation.tags));


    try {
        if (currentPostType === 'post') {
            
            const title = document.getElementById('publication-title').value.trim();
            const textContent = document.getElementById('publication-text').value.trim();
            
            if (!textContent && selectedFiles.length === 0 && !title && hashtagValidation.tags.length === 0) {
                throw new Error('js.publication.errorEmpty');
            }
            
            formData.append('title', title);
            formData.append('text_content', textContent);
            
            for (const file of selectedFiles) {
                formData.append('attachments[]', file, file.name);
            }
        } else { // Asume 'poll'

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
            
            // No reseteamos el formulario aquí, la navegación lo hará
            // resetForm(); 

            let returnUrl = window.projectBasePath + '/'; 
            if (communityId === '') {
                const profileLink = document.querySelector('.popover-module[data-module="moduleSelect"] a[data-i18n="header.profile.myProfile"]');
                if (profileLink && profileLink.href) {
                    returnUrl = profileLink.href;
                }
            }
            
            const link = document.createElement('a');
            link.href = returnUrl;
            link.setAttribute('data-nav-js', 'true');
            document.body.appendChild(link);
            link.click();
            link.remove();
            
        } else {
            throw new Error(result.message || 'js.api.errorServer');
        }

    } catch (error) {
        if (error.message === 'js.publication.errorHashtagLimit' || error.message === 'js.publication.errorHashtagLength') {
            showValidationError(error.message);
        } else {
            showAlert(getTranslation(error.message || 'js.api.errorConnection'), 'error');
        }
        togglePublishSpinner(publishButton, false);
    }
}
async function handleProfilePostSubmit(form) {
    const submitButton = form.querySelector('button[type="submit"]');
    const input = form.querySelector('input[name="text_content"]'); 
    if (!submitButton || !input || submitButton.disabled || !input.value.trim()) {
        return;
    }
    togglePrimaryButtonSpinner(submitButton, true);
    const formData = new FormData(form);
    input.disabled = true;
    formData.append('hashtags', JSON.stringify([])); 
    try {
        const result = await callPublicationApi(formData);
        if (result.success) {
            showAlert(getTranslation('js.publication.success'), 'success');
            input.value = ''; 
            input.dispatchEvent(new Event('input')); 
            const currentTab = document.querySelector('.profile-nav-button.active[data-nav-js="true"]');
            const postsTab = document.querySelector('.profile-nav-button[data-nav-js="true"][data-href*="/profile/"]'); 
            if (currentTab && (currentTab.href.endsWith('/profile/' + window.location.pathname.split('/')[2]) || currentTab.href.endsWith('/posts'))) {
                currentTab.click(); 
            } else if (postsTab) {
                postsTab.click(); 
            } else {
                window.location.reload(); 
            }
        } else {
            showAlert(getTranslation(result.message || 'js.api.errorServer'), 'error');
        }
    } catch (e) {
        showAlert(getTranslation('js.api.errorConnection'), 'error');
    } finally {
        togglePrimaryButtonSpinner(submitButton, false);
        input.disabled = false;
    }
}
function resetCommunityTrigger() {
    selectedCommunityId = 'profile';
    const triggerText = document.getElementById('publication-community-text');
    const triggerIcon = document.getElementById('publication-community-icon');
    const myProfileText = getTranslation('create_publication.myProfile') || 'Mi Perfil';
    if (triggerText) {
        triggerText.textContent = myProfileText;
        triggerText.setAttribute('data-i18n', 'create_publication.myProfile');
    }
    if (triggerIcon) {
        triggerIcon.textContent = 'person'; 
    }
    const popover = document.querySelector('[data-module="moduleCommunitySelect"]');
    if (popover) {
        popover.querySelectorAll('.menu-link').forEach(link => {
            const isDefault = link.dataset.value === 'profile';
            link.classList.toggle('active', isDefault);
            const icon = link.querySelector('.menu-link-check-icon');
            if (icon) {
                icon.innerHTML = isDefault ? '<span class="material-symbols-rounded">check</span>' : '';
            }
        });
    }
}

// --- ▼▼▼ INICIO DE NUEVAS FUNCIONES PARA EL VISOR ▼▼▼ ---

/**
 * Cierra el visor de fotos y resetea su estado.
 */
function closePhotoViewer() {
    if (!viewerModal) return;
    viewerModal.classList.remove('active');
    viewerImage.src = ''; // Detiene la carga de la imagen
    currentViewerImages = [];
    currentViewerIndex = 0;
}

/**
 * Muestra la imagen siguiente en el array 'currentViewerImages'.
 */
function showNextImage() {
    if (currentViewerIndex < currentViewerImages.length - 1) {
        currentViewerIndex++;
        viewerImage.src = currentViewerImages[currentViewerIndex];
        updateViewerControls();
    }
}

/**
 * Muestra la imagen anterior en el array 'currentViewerImages'.
 */
function showPrevImage() {
    if (currentViewerIndex > 0) {
        currentViewerIndex--;
        viewerImage.src = currentViewerImages[currentViewerIndex];
        updateViewerControls();
    }
}

/**
 * Habilita/deshabilita los botones de previo/siguiente según el índice.
 */
function updateViewerControls() {
    if (!viewerBtnPrev || !viewerBtnNext) return;
    viewerBtnPrev.disabled = (currentViewerIndex === 0);
    viewerBtnNext.disabled = (currentViewerIndex >= currentViewerImages.length - 1);
}

/**
 * Abre el visor de fotos con la imagen clicada.
 * @param {HTMLImageElement} clickedImageEl - El elemento <img> que fue clicado.
 */
function openPhotoViewer(clickedImageEl) {
    if (!viewerModal) return;

    // 1. Encontrar el post padre
    const postCard = clickedImageEl.closest('.component-card--post');
    if (!postCard) return;

    // 2. Encontrar info del publicador
    const avatarImg = postCard.querySelector('.post-card-header .component-card__avatar img');
    const nameEl = postCard.querySelector('.post-card-header .component-card__title');

    // 3. Poblar el header del modal
    viewerAvatar.src = avatarImg ? avatarImg.src : defaultAvatar;
    viewerName.textContent = nameEl ? nameEl.textContent : 'Publicación';
    
    // 4. Encontrar todas las imágenes del post
    const allImages = postCard.querySelectorAll('.post-attachment-item img');
    currentViewerImages = Array.from(allImages).map(img => img.src);
    
    // 5. Encontrar el índice de la imagen clicada
    currentViewerIndex = currentViewerImages.indexOf(clickedImageEl.src);
    if (currentViewerIndex === -1) currentViewerIndex = 0; // Fallback

    // 6. Mostrar la imagen clicada
    viewerImage.src = currentViewerImages[currentViewerIndex];
    
    // 7. Actualizar botones
    updateViewerControls();

    // 8. Mostrar el modal
    viewerModal.classList.add('active');
}

/**
 * Inicializa el visor de fotos (se llama una vez).
 * Busca los elementos del modal y añade los listeners de control.
 */
function initPhotoViewer() {
    viewerModal = document.getElementById('photo-viewer-modal');
    if (!viewerModal) return; // Si el modal no existe, no hacer nada

    viewerImage = document.getElementById('viewer-image');
    viewerAvatar = document.getElementById('viewer-user-avatar');
    viewerName = document.getElementById('viewer-user-name');
    viewerBtnPrev = document.getElementById('viewer-btn-prev');
    viewerBtnNext = document.getElementById('viewer-btn-next');
    viewerBtnClose = document.getElementById('viewer-btn-close');

    // Listeners de control
    viewerBtnClose?.addEventListener('click', closePhotoViewer);
    viewerBtnNext?.addEventListener('click', showNextImage);
    viewerBtnPrev?.addEventListener('click', showPrevImage);

    // Clic en el fondo para cerrar
    viewerModal.addEventListener('click', (e) => {
        if (e.target === viewerModal) { // Solo si se hace clic en el fondo
            closePhotoViewer();
        }
    });

    // Listener de teclado (Escape)
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && viewerModal.classList.contains('active')) {
            closePhotoViewer();
        }
        if (e.key === 'ArrowRight' && viewerModal.classList.contains('active')) {
            showNextImage();
        }
        if (e.key === 'ArrowLeft' && viewerModal.classList.contains('active')) {
            showPrevImage();
        }
    });
}
// --- ▲▲▲ FIN DE NUEVAS FUNCIONES PARA EL VISOR ▲▲▲ ---


// Esta función se llamará UNA SOLA VEZ desde app-init.js
// Configura los listeners globales que siempre deben estar activos.
export function setupPublicationListeners() {
    
    document.body.addEventListener('click', (e) => {
        
        // --- ▼▼▼ INICIO DE LÓGICA DEL VISOR DE FOTOS ▼▼▼ ---
        // Detectar clic en una imagen de un post
        const photoViewerImage = e.target.closest('.post-attachment-item img');
        if (photoViewerImage) {
            // Prevenir cualquier otra acción (como navegar al post, si el <a> es el padre)
            e.preventDefault(); 
            e.stopPropagation(); // Detener para que otros listeners no se activen
            openPhotoViewer(photoViewerImage);
            return; // Importante: Salir para no procesar otros clics
        }
        // --- ▲▲▲ FIN DE LÓGICA DEL VISOR DE FOTOS ▲▲▲ ---

        // --- (Listener de 'post-type-toggle' - CÓDIGO MUERTO) ---
        // Este código está aquí por si lo implementas en el futuro,
        // pero actualmente no hace nada porque tu PHP no tiene el toggle.
        const toggleButton = e.target.closest('#post-type-toggle .component-toggle-tab');
        let section = e.target.closest('[data-section*="create-"]');
        if (toggleButton && section) {
            e.preventDefault();
            if (toggleButton.classList.contains('active')) return;
            
            const newType = toggleButton.dataset.type;
            currentPostType = newType; 
            
            // --- ▼▼▼ INICIO DE CORRECCIÓN (querySelector) ▼▼▼ ---
            const postArea = document.querySelector('.post-content-area');
            const pollArea = document.querySelector('.poll-content-area');
            // --- ▲▲▲ FIN DE CORRECCIÓN ▲▲▲ ---
            const attachBtn = document.getElementById('attach-files-btn');
            const attachSpacer = document.getElementById('attach-files-spacer');
            const toggleContainer = document.getElementById('post-type-toggle');
    
            toggleContainer.querySelectorAll('.component-toggle-tab').forEach(btn => btn.classList.remove('active'));
            toggleButton.classList.add('active');
    
            if (newType === 'poll') {
                if (postArea) { postArea.classList.remove('active'); postArea.classList.add('disabled'); }
                if (pollArea) { pollArea.classList.add('active'); pollArea.classList.remove('disabled'); }
                if (attachBtn) attachBtn.style.display = 'none';
                if (attachSpacer) attachSpacer.style.display = 'block';
                history.pushState(null, '', window.projectBasePath + '/create-poll');
                
                const optionsContainer = document.getElementById('poll-options-container');
                if (optionsContainer && optionsContainer.children.length === 0) {
                    addPollOption(false);
                    addPollOption(false);
                }
            } else { 
                if (postArea) { postArea.classList.add('active'); postArea.classList.remove('disabled'); }
                if (pollArea) { pollArea.classList.remove('active'); pollArea.classList.add('disabled'); }
                if (attachBtn) attachBtn.style.display = 'flex';
                if (attachSpacer) attachSpacer.style.display = 'none';
                history.pushState(null, '', window.projectBasePath + '/create-publication');
            }
            validatePublicationState();
            return; 
        }

        // --- (Listeners de click para el formulario de publicación) ---
        section = e.target.closest('[data-section*="create-"]');
        if (!section) return; // Si el clic no fue en la sección de crear, ignorar el resto

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

        const menuLink = e.target.closest('[data-module="moduleCommunitySelect"] .menu-link[data-value]');
        if (menuLink) {
            e.preventDefault();
            const newId = menuLink.dataset.value; 
            const newTextKey = menuLink.dataset.textKey;
            const newIcon = menuLink.dataset.icon;
            selectedCommunityId = newId;
            const triggerText = document.getElementById('publication-community-text');
            const triggerIcon = document.getElementById('publication-community-icon');
            if (triggerText) {
                if (newTextKey.includes('.')) {
                    triggerText.textContent = getTranslation(newTextKey);
                    triggerText.setAttribute('data-i18n', newTextKey);
                } else {
                    triggerText.textContent = newTextKey; 
                    triggerText.removeAttribute('data-i18n');
                }
            }
            if (triggerIcon) {
                triggerIcon.textContent = newIcon; 
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
        
        const privacyTrigger = e.target.closest('#publication-privacy-trigger[data-action="toggleModulePrivacySelect"]');
        if (privacyTrigger) {
            e.preventDefault();
            e.stopPropagation();
            const module = document.querySelector('[data-module="modulePrivacySelect"]');
            if (module) {
                deactivateAllModules(module); 
                module.classList.toggle('disabled');
                module.classList.toggle('active');
            }
            return;
        }

        const privacyMenuLink = e.target.closest('[data-module="modulePrivacySelect"] .menu-link[data-value]');
        if (privacyMenuLink) {
            e.preventDefault();
            selectedPrivacyLevel = privacyMenuLink.dataset.value;
            const newTextKey = privacyMenuLink.dataset.textKey;
            const newIcon = privacyMenuLink.dataset.icon;
            const triggerText = document.getElementById('publication-privacy-text');
            const triggerIcon = document.getElementById('publication-privacy-icon');
            if (triggerText) {
                triggerText.textContent = getTranslation(newTextKey);
                triggerText.setAttribute('data-i18n', newTextKey);
            }
            if (triggerIcon) {
                triggerIcon.textContent = newIcon; 
            }
            const menuList = privacyMenuLink.closest('.menu-list');
            if (menuList) {
                menuList.querySelectorAll('.menu-link').forEach(link => link.classList.remove('active'));
                menuList.querySelectorAll('.menu-link-check-icon').forEach(icon => icon.innerHTML = '');
            }
            privacyMenuLink.classList.add('active');
            const checkIcon = privacyMenuLink.querySelector('.menu-link-check-icon');
            if (checkIcon) checkIcon.innerHTML = '<span class="material-symbols-rounded">check</span>';
            deactivateAllModules();
            return;
        }
        
        if (e.target.id === 'publish-post-btn' || e.target.closest('#publish-post-btn')) {
            e.preventDefault();
            handlePublishSubmit();
            return;
        }
        
        if (e.target.id === 'attach-files-btn' || e.target.closest('#attach-files-btn')) {
            e.preventDefault();
            document.getElementById('publication-file-input')?.click();
            return;
        }
        
        if (e.target.id === 'add-poll-option-btn' || e.target.closest('#add-poll-option-btn')) {
            e.preventDefault();
            addPollOption(true);
            return;
        }
        
        const removeOptionBtn = e.target.closest('[data-action="remove-poll-option"]');
        if (removeOptionBtn) {
            e.preventDefault();
            removePollOption(removeOptionBtn);
            return;
        }
    });
    
    document.body.addEventListener('input', (e) => {
        const createSection = e.target.closest('[data-section*="create-"]');
        if (createSection) {
            if (e.target.id === 'publication-title' || e.target.id === 'publication-text' || e.target.id === 'poll-question' || e.target.closest('#poll-options-container') || e.target.id === 'publication-hashtags' || e.target.id === 'poll-hashtags') {
                validatePublicationState();
            }
            return;
        }

        const profilePostInput = e.target.closest('form[data-action="profile-post-submit"] input[name="text_content"]');
        if (profilePostInput) {
            const form = profilePostInput.closest('form');
            const submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = profilePostInput.value.trim().length === 0;
            }
        }
    });
    
    document.body.addEventListener('submit', async (e) => {
        const createPostForm = e.target.closest('form#create-post-form');
        if (createPostForm) {
            e.preventDefault();
            handlePublishSubmit();
            return;
        }

        const profilePostForm = e.target.closest('form[data-action="profile-post-submit"]');
        if (profilePostForm) {
            e.preventDefault();
            await handleProfilePostSubmit(profilePostForm);
            return;
        }
    });
    
    document.body.addEventListener('change', (e) => {
        const section = e.target.closest('[data-section*="create-"]');
         if (e.target.id === 'publication-file-input' && section) {
            handleFileSelection(e);
        }
    });

    // --- ▼▼▼ INICIO DE NUEVA LÓGICA ▼▼▼ ---
    // Inicializar el visor de fotos (buscar elementos y añadir listeners)
    initPhotoViewer();
    // --- ▲▲▲ FIN DE NUEVA LÓGICA ▲▲▲ ---
}

// Esta función se llamará CADA VEZ que se cargue una página (desde url-manager.js)
// Inicializa el formulario de publicación SI EXISTE en la página actual.
export function initPublicationForm() {
    
    if (document.getElementById('create-post-form')) {
        
        resetForm();

        // --- ▼▼▼ INICIO DE CORRECCIÓN (querySelector) ▼▼▼ ---
        const pollAreaOnLoad = document.querySelector('.poll-content-area');
        
        if (pollAreaOnLoad) {
            
            if (pollAreaOnLoad.classList.contains('active')) {
                currentPostType = 'poll';
            } else {
                currentPostType = 'post';
            }
        } else {
            currentPostType = 'post';
        }
        
        // --- ▲▲▲ FIN DE CORRECCIÓN ▲▲▲ ---


        if (currentPostType === 'poll') {
            const optionsContainer = document.getElementById('poll-options-container');
            if (optionsContainer && optionsContainer.children.length === 0) {
                addPollOption(false);
                addPollOption(false);
            }
        }
        
        validatePublicationState();
        
    } else {
    }
}
// --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
import { callPublicationApi } from '../services/api-service.js';
import { getTranslation } from '../services/i18n-manager.js';
import { showAlert } from '../services/alert-manager.js';
import { deactivateAllModules } from '../app/main-controller.js';

const MAX_FILES = 4;
const MAX_POLL_OPTIONS = 6;
let selectedFiles = []; 
let selectedCommunityId = null; 
// --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
let selectedPrivacyLevel = 'public'; // Por defecto es público
// --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
let currentPostType = 'post'; 

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

function validatePublicationState() {
    const publishButton = document.getElementById('publish-post-btn');
    if (!publishButton) return;

    const hasCommunity = selectedCommunityId !== null;
    let isContentValid = false;

    if (currentPostType === 'post') {
        const textInput = document.getElementById('publication-text');
        const titleInput = document.getElementById('publication-title'); 
        const hasText = textInput ? textInput.value.trim().length > 0 : false;
        const hasTitle = titleInput ? titleInput.value.trim().length > 0 : false; 
        const hasFiles = selectedFiles.length > 0;
        isContentValid = hasText || hasFiles || hasTitle; 
    } else { 
        const questionInput = document.getElementById('poll-question');
        const options = document.querySelectorAll('#poll-options-container .component-input-group');
        const hasQuestion = questionInput ? questionInput.value.trim().length > 0 : false;
        const hasMinOptions = options.length >= 2;
        const allOptionsFilled = Array.from(options).every(opt => opt.querySelector('input').value.trim().length > 0);
        
        isContentValid = hasQuestion && hasMinOptions && allOptionsFilled;
    }
    
    // (Sin cambios aquí, la privacidad siempre tiene un valor por defecto)
    publishButton.disabled = !isContentValid || !hasCommunity;
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
    
    // --- ▼▼▼ INICIO DE MODIFICACIÓN (Reset de Privacidad) ▼▼▼ ---
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
    // --- ▲▲▲ FIN DE MODIFICACIÓN (Reset de Privacidad) ▲▲▲ ---
    
    validatePublicationState();
}

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
    formData.append('action', 'create-post'); 
    formData.append('community_id', communityId);
    formData.append('post_type', currentPostType);
    
    // --- ▼▼▼ INICIO DE MODIFICACIÓN (Añadir privacidad) ▼▼▼ ---
    formData.append('privacy_level', selectedPrivacyLevel);
    // --- ▲▲▲ FIN DE MODIFICACIÓN (Añadir privacidad) ▲▲▲ ---

    try {
        if (currentPostType === 'post') {
            const title = document.getElementById('publication-title').value.trim();
            const textContent = document.getElementById('publication-text').value.trim();
            if (!textContent && selectedFiles.length === 0 && !title) {
                throw new Error('js.publication.errorEmpty');
            }
            formData.append('title', title);
            formData.append('text_content', textContent);
            
            for (const file of selectedFiles) {
                formData.append('attachments[]', file, file.name);
            }
        } else { 
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

function resetCommunityTrigger() {
    // ... (función sin cambios)
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


export function initPublicationManager() {
    
    resetForm();
    currentPostType = document.querySelector('.component-toggle-tab.active')?.dataset.type || 'post';

    if (currentPostType === 'poll') {
        const optionsContainer = document.getElementById('poll-options-container');
        if (optionsContainer && optionsContainer.children.length === 0) {
            addPollOption(false);
            addPollOption(false);
        }
    }


    document.body.addEventListener('click', (e) => {
        // ... (código del listener de 'post-type-toggle' sin cambios) ...
        const toggleButton = e.target.closest('#post-type-toggle .component-toggle-tab');
        const section = e.target.closest('[data-section*="create-"]');
        if (!toggleButton || !section) {
            // (no es un toggle, continuar más abajo)
        } else {
            e.preventDefault();
            if (toggleButton.classList.contains('active')) return;
            
            const newType = toggleButton.dataset.type;
            currentPostType = newType; 
            
            const postArea = document.getElementById('post-content-area');
            const pollArea = document.getElementById('poll-content-area');
            const attachBtn = document.getElementById('attach-files-btn');
            const attachSpacer = document.getElementById('attach-files-spacer');
            const toggleContainer = document.getElementById('post-type-toggle');
    
            toggleContainer.querySelectorAll('.component-toggle-tab').forEach(btn => {
                btn.classList.remove('active');
            });
            toggleButton.classList.add('active');
    
            // ==========================================================
            // ============ INICIO DE LA CORRECCIÓN DEL ERROR ============
            // ==========================================================
            if (newType === 'poll') {
                if (postArea) { /* postArea.style.display = 'none'; */ postArea.classList.remove('active'); postArea.classList.add('disabled'); }
                if (pollArea) { /* pollArea.style.display = 'flex'; */ pollArea.classList.add('active'); pollArea.classList.remove('disabled'); }
                if (attachBtn) attachBtn.style.display = 'none';
                if (attachSpacer) attachSpacer.style.display = 'block';
                history.pushState(null, '', window.projectBasePath + '/create-poll');
                
                const optionsContainer = document.getElementById('poll-options-container');
                if (optionsContainer && optionsContainer.children.length === 0) {
                    addPollOption(false);
                    addPollOption(false);
                }
    
            } else { 
                if (postArea) { /* postArea.style.display = 'flex'; */ postArea.classList.add('active'); postArea.classList.remove('disabled'); }
                if (pollArea) { /* pollArea.style.display = 'none'; */ pollArea.classList.remove('active'); pollArea.classList.add('disabled'); }
                if (attachBtn) attachBtn.style.display = 'flex';
                if (attachSpacer) attachSpacer.style.display = 'none';
                history.pushState(null, '', window.projectBasePath + '/create-publication');
            }
            // ==========================================================
            // ============= FIN DE LA CORRECCIÓN DEL ERROR =============
            // ==========================================================
            
            validatePublicationState();
            return; // Importante salir después de manejar el toggle
        }


        // --- (Listener de 'input' sin cambios) ---
        // ...
    });
    
    document.body.addEventListener('input', (e) => {
        const section = e.target.closest('[data-section*="create-"]');
        if (!section) return;

        if (e.target.id === 'publication-title' || e.target.id === 'publication-text' || e.target.id === 'poll-question' || e.target.closest('#poll-options-container')) {
            validatePublicationState();
        }
    });
    
    document.body.addEventListener('click', (e) => {
        const section = e.target.closest('[data-section*="create-"]');
        if (!section) return; 

        // --- (Listener de 'moduleCommunitySelect' sin cambios) ---
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
        
        // --- ▼▼▼ INICIO DE NUEVO BLOQUE (Listener de 'modulePrivacySelect') ▼▼▼ ---
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
            // No es necesario llamar a validatePublicationState() aquí, ya que la privacidad siempre tiene un valor.
            return;
        }
        // --- ▲▲▲ FIN DE NUEVO BLOQUE (Listener de 'modulePrivacySelect') ▲▲▲ ---
        
        
        // --- (Listeners de botones de submit, attach, poll, etc. sin cambios) ---
        if (e.target.id === 'publish-post-btn') {
            e.preventDefault();
            handlePublishSubmit();
            return;
        }
        
        if (e.target.id === 'attach-files-btn') {
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
    
    // --- (Listener de 'change' para input de archivos sin cambios) ---
    document.body.addEventListener('change', (e) => {
        const section = e.target.closest('[data-section*="create-"]');
         if (e.target.id === 'publication-file-input' && section) {
            handleFileSelection(e);
        }
    });
    
    if (document.querySelector('[data-section*="create-"].active')) {
         validatePublicationState();
    }
}
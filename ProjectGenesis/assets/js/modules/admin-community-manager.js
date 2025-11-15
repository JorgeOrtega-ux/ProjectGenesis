import { callAdminApi } from '../services/api-service.js';
import { showAlert } from '../services/alert-manager.js';
import { getTranslation, applyTranslations } from '../services/i18n-manager.js';
import { hideTooltip } from '../services/tooltip-manager.js';
import { deactivateAllModules } from '../app/main-controller.js';

let selectedCommunityId = null;

function getCsrfTokenFromPage() {
    const csrfInput = document.querySelector('input[name="csrf_token"]');
    return csrfInput ? csrfInput.value : (window.csrfToken || '');
}

function showInlineError(cardElement, messageKey, data = null) {
    if (!cardElement) return;
    hideInlineError(cardElement);
    const errorDiv = document.createElement('div');
    errorDiv.className = 'component-card__error';
    let message = getTranslation(messageKey);
    if (data) {
        Object.keys(data).forEach(key => {
            message = message.replace(`%${key}%`, data[key]);
        });
    }
    errorDiv.textContent = message;
    errorDiv.classList.add('active');
    cardElement.parentNode.insertBefore(errorDiv, cardElement.nextSibling);
}

function hideInlineError(cardElement) {
    if (!cardElement) return;
    const nextElement = cardElement.nextElementSibling;
    if (nextElement && nextElement.classList.contains('component-card__error')) {
        nextElement.remove();
    }
}

function toggleButtonSpinner(button, textKey, isLoading) {
    if (!button) return;
    button.disabled = isLoading;
    if (isLoading) {
        button.dataset.originalText = button.innerHTML;
        const spinnerClass = 'logout-spinner';
        let spinnerStyle = 'width: 20px; height: 20px; border-width: 2px; margin: 0 auto; border-top-color: inherit;';
        
        if (button.classList.contains('component-button') && !button.classList.contains('danger')) {
            spinnerStyle += ' border-top-color: #ffffff; border-left-color: #ffffff20; border-bottom-color: #ffffff20; border-right-color: #ffffff20;';
        } else if (button.classList.contains('danger')) {
             spinnerStyle += ' border-top-color: #ffffff; border-left-color: #ffffff20; border-bottom-color: #ffffff20; border-right-color: #ffffff20;';
        }

        button.innerHTML = `<span class="${spinnerClass}" style="${spinnerStyle}"></span>`;
    } else {
        button.innerHTML = button.dataset.originalText || getTranslation(textKey);
    }
}


function enableCommunitySelectionActions() {
    const toolbarContainer = document.querySelector('#community-list-container')?.closest('.section-content')?.querySelector('.page-toolbar-container');
    if (!toolbarContainer) return;
    toolbarContainer.classList.add('selection-active');
    const selectionButtons = toolbarContainer.querySelectorAll('.toolbar-action-selection button');
    selectionButtons.forEach(btn => {
        btn.disabled = false;
    });
}

function disableCommunitySelectionActions() {
    const toolbarContainer = document.querySelector('#community-list-container')?.closest('.section-content')?.querySelector('.page-toolbar-container');
    if (!toolbarContainer) return;
    toolbarContainer.classList.remove('selection-active');
    const selectionButtons = toolbarContainer.querySelectorAll('.toolbar-action-selection button');
    selectionButtons.forEach(btn => {
        btn.disabled = true;
    });
}

function clearCommunitySelection() {
    const selectedCard = document.querySelector('.card-item.selected[data-community-id]');
    if (selectedCard) {
        selectedCard.classList.remove('selected');
    }
    disableCommunitySelectionActions();
    selectedCommunityId = null;
}


async function handleImageUpload(cardElement, fileInput, imageType, communityId, previewImg, actions) {
    const file = fileInput.files[0];
    const saveTrigger = cardElement.querySelector(actions.save);
    hideInlineError(cardElement);

    if (!file) {
        showInlineError(cardElement, 'js.settings.errorAvatarSelect');
        return;
    }

    toggleButtonSpinner(saveTrigger, 'settings.profile.save', true);

    const formData = new FormData();
    formData.append('action', 'admin-upload-community-image');
    formData.append('community_id', communityId);
    formData.append('image_type', imageType);
    formData.append('image', file);
    formData.append('csrf_token', getCsrfTokenFromPage());

    const result = await callAdminApi(formData);

    if (result.success) {
        showAlert(getTranslation(result.message || 'admin.communities.success.imageUploaded'), 'success');
        previewImg.src = result.newImageUrl;
        previewImg.dataset.originalSrc = result.newImageUrl;
        fileInput.value = '';

        cardElement.querySelector(actions.preview).classList.remove('active');
        cardElement.querySelector(actions.preview).classList.add('disabled');
        cardElement.querySelector(actions.default).classList.remove('active');
        cardElement.querySelector(actions.default).classList.add('disabled');
        cardElement.querySelector(actions.custom).classList.add('active');
        cardElement.querySelector(actions.custom).classList.remove('disabled');
        cardElement.dataset.originalActions = 'custom';
    } else {
        showInlineError(cardElement, result.message || 'js.settings.errorSaveUnknown');
    }
    toggleButtonSpinner(saveTrigger, 'settings.profile.save', false);
}

async function handleImageRemove(cardElement, imageType, communityId, previewImg, actions) {
    const removeTrigger = cardElement.querySelector(actions.remove);
    hideInlineError(cardElement);

    toggleButtonSpinner(removeTrigger, 'settings.profile.removePhoto', true);

    const formData = new FormData();
    formData.append('action', 'admin-remove-community-image');
    formData.append('community_id', communityId);
    formData.append('image_type', imageType);
    formData.append('csrf_token', getCsrfTokenFromPage());

    const result = await callAdminApi(formData);

    if (result.success) {
        showAlert(getTranslation(result.message || 'admin.communities.success.imageRemoved'), 'success');
        
        const newSrc = result.newImageUrl || ( (imageType === 'icon') ? 'https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff' : (window.projectBasePath + '/assets/images/default_banner.png') );
        
        previewImg.src = newSrc;
        previewImg.dataset.originalSrc = newSrc;

        cardElement.querySelector(actions.preview).classList.remove('active');
        cardElement.querySelector(actions.preview).classList.add('disabled');
        cardElement.querySelector(actions.custom).classList.remove('active');
        cardElement.querySelector(actions.custom).classList.add('disabled');
        cardElement.querySelector(actions.default).classList.add('active');
        cardElement.querySelector(actions.default).classList.remove('disabled');
        cardElement.dataset.originalActions = 'default';
    } else {
        showInlineError(cardElement, result.message || 'js.settings.errorAvatarRemove');
    }
    toggleButtonSpinner(removeTrigger, 'settings.profile.removePhoto', false);
}

function handleImageFileChange(cardElement, fileInput, previewImg, actions) {
    hideInlineError(cardElement);
    const file = fileInput.files[0];
    if (!file) return;

    if (!['image/png', 'image/jpeg', 'image/gif', 'image/webp'].includes(file.type)) {
        showInlineError(cardElement, 'js.settings.errorAvatarFormat');
        fileInput.value = '';
        return;
    }
    
    const maxSizeInMB = window.avatarMaxSizeMB || 2;
    if (file.size > maxSizeInMB * 1024 * 1024) {
        showInlineError(cardElement, 'js.settings.errorAvatarSize', { size: maxSizeInMB });
        fileInput.value = '';
        return;
    }

    if (!previewImg.dataset.originalSrc) {
        previewImg.dataset.originalSrc = previewImg.src;
    }
    const reader = new FileReader();
    reader.onload = (event) => { previewImg.src = event.target.result; };
    reader.readAsDataURL(file);

    const actionsDefault = cardElement.querySelector(actions.default);
    cardElement.dataset.originalActions = (actionsDefault.classList.contains('active')) ? 'default' : 'custom';

    cardElement.querySelector(actions.default).classList.remove('active');
    cardElement.querySelector(actions.default).classList.add('disabled');
    cardElement.querySelector(actions.custom).classList.remove('active');
    cardElement.querySelector(actions.custom).classList.add('disabled');
    cardElement.querySelector(actions.preview).classList.add('active');
    cardElement.querySelector(actions.preview).classList.remove('disabled');
}

function handleImageCancel(cardElement, fileInput, previewImg, actions) {
    hideInlineError(cardElement);
    const originalAvatarSrc = previewImg.dataset.originalSrc;
    if (previewImg && originalAvatarSrc) previewImg.src = originalAvatarSrc;
    fileInput.value = '';

    cardElement.querySelector(actions.preview).classList.remove('active');
    cardElement.querySelector(actions.preview).classList.add('disabled');
    
    const originalState = cardElement.dataset.originalActions === 'default' ? actions.default : actions.custom;
    
    cardElement.querySelector(originalState).classList.add('active');
    cardElement.querySelector(originalState).classList.remove('disabled');
}


export function initAdminCommunityManager() {
    
    document.body.addEventListener('click', async (e) => {
        const listPage = e.target.closest('[data-section="admin-communities"]');
        if (!listPage) return; 

        const communityCard = e.target.closest('.card-item[data-community-id]');
        if (communityCard) {
            e.preventDefault();
            const communityId = communityCard.dataset.communityId;
            if (selectedCommunityId === communityId) {
                clearCommunitySelection();
            } else {
                const oldSelected = document.querySelector('.card-item.selected[data-community-id]');
                if (oldSelected) {
                    oldSelected.classList.remove('selected');
                }
                communityCard.classList.add('selected');
                selectedCommunityId = communityId;
                enableCommunitySelectionActions();
            }
            return;
        }

        const button = e.target.closest('[data-action]');
        if (!button) {
            if (!e.target.closest('[data-module].active')) {
                clearCommunitySelection();
            }
            return;
        }

        const action = button.getAttribute('data-action');
        
        if (action === 'admin-community-clear-selection') {
            e.preventDefault();
            clearCommunitySelection();
            return;
        }

        if (action === 'admin-community-edit-selected') {
            e.preventDefault();
            if (!selectedCommunityId) {
                showAlert(getTranslation('js.admin.errorNoSelection'), 'error'); 
                return;
            }
            
            const linkUrl = window.projectBasePath + '/admin/edit-community?id=' + selectedCommunityId;
            const link = document.createElement('a');
            link.href = linkUrl;
            link.setAttribute('data-nav-js', 'true'); 
            document.body.appendChild(link);
            link.click();
            link.remove();
            
            clearCommunitySelection(); 
            deactivateAllModules(); 
            return;
        }
    });

    document.body.addEventListener('click', async (e) => {
        const editPage = e.target.closest('[data-section="admin-edit-community"]');
        if (!editPage) return; 

        const button = e.target.closest('[data-action]');
        const target = e.target;
        const targetCommunityId = document.getElementById('admin-edit-target-community-id').value;
        const isCreating = document.getElementById('admin-edit-is-creating').value === '1';
        
        const stepperButton = e.target.closest('.component-stepper button[data-step-action]');
        if (stepperButton) {
            e.preventDefault();
            const wrapper = stepperButton.closest('.component-stepper');
            if (!wrapper || wrapper.classList.contains('disabled-interactive')) return;
            
            const stepAction = stepperButton.dataset.stepAction;
            const valueDisplay = wrapper.querySelector('.stepper-value');
            const min = parseInt(wrapper.dataset.min, 10);
            const max = parseInt(wrapper.dataset.max, 10);
            
            const step1 = parseInt(wrapper.dataset.step1 || '1', 10);
            const step10 = parseInt(wrapper.dataset.step10 || '10');

            let currentValue = parseInt(wrapper.dataset.currentValue, 10);
            let newValue = currentValue;
            let stepAmount = 0;

            switch (stepAction) {
                case 'increment-1': stepAmount = step1; break;
                case 'increment-10': stepAmount = step10; break;
                case 'decrement-1': stepAmount = -step1; break;
                case 'decrement-10': stepAmount = -step10; break;
            }
            
            newValue = currentValue + stepAmount;
            
            if (!isNaN(min) && newValue < min) newValue = min;
            if (!isNaN(max) && newValue > max) newValue = max;
            
            if (newValue === currentValue) return;

            if (valueDisplay) valueDisplay.textContent = newValue;
            wrapper.dataset.currentValue = newValue;

            wrapper.querySelector('[data-step-action="decrement-10"]').disabled = newValue < min + step10;
            wrapper.querySelector('[data-step-action="decrement-1"]').disabled = newValue <= min;
            wrapper.querySelector('[data-step-action="increment-1"]').disabled = newValue >= max;
            wrapper.querySelector('[data-step-action="increment-10"]').disabled = newValue > max - step10;
            
            return;
        }


        const iconCard = document.getElementById('admin-community-icon-section');
        const iconInput = document.getElementById('admin-icon-upload-input');
        const iconPreview = document.getElementById('admin-icon-preview-image');
        const iconActions = {
            default: '#admin-icon-actions-default',
            custom: '#admin-icon-actions-custom',
            preview: '#admin-icon-actions-preview',
            remove: '#admin-icon-remove-trigger',
            save: '#admin-icon-save-trigger-btn'
        };

        if (target.closest('#admin-icon-preview-container') || target.closest('#admin-icon-upload-trigger') || target.closest('#admin-icon-change-trigger')) {
            e.preventDefault();
            iconInput?.click();
            return;
        }
        if (target.closest('#admin-icon-cancel-trigger')) {
            e.preventDefault();
            handleImageCancel(iconCard, iconInput, iconPreview, iconActions);
            return;
        }
        if (target.closest('#admin-icon-remove-trigger')) {
            e.preventDefault();
            if(isCreating) return;
            await handleImageRemove(iconCard, 'icon', targetCommunityId, iconPreview, iconActions);
            return;
        }
        if (target.closest('#admin-icon-save-trigger-btn')) {
            e.preventDefault();
            if(isCreating) return;
            await handleImageUpload(iconCard, iconInput, 'icon', targetCommunityId, iconPreview, iconActions);
            return;
        }
        
        const bannerCard = document.getElementById('admin-community-banner-section');
        const bannerInput = document.getElementById('admin-banner-upload-input');
        const bannerPreview = document.getElementById('admin-banner-preview-image');
        const bannerActions = {
            default: '#admin-banner-actions-default',
            custom: '#admin-banner-actions-custom',
            preview: '#admin-banner-actions-preview',
            remove: '#admin-banner-remove-trigger',
            save: '#admin-banner-save-trigger-btn'
        };

        if (target.closest('#admin-banner-preview-container') || target.closest('#admin-banner-upload-trigger') || target.closest('#admin-banner-change-trigger')) {
            e.preventDefault();
            bannerInput?.click();
            return;
        }
        if (target.closest('#admin-banner-cancel-trigger')) {
            e.preventDefault();
            handleImageCancel(bannerCard, bannerInput, bannerPreview, bannerActions);
            return;
        }
        if (target.closest('#admin-banner-remove-trigger')) {
            e.preventDefault();
            if(isCreating) return;
            await handleImageRemove(bannerCard, 'banner', targetCommunityId, bannerPreview, bannerActions);
            return;
        }
        if (target.closest('#admin-banner-save-trigger-btn')) {
            e.preventDefault();
            if(isCreating) return;
            await handleImageUpload(bannerCard, bannerInput, 'banner', targetCommunityId, bannerPreview, bannerActions);
            return;
        }
        
        if (target.closest('#admin-community-details-save-btn')) {
            e.preventDefault();
            const saveBtn = target.closest('#admin-community-details-save-btn');
            const card = saveBtn.closest('.component-card');
            const nameInput = document.getElementById('admin-community-name');
            const communityType = document.querySelector('[data-module="moduleAdminCommunityType"] .menu-link.active')?.dataset.value || 'municipio';
            
            const maxMembersStepper = document.getElementById('admin-community-max-members');
            const maxMembers = maxMembersStepper ? maxMembersStepper.dataset.currentValue : '0';

            hideInlineError(card);

            if (!nameInput.value.trim()) {
                showInlineError(card, 'admin.communities.error.nameRequired'); 
                return;
            }

            toggleButtonSpinner(saveBtn, 'settings.profile.save', true);

            const formData = new FormData();
            if (isCreating) {
                formData.append('action', 'admin-create-community');
                formData.append('name', nameInput.value);
                formData.append('community_type', communityType);
                
                formData.append('max_members', maxMembers);
                
                const privacy = document.querySelector('[data-module="moduleAdminCommunityPrivacy"] .menu-link.active')?.dataset.value || 'public';
                const accessCode = document.getElementById('admin-community-code').value;
                formData.append('privacy', privacy);
                formData.append('access_code', accessCode);
            } else {
                formData.append('action', 'admin-update-community-details');
                formData.append('community_id', targetCommunityId);
                formData.append('name', nameInput.value);
                formData.append('community_type', communityType);
                
                formData.append('max_members', maxMembers);
            }
            formData.append('csrf_token', getCsrfTokenFromPage());

            const result = await callAdminApi(formData);

            if (result.success) {
                showAlert(getTranslation(result.message), 'success');
                if (isCreating && result.new_community_id) {
                    const linkUrl = window.projectBasePath + '/admin/edit-community?id=' + result.new_community_id;
                    const link = document.createElement('a');
                    link.href = linkUrl;
                    link.setAttribute('data-nav-js', 'true'); 
                    document.body.appendChild(link);
                    link.click();
                    link.remove();
                } else {
                    document.querySelector('.component-page-description strong').textContent = result.newName;
                    toggleButtonSpinner(saveBtn, 'settings.profile.save', false);
                }
            } else {
                showInlineError(card, result.message || 'js.settings.errorSaveUnknown');
                toggleButtonSpinner(saveBtn, 'settings.profile.save', false);
            }
            return;
        }

        if (target.closest('#admin-community-privacy-save-btn')) {
            e.preventDefault();
            if (isCreating) {
                showAlert(getTranslation('admin.communities.error.saveDetailsFirst'), 'info'); 
                return;
            }

            const saveBtn = target.closest('#admin-community-privacy-save-btn');
            const card = saveBtn.closest('.component-card');
            const privacy = document.querySelector('[data-module="moduleAdminCommunityPrivacy"] .menu-link.active')?.dataset.value || 'public';
            const accessCode = document.getElementById('admin-community-code').value;
            
            hideInlineError(card);
            toggleButtonSpinner(saveBtn, 'settings.profile.save', true);

            const formData = new FormData();
            formData.append('action', 'admin-update-community-privacy');
            formData.append('community_id', targetCommunityId);
            formData.append('privacy', privacy);
            formData.append('access_code', accessCode);
            formData.append('csrf_token', getCsrfTokenFromPage());

            const result = await callAdminApi(formData);
            
            if (result.success) {
                showAlert(getTranslation(result.message), 'success');
                if (result.newAccessCode) {
                    document.getElementById('admin-community-code').value = result.newAccessCode;
                }
            } else {
                showInlineError(card, result.message || 'js.settings.errorSaveUnknown');
            }
            toggleButtonSpinner(saveBtn, 'settings.profile.save', false);
            return;
        }
        
        if (target.closest('[data-action="toggleModuleAdminCommunityType"]')) {
            e.preventDefault();
            e.stopPropagation();
            const module = document.querySelector('[data-module="moduleAdminCommunityType"]');
            if (module) {
                deactivateAllModules(module);
                module.classList.toggle('disabled');
                module.classList.toggle('active');
            }
            return;
        }
        
        const typeLink = target.closest('[data-module="moduleAdminCommunityType"] .menu-link');
        if (typeLink) {
            e.preventDefault();
            const newType = typeLink.dataset.value;
            const newIcon = typeLink.dataset.icon;
            const newTextKey = typeLink.dataset.textKey;

            const trigger = document.querySelector('[data-action="toggleModuleAdminCommunityType"]');
            trigger.querySelector('.trigger-select-icon span').textContent = newIcon;
            trigger.querySelector('.trigger-select-text span').textContent = getTranslation(newTextKey);
            trigger.querySelector('.trigger-select-text span').dataset.i18n = newTextKey;

            typeLink.closest('.menu-list').querySelectorAll('.menu-link').forEach(link => {
                link.classList.remove('active');
                link.querySelector('.menu-link-check-icon').innerHTML = '';
            });
            typeLink.classList.add('active');
            typeLink.querySelector('.menu-link-check-icon').innerHTML = '<span class="material-symbols-rounded">check</span>';
            
            deactivateAllModules();
            return;
        }
        
        if (target.closest('[data-action="toggleModuleAdminCommunityPrivacy"]')) {
            e.preventDefault();
            e.stopPropagation();
            const module = document.querySelector('[data-module="moduleAdminCommunityPrivacy"]');
            if (module) {
                deactivateAllModules(module);
                module.classList.toggle('disabled');
                module.classList.toggle('active');
            }
            return;
        }

        const privacyLink = target.closest('[data-module="moduleAdminCommunityPrivacy"] .menu-link');
        if (privacyLink) {
            e.preventDefault();
            const newPrivacy = privacyLink.dataset.value;
            const newIcon = privacyLink.dataset.icon;
            const newTextKey = privacyLink.dataset.textKey;

            const trigger = document.querySelector('[data-action="toggleModuleAdminCommunityPrivacy"]');
            trigger.querySelector('.trigger-select-icon span').textContent = newIcon;
            trigger.querySelector('.trigger-select-text span').textContent = getTranslation(newTextKey);
            trigger.querySelector('.trigger-select-text span').dataset.i18n = newTextKey;

            privacyLink.closest('.menu-list').querySelectorAll('.menu-link').forEach(link => {
                link.classList.remove('active');
                link.querySelector('.menu-link-check-icon').innerHTML = '';
            });
            privacyLink.classList.add('active');
            privacyLink.querySelector('.menu-link-check-icon').innerHTML = '<span class="material-symbols-rounded">check</span>';
            
            const codeGroup = document.getElementById('admin-access-code-group');
            if (newPrivacy === 'private') {
                codeGroup.style.display = 'block';
            } else {
                codeGroup.style.display = 'none';
            }

            deactivateAllModules();
            return;
        }
        
        if (target.closest('[data-action="admin-generate-code"]')) {
            e.preventDefault();
            const genBtn = target.closest('[data-action="admin-generate-code"]');
            toggleButtonSpinner(genBtn, null, true); 

            const formData = new FormData();
            formData.append('action', 'admin-generate-access-code');
            formData.append('csrf_token', getCsrfTokenFromPage());
            
            const result = await callAdminApi(formData);
            if (result.success && result.newAccessCode) {
                document.getElementById('admin-community-code').value = result.newAccessCode;
            } else {
                showAlert(getTranslation(result.message || 'js.api.errorServer'), 'error');
            }
            
            toggleButtonSpinner(genBtn, null, false);
            genBtn.innerHTML = '<span class="material-symbols-rounded">auto_fix_high</span>';
            return;
        }
        
        if (target.closest('#admin-community-delete-btn')) {
            e.preventDefault();
            showAlert('La eliminación de comunidades aún no está implementada.', 'error'); 
            return;
        }

    });
    
    document.body.addEventListener('change', (e) => {
        const editPage = e.target.closest('[data-section="admin-edit-community"]');
        if (!editPage) return;
        
        const target = e.target;
        
        if (target.id === 'admin-icon-upload-input') {
            const iconCard = document.getElementById('admin-community-icon-section');
            const iconInput = document.getElementById('admin-icon-upload-input');
            const iconPreview = document.getElementById('admin-icon-preview-image');
            const iconActions = {
                default: '#admin-icon-actions-default',
                custom: '#admin-icon-actions-custom',
                preview: '#admin-icon-actions-preview'
            };
            handleImageFileChange(iconCard, iconInput, iconPreview, iconActions);
        }
        
        if (target.id === 'admin-banner-upload-input') {
            const bannerCard = document.getElementById('admin-community-banner-section');
            const bannerInput = document.getElementById('admin-banner-upload-input');
            const bannerPreview = document.getElementById('admin-banner-preview-image');
            const bannerActions = {
                default: '#admin-banner-actions-default',
                custom: '#admin-banner-actions-custom',
                preview: '#admin-banner-actions-preview'
            };
            handleImageFileChange(bannerCard, bannerInput, bannerPreview, bannerActions);
        }
    });
    
    document.body.addEventListener('input', (e) => {
        const editPage = e.target.closest('[data-section="admin-edit-community"]');
        if (!editPage) return;
        
        if (e.target.matches('.component-input') || e.target.matches('.component-text-input')) {
            const card = e.target.closest('.component-card');
            if (card) {
                hideInlineError(card);
            }
        }
        
        if (e.target.id === 'admin-community-code') {
            let input = e.target.value.replace(/[^0-9a-zA-Z]/g, '');
            input = input.toUpperCase();
            input = input.substring(0, 12);

            let formatted = '';
            for (let i = 0; i < input.length; i++) {
                if (i > 0 && i % 4 === 0) {
                    formatted += '-';
                }
                formatted += input[i];
            }
            e.target.value = formatted;
        }
    });

    setTimeout(() => {
        const editPage = document.querySelector('[data-section="admin-edit-community"]');
        if (!editPage) return;
        
        const iconPreview = document.getElementById('admin-icon-preview-image');
        if (iconPreview && !iconPreview.dataset.originalSrc) {
            iconPreview.dataset.originalSrc = iconPreview.src;
        }
        const bannerPreview = document.getElementById('admin-banner-preview-image');
        if (bannerPreview && !bannerPreview.dataset.originalSrc) {
            bannerPreview.style.backgroundImage = bannerPreview.style.backgroundImage || 'none';
            bannerPreview.dataset.originalBg = bannerPreview.style.backgroundImage;
        }
    }, 100);

}
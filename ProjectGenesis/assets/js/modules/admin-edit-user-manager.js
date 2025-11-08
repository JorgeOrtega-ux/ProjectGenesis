import { callAdminApi }  from '../services/api-service.js';
import { getTranslation } from '../services/i18n-manager.js';

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

function toggleButtonSpinner(button, text, isLoading) {
    if (!button) return;
    button.disabled = isLoading;
    if (isLoading) {
        button.dataset.originalText = button.textContent;
        const spinnerClass = 'logout-spinner'; 
        let spinnerStyle = 'width: 20px; height: 20px; border-width: 2px; margin: 0 auto; border-top-color: inherit;';
        
        if (button.classList.contains('modal__button-small--primary') || 
            button.classList.contains('modal__button-small--danger') || 
            (button.classList.contains('component-button') && !button.classList.contains('danger')) ||
            button.id === 'admin-password-update-save') { 
            
            if(button.classList.contains('component-button') && button.classList.contains('danger')) {
            } else {
                 spinnerStyle += ' border-top-color: #ffffff; border-left-color: #ffffff20; border-bottom-color: #ffffff20; border-right-color: #ffffff20;';
            }
        }
        button.innerHTML = `<span class="${spinnerClass}" style="${spinnerStyle}"></span>`;
    } else {
        button.innerHTML = button.dataset.originalText || text;
    }
}

function focusInputAndMoveCursorToEnd(inputElement) {
    if (!inputElement) return;
    const length = inputElement.value.length;
    const originalType = inputElement.type;
    try {
        if (inputElement.type === 'email' || inputElement.type === 'text' || inputElement.type === 'password') { 
             inputElement.type = 'text';
        }
        inputElement.focus();
        setTimeout(() => {
            try {
                inputElement.setSelectionRange(length, length);
            } catch (e) {}
            inputElement.type = originalType;
        }, 0);
    } catch (e) {
        inputElement.type = originalType;
    }
}

function getCsrfTokenFromPage() {
    const csrfInput = document.querySelector('input[name="csrf_token"]');
    return csrfInput ? csrfInput.value : (window.csrfToken || ''); 
}



export function initAdminEditUserManager() {


    document.body.addEventListener('click', async (e) => {
        
        const targetUserIdInput = document.getElementById('admin-edit-target-user-id');
        if (!targetUserIdInput) {
            return;
        }
        const targetUserId = targetUserIdInput.value;
        const target = e.target;
        
        const avatarCard = document.getElementById('admin-avatar-section');
        if (avatarCard) {
             if (target.closest('#admin-avatar-preview-container') || target.closest('#admin-avatar-upload-trigger') || target.closest('#admin-avatar-change-trigger')) {
                e.preventDefault();
                hideInlineError(avatarCard); 
                document.getElementById('admin-avatar-upload-input')?.click();
                return;
            }

            if (target.closest('#admin-avatar-cancel-trigger')) {
                e.preventDefault();
                hideInlineError(avatarCard); 
                const previewImage = document.getElementById('admin-avatar-preview-image');
                const originalAvatarSrc = previewImage.dataset.originalSrc;
                if (previewImage && originalAvatarSrc) previewImage.src = originalAvatarSrc;
                document.getElementById('admin-avatar-upload-input').value = ''; 

                document.getElementById('admin-avatar-actions-preview').classList.remove('active');
                document.getElementById('admin-avatar-actions-preview').classList.add('disabled');
                
                const originalState = avatarCard.dataset.originalActions === 'default'
                    ? 'admin-avatar-actions-default'
                    : 'admin-avatar-actions-custom';
                
                document.getElementById(originalState).classList.add('active');
                document.getElementById(originalState).classList.remove('disabled');
                return;
            }

            if (target.closest('#admin-avatar-remove-trigger')) {
                e.preventDefault();
                hideInlineError(avatarCard); 
                const removeTrigger = target.closest('#admin-avatar-remove-trigger');
                toggleButtonSpinner(removeTrigger, getTranslation('settings.profile.removePhoto'), true);

                const formData = new FormData();
                formData.append('action', 'admin-remove-avatar');
                formData.append('target_user_id', targetUserId); 
                formData.append('csrf_token', getCsrfTokenFromPage()); 

                const result = await callAdminApi(formData); 

                if (result.success) {
                    window.showAlert(getTranslation(result.message || 'js.settings.successAvatarRemoved'), 'success');
                    
                    const previewImage = document.getElementById('admin-avatar-preview-image');
                    previewImage.src = result.newAvatarUrl; 
                    previewImage.dataset.originalSrc = result.newAvatarUrl;

                    document.getElementById('admin-avatar-actions-preview').classList.remove('active');
                    document.getElementById('admin-avatar-actions-preview').classList.add('disabled');
                    document.getElementById('admin-avatar-actions-custom').classList.remove('active');
                    document.getElementById('admin-avatar-actions-custom').classList.add('disabled');
                    document.getElementById('admin-avatar-actions-default').classList.add('active');
                    document.getElementById('admin-avatar-actions-default').classList.remove('disabled');
                    
                    avatarCard.dataset.originalActions = 'default'; 
                    
                    toggleButtonSpinner(removeTrigger, getTranslation('settings.profile.removePhoto'), false);
                } else {
                    showInlineError(avatarCard, result.message || 'js.settings.errorAvatarRemove');
                    toggleButtonSpinner(removeTrigger, getTranslation('settings.profile.removePhoto'), false);
                }
                return;
            }

            if (target.closest('#admin-avatar-save-trigger-btn')) {
                 e.preventDefault();
                const fileInput = document.getElementById('admin-avatar-upload-input');
                const saveTrigger = target.closest('#admin-avatar-save-trigger-btn');
                hideInlineError(avatarCard);
                if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                    showInlineError(avatarCard, 'js.settings.errorAvatarSelect');
                    return;
                }
                toggleButtonSpinner(saveTrigger, getTranslation('settings.profile.save'), true);

                const formData = new FormData();
                formData.append('action', 'admin-upload-avatar');
                formData.append('target_user_id', targetUserId); 
                formData.append('avatar', fileInput.files[0]);
                formData.append('csrf_token', getCsrfTokenFromPage()); 

                const result = await callAdminApi(formData); 

                if (result.success) {
                    window.showAlert(getTranslation(result.message || 'js.settings.successAvatarUpdate'), 'success');

                    const previewImage = document.getElementById('admin-avatar-preview-image');
                    previewImage.src = result.newAvatarUrl; 
                    previewImage.dataset.originalSrc = result.newAvatarUrl; 
                    
                    document.getElementById('admin-avatar-upload-input').value = ''; 

                    document.getElementById('admin-avatar-actions-preview').classList.remove('active');
                    document.getElementById('admin-avatar-actions-preview').classList.add('disabled');
                    document.getElementById('admin-avatar-actions-default').classList.remove('active');
                    document.getElementById('admin-avatar-actions-default').classList.add('disabled');
                    document.getElementById('admin-avatar-actions-custom').classList.add('active');
                    document.getElementById('admin-avatar-actions-custom').classList.remove('disabled');
                    avatarCard.dataset.originalActions = 'custom'; 

                    toggleButtonSpinner(saveTrigger, getTranslation('settings.profile.save'), false);
                } else {
                    showInlineError(avatarCard, result.message || 'js.settings.errorSaveUnknown');
                    toggleButtonSpinner(saveTrigger, getTranslation('settings.profile.save'), false);
                }
                return;
            }
        }

        const usernameCard = document.getElementById('admin-username-section');
        if (usernameCard) {
            hideInlineError(usernameCard);
            if (target.closest('#admin-username-edit-trigger')) {
                e.preventDefault();
                document.getElementById('admin-username-view-state').classList.remove('active');
                document.getElementById('admin-username-view-state').classList.add('disabled');
                document.getElementById('admin-username-actions-view').classList.remove('active');
                document.getElementById('admin-username-actions-view').classList.add('disabled');
                document.getElementById('admin-username-edit-state').classList.add('active');
                document.getElementById('admin-username-edit-state').classList.remove('disabled');
                document.getElementById('admin-username-actions-edit').classList.add('active');
                document.getElementById('admin-username-actions-edit').classList.remove('disabled');
                focusInputAndMoveCursorToEnd(document.getElementById('admin-username-input'));
                return;
            }
            if (target.closest('#admin-username-cancel-trigger')) {
                e.preventDefault();
                const displayElement = document.getElementById('admin-username-display-text');
                const inputElement = document.getElementById('admin-username-input');
                if (displayElement && inputElement) inputElement.value = displayElement.dataset.originalUsername;
                document.getElementById('admin-username-edit-state').classList.remove('active');
                document.getElementById('admin-username-edit-state').classList.add('disabled');
                document.getElementById('admin-username-actions-edit').classList.remove('active');
                document.getElementById('admin-username-actions-edit').classList.add('disabled');
                document.getElementById('admin-username-view-state').classList.add('active');
                document.getElementById('admin-username-view-state').classList.remove('disabled');
                document.getElementById('admin-username-actions-view').classList.add('active');
                document.getElementById('admin-username-actions-view').classList.remove('disabled');
                return;
            }
             if (target.closest('#admin-username-save-trigger-btn')) {
                e.preventDefault();
                const saveTrigger = target.closest('#admin-username-save-trigger-btn');
                const inputElement = document.getElementById('admin-username-input');
                const minUserLength = window.minUsernameLength || 6;
                const maxUserLength = window.maxUsernameLength || 32;
                if (inputElement.value.length < minUserLength || inputElement.value.length > maxUserLength) {
                    showInlineError(usernameCard, 'js.auth.errorUsernameLength', { min: minUserLength, max: maxUserLength });
                    return;
                }
                toggleButtonSpinner(saveTrigger, getTranslation('settings.profile.save'), true);
                const formData = new FormData();
                formData.append('action', 'admin-update-username');
                formData.append('target_user_id', targetUserId); 
                formData.append('username', inputElement.value);
                formData.append('csrf_token', getCsrfTokenFromPage()); 

                const result = await callAdminApi(formData); 

                if (result.success) {
                    window.showAlert(getTranslation(result.message || 'js.settings.successUsernameUpdate'), 'success');
                    
                    const newUsername = result.newUsername; 
                    const displayElement = document.getElementById('admin-username-display-text');
                    const inputElement = document.getElementById('admin-username-input');
                    
                    displayElement.textContent = newUsername;
                    displayElement.dataset.originalUsername = newUsername;
                    inputElement.value = newUsername;

                    document.getElementById('admin-username-edit-state').classList.remove('active');
                    document.getElementById('admin-username-edit-state').classList.add('disabled');
                    document.getElementById('admin-username-actions-edit').classList.remove('active');
                    document.getElementById('admin-username-actions-edit').classList.add('disabled');
                    document.getElementById('admin-username-view-state').classList.add('active');
                    document.getElementById('admin-username-view-state').classList.remove('disabled');
                    document.getElementById('admin-username-actions-view').classList.add('active');
                    document.getElementById('admin-username-actions-view').classList.remove('disabled');

                    if (result.newAvatarUrl) {
                        const previewImage = document.getElementById('admin-avatar-preview-image');
                        previewImage.src = result.newAvatarUrl;
                        previewImage.dataset.originalSrc = result.newAvatarUrl;
                    }

                    toggleButtonSpinner(saveTrigger, getTranslation('settings.profile.save'), false);
                } else {
                    showInlineError(usernameCard, result.message || 'js.settings.errorSaveUnknown', result.data);
                    toggleButtonSpinner(saveTrigger, getTranslation('settings.profile.save'), false);
                }
                 return;
            }
        }

        const emailCard = document.getElementById('admin-email-section');
        if (emailCard) {
            hideInlineError(emailCard);
            if (target.closest('#admin-email-edit-trigger')) {
                e.preventDefault();
                document.getElementById('admin-email-view-state').classList.remove('active');
                document.getElementById('admin-email-view-state').classList.add('disabled');
                document.getElementById('admin-email-actions-view').classList.remove('active');
                document.getElementById('admin-email-actions-view').classList.add('disabled');
                document.getElementById('admin-email-edit-state').classList.add('active');
                document.getElementById('admin-email-edit-state').classList.remove('disabled');
                document.getElementById('admin-email-actions-edit').classList.add('active');
                document.getElementById('admin-email-actions-edit').classList.remove('disabled');
                focusInputAndMoveCursorToEnd(document.getElementById('admin-email-input'));
                return;
            }
            if (target.closest('#admin-email-cancel-trigger')) {
                e.preventDefault();
                const displayElement = document.getElementById('admin-email-display-text');
                const inputElement = document.getElementById('admin-email-input');
                if (displayElement && inputElement) inputElement.value = displayElement.dataset.originalEmail;
                document.getElementById('admin-email-edit-state').classList.remove('active');
                document.getElementById('admin-email-edit-state').classList.add('disabled');
                document.getElementById('admin-email-actions-edit').classList.remove('active');
                document.getElementById('admin-email-actions-edit').classList.add('disabled');
                document.getElementById('admin-email-view-state').classList.add('active');
                document.getElementById('admin-email-view-state').classList.remove('disabled');
                document.getElementById('admin-email-actions-view').classList.add('active');
                document.getElementById('admin-email-actions-view').classList.remove('disabled');
                return;
            }
             if (target.closest('#admin-email-save-trigger-btn')) {
                e.preventDefault();
                const saveTrigger = target.closest('#admin-email-save-trigger-btn');
                const inputElement = document.getElementById('admin-email-input');
                const newEmail = inputElement.value;

                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(newEmail)) {
                    showInlineError(emailCard, 'js.auth.errorInvalidEmail'); return;
                }
                if (newEmail.length > (window.maxEmailLength || 255)) {
                    showInlineError(emailCard, 'js.auth.errorEmailLength'); return;
                }
                
                
                toggleButtonSpinner(saveTrigger, getTranslation('settings.profile.save'), true);
                const formData = new FormData();
                formData.append('action', 'admin-update-email');
                formData.append('target_user_id', targetUserId); 
                formData.append('email', newEmail);
                formData.append('csrf_token', getCsrfTokenFromPage()); 

                const result = await callAdminApi(formData); 

                if (result.success) {
                    window.showAlert(getTranslation(result.message || 'js.settings.successEmailUpdate'), 'success');

                    const newEmail = result.newEmail; 
                    const displayElement = document.getElementById('admin-email-display-text');
                    const inputElement = document.getElementById('admin-email-input');

                    displayElement.textContent = newEmail;
                    displayElement.dataset.originalEmail = newEmail;
                    inputElement.value = newEmail;

                    document.getElementById('admin-email-edit-state').classList.remove('active');
                    document.getElementById('admin-email-edit-state').classList.add('disabled');
                    document.getElementById('admin-email-actions-edit').classList.remove('active');
                    document.getElementById('admin-email-actions-edit').classList.add('disabled');
                    document.getElementById('admin-email-view-state').classList.add('active');
                    document.getElementById('admin-email-view-state').classList.remove('disabled');
                    document.getElementById('admin-email-actions-view').classList.add('active');
                    document.getElementById('admin-email-actions-view').classList.remove('disabled');

                    toggleButtonSpinner(saveTrigger, getTranslation('settings.profile.save'), false);
                } else {
                    showInlineError(emailCard, result.message || 'js.settings.errorSaveUnknown', result.data);
                    toggleButtonSpinner(saveTrigger, getTranslation('settings.profile.save'), false);
                }
                 return;
            }
        }

         if (target.closest('#admin-password-update-save')) {
            e.preventDefault();
             const step2Card = target.closest('#admin-pass-step-2');
             if (!step2Card) return; 

            const saveTrigger = target.closest('#admin-password-update-save');
            const newPassInput = document.getElementById('admin-password-update-new'); 
            const confirmPassInput = document.getElementById('admin-password-update-confirm'); 

            if (!newPassInput || !confirmPassInput) return;

            hideInlineError(step2Card); 
            
            if (newPassInput.value || confirmPassInput.value) {
                const minPassLength = window.minPasswordLength || 8;
                const maxPassLength = window.maxPasswordLength || 72;
                 if (newPassInput.value.length < minPassLength || newPassInput.value.length > maxPassLength) {
                    showInlineError(step2Card, 'js.auth.errorPasswordLength', {min: minPassLength, max: maxPassLength});
                     return;
                 }
                 if (newPassInput.value !== confirmPassInput.value) {
                    showInlineError(step2Card, 'js.auth.errorPasswordMismatch');
                     return;
                 }
            } else {
                window.showAlert(getTranslation('admin.edit.errorPassEmpty'), 'info');
                return;
            }

            toggleButtonSpinner(saveTrigger, getTranslation('settings.login.savePassword'), true);

            const formData = new FormData();
            formData.append('action', 'admin-update-password');
            formData.append('target_user_id', targetUserId); 
            formData.append('new_password', newPassInput.value);
            formData.append('confirm_password', confirmPassInput.value);
            formData.append('csrf_token', getCsrfTokenFromPage()); 

            const result = await callAdminApi(formData); 

            if (result.success) {
                window.showAlert(getTranslation(result.message || 'js.settings.successPassUpdate'), 'success');
                
                if (result.newPasswordHash) {
                    document.getElementById('admin-password-hash-display').value = result.newPasswordHash;
                }
                newPassInput.value = '';
                confirmPassInput.value = '';

                toggleButtonSpinner(saveTrigger, getTranslation('settings.login.savePassword'), false);
            } else {
                showInlineError(step2Card, result.message || 'js.settings.errorSaving', result.data);
                toggleButtonSpinner(saveTrigger, getTranslation('settings.login.savePassword'), false);
            }
            return;
        }

    }); 

    document.body.addEventListener('change', async (e) => {
        const targetUserIdInput = document.getElementById('admin-edit-target-user-id');
        if (!targetUserIdInput) {
            return; 
        }

        const target = e.target;
        const card = target.closest('#admin-avatar-section');

        if (target.id === 'admin-avatar-upload-input' && card) {
            hideInlineError(card);
            const fileInput = target;
            const previewImage = document.getElementById('admin-avatar-preview-image');
            const file = fileInput.files[0];
            if (!file) return;
            if (!['image/png', 'image/jpeg', 'image/gif', 'image/webp'].includes(file.type)) {
                showInlineError(card, 'js.settings.errorAvatarFormat');
                fileInput.value = ''; 
                return;
            }

            const maxSizeInMB = window.avatarMaxSizeMB || 2;
            if (file.size > maxSizeInMB * 1024 * 1024) { 
                showInlineError(card, 'js.settings.errorAvatarSize', { size: maxSizeInMB });
                fileInput.value = ''; 
                return;
            }
            
            if (!previewImage.dataset.originalSrc) {
                previewImage.dataset.originalSrc = previewImage.src;
            }
            const reader = new FileReader();
            reader.onload = (event) => { previewImage.src = event.target.result; };
            reader.readAsDataURL(file);

            const actionsDefault = document.getElementById('admin-avatar-actions-default');
            const avatarCard = document.getElementById('admin-avatar-section'); 
            avatarCard.dataset.originalActions = (actionsDefault.classList.contains('active')) ? 'default' : 'custom';

            document.getElementById('admin-avatar-actions-default').classList.remove('active');
            document.getElementById('admin-avatar-actions-default').classList.add('disabled');
            document.getElementById('admin-avatar-actions-custom').classList.remove('active');
            document.getElementById('admin-avatar-actions-custom').classList.add('disabled');
            document.getElementById('admin-avatar-actions-preview').classList.add('active');
            document.getElementById('admin-avatar-actions-preview').classList.remove('disabled');
        }
    }); 

    document.body.addEventListener('input', (e) => {
        const targetUserIdInput = document.getElementById('admin-edit-target-user-id');
        if (!targetUserIdInput) {
            return; 
        }
        
        const target = e.target;
        if (target.matches('.component-text-input') || target.closest('.component-input-group')) {
            const card = target.closest('.component-card');
            if (card) {
                hideInlineError(card);
            }
        }
    }); 

    setTimeout(() => {
        const targetUserIdInput = document.getElementById('admin-edit-target-user-id');
        if (!targetUserIdInput) {
            return; 
        }
        
        const previewImage = document.getElementById('admin-avatar-preview-image');
        if (previewImage && !previewImage.dataset.originalSrc) {
            previewImage.dataset.originalSrc = previewImage.src;
        }
    }, 100);
}
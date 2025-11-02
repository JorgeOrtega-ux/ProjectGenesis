// FILE: assets/js/modules/admin-edit-user-manager.js

import { callAdminApi }  from '../services/api-service.js';
import { getTranslation } from '../services/i18n-manager.js';

// --- Helper Functions (Copiadas de settings-manager.js) ---

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
        const spinnerClass = 'logout-spinner'; // Usar siempre el spinner pequeño
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

// --- Fin Helper Functions ---


export function initAdminEditUserManager() {

    // --- ▼▼▼ CORRECCIÓN: ELIMINAR LA COMPROBACIÓN INICIAL ▼▼▼ ---
    // La comprobación `if (!targetUserIdInput)` se ha eliminado.
    // Los listeners se adjuntan al 'body' incondicionalmente.
    // La comprobación de si estamos en la página correcta se hará DENTRO del listener.
    // --- ▲▲▲ FIN DE CORRECCIÓN ▲▲▲ ---

    document.body.addEventListener('click', async (e) => {
        
        // --- ▼▼▼ CORRECCIÓN: OBTENER ID Y TARGET AQUÍ ▼▼▼ ---
        // Comprobar si estamos en la página de edición ANTES de ejecutar cualquier lógica.
        const targetUserIdInput = document.getElementById('admin-edit-target-user-id');
        if (!targetUserIdInput) {
            // No estamos en la página de admin-edit-user, no hacer nada.
            return;
        }
        const targetUserId = targetUserIdInput.value;
        const target = e.target;
        // --- ▲▲▲ FIN DE CORRECCIÓN ▲▲▲ ---
        
        // === LÓGICA DE AVATAR ===
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

                document.getElementById('admin-avatar-actions-preview').style.display = 'none';
                const originalState = avatarCard.dataset.originalActions === 'default'
                    ? 'admin-avatar-actions-default'
                    : 'admin-avatar-actions-custom';
                document.getElementById(originalState).style.display = 'flex';
                return;
            }

            if (target.closest('#admin-avatar-remove-trigger')) {
                e.preventDefault();
                hideInlineError(avatarCard); 
                const removeTrigger = target.closest('#admin-avatar-remove-trigger');
                toggleButtonSpinner(removeTrigger, getTranslation('settings.profile.removePhoto'), true);

                const formData = new FormData();
                formData.append('action', 'admin-remove-avatar');
                formData.append('target_user_id', targetUserId); // ID del usuario a editar
                formData.append('csrf_token', getCsrfTokenFromPage()); 

                const result = await callAdminApi(formData); // API de Admin

                // --- ▼▼▼ INICIO DE MODIFICACIÓN (NO-RELOAD) ▼▼▼ ---
                if (result.success) {
                    window.showAlert(getTranslation(result.message || 'js.settings.successAvatarRemoved'), 'success');
                    
                    const previewImage = document.getElementById('admin-avatar-preview-image');
                    previewImage.src = result.newAvatarUrl; // API returns new default URL
                    previewImage.dataset.originalSrc = result.newAvatarUrl;

                    // Hide custom buttons, show default buttons
                    document.getElementById('admin-avatar-actions-preview').style.display = 'none';
                    document.getElementById('admin-avatar-actions-custom').style.display = 'none';
                    document.getElementById('admin-avatar-actions-default').style.display = 'flex';
                    avatarCard.dataset.originalActions = 'default'; // Update the original state
                    
                    toggleButtonSpinner(removeTrigger, getTranslation('settings.profile.removePhoto'), false);
                } else {
                    showInlineError(avatarCard, result.message || 'js.settings.errorAvatarRemove');
                    toggleButtonSpinner(removeTrigger, getTranslation('settings.profile.removePhoto'), false);
                }
                // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
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
                formData.append('target_user_id', targetUserId); // ID del usuario a editar
                formData.append('avatar', fileInput.files[0]);
                formData.append('csrf_token', getCsrfTokenFromPage()); 

                const result = await callAdminApi(formData); // API de Admin

                // --- ▼▼▼ INICIO DE MODIFICACIÓN (NO-RELOAD) ▼▼▼ ---
                if (result.success) {
                    window.showAlert(getTranslation(result.message || 'js.settings.successAvatarUpdate'), 'success');

                    const previewImage = document.getElementById('admin-avatar-preview-image');
                    previewImage.src = result.newAvatarUrl; // API must return this
                    previewImage.dataset.originalSrc = result.newAvatarUrl; // Update original source
                    
                    document.getElementById('admin-avatar-upload-input').value = ''; // Clear file input

                    // Hide preview buttons, show custom buttons
                    document.getElementById('admin-avatar-actions-preview').style.display = 'none';
                    document.getElementById('admin-avatar-actions-default').style.display = 'none';
                    document.getElementById('admin-avatar-actions-custom').style.display = 'flex';
                    avatarCard.dataset.originalActions = 'custom'; // Update the original state

                    toggleButtonSpinner(saveTrigger, getTranslation('settings.profile.save'), false);
                } else {
                    showInlineError(avatarCard, result.message || 'js.settings.errorSaveUnknown');
                    toggleButtonSpinner(saveTrigger, getTranslation('settings.profile.save'), false);
                }
                // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
                return;
            }
        }

        // === LÓGICA DE USERNAME ===
        const usernameCard = document.getElementById('admin-username-section');
        if (usernameCard) {
            hideInlineError(usernameCard);
            if (target.closest('#admin-username-edit-trigger')) {
                e.preventDefault();
                document.getElementById('admin-username-view-state').style.display = 'none';
                document.getElementById('admin-username-actions-view').style.display = 'none';
                document.getElementById('admin-username-edit-state').style.display = 'flex';
                document.getElementById('admin-username-actions-edit').style.display = 'flex';
                focusInputAndMoveCursorToEnd(document.getElementById('admin-username-input'));
                return;
            }
            if (target.closest('#admin-username-cancel-trigger')) {
                e.preventDefault();
                const displayElement = document.getElementById('admin-username-display-text');
                const inputElement = document.getElementById('admin-username-input');
                if (displayElement && inputElement) inputElement.value = displayElement.dataset.originalUsername;
                document.getElementById('admin-username-edit-state').style.display = 'none';
                document.getElementById('admin-username-actions-edit').style.display = 'none';
                document.getElementById('admin-username-view-state').style.display = 'flex';
                document.getElementById('admin-username-actions-view').style.display = 'flex';
                return;
            }
             if (target.closest('#admin-username-save-trigger-btn')) {
                e.preventDefault();
                const saveTrigger = target.closest('#admin-username-save-trigger-btn');
                const inputElement = document.getElementById('admin-username-input');
                if (inputElement.value.length < 6 || inputElement.value.length > 32) {
                    showInlineError(usernameCard, 'js.auth.errorUsernameLength', { min: 6, max: 32 });
                    return;
                }
                toggleButtonSpinner(saveTrigger, getTranslation('settings.profile.save'), true);
                const formData = new FormData();
                formData.append('action', 'admin-update-username');
                formData.append('target_user_id', targetUserId); // ID del usuario a editar
                formData.append('username', inputElement.value);
                formData.append('csrf_token', getCsrfTokenFromPage()); 

                const result = await callAdminApi(formData); // API de Admin

                // --- ▼▼▼ INICIO DE MODIFICACIÓN (NO-RELOAD) ▼▼▼ ---
                if (result.success) {
                    window.showAlert(getTranslation(result.message || 'js.settings.successUsernameUpdate'), 'success');
                    
                    const newUsername = result.newUsername; // Get from API
                    const displayElement = document.getElementById('admin-username-display-text');
                    const inputElement = document.getElementById('admin-username-input');
                    
                    displayElement.textContent = newUsername;
                    displayElement.dataset.originalUsername = newUsername;
                    inputElement.value = newUsername;

                    // Hide edit state, show view state
                    document.getElementById('admin-username-edit-state').style.display = 'none';
                    document.getElementById('admin-username-actions-edit').style.display = 'none';
                    document.getElementById('admin-username-view-state').style.display = 'flex';
                    document.getElementById('admin-username-actions-view').style.display = 'flex';

                    // Check if avatar also changed (if it was a default one)
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
                // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
                 return;
            }
        }

        // === LÓGICA DE EMAIL (Basada en Username) ===
        const emailCard = document.getElementById('admin-email-section');
        if (emailCard) {
            hideInlineError(emailCard);
            if (target.closest('#admin-email-edit-trigger')) {
                e.preventDefault();
                document.getElementById('admin-email-view-state').style.display = 'none';
                document.getElementById('admin-email-actions-view').style.display = 'none';
                document.getElementById('admin-email-edit-state').style.display = 'flex';
                document.getElementById('admin-email-actions-edit').style.display = 'flex';
                focusInputAndMoveCursorToEnd(document.getElementById('admin-email-input'));
                return;
            }
            if (target.closest('#admin-email-cancel-trigger')) {
                e.preventDefault();
                const displayElement = document.getElementById('admin-email-display-text');
                const inputElement = document.getElementById('admin-email-input');
                if (displayElement && inputElement) inputElement.value = displayElement.dataset.originalEmail;
                document.getElementById('admin-email-edit-state').style.display = 'none';
                document.getElementById('admin-email-actions-edit').style.display = 'none';
                document.getElementById('admin-email-view-state').style.display = 'flex';
                document.getElementById('admin-email-actions-view').style.display = 'flex';
                return;
            }
             if (target.closest('#admin-email-save-trigger-btn')) {
                e.preventDefault();
                const saveTrigger = target.closest('#admin-email-save-trigger-btn');
                const inputElement = document.getElementById('admin-email-input');
                const newEmail = inputElement.value;

                // Validación de Email
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(newEmail)) {
                    showInlineError(emailCard, 'js.auth.errorInvalidEmail'); return;
                }
                if (newEmail.length > 255) {
                    showInlineError(emailCard, 'js.auth.errorEmailLength'); return;
                }
                const allowedDomains = /@(gmail\.com|outlook\.com|hotmail\.com|yahoo\.com|icloud\.com)$/i;
                if (!allowedDomains.test(newEmail)) {
                    showInlineError(emailCard, 'js.auth.errorEmailDomain'); return;
                }

                toggleButtonSpinner(saveTrigger, getTranslation('settings.profile.save'), true);
                const formData = new FormData();
                formData.append('action', 'admin-update-email');
                formData.append('target_user_id', targetUserId); // ID del usuario a editar
                formData.append('email', newEmail);
                formData.append('csrf_token', getCsrfTokenFromPage()); 

                const result = await callAdminApi(formData); // API de Admin

                // --- ▼▼▼ INICIO DE MODIFICACIÓN (NO-RELOAD) ▼▼▼ ---
                if (result.success) {
                    window.showAlert(getTranslation(result.message || 'js.settings.successEmailUpdate'), 'success');

                    const newEmail = result.newEmail; // Get from API
                    const displayElement = document.getElementById('admin-email-display-text');
                    const inputElement = document.getElementById('admin-email-input');

                    displayElement.textContent = newEmail;
                    displayElement.dataset.originalEmail = newEmail;
                    inputElement.value = newEmail;

                    // Hide edit state, show view state
                    document.getElementById('admin-email-edit-state').style.display = 'none';
                    document.getElementById('admin-email-actions-edit').style.display = 'none';
                    document.getElementById('admin-email-view-state').style.display = 'flex';
                    document.getElementById('admin-email-actions-view').style.display = 'flex';

                    toggleButtonSpinner(saveTrigger, getTranslation('settings.profile.save'), false);
                } else {
                    showInlineError(emailCard, result.message || 'js.settings.errorSaveUnknown', result.data);
                    toggleButtonSpinner(saveTrigger, getTranslation('settings.profile.save'), false);
                }
                // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
                 return;
            }
        }

        // === LÓGICA DE CONTRASEÑA ===
         if (target.closest('#admin-password-update-save')) {
            e.preventDefault();
             const step2Card = target.closest('#admin-pass-step-2');
             if (!step2Card) return; 

            const saveTrigger = target.closest('#admin-password-update-save');
            const newPassInput = document.getElementById('admin-password-update-new'); 
            const confirmPassInput = document.getElementById('admin-password-update-confirm'); 

            if (!newPassInput || !confirmPassInput) return;

            hideInlineError(step2Card); 
            
            // Permitir campos vacíos (para "no cambiar"), pero si uno está lleno, validar
            if (newPassInput.value || confirmPassInput.value) {
                 if (newPassInput.value.length < 8 || newPassInput.value.length > 72) {
                    showInlineError(step2Card, 'js.auth.errorPasswordLength', {min: 8, max: 72});
                     return;
                 }
                 if (newPassInput.value !== confirmPassInput.value) {
                    showInlineError(step2Card, 'js.auth.errorPasswordMismatch');
                     return;
                 }
            } else {
                // Ambos están vacíos, no hacer nada.
                window.showAlert(getTranslation('admin.edit.errorPassEmpty'), 'info');
                return;
            }

            toggleButtonSpinner(saveTrigger, getTranslation('settings.login.savePassword'), true);

            const formData = new FormData();
            formData.append('action', 'admin-update-password');
            formData.append('target_user_id', targetUserId); // ID del usuario a editar
            formData.append('new_password', newPassInput.value);
            formData.append('confirm_password', confirmPassInput.value);
            formData.append('csrf_token', getCsrfTokenFromPage()); 

            const result = await callAdminApi(formData); // API de Admin

            // --- ▼▼▼ INICIO DE MODIFICACIÓN (NO-RELOAD) ▼▼▼ ---
            if (result.success) {
                window.showAlert(getTranslation(result.message || 'js.settings.successPassUpdate'), 'success');
                
                // Actualizar el hash mostrado y limpiar los campos
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
            // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
            return;
        }

    }); 

    // === LISTENER DE 'CHANGE' PARA AVATAR ===
    document.body.addEventListener('change', async (e) => {
        // --- ▼▼▼ CORRECCIÓN: AÑADIR COMPROBACIÓN DE PÁGINA ▼▼▼ ---
        const targetUserIdInput = document.getElementById('admin-edit-target-user-id');
        if (!targetUserIdInput) {
            return; // No estamos en la página de admin-edit-user
        }
        // --- ▲▲▲ FIN DE CORRECCIÓN ▲▲▲ ---

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
            if (file.size > 2 * 1024 * 1024) { // 2MB
                showInlineError(card, 'js.settings.errorAvatarSize');
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
            avatarCard.dataset.originalActions = (actionsDefault.style.display !== 'none') ? 'default' : 'custom';

            document.getElementById('admin-avatar-actions-default').style.display = 'none';
            document.getElementById('admin-avatar-actions-custom').style.display = 'none';
            document.getElementById('admin-avatar-actions-preview').style.display = 'flex';
        }
    }); 

    // === LISTENER DE 'INPUT' PARA OCULTAR ERRORES ===
    document.body.addEventListener('input', (e) => {
        // --- ▼▼▼ CORRECCIÓN: AÑADIR COMPROBACIÓN DE PÁGINA ▼▼▼ ---
        const targetUserIdInput = document.getElementById('admin-edit-target-user-id');
        if (!targetUserIdInput) {
            return; // No estamos en la página de admin-edit-user
        }
        // --- ▲▲▲ FIN DE CORRECCIÓN ▲▲▲ ---
        
        const target = e.target;
        if (target.matches('.component-text-input') || target.closest('.component-input-group')) {
            const card = target.closest('.component-card');
            if (card) {
                hideInlineError(card);
            }
        }
    }); 

    // Guardar la URL original del avatar al cargar
    setTimeout(() => {
        // --- ▼▼▼ CORRECCIÓN: AÑADIR COMPROBACIÓN DE PÁGINA ▼▼▼ ---
        const targetUserIdInput = document.getElementById('admin-edit-target-user-id');
        if (!targetUserIdInput) {
            return; // No estamos en la página de admin-edit-user
        }
        // --- ▲▲▲ FIN DE CORRECCIÓN ▲▲▲ ---
        
        const previewImage = document.getElementById('admin-avatar-preview-image');
        if (previewImage && !previewImage.dataset.originalSrc) {
            previewImage.dataset.originalSrc = previewImage.src;
        }
    }, 100);
}
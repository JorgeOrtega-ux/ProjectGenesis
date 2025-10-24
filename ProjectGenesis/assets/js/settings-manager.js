/* ====================================== */
/* ======== SETTINGS-MANAGER.JS ========= */
/* ====================================== */
import { callSettingsApi } from './api-service.js'; // <-- AÑADIDO

// const SETTINGS_ENDPOINT = ...; // <-- ELIMINADO

function showAvatarError(message) {
    const errorDiv = document.getElementById('avatar-error');
    if (errorDiv) {
        errorDiv.style.display = 'none';
    }
    window.showAlert(message, 'error');
}

function hideAvatarError() {
    const errorDiv = document.getElementById('avatar-error');
    if (errorDiv) {
        errorDiv.style.display = 'none';
    }
}

function toggleButtonSpinner(button, text, isLoading) {
    if (!button) return;
    
    button.disabled = isLoading;
    
    if (isLoading) {
        button.dataset.originalText = button.textContent;
        button.innerHTML = `
            <span class="logout-spinner" 
                  style="width: 20px; height: 20px; border-width: 2px; margin: 0 auto; border-top-color: inherit;">
            </span>`;
    } else {
        button.innerHTML = button.dataset.originalText || text;
    }
}

function focusInputAndMoveCursorToEnd(inputElement) {
    if (!inputElement) return;
    
    const length = inputElement.value.length;
    const originalType = inputElement.type; 

    try {
        if (inputElement.type === 'email' || inputElement.type === 'text') {
             inputElement.type = 'text'; 
        }

        inputElement.focus();
        
        setTimeout(() => {
            try {
                inputElement.setSelectionRange(length, length);
            } catch (e) {
                // Ignorar
            }
            inputElement.type = originalType; 
        }, 0);

    } catch (e) {
        inputElement.type = originalType;
    }
}

export function initSettingsManager() {

    document.body.addEventListener('click', async (e) => {
        const fileInput = document.getElementById('avatar-upload-input');

        // --- Lógica de Avatar (Clics) ---
        if (e.target.closest('#avatar-preview-container')) {
            e.preventDefault();
            hideAvatarError();
            if (fileInput) {
                fileInput.click();
            }
            return;
        }

        if (e.target.closest('#avatar-upload-trigger') || e.target.closest('#avatar-change-trigger')) {
            e.preventDefault();
            hideAvatarError();
            if (fileInput) {
                fileInput.click();
            }
            return;
        }

        if (e.target.closest('#avatar-cancel-trigger')) {
            e.preventDefault();
            const previewImage = document.getElementById('avatar-preview-image');
            const originalAvatarSrc = previewImage.dataset.originalSrc; 
            const avatarForm = document.getElementById('avatar-form');

            if (previewImage && originalAvatarSrc) {
                previewImage.src = originalAvatarSrc;
            }
            if (avatarForm) {
                avatarForm.reset();
            }
            hideAvatarError();

            document.getElementById('avatar-actions-preview').style.display = 'none';
            
            const originalState = avatarForm.dataset.originalActions; 
            if (originalState === 'default') {
                document.getElementById('avatar-actions-default').style.display = 'flex';
            } else {
                document.getElementById('avatar-actions-custom').style.display = 'flex';
            }
            return;
        }

        if (e.target.closest('#avatar-remove-trigger')) {
            e.preventDefault();
            const avatarForm = document.getElementById('avatar-form');
            if (!avatarForm) return;

            hideAvatarError();
            const removeTrigger = e.target.closest('#avatar-remove-trigger');
            toggleButtonSpinner(removeTrigger, 'Eliminar foto', true);

            // --- LÓGICA DE FETCH REFACTORIZADA ---
            const formData = new FormData(avatarForm);
            formData.append('action', 'remove-avatar');

            const result = await callSettingsApi(formData);
            
            if (result.success) {
                window.showAlert(result.message || 'Avatar eliminado.', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showAvatarError(result.message || 'Error desconocido al eliminar.');
                toggleButtonSpinner(removeTrigger, 'Eliminar foto', false);
            }
            // --- FIN DEL REFACTOR ---
        }
        // --- Fin Lógica de Avatar ---


        // --- LÓGICA PARA NOMBRE DE USUARIO ---
        if (e.target.closest('#username-edit-trigger')) {
            e.preventDefault();
            document.getElementById('username-view-state').style.display = 'none';
            document.getElementById('username-actions-view').style.display = 'none';
            
            document.getElementById('username-edit-state').style.display = 'flex';
            document.getElementById('username-actions-edit').style.display = 'flex';
            
            focusInputAndMoveCursorToEnd(document.getElementById('username-input'));
            return;
        }

        if (e.target.closest('#username-cancel-trigger')) {
            e.preventDefault();
            
            const displayElement = document.getElementById('username-display-text');
            const inputElement = document.getElementById('username-input');
            if (displayElement && inputElement) {
                inputElement.value = displayElement.dataset.originalUsername;
            }

            document.getElementById('username-edit-state').style.display = 'none';
            document.getElementById('username-actions-edit').style.display = 'none';
            document.getElementById('username-view-state').style.display = 'flex';
            document.getElementById('username-actions-view').style.display = 'flex';
            return;
        }
        
        // --- LÓGICA PARA EMAIL ---
        if (e.target.closest('#email-edit-trigger')) {
            e.preventDefault();
            const editTrigger = e.target.closest('#email-edit-trigger');
            toggleButtonSpinner(editTrigger, 'Editar', true);

            // --- LÓGICA DE FETCH REFACTORIZADA ---
            const csrfToken = document.querySelector('#email-form [name="csrf_token"]');
            const formData = new FormData();
            formData.append('action', 'request-email-change-code');
            // (El token CSRF se añadirá automáticamente en callSettingsApi)

            const result = await callSettingsApi(formData);

            if (result.success) {
                const currentEmail = document.getElementById('email-display-text')?.dataset.originalEmail;
                const modalEmailEl = document.getElementById('email-verify-modal-email');
                if (modalEmailEl && currentEmail) {
                    modalEmailEl.textContent = currentEmail;
                }

                const modal = document.getElementById('email-verify-modal');
                if(modal) modal.style.display = 'flex';
                focusInputAndMoveCursorToEnd(document.getElementById('email-verify-code'));
                
                const modalError = document.getElementById('email-verify-error');
                if(modalError) modalError.style.display = 'none';
                const modalInput = document.getElementById('email-verify-code');
                if(modalInput) modalInput.value = '';

                window.showAlert('Se ha enviado (simulado) un código a tu correo actual.', 'info');
            } else {
                window.showAlert(result.message || 'Error al solicitar el código.', 'error');
            }
            // --- FIN DEL REFACTOR ---
            
            toggleButtonSpinner(editTrigger, 'Editar', false);
            return;
        }

        if (e.target.closest('#email-verify-resend')) {
            e.preventDefault();
            const resendTrigger = e.target.closest('#email-verify-resend');
            
            if (resendTrigger.classList.contains('disabled-interactive')) return;
            
            resendTrigger.classList.add('disabled-interactive');
            const originalText = resendTrigger.textContent;
            resendTrigger.textContent = 'Enviando...';
            
            // --- LÓGICA DE FETCH REFACTORIZADA ---
            const formData = new FormData();
            formData.append('action', 'request-email-change-code');
            
            const result = await callSettingsApi(formData);

            if (result.success) {
                window.showAlert('Se ha reenviado (simulado) un nuevo código.', 'success');
            } else {
                window.showAlert(result.message || 'Error al reenviar el código.', 'error');
            }
            // --- FIN DEL REFACTOR ---

            resendTrigger.classList.remove('disabled-interactive');
            resendTrigger.textContent = originalText;
            return;
        }
        
        if (e.target.closest('#email-verify-close')) {
            e.preventDefault();
            const modal = document.getElementById('email-verify-modal');
            if(modal) modal.style.display = 'none';
            return;
        }

        if (e.target.closest('#email-verify-continue')) {
            e.preventDefault();
            const continueTrigger = e.target.closest('#email-verify-continue');
            const modalError = document.getElementById('email-verify-error');
            const modalInput = document.getElementById('email-verify-code');

            if (!modalInput || !modalInput.value) {
                if(modalError) {
                    modalError.textContent = 'Por favor, introduce el código.';
                    modalError.style.display = 'block';
                }
                return;
            }

            toggleButtonSpinner(continueTrigger, 'Continuar', true);
            if(modalError) modalError.style.display = 'none';

            // --- LÓGICA DE FETCH REFACTORIZADA ---
            const formData = new FormData();
            formData.append('action', 'verify-email-change-code');
            formData.append('verification_code', modalInput.value);

            const result = await callSettingsApi(formData);

            if (result.success) {
                const modal = document.getElementById('email-verify-modal');
                if(modal) modal.style.display = 'none';

                document.getElementById('email-view-state').style.display = 'none';
                document.getElementById('email-actions-view').style.display = 'none';
                document.getElementById('email-edit-state').style.display = 'flex';
                document.getElementById('email-actions-edit').style.display = 'flex';
                
                focusInputAndMoveCursorToEnd(document.getElementById('email-input'));
                window.showAlert(result.message || 'Verificación correcta.', 'success');
            } else {
                if(modalError) {
                    modalError.textContent = result.message || 'Error al verificar.';
                    modalError.style.display = 'block';
                }
            }
            // --- FIN DEL REFACTOR ---
            
            toggleButtonSpinner(continueTrigger, 'Continuar', false);
            return;
        }

        if (e.target.closest('#email-cancel-trigger')) {
            e.preventDefault();
            
            const displayElement = document.getElementById('email-display-text');
            const inputElement = document.getElementById('email-input');
            if (displayElement && inputElement) {
                inputElement.value = displayElement.dataset.originalEmail;
            }

            document.getElementById('email-edit-state').style.display = 'none';
            document.getElementById('email-actions-edit').style.display = 'none';
            document.getElementById('email-view-state').style.display = 'flex';
            document.getElementById('email-actions-view').style.display = 'flex';
            return;
        }
    });

    // 2. Delegación para SUBMIT
    document.body.addEventListener('submit', async (e) => {
        
        if (e.target.id === 'avatar-form') {
            e.preventDefault();
            const avatarForm = e.target;
            const fileInput = document.getElementById('avatar-upload-input');
            const saveTrigger = document.getElementById('avatar-save-trigger');
            
            hideAvatarError();
            
            if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                showAvatarError('Por favor, selecciona un archivo primero.');
                return;
            }

            toggleButtonSpinner(saveTrigger, 'Guardar', true);

            // --- LÓGICA DE FETCH REFACTORIZADA ---
            const formData = new FormData(avatarForm);
            formData.append('action', 'upload-avatar');

            const result = await callSettingsApi(formData);
            
            if (result.success) {
                window.showAlert(result.message || 'Avatar actualizado.', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showAvatarError(result.message || 'Error desconocido al guardar.');
                toggleButtonSpinner(saveTrigger, 'Guardar', false);
            }
            // --- FIN DEL REFACTOR ---
        }

        if (e.target.id === 'username-form') {
            e.preventDefault();
            const usernameForm = e.target;
            const saveTrigger = document.getElementById('username-save-trigger');
            const inputElement = document.getElementById('username-input');

            if (inputElement.value.length < 6) {
                window.showAlert('El nombre de usuario debe tener al menos 6 caracteres.', 'error');
                return;
            }

            toggleButtonSpinner(saveTrigger, 'Guardar', true);

            // --- LÓGICA DE FETCH REFACTORIZADA ---
            const formData = new FormData(usernameForm);
            formData.append('action', 'update-username'); 

            const result = await callSettingsApi(formData);
            
            if (result.success) {
                window.showAlert(result.message || 'Nombre de usuario actualizado.', 'success');
                setTimeout(() => location.reload(), 1500); 
            } else {
                window.showAlert(result.message || 'Error desconocido al guardar.', 'error');
                toggleButtonSpinner(saveTrigger, 'Guardar', false);
            }
            // --- FIN DEL REFACTOR ---
        }
        
        if (e.target.id === 'email-form') {
            e.preventDefault();
            const emailForm = e.target;
            const saveTrigger = document.getElementById('email-save-trigger');
            const inputElement = document.getElementById('email-input');
            const newEmail = inputElement.value;

            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(newEmail)) {
                window.showAlert('Por favor, introduce un correo electrónico válido.', 'error');
                return;
            }
            
            const allowedDomains = /@(gmail\.com|outlook\.com|hotmail\.com|yahoo\.com|icloud\.com)$/i;
            if (!allowedDomains.test(newEmail)) {
                 window.showAlert('Solo se permiten correos @gmail, @outlook, @hotmail, @yahoo o @icloud.', 'error');
                return;
            }

            toggleButtonSpinner(saveTrigger, 'Guardar', true);

            // --- LÓGICA DE FETCH REFACTORIZADA ---
            const formData = new FormData(emailForm);
            formData.append('action', 'update-email'); 

            const result = await callSettingsApi(formData);
            
            if (result.success) {
                window.showAlert(result.message || 'Correo actualizado.', 'success');
                setTimeout(() => location.reload(), 1500); 
            } else {
                window.showAlert(result.message || 'Error desconocido al guardar.', 'error');
                toggleButtonSpinner(saveTrigger, 'Guardar', false);
            }
            // --- FIN DEL REFACTOR ---
        }
    });

    // 3. Delegación para CHANGE
    document.body.addEventListener('change', (e) => {
        if (e.target.id === 'avatar-upload-input') {
            const fileInput = e.target;
            const previewImage = document.getElementById('avatar-preview-image');
            const file = fileInput.files[0];
            
            if (!file) return;

            if (!['image/png', 'image/jpeg', 'image/gif', 'image/webp'].includes(file.type)) {
                showAvatarError('Formato no válido (solo PNG, JPEG, GIF o WebP).');
                fileInput.form.reset();
                return;
            }
            if (file.size > 2 * 1024 * 1024) {
                showAvatarError('El archivo es demasiado grande (máx 2MB).');
                fileInput.form.reset();
                return;
            }
            
            if (!previewImage.dataset.originalSrc) {
                previewImage.dataset.originalSrc = previewImage.src;
            }

            const reader = new FileReader();
            reader.onload = (event) => {
                previewImage.src = event.target.result;
            };
            reader.readAsDataURL(file);

            const actionsDefault = document.getElementById('avatar-actions-default');
            const avatarForm = fileInput.form;

            if (actionsDefault.style.display !== 'none') {
                avatarForm.dataset.originalActions = 'default';
            } else {
                avatarForm.dataset.originalActions = 'custom';
            }

            document.getElementById('avatar-actions-default').style.display = 'none';
            document.getElementById('avatar-actions-custom').style.display = 'none';
            document.getElementById('avatar-actions-preview').style.display = 'flex';
        }
    });
    
    // 4. Guardar la URL original
    setTimeout(() => {
        const previewImage = document.getElementById('avatar-preview-image');
        if (previewImage && !previewImage.dataset.originalSrc) {
            previewImage.dataset.originalSrc = previewImage.src;
        }
    }, 100);
}
import { callSettingsApi } from './api-service.js'; 
import { deactivateAllModules } from './main-controller.js';


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
            }
            inputElement.type = originalType; 
        }, 0);

    } catch (e) {
        inputElement.type = originalType;
    }
}

async function handlePreferenceChange(preferenceTypeOrField, newValue) {
    if (!preferenceTypeOrField || newValue === undefined) { 
        console.error('handlePreferenceChange: Faltan el tipo/campo o el valor.');
        return;
    }

    const fieldMap = {
        'language': 'language',
        'theme': 'theme',
        'usage': 'usage_type' 
    };

    const fieldName = fieldMap[preferenceTypeOrField] || preferenceTypeOrField;

    if (!fieldName) {
        console.error('Tipo de preferencia desconocido:', preferenceTypeOrField);
        return;
    }

    const formData = new FormData();
    formData.append('action', 'update-preference');
    formData.append('field', fieldName);
    formData.append('value', newValue);

    const result = await callSettingsApi(formData);

    if (result.success) {
        if (preferenceTypeOrField === 'language' || preferenceTypeOrField === 'theme' || preferenceTypeOrField === 'usage') {
             window.showAlert(result.message || 'Preferencia actualizada.', 'success');
        }
       
        
        if (preferenceTypeOrField === 'theme') {
            window.userTheme = newValue;
            if (window.applyCurrentTheme) {
                window.applyCurrentTheme(newValue);
            }
        }
        
        if (fieldName === 'increase_message_duration') {
            window.userIncreaseMessageDuration = Number(newValue);
        }
        

        if (preferenceTypeOrField === 'language') {
            window.showAlert('Idioma actualizado. La página se recargará.', 'success');
            setTimeout(() => location.reload(), 1500);
        }
    } else {
        window.showAlert(result.message || 'Error al guardar la preferencia.', 'error');
    }
}


export function initSettingsManager() {

    document.body.addEventListener('click', async (e) => {
        const fileInput = document.getElementById('avatar-upload-input');

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
        }


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
        
        if (e.target.closest('#email-edit-trigger')) {
            e.preventDefault();
            const editTrigger = e.target.closest('#email-edit-trigger');
            toggleButtonSpinner(editTrigger, 'Editar', true);

            const csrfToken = document.querySelector('#email-form [name="csrf_token"]');
            const formData = new FormData();
            formData.append('action', 'request-email-change-code');

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
            
            const formData = new FormData();
            formData.append('action', 'request-email-change-code');
            
            const result = await callSettingsApi(formData);

            if (result.success) {
                window.showAlert('Se ha reenviado (simulado) un nuevo código.', 'success');
            } else {
                window.showAlert(result.message || 'Error al reenviar el código.', 'error');
            }

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

        if (e.target.closest('#tfa-verify-close')) {
            e.preventDefault();
            const modal = document.getElementById('tfa-verify-modal');
            if(modal) modal.style.display = 'none';
            return;
        }

        if (e.target.closest('#tfa-toggle-button')) {
            e.preventDefault();
            
            const toggleButton = e.target.closest('#tfa-toggle-button');
            const modal = document.getElementById('tfa-verify-modal');
            
            if (!modal) {
                window.showAlert('Error: No se encontró el modal de verificación.', 'error');
                return;
            }

            const isCurrentlyEnabled = toggleButton.dataset.isEnabled === '1';

            const modalTitle = document.getElementById('tfa-modal-title');
            const modalText = document.getElementById('tfa-modal-text');
            const errorDiv = document.getElementById('tfa-verify-error');
            const passInput = document.getElementById('tfa-verify-password');

            if (!isCurrentlyEnabled) {
                if(modalTitle) modalTitle.textContent = 'Activar Verificación de dos pasos';
                if(modalText) modalText.textContent = 'Para activar esta función, por favor ingresa tu contraseña actual.';
            } else {
                if(modalTitle) modalTitle.textContent = 'Desactivar Verificación de dos pasos';
                if(modalText) modalText.textContent = 'Para desactivar esta función, por favor ingresa tu contraseña actual.';
            }

            if(errorDiv) errorDiv.style.display = 'none';
            if(passInput) passInput.value = '';

            modal.style.display = 'flex';
            focusInputAndMoveCursorToEnd(passInput);
            return;
        }

        if (e.target.closest('#tfa-verify-continue')) {
            e.preventDefault();
            const modal = document.getElementById('tfa-verify-modal');
            const verifyTrigger = e.target.closest('#tfa-verify-continue');
            const errorDiv = document.getElementById('tfa-verify-error');
            const currentPassInput = document.getElementById('tfa-verify-password');
            
            const toggleButton = document.getElementById('tfa-toggle-button');

            if (!currentPassInput.value) {
                if(errorDiv) {
                    errorDiv.textContent = 'Por favor, introduce tu contraseña actual.';
                    errorDiv.style.display = 'block';
                }
                return;
            }
            
            toggleButtonSpinner(verifyTrigger, 'Confirmar', true);
            if(errorDiv) errorDiv.style.display = 'none';

            const passFormData = new FormData();
            passFormData.append('action', 'verify-current-password');
            passFormData.append('current_password', currentPassInput.value);

            const passResult = await callSettingsApi(passFormData);

            if (passResult.success) {
                const twoFaFormData = new FormData();
                twoFaFormData.append('action', 'toggle-2fa');
                
                const twoFaResult = await callSettingsApi(twoFaFormData);

                if (twoFaResult.success) {
                    if(modal) modal.style.display = 'none';
                    window.showAlert(twoFaResult.message, 'success');
                    
                    const statusText = document.getElementById('tfa-status-text');
                    
                    if (twoFaResult.newState === 1) {
                        if (statusText) statusText.textContent = 'La autenticación de dos pasos está activa.';
                        if (toggleButton) {
                            toggleButton.textContent = 'Deshabilitar';
                            toggleButton.classList.add('danger');
                            toggleButton.dataset.isEnabled = '1';
                        }
                    } else {
                        if (statusText) statusText.textContent = 'Añade una capa extra de seguridad a tu cuenta.';
                        if (toggleButton) {
                            toggleButton.textContent = 'Habilitar';
                            toggleButton.classList.remove('danger');
                            toggleButton.dataset.isEnabled = '0';
                        }
                    }

                } else {
                    if(errorDiv) {
                        errorDiv.textContent = twoFaResult.message || 'Error al cambiar 2FA.';
                        errorDiv.style.display = 'block';
                    }
                }
                
            } else {
                if(errorDiv) {
                    errorDiv.textContent = passResult.message || 'Error al verificar.';
                    errorDiv.style.display = 'block';
                }
            }
            
            toggleButtonSpinner(verifyTrigger, 'Confirmar', false);
            currentPassInput.value = ''; 
            return;
        }

        if (e.target.closest('#password-edit-trigger')) {
            e.preventDefault();
            const modal = document.getElementById('password-change-modal');
            if (!modal) return;

            modal.querySelector('[data-step="1"]').style.display = 'flex';
            modal.querySelector('[data-step="2"]').style.display = 'none';
            
            modal.querySelector('#password-verify-error').style.display = 'none';
            modal.querySelector('#password-update-error').style.display = 'none';

            modal.querySelector('#password-verify-current').value = '';
            modal.querySelector('#password-update-new').value = '';
            modal.querySelector('#password-update-confirm').value = '';
            
            modal.style.display = 'flex';
            focusInputAndMoveCursorToEnd(modal.querySelector('#password-verify-current'));
            return;
        }

        if (e.target.closest('#password-verify-close')) {
            e.preventDefault();
            const modal = document.getElementById('password-change-modal');
            if(modal) modal.style.display = 'none';
            return;
        }

        if (e.target.closest('#password-update-back')) {
            e.preventDefault();
            const modal = document.getElementById('password-change-modal');
            if (!modal) return;
            
            modal.querySelector('[data-step="1"]').style.display = 'flex';
            modal.querySelector('[data-step="2"]').style.display = 'none';
            modal.querySelector('#password-update-error').style.display = 'none';
            focusInputAndMoveCursorToEnd(modal.querySelector('#password-verify-current'));
            return;
        }
        
        if (e.target.closest('#password-verify-continue')) {
            e.preventDefault();
            const modal = document.getElementById('password-change-modal');
            const verifyTrigger = e.target.closest('#password-verify-continue');
            const errorDiv = document.getElementById('password-verify-error');
            const currentPassInput = document.getElementById('password-verify-current');

            if (!currentPassInput.value) {
                if(errorDiv) {
                    errorDiv.textContent = 'Por favor, introduce tu contraseña actual.';
                    errorDiv.style.display = 'block';
                }
                return;
            }
            
            toggleButtonSpinner(verifyTrigger, 'Continuar', true);
            if(errorDiv) errorDiv.style.display = 'none';

            const formData = new FormData();
            formData.append('action', 'verify-current-password');
            formData.append('current_password', currentPassInput.value);

            const result = await callSettingsApi(formData);

            if (result.success) {
                modal.querySelector('[data-step="1"]').style.display = 'none';
                modal.querySelector('[data-step="2"]').style.display = 'flex';
                focusInputAndMoveCursorToEnd(modal.querySelector('#password-update-new'));
            } else {
                if(errorDiv) {
                    errorDiv.textContent = result.message || 'Error al verificar.';
                    errorDiv.style.display = 'block';
                }
            }
            
            toggleButtonSpinner(verifyTrigger, 'Continuar', false);
            return;
        }

        if (e.target.closest('#password-update-save')) {
            e.preventDefault();
            const modal = document.getElementById('password-change-modal');
            const saveTrigger = e.target.closest('#password-update-save');
            const errorDiv = document.getElementById('password-update-error');
            const newPassInput = document.getElementById('password-update-new');
            const confirmPassInput = document.getElementById('password-update-confirm');

            if (newPassInput.value.length < 8 || newPassInput.value.length > 72) {
                if(errorDiv) {
                    errorDiv.textContent = 'La nueva contraseña debe tener entre 8 y 72 caracteres.';
                    errorDiv.style.display = 'block';
                }
                return;
            }
            if (newPassInput.value !== confirmPassInput.value) {
                if(errorDiv) {
                    errorDiv.textContent = 'Las nuevas contraseñas no coinciden.';
                    errorDiv.style.display = 'block';
                }
                return;
            }

            toggleButtonSpinner(saveTrigger, 'Guardar Contraseña', true);
            if(errorDiv) errorDiv.style.display = 'none';

            const formData = new FormData();
            formData.append('action', 'update-password');
            formData.append('new_password', newPassInput.value);
            formData.append('confirm_password', confirmPassInput.value);

            const result = await callSettingsApi(formData);

            if (result.success) {
                if(modal) modal.style.display = 'none';
                window.showAlert(result.message || 'Contraseña actualizada.', 'success');
            } else {
                if(errorDiv) {
                    errorDiv.textContent = result.message || 'Error al guardar.';
                    errorDiv.style.display = 'block';
                }
            }
            
            toggleButtonSpinner(saveTrigger, 'Guardar Contraseña', false);
            return;
        }

        
        
        if (e.target.closest('#logout-all-devices-trigger')) {
            e.preventDefault();
            const modal = document.getElementById('logout-all-modal');
            if(modal) {
                const dangerBtn = modal.querySelector('#logout-all-confirm');
                if(dangerBtn) {
                     toggleButtonSpinner(dangerBtn, 'Cerrar sesión', false);
                }
                modal.style.display = 'flex';
            }
            return;
        }

        if (e.target.closest('#logout-all-close') || e.target.closest('#logout-all-cancel')) {
            e.preventDefault();
            const modal = document.getElementById('logout-all-modal');
            if(modal) modal.style.display = 'none';
            return;
        }

        if (e.target.closest('#logout-all-confirm')) {
            e.preventDefault();
            const confirmButton = e.target.closest('#logout-all-confirm');
            
            toggleButtonSpinner(confirmButton, 'Cerrar sesión', true);

            const formData = new FormData();
            formData.append('action', 'logout-all-devices');
            
            const result = await callSettingsApi(formData);

            if (result.success) {
                window.showAlert('Se invalidaron las demás sesiones. Cerrando sesión actual...', 'success');
                
                setTimeout(() => {
                    const token = window.csrfToken || '';
                    const logoutUrl = (window.projectBasePath || '') + '/config/logout.php';
                    window.location.href = `${logoutUrl}?csrf_token=${encodeURIComponent(token)}`;
                }, 1500); 

            } else {
                window.showAlert(result.message || 'Error al cerrar las sesiones.', 'error');
                toggleButtonSpinner(confirmButton, 'Cerrar sesión', false);
            }
            return;
        }
        
        // --- ▼▼▼ INICIO DE MODIFICACIÓN DEL CONTROLADOR ▼▼▼ ---
        const clickedLink = e.target.closest('.module-trigger-select .menu-link');
        if (clickedLink) {
            e.preventDefault();
            
            const menuList = clickedLink.closest('.menu-list');
            const module = clickedLink.closest('.module-content[data-preference-type]'); 
            const wrapper = clickedLink.closest('.trigger-select-wrapper');
            const trigger = wrapper?.querySelector('.trigger-selector');
            const triggerTextEl = trigger?.querySelector('.trigger-select-text span');
            
            const newText = clickedLink.querySelector('.menu-link-text span')?.textContent;
            const newValue = clickedLink.dataset.value; 
            const prefType = module?.dataset.preferenceType; 

            if (!menuList || !module || !triggerTextEl || !newText || !newValue || !prefType) {
                 deactivateAllModules();
                return;
            }

            if (clickedLink.classList.contains('active')) {
                deactivateAllModules(); 
                return; 
            }

            triggerTextEl.textContent = newText;
            
            menuList.querySelectorAll('.menu-link').forEach(link => {
                link.classList.remove('active');
                
                // --- MODIFICADO: Apuntar al contenedor derecho ---
                const icon = link.querySelector('.menu-link-check-icon'); 
                if (icon) {
                    icon.innerHTML = ''; 
                }
            });
            
            clickedLink.classList.add('active');
            
            // --- MODIFICADO: Apuntar al contenedor derecho ---
            const iconContainer = clickedLink.querySelector('.menu-link-check-icon'); 
            if (iconContainer) {
                iconContainer.innerHTML = '<span class="material-symbols-rounded">check</span>';
            }
            
            deactivateAllModules(); 

            handlePreferenceChange(prefType, newValue); 
            
            return;
        }
        // --- ▲▲▲ FIN DE MODIFICACIÓN DEL CONTROLADOR ▲▲▲ ---


    });

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
        }

        if (e.target.id === 'username-form') {
            e.preventDefault();
            const usernameForm = e.target;
            const saveTrigger = document.getElementById('username-save-trigger');
            const inputElement = document.getElementById('username-input');

            if (inputElement.value.length < 6 || inputElement.value.length > 32) {
                window.showAlert('El nombre de usuario debe tener entre 6 y 32 caracteres.', 'error');
                return;
            }

            toggleButtonSpinner(saveTrigger, 'Guardar', true);

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

            if (newEmail.length > 255) {
                window.showAlert('El correo no puede tener más de 255 caracteres.', 'error');
                return;
            }
            
            const allowedDomains = /@(gmail\.com|outlook\.com|hotmail\.com|yahoo\.com|icloud\.com)$/i;
            if (!allowedDomains.test(newEmail)) {
                 window.showAlert('Solo se permiten correos @gmail, @outlook, @hotmail, @yahoo o @icloud.', 'error');
                return;
            }

            toggleButtonSpinner(saveTrigger, 'Guardar', true);

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
        }
    });

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

        
        else if (e.target.matches('input[type="checkbox"][data-preference-type="boolean"]')) {
            const checkbox = e.target;
            const fieldName = checkbox.dataset.fieldName;
            
            const newValue = checkbox.checked ? '1' : '0';

            if (fieldName) {
                handlePreferenceChange(fieldName, newValue);
            } else {
                console.error('Este toggle no tiene un data-field-name:', checkbox);
            }
        }
    });
    
    setTimeout(() => {
        const previewImage = document.getElementById('avatar-preview-image');
        if (previewImage && !previewImage.dataset.originalSrc) {
            previewImage.dataset.originalSrc = previewImage.src;
        }
    }, 100);
}
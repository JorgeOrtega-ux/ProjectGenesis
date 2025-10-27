import { callSettingsApi } from './api-service.js'; 
import { deactivateAllModules } from './main-controller.js';
import { getTranslation, loadTranslations, applyTranslations } from './i18n-manager.js';


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
        
        // Esta es la alerta genérica que se conservará.
        if (preferenceTypeOrField === 'language' || preferenceTypeOrField === 'theme' || preferenceTypeOrField === 'usage') {
             window.showAlert(result.message || getTranslation('js.settings.successPreference'), 'success');
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
            
            // --- ▼▼▼ INICIO DE LA MODIFICACIÓN ▼▼▼ ---
            // Se eliminó la alerta específica de idioma que estaba aquí.
            // window.showAlert(getTranslation('js.settings.successLang'), 'success'); // <-- ELIMINADO
            // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---

            // Actualizar la variable global
            window.userLanguage = newValue; 
            
            // Cargar nuevas traducciones y aplicarlas a toda la página
            await loadTranslations(newValue);
            applyTranslations(document.body); 
        }

    } else {
        window.showAlert(result.message || getTranslation('js.settings.errorPreference'), 'error');
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
            toggleButtonSpinner(removeTrigger, getTranslation('settings.profile.removePhoto'), true);

            const formData = new FormData(avatarForm);
            formData.append('action', 'remove-avatar');

            const result = await callSettingsApi(formData);
            
            if (result.success) {
                window.showAlert(result.message || getTranslation('js.settings.successAvatarRemoved'), 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showAvatarError(result.message || getTranslation('js.settings.errorAvatarRemove'));
                toggleButtonSpinner(removeTrigger, getTranslation('settings.profile.removePhoto'), false);
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
            toggleButtonSpinner(editTrigger, getTranslation('settings.profile.edit'), true);

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

                window.showAlert(getTranslation('js.settings.infoCodeSentCurrent'), 'info');
            } else {
                window.showAlert(result.message || getTranslation('js.settings.errorCodeRequest'), 'error');
            }
            
            toggleButtonSpinner(editTrigger, getTranslation('settings.profile.edit'), false);
            return;
        }

        if (e.target.closest('#email-verify-resend')) {
            e.preventDefault();
            const resendTrigger = e.target.closest('#email-verify-resend');
            
            if (resendTrigger.classList.contains('disabled-interactive')) return;
            
            resendTrigger.classList.add('disabled-interactive');
            const originalText = resendTrigger.textContent;
            resendTrigger.textContent = getTranslation('js.settings.sending');
            
            const formData = new FormData();
            formData.append('action', 'request-email-change-code');
            
            const result = await callSettingsApi(formData);

            if (result.success) {
                window.showAlert(getTranslation('js.settings.successCodeResent'), 'success');
            } else {
                window.showAlert(result.message || getTranslation('js.settings.errorCodeResent'), 'error');
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
                    modalError.textContent = getTranslation('js.settings.errorEnterCode');
                    modalError.style.display = 'block';
                }
                return;
            }

            toggleButtonSpinner(continueTrigger, getTranslation('settings.profile.continue'), true);
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
                window.showAlert(result.message || getTranslation('js.settings.successVerification'), 'success');
            } else {
                if(modalError) {
                    modalError.textContent = result.message || getTranslation('js.settings.errorVerification');
                    modalError.style.display = 'block';
                }
            }
            
            toggleButtonSpinner(continueTrigger, getTranslation('settings.profile.continue'), false);
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
                window.showAlert(getTranslation('js.settings.errorModalNotFound'), 'error');
                return;
            }

            const isCurrentlyEnabled = toggleButton.dataset.isEnabled === '1';

            const modalTitle = document.getElementById('tfa-modal-title');
            const modalText = document.getElementById('tfa-modal-text');
            const errorDiv = document.getElementById('tfa-verify-error');
            const passInput = document.getElementById('tfa-verify-password');

            if (!isCurrentlyEnabled) {
                if(modalTitle) modalTitle.textContent = getTranslation('js.settings.modal2faTitleEnable');
                if(modalText) modalText.textContent = getTranslation('js.settings.modal2faDescEnable');
            } else {
                if(modalTitle) modalTitle.textContent = getTranslation('js.settings.modal2faTitleDisable');
                if(modalText) modalText.textContent = getTranslation('js.settings.modal2faDescDisable');
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
                    errorDiv.textContent = getTranslation('js.settings.errorEnterCurrentPass');
                    errorDiv.style.display = 'block';
                }
                return;
            }
            
            toggleButtonSpinner(verifyTrigger, getTranslation('settings.login.confirm'), true);
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
                        if (statusText) statusText.textContent = getTranslation('settings.login.2faEnabled');
                        if (toggleButton) {
                            toggleButton.textContent = getTranslation('settings.login.disable');
                            toggleButton.classList.add('danger');
                            toggleButton.dataset.isEnabled = '1';
                        }
                    } else {
                        if (statusText) statusText.textContent = getTranslation('settings.login.2faDisabled');
                        if (toggleButton) {
                            toggleButton.textContent = getTranslation('settings.login.enable');
                            toggleButton.classList.remove('danger');
                            toggleButton.dataset.isEnabled = '0';
                        }
                    }

                } else {
                    if(errorDiv) {
                        errorDiv.textContent = twoFaResult.message || getTranslation('js.settings.error2faToggle');
                        errorDiv.style.display = 'block';
                    }
                }
                
            } else {
                if(errorDiv) {
                    errorDiv.textContent = passResult.message || getTranslation('js.settings.errorVerification');
                    errorDiv.style.display = 'block';
                }
            }
            
            toggleButtonSpinner(verifyTrigger, getTranslation('settings.login.confirm'), false);
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
                    errorDiv.textContent = getTranslation('js.settings.errorEnterCurrentPass');
                    errorDiv.style.display = 'block';
                }
                return;
            }
            
            toggleButtonSpinner(verifyTrigger, getTranslation('settings.profile.continue'), true);
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
                    errorDiv.textContent = result.message || getTranslation('js.settings.errorVerification');
                    errorDiv.style.display = 'block';
                }
            }
            
            toggleButtonSpinner(verifyTrigger, getTranslation('settings.profile.continue'), false);
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
                    errorDiv.textContent = getTranslation('js.settings.errorNewPassLength');
                    errorDiv.style.display = 'block';
                }
                return;
            }
            if (newPassInput.value !== confirmPassInput.value) {
                if(errorDiv) {
                    errorDiv.textContent = getTranslation('js.settings.errorNewPassMismatch');
                    errorDiv.style.display = 'block';
                }
                return;
            }

            toggleButtonSpinner(saveTrigger, getTranslation('settings.login.savePassword'), true);
            if(errorDiv) errorDiv.style.display = 'none';

            const formData = new FormData();
            formData.append('action', 'update-password');
            formData.append('new_password', newPassInput.value);
            formData.append('confirm_password', confirmPassInput.value);

            const result = await callSettingsApi(formData);

            if (result.success) {
                if(modal) modal.style.display = 'none';
                window.showAlert(result.message || getTranslation('js.settings.successPassUpdate'), 'success');
            } else {
                if(errorDiv) {
                    errorDiv.textContent = result.message || getTranslation('js.settings.errorSaving');
                    errorDiv.style.display = 'block';
                }
            }
            
            toggleButtonSpinner(saveTrigger, getTranslation('settings.login.savePassword'), false);
            return;
        }

        
        
        if (e.target.closest('#logout-all-devices-trigger')) {
            e.preventDefault();
            const modal = document.getElementById('logout-all-modal');
            if(modal) {
                const dangerBtn = modal.querySelector('#logout-all-confirm');
                if(dangerBtn) {
                     toggleButtonSpinner(dangerBtn, getTranslation('settings.devices.invalidateButton'), false);
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
            
            toggleButtonSpinner(confirmButton, getTranslation('settings.devices.invalidateButton'), true);

            const formData = new FormData();
            formData.append('action', 'logout-all-devices');
            
            const result = await callSettingsApi(formData);

            if (result.success) {
                window.showAlert(getTranslation('js.settings.infoLogoutAll'), 'success');
                
                setTimeout(() => {
                    const token = window.csrfToken || '';
                    const logoutUrl = (window.projectBasePath || '') + '/config/logout.php';
                    window.location.href = `${logoutUrl}?csrf_token=${encodeURIComponent(token)}`;
                }, 1500); 

            } else {
                window.showAlert(result.message || getTranslation('js.settings.errorLogoutAll'), 'error');
                toggleButtonSpinner(confirmButton, getTranslation('settings.devices.invalidateButton'), false);
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
                showAvatarError(getTranslation('js.settings.errorAvatarSelect'));
                return;
            }

            toggleButtonSpinner(saveTrigger, getTranslation('settings.profile.save'), true);

            const formData = new FormData(avatarForm);
            formData.append('action', 'upload-avatar');

            const result = await callSettingsApi(formData);
            
            if (result.success) {
                window.showAlert(result.message || getTranslation('js.settings.successAvatarUpdate'), 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showAvatarError(result.message || getTranslation('js.settings.errorSaveUnknown'));
                toggleButtonSpinner(saveTrigger, getTranslation('settings.profile.save'), false);
            }
        }

        if (e.target.id === 'username-form') {
            e.preventDefault();
            const usernameForm = e.target;
            const saveTrigger = document.getElementById('username-save-trigger');
            const inputElement = document.getElementById('username-input');

            if (inputElement.value.length < 6 || inputElement.value.length > 32) {
                window.showAlert(getTranslation('js.auth.errorUsernameLength'), 'error');
                return;
            }

            toggleButtonSpinner(saveTrigger, getTranslation('settings.profile.save'), true);

            const formData = new FormData(usernameForm);
            formData.append('action', 'update-username'); 

            const result = await callSettingsApi(formData);
            
            if (result.success) {
                window.showAlert(result.message || getTranslation('js.settings.successUsernameUpdate'), 'success');
                setTimeout(() => location.reload(), 1500); 
            } else {
                window.showAlert(result.message || getTranslation('js.settings.errorSaveUnknown'), 'error');
                toggleButtonSpinner(saveTrigger, getTranslation('settings.profile.save'), false);
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
                window.showAlert(getTranslation('js.auth.errorInvalidEmail'), 'error');
                return;
            }

            if (newEmail.length > 255) {
                window.showAlert(getTranslation('js.auth.errorEmailLength'), 'error');
                return;
            }
            
            const allowedDomains = /@(gmail\.com|outlook\.com|hotmail\.com|yahoo\.com|icloud\.com)$/i;
            if (!allowedDomains.test(newEmail)) {
                 window.showAlert(getTranslation('js.auth.errorEmailDomain'), 'error');
                return;
            }

            toggleButtonSpinner(saveTrigger, getTranslation('settings.profile.save'), true);

            const formData = new FormData(emailForm);
            formData.append('action', 'update-email'); 

            const result = await callSettingsApi(formData);
            
            if (result.success) {
                window.showAlert(result.message || getTranslation('js.settings.successEmailUpdate'), 'success');
                setTimeout(() => location.reload(), 1500); 
            } else {
                window.showAlert(result.message || getTranslation('js.settings.errorSaveUnknown'), 'error');
                toggleButtonSpinner(saveTrigger, getTranslation('settings.profile.save'), false);
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
                showAvatarError(getTranslation('js.settings.errorAvatarFormat'));
                fileInput.form.reset();
                return;
            }
            if (file.size > 2 * 1024 * 1024) {
                showAvatarError(getTranslation('js.settings.errorAvatarSize'));
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
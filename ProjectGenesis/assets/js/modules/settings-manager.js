import { callSettingsApi }  from '../services/api-service.js';
import { deactivateAllModules }  from '../app/main-controller.js';
import { getTranslation, loadTranslations, applyTranslations } from '../services/i18n-manager.js';
import { startResendTimer } from './auth-manager.js';

let isPreferenceLocked = false;
let preferenceLockoutTimer = null;

function showInlineError(cardElement, messageKey, data = null) {
    if (!cardElement) return;

    hideInlineError(cardElement); 

    const errorDiv = document.createElement('div');
    errorDiv.className = 'component-card__error'; // MODIFICADO
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
    if (nextElement && nextElement.classList.contains('component-card__error')) { // MODIFICADO
        nextElement.remove();
    }
}

function toggleButtonSpinner(button, text, isLoading) {
    if (!button) return;
    button.disabled = isLoading;
    if (isLoading) {
        button.dataset.originalText = button.textContent;
        const spinnerClass = (button.classList.contains('modal__button-small') || button.classList.contains('component-button')) ? 'logout-spinner' : 'auth-button-spinner'; // MODIFICADO
        let spinnerStyle = (button.classList.contains('modal__button-small') || button.classList.contains('component-button')) ? 'width: 20px; height: 20px; border-width: 2px; margin: 0 auto; border-top-color: inherit;' : ''; // MODIFICADO
        
        if (button.classList.contains('modal__button-small--primary') || 
            button.classList.contains('modal__button-small--danger') || 
            (button.classList.contains('component-button') && !button.classList.contains('danger'))) { // MODIFICADO
            
            if(button.classList.contains('component-button') && button.classList.contains('danger')) { // MODIFICADO
            } else {
                 spinnerStyle += ' border-top-color: #ffffff; border-left-color: #ffffff20; border-bottom-color: #ffffff20; border-right-color: #ffffff20;';
            }
        }

        if(spinnerClass === 'logout-spinner') {
            button.innerHTML = `<span class="${spinnerClass}" style="${spinnerStyle}"></span>`;
        } else {
            button.innerHTML = `<div class="${spinnerClass}"></div>`;
        }
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
            } catch (e) {
            }
            inputElement.type = originalType;
        }, 0);

    } catch (e) {
        inputElement.type = originalType;
    }
}

function formatTimestampToSimpleDate(utcTimestamp) {
    try {
        const date = new Date(utcTimestamp + 'Z'); 
        if (isNaN(date.getTime())) {
            throw new Error('Invalid date');
        }
        
        const day = String(date.getUTCDate()).padStart(2, '0');
        const month = String(date.getUTCMonth() + 1).padStart(2, '0'); 
        const year = date.getUTCFullYear();
        
        return `${day}/${month}/${year}`;
    } catch (e) {
        console.error('Error al formatear la fecha:', e);
        return 'fecha inválida';
    }
}


async function handlePreferenceChange(preferenceTypeOrField, newValue, cardElement) {
    
    if (isPreferenceLocked) {
        showInlineError(cardElement, 'js.api.genericSpamError'); 
        return; 
    }

    if (!preferenceTypeOrField || newValue === undefined || !cardElement) {
        console.error('handlePreferenceChange: Faltan tipo/campo, valor o elemento de tarjeta.');
        return;
    }

    hideInlineError(cardElement);

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
            window.userLanguage = newValue;
            
            await loadTranslations(newValue);
            
            applyTranslations(document.body);
            
            window.showAlert(getTranslation('js.settings.successPreference'), 'success');

        } else {
             window.showAlert(getTranslation(result.message || 'js.settings.successPreference'), 'success');
        }

    } else {
        if (result.message === 'js.auth.errorTooManyAttempts') {
            isPreferenceLocked = true;
            showInlineError(cardElement, 'js.api.genericSpamError'); 

            if (preferenceLockoutTimer) {
                clearTimeout(preferenceLockoutTimer);
            }

            const lockoutDuration = (result.data?.minutes || 60) * 60 * 1000;
            preferenceLockoutTimer = setTimeout(() => {
                isPreferenceLocked = false;
            }, lockoutDuration);
            
        } else {
            showInlineError(cardElement, result.message || 'js.settings.errorPreference');
        }
    }
}

function getCsrfTokenFromPage() {
    const csrfInput = document.querySelector('input[name="csrf_token"]');
    return csrfInput ? csrfInput.value : (window.csrfToken || ''); 
}

export function initSettingsManager() {

    document.body.addEventListener('click', async (e) => {
        const target = e.target;
        const card = target.closest('.component-card'); // MODIFICADO

        const avatarCard = document.getElementById('avatar-section');
        if (avatarCard) {
             if (target.closest('#avatar-preview-container') || target.closest('#avatar-upload-trigger') || target.closest('#avatar-change-trigger')) {
                e.preventDefault();
                hideInlineError(avatarCard); 
                document.getElementById('avatar-upload-input')?.click();
                return;
            }

            if (target.closest('#avatar-cancel-trigger')) {
                e.preventDefault();
                hideInlineError(avatarCard); 
                const previewImage = document.getElementById('avatar-preview-image');
                const originalAvatarSrc = previewImage.dataset.originalSrc;
                if (previewImage && originalAvatarSrc) previewImage.src = originalAvatarSrc;
                document.getElementById('avatar-upload-input').value = ''; 

                document.getElementById('avatar-actions-preview').style.display = 'none';
                const originalState = avatarCard.dataset.originalActions === 'default'
                    ? 'avatar-actions-default'
                    : 'avatar-actions-custom';
                document.getElementById(originalState).style.display = 'flex';
                return;
            }

            if (target.closest('#avatar-remove-trigger')) {
                e.preventDefault();
                hideInlineError(avatarCard); 
                const removeTrigger = target.closest('#avatar-remove-trigger');
                toggleButtonSpinner(removeTrigger, getTranslation('settings.profile.removePhoto'), true);

                const formData = new FormData();
                formData.append('action', 'remove-avatar');
                formData.append('csrf_token', getCsrfTokenFromPage()); 

                const result = await callSettingsApi(formData);

                if (result.success) {
                    window.showAlert(getTranslation(result.message || 'js.settings.successAvatarRemoved'), 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showInlineError(avatarCard, result.message || 'js.settings.errorAvatarRemove');
                    toggleButtonSpinner(removeTrigger, getTranslation('settings.profile.removePhoto'), false);
                }
                return;
            }

            if (target.closest('#avatar-save-trigger-btn')) {
                 e.preventDefault();
                const fileInput = document.getElementById('avatar-upload-input');
                const saveTrigger = target.closest('#avatar-save-trigger-btn');

                hideInlineError(avatarCard);

                if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                    showInlineError(avatarCard, 'js.settings.errorAvatarSelect');
                    return;
                }

                toggleButtonSpinner(saveTrigger, getTranslation('settings.profile.save'), true);

                const formData = new FormData();
                formData.append('action', 'upload-avatar');
                formData.append('avatar', fileInput.files[0]);
                formData.append('csrf_token', getCsrfTokenFromPage()); 

                const result = await callSettingsApi(formData);

                if (result.success) {
                    window.showAlert(getTranslation(result.message || 'js.settings.successAvatarUpdate'), 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showInlineError(avatarCard, result.message || 'js.settings.errorSaveUnknown');
                    toggleButtonSpinner(saveTrigger, getTranslation('settings.profile.save'), false);
                }
                return;
            }
        }

        const usernameCard = document.getElementById('username-section');
        if (usernameCard) {
            hideInlineError(usernameCard);

            if (target.closest('#username-edit-trigger')) {
                e.preventDefault();
                document.getElementById('username-view-state').style.display = 'none';
                document.getElementById('username-actions-view').style.display = 'none';
                document.getElementById('username-edit-state').style.display = 'flex';
                document.getElementById('username-actions-edit').style.display = 'flex';
                focusInputAndMoveCursorToEnd(document.getElementById('username-input'));
                return;
            }

            if (target.closest('#username-cancel-trigger')) {
                e.preventDefault();
                const displayElement = document.getElementById('username-display-text');
                const inputElement = document.getElementById('username-input');
                if (displayElement && inputElement) inputElement.value = displayElement.dataset.originalUsername;
                document.getElementById('username-edit-state').style.display = 'none';
                document.getElementById('username-actions-edit').style.display = 'none';
                document.getElementById('username-view-state').style.display = 'flex';
                document.getElementById('username-actions-view').style.display = 'flex';
                return;
            }
             if (target.closest('#username-save-trigger-btn')) {
                e.preventDefault();
                const saveTrigger = target.closest('#username-save-trigger-btn');
                const inputElement = document.getElementById('username-input');
                const actionInput = usernameCard.querySelector('[name="action"]');

                if (inputElement.value.length < 6 || inputElement.value.length > 32) {
                    showInlineError(usernameCard, 'js.auth.errorUsernameLength', { min: 6, max: 32 });
                    return;
                }

                toggleButtonSpinner(saveTrigger, getTranslation('settings.profile.save'), true);

                const formData = new FormData();
                formData.append('action', actionInput.value);
                formData.append('username', inputElement.value);
                formData.append('csrf_token', getCsrfTokenFromPage()); 

                const result = await callSettingsApi(formData);

                if (result.success) {
                    window.showAlert(getTranslation(result.message || 'js.settings.successUsernameUpdate'), 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showInlineError(usernameCard, result.message || 'js.settings.errorSaveUnknown', result.data);
                    toggleButtonSpinner(saveTrigger, getTranslation('settings.profile.save'), false);
                }
                 return;
            }
        }

        const emailCard = document.getElementById('email-section');
        if (emailCard) {
            hideInlineError(emailCard);
        }
        
        if (target.closest('#email-verify-resend')) {
            e.preventDefault();
            const resendTrigger = target.closest('#email-verify-resend');
            const card = resendTrigger.closest('.component-card'); // MODIFICADO
            if (!card) return; 

            if (resendTrigger.classList.contains('disabled-interactive')) return;

            startResendTimer(resendTrigger, 60);
            hideInlineError(card);

            const formData = new FormData();
            formData.append('action', 'request-email-change-code');
            formData.append('csrf_token', getCsrfTokenFromPage());

            const result = await callSettingsApi(formData);

            if (result.success) {
                window.showAlert(getTranslation('js.settings.successCodeResent'), 'success');
            } else {
                showInlineError(card, result.message || 'js.settings.errorCodeResent', result.data);
                
                const timerId = resendTrigger.dataset.timerId;
                if (timerId) clearInterval(timerId);
                const originalBaseText = getTranslation('settings.profile.modalCodeResendA');
                resendTrigger.textContent = originalBaseText;
                resendTrigger.classList.remove('disabled-interactive');
            }
            return;
        }

        if (target.closest('#email-verify-continue')) {
            e.preventDefault();
            const continueTrigger = target.closest('#email-verify-continue');
            const card = continueTrigger.closest('.component-card'); // MODIFICADO
            if (!card) return;
            const modalInput = document.getElementById('email-verify-code');

            hideInlineError(card);

            if (!modalInput || !modalInput.value) {
                showInlineError(card, 'js.settings.errorEnterCode');
                return;
            }

            toggleButtonSpinner(continueTrigger, getTranslation('settings.profile.continue'), true);
            
            const formData = new FormData();
            formData.append('action', 'verify-email-change-code');
            formData.append('verification_code', modalInput.value);
            formData.append('csrf_token', getCsrfTokenFromPage());

            const result = await callSettingsApi(formData);

            if (result.success) {
                card.style.display = 'none';
                const step2Card = document.getElementById('email-step-2-update');
                if (step2Card) {
                    step2Card.style.display = 'flex';
                    focusInputAndMoveCursorToEnd(document.getElementById('email-input-new'));
                }
                window.showAlert(getTranslation(result.message || 'js.settings.successVerification'), 'success');
            } else {
                showInlineError(card, result.message || 'js.settings.errorVerification');
            }
            toggleButtonSpinner(continueTrigger, getTranslation('settings.profile.continue'), false);
            return;
        }
        
        if (target.closest('#email-save-trigger-btn')) {
            e.preventDefault();
            const saveTrigger = target.closest('#email-save-trigger-btn');
            const card = saveTrigger.closest('.component-card'); // MODIFICADO
            if (!card) return;
            
            const inputElement = document.getElementById('email-input-new'); 
            const newEmail = inputElement.value;

            hideInlineError(card); 

            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(newEmail)) {
                showInlineError(card, 'js.auth.errorInvalidEmail'); return;
            }
            if (newEmail.length > 255) {
                showInlineError(card, 'js.auth.errorEmailLength'); return;
            }
            const allowedDomains = /@(gmail\.com|outlook\.com|hotmail\.com|yahoo\.com|icloud\.com)$/i;
            if (!allowedDomains.test(newEmail)) {
                showInlineError(card, 'js.auth.errorEmailDomain'); return;
            }

            toggleButtonSpinner(saveTrigger, getTranslation('settings.profile.save'), true);

            const formData = new FormData();
            formData.append('action', 'update-email'); 
            formData.append('email', newEmail);
            formData.append('csrf_token', getCsrfTokenFromPage());

            const result = await callSettingsApi(formData);

            if (result.success) {
                window.showAlert(getTranslation(result.message || 'js.settings.successEmailUpdate'), 'success');

                setTimeout(() => {
                    const link = document.createElement('a');
                    link.href = window.projectBasePath + '/settings/your-profile';
                    link.setAttribute('data-nav-js', 'true');
                    document.body.appendChild(link);
                    link.click();
                    link.remove();
                }, 1500);

            } else {
                showInlineError(card, result.message || 'js.settings.errorSaveUnknown', result.data);
                toggleButtonSpinner(saveTrigger, getTranslation('settings.profile.save'), false);
            }
            return;
        }


        const clickedLink = target.closest('.popover-module .menu-link'); 
        if (clickedLink && card) { 
            e.preventDefault();
            hideInlineError(card); 

            const menuList = clickedLink.closest('.menu-list');
            const module = clickedLink.closest('.popover-module[data-preference-type]'); 
            const wrapper = card.querySelector('.trigger-select-wrapper'); 
            const trigger = wrapper?.querySelector('.trigger-selector');
            const triggerTextEl = trigger?.querySelector('.trigger-select-text span');
            const triggerIconEl = trigger?.querySelector('.trigger-select-icon span');

            const newTextKey = clickedLink.querySelector('.menu-link-text span')?.getAttribute('data-i18n');
            const newValue = clickedLink.dataset.value;
            const prefType = module?.dataset.preferenceType;
            const newIconName = clickedLink.querySelector('.menu-link-icon span')?.textContent;


            if (!menuList || !module || !wrapper || !trigger || !triggerTextEl || !newTextKey || !newValue || !prefType || !triggerIconEl) { 
                 console.error("Error finding elements for preference change", {menuList, module, wrapper, trigger, triggerTextEl, newTextKey, newValue, prefType, triggerIconEl});
                 deactivateAllModules();
                return;
            }

            if (clickedLink.classList.contains('active')) {
                deactivateAllModules();
                return;
            }

             triggerTextEl.setAttribute('data-i18n', newTextKey);
             triggerTextEl.textContent = getTranslation(newTextKey);
             triggerIconEl.textContent = newIconName;


            menuList.querySelectorAll('.menu-link').forEach(link => {
                link.classList.remove('active');
                const icon = link.querySelector('.menu-link-check-icon');
                if (icon) icon.innerHTML = '';
            });
            clickedLink.classList.add('active');
            const iconContainer = clickedLink.querySelector('.menu-link-check-icon');
            if (iconContainer) iconContainer.innerHTML = '<span class="material-symbols-rounded">check</span>';

            deactivateAllModules();

            trigger.classList.add('disabled-interactive'); 
            try {
                await handlePreferenceChange(prefType, newValue, card);
            } catch (error) {
                console.error("Error during preference change:", error); 
            } finally {
                trigger.classList.remove('disabled-interactive'); 
            }

            return; 
        }


         if (target.closest('#tfa-verify-continue')) {
             e.preventDefault();
                const card = target.closest('.component-card'); // MODIFICADO
                if (!card) return; 

                const verifyTrigger = target.closest('#tfa-verify-continue');
                const currentPassInput = document.getElementById('tfa-verify-password');
                
                hideInlineError(card);

                if (!currentPassInput || !currentPassInput.value) {
                    showInlineError(card, 'js.settings.errorEnterCurrentPass');
                    return;
                }

                toggleButtonSpinner(verifyTrigger, getTranslation('settings.login.confirm'), true);

                const passFormData = new FormData();
                passFormData.append('action', 'verify-current-password');
                passFormData.append('current_password', currentPassInput.value);
                 passFormData.append('csrf_token', getCsrfTokenFromPage()); 

                const passResult = await callSettingsApi(passFormData);

                if (passResult.success) {
                    const twoFaFormData = new FormData();
                    twoFaFormData.append('action', 'toggle-2fa');
                    twoFaFormData.append('csrf_token', getCsrfTokenFromPage()); 

                    const twoFaResult = await callSettingsApi(twoFaFormData);

                    if (twoFaResult.success) {
                        window.showAlert(getTranslation(twoFaResult.message), 'success');

                        setTimeout(() => {
                            const link = document.createElement('a');
                            link.href = window.projectBasePath + '/settings/login-security';
                            link.setAttribute('data-nav-js', 'true');
                            document.body.appendChild(link);
                            link.click();
                            link.remove();
                        }, 1500);

                    } else {
                        showInlineError(card, twoFaResult.message || 'js.settings.error2faToggle');
                    }

                } else {
                    showInlineError(card, passResult.message || 'js.settings.errorVerification');
                }

                toggleButtonSpinner(verifyTrigger, getTranslation('settings.login.confirm'), false);
                if(currentPassInput) currentPassInput.value = '';
            return;
        }


         if (target.closest('#password-verify-continue')) {
            e.preventDefault();
            const step1Card = target.closest('#password-step-1');
            if (!step1Card) return; 
            
            const verifyTrigger = target.closest('#password-verify-continue');
            const currentPassInput = document.getElementById('password-verify-current'); 

            hideInlineError(step1Card); 

            if (!currentPassInput || !currentPassInput.value) {
                showInlineError(step1Card, 'js.settings.errorEnterCurrentPass');
                return;
            }

            toggleButtonSpinner(verifyTrigger, getTranslation('settings.profile.continue'), true);

            const formData = new FormData();
            formData.append('action', 'verify-current-password');
            formData.append('current_password', currentPassInput.value);
            formData.append('csrf_token', getCsrfTokenFromPage()); 

            const result = await callSettingsApi(formData);

            if (result.success) {
                step1Card.style.display = 'none';
                const step2Card = document.getElementById('password-step-2');
                if (step2Card) {
                    step2Card.style.display = 'flex'; 
                    focusInputAndMoveCursorToEnd(document.getElementById('password-update-new'));
                }
            } else {
                showInlineError(step1Card, result.message || 'js.settings.errorVerification', result.data);
            }

            toggleButtonSpinner(verifyTrigger, getTranslation('settings.profile.continue'), false);
            return;
        }

         if (target.closest('#password-update-save')) {
            e.preventDefault();
             const step2Card = target.closest('#password-step-2');
             if (!step2Card) return; 

            const saveTrigger = target.closest('#password-update-save');
            const newPassInput = document.getElementById('password-update-new'); 
            const confirmPassInput = document.getElementById('password-update-confirm'); 

            if (!newPassInput || !confirmPassInput) return;

            hideInlineError(step2Card); 

             if (newPassInput.value.length < 8 || newPassInput.value.length > 72) {
                showInlineError(step2Card, 'js.auth.errorPasswordLength', {min: 8, max: 72});
                 return;
             }
             if (newPassInput.value !== confirmPassInput.value) {
                showInlineError(step2Card, 'js.auth.errorPasswordMismatch');
                 return;
             }

            toggleButtonSpinner(saveTrigger, getTranslation('settings.login.savePassword'), true);

            const formData = new FormData();
            formData.append('action', 'update-password');
            formData.append('new_password', newPassInput.value);
            formData.append('confirm_password', confirmPassInput.value);
            formData.append('csrf_token', getCsrfTokenFromPage()); 


            const result = await callSettingsApi(formData);

            if (result.success) {
                window.showAlert(getTranslation(result.message || 'js.settings.successPassUpdate'), 'success');

                setTimeout(() => {
                    const link = document.createElement('a');
                    link.href = window.projectBasePath + '/settings/login-security';
                    link.setAttribute('data-nav-js', 'true'); 
                    document.body.appendChild(link);
                    link.click();
                    link.remove();
                }, 1500);

            } else {
                showInlineError(step2Card, result.message || 'js.settings.errorSaving', result.data);
                toggleButtonSpinner(saveTrigger, getTranslation('settings.login.savePassword'), false);
            }

            return;
        }


         if (target.closest('#logout-all-devices-trigger')) {
            e.preventDefault();
            const modal = document.getElementById('logout-all-modal');
            if(modal) {
                const dangerBtn = modal.querySelector('#logout-all-confirm');
                if(dangerBtn) {
                     toggleButtonSpinner(dangerBtn, getTranslation('settings.devices.modalConfirm'), false);
                }
                modal.style.display = 'flex';
            }
            return;
        }
         if (target.closest('#logout-all-cancel')) {
            e.preventDefault();
            const modal = document.getElementById('logout-all-modal');
            if(modal) modal.style.display = 'none';
            return;
        }
        if (target.closest('#logout-all-confirm')) {
             e.preventDefault();
             const confirmButton = target.closest('#logout-all-confirm');
             if(!confirmButton) return; 

            toggleButtonSpinner(confirmButton, getTranslation('settings.devices.modalConfirm'), true);

            const formData = new FormData();
            formData.append('action', 'logout-all-devices');
            formData.append('csrf_token', getCsrfTokenFromPage()); 

            const result = await callSettingsApi(formData);

            if (result.success) {
                window.showAlert(getTranslation('js.settings.infoLogoutAll'), 'success');

                setTimeout(() => {
                    const token = getCsrfTokenFromPage(); 
                    const logoutUrl = (window.projectBasePath || '') + '/config/logout.php';
                    window.location.href = `${logoutUrl}?csrf_token=${encodeURIComponent(token)}`;
                }, 1500);

            } else {
                window.showAlert(getTranslation(result.message || 'js.settings.errorLogoutAll'), 'error');
                toggleButtonSpinner(confirmButton, getTranslation('settings.devices.modalConfirm'), false);
            }
            return; 
        }


         
        

        if (target.closest('#delete-account-confirm')) {
             e.preventDefault();
             const confirmButton = target.closest('#delete-account-confirm');
             const card = target.closest('.component-card'); // MODIFICADO
             if(!confirmButton || !card) return; 
            
            const passwordInput = document.getElementById('delete-account-password');
            
            hideInlineError(card); 

            if (!passwordInput || !passwordInput.value) {
                showInlineError(card, 'js.settings.errorEnterCurrentPass'); 
                return;
            }

            toggleButtonSpinner(confirmButton, getTranslation('settings.login.modalDeleteConfirm'), true);

            const formData = new FormData();
            formData.append('action', 'delete-account');
            formData.append('current_password', passwordInput.value);
            formData.append('csrf_token', getCsrfTokenFromPage()); 

            const result = await callSettingsApi(formData);

            if (result.success) {
                window.showAlert(getTranslation(result.message || 'js.settings.successAccountDeleted'), 'success');
                setTimeout(() => {
                    window.location.href = (window.projectBasePath || '') + '/login';
                }, 2000);

            } else {
                showInlineError(card, result.message || 'js.settings.errorAccountDelete', result.data); 
                toggleButtonSpinner(confirmButton, getTranslation('settings.login.modalDeleteConfirm'), false);
            }
            return;
        }

    }); 


    document.body.addEventListener('change', async (e) => {
        const target = e.target;
        const card = target.closest('.component-card'); // MODIFICADO

        if (target.id === 'avatar-upload-input' && card) {
            hideInlineError(card);
            const fileInput = target;
            const previewImage = document.getElementById('avatar-preview-image');
            const file = fileInput.files[0];

            if (!file) return;

            if (!['image/png', 'image/jpeg', 'image/gif', 'image/webp'].includes(file.type)) {
                showInlineError(card, 'js.settings.errorAvatarFormat');
                fileInput.value = ''; 
                return;
            }
            if (file.size > 2 * 1024 * 1024) { 
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

            const actionsDefault = document.getElementById('avatar-actions-default');
            const avatarCard = document.getElementById('avatar-section'); 
            avatarCard.dataset.originalActions = (actionsDefault.style.display !== 'none') ? 'default' : 'custom';

            document.getElementById('avatar-actions-default').style.display = 'none';
            document.getElementById('avatar-actions-custom').style.display = 'none';
            document.getElementById('avatar-actions-preview').style.display = 'flex';
        }

        else if (target.matches('input[type="checkbox"][data-preference-type="boolean"]') && card) {
             hideInlineError(card);
            const checkbox = target;
            const fieldName = checkbox.dataset.fieldName;
            const newValue = checkbox.checked ? '1' : '0';

            if (fieldName) {
                checkbox.disabled = true; 
                try {
                    await handlePreferenceChange(fieldName, newValue, card);
                } catch (error) {
                    console.error("Error during toggle preference change:", error);
                } finally {
                    checkbox.disabled = false; 
                }
            } else {
                console.error('Este toggle no tiene un data-field-name:', checkbox);
            }
        }
    }); 

    document.body.addEventListener('input', (e) => {
        const target = e.target;

        if (target.id === 'delete-account-password') {
            const confirmBtn = document.getElementById('delete-account-confirm');
            if (confirmBtn) {
                confirmBtn.disabled = !target.value.trim();
            }
        }


        if (target.matches('.component-text-input') || target.closest('.auth-input-group') || target.closest('.modal__input-group') || target.closest('.component-input-group')) { // MODIFICADO
            const card = target.closest('.component-card'); // MODIFICADO
            if (card) {
                hideInlineError(card);
            }
            const modalContent = target.closest('.modal-content');
            if (modalContent) {
                 const errorDiv = modalContent.querySelector('.auth-error-message, .component-card__error'); // MODIFICADO
                 if (errorDiv) {
                    if(errorDiv.classList.contains('auth-error-message')) {
                         errorDiv.style.display = 'none';
                    } else {
                         errorDiv.remove(); 
                    }
                 }
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
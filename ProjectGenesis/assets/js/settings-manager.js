import { callSettingsApi } from './api-service.js';
import { deactivateAllModules } from './main-controller.js';
import { getTranslation, loadTranslations, applyTranslations } from './i18n-manager.js';
import { startResendTimer } from './auth-manager.js';

// --- ▼▼▼ INICIO DE LA MODIFICACIÓN (RATE LIMIT) ▼▼▼ ---
let isPreferenceLocked = false;
let preferenceLockoutTimer = null;
// --- ▲▲▲ FIN DE LA MODIFICACIÓN (RATE LIMIT) ▲▲▲ ---

// --- (Helper functions showInlineError, hideInlineError, toggleButtonSpinner, focusInputAndMoveCursorToEnd remain the same) ---
/**
 * Muestra un mensaje de error inline debajo de un elemento .settings-card.
 * @param {HTMLElement} cardElement El elemento .settings-card debajo del cual mostrar el error.
 * @param {string} messageKey La clave de traducción para el mensaje de error.
 * @param {object|null} data Datos opcionales para reemplazar placeholders en la traducción (ej. { days: 5 }).
 */
function showInlineError(cardElement, messageKey, data = null) {
    if (!cardElement) return;

    hideInlineError(cardElement); // Elimina errores previos para esta tarjeta

    const errorDiv = document.createElement('div');
    errorDiv.className = 'settings-card__error';
    let message = getTranslation(messageKey);

    // Reemplazar placeholders si hay datos
    if (data) {
        Object.keys(data).forEach(key => {
            message = message.replace(`%${key}%`, data[key]);
        });
    }

    errorDiv.textContent = message;

    // Insertar el div de error justo después de la tarjeta
    cardElement.parentNode.insertBefore(errorDiv, cardElement.nextSibling);
}

/**
 * Oculta (elimina) el mensaje de error inline asociado a un elemento .settings-card.
 * @param {HTMLElement} cardElement El elemento .settings-card cuyo error se quiere ocultar.
 */
function hideInlineError(cardElement) {
    if (!cardElement) return;
    const nextElement = cardElement.nextElementSibling;
    if (nextElement && nextElement.classList.contains('settings-card__error')) {
        nextElement.remove();
    }
}

// Función para spinner
function toggleButtonSpinner(button, text, isLoading) {
    if (!button) return;
    button.disabled = isLoading;
    if (isLoading) {
        button.dataset.originalText = button.textContent;
        // --- MODIFICACIÓN: Usar spinner más pequeño para botones pequeños ---
        const spinnerClass = button.classList.contains('modal__button-small') ? 'logout-spinner' : 'auth-button-spinner';
        const spinnerStyle = button.classList.contains('modal__button-small') ? 'width: 20px; height: 20px; border-width: 2px; margin: 0 auto; border-top-color: inherit;' : '';
        
        if(spinnerClass === 'logout-spinner') {
            button.innerHTML = `<span class="${spinnerClass}" style="${spinnerStyle}"></span>`;
        } else {
            button.innerHTML = `<div class="${spinnerClass}"></div>`;
        }
    } else {
        button.innerHTML = button.dataset.originalText || text;
    }
}

// Función focusInputAndMoveCursorToEnd
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
                // Ignore errors like "setSelectionRange is not supported"
            }
            inputElement.type = originalType;
        }, 0);

    } catch (e) {
        inputElement.type = originalType;
        // Fallback or log error if needed
    }
}

/**
 * Formatea una fecha UTC (ej. "2025-10-27 18:32:00") a un formato simple "dd/mm/yyyy".
 * @param {string} utcTimestamp
 * @returns {string}
 */
function formatTimestampToSimpleDate(utcTimestamp) {
    try {
        // Añadir 'Z' para asegurar que JS lo interprete como UTC
        const date = new Date(utcTimestamp + 'Z'); 
        if (isNaN(date.getTime())) {
            throw new Error('Invalid date');
        }
        
        const day = String(date.getUTCDate()).padStart(2, '0');
        const month = String(date.getUTCMonth() + 1).padStart(2, '0'); // Enero es 0
        const year = date.getUTCFullYear();
        
        return `${day}/${month}/${year}`;
    } catch (e) {
        console.error('Error al formatear la fecha:', e);
        return 'fecha inválida';
    }
}


async function handlePreferenceChange(preferenceTypeOrField, newValue, cardElement) {
    
    // --- ▼▼▼ INICIO DE LA MODIFICACIÓN (RATE LIMIT) ▼▼▼ ---
    if (isPreferenceLocked) {
        // Estamos bloqueados localmente, no enviar petición.
        // Mostrar el error genérico solicitado.
        showInlineError(cardElement, 'js.api.genericSpamError'); 
        return; // Detener la ejecución
    }
    // --- ▲▲▲ FIN DE LA MODIFICACIÓN (RATE LIMIT) ▲▲▲ ---

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
    // CSRF token se añade en _post

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
            
            // 1. Cargar las nuevas traducciones dinámicamente
            await loadTranslations(newValue);
            
            // 2. Aplicar las traducciones a toda la página
            applyTranslations(document.body);
            
            // 3. Mostrar la alerta de éxito estándar (que no menciona la recarga)
            window.showAlert(getTranslation('js.settings.successPreference'), 'success');

        } else {
             // Mostrar la alerta estándar para todas las demás preferencias
             window.showAlert(getTranslation(result.message || 'js.settings.successPreference'), 'success');
        }

    } else {
        // --- ▼▼▼ INICIO DE LA MODIFICACIÓN (RATE LIMIT) ▼▼▼ ---
        // Comprobar si el error es el de "demasiados intentos"
        if (result.message === 'js.auth.errorTooManyAttempts') {
            // El servidor nos dijo que estamos bloqueados.
            isPreferenceLocked = true;
            // Mostrar el error genérico solicitado
            showInlineError(cardElement, 'js.api.genericSpamError'); 

            // Limpiar el temporizador existente si lo hay
            if (preferenceLockoutTimer) {
                clearTimeout(preferenceLockoutTimer);
            }

            // Iniciar un temporizador local para desbloquear después de 60 minutos
            // (60 minutos * 60 segundos * 1000 ms)
            const lockoutDuration = (result.data?.minutes || 60) * 60 * 1000;
            preferenceLockoutTimer = setTimeout(() => {
                isPreferenceLocked = false;
            }, lockoutDuration);
            
        } else {
            // Mostrar cualquier otro error de guardado
            showInlineError(cardElement, result.message || 'js.settings.errorPreference');
        }
        // --- ▲▲▲ FIN DE LA MODIFICACIÓN (RATE LIMIT) ▲▲▲ ---
    }
}

function getCsrfTokenFromPage() {
    // Intenta encontrar *cualquier* input CSRF en la página
    const csrfInput = document.querySelector('input[name="csrf_token"]');
    return csrfInput ? csrfInput.value : (window.csrfToken || ''); // Fallback al global
}

export function initSettingsManager() {

    document.body.addEventListener('click', async (e) => {
        const target = e.target;
        const card = target.closest('.settings-card');

        // --- (Avatar handlers remain the same) ---
        const avatarCard = document.getElementById('avatar-section');
        if (avatarCard) {
            // ... (código avatar sin cambios) ...
             if (target.closest('#avatar-preview-container') || target.closest('#avatar-upload-trigger') || target.closest('#avatar-change-trigger')) {
                e.preventDefault();
                hideInlineError(avatarCard); // Ocultar error al intentar abrir selector
                document.getElementById('avatar-upload-input')?.click();
                return;
            }

            if (target.closest('#avatar-cancel-trigger')) {
                e.preventDefault();
                hideInlineError(avatarCard); // Ocultar error al cancelar
                const previewImage = document.getElementById('avatar-preview-image');
                const originalAvatarSrc = previewImage.dataset.originalSrc;
                if (previewImage && originalAvatarSrc) previewImage.src = originalAvatarSrc;
                document.getElementById('avatar-upload-input').value = ''; // Limpiar input file

                document.getElementById('avatar-actions-preview').style.display = 'none';
                const originalState = avatarCard.dataset.originalActions === 'default'
                    ? 'avatar-actions-default'
                    : 'avatar-actions-custom';
                document.getElementById(originalState).style.display = 'flex';
                return;
            }

            if (target.closest('#avatar-remove-trigger')) {
                e.preventDefault();
                hideInlineError(avatarCard); // Ocultar error al intentar eliminar
                const removeTrigger = target.closest('#avatar-remove-trigger');
                toggleButtonSpinner(removeTrigger, getTranslation('settings.profile.removePhoto'), true);

                const formData = new FormData();
                formData.append('action', 'remove-avatar');
                formData.append('csrf_token', getCsrfTokenFromPage()); // Usar helper

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
                formData.append('csrf_token', getCsrfTokenFromPage()); // Usar helper

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

        // --- (Username handlers remain the same) ---
        const usernameCard = document.getElementById('username-section');
        if (usernameCard) {
            // ... (código username sin cambios, excepto CSRF token) ...
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
                formData.append('csrf_token', getCsrfTokenFromPage()); // Usar helper

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

        // --- (Email handlers remain the same, except CSRF token) ---
        const emailCard = document.getElementById('email-section');
        if (emailCard) {
            // ... (código email sin cambios, excepto CSRF token y llamada a startResendTimer) ...
            hideInlineError(emailCard);

            if (target.closest('#email-edit-trigger')) {
                e.preventDefault();
                const editTrigger = target.closest('#email-edit-trigger');
                toggleButtonSpinner(editTrigger, getTranslation('settings.profile.edit'), true);

                const formData = new FormData();
                formData.append('action', 'request-email-change-code');
                formData.append('csrf_token', getCsrfTokenFromPage()); // Usar helper

                const result = await callSettingsApi(formData);

                if (result.success) {
                    const modal = document.getElementById('email-verify-modal');
                    if(modal) {
                        const resendLink = modal.querySelector('#email-verify-resend');
                        if (resendLink) {
                            startResendTimer(resendLink, 60); // Iniciar timer aquí
                        }
                        const currentEmail = document.getElementById('email-display-text')?.dataset.originalEmail;
                        const modalEmailEl = document.getElementById('email-verify-modal-email');
                        if (modalEmailEl && currentEmail) modalEmailEl.textContent = currentEmail;
                        const modalError = document.getElementById('email-verify-error');
                        if(modalError) modalError.style.display = 'none';
                        const modalInput = document.getElementById('email-verify-code');
                        if(modalInput) modalInput.value = '';
                        
                        // --- MODIFICACIÓN: Resetear spinner de botón ---
                        const continueBtn = modal.querySelector('#email-verify-continue');
                        toggleButtonSpinner(continueBtn, getTranslation('settings.profile.continue'), false);
                        
                        modal.style.display = 'flex';
                        focusInputAndMoveCursorToEnd(document.getElementById('email-verify-code'));
                    }
                    window.showAlert(getTranslation('js.settings.infoCodeSentCurrent'), 'info');
                } else {
                    showInlineError(emailCard, result.message || 'js.settings.errorCodeRequest', result.data);
                }
                toggleButtonSpinner(editTrigger, getTranslation('settings.profile.edit'), false);
                return;
            }
             if (target.closest('#email-cancel-trigger')) {
                 e.preventDefault();
                const displayElement = document.getElementById('email-display-text');
                const inputElement = document.getElementById('email-input');
                if (displayElement && inputElement) inputElement.value = displayElement.dataset.originalEmail;
                document.getElementById('email-edit-state').style.display = 'none';
                document.getElementById('email-actions-edit').style.display = 'none';
                document.getElementById('email-view-state').style.display = 'flex';
                document.getElementById('email-actions-view').style.display = 'flex';
                return;
            }
            if (target.closest('#email-save-trigger-btn')) {
                e.preventDefault();
                const saveTrigger = target.closest('#email-save-trigger-btn');
                const inputElement = document.getElementById('email-input');
                const newEmail = inputElement.value;
                const actionInput = emailCard.querySelector('[name="action"]');

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
                formData.append('action', actionInput.value);
                formData.append('email', newEmail);
                formData.append('csrf_token', getCsrfTokenFromPage()); // Usar helper

                const result = await callSettingsApi(formData);

                if (result.success) {
                    window.showAlert(getTranslation(result.message || 'js.settings.successEmailUpdate'), 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showInlineError(emailCard, result.message || 'js.settings.errorSaveUnknown', result.data);
                    toggleButtonSpinner(saveTrigger, getTranslation('settings.profile.save'), false);
                }
                return;
            }
        }

        // --- ▼▼▼ INICIO DE MODAL EMAIL MODIFICADO ▼▼▼ ---
        const emailVerifyModal = document.getElementById('email-verify-modal');
        if (emailVerifyModal && emailVerifyModal.contains(target)) {
             if (target.closest('#email-verify-resend')) {
                e.preventDefault();
                const resendTrigger = target.closest('#email-verify-resend');
                const modalError = document.getElementById('email-verify-error');
                if (resendTrigger.classList.contains('disabled-interactive')) return;

                startResendTimer(resendTrigger, 60);

                if(modalError) modalError.style.display = 'none';

                const formData = new FormData();
                formData.append('action', 'request-email-change-code');
                formData.append('csrf_token', getCsrfTokenFromPage()); // Usar helper

                const result = await callSettingsApi(formData);

                if (result.success) {
                    window.showAlert(getTranslation('js.settings.successCodeResent'), 'success');
                } else {
                    if(modalError) {
                        modalError.textContent = getTranslation(result.message || 'js.settings.errorCodeResent', result.data);
                        modalError.style.display = 'block';
                    }
                    const timerId = resendTrigger.dataset.timerId;
                    if (timerId) clearInterval(timerId);
                    const originalBaseText = getTranslation('settings.profile.modalCodeResendA');
                    resendTrigger.textContent = originalBaseText;
                    resendTrigger.classList.remove('disabled-interactive');
                    resendTrigger.style.opacity = '1';
                    resendTrigger.style.textDecoration = '';
                }
                return;
            }
            if (target.closest('#email-verify-continue')) {
                e.preventDefault();
                const continueTrigger = target.closest('#email-verify-continue');
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
                formData.append('csrf_token', getCsrfTokenFromPage()); // Usar helper


                const result = await callSettingsApi(formData);

                if (result.success) {
                    emailVerifyModal.style.display = 'none';
                    document.getElementById('email-view-state').style.display = 'none';
                    document.getElementById('email-actions-view').style.display = 'none';
                    document.getElementById('email-edit-state').style.display = 'flex';
                    document.getElementById('email-actions-edit').style.display = 'flex';
                    focusInputAndMoveCursorToEnd(document.getElementById('email-input'));
                    window.showAlert(getTranslation(result.message || 'js.settings.successVerification'), 'success');
                } else {
                    if(modalError) {
                        modalError.textContent = result.message || getTranslation('js.settings.errorVerification');
                        modalError.style.display = 'block';
                    }
                }
                toggleButtonSpinner(continueTrigger, getTranslation('settings.profile.continue'), false);
                return;
            }
            // LISTENER PARA EL NUEVO BOTÓN CANCELAR
            if (target.closest('#email-verify-cancel')) {
                e.preventDefault();
                emailVerifyModal.style.display = 'none';
                return;
            }
        }
        // --- ▲▲▲ FIN DE MODAL EMAIL MODIFICADO ▲▲▲ ---


        // --- PREFERENCES (Dropdowns/Popovers) ---
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

            // --- ▼▼▼ INICIO DE LA MODIFICACIÓN ▼▼▼ ---
            trigger.classList.add('disabled-interactive'); // 1. Deshabilitar trigger
            try {
                await handlePreferenceChange(prefType, newValue, card); // 2. Esperar guardado
            } catch (error) {
                // Capturar cualquier error inesperado de la función
                console.error("Error during preference change:", error); 
                // El error inline ya se muestra dentro de handlePreferenceChange si falla la API
            } finally {
                // 3. Reactivar el trigger sin importar si falló o no
                trigger.classList.remove('disabled-interactive'); 
            }
            // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▼▼▼ ---

            return; 
        }


        // --- ▼▼▼ INICIO DE MODAL 2FA MODIFICADO ▼▼▼ ---
         if (target.closest('#tfa-verify-cancel')) { // <-- MODIFICADO DE -close A -cancel
            e.preventDefault();
            const modal = document.getElementById('tfa-verify-modal');
            if(modal) modal.style.display = 'none';
            return;
        }
        if (target.closest('#tfa-toggle-button')) {
             e.preventDefault();
            const toggleButton = target.closest('#tfa-toggle-button');
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
                if(modalTitle) modalTitle.dataset.i18n = 'js.settings.modal2faTitleEnable';
                if(modalText) modalText.dataset.i18n = 'js.settings.modal2faDescEnable';
            } else {
                 if(modalTitle) modalTitle.dataset.i18n = 'js.settings.modal2faTitleDisable';
                 if(modalText) modalText.dataset.i18n = 'js.settings.modal2faDescDisable';
            }
             applyTranslations(modal);

            if(errorDiv) errorDiv.style.display = 'none';
            if(passInput) passInput.value = '';

            // --- MODIFICACIÓN: Resetear spinner de botón ---
            const continueBtn = modal.querySelector('#tfa-verify-continue');
            toggleButtonSpinner(continueBtn, getTranslation('settings.login.confirm'), false);

            modal.style.display = 'flex';
            focusInputAndMoveCursorToEnd(passInput);

            return;
        }
         if (target.closest('#tfa-verify-continue')) {
             e.preventDefault();
                const modal = document.getElementById('tfa-verify-modal');
                const verifyTrigger = target.closest('#tfa-verify-continue');
                const errorDiv = document.getElementById('tfa-verify-error');
                const currentPassInput = document.getElementById('tfa-verify-password');
                const toggleButton = document.getElementById('tfa-toggle-button'); // Necesario para actualizar su estado/texto

                if (!currentPassInput || !currentPassInput.value) { // Comprobar si existe el input
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
                 passFormData.append('csrf_token', getCsrfTokenFromPage()); // Usar helper

                const passResult = await callSettingsApi(passFormData);

                if (passResult.success) {
                    const twoFaFormData = new FormData();
                    twoFaFormData.append('action', 'toggle-2fa');
                    twoFaFormData.append('csrf_token', getCsrfTokenFromPage()); // Usar helper

                    const twoFaResult = await callSettingsApi(twoFaFormData);

                    if (twoFaResult.success) {
                        if(modal) modal.style.display = 'none';
                        window.showAlert(getTranslation(twoFaResult.message), 'success');

                        const statusText = document.getElementById('tfa-status-text');
                        const statusKey = twoFaResult.newState === 1 ? 'settings.login.2faEnabled' : 'settings.login.2faDisabled';
                        const buttonKey = twoFaResult.newState === 1 ? 'settings.login.disable' : 'settings.login.enable';

                        if (statusText) statusText.setAttribute('data-i18n', statusKey);
                        if (toggleButton) { // Comprobar si existe el botón
                            toggleButton.setAttribute('data-i18n', buttonKey);
                            if (twoFaResult.newState === 1) toggleButton.classList.add('danger');
                            else toggleButton.classList.remove('danger');
                            toggleButton.dataset.isEnabled = twoFaResult.newState.toString();
                        }
                         // Re-traducir la tarjeta completa o al menos el status y botón
                        const tfaCard = document.getElementById('tfa-toggle-button')?.closest('.settings-card');
                        if (tfaCard) applyTranslations(tfaCard);


                    } else {
                        if(errorDiv) {
                            errorDiv.textContent = getTranslation(twoFaResult.message || 'js.settings.error2faToggle');
                            errorDiv.style.display = 'block';
                        }
                    }

                } else {
                    if(errorDiv) {
                        errorDiv.textContent = getTranslation(passResult.message || 'js.settings.errorVerification');
                        errorDiv.style.display = 'block';
                    }
                }

                toggleButtonSpinner(verifyTrigger, getTranslation('settings.login.confirm'), false);
                if(currentPassInput) currentPassInput.value = ''; // Limpiar contraseña
            return; // <-- Añadido return explícito
        }
        // --- ▲▲▲ FIN DE MODAL 2FA MODIFICADO ▲▲▲ ---


        // --- ▼▼▼ INICIO DE MODAL PASSWORD MODIFICADO ▼▼▼ ---
         if (target.closest('#password-edit-trigger')) {
            e.preventDefault();
            const modal = document.getElementById('password-change-modal');
            if (!modal) return;
            
            modal.querySelector('[data-step="1"]').style.cssText = 'display: flex; flex-direction: column; gap: 16px;';
            modal.querySelector('[data-step="2"]').style.display = 'none';

            const errorDiv1 = modal.querySelector('#password-verify-error');
            const errorDiv2 = modal.querySelector('#password-update-error');
            if (errorDiv1) errorDiv1.style.display = 'none';
            if (errorDiv2) errorDiv2.style.display = 'none';


            const input1 = modal.querySelector('#password-verify-current');
            const input2 = modal.querySelector('#password-update-new');
            const input3 = modal.querySelector('#password-update-confirm');
             if (input1) input1.value = '';
             if (input2) input2.value = '';
             if (input3) input3.value = '';
            
            // --- MODIFICACIÓN: Resetear spinner de botón ---
            const continueBtn = modal.querySelector('#password-verify-continue');
            toggleButtonSpinner(continueBtn, getTranslation('settings.profile.continue'), false);
            const saveBtn = modal.querySelector('#password-update-save');
            toggleButtonSpinner(saveBtn, getTranslation('settings.login.savePassword'), false);

            modal.style.display = 'flex';
            focusInputAndMoveCursorToEnd(input1);
            return;
        }
         if (target.closest('#password-verify-cancel')) { // <-- MODIFICADO DE -close A -cancel
            e.preventDefault();
            const modal = document.getElementById('password-change-modal');
            if(modal) modal.style.display = 'none';
            return;
        }
        if (target.closest('#password-update-back')) {
             e.preventDefault();
            const modal = document.getElementById('password-change-modal');
            if (!modal) return;
            
             modal.querySelector('[data-step="1"]').style.cssText = 'display: flex; flex-direction: column; gap: 16px;';
             modal.querySelector('[data-step="2"]').style.display = 'none';

            const errorDiv = modal.querySelector('#password-update-error');
             if (errorDiv) errorDiv.style.display = 'none';
            focusInputAndMoveCursorToEnd(modal.querySelector('#password-verify-current'));
            return;
        }
         if (target.closest('#password-verify-continue')) {
            e.preventDefault();
            const modal = document.getElementById('password-change-modal');
            if (!modal) return; // Añadido chequeo
            const verifyTrigger = target.closest('#password-verify-continue');
            const errorDiv = modal.querySelector('#password-verify-error'); // Corregido ID
            const currentPassInput = modal.querySelector('#password-verify-current'); // Corregido selector

            if (!currentPassInput || !currentPassInput.value) {
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
            formData.append('csrf_token', getCsrfTokenFromPage()); // Usar helper

            const result = await callSettingsApi(formData);

            if (result.success) {
                modal.querySelector('[data-step="1"]').style.display = 'none';
                modal.querySelector('[data-step="2"]').style.cssText = 'display: flex; flex-direction: column; gap: 16px;';
                focusInputAndMoveCursorToEnd(modal.querySelector('#password-update-new'));
            } else {
                if(errorDiv) {
                    errorDiv.textContent = getTranslation(result.message || 'js.settings.errorVerification');
                    errorDiv.style.display = 'block';
                }
            }

            toggleButtonSpinner(verifyTrigger, getTranslation('settings.profile.continue'), false);
            return; // <-- Añadido return explícito
        }
         if (target.closest('#password-update-save')) {
            e.preventDefault();
             const modal = document.getElementById('password-change-modal');
             if (!modal) return; // Añadido chequeo
            const saveTrigger = target.closest('#password-update-save');
            const errorDiv = modal.querySelector('#password-update-error'); // Corregido ID
            const newPassInput = modal.querySelector('#password-update-new'); // Corregido selector
            const confirmPassInput = modal.querySelector('#password-update-confirm'); // Corregido selector

            if (!newPassInput || !confirmPassInput) return;

             if (newPassInput.value.length < 8 || newPassInput.value.length > 72) {
                 if(errorDiv) {
                    errorDiv.textContent = getTranslation('js.auth.errorPasswordLength', {min: 8, max: 72});
                    errorDiv.style.display = 'block';
                 }
                 return;
             }
             if (newPassInput.value !== confirmPassInput.value) {
                 if(errorDiv) {
                    errorDiv.textContent = getTranslation('js.auth.errorPasswordMismatch');
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
            formData.append('csrf_token', getCsrfTokenFromPage()); // Usar helper


            const result = await callSettingsApi(formData);

            if (result.success) {
                if(modal) modal.style.display = 'none';
                window.showAlert(getTranslation(result.message || 'js.settings.successPassUpdate'), 'success');

                if (result.newTimestamp) {
                    const passwordTextElement = document.getElementById('password-last-updated-text');
                    if (passwordTextElement) {
                        const formattedDate = formatTimestampToSimpleDate(result.newTimestamp);
                        const newText = `Última actualización de tu contraseña: ${formattedDate}`;
                        passwordTextElement.textContent = newText;
                        passwordTextElement.removeAttribute('data-i18n');
                    }
                }

            } else {
                if(errorDiv) {
                    errorDiv.textContent = getTranslation(result.message || 'js.settings.errorSaving', result.data);
                    errorDiv.style.display = 'block';
                }
            }

            toggleButtonSpinner(saveTrigger, getTranslation('settings.login.savePassword'), false);
            return; // <-- Añadido return explícito
        }
        // --- ▲▲▲ FIN DE MODAL PASSWORD MODIFICADO ▲▲▲ ---


        // --- ▼▼▼ INICIO DE MODAL LOGOUT-ALL MODIFICADO ▼▼▼ ---
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
         if (target.closest('#logout-all-cancel')) { // <-- MODIFICADO (eliminado -close)
            e.preventDefault();
            const modal = document.getElementById('logout-all-modal');
            if(modal) modal.style.display = 'none';
            return;
        }
        if (target.closest('#logout-all-confirm')) {
             e.preventDefault();
             const confirmButton = target.closest('#logout-all-confirm');
             if(!confirmButton) return; // Añadido chequeo

            toggleButtonSpinner(confirmButton, getTranslation('settings.devices.modalConfirm'), true);

            const formData = new FormData();
            formData.append('action', 'logout-all-devices');
            formData.append('csrf_token', getCsrfTokenFromPage()); // Usar helper

            const result = await callSettingsApi(formData);

            if (result.success) {
                window.showAlert(getTranslation('js.settings.infoLogoutAll'), 'success');

                setTimeout(() => {
                    const token = getCsrfTokenFromPage(); // Usar helper
                    const logoutUrl = (window.projectBasePath || '') + '/config/logout.php';
                    window.location.href = `${logoutUrl}?csrf_token=${encodeURIComponent(token)}`;
                }, 1500);

            } else {
                window.showAlert(getTranslation(result.message || 'js.settings.errorLogoutAll'), 'error');
                toggleButtonSpinner(confirmButton, getTranslation('settings.devices.modalConfirm'), false);
            }
            return; // <-- Añadido return explícito
        }
        // --- ▲▲▲ FIN DE MODAL LOGOUT-ALL MODIFICADO ▲▲▲ ---


        // --- ▼▼▼ INICIO DE MODAL DELETE-ACCOUNT MODIFICADO ▼▼▼ ---
         if (target.closest('#delete-account-trigger')) {
            e.preventDefault();
            const modal = document.getElementById('delete-account-modal');
             if(modal) {
                const passwordInput = modal.querySelector('#delete-account-password');
                const errorDiv = modal.querySelector('#delete-account-error');
                const confirmBtn = modal.querySelector('#delete-account-confirm');

                if(passwordInput) passwordInput.value = '';
                if(errorDiv) errorDiv.style.display = 'none';
                if(confirmBtn) {
                    toggleButtonSpinner(confirmBtn, getTranslation('settings.login.modalDeleteConfirm'), false);
                }

                modal.style.display = 'flex';
                if(passwordInput) focusInputAndMoveCursorToEnd(passwordInput);
            }
            return;
        }
         if (target.closest('#delete-account-cancel')) { // <-- MODIFICADO (eliminado -close)
            e.preventDefault();
            const modal = document.getElementById('delete-account-modal');
            if(modal) modal.style.display = 'none';
            return;
        }
        if (target.closest('#delete-account-confirm')) {
             e.preventDefault();
             const confirmButton = target.closest('#delete-account-confirm');
             if(!confirmButton) return; // Añadido chequeo
            const modal = document.getElementById('delete-account-modal');
            if(!modal) return; // Añadido chequeo
            const errorDiv = modal.querySelector('#delete-account-error');
            const passwordInput = modal.querySelector('#delete-account-password');

            if (!passwordInput || !passwordInput.value) {
                if(errorDiv) {
                    errorDiv.textContent = getTranslation('js.settings.errorEnterCurrentPass');
                    errorDiv.style.display = 'block';
                }
                return;
            }

            toggleButtonSpinner(confirmButton, getTranslation('settings.login.modalDeleteConfirm'), true);
            if(errorDiv) errorDiv.style.display = 'none';

            const formData = new FormData();
            formData.append('action', 'delete-account');
            formData.append('current_password', passwordInput.value);
            formData.append('csrf_token', getCsrfTokenFromPage()); // Usar helper

            const result = await callSettingsApi(formData);

            if (result.success) {
                window.showAlert(getTranslation(result.message || 'js.settings.successAccountDeleted'), 'success');
                setTimeout(() => {
                    window.location.href = (window.projectBasePath || '') + '/login';
                }, 2000);

            } else {
                if(errorDiv) {
                    errorDiv.textContent = getTranslation(result.message || 'js.settings.errorAccountDelete', result.data);
                    errorDiv.style.display = 'block';
                }
                toggleButtonSpinner(confirmButton, getTranslation('settings.login.modalDeleteConfirm'), false);
            }
            return; // <-- Añadido return explícito
        }
        // --- ▲▲▲ FIN DE MODAL DELETE-ACCOUNT MODIFICADO ▲▲▲ ---

    }); // Fin listener 'click'


    // --- ▼▼▼ INICIO DE LA MODIFICACIÓN (AÑADIR ASYNC) ▼▼▼ ---
    document.body.addEventListener('change', async (e) => {
    // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
        const target = e.target;
        const card = target.closest('.settings-card');

        // Input de Avatar
        if (target.id === 'avatar-upload-input' && card) {
            hideInlineError(card); // Ocultar error al seleccionar archivo
            const fileInput = target;
            const previewImage = document.getElementById('avatar-preview-image');
            const file = fileInput.files[0];

            if (!file) return;

            // Validaciones de cliente -> Mostrar error inline
            if (!['image/png', 'image/jpeg', 'image/gif', 'image/webp'].includes(file.type)) {
                showInlineError(card, 'js.settings.errorAvatarFormat');
                fileInput.value = ''; // Reset input
                return;
            }
            if (file.size > 2 * 1024 * 1024) { // 2MB
                showInlineError(card, 'js.settings.errorAvatarSize');
                fileInput.value = ''; // Reset input
                return;
            }

            // Mostrar previsualización y botones (sin cambios)
            if (!previewImage.dataset.originalSrc) {
                previewImage.dataset.originalSrc = previewImage.src;
            }
            const reader = new FileReader();
            reader.onload = (event) => { previewImage.src = event.target.result; };
            reader.readAsDataURL(file);

            const actionsDefault = document.getElementById('avatar-actions-default');
            const avatarCard = document.getElementById('avatar-section'); // Usar ID del div
            avatarCard.dataset.originalActions = (actionsDefault.style.display !== 'none') ? 'default' : 'custom';

            document.getElementById('avatar-actions-default').style.display = 'none';
            document.getElementById('avatar-actions-custom').style.display = 'none';
            document.getElementById('avatar-actions-preview').style.display = 'flex';
        }

        // Toggles de Preferencias Booleanas
        else if (target.matches('input[type="checkbox"][data-preference-type="boolean"]') && card) {
             hideInlineError(card); // Ocultar error al cambiar toggle
            const checkbox = target;
            const fieldName = checkbox.dataset.fieldName;
            const newValue = checkbox.checked ? '1' : '0';

            if (fieldName) {
                // --- ▼▼▼ INICIO DE LA MODIFICACIÓN (DESHABILITAR Y AWAIT) ▼▼▼ ---
                checkbox.disabled = true; // 1. Deshabilitar
                try {
                    await handlePreferenceChange(fieldName, newValue, card); // 2. Esperar
                } catch (error) {
                    console.error("Error during toggle preference change:", error);
                } finally {
                    checkbox.disabled = false; // 3. Reactivar
                }
                // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
            } else {
                console.error('Este toggle no tiene un data-field-name:', checkbox);
            }
        }
    }); // Fin listener 'change'

     // Listener de Input (para ocultar errores al escribir)
    document.body.addEventListener('input', (e) => {
        const target = e.target;
        // Ocultar error inline si se escribe en un input dentro de una tarjeta
        // --- MODIFICACIÓN: Usar .modal__input-group y .modal__input ---
        if (target.matches('.settings-username-input') || target.closest('.auth-input-group') || target.closest('.modal__input-group')) {
            const card = target.closest('.settings-card');
            if (card) {
                hideInlineError(card);
            }
            // También ocultar errores en modales
            const modalContent = target.closest('.modal-content');
            if (modalContent) {
                 const errorDiv = modalContent.querySelector('.auth-error-message');
                 if (errorDiv) errorDiv.style.display = 'none';
            }
        }
    }); // Fin listener 'input'

    // --- (setTimeout for original avatar src remains the same) ---
    setTimeout(() => {
        const previewImage = document.getElementById('avatar-preview-image');
        if (previewImage && !previewImage.dataset.originalSrc) {
            previewImage.dataset.originalSrc = previewImage.src;
        }
    }, 100);

} // Fin initSettingsManager
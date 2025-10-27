import { callSettingsApi } from './api-service.js';
import { deactivateAllModules } from './main-controller.js';
import { getTranslation, loadTranslations, applyTranslations } from './i18n-manager.js';

// --- ▼▼▼ NUEVAS FUNCIONES HELPER PARA ERRORES INLINE ▼▼▼ ---

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

// --- ▲▲▲ FIN DE NUEVAS FUNCIONES HELPER ---


// Función para spinner (sin cambios)
function toggleButtonSpinner(button, text, isLoading) {
    if (!button) return;
    button.disabled = isLoading;
    if (isLoading) {
        button.dataset.originalText = button.textContent;
        button.innerHTML = `<span class="logout-spinner" style="width: 20px; height: 20px; border-width: 2px; margin: 0 auto; border-top-color: inherit;"></span>`;
    } else {
        button.innerHTML = button.dataset.originalText || text;
    }
}

// Función focusInputAndMoveCursorToEnd (sin cambios)
function focusInputAndMoveCursorToEnd(inputElement) {
    // ... (código existente sin cambios)
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


// Función handlePreferenceChange (Modificada para usar showInlineError)
async function handlePreferenceChange(preferenceTypeOrField, newValue, cardElement) { // Añadido cardElement
    if (!preferenceTypeOrField || newValue === undefined || !cardElement) { // Añadida validación cardElement
        console.error('handlePreferenceChange: Faltan tipo/campo, valor o elemento de tarjeta.');
        return;
    }

    hideInlineError(cardElement); // Ocultar error previo al intentar guardar

    const fieldMap = {
        'language': 'language',
        'theme': 'theme',
        'usage': 'usage_type'
    };

    const fieldName = fieldMap[preferenceTypeOrField] || preferenceTypeOrField;

    if (!fieldName) {
        console.error('Tipo de preferencia desconocido:', preferenceTypeOrField);
        // Podríamos mostrar un error inline aquí si fuera necesario
        return;
    }

    const formData = new FormData();
    formData.append('action', 'update-preference');
    formData.append('field', fieldName);
    formData.append('value', newValue);
    // CSRF token se añade en _post

    const result = await callSettingsApi(formData);

    if (result.success) {
        // Mostramos alerta de éxito (toast)
        window.showAlert(result.message || getTranslation('js.settings.successPreference'), 'success');

        // Actualizar estado local (sin cambios)
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
        }

    } else {
        // Mostrar error inline debajo de la tarjeta correspondiente
        showInlineError(cardElement, result.message || 'js.settings.errorPreference');
    }
}

// Función principal
export function initSettingsManager() {

    // Listener de Clics (Refactorizado masivamente)
    document.body.addEventListener('click', async (e) => {
        const target = e.target;
        const card = target.closest('.settings-card'); // Encontrar la tarjeta padre

        // --- AVATAR ---
        const avatarCard = document.getElementById('avatar-section'); // Usamos el ID del div padre
        if (avatarCard) {
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
                formData.append('csrf_token', avatarCard.querySelector('[name="csrf_token"]').value); // Obtener token del input oculto

                const result = await callSettingsApi(formData);

                if (result.success) {
                    window.showAlert(result.message || getTranslation('js.settings.successAvatarRemoved'), 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showInlineError(avatarCard, result.message || 'js.settings.errorAvatarRemove'); // Error inline
                    toggleButtonSpinner(removeTrigger, getTranslation('settings.profile.removePhoto'), false);
                }
                return; // Importante añadir return aquí
            }

            // Guardar Avatar (movido del listener 'submit')
            if (target.closest('#avatar-save-trigger-btn')) {
                 e.preventDefault();
                const fileInput = document.getElementById('avatar-upload-input');
                const saveTrigger = target.closest('#avatar-save-trigger-btn');

                hideInlineError(avatarCard); // Ocultar error al intentar guardar

                if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                    showInlineError(avatarCard, 'js.settings.errorAvatarSelect'); // Error inline
                    return;
                }

                toggleButtonSpinner(saveTrigger, getTranslation('settings.profile.save'), true);

                const formData = new FormData();
                formData.append('action', 'upload-avatar');
                formData.append('avatar', fileInput.files[0]); // Añadir el archivo
                formData.append('csrf_token', avatarCard.querySelector('[name="csrf_token"]').value); // Añadir token

                const result = await callSettingsApi(formData);

                if (result.success) {
                    window.showAlert(result.message || getTranslation('js.settings.successAvatarUpdate'), 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showInlineError(avatarCard, result.message || 'js.settings.errorSaveUnknown'); // Error inline
                    toggleButtonSpinner(saveTrigger, getTranslation('settings.profile.save'), false);
                }
                return; // Importante añadir return aquí
            }
        } // Fin if(avatarCard)

        // --- USERNAME ---
        const usernameCard = document.getElementById('username-section');
        if (usernameCard) {
            hideInlineError(usernameCard); // Ocultar error si se interactúa con la tarjeta

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

            // Guardar Username (movido del listener 'submit')
            if (target.closest('#username-save-trigger-btn')) {
                e.preventDefault();
                const saveTrigger = target.closest('#username-save-trigger-btn');
                const inputElement = document.getElementById('username-input');
                const csrfTokenInput = usernameCard.querySelector('[name="csrf_token"]');
                const actionInput = usernameCard.querySelector('[name="action"]'); // Obtener el input de acción

                // Validación simple de longitud
                if (inputElement.value.length < 6 || inputElement.value.length > 32) {
                    // Usamos la clave genérica, el backend dará el mensaje específico si falla por otra razón
                    showInlineError(usernameCard, 'js.auth.errorUsernameLength', { min: 6, max: 32 }); // Error inline con datos
                    return;
                }

                toggleButtonSpinner(saveTrigger, getTranslation('settings.profile.save'), true);

                const formData = new FormData();
                formData.append('action', actionInput.value); // Usar valor del input action
                formData.append('username', inputElement.value);
                formData.append('csrf_token', csrfTokenInput.value);

                const result = await callSettingsApi(formData);

                if (result.success) {
                    window.showAlert(result.message || getTranslation('js.settings.successUsernameUpdate'), 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showInlineError(usernameCard, result.message || 'js.settings.errorSaveUnknown', result.data); // Error inline con datos opcionales
                    toggleButtonSpinner(saveTrigger, getTranslation('settings.profile.save'), false);
                }
                 return; // Importante añadir return aquí
            }
        } // Fin if(usernameCard)

        // --- EMAIL ---
        const emailCard = document.getElementById('email-section');
        if (emailCard) {
             hideInlineError(emailCard); // Ocultar error si se interactúa

            // Abrir Modal de Verificación (botón Editar)
            if (target.closest('#email-edit-trigger')) {
                e.preventDefault();
                const editTrigger = target.closest('#email-edit-trigger');
                toggleButtonSpinner(editTrigger, getTranslation('settings.profile.edit'), true);

                const formData = new FormData();
                formData.append('action', 'request-email-change-code');
                formData.append('csrf_token', emailCard.querySelector('[name="csrf_token"]').value);

                const result = await callSettingsApi(formData);

                if (result.success) {
                    // Configurar y mostrar modal (sin cambios)
                    const currentEmail = document.getElementById('email-display-text')?.dataset.originalEmail;
                    const modalEmailEl = document.getElementById('email-verify-modal-email');
                    if (modalEmailEl && currentEmail) modalEmailEl.textContent = currentEmail;
                    const modal = document.getElementById('email-verify-modal');
                    if(modal) modal.style.display = 'flex';
                    focusInputAndMoveCursorToEnd(document.getElementById('email-verify-code'));
                    const modalError = document.getElementById('email-verify-error');
                    if(modalError) modalError.style.display = 'none';
                    const modalInput = document.getElementById('email-verify-code');
                    if(modalInput) modalInput.value = '';
                    window.showAlert(getTranslation('js.settings.infoCodeSentCurrent'), 'info'); // Usamos toast para info
                } else {
                    // Error al solicitar código -> Error inline en la tarjeta de Email
                    showInlineError(emailCard, result.message || 'js.settings.errorCodeRequest');
                }
                toggleButtonSpinner(editTrigger, getTranslation('settings.profile.edit'), false);
                return;
            }

            // Cancelar edición de Email
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

             // Guardar Email (movido del listener 'submit')
            if (target.closest('#email-save-trigger-btn')) {
                e.preventDefault();
                const saveTrigger = target.closest('#email-save-trigger-btn');
                const inputElement = document.getElementById('email-input');
                const newEmail = inputElement.value;
                const csrfTokenInput = emailCard.querySelector('[name="csrf_token"]');
                const actionInput = emailCard.querySelector('[name="action"]');

                // Validaciones de cliente
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
                formData.append('csrf_token', csrfTokenInput.value);

                const result = await callSettingsApi(formData);

                if (result.success) {
                    window.showAlert(result.message || getTranslation('js.settings.successEmailUpdate'), 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showInlineError(emailCard, result.message || 'js.settings.errorSaveUnknown', result.data);
                    toggleButtonSpinner(saveTrigger, getTranslation('settings.profile.save'), false);
                }
                return; // Importante
            }

        } // Fin if(emailCard)

        // --- Clics dentro del Modal de Verificación de Email ---
        const emailVerifyModal = document.getElementById('email-verify-modal');
        if (emailVerifyModal && emailVerifyModal.contains(target)) {
             // Reenviar Código
             if (target.closest('#email-verify-resend')) {
                e.preventDefault();
                const resendTrigger = target.closest('#email-verify-resend');
                const modalError = document.getElementById('email-verify-error');
                if (resendTrigger.classList.contains('disabled-interactive')) return;

                resendTrigger.classList.add('disabled-interactive');
                const originalText = resendTrigger.textContent;
                resendTrigger.textContent = getTranslation('js.settings.sending');
                if(modalError) modalError.style.display = 'none'; // Ocultar error del modal

                const formData = new FormData();
                formData.append('action', 'request-email-change-code');
                // Necesitamos el token CSRF global o de alguna tarjeta visible
                const anyVisibleCard = document.querySelector('.settings-card:not([style*="display: none"])');
                formData.append('csrf_token', anyVisibleCard?.querySelector('[name="csrf_token"]').value || window.csrfToken);

                const result = await callSettingsApi(formData);

                if (result.success) {
                    window.showAlert(getTranslation('js.settings.successCodeResent'), 'success'); // Toast para éxito
                } else {
                    // Mostrar error DENTRO del modal
                    if(modalError) {
                        modalError.textContent = result.message || getTranslation('js.settings.errorCodeResent');
                        modalError.style.display = 'block';
                    }
                }
                resendTrigger.classList.remove('disabled-interactive');
                resendTrigger.textContent = originalText;
                return;
            }

            // Confirmar Código
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
                 const anyVisibleCard = document.querySelector('.settings-card:not([style*="display: none"])');
                 formData.append('csrf_token', anyVisibleCard?.querySelector('[name="csrf_token"]').value || window.csrfToken);


                const result = await callSettingsApi(formData);

                if (result.success) {
                    emailVerifyModal.style.display = 'none'; // Ocultar modal
                    // Mostrar estado de edición de email (sin cambios)
                    document.getElementById('email-view-state').style.display = 'none';
                    document.getElementById('email-actions-view').style.display = 'none';
                    document.getElementById('email-edit-state').style.display = 'flex';
                    document.getElementById('email-actions-edit').style.display = 'flex';
                    focusInputAndMoveCursorToEnd(document.getElementById('email-input'));
                    window.showAlert(result.message || getTranslation('js.settings.successVerification'), 'success'); // Toast éxito
                } else {
                    // Error DENTRO del modal
                    if(modalError) {
                        modalError.textContent = result.message || getTranslation('js.settings.errorVerification');
                        modalError.style.display = 'block';
                    }
                }
                toggleButtonSpinner(continueTrigger, getTranslation('settings.profile.continue'), false);
                return;
            }

            // Cerrar Modal
            if (target.closest('#email-verify-close')) {
                e.preventDefault();
                emailVerifyModal.style.display = 'none';
                return;
            }
        } // Fin if emailVerifyModal

        // --- PREFERENCES (Idioma, Tema, Uso, Toggles) ---
        const clickedLink = target.closest('.module-trigger-select .menu-link');
        if (clickedLink && card) { // Asegurarse de que estamos dentro de una tarjeta
            e.preventDefault();
            hideInlineError(card); // Ocultar error al cambiar preferencia

            const menuList = clickedLink.closest('.menu-list');
            const module = clickedLink.closest('.module-content[data-preference-type]');
            const wrapper = clickedLink.closest('.trigger-select-wrapper');
            const trigger = wrapper?.querySelector('.trigger-selector');
            const triggerTextEl = trigger?.querySelector('.trigger-select-text span');
            const triggerIconEl = trigger?.querySelector('.trigger-select-icon span'); // Icono del trigger

            const newTextKey = clickedLink.querySelector('.menu-link-text span')?.getAttribute('data-i18n'); // Clave i18n
            const newValue = clickedLink.dataset.value;
            const prefType = module?.dataset.preferenceType;
            const newIconName = clickedLink.querySelector('.menu-link-icon span')?.textContent; // Icono del item seleccionado


            if (!menuList || !module || !triggerTextEl || !newTextKey || !newValue || !prefType || !triggerIconEl) {
                 deactivateAllModules();
                return;
            }

            if (clickedLink.classList.contains('active')) {
                deactivateAllModules();
                return;
            }

            // Actualizar trigger con la CLAVE i18n y el icono
             triggerTextEl.setAttribute('data-i18n', newTextKey);
             triggerTextEl.textContent = getTranslation(newTextKey); // Actualizar texto inmediatamente
             triggerIconEl.textContent = newIconName; // Actualizar icono inmediatamente


            // Actualizar estado visual del menú (sin cambios)
            menuList.querySelectorAll('.menu-link').forEach(link => {
                link.classList.remove('active');
                const icon = link.querySelector('.menu-link-check-icon');
                if (icon) icon.innerHTML = '';
            });
            clickedLink.classList.add('active');
            const iconContainer = clickedLink.querySelector('.menu-link-check-icon');
            if (iconContainer) iconContainer.innerHTML = '<span class="material-symbols-rounded">check</span>';

            deactivateAllModules();

            // Llamar a handlePreferenceChange pasando la tarjeta actual
            handlePreferenceChange(prefType, newValue, card);

            return;
        }

        // --- Clics en Toggles ---
        // (Movido al listener 'change' más abajo, que es más apropiado)

        // --- MANEJO DE OTROS MODALES (2FA, Contraseña, Logout All, Delete Account) ---
        // Estos modales ya tienen su propio manejo de errores interno (ej. #tfa-verify-error)
        // No necesitan usar showInlineError/hideInlineError para la tarjeta principal.
        // El código existente para estos modales se mantiene aquí.

        // ... (Código existente para #tfa-verify-close, #tfa-toggle-button, #tfa-verify-continue) ...
         if (target.closest('#tfa-verify-close')) {
            e.preventDefault();
            const modal = document.getElementById('tfa-verify-modal');
            if(modal) modal.style.display = 'none';
            return;
        }

        if (target.closest('#tfa-toggle-button')) {
             e.preventDefault();
             // ... (código existente sin cambios)
            const toggleButton = target.closest('#tfa-toggle-button');
            const modal = document.getElementById('tfa-verify-modal');
            if (!modal) {
                // Usamos toast para este error porque no hay tarjeta obvia asociada
                window.showAlert(getTranslation('js.settings.errorModalNotFound'), 'error');
                return;
            }
            // ... resto del código sin cambios
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
             applyTranslations(modal); // Aplicar traducciones al modal

            if(errorDiv) errorDiv.style.display = 'none';
            if(passInput) passInput.value = '';

            modal.style.display = 'flex';
            focusInputAndMoveCursorToEnd(passInput);

            return;
        }
         if (target.closest('#tfa-verify-continue')) {
             e.preventDefault();
             // ... (código existente sin cambios)
                const modal = document.getElementById('tfa-verify-modal');
                const verifyTrigger = target.closest('#tfa-verify-continue');
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
                 const anyVisibleCard = document.querySelector('.settings-card:not([style*="display: none"])');
                 passFormData.append('csrf_token', anyVisibleCard?.querySelector('[name="csrf_token"]').value || window.csrfToken);


                const passResult = await callSettingsApi(passFormData);

                if (passResult.success) {
                    const twoFaFormData = new FormData();
                    twoFaFormData.append('action', 'toggle-2fa');
                    twoFaFormData.append('csrf_token', anyVisibleCard?.querySelector('[name="csrf_token"]').value || window.csrfToken);

                    const twoFaResult = await callSettingsApi(twoFaFormData);

                    if (twoFaResult.success) {
                        if(modal) modal.style.display = 'none';
                        window.showAlert(getTranslation(twoFaResult.message), 'success'); // Traducir clave

                        const statusText = document.getElementById('tfa-status-text');
                        const statusKey = twoFaResult.newState === 1 ? 'settings.login.2faEnabled' : 'settings.login.2faDisabled';
                        const buttonKey = twoFaResult.newState === 1 ? 'settings.login.disable' : 'settings.login.enable';

                        if (statusText) statusText.setAttribute('data-i18n', statusKey);
                        if (toggleButton) {
                            toggleButton.setAttribute('data-i18n', buttonKey);
                            if (twoFaResult.newState === 1) toggleButton.classList.add('danger');
                            else toggleButton.classList.remove('danger');
                            toggleButton.dataset.isEnabled = twoFaResult.newState.toString();
                        }
                        applyTranslations(toggleButton?.closest('.settings-card')); // Re-traducir tarjeta

                    } else {
                        if(errorDiv) {
                            errorDiv.textContent = getTranslation(twoFaResult.message || 'js.settings.error2faToggle'); // Traducir clave
                            errorDiv.style.display = 'block';
                        }
                    }

                } else {
                    if(errorDiv) {
                        errorDiv.textContent = getTranslation(passResult.message || 'js.settings.errorVerification'); // Traducir clave
                        errorDiv.style.display = 'block';
                    }
                }

                toggleButtonSpinner(verifyTrigger, getTranslation('settings.login.confirm'), false);
                if(currentPassInput) currentPassInput.value = '';
            return;
        }

        // ... (Código existente para #password-edit-trigger, #password-verify-close, #password-update-back, #password-verify-continue, #password-update-save) ...
         if (target.closest('#password-edit-trigger')) {
            e.preventDefault();
            // ... (código existente sin cambios)
            const modal = document.getElementById('password-change-modal');
            if (!modal) return;

            modal.querySelector('[data-step="1"]').style.display = 'flex';
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

            modal.style.display = 'flex';
            focusInputAndMoveCursorToEnd(input1);
            return;
        }
         if (target.closest('#password-verify-close')) {
            e.preventDefault();
            const modal = document.getElementById('password-change-modal');
            if(modal) modal.style.display = 'none';
            return;
        }

        if (target.closest('#password-update-back')) {
             e.preventDefault();
             // ... (código existente sin cambios)
            const modal = document.getElementById('password-change-modal');
            if (!modal) return;

            modal.querySelector('[data-step="1"]').style.display = 'flex';
            modal.querySelector('[data-step="2"]').style.display = 'none';
            const errorDiv = modal.querySelector('#password-update-error');
             if (errorDiv) errorDiv.style.display = 'none';
            focusInputAndMoveCursorToEnd(modal.querySelector('#password-verify-current'));
            return;
        }
         if (target.closest('#password-verify-continue')) {
            e.preventDefault();
            // ... (código existente sin cambios)
            const modal = document.getElementById('password-change-modal');
            const verifyTrigger = target.closest('#password-verify-continue');
            const errorDiv = document.getElementById('password-verify-error');
            const currentPassInput = document.getElementById('password-verify-current');

            if (!currentPassInput || !currentPassInput.value) { // Añadido chequeo de existencia
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
            const anyVisibleCard = document.querySelector('.settings-card:not([style*="display: none"])');
            formData.append('csrf_token', anyVisibleCard?.querySelector('[name="csrf_token"]').value || window.csrfToken);

            const result = await callSettingsApi(formData);

            if (result.success) {
                modal.querySelector('[data-step="1"]').style.display = 'none';
                modal.querySelector('[data-step="2"]').style.display = 'flex';
                focusInputAndMoveCursorToEnd(modal.querySelector('#password-update-new'));
            } else {
                if(errorDiv) {
                    errorDiv.textContent = getTranslation(result.message || 'js.settings.errorVerification'); // Traducir clave
                    errorDiv.style.display = 'block';
                }
            }

            toggleButtonSpinner(verifyTrigger, getTranslation('settings.profile.continue'), false);
            return;
        }
         if (target.closest('#password-update-save')) {
            e.preventDefault();
            // ... (código existente sin cambios)
             const modal = document.getElementById('password-change-modal');
            const saveTrigger = target.closest('#password-update-save');
            const errorDiv = document.getElementById('password-update-error');
            const newPassInput = document.getElementById('password-update-new');
            const confirmPassInput = document.getElementById('password-update-confirm');

            if (!newPassInput || !confirmPassInput) return; // Añadido chequeo

             // Usar claves i18n para errores
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
            const anyVisibleCard = document.querySelector('.settings-card:not([style*="display: none"])');
            formData.append('csrf_token', anyVisibleCard?.querySelector('[name="csrf_token"]').value || window.csrfToken);


            const result = await callSettingsApi(formData);

            if (result.success) {
                if(modal) modal.style.display = 'none';
                window.showAlert(getTranslation(result.message || 'js.settings.successPassUpdate'), 'success'); // Traducir clave
            } else {
                if(errorDiv) {
                     // Traducir clave y añadir datos si existen
                    errorDiv.textContent = getTranslation(result.message || 'js.settings.errorSaving', result.data);
                    errorDiv.style.display = 'block';
                }
            }

            toggleButtonSpinner(saveTrigger, getTranslation('settings.login.savePassword'), false);
            return;
        }

        // ... (Código existente para #logout-all-devices-trigger, #logout-all-close, #logout-all-cancel, #logout-all-confirm) ...
         if (target.closest('#logout-all-devices-trigger')) {
            e.preventDefault();
             // ... (código existente sin cambios)
            const modal = document.getElementById('logout-all-modal');
            if(modal) {
                const dangerBtn = modal.querySelector('#logout-all-confirm');
                if(dangerBtn) {
                     // Usar getTranslation para el texto del botón
                     toggleButtonSpinner(dangerBtn, getTranslation('settings.devices.modalConfirm'), false);
                }
                modal.style.display = 'flex';
            }
            return;
        }
         if (target.closest('#logout-all-close') || target.closest('#logout-all-cancel')) {
            e.preventDefault();
            const modal = document.getElementById('logout-all-modal');
            if(modal) modal.style.display = 'none';
            return;
        }

        if (target.closest('#logout-all-confirm')) {
             e.preventDefault();
             // ... (código existente sin cambios)
             const confirmButton = target.closest('#logout-all-confirm');

            toggleButtonSpinner(confirmButton, getTranslation('settings.devices.modalConfirm'), true);

            const formData = new FormData();
            formData.append('action', 'logout-all-devices');
            const anyVisibleCard = document.querySelector('.settings-card:not([style*="display: none"])');
            formData.append('csrf_token', anyVisibleCard?.querySelector('[name="csrf_token"]').value || window.csrfToken);

            const result = await callSettingsApi(formData);

            if (result.success) {
                window.showAlert(getTranslation('js.settings.infoLogoutAll'), 'success'); // Traducir

                setTimeout(() => {
                    const token = window.csrfToken || '';
                    const logoutUrl = (window.projectBasePath || '') + '/config/logout.php';
                    window.location.href = `${logoutUrl}?csrf_token=${encodeURIComponent(token)}`;
                }, 1500);

            } else {
                 // Usamos toast para este error porque no hay un lugar inline obvio
                window.showAlert(getTranslation(result.message || 'js.settings.errorLogoutAll'), 'error'); // Traducir
                toggleButtonSpinner(confirmButton, getTranslation('settings.devices.modalConfirm'), false);
            }
            return;
        }

        // ... (Código existente para #delete-account-trigger, #delete-account-close, #delete-account-cancel, #delete-account-confirm) ...
         if (target.closest('#delete-account-trigger')) {
            e.preventDefault();
             // ... (código existente sin cambios)
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
         if (target.closest('#delete-account-close') || target.closest('#delete-account-cancel')) {
            e.preventDefault();
            const modal = document.getElementById('delete-account-modal');
            if(modal) modal.style.display = 'none';
            return;
        }

        if (target.closest('#delete-account-confirm')) {
             e.preventDefault();
             // ... (código existente sin cambios)
             const confirmButton = target.closest('#delete-account-confirm');
            const modal = document.getElementById('delete-account-modal');
            const errorDiv = modal.querySelector('#delete-account-error');
            const passwordInput = modal.querySelector('#delete-account-password');

            if (!passwordInput || !passwordInput.value) { // Añadido chequeo
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
            const anyVisibleCard = document.querySelector('.settings-card:not([style*="display: none"])');
            formData.append('csrf_token', anyVisibleCard?.querySelector('[name="csrf_token"]').value || window.csrfToken);

            const result = await callSettingsApi(formData);

            if (result.success) {
                window.showAlert(getTranslation(result.message || 'js.settings.successAccountDeleted'), 'success'); // Traducir
                setTimeout(() => {
                    window.location.href = (window.projectBasePath || '') + '/login';
                }, 2000);

            } else {
                if(errorDiv) {
                     // Traducir clave y añadir datos si existen
                    errorDiv.textContent = getTranslation(result.message || 'js.settings.errorAccountDelete', result.data);
                    errorDiv.style.display = 'block';
                }
                toggleButtonSpinner(confirmButton, getTranslation('settings.login.modalDeleteConfirm'), false);
            }
            return;
        }

    }); // Fin listener 'click'

    // Listener de Cambios (para avatar y toggles)
    document.body.addEventListener('change', (e) => {
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
                // Llamar a handlePreferenceChange pasando la tarjeta actual
                handlePreferenceChange(fieldName, newValue, card);
            } else {
                console.error('Este toggle no tiene un data-field-name:', checkbox);
            }
        }
    }); // Fin listener 'change'

     // Listener de Input (para ocultar errores al escribir)
    document.body.addEventListener('input', (e) => {
        const target = e.target;
        // Ocultar error inline si se escribe en un input dentro de una tarjeta
        if (target.matches('.settings-username-input') || target.matches('.auth-input-group input')) {
            const card = target.closest('.settings-card');
            if (card) {
                hideInlineError(card);
            }
            // También ocultar errores en modales
            const modalContent = target.closest('.settings-modal-content');
            if (modalContent) {
                 const errorDiv = modalContent.querySelector('.auth-error-message');
                 if (errorDiv) errorDiv.style.display = 'none';
            }
        }
    }); // Fin listener 'input'


    // Guardar src original del avatar al inicio (sin cambios)
    setTimeout(() => {
        const previewImage = document.getElementById('avatar-preview-image');
        if (previewImage && !previewImage.dataset.originalSrc) {
            previewImage.dataset.originalSrc = previewImage.src;
        }
    }, 100);

} // Fin initSettingsManager
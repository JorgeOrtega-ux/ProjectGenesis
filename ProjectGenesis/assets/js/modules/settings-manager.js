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
        const spinnerClass = (button.classList.contains('modal__button-small') || button.classList.contains('component-button')) ? 'logout-spinner' : 'auth-button-spinner'; 
        let spinnerStyle = (button.classList.contains('modal__button-small') || button.classList.contains('component-button')) ? 'width: 20px; height: 20px; border-width: 2px; margin: 0 auto; border-top-color: inherit;' : ''; 
        
        if (button.classList.contains('modal__button-small--primary') || 
            button.classList.contains('modal__button-small--danger') || 
            (button.classList.contains('component-button') && !button.classList.contains('danger'))) { 
            
            if(button.classList.contains('component-button') && button.classList.contains('danger')) { 
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
        'usage': 'usage_type',
        'employment': 'employment',
        'education': 'education',
        'privacy': 'message_privacy_level' 
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

function getOriginalBio(bioCard) {
    const viewState = bioCard.querySelector('#profile-bio-view-state');
    if (!viewState) return '';
    const contentDiv = viewState.querySelector('.profile-bio-content');
    return contentDiv ? contentDiv.textContent.trim() : '';
}

export function initSettingsManager() {

    document.body.addEventListener('click', async (e) => {
        const target = e.target;
        const card = target.closest('.component-card'); 

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

                document.getElementById('avatar-actions-preview').classList.remove('active');
                document.getElementById('avatar-actions-preview').classList.add('disabled');
                
                const originalState = avatarCard.dataset.originalActions === 'default'
                    ? 'avatar-actions-default'
                    : 'avatar-actions-custom';
                
                document.getElementById(originalState).classList.add('active');
                document.getElementById(originalState).classList.remove('disabled');
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
        
        const bannerCard = document.getElementById('profile-banner-section');
        if (bannerCard) {
            if (target.closest('#profile-banner-upload-trigger') || target.closest('#profile-banner-change-trigger')) {
                e.preventDefault();
                document.getElementById('profile-banner-upload-input')?.click();
                return;
            }
            
            if (target.closest('#profile-banner-cancel-trigger')) {
                e.preventDefault();
                const previewBanner = document.getElementById('profile-banner-preview');
                const originalBannerBg = previewBanner.dataset.originalBg;
                
                if (previewBanner && originalBannerBg) {
                    previewBanner.style.backgroundImage = originalBannerBg;
                }
                document.getElementById('profile-banner-upload-input').value = ''; 

                document.getElementById('banner-actions-preview').classList.remove('active');
                document.getElementById('banner-actions-preview').classList.add('disabled');
                
                const originalState = bannerCard.dataset.originalActions === 'default'
                    ? 'banner-actions-default'
                    : 'banner-actions-custom';
                
                document.getElementById(originalState).classList.add('active');
                document.getElementById(originalState).classList.remove('disabled');
                return;
            }
            
            if (target.closest('#profile-banner-remove-trigger')) {
                e.preventDefault();
                const removeTrigger = target.closest('#profile-banner-remove-trigger');
                toggleButtonSpinner(removeTrigger, 'Eliminar', true);

                const formData = new FormData();
                formData.append('action', 'remove-banner');
                formData.append('csrf_token', getCsrfTokenFromPage()); 

                const result = await callSettingsApi(formData);

                if (result.success) {
                    window.showAlert(getTranslation(result.message || 'js.settings.successAvatarRemoved'), 'success');
                    
                    const previewBanner = document.getElementById('profile-banner-preview');
                    const defaultBannerUrl = `${window.projectBasePath}/assets/images/default_banner.png`;
                    const newBg = `url('${defaultBannerUrl}')`;

                    previewBanner.style.backgroundImage = newBg;
                    previewBanner.dataset.originalBg = newBg;

                    document.getElementById('banner-actions-preview').classList.remove('active');
                    document.getElementById('banner-actions-preview').classList.add('disabled');
                    document.getElementById('banner-actions-custom').classList.remove('active');
                    document.getElementById('banner-actions-custom').classList.add('disabled');
                    document.getElementById('banner-actions-default').classList.add('active');
                    document.getElementById('banner-actions-default').classList.remove('disabled');
                    
                    bannerCard.dataset.originalActions = 'default';
                } else {
                    window.showAlert(getTranslation(result.message || 'js.settings.errorAvatarRemove'), 'error');
                }
                toggleButtonSpinner(removeTrigger, 'Eliminar', false);
                return;
            }
            
            if (target.closest('#profile-banner-save-trigger-btn')) {
                 e.preventDefault();
                const fileInput = document.getElementById('profile-banner-upload-input');
                const saveTrigger = target.closest('#profile-banner-save-trigger-btn');

                if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                    window.showAlert(getTranslation('js.settings.errorAvatarSelect'), 'error');
                    return;
                }

                toggleButtonSpinner(saveTrigger, 'Guardar', true);

                const formData = new FormData();
                formData.append('action', 'upload-banner');
                formData.append('banner', fileInput.files[0]);
                formData.append('csrf_token', getCsrfTokenFromPage()); 

                const result = await callSettingsApi(formData);

                if (result.success) {
                    window.showAlert(getTranslation(result.message || 'js.settings.successAvatarUpdate'), 'success');
                    
                    const previewBanner = document.getElementById('profile-banner-preview');
                    const newBg = `url('${result.newBannerUrl}')`;
                    
                    previewBanner.style.backgroundImage = newBg;
                    previewBanner.dataset.originalBg = newBg;
                    fileInput.value = ''; 

                    document.getElementById('banner-actions-preview').classList.remove('active');
                    document.getElementById('banner-actions-preview').classList.add('disabled');
                    document.getElementById('banner-actions-default').classList.remove('active');
                    document.getElementById('banner-actions-default').classList.add('disabled');
                    document.getElementById('banner-actions-custom').classList.add('active');
                    document.getElementById('banner-actions-custom').classList.remove('disabled');
                    
                    bannerCard.dataset.originalActions = 'custom';
                } else {
                    window.showAlert(getTranslation(result.message || 'js.settings.errorSaveUnknown'), 'error');
                }
                toggleButtonSpinner(saveTrigger, 'Guardar', false);
                return;
            }
        }

        const bioCard = document.getElementById('profile-bio-card');
        if (bioCard) {
            
            const viewState = bioCard.querySelector('#profile-bio-view-state');
            const editForm = bioCard.querySelector('#profile-bio-edit-form');
            const editTrigger = bioCard.querySelector('#profile-bio-edit-trigger');
            const addTrigger = bioCard.querySelector('#profile-bio-add-trigger');
            const cancelBtn = bioCard.querySelector('#profile-bio-cancel-btn');
            const saveBtn = bioCard.querySelector('#profile-bio-save-btn');
            const textarea = bioCard.querySelector('#profile-bio-textarea');
            
            if (target === editTrigger || (addTrigger && target.closest('#profile-bio-add-trigger'))) {
                e.preventDefault();
                hideInlineError(bioCard);
                if (viewState) viewState.style.display = 'none';
                if (editTrigger) editTrigger.style.display = 'none';
                if (editForm) editForm.style.display = 'flex';
                if (textarea) {
                    textarea.value = getOriginalBio(bioCard); 
                    textarea.focus();
                }
                return;
            }
            
            if (target === cancelBtn) {
                e.preventDefault();
                hideInlineError(bioCard);
                if (editForm) editForm.style.display = 'none';
                if (viewState) viewState.style.display = 'block';
                if (editTrigger) editTrigger.style.display = ''; 
                
                if (textarea) textarea.value = getOriginalBio(bioCard);
                return;
            }

            if (target === saveBtn) {
                e.preventDefault();
                hideInlineError(bioCard);
                
                const newBio = textarea.value.trim();
                const originalBio = getOriginalBio(bioCard);
                const MAX_BIO_LENGTH = 500; 

                if (newBio.length > MAX_BIO_LENGTH) {
                    showInlineError(bioCard, 'js.settings.errorBioTooLong', { length: MAX_BIO_LENGTH }); 
                    return;
                }
                
                if (newBio === originalBio) {
                    if (editForm) editForm.style.display = 'none';
                    if (viewState) viewState.style.display = 'block';
                    if (editTrigger) editTrigger.style.display = '';
                    return;
                }

                toggleButtonSpinner(saveBtn, getTranslation('settings.profile.save'), true);

                const formData = new FormData();
                formData.append('action', 'update-bio');
                formData.append('bio', newBio);
                formData.append('csrf_token', getCsrfTokenFromPage());

                try {
                    const result = await callSettingsApi(formData);
                    if (result.success) {
                        window.showAlert(getTranslation(result.message || 'js.settings.successBioUpdate'), 'success'); 
                        
                        let contentView = viewState.querySelector('.profile-bio-content');
                        if (!contentView) {
                            viewState.innerHTML = ''; 
                            contentView = document.createElement('div');
                            contentView.className = 'profile-bio-content';
                            viewState.appendChild(contentView);
                        }
                        
                        contentView.textContent = result.newBio; 
                        
                        if (result.newBio.length === 0) {
                            viewState.innerHTML = `
                                <div class="profile-bio-placeholder" style="cursor: pointer;" id="profile-bio-add-trigger">
                                    <button type="button" class="profile-bio-add-btn">
                                        Agregar presentación
                                    </button>
                                </div>`;
                            if (editTrigger) editTrigger.style.display = 'none';
                        } else {
                            if (editTrigger) editTrigger.style.display = ''; 
                        }
                        
                        if (editForm) editForm.style.display = 'none';
                        if (viewState) viewState.style.display = 'block';

                    } else {
                        showInlineError(bioCard, result.message || 'js.settings.errorSaveUnknown');
                    }
                } catch (error) {
                    showInlineError(bioCard, 'js.api.errorConnection');
                } finally {
                    toggleButtonSpinner(saveBtn, getTranslation('settings.profile.save'), false);
                }
                return;
            }
        }


        const usernameCard = document.getElementById('username-section');
        if (usernameCard) {
            hideInlineError(usernameCard);

            if (target.closest('#username-edit-trigger')) {
                e.preventDefault();
                document.getElementById('username-view-state').classList.remove('active');
                document.getElementById('username-view-state').classList.add('disabled');
                document.getElementById('username-actions-view').classList.remove('active');
                document.getElementById('username-actions-view').classList.add('disabled');
                document.getElementById('username-edit-state').classList.add('active');
                document.getElementById('username-edit-state').classList.remove('disabled');
                document.getElementById('username-actions-edit').classList.add('active');
                document.getElementById('username-actions-edit').classList.remove('disabled');
                focusInputAndMoveCursorToEnd(document.getElementById('username-input'));
                return;
            }

            if (target.closest('#username-cancel-trigger')) {
                e.preventDefault();
                const displayElement = document.getElementById('username-display-text');
                const inputElement = document.getElementById('username-input');
                if (displayElement && inputElement) inputElement.value = displayElement.dataset.originalUsername;
                document.getElementById('username-edit-state').classList.remove('active');
                document.getElementById('username-edit-state').classList.add('disabled');
                document.getElementById('username-actions-edit').classList.remove('active');
                document.getElementById('username-actions-edit').classList.add('disabled');
                document.getElementById('username-view-state').classList.add('active');
                document.getElementById('username-view-state').classList.remove('disabled');
                document.getElementById('username-actions-view').classList.add('active');
                document.getElementById('username-actions-view').classList.remove('disabled');
                return;
            }
             if (target.closest('#username-save-trigger-btn')) {
                e.preventDefault();
                const saveTrigger = target.closest('#username-save-trigger-btn');
                const inputElement = document.getElementById('username-input');
                const actionInput = usernameCard.querySelector('[name="action"]');

                const minUserLength = window.minUsernameLength || 6;
                const maxUserLength = window.maxUsernameLength || 32;
                if (inputElement.value.length < minUserLength || inputElement.value.length > maxUserLength) {
                    showInlineError(usernameCard, 'js.auth.errorUsernameLength', { min: minUserLength, max: maxUserLength });
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
            const card = resendTrigger.closest('.component-card'); 
            if (!card) return; 

            if (resendTrigger.classList.contains('disabled-interactive')) return;

            const cooldown = window.codeResendCooldownSeconds || 60;
            startResendTimer(resendTrigger, cooldown);
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
            const card = continueTrigger.closest('.component-card'); 
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
                card.classList.remove('active');
                card.classList.add('disabled');
                const step2Card = document.getElementById('email-step-2-update');
                if (step2Card) {
                    step2Card.classList.add('active');
                    step2Card.classList.remove('disabled');
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
            const card = saveTrigger.closest('.component-card'); 
            if (!card) return;
            
            const inputElement = document.getElementById('email-input-new'); 
            const newEmail = inputElement.value;

            hideInlineError(card); 

            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(newEmail)) {
                showInlineError(card, 'js.auth.errorInvalidEmail'); return;
            }
            if (newEmail.length > (window.maxEmailLength || 255)) {
                showInlineError(card, 'js.auth.errorEmailLength'); return;
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
            
            if (!module) {
                deactivateAllModules();
                return;
            }

            const wrapper = card.querySelector('.trigger-select-wrapper'); 
            const trigger = wrapper?.querySelector('.trigger-selector');
            const triggerTextEl = trigger?.querySelector('.trigger-select-text span');
            const triggerIconEl = trigger?.querySelector('.trigger-select-icon span');

            const newTextSpan = clickedLink.querySelector('.menu-link-text span');
            if (!newTextSpan) {
                console.error("Error: No se encontró el span de texto en el menu-link.");
                deactivateAllModules();
                return;
            }
            const newTextKey = newTextSpan.getAttribute('data-i18n'); 
            const newTextContent = newTextSpan.textContent; 
            
            const newValue = clickedLink.dataset.value;
            const prefType = module?.dataset.preferenceType;
            const newIconName = clickedLink.querySelector('.menu-link-icon span')?.textContent;


            if (!menuList || !wrapper || !trigger || !triggerTextEl || !newValue || !prefType || !triggerIconEl || !newTextContent) { 
                 console.error("Error finding elements for preference change", {menuList, module, wrapper, trigger, triggerTextEl, newTextKey, newValue, prefType, triggerIconEl});
                 deactivateAllModules();
                return;
            }

            if (clickedLink.classList.contains('active')) {
                deactivateAllModules();
                return;
            }

            if (newTextKey) {
                triggerTextEl.setAttribute('data-i18n', newTextKey);
                triggerTextEl.textContent = getTranslation(newTextKey);
            } else {
                triggerTextEl.removeAttribute('data-i18n');
                triggerTextEl.textContent = newTextContent;
            }
             
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
                const card = target.closest('.component-card'); 
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
                step1Card.classList.remove('active');
                step1Card.classList.add('disabled');
                const step2Card = document.getElementById('password-step-2');
                if (step2Card) {
                    step2Card.classList.add('active');
                    step2Card.classList.remove('disabled');
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
            
            const logoutOthersCheckbox = document.getElementById('password-logout-others');


            if (!newPassInput || !confirmPassInput) return;

            hideInlineError(step2Card); 

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

            toggleButtonSpinner(saveTrigger, getTranslation('settings.login.savePassword'), true);

            const formData = new FormData();
            formData.append('action', 'update-password');
            formData.append('new_password', newPassInput.value);
            formData.append('confirm_password', confirmPassInput.value);
            formData.append('csrf_token', getCsrfTokenFromPage()); 

            const logoutOthers = logoutOthersCheckbox ? logoutOthersCheckbox.checked : true;
            formData.append('logout_others', logoutOthers ? '1' : '0');


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
                modal.classList.add('active'); 
            }
            return;
        }
         if (target.closest('#logout-all-cancel')) {
            e.preventDefault();
            const modal = document.getElementById('logout-all-modal');
            if(modal) modal.classList.remove('active'); 
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
                    
                    const logoutUrl = (window.projectBasePath || '') + '/config/actions/logout.php';

                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = logoutUrl;
                    form.style.display = 'none';

                    const tokenInput = document.createElement('input');
                    tokenInput.type = 'hidden';
                    tokenInput.name = 'csrf_token';
                    tokenInput.value = token;

                    form.appendChild(tokenInput);
                    document.body.appendChild(form);
                    form.submit();
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
             const card = target.closest('.component-card'); 
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

        if (target.closest('[data-action="logout-individual-session"]')) {
            e.preventDefault();
            const logoutButton = target.closest('[data-action="logout-individual-session"]');
            const sessionId = logoutButton.dataset.sessionId;
            const card = logoutButton.closest('.component-card');
            
            if (!sessionId || !card) return;

            if (!confirm(getTranslation('js.settings.confirmLogoutIndividual'))) {
                return;
            }

            toggleButtonSpinner(logoutButton, getTranslation('settings.devices.logoutButton'), true);
            hideInlineError(card);

            const formData = new FormData();
            formData.append('action', 'logout-individual-session');
            formData.append('session_id', sessionId);
            formData.append('csrf_token', getCsrfTokenFromPage());

            const result = await callSettingsApi(formData);

            if (result.success) {
                window.showAlert(getTranslation(result.message || 'js.settings.successLogoutIndividual'), 'success');
                card.remove(); 
            } else {
                toggleButtonSpinner(logoutButton, getTranslation('settings.devices.logoutButton'), false);
                showInlineError(card, result.message || 'js.settings.errorLogoutFail');
            }
            return;
        }

    }); 


    document.body.addEventListener('change', async (e) => {
        const target = e.target;
        const card = target.closest('.component-card'); 

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

            const actionsDefault = document.getElementById('avatar-actions-default');
            const avatarCard = document.getElementById('avatar-section'); 
            
            avatarCard.dataset.originalActions = (actionsDefault.classList.contains('active')) ? 'default' : 'custom';

            document.getElementById('avatar-actions-default').classList.remove('active');
            document.getElementById('avatar-actions-default').classList.add('disabled');
            document.getElementById('avatar-actions-custom').classList.remove('active');
            document.getElementById('avatar-actions-custom').classList.add('disabled');
            document.getElementById('avatar-actions-preview').classList.add('active');
            document.getElementById('avatar-actions-preview').classList.remove('disabled');
        }
        
        const bannerCard = target.closest('#profile-banner-section');
        if (target.id === 'profile-banner-upload-input' && bannerCard) {
            const fileInput = target;
            const previewBanner = document.getElementById('profile-banner-preview');
            const file = fileInput.files[0];

            if (!file) return;

            if (!['image/png', 'image/jpeg', 'image/gif', 'image/webp'].includes(file.type)) {
                window.showAlert(getTranslation('js.settings.errorAvatarFormat'), 'error');
                fileInput.value = ''; 
                return;
            }
            
            const maxSizeInMB = window.avatarMaxSizeMB || 2;
            if (file.size > maxSizeInMB * 1024 * 1024) { 
                window.showAlert(getTranslation('js.settings.errorAvatarSize', { size: maxSizeInMB }), 'error');
                fileInput.value = ''; 
                return;
            }

            if (!previewBanner.dataset.originalBg) {
                previewBanner.dataset.originalBg = previewBanner.style.backgroundImage;
            }
            const reader = new FileReader();
            reader.onload = (event) => { 
                previewBanner.style.backgroundImage = `url('${event.target.result}')`;
            };
            reader.readAsDataURL(file);

            const actionsDefault = document.getElementById('banner-actions-default');
            bannerCard.dataset.originalActions = (actionsDefault.classList.contains('active')) ? 'default' : 'custom';

            document.getElementById('banner-actions-default').classList.remove('active');
            document.getElementById('banner-actions-default').classList.add('disabled');
            document.getElementById('banner-actions-custom').classList.remove('active');
            document.getElementById('banner-actions-custom').classList.add('disabled');
            document.getElementById('banner-actions-preview').classList.add('active');
            document.getElementById('banner-actions-preview').classList.remove('disabled');
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

        if (target.id === 'profile-bio-textarea') {
            const bioCard = target.closest('#profile-bio-card');
            if (bioCard) {
                hideInlineError(bioCard);
            }
        }

        if (target.matches('.component-text-input') || target.closest('.auth-input-group') || target.closest('.modal__input-group') || target.closest('.component-input-group')) { 
            const card = target.closest('.component-card'); 
            if (card) {
                hideInlineError(card);
            }
            const modalContent = target.closest('.modal-content');
            if (modalContent) {
                 const errorDiv = modalContent.querySelector('.auth-error-message, .component-card__error'); 
                 if (errorDiv) {
                    if(errorDiv.classList.contains('auth-error-message')) {
                         errorDiv.classList.remove('active');
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
        
        const previewBanner = document.getElementById('profile-banner-preview');
        if (previewBanner && !previewBanner.dataset.originalBg) {
            previewBanner.style.backgroundImage = previewBanner.style.backgroundImage;
        }
    }, 100);

}
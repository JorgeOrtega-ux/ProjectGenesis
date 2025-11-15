import { callAuthApi }  from '../services/api-service.js';
import { handleNavigation } from '../app/url-manager.js';
import { getTranslation } from '../services/i18n-manager.js';

function setButtonSpinner(button) {
    if (!button) return;
    button.dataset.originalHtml = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<div class="auth-button-spinner"></div>';
}

function removeButtonSpinner(button) {
    if (!button) return;
    button.disabled = false;
    if (button.dataset.originalHtml) {
        button.innerHTML = button.dataset.originalHtml;
    }
}

export function startResendTimer(linkElement, seconds) {
    if (!linkElement) {
        return;
    }

    let secondsRemaining = seconds;
    
    const originalBaseText = linkElement.textContent.trim().replace(/\s*\(\d+s?\)$/, '');

    linkElement.classList.add('disabled-interactive');
    linkElement.textContent = `${originalBaseText} (${secondsRemaining}s)`;

    const intervalId = setInterval(() => {
        secondsRemaining--;
        if (secondsRemaining > 0) {
            linkElement.textContent = `${originalBaseText} (${secondsRemaining}s)`;
        } else {
            clearInterval(intervalId);
            linkElement.textContent = originalBaseText;
            linkElement.classList.remove('disabled-interactive');
        }
    }, 1000);

    linkElement.dataset.timerId = intervalId;
}

async function handleRegistrationSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const button = form.querySelector('button[type="submit"]');
    const activeStep = form.querySelector('.auth-step.active');
    const errorDiv = activeStep.querySelector('.auth-error-message');

    setButtonSpinner(button);

    const formData = new FormData(form);
    formData.append('action', 'register-verify');
    formData.append('email', sessionStorage.getItem('regEmail') || '');

    const result = await callAuthApi(formData);

    if (result.success) {
        sessionStorage.removeItem('regEmail');
        sessionStorage.removeItem('regPass');
        window.location.href = window.projectBasePath + '/';
    } else {
        const codeInput = form.querySelector('#register-code');
        showAuthError(errorDiv, getTranslation(result.message || 'js.auth.genericError'), result.data, codeInput);
        
        removeButtonSpinner(button);
    }
}

async function handleResetSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const button = form.querySelector('button[type="submit"]');
    const activeStep = form.querySelector('.auth-step.active');
    const errorDiv = activeStep.querySelector('.auth-error-message');

    const password = form.querySelector('#reset-password');
    const passwordConfirm = form.querySelector('#reset-password-confirm');

    const minPassLength = window.minPasswordLength || 8; 
    const maxPassLength = window.maxPasswordLength || 72; 
    if (password.value.length < minPassLength || password.value.length > maxPassLength) {
        showAuthError(errorDiv, getTranslation('js.auth.errorPasswordLength', {min: minPassLength, max: maxPassLength}), null, password);
        return;
    }
    if (password.value !== passwordConfirm.value) {
        showAuthError(errorDiv, getTranslation('js.auth.errorPasswordMismatch'), null, [password, passwordConfirm]);
        return;
    }

    setButtonSpinner(button);

    const formData = new FormData(form);
    formData.append('action', 'reset-update-password');

    const result = await callAuthApi(formData);

    if (result.success) {
        sessionStorage.removeItem('resetEmail');
        sessionStorage.removeItem('resetCode');
        window.showAlert(getTranslation(result.message || 'js.auth.successPasswordUpdate'), 'success');
        setTimeout(() => {
            window.location.href = window.projectBasePath + '/login';
        }, 2000);
    } else {
        showAuthError(errorDiv, getTranslation(result.message || 'js.auth.genericError'), result.data);
        
        removeButtonSpinner(button);
    }
}

async function handleLoginFinalSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const button = form.querySelector('button[type="submit"]');
    
    const activeStep = button.closest('.auth-step'); 
    const errorDiv = activeStep.querySelector('.auth-error-message');

    setButtonSpinner(button);

    const formData = new FormData(form);
    formData.append('action', 'login-verify-2fa');

    const result = await callAuthApi(formData);

    if (result.success) {
        window.location.href = window.projectBasePath + '/';
    } else {
        const codeInput = form.querySelector('#login-code');
        showAuthError(errorDiv, getTranslation(result.message || 'js.auth.genericError'), result.data, codeInput);
        
        removeButtonSpinner(button);
    }
}


function showAuthError(errorDiv, message, data = null, inputElement = null) {
    if (errorDiv) {
        let finalMessage = message;
        
        if (data) {
            Object.keys(data).forEach(key => {
                const regex = new RegExp(`%${key}%`, 'g');
                finalMessage = finalMessage.replace(regex, data[key]);
            });
        }
        
        errorDiv.textContent = finalMessage;
        errorDiv.classList.add('active'); 
    }
    
    if (inputElement) {
        if (Array.isArray(inputElement)) {
            inputElement.forEach(input => {
                if(input) input.classList.add('auth-input-error');
            });
        } else {
            inputElement.classList.add('auth-input-error');
        }
    }
}


function initPasswordToggles() {
    document.body.addEventListener('click', e => {
        const toggleBtn = e.target.closest('.auth-toggle-password:not(.auth-generate-username)'); 
        if (toggleBtn) {
            const inputId = toggleBtn.getAttribute('data-toggle');
            const input = document.getElementById(inputId);
            const icon = toggleBtn.querySelector('.material-symbols-rounded');

            if (input) {
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.textContent = 'visibility_off';
                } else {
                    input.type = 'password';
                    icon.textContent = 'visibility';
                }
            }
        }
    });
}

function initRegisterWizard() {

    document.body.addEventListener('click', async e => {
        const button = e.target.closest('[data-auth-action]');
        if (!button) return;

        const action = button.getAttribute('data-auth-action');

        if (action === 'generate-username') {
            e.preventDefault();
            const inputId = button.getAttribute('data-toggle');
            const input = document.getElementById(inputId);
            
            if (input) {
                const now = new Date();
                const year = now.getFullYear();
                const month = String(now.getMonth() + 1).padStart(2, '0');
                const day = String(now.getDate()).padStart(2, '0');
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');
                const seconds = String(now.getSeconds()).padStart(2, '0');
                
                const timestamp = `${year}${month}${day}_${hours}${minutes}${seconds}`;
                
                const chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
                const suffix = chars[Math.floor(Math.random() * chars.length)] + chars[Math.floor(Math.random() * chars.length)];
                
                const newUsername = `user${timestamp}${suffix}`;
                
                const maxUserLength = window.maxUsernameLength || 32;
                input.value = newUsername.substring(0, maxUserLength);
                
                input.dispatchEvent(new Event('input', { bubbles: true }));
                
                button.blur();
            }
            return; 
        }


        if (action === 'resend-code') {
            e.preventDefault();
            
            const registerForm = button.closest('#register-form');
            if (!registerForm) return; 

            const currentStepEl = button.closest('.auth-step');
            const errorDiv = currentStepEl.querySelector('.auth-error-message');
            
            const linkElement = button;

            if (linkElement.classList.contains('disabled-interactive')) {
                return;
            }

            const email = sessionStorage.getItem('regEmail');
            if (!email) {
                showAuthError(errorDiv, getTranslation('js.auth.errorNoEmail'));
                return;
            }
            
            
            const originalText = linkElement.textContent.replace(/\s*\(\d+s?\)$/, '').trim();
            
            const cooldown = window.codeResendCooldownSeconds || 60;
            startResendTimer(linkElement, cooldown); 

            const formData = new FormData();
            formData.append('action', 'register-resend-code');
            formData.append('email', email);
            
            const csrfToken = registerForm.querySelector('[name="csrf_token"]');
            if(csrfToken) {
                 formData.append('csrf_token', csrfToken.value);
            }

            const result = await callAuthApi(formData);

            if (result.success) {
                window.showAlert(getTranslation(result.message || 'js.auth.successCodeResent'), 'success');
            } else {
                showAuthError(errorDiv, getTranslation(result.message || 'js.auth.errorCodeResent'), result.data);
                
                const timerId = linkElement.dataset.timerId;
                if (timerId) {
                    clearInterval(timerId);
                }
                
                linkElement.textContent = originalText; 
                linkElement.classList.remove('disabled-interactive');
            }
            return;
        }


        const registerForm = button.closest('#register-form');
        if (!registerForm) return;

        const currentStepEl = button.closest('.auth-step');
        if (!currentStepEl) return;
        const errorDiv = currentStepEl.querySelector('.auth-error-message'); 
        if (!errorDiv) return; 

        const currentStep = parseInt(currentStepEl.getAttribute('data-step'), 10);

        if (action === 'prev-step') {
            return;
        }

        if (action === 'next-step') {
            let isValid = true;
            let clientErrorMessage = getTranslation('js.auth.errorCompleteFields');
            let errorData = null;

            if (currentStep === 1) {
                const emailInput = currentStepEl.querySelector('#register-email');
                const passwordInput = currentStepEl.querySelector('#register-password');

                if (!emailInput.value || !passwordInput.value) {
                    isValid = false;
                    clientErrorMessage = getTranslation('js.auth.errorCompleteEmailPass');
                    showAuthError(errorDiv, clientErrorMessage, null, [emailInput, passwordInput].filter(el => !el.value));
                } else if (!emailInput.value.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) { 
                    isValid = false;
                    clientErrorMessage = getTranslation('js.auth.errorInvalidEmail');
                    showAuthError(errorDiv, clientErrorMessage, null, emailInput);
                } else if (emailInput.value.length > (window.maxEmailLength || 255)) {
                    isValid = false;
                    clientErrorMessage = getTranslation('js.auth.errorEmailLength');
                    showAuthError(errorDiv, clientErrorMessage, null, emailInput);
                } else {
                    const minPassLength = window.minPasswordLength || 8;
                    const maxPassLength = window.maxPasswordLength || 72;
                    if (passwordInput.value.length < minPassLength || passwordInput.value.length > maxPassLength) {
                        isValid = false;
                        clientErrorMessage = getTranslation('js.auth.errorPasswordLength');
                        errorData = {min: minPassLength, max: maxPassLength};
                        showAuthError(errorDiv, clientErrorMessage, errorData, passwordInput);
                    }
                }
            }

            else if (currentStep === 2) {
                const usernameInput = currentStepEl.querySelector('#register-username');
                const minUserLength = window.minUsernameLength || 6;
                const maxUserLength = window.maxUsernameLength || 32;

                if (!usernameInput.value) {
                    isValid = false;
                    clientErrorMessage = getTranslation('js.auth.errorUsernameMissing');
                    showAuthError(errorDiv, clientErrorMessage, null, usernameInput);
                } else if (usernameInput.value.length < minUserLength || usernameInput.value.length > maxUserLength) {
                    isValid = false;
                    clientErrorMessage = getTranslation('js.auth.errorUsernameLength');
                    errorData = {min: minUserLength, max: maxUserLength};
                    showAuthError(errorDiv, clientErrorMessage, errorData, usernameInput);
                }
            }

            if (!isValid) {
                return;
            }

            if (errorDiv) errorDiv.classList.remove('active'); 

            setButtonSpinner(button);

            const formData = new FormData(registerForm);
            let fetchAction = '';

            if (currentStep === 1) {
                fetchAction = 'register-check-email';
            }
            else if (currentStep === 2) {
                fetchAction = 'register-check-username-and-generate-code';
                formData.append('email', sessionStorage.getItem('regEmail') || '');
                formData.append('password', sessionStorage.getItem('regPass') || '');
            }
            formData.append('action', fetchAction);

            const result = await callAuthApi(formData);

            if (result.success) {
                let nextPath = '';
                if (currentStep === 1) {
                    sessionStorage.setItem('regEmail', registerForm.querySelector('#register-email').value);
                    sessionStorage.setItem('regPass', registerForm.querySelector('#register-password').value);
                    nextPath = '/register/additional-data';
                } else if (currentStep === 2) {
                    nextPath = '/register/verification-code';
                }

                if (nextPath) {
                    const fullUrlPath = window.projectBasePath + nextPath;
                    history.pushState(null, '', fullUrlPath);
                    handleNavigation(); 
                }
            } else {
                let errorInput = null;
                if (result.message === 'js.auth.errorEmailInUse') {
                    errorInput = registerForm.querySelector('#register-email');
                } else if (result.message === 'js.auth.errorUsernameInUse') {
                    errorInput = registerForm.querySelector('#register-username');
                }
                showAuthError(errorDiv, getTranslation(result.message || 'js.auth.errorUnknown'), result.data, errorInput); 
            }

            if (!result.success) {
                 removeButtonSpinner(button);
            }
        }
    });

}

function initResetWizard() {
    document.body.addEventListener('click', async e => {
        const button = e.target.closest('[data-auth-action]');
        if (!button) return;

        const resetForm = button.closest('#reset-form');
        if (!resetForm) return;

        const currentStepEl = button.closest('.auth-step');
        if (!currentStepEl) return;
        const errorDiv = currentStepEl.querySelector('.auth-error-message'); 
        
        const action = button.getAttribute('data-auth-action');

        if (action === 'resend-code') {
            e.preventDefault();
            const linkElement = button;

            if (linkElement.classList.contains('disabled-interactive')) {
                return;
            }
            
            const email = sessionStorage.getItem('resetEmail');
            if (!email) {
                showAuthError(errorDiv, getTranslation('js.auth.errorNoEmail'));
                return;
            }


            const originalText = linkElement.textContent.replace(/\s*\(\d+s?\)$/, '').trim();
            
            const cooldown = window.codeResendCooldownSeconds || 60;
            startResendTimer(linkElement, cooldown);

            const formData = new FormData();
            formData.append('action', 'reset-resend-code');
            formData.append('email', email);
            
            const csrfToken = resetForm.querySelector('[name="csrf_token"]');
            if(csrfToken) {
                 formData.append('csrf_token', csrfToken.value);
            }

            const result = await callAuthApi(formData);

            if (result.success) {
                window.showAlert(getTranslation(result.message || 'js.auth.successCodeResent'), 'success');
            } else {
                showAuthError(errorDiv, getTranslation(result.message || 'js.auth.errorCodeResent'), result.data);
                
                const timerId = linkElement.dataset.timerId;
                if (timerId) {
                    clearInterval(timerId);
                }
                
                linkElement.textContent = originalText; 
                linkElement.classList.remove('disabled-interactive');
            }
            return;
        }


        const currentStep = parseInt(currentStepEl.getAttribute('data-step'), 10);

        if (action === 'prev-step') {
            return;
        }

        if (action === 'next-step') {
            let isValid = true;
            let clientErrorMessage = getTranslation('js.auth.errorCompleteFields');

            if (currentStep === 1) {
                const emailInput = currentStepEl.querySelector('#reset-email');
                if (!emailInput.value || !emailInput.value.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) { 
                    isValid = false;
                    clientErrorMessage = getTranslation('js.auth.errorInvalidEmail');
                    showAuthError(errorDiv, clientErrorMessage, null, emailInput);
                } else if (emailInput.value.length > (window.maxEmailLength || 255)) {
                    isValid = false;
                    clientErrorMessage = getTranslation('js.auth.errorEmailLength');
                    showAuthError(errorDiv, clientErrorMessage, null, emailInput);
                }
            }

            else if (currentStep === 2) {
                const codeInput = currentStepEl.querySelector('#reset-code');
                if (!codeInput.value || codeInput.value.length < 14) {
                    isValid = false;
                    clientErrorMessage = getTranslation('js.auth.errorInvalidCode');
                    showAuthError(errorDiv, clientErrorMessage, null, codeInput);
                }
            }

            if (!isValid) {
                return;
            }

            if(errorDiv) errorDiv.classList.remove('active'); 
            
            setButtonSpinner(button);

            const formData = new FormData(resetForm);
            let fetchAction = '';

            if (currentStep === 1) {
                fetchAction = 'reset-check-email';
            }
            else if (currentStep === 2) {
                fetchAction = 'reset-check-code';
                formData.append('email', sessionStorage.getItem('resetEmail') || '');
            }
            formData.append('action', fetchAction);

            const result = await callAuthApi(formData);

            if (result.success) {
                 let nextPath = '';
                 if (currentStep === 1) {
                    sessionStorage.setItem('resetEmail', resetForm.querySelector('#reset-email').value);
                    nextPath = '/reset-password/verify-code';
                 } else if (currentStep === 2) {
                    sessionStorage.setItem('resetCode', resetForm.querySelector('#reset-code').value);
                    nextPath = '/reset-password/new-password';
                 }
                 
                 if (nextPath) {
                    const fullUrlPath = window.projectBasePath + nextPath;
                    history.pushState(null, '', fullUrlPath);
                    handleNavigation(); 
                 }

            } else {
                let errorInput = null;
                if (result.message === 'js.auth.errorUserNotFound') {
                    errorInput = resetForm.querySelector('#reset-email');
                } else if (result.message === 'js.auth.errorCodeExpired') {
                    errorInput = resetForm.querySelector('#reset-code');
                }
                showAuthError(errorDiv, getTranslation(result.message || 'js.auth.errorUnknown'), result.data, errorInput); 
            }

            if (!result.success) {
                removeButtonSpinner(button);
            }
        }
    });
}

function initLoginWizard() {
    document.body.addEventListener('click', async e => {
        const button = e.target.closest('[data-auth-action]');
        if (!button) return;

        const loginForm = button.closest('#login-form');
        if (!loginForm) return;

        const action = button.getAttribute('data-auth-action');

        if (action === 'resend-code') {
            e.preventDefault();
            
            const currentStepEl = button.closest('.auth-step');
            const errorDiv = currentStepEl.querySelector('.auth-error-message');
            const linkElement = button;

            if (linkElement.classList.contains('disabled-interactive')) {
                return;
            }

            const emailInput = loginForm.querySelector('#login-email');
            if (!emailInput || !emailInput.value) {
                showAuthError(errorDiv, getTranslation('js.auth.errorNoEmail')); 
                return;
            }
            const email = emailInput.value;
            
            const originalText = linkElement.textContent.replace(/\s*\(\d+s?\)$/, '').trim();
            
            const cooldown = window.codeResendCooldownSeconds || 60;
            startResendTimer(linkElement, cooldown); 

            const formData = new FormData();
            formData.append('action', 'login-resend-2fa-code'); 
            formData.append('email', email);
            
            const csrfToken = loginForm.querySelector('[name="csrf_token"]');
            if(csrfToken) {
                 formData.append('csrf_token', csrfToken.value);
            }

            const result = await callAuthApi(formData);

            if (result.success) {
                window.showAlert(getTranslation(result.message || 'js.auth.successCodeResent'), 'success');
            } else {
                showAuthError(errorDiv, getTranslation(result.message || 'js.auth.errorCodeResent'), result.data);
                
                const timerId = linkElement.dataset.timerId;
                if (timerId) {
                    clearInterval(timerId);
                }
                
                linkElement.textContent = originalText; 
                linkElement.classList.remove('disabled-interactive');
            }
            return;
        }


        const currentStepEl = button.closest('.auth-step');
        if (!currentStepEl) return;
        const errorDiv = currentStepEl.querySelector('.auth-error-message'); 

        const currentStep = parseInt(currentStepEl.getAttribute('data-step'), 10);

        if (action === 'prev-step') {
            const prevStepEl = loginForm.querySelector(`[data-step="${currentStep - 1}"]`);
            if (prevStepEl) {
                currentStepEl.classList.remove('active');
                currentStepEl.classList.add('disabled');
                
                prevStepEl.classList.add('active');
                prevStepEl.classList.remove('disabled');
                
                if(errorDiv) errorDiv.classList.remove('active');
            }
            return;
        }

        if (action === 'next-step') { 
            const emailInput = currentStepEl.querySelector('#login-email');
            const passwordInput = currentStepEl.querySelector('#login-password');
            if (!emailInput.value || !passwordInput.value) {
                showAuthError(errorDiv, getTranslation('js.auth.errorCompleteEmailPass'), null, [emailInput, passwordInput].filter(el => !el.value)); 
                return;
            }

            if(errorDiv) errorDiv.classList.remove('active'); 
            
            setButtonSpinner(button);

            const formData = new FormData(loginForm);
            formData.append('action', 'login-check-credentials');

            const result = await callAuthApi(formData);

            if (result.success) {
                if (result.is_2fa_required) {
                    const nextStepEl = loginForm.querySelector(`[data-step="${currentStep + 1}"]`);
                    if (nextStepEl) {
                        currentStepEl.classList.remove('active');
                        currentStepEl.classList.add('disabled');
                        
                        nextStepEl.classList.add('active');
                        nextStepEl.classList.remove('disabled');
                        
                         const nextInput = nextStepEl.querySelector('input#login-code');
                         if (nextInput) nextInput.focus();
                         
                         const resendLink = nextStepEl.querySelector('#login-resend-code-link');
                         const cooldown = result.cooldown || 0; 
                         if (resendLink && cooldown > 0) {
                            startResendTimer(resendLink, cooldown);
                         }
                    }
                     if (result.message) {
                         window.showAlert(getTranslation(result.message), 'info');
                     }
                } else {
                     if (result.message) {
                         window.showAlert(getTranslation(result.message), 'success');
                         await new Promise(resolve => setTimeout(resolve, 500)); 
                     }
                    window.location.href = window.projectBasePath + '/';
                }
            } else if (result.redirect_to_status) {
                if (result.redirect_to_status === 'server_full') {
                    window.location.href = window.projectBasePath + '/server-full';
                } else if (result.redirect_to_status === 'maintenance') {
                    window.location.href = window.projectBasePath + '/maintenance';
                } else {
                    window.location.href = window.projectBasePath + '/account-status/' + result.redirect_to_status;
                }
            } else {
                let errorInput = [emailInput, passwordInput]; 
                if (result.message === 'js.auth.errorTooManyAttempts') {
                    errorInput = null; 
                }
                showAuthError(errorDiv, getTranslation(result.message || 'js.auth.errorUnknown'), result.data, errorInput); 
                
                removeButtonSpinner(button);
            }

             if(result.success && result.is_2fa_required) {
             } else if (!result.success && !result.redirect_to_status) { 
             }
        }
    });
}


export function initAuthManager() {
    initPasswordToggles();

    document.body.addEventListener('input', e => {
        
        const authInput = e.target.closest('.auth-input-group input');
        if (authInput && e.target.closest('.auth-form')) {
            
            authInput.classList.remove('auth-input-error');
            
            const currentStep = authInput.closest('.auth-step');
            if (currentStep) {
                const errorDiv = currentStep.querySelector('.auth-error-message');
                if (errorDiv && errorDiv.classList.contains('active')) {
                    errorDiv.classList.remove('active');
                }
            }
        }

        const isRegisterCode = (e.target.id === 'register-code' && e.target.closest('#register-form'));
        const isResetCode = (e.target.id === 'reset-code' && e.target.closest('#reset-form'));
        const isLoginCode = (e.target.id === 'login-code' && e.target.closest('#login-form'));
        
        const isSettingsEmailCode = (e.target.id === 'email-verify-code' && e.target.closest('[data-section="settings-change-email"]'));
        
        const isJoinGroupCode = (e.target.id === 'join-code' && e.target.closest('#join-group-form'));

        if (isRegisterCode || isResetCode || isLoginCode || isSettingsEmailCode || isJoinGroupCode) {
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

    initRegisterWizard();
    initResetWizard();
    initLoginWizard();

    document.body.addEventListener('submit', e => {
        if (e.target.id === 'login-form') {
            handleLoginFinalSubmit(e);
        } else if (e.target.id === 'register-form') {
            handleRegistrationSubmit(e);
        } else if (e.target.id === 'reset-form') {
            handleResetSubmit(e);
        }
    });
}
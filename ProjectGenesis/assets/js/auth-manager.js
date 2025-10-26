import { callAuthApi } from './api-service.js';
import { handleNavigation } from './url-manager.js';
import { getTranslation } from './i18n-manager.js';

export function startResendTimer(linkElement, seconds) {
    if (!linkElement) {
        return;
    }

    let secondsRemaining = seconds;
    
    const originalBaseText = linkElement.textContent.trim().replace(/\s*\(\d+s?\)$/, '');

    linkElement.classList.add('disabled-interactive');
    linkElement.style.opacity = '0.7';
    linkElement.style.textDecoration = 'none';
    linkElement.textContent = `${originalBaseText} (${secondsRemaining}s)`;

    const intervalId = setInterval(() => {
        secondsRemaining--;
        if (secondsRemaining > 0) {
            linkElement.textContent = `${originalBaseText} (${secondsRemaining}s)`;
        } else {
            clearInterval(intervalId);
            linkElement.textContent = originalBaseText;
            linkElement.classList.remove('disabled-interactive');
            linkElement.style.opacity = '1';
            linkElement.style.textDecoration = '';
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

    button.disabled = true;
    button.textContent = getTranslation('js.auth.verifying');

    const formData = new FormData(form);
    formData.append('action', 'register-verify');
    formData.append('email', sessionStorage.getItem('regEmail') || '');

    const result = await callAuthApi(formData);

    if (result.success) {
        sessionStorage.removeItem('regEmail');
        sessionStorage.removeItem('regPass');
        window.location.href = window.projectBasePath + '/';
    } else {
        showAuthError(errorDiv, result.message || getTranslation('js.auth.genericError'));
        button.disabled = false;
        button.textContent = getTranslation('page.register.verifyButton');
    }
}

async function handleResetSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const button = form.querySelector('button[type="submit"]');
    const activeStep = form.querySelector('.auth-step.active');
    const errorDiv = activeStep.querySelector('.auth-error-message');

    const password = form.querySelector('#reset-password').value;
    const passwordConfirm = form.querySelector('#reset-password-confirm').value;

    if (password.length < 8 || password.length > 72) {
        showAuthError(errorDiv, getTranslation('js.auth.errorPasswordLength'));
        return;
    }
    if (password !== passwordConfirm) {
        showAuthError(errorDiv, getTranslation('js.auth.errorPasswordMismatch'));
        return;
    }

    button.disabled = true;
    button.textContent = getTranslation('js.auth.saving');

    const formData = new FormData(form);
    formData.append('action', 'reset-update-password');

    const result = await callAuthApi(formData);

    if (result.success) {
        sessionStorage.removeItem('resetEmail');
        sessionStorage.removeItem('resetCode');
        window.showAlert(result.message || getTranslation('js.auth.successPasswordUpdate'), 'success');
        setTimeout(() => {
            window.location.href = window.projectBasePath + '/login';
        }, 2000);
    } else {
        showAuthError(errorDiv, result.message || getTranslation('js.auth.genericError'));
        button.disabled = false;
        button.textContent = getTranslation('js.auth.saveAndContinue');
    }
}

async function handleLoginFinalSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const button = form.querySelector('button[type="submit"]');
    const activeStep = form.querySelector('.auth-step.active');
    const errorDiv = activeStep.querySelector('.auth-error-message');

    button.disabled = true;
    button.textContent = getTranslation('js.auth.verifying');

    const formData = new FormData(form);
    formData.append('action', 'login-verify-2fa');

    const result = await callAuthApi(formData);

    if (result.success) {
        window.location.href = window.projectBasePath + '/';
    } else {
        showAuthError(errorDiv, result.message || getTranslation('js.auth.genericError'));
        button.disabled = false;
        button.textContent = getTranslation('page.login.verifyButton');
    }
}

function showAuthError(errorDiv, message) {
    
    if (errorDiv) {
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
    }
}


function initPasswordToggles() {
    document.body.addEventListener('click', e => {
        const toggleBtn = e.target.closest('.auth-toggle-password');
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
            
            startResendTimer(linkElement, 60); 

            const formData = new FormData();
            formData.append('action', 'register-resend-code');
            formData.append('email', email);
            
            const csrfToken = registerForm.querySelector('[name="csrf_token"]');
            if(csrfToken) {
                 formData.append('csrf_token', csrfToken.value);
            }

            const result = await callAuthApi(formData);

            if (result.success) {
                window.showAlert(result.message || getTranslation('js.auth.successCodeResent'), 'success');
            } else {
                showAuthError(errorDiv, result.message || getTranslation('js.auth.errorCodeResent'));
                
                const timerId = linkElement.dataset.timerId;
                if (timerId) {
                    clearInterval(timerId);
                }
                
                linkElement.textContent = originalText; 
                linkElement.classList.remove('disabled-interactive');
                linkElement.style.opacity = '1';
                linkElement.style.textDecoration = '';
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

            if (currentStep === 1) {
                const emailInput = currentStepEl.querySelector('#register-email');
                const passwordInput = currentStepEl.querySelector('#register-password');
                const allowedDomains = /@(gmail\.com|outlook\.com|hotmail\.com|yahoo\.com|icloud\.com)$/i;

                if (!emailInput.value || !passwordInput.value) {
                    isValid = false;
                    clientErrorMessage = getTranslation('js.auth.errorCompleteEmailPass');
                } else if (!emailInput.value.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) { 
                    isValid = false;
                    clientErrorMessage = getTranslation('js.auth.errorInvalidEmail');
                } else if (emailInput.value.length > 255) {
                    isValid = false;
                    clientErrorMessage = getTranslation('js.auth.errorEmailLength');
                } else if (!allowedDomains.test(emailInput.value)) {
                    isValid = false;
                    clientErrorMessage = getTranslation('js.auth.errorEmailDomain');
                } else if (passwordInput.value.length < 8 || passwordInput.value.length > 72) {
                    isValid = false;
                    clientErrorMessage = getTranslation('js.auth.errorPasswordLength');
                }
            }
            else if (currentStep === 2) {
                const usernameInput = currentStepEl.querySelector('#register-username');

                if (!usernameInput.value) {
                    isValid = false;
                    clientErrorMessage = getTranslation('js.auth.errorUsernameMissing');
                } else if (usernameInput.value.length < 6 || usernameInput.value.length > 32) {
                    isValid = false;
                    clientErrorMessage = getTranslation('js.auth.errorUsernameLength');
                }
            }

            if (!isValid) {
                showAuthError(errorDiv, clientErrorMessage); 
                return;
            }

            if (errorDiv) errorDiv.style.display = 'none';

            button.disabled = true;
            button.textContent = getTranslation('js.auth.verifying');

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
                showAuthError(errorDiv, result.message || getTranslation('js.auth.errorUnknown')); 
            }

            if (!result.success || !nextPath) {
                 button.disabled = false;
                 button.textContent = getTranslation('page.register.continueButton');
            }
        }
    });

    document.body.addEventListener('input', e => {
        const isRegisterCode = (e.target.id === 'register-code' && e.target.closest('#register-form'));
        const isResetCode = (e.target.id === 'reset-code' && e.target.closest('#reset-form'));
        const isLoginCode = (e.target.id === 'login-code' && e.target.closest('#login-form'));
        const isSettingsEmailCode = (e.target.id === 'email-verify-code' && e.target.closest('#email-verify-modal'));

        if (isRegisterCode || isResetCode || isLoginCode || isSettingsEmailCode) {
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
            
            startResendTimer(linkElement, 60);

            const formData = new FormData();
            formData.append('action', 'reset-resend-code');
            formData.append('email', email);
            
            const csrfToken = resetForm.querySelector('[name="csrf_token"]');
            if(csrfToken) {
                 formData.append('csrf_token', csrfToken.value);
            }

            const result = await callAuthApi(formData);

            if (result.success) {
                window.showAlert(result.message || getTranslation('js.auth.successCodeResent'), 'success');
            } else {
                showAuthError(errorDiv, result.message || getTranslation('js.auth.errorCodeResent'));
                
                const timerId = linkElement.dataset.timerId;
                if (timerId) {
                    clearInterval(timerId);
                }
                
                linkElement.textContent = originalText; 
                linkElement.classList.remove('disabled-interactive');
                linkElement.style.opacity = '1';
                linkElement.style.textDecoration = '';
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
                } else if (emailInput.value.length > 255) {
                    isValid = false;
                    clientErrorMessage = getTranslation('js.auth.errorEmailLength');
                }
            }
            else if (currentStep === 2) {
                const codeInput = currentStepEl.querySelector('#reset-code');
                if (!codeInput.value || codeInput.value.length < 14) {
                    isValid = false;
                    clientErrorMessage = getTranslation('js.auth.errorInvalidCode');
                }
            }

            if (!isValid) {
                showAuthError(errorDiv, clientErrorMessage); 
                return;
            }

            if(errorDiv) errorDiv.style.display = 'none'; 
            button.disabled = true;
            button.textContent = getTranslation('js.auth.verifying');

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
                showAuthError(errorDiv, result.message || getTranslation('js.auth.errorUnknown')); 
            }

            if (!result.success) {
                button.disabled = false;
                button.textContent = (currentStep === 1) ? getTranslation('page.reset.sendCodeButton') : getTranslation('page.reset.verifyButton');
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

        const currentStepEl = button.closest('.auth-step');
        if (!currentStepEl) return;
        const errorDiv = currentStepEl.querySelector('.auth-error-message'); 

        const action = button.getAttribute('data-auth-action');
        const currentStep = parseInt(currentStepEl.getAttribute('data-step'), 10);

        if (action === 'prev-step') {
            const prevStepEl = loginForm.querySelector(`[data-step="${currentStep - 1}"]`);
            if (prevStepEl) {
                currentStepEl.style.display = 'none';
                prevStepEl.style.display = 'block';
                if(errorDiv) errorDiv.style.display = 'none'; 
            }
            return;
        }

        if (action === 'next-step') { 
            const emailInput = currentStepEl.querySelector('#login-email');
            const passwordInput = currentStepEl.querySelector('#login-password');
            if (!emailInput.value || !passwordInput.value) {
                showAuthError(errorDiv, getTranslation('js.auth.errorCompleteEmailPass')); 
                return;
            }

            if(errorDiv) errorDiv.style.display = 'none'; 
            button.disabled = true;
            button.textContent = getTranslation('js.auth.processing');

            const formData = new FormData(loginForm);
            formData.append('action', 'login-check-credentials');

            const result = await callAuthApi(formData);

            if (result.success) {
                if (result.is_2fa_required) {
                    const nextStepEl = loginForm.querySelector(`[data-step="${currentStep + 1}"]`);
                    if (nextStepEl) {
                        currentStepEl.style.display = 'none';
                        nextStepEl.style.display = 'block';
                         const nextInput = nextStepEl.querySelector('input#login-code');
                         if (nextInput) nextInput.focus();
                    }
                     if (result.message) {
                        window.showAlert(result.message, 'info');
                     }
                } else {
                     if (result.message) {
                         window.showAlert(result.message, 'success');
                         await new Promise(resolve => setTimeout(resolve, 500)); 
                     }
                    window.location.href = window.projectBasePath + '/';
                }
            } else {
                showAuthError(errorDiv, result.message || getTranslation('js.auth.errorUnknown')); 
                button.disabled = false; 
                button.textContent = getTranslation('page.login.continueButton');
            }

             if(result.success && result.is_2fa_required) {
             } else if (!result.success) {
             }
        }
    });
}


export function initAuthManager() {
    initPasswordToggles();
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
/* ====================================== */
/* ========= AUTH-MANAGER.JS ============ */
/* ====================================== */
import { callAuthApi } from './api-service.js'; // <-- AÑADIDO

// const AUTH_ENDPOINT = ...; // <-- ELIMINADO

async function handleRegistrationSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const button = form.querySelector('button[type="submit"]');
    const errorDiv = document.getElementById('register-error');

    button.disabled = true;
    button.textContent = 'Verificando...';

    const formData = new FormData(form);
    formData.append('action', 'register-verify');

    // --- LÓGICA DE FETCH REFACTORIZADA ---
    const result = await callAuthApi(formData);

    if (result.success) {
        window.location.href = window.projectBasePath + '/';
    } else {
        showAuthError(errorDiv, result.message || 'Ha ocurrido un error.');
    }
    // --- FIN DEL REFACTOR ---

    button.disabled = false;
    button.textContent = 'Verificar y Crear Cuenta';
}

async function handleResetSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const button = form.querySelector('button[type="submit"]');
    const errorDiv = document.getElementById('reset-error');

    const password = form.querySelector('#reset-password').value;
    const passwordConfirm = form.querySelector('#reset-password-confirm').value;

    if (password.length < 8) {
        showAuthError(errorDiv, 'La contraseña debe tener al menos 8 caracteres.');
        return;
    }
    if (password !== passwordConfirm) {
        showAuthError(errorDiv, 'Las contraseñas no coinciden.');
        return;
    }

    button.disabled = true;
    button.textContent = 'Guardando...';

    const formData = new FormData(form);
    formData.append('action', 'reset-update-password');

    // --- LÓGICA DE FETCH REFACTORIZADA ---
    const result = await callAuthApi(formData);

    if (result.success) {
        window.showAlert(result.message || '¡Contraseña actualizada!', 'success');
        setTimeout(() => {
            window.location.href = window.projectBasePath + '/login';
        }, 2000);
    } else {
        showAuthError(errorDiv, result.message || 'Ha ocurrido un error.');
    }
    // --- FIN DEL REFACTOR ---

    button.disabled = false;
    button.textContent = 'Guardar y Continuar';
}

async function handleLoginFinalSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const button = form.querySelector('button[type="submit"]');
    const errorDiv = document.getElementById('login-error');

    button.disabled = true;
    button.textContent = 'Verificando...';

    const formData = new FormData(form);
    formData.append('action', 'login-verify-2fa');

    // --- LÓGICA DE FETCH REFACTORIZADA ---
    const result = await callAuthApi(formData);

    if (result.success) {
        window.location.href = window.projectBasePath + '/';
    } else {
        showAuthError(errorDiv, result.message || 'Ha ocurrido un error.');
    }
    // --- FIN DEL REFACTOR ---

    button.disabled = false;
    button.textContent = 'Verificar e Ingresar';
}

function showAuthError(errorDiv, message) {
    if (errorDiv) {
        errorDiv.style.display = 'none';
    }
    window.showAlert(message, 'error');
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

        const registerForm = button.closest('#register-form');
        if (!registerForm) return;

        const errorDiv = registerForm.querySelector('#register-error');
        if (!errorDiv) return;

        const action = button.getAttribute('data-auth-action');
        const currentStepEl = button.closest('.auth-step');
        if (!currentStepEl) return;

        const currentStep = parseInt(currentStepEl.getAttribute('data-step'), 10);

        if (action === 'prev-step') {
            const prevStepEl = registerForm.querySelector(`[data-step="${currentStep - 1}"]`);
            if (prevStepEl) {
                currentStepEl.style.display = 'none';
                prevStepEl.style.display = 'block';
                errorDiv.style.display = 'none';
            }
            return;
        }

        if (action === 'next-step') {
            let isValid = true;
            let clientErrorMessage = 'Por favor, completa todos los campos correctamente.';

            if (currentStep === 1) {
                const emailInput = currentStepEl.querySelector('#register-email');
                const passwordInput = currentStepEl.querySelector('#register-password');
                const allowedDomains = /@(gmail\.com|outlook\.com|hotmail\.com|yahoo\.com|icloud\.com)$/i;

                if (!emailInput.value || !passwordInput.value) {
                    isValid = false;
                    clientErrorMessage = 'Por favor, completa email y contraseña.';
                } else if (!allowedDomains.test(emailInput.value)) {
                    isValid = false;
                    clientErrorMessage = 'Solo se permiten correos @gmail, @outlook, @hotmail, @yahoo o @icloud.';
                } else if (passwordInput.value.length < 8) {
                    isValid = false;
                    clientErrorMessage = 'La contraseña debe tener al menos 8 caracteres.';
                }
            }
            else if (currentStep === 2) {
                const usernameInput = currentStepEl.querySelector('#register-username');

                if (!usernameInput.value) {
                    isValid = false;
                    clientErrorMessage = 'Por favor, introduce un nombre de usuario.';
                } else if (usernameInput.value.length < 6) {
                    isValid = false;
                    clientErrorMessage = 'El nombre de usuario debe tener al menos 6 caracteres.';
                }
            }

            if (!isValid) {
                showAuthError(errorDiv, clientErrorMessage);
                return;
            }

            errorDiv.style.display = 'none';
            button.disabled = true;
            button.textContent = 'Verificando...';

            // --- LÓGICA DE FETCH REFACTORIZADA ---
            const formData = new FormData(registerForm);
            let fetchAction = '';

            if (currentStep === 1) {
                fetchAction = 'register-check-email';
            }
            else if (currentStep === 2) {
                fetchAction = 'register-check-username-and-generate-code';
            }
            formData.append('action', fetchAction);

            const result = await callAuthApi(formData);

            if (result.success) {
                const nextStepEl = registerForm.querySelector(`[data-step="${currentStep + 1}"]`);
                if (nextStepEl) {
                    currentStepEl.style.display = 'none';
                    nextStepEl.style.display = 'block';
                }
            } else {
                showAuthError(errorDiv, result.message || 'Error desconocido.');
            }
            // --- FIN DEL REFACTOR ---

            button.disabled = false;
            button.textContent = 'Continuar';
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

        const errorDiv = resetForm.querySelector('#reset-error');
        if (!errorDiv) return;

        const action = button.getAttribute('data-auth-action');
        const currentStepEl = button.closest('.auth-step');
        if (!currentStepEl) return;

        const currentStep = parseInt(currentStepEl.getAttribute('data-step'), 10);

        if (action === 'prev-step') {
            const prevStepEl = resetForm.querySelector(`[data-step="${currentStep - 1}"]`);
            if (prevStepEl) {
                currentStepEl.style.display = 'none';
                prevStepEl.style.display = 'block';
                errorDiv.style.display = 'none';
            }
            return;
        }

        if (action === 'next-step') {
            let isValid = true;
            let clientErrorMessage = 'Por favor, completa todos los campos.';

            if (currentStep === 1) {
                const emailInput = currentStepEl.querySelector('#reset-email');
                if (!emailInput.value) {
                    isValid = false;
                    clientErrorMessage = 'Por favor, introduce tu email.';
                }
            }
            else if (currentStep === 2) {
                const codeInput = currentStepEl.querySelector('#reset-code');
                if (!codeInput.value) {
                    isValid = false;
                    clientErrorMessage = 'Por favor, introduce el código de verificación.';
                }
            }

            if (!isValid) {
                showAuthError(errorDiv, clientErrorMessage);
                return;
            }

            errorDiv.style.display = 'none';
            button.disabled = true;
            button.textContent = 'Verificando...';

            // --- LÓGICA DE FETCH REFACTORIZADA ---
            const formData = new FormData(resetForm);
            let fetchAction = '';

            if (currentStep === 1) {
                fetchAction = 'reset-check-email';
            }
            else if (currentStep === 2) {
                fetchAction = 'reset-check-code';
            }
            formData.append('action', fetchAction);

            const result = await callAuthApi(formData);

            if (result.success) {
                const nextStepEl = resetForm.querySelector(`[data-step="${currentStep + 1}"]`);
                if (nextStepEl) {
                    currentStepEl.style.display = 'none';
                    nextStepEl.style.display = 'block';
                }
                if (currentStep === 1 && result.message) {
                    window.showAlert(result.message, 'info');
                }
            } else {
                showAuthError(errorDiv, result.message || 'Error desconocido.');
            }
            // --- FIN DEL REFACTOR ---

            button.disabled = false;
            button.textContent = (currentStep === 1) ? 'Enviar Código' : 'Verificar';
        }
    });
}

function initLoginWizard() {

    document.body.addEventListener('click', async e => {
        const button = e.target.closest('[data-auth-action]');
        if (!button) return;

        const loginForm = button.closest('#login-form');
        if (!loginForm) return;

        const errorDiv = loginForm.querySelector('#login-error');
        if (!errorDiv) return;

        const action = button.getAttribute('data-auth-action');
        const currentStepEl = button.closest('.auth-step');
        if (!currentStepEl) return;

        const currentStep = parseInt(currentStepEl.getAttribute('data-step'), 10);

        if (action === 'prev-step') {
            const prevStepEl = loginForm.querySelector(`[data-step="${currentStep - 1}"]`);
            if (prevStepEl) {
                currentStepEl.style.display = 'none';
                prevStepEl.style.display = 'block';
                errorDiv.style.display = 'none';
            }
            return;
        }

        if (action === 'next-step') {
            const emailInput = currentStepEl.querySelector('#login-email');
            const passwordInput = currentStepEl.querySelector('#login-password');
            if (!emailInput.value || !passwordInput.value) {
                showAuthError(errorDiv, 'Por favor, completa email y contraseña.');
                return;
            }

            errorDiv.style.display = 'none';
            button.disabled = true;
            button.textContent = 'Procesando...';

            // --- LÓGICA DE FETCH REFACTORIZADA ---
            const formData = new FormData(loginForm);
            formData.append('action', 'login-check-credentials');

            const result = await callAuthApi(formData);

            if (result.success) {
                if (result.is_2fa_required) {
                    const nextStepEl = loginForm.querySelector(`[data-step="${currentStep + 1}"]`);
                    if (nextStepEl) {
                        currentStepEl.style.display = 'none';
                        nextStepEl.style.display = 'block';
                    }
                } else {
                    window.location.href = window.projectBasePath + '/';
                }
            } else {
                showAuthError(errorDiv, result.message || 'Error desconocido.');
            }
            // --- FIN DEL REFACTOR ---

            button.disabled = false;
            button.textContent = 'Continuar';
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
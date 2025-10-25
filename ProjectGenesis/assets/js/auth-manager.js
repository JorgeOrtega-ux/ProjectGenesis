/* ====================================== */
/* ========= AUTH-MANAGER.JS ============ */
/* ====================================== */
import { callAuthApi } from './api-service.js';
// --- ▼▼▼ MODIFICACIÓN: IMPORTAR EL MANEJADOR DE NAVEGACIÓN ▼▼▼ ---
import { handleNavigation } from './url-manager.js';
// --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---

// --- ▼▼▼ INICIO: NUEVA FUNCIÓN DE TEMPORIZADOR ▼▼▼ ---
/**
 * Inicia un temporizador de cooldown en un enlace de reenvío.
 * @param {HTMLElement} linkElement El elemento <a> del enlace.
 * @param {number} seconds Duración del cooldown en segundos.
 */
function startResendTimer(linkElement, seconds) {
    if (!linkElement || linkElement.classList.contains('disabled-interactive')) {
        return;
    }

    let secondsRemaining = seconds;
    const originalText = linkElement.textContent;

    // 1. Deshabilitar inmediatamente
    linkElement.classList.add('disabled-interactive');
    linkElement.style.opacity = '0.7';
    linkElement.style.textDecoration = 'none';
    linkElement.textContent = `Reenviar en ${secondsRemaining}s`;

    // 2. Iniciar intervalo
    const intervalId = setInterval(() => {
        secondsRemaining--;
        if (secondsRemaining > 0) {
            linkElement.textContent = `Reenviar en ${secondsRemaining}s`;
        } else {
            // 3. Al terminar, limpiar y rehabilitar
            clearInterval(intervalId);
            linkElement.textContent = originalText;
            linkElement.classList.remove('disabled-interactive');
            linkElement.style.opacity = '1';
            linkElement.style.textDecoration = ''; // Vuelve al default
        }
    }, 1000);
}
// --- ▲▲▲ FIN: NUEVA FUNCIÓN DE TEMPORIZADOR ▲▲▲ ---


async function handleRegistrationSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const button = form.querySelector('button[type="submit"]');
    const errorDiv = document.getElementById('register-error');

    button.disabled = true;
    button.textContent = 'Verificando...';

    const formData = new FormData(form);
    formData.append('action', 'register-verify');
    
    // --- ▼▼▼ MODIFICACIÓN: AÑADIR EMAIL GUARDADO ▼▼▼ ---
    formData.append('email', sessionStorage.getItem('regEmail') || '');
    // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---

    // --- LÓGICA DE FETCH REFACTORIZADA ---
    const result = await callAuthApi(formData);

    if (result.success) {
        // --- ▼▼▼ MODIFICACIÓN: LIMPIAR STORAGE AL FINALIZAR ▼▼▼ ---
        sessionStorage.removeItem('regEmail');
        sessionStorage.removeItem('regPass');
        // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
        
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
    
    // --- ▼▼▼ MODIFICACIÓN: AÑADIR DATOS GUARDADOS (EMAIL Y CÓDIGO) ▼▼▼ ---
    formData.append('email', sessionStorage.getItem('resetEmail') || '');
    formData.append('verification_code', sessionStorage.getItem('resetCode') || '');
    // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---

    // --- LÓGICA DE FETCH REFACTORIZADA ---
    const result = await callAuthApi(formData);

    if (result.success) {
        // --- ▼▼▼ MODIFICACIÓN: LIMPIAR STORAGE AL FINALIZAR ▼▼▼ ---
        sessionStorage.removeItem('resetEmail');
        sessionStorage.removeItem('resetCode');
        // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
        
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

        const action = button.getAttribute('data-auth-action');

        // --- ▼▼▼ INICIO: NUEVA LÓGICA PARA REENVIAR CÓDIGO ▼▼▼ ---
        if (action === 'resend-code') {
            e.preventDefault();
            const linkElement = button;
            
            // 1. Evitar doble clic si ya está deshabilitado
            if (linkElement.classList.contains('disabled-interactive')) {
                return;
            }

            // 2. Obtener email de sessionStorage
            const email = sessionStorage.getItem('regEmail');
            if (!email) {
                window.showAlert('Error: No se encontró tu email. Por favor, recarga la página.', 'error');
                return;
            }
            
            // 3. Iniciar temporizador (UX)
            startResendTimer(linkElement, 60);

            // 4. Preparar y llamar a la API
            const formData = new FormData();
            formData.append('action', 'register-resend-code');
            formData.append('email', email);
            
            const result = await callAuthApi(formData);
            
            // 5. Mostrar resultado
            if (result.success) {
                window.showAlert(result.message || 'Se ha reenviado un nuevo código.', 'success');
            } else {
                // Si falla el servidor (ej. cooldown), el temporizador del cliente sigue
                // por simplicidad, pero mostramos el error del servidor.
                window.showAlert(result.message || 'Error al reenviar el código.', 'error');
                // En un caso de uso más complejo, podríamos cancelar el timer si el servidor falla,
                // pero por ahora, dejarlo correr evita spam.
            }
            return; // Fin de la acción de reenviar
        }
        // --- ▲▲▲ FIN: NUEVA LÓGICA PARA REENVIAR CÓDIGO ▲▲▲ ---


        // --- Lógica existente para 'prev-step' y 'next-step' ---
        
        const registerForm = button.closest('#register-form');
        if (!registerForm) return;

        const errorDiv = registerForm.querySelector('#register-error');
        if (!errorDiv) return;

        const currentStepEl = button.closest('.auth-step');
        if (!currentStepEl) return;

        const currentStep = parseInt(currentStepEl.getAttribute('data-step'), 10);

        if (action === 'prev-step') {
            // Esta lógica ahora solo la usa reset-password.php
            // register.php usa <a> links que maneja url-manager.js
            const form = button.closest('form');
            const prevStepEl = form.querySelector(`[data-step="${currentStep - 1}"]`);
            if (prevStepEl) {
                currentStepEl.style.display = 'none';
                prevStepEl.style.display = 'block';
                if(errorDiv) errorDiv.style.display = 'none';
            }
            return;
        }

        if (action === 'next-step') {
            let isValid = true;
            let clientErrorMessage = 'Por favor, completa todos los campos correctamente.';

            // --- Validación de Cliente (sin cambios) ---
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
            // --- Fin Validación de Cliente ---

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
                
                // --- ▼▼▼ MODIFICACIÓN: AÑADIR DATOS GUARDADOS ▼▼▼ ---
                formData.append('email', sessionStorage.getItem('regEmail') || '');
                formData.append('password', sessionStorage.getItem('regPass') || '');
                // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
            }
            formData.append('action', fetchAction);

            const result = await callAuthApi(formData);
            // --- FIN DEL REFACTOR ---

            // --- ▼▼▼ MODIFICACIÓN: CAMBIAR URL EN LUGAR DE TOGGLE ▼▼▼ ---
            if (result.success) {
                
                let nextPath = '';
                if (currentStep === 1) {
                    // --- ▼▼▼ MODIFICACIÓN: GUARDAR DATOS ▼▼▼ ---
                    sessionStorage.setItem('regEmail', registerForm.querySelector('#register-email').value);
                    sessionStorage.setItem('regPass', registerForm.querySelector('#register-password').value);
                    // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
                    nextPath = '/register/additional-data';

                } else if (currentStep === 2) {
                    // (No borramos nada aquí, lo necesitamos para el paso 3)
                    nextPath = '/register/verification-code';
                }

                if (nextPath) {
                    const fullUrlPath = window.projectBasePath + nextPath;
                    // Cambiamos la URL en la barra de direcciones
                    history.pushState(null, '', fullUrlPath);
                    // Llamamos manualmente al router para que cargue el contenido del nuevo paso
                    handleNavigation();
                }
                
            } else {
                showAuthError(errorDiv, result.message || 'Error desconocido.');
            }
            // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---

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
                // --- ▼▼▼ MODIFICACIÓN: AÑADIR EMAIL GUARDADO ▼▼▼ ---
                formData.append('email', sessionStorage.getItem('resetEmail') || '');
                // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
            }
            formData.append('action', fetchAction);

            const result = await callAuthApi(formData);

            if (result.success) {
                if (currentStep === 1) {
                    // --- ▼▼▼ MODIFICACIÓN: GUARDAR EMAIL ▼▼▼ ---
                    sessionStorage.setItem('resetEmail', resetForm.querySelector('#reset-email').value);
                    // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
                     if (result.message) {
                        window.showAlert(result.message, 'info');
                    }
                }
                else if (currentStep === 2) {
                    // --- ▼▼▼ MODIFICACIÓN: GUARDAR CÓDIGO ▼▼▼ ---
                    sessionStorage.setItem('resetCode', resetForm.querySelector('#reset-code').value);
                    // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
                }

                const nextStepEl = resetForm.querySelector(`[data-step="${currentStep + 1}"]`);
                if (nextStepEl) {
                    currentStepEl.style.display = 'none';
                    nextStepEl.style.display = 'block';
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

    // --- ▼▼▼ INICIO: NUEVA LÓGICA PARA INICIAR TIMER AL CARGAR PÁGINA ▼▼▼ ---
    // Se ejecuta después de que DOMContentLoaded ha disparado
    // y el router.php ha renderizado el HTML inicial.
    try {
        const step3Fieldset = document.querySelector('#register-form [data-step="3"]');
        
        // Comprobar si el fieldset 3 existe Y está activo
        if (step3Fieldset && step3Fieldset.classList.contains('active')) {
            const link = document.getElementById('register-resend-code-link');
            if (link) {
                // Iniciar el timer automáticamente al cargar la página
                startResendTimer(link, 60); 
            }
        }
    } catch (e) {
        console.error("Error al iniciar el temporizador de reenvío:", e);
    }
    // --- ▲▲▲ FIN: NUEVA LÓGICA PARA INICIAR TIMER AL CARGAR PÁGINA ▲▲▲ ---
}
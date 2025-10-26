/* ====================================== */
/* ========= AUTH-MANAGER.JS ============ */
/* ====================================== */
import { callAuthApi } from './api-service.js';
import { handleNavigation } from './url-manager.js';

/**
 * Inicia un temporizador de cooldown en un enlace de reenvío.
 * @param {HTMLElement} linkElement El elemento <a> del enlace.
 * @param {number} seconds Duración del cooldown en segundos.
 */
export function startResendTimer(linkElement, seconds) {
    if (!linkElement) {
        return;
    }

    let secondsRemaining = seconds;
    
    // --- ▼▼▼ ¡ESTA ES LA LÍNEA CORREGIDA! ▼▼▼ ---
    // Trimeamos ANTES de hacer el replace para eliminar el whitespace
    const originalBaseText = linkElement.textContent.trim().replace(/\s*\(\d+s?\)$/, '');
    // --- ▲▲▲ FIN DE LA CORRECCIÓN ▲▲▲ ---

    // 1. Deshabilitar inmediatamente y mostrar el timer
    linkElement.classList.add('disabled-interactive');
    linkElement.style.opacity = '0.7';
    linkElement.style.textDecoration = 'none';
    linkElement.textContent = `${originalBaseText} (${secondsRemaining}s)`;

    // 2. Iniciar intervalo
    const intervalId = setInterval(() => {
        secondsRemaining--;
        if (secondsRemaining > 0) {
            linkElement.textContent = `${originalBaseText} (${secondsRemaining}s)`;
        } else {
            // 3. Al terminar, limpiar y rehabilitar
            clearInterval(intervalId);
            linkElement.textContent = originalBaseText;
            linkElement.classList.remove('disabled-interactive');
            linkElement.style.opacity = '1';
            linkElement.style.textDecoration = '';
        }
    }, 1000);

    // Guardar referencia al intervalo para poder cancelarlo si falla la API
    linkElement.dataset.timerId = intervalId;
}

async function handleRegistrationSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const button = form.querySelector('button[type="submit"]');
    // --- ▼▼▼ ¡MODIFICADO! ▼▼▼ ---
    const activeStep = form.querySelector('.auth-step.active');
    const errorDiv = activeStep.querySelector('.auth-error-message');
    // --- ▲▲▲ ¡FIN DE LA MODIFICACIÓN! ▲▲▲ ---

    button.disabled = true;
    button.textContent = 'Verificando...';

    const formData = new FormData(form);
    formData.append('action', 'register-verify');
    formData.append('email', sessionStorage.getItem('regEmail') || '');

    const result = await callAuthApi(formData);

    if (result.success) {
        sessionStorage.removeItem('regEmail');
        sessionStorage.removeItem('regPass');
        window.location.href = window.projectBasePath + '/';
    } else {
        showAuthError(errorDiv, result.message || 'Ha ocurrido un error.');
        button.disabled = false;
        button.textContent = 'Verificar y Crear Cuenta';
    }
}

async function handleResetSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const button = form.querySelector('button[type="submit"]');
    // --- ▼▼▼ ¡MODIFICADO! ▼▼▼ ---
    const activeStep = form.querySelector('.auth-step.active');
    const errorDiv = activeStep.querySelector('.auth-error-message');
    // --- ▲▲▲ ¡FIN DE LA MODIFICACIÓN! ▲▲▲ ---

    const password = form.querySelector('#reset-password').value;
    const passwordConfirm = form.querySelector('#reset-password-confirm').value;

    // --- ▼▼▼ INICIO MODIFICACIÓN LÍMITES ▼▼▼ ---
    if (password.length < 8 || password.length > 72) {
        showAuthError(errorDiv, 'La contraseña debe tener entre 8 y 72 caracteres.');
        return;
    }
    // --- ▲▲▲ FIN MODIFICACIÓN LÍMITES ▲▲▲ ---
    if (password !== passwordConfirm) {
        showAuthError(errorDiv, 'Las contraseñas no coinciden.');
        return;
    }

    button.disabled = true;
    button.textContent = 'Guardando...';

    const formData = new FormData(form);
    formData.append('action', 'reset-update-password');
    // --- ▼▼▼ ¡MODIFICACIÓN! ▼▼▼ ---
    // Ya no necesitamos enviar email/código, la API los toma de la SESIÓN
    // formData.append('email', sessionStorage.getItem('resetEmail') || '');
    // formData.append('verification_code', sessionStorage.getItem('resetCode') || '');
    // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

    const result = await callAuthApi(formData);

    if (result.success) {
        sessionStorage.removeItem('resetEmail');
        sessionStorage.removeItem('resetCode');
        window.showAlert(result.message || '¡Contraseña actualizada!', 'success');
        setTimeout(() => {
            window.location.href = window.projectBasePath + '/login';
        }, 2000);
    } else {
        showAuthError(errorDiv, result.message || 'Ha ocurrido un error.');
        button.disabled = false;
        button.textContent = 'Guardar y Continuar';
    }
}

async function handleLoginFinalSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const button = form.querySelector('button[type="submit"]');
    // --- ▼▼▼ ¡MODIFICADO! ▼▼▼ ---
    const activeStep = form.querySelector('.auth-step.active');
    const errorDiv = activeStep.querySelector('.auth-error-message');
    // --- ▲▲▲ ¡FIN DE LA MODIFICACIÓN! ▲▲▲ ---

    button.disabled = true;
    button.textContent = 'Verificando...';

    const formData = new FormData(form);
    formData.append('action', 'login-verify-2fa');

    const result = await callAuthApi(formData);

    if (result.success) {
        window.location.href = window.projectBasePath + '/';
    } else {
        showAuthError(errorDiv, result.message || 'Ha ocurrido un error.');
        button.disabled = false;
        button.textContent = 'Verificar e Ingresar';
    }
}

// --- ▼▼▼ ¡INICIO DE LA MODIFICACIÓN PRINCIPAL! ▼▼▼ ---
/**
 * Muestra un error en el div de error del formulario de autenticación.
 * @param {HTMLElement | null} errorDiv El elemento <div> donde mostrar el error.
 * @param {string} message El mensaje de error.
 */
function showAuthError(errorDiv, message) {
    // window.showAlert(message, 'error'); // <-- LÍNEA ELIMINADA
    
    // Nueva lógica para mostrar el error en el div
    if (errorDiv) {
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
    }
}
// --- ▲▲▲ ¡FIN DE LA MODIFICACIÓN PRINCIPAL! ▲▲▲ ---


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
            
            // Asegurarnos que solo afecte al formulario de REGISTRO
            const registerForm = button.closest('#register-form');
            if (!registerForm) return; 

            // --- ▼▼▼ ¡MODIFICADO! ▼▼▼ ---
            const currentStepEl = button.closest('.auth-step');
            const errorDiv = currentStepEl.querySelector('.auth-error-message');
            // --- ▲▲▲ ¡FIN DE LA MODIFICACIÓN! ▲▲▲ ---
            
            const linkElement = button;

            if (linkElement.classList.contains('disabled-interactive')) {
                return;
            }

            const email = sessionStorage.getItem('regEmail');
            if (!email) {
                // --- MODIFICADO ---
                // Usamos el errorDiv del formulario si no hay email (caso raro)
                showAuthError(errorDiv, 'Error: No se encontró tu email. Por favor, recarga la página.');
                // --- FIN MODIFICADO ---
                return;
            }
            
            // --- ▼▼▼ ¡INICIO DE LA MODIFICACIÓN! ▼▼▼ ---
            
            // 1. Respaldar el texto original (por si falla la API)
            const originalText = linkElement.textContent.replace(/\s*\(\d+s?\)$/, '').trim();
            
            // 2. Iniciar el temporizador INMEDIATAMENTE
            // Esto cambia el texto a "Reenviar... (60s)" y deshabilita el link
            startResendTimer(linkElement, 60); 

            // 3. Preparar la llamada a la API
            const formData = new FormData();
            formData.append('action', 'register-resend-code');
            formData.append('email', email);
            
            const csrfToken = registerForm.querySelector('[name="csrf_token"]');
            if(csrfToken) {
                 formData.append('csrf_token', csrfToken.value);
            }

            // 4. Llamar a la API (mientras el timer corre)
            const result = await callAuthApi(formData);

            // 5. Manejar el resultado
            if (result.success) {
                // ¡Éxito! El timer sigue corriendo. Solo mostramos el toast.
                window.showAlert(result.message || 'Se ha reenviado un nuevo código.', 'success');
            } else {
                // ¡Falló! Mostramos el error en el div
                showAuthError(errorDiv, result.message || 'Error al reenviar el código.');
                
                // Detener el temporizador
                const timerId = linkElement.dataset.timerId;
                if (timerId) {
                    clearInterval(timerId);
                }
                
                // Revertir el link a su estado original
                linkElement.textContent = originalText; 
                linkElement.classList.remove('disabled-interactive');
                linkElement.style.opacity = '1';
                linkElement.style.textDecoration = '';
            }
            // --- ▲▲▲ ¡FIN DE LA MODIFICACIÓN! ▲▲▲ ---
            return;
        }

        // --- Lógica existente para 'prev-step' y 'next-step' ---

        const registerForm = button.closest('#register-form');
        if (!registerForm) return;

        // --- ▼▼▼ ¡MODIFICADO! ▼▼▼ ---
        const currentStepEl = button.closest('.auth-step');
        if (!currentStepEl) return;
        const errorDiv = currentStepEl.querySelector('.auth-error-message'); 
        if (!errorDiv) return; 
        // --- ▲▲▲ ¡FIN DE LA MODIFICACIÓN! ▲▲▲ ---

        const currentStep = parseInt(currentStepEl.getAttribute('data-step'), 10);

        if (action === 'prev-step') {
            // Esta acción ahora es manejada por <a> tags, pero
            // la dejamos por si acaso (aunque debería ser eliminada
            // del HTML de register.php)
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
                } else if (!emailInput.value.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) { 
                    isValid = false;
                    clientErrorMessage = 'El formato de correo no es válido.';
                // --- ▼▼▼ INICIO MODIFICACIÓN LÍMITES ▼▼▼ ---
                } else if (emailInput.value.length > 255) {
                    isValid = false;
                    clientErrorMessage = 'El correo no puede tener más de 255 caracteres.';
                // --- ▲▲▲ FIN MODIFICACIÓN LÍMITES ▲▲▲ ---
                } else if (!allowedDomains.test(emailInput.value)) {
                    isValid = false;
                    clientErrorMessage = 'Solo se permiten correos @gmail, @outlook, @hotmail, @yahoo o @icloud.';
                // --- ▼▼▼ INICIO MODIFICACIÓN LÍMITES ▼▼▼ ---
                } else if (passwordInput.value.length < 8 || passwordInput.value.length > 72) {
                    isValid = false;
                    clientErrorMessage = 'La contraseña debe tener entre 8 y 72 caracteres.';
                // --- ▲▲▲ FIN MODIFICACIÓN LÍMITES ▲▲▲ ---
                }
            }
            else if (currentStep === 2) {
                const usernameInput = currentStepEl.querySelector('#register-username');

                if (!usernameInput.value) {
                    isValid = false;
                    clientErrorMessage = 'Por favor, introduce un nombre de usuario.';
                // --- ▼▼▼ INICIO MODIFICACIÓN LÍMITES ▼▼▼ ---
                } else if (usernameInput.value.length < 6 || usernameInput.value.length > 32) {
                    isValid = false;
                    clientErrorMessage = 'El nombre de usuario debe tener entre 6 y 32 caracteres.';
                // --- ▲▲▲ FIN MODIFICACIÓN LÍMITES ▲▲▲ ---
                }
            }
            // --- Fin Validación de Cliente ---

            if (!isValid) {
                // --- ▼▼▼ ¡MODIFICADO! ▼▼▼ ---
                // window.showAlert(clientErrorMessage, 'error'); // <-- LÍNEA ELIMINADA
                showAuthError(errorDiv, clientErrorMessage); // <-- LÍNEA AÑADIDA
                // --- ▲▲▲ ¡FIN DE LA MODIFICACIÓN! ▲▲▲ ---
                return;
            }

            // --- ▼▼▼ ¡MODIFICADO! ▼▼▼ ---
            // const errorDivElement = registerForm.querySelector('.auth-error-message');
            // if (errorDivElement) errorDivElement.style.display = 'none';
            if (errorDiv) errorDiv.style.display = 'none';
            // --- ▲▲▲ ¡FIN DE LA MODIFICACIÓN! ▲▲▲ ---

            button.disabled = true;
            button.textContent = 'Verificando...';

            // --- LÓGICA DE FETCH REFACTORIZADA (sin cambios) ---
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
            // --- FIN DEL REFACTOR ---

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
                    handleNavigation(); // handleNavigation se encargará de cargar y mostrar
                }
            } else {
                // --- ▼▼▼ ¡MODIFICADO! ▼▼▼ ---
                // showAuthError(null, result.message || 'Error desconocido.'); // <-- LÍNEA ELIMINADA
                showAuthError(errorDiv, result.message || 'Error desconocido.'); // <-- LÍNEA AÑADIDA
                // --- ▲▲▲ ¡FIN DE LA MODIFICACIÓN! ▲▲▲ ---
            }

            if (!result.success || !nextPath) {
                 button.disabled = false;
                 button.textContent = 'Continuar';
            }
        }
    });

    document.body.addEventListener('input', e => {
        // Lógica de formateo de código sin cambios
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

        // --- ▼▼▼ ¡MODIFICADO! ▼▼▼ ---
        const currentStepEl = button.closest('.auth-step');
        if (!currentStepEl) return;
        const errorDiv = currentStepEl.querySelector('.auth-error-message'); 
        // --- ▲▲▲ ¡FIN DE LA MODIFICACIÓN! ▲▲▲ ---
        
        const action = button.getAttribute('data-auth-action');

        // --- LÓGICA DE REENVÍO (Corregida) ---
        if (action === 'resend-code') {
            e.preventDefault();
            const linkElement = button;

            if (linkElement.classList.contains('disabled-interactive')) {
                return;
            }
            
            // --- ▼▼▼ ¡MODIFICACIÓN! Tomar email de sessionStorage ▼▼▼ ---
            const email = sessionStorage.getItem('resetEmail');
            if (!email) {
                // --- MODIFICADO ---
                showAuthError(errorDiv, 'Error: No se encontró tu email. Por favor, vuelve al paso 1.');
                // --- FIN MODIFICADO ---
                return;
            }
            // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

            // --- ▼▼▼ ¡INICIO DE LA MODIFICACIÓN! ▼▼▼ ---

            // 1. Respaldar el texto original (por si falla la API)
            const originalText = linkElement.textContent.replace(/\s*\(\d+s?\)$/, '').trim();
            
            // 2. Iniciar el temporizador INMEDIATAMENTE
            startResendTimer(linkElement, 60);

            // 3. Preparar la llamada a la API
            const formData = new FormData();
            formData.append('action', 'reset-resend-code');
            formData.append('email', email);
            
            const csrfToken = resetForm.querySelector('[name="csrf_token"]');
            if(csrfToken) {
                 formData.append('csrf_token', csrfToken.value);
            }

            // 4. Llamar a la API (mientras el timer corre)
            const result = await callAuthApi(formData);

            // 5. Manejar el resultado
            if (result.success) {
                // ¡Éxito! El timer sigue corriendo. Solo mostramos el toast.
                window.showAlert(result.message || 'Se ha reenviado un nuevo código.', 'success');
            } else {
                // ¡Falló! Mostramos el error en el div
                showAuthError(errorDiv, result.message || 'Error al reenviar el código.');
                
                // Detener el temporizador
                const timerId = linkElement.dataset.timerId;
                if (timerId) {
                    clearInterval(timerId);
                }
                
                // Revertir el link a su estado original
                linkElement.textContent = originalText; 
                linkElement.classList.remove('disabled-interactive');
                linkElement.style.opacity = '1';
                linkElement.style.textDecoration = '';
            }
            // --- ▲▲▲ ¡FIN DE LA MODIFICACIÓN! ▲▲▲ ---
            return;
        }


        const currentStep = parseInt(currentStepEl.getAttribute('data-step'), 10);

        if (action === 'prev-step') {
            // Esta acción ahora es manejada por <a> tags
            return;
        }

        // --- ▼▼▼ ¡INICIO DE LA MODIFICACIÓN GRANDE! ▼▼▼ ---
        if (action === 'next-step') {
            let isValid = true;
            let clientErrorMessage = 'Por favor, completa todos los campos.';

            if (currentStep === 1) {
                const emailInput = currentStepEl.querySelector('#reset-email');
                if (!emailInput.value || !emailInput.value.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) { 
                    isValid = false;
                    clientErrorMessage = 'Por favor, introduce un email válido.';
                // --- ▼▼▼ INICIO MODIFICACIÓN LÍMITES ▼▼▼ ---
                } else if (emailInput.value.length > 255) {
                    isValid = false;
                    clientErrorMessage = 'El correo no puede tener más de 255 caracteres.';
                // --- ▲▲▲ FIN MODIFICACIÓN LÍMITES ▲▲▲ ---
                }
            }
            else if (currentStep === 2) {
                const codeInput = currentStepEl.querySelector('#reset-code');
                if (!codeInput.value || codeInput.value.length < 14) {
                    isValid = false;
                    clientErrorMessage = 'Por favor, introduce el código de verificación completo.';
                }
            }

            if (!isValid) {
                // --- ▼▼▼ ¡MODIFICADO! ▼▼▼ ---
                // showAuthError(null, clientErrorMessage); // <-- LÍNEA ELIMINADA
                showAuthError(errorDiv, clientErrorMessage); // <-- LÍNEA AÑADIDA
                // --- ▲▲▲ ¡FIN DE LA MODIFICACIÓN! ▲▲▲ ---
                return;
            }

            if(errorDiv) errorDiv.style.display = 'none'; // Ocultar si existe
            button.disabled = true;
            button.textContent = 'Verificando...';

            const formData = new FormData(resetForm);
            let fetchAction = '';

            if (currentStep === 1) {
                fetchAction = 'reset-check-email';
            }
            else if (currentStep === 2) {
                fetchAction = 'reset-check-code';
                // Añadimos el email desde sessionStorage
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
                    handleNavigation(); // handleNavigation se encargará de cargar y mostrar
                 }

            } else {
                // --- ▼▼▼ ¡MODIFICADO! ▼▼▼ ---
                // showAuthError(null, result.message || 'Error desconocido.'); // <-- LÍNEA ELIMINADA
                showAuthError(errorDiv, result.message || 'Error desconocido.'); // <-- LÍNEA AÑADIDA
                // --- ▲▲▲ ¡FIN DE LA MODIFICACIÓN! ▲▲▲ ---
            }

            // Rehabilitar botón solo si falló
            if (!result.success) {
                button.disabled = false;
                button.textContent = (currentStep === 1) ? 'Enviar Código' : 'Verificar';
            }
        }
        // --- ▲▲▲ FIN DE LA MODIFICACIÓN GRANDE ▲▲▲ ---
    });
}

function initLoginWizard() {
    // Sin cambios
    document.body.addEventListener('click', async e => {
        const button = e.target.closest('[data-auth-action]');
        if (!button) return;

        const loginForm = button.closest('#login-form');
        if (!loginForm) return;

        // --- ▼▼▼ ¡MODIFICADO! ▼▼▼ ---
        const currentStepEl = button.closest('.auth-step');
        if (!currentStepEl) return;
        const errorDiv = currentStepEl.querySelector('.auth-error-message'); 
        // --- ▲▲▲ ¡FIN DE LA MODIFICACIÓN! ▲▲▲ ---

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
                // --- ▼▼▼ ¡MODIFICADO! ▼▼▼ ---
                // showAuthError(null, 'Por favor, completa email y contraseña.'); // <-- LÍNEA ELIMINADA
                showAuthError(errorDiv, 'Por favor, completa email y contraseña.'); // <-- LÍNEA AÑADIDA
                // --- ▲▲▲ ¡FIN DE LA MODIFICACIÓN! ▲▲▲ ---
                return;
            }

            if(errorDiv) errorDiv.style.display = 'none'; 
            button.disabled = true;
            button.textContent = 'Procesando...';

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
                // --- ▼▼▼ ¡MODIFICADO! ▼▼▼ ---
                // showAuthError(null, result.message || 'Error desconocido.'); // <-- LÍNEA ELIMINADA
                showAuthError(errorDiv, result.message || 'Error desconocido.'); // <-- LÍNEA AÑADIDA
                // --- ▲▲▲ ¡FIN DE LA MODIFICACIÓN! ▲▲▲ ---
                button.disabled = false; 
                button.textContent = 'Continuar';
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
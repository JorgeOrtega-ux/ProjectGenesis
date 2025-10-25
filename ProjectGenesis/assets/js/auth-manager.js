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
function startResendTimer(linkElement, seconds) {
    if (!linkElement) {
        return;
    }

    let secondsRemaining = seconds;
    const originalBaseText = linkElement.textContent.replace(/\s*\(\d+s?\)$/, '').trim();

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
    const errorDiv = document.getElementById('register-error');

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
    formData.append('email', sessionStorage.getItem('resetEmail') || '');
    formData.append('verification_code', sessionStorage.getItem('resetCode') || '');

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
    const errorDiv = document.getElementById('login-error');

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

function showAuthError(errorDiv, message) {
    // Ya no usamos el errorDiv del formulario, usamos showAlert global
    // if (errorDiv) {
    //     errorDiv.style.display = 'none';
    // }
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

        if (action === 'resend-code') {
            e.preventDefault();
            const linkElement = button;

            if (linkElement.classList.contains('disabled-interactive')) {
                return;
            }

            const email = sessionStorage.getItem('regEmail');
            if (!email) {
                window.showAlert('Error: No se encontró tu email. Por favor, recarga la página.', 'error');
                return;
            }

            // --- ▼▼▼ INICIO DE LA MODIFICACIÓN ▼▼▼ ---
            // Guardar texto base antes de iniciar el timer
            const originalText = linkElement.textContent.replace(/\s*\(\d+s?\)$/, '').trim();
            // Iniciar el temporizador INMEDIATAMENTE para mostrar (60s)
            startResendTimer(linkElement, 60);
            // Ya NO cambiamos el texto a "Enviando..."
            // linkElement.textContent = 'Enviando...';
            // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---

            const formData = new FormData();
            formData.append('action', 'register-resend-code');
            formData.append('email', email);

            const result = await callAuthApi(formData);

            if (result.success) {
                window.showAlert(result.message || 'Se ha reenviado un nuevo código.', 'success');
                // El timer ya está corriendo, no hacemos nada más
            } else {
                window.showAlert(result.message || 'Error al reenviar el código.', 'error');

                // --- ▼▼▼ INICIO DE LA MODIFICACIÓN ▼▼▼ ---
                // FALLO: Detener el timer y rehabilitar el botón
                const timerId = linkElement.dataset.timerId;
                if (timerId) {
                    clearInterval(parseInt(timerId)); // Detener el intervalo
                }
                linkElement.textContent = originalText; // Restaurar texto base
                linkElement.classList.remove('disabled-interactive');
                linkElement.style.opacity = '1';
                linkElement.style.textDecoration = '';
                delete linkElement.dataset.timerId; // Limpiar el ID del timer
                // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
            }
            return;
        }

        // --- Lógica existente para 'prev-step' y 'next-step' ---

        const registerForm = button.closest('#register-form');
        if (!registerForm) return;

        const errorDiv = registerForm.querySelector('#register-error'); // Mantener para errores de validación
        if (!errorDiv) return; // Aunque showAuthError ya no lo use directamente

        const currentStepEl = button.closest('.auth-step');
        if (!currentStepEl) return;

        const currentStep = parseInt(currentStepEl.getAttribute('data-step'), 10);

        if (action === 'prev-step') {
            // Lógica sin cambios
            const form = button.closest('form');
            const prevStepEl = form.querySelector(`[data-step="${currentStep - 1}"]`);
            if (prevStepEl) {
                currentStepEl.style.display = 'none';
                prevStepEl.style.display = 'block';
                // Opcional: Ocultar el errorDiv si se muestra
                const errorDivElement = form.querySelector('.auth-error-message');
                if (errorDivElement) errorDivElement.style.display = 'none';
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
                } else if (!emailInput.value.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) { // Mejor validación de formato
                    isValid = false;
                    clientErrorMessage = 'El formato de correo no es válido.';
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
                // Usamos showAlert en lugar de errorDiv
                window.showAlert(clientErrorMessage, 'error');
                return;
            }

            // Ocultar errorDiv si aún existe y estaba visible
            const errorDivElement = registerForm.querySelector('.auth-error-message');
            if (errorDivElement) errorDivElement.style.display = 'none';

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
                showAuthError(null, result.message || 'Error desconocido.'); // Pasar null a showAuthError
            }

            // Asegurarse de rehabilitar el botón solo si no hubo navegación exitosa
            if (!result.success || !nextPath) {
                 button.disabled = false;
                 button.textContent = 'Continuar';
            }
            // Si la navegación fue exitosa, el botón desaparecerá al cargar la nueva página
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
    // Sin cambios necesarios aquí para el texto del timer
    document.body.addEventListener('click', async e => {
        const button = e.target.closest('[data-auth-action]');
        if (!button) return;

        const resetForm = button.closest('#reset-form');
        if (!resetForm) return;

        const errorDiv = resetForm.querySelector('#reset-error'); // Mantener para validación
        // if (!errorDiv) return; // Permitir que funcione sin errorDiv

        const action = button.getAttribute('data-auth-action');
        const currentStepEl = button.closest('.auth-step');
        if (!currentStepEl) return;

        const currentStep = parseInt(currentStepEl.getAttribute('data-step'), 10);

        if (action === 'prev-step') {
            const prevStepEl = resetForm.querySelector(`[data-step="${currentStep - 1}"]`);
            if (prevStepEl) {
                currentStepEl.style.display = 'none';
                prevStepEl.style.display = 'block';
                if(errorDiv) errorDiv.style.display = 'none'; // Ocultar si existe
            }
            return;
        }

        if (action === 'next-step') {
            let isValid = true;
            let clientErrorMessage = 'Por favor, completa todos los campos.';

            if (currentStep === 1) {
                const emailInput = currentStepEl.querySelector('#reset-email');
                if (!emailInput.value || !emailInput.value.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) { // Validar formato
                    isValid = false;
                    clientErrorMessage = 'Por favor, introduce un email válido.';
                }
            }
            else if (currentStep === 2) {
                const codeInput = currentStepEl.querySelector('#reset-code');
                 // Simplificar validación, el formato ya se fuerza en input
                if (!codeInput.value || codeInput.value.length < 14) {
                    isValid = false;
                    clientErrorMessage = 'Por favor, introduce el código de verificación completo.';
                }
            }

            if (!isValid) {
                showAuthError(null, clientErrorMessage); // Pasar null
                return;
            }

            if(errorDiv) errorDiv.style.display = 'none'; // Ocultar si existe
            button.disabled = true;
            button.textContent = 'Verificando...';

            // --- LÓGICA DE FETCH REFACTORIZADA (sin cambios) ---
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
                 // Mover al siguiente paso
                const nextStepEl = resetForm.querySelector(`[data-step="${currentStep + 1}"]`);
                if (nextStepEl) {
                    currentStepEl.style.display = 'none';
                    nextStepEl.style.display = 'block';
                    // Enfocar el primer input del siguiente paso si existe
                    const nextInput = nextStepEl.querySelector('input:not([type="hidden"]), textarea, select');
                    if (nextInput) nextInput.focus();
                }

                // Guardar datos en sessionStorage si es necesario
                if (currentStep === 1) {
                    sessionStorage.setItem('resetEmail', resetForm.querySelector('#reset-email').value);
                     if (result.message) { // Mostrar mensaje informativo del backend (ej. código simulado enviado)
                        window.showAlert(result.message, 'info');
                    }
                }
                else if (currentStep === 2) {
                    sessionStorage.setItem('resetCode', resetForm.querySelector('#reset-code').value);
                     // Opcional: Mostrar mensaje de éxito si el backend lo envía
                     if (result.message) {
                        window.showAlert(result.message, 'success');
                     }
                }

            } else {
                showAuthError(null, result.message || 'Error desconocido.'); // Pasar null
            }
            // --- FIN DEL REFACTOR ---

            // Rehabilitar botón solo si falló
            if (!result.success) {
                button.disabled = false;
                 // Restaurar texto correcto según el paso
                button.textContent = (currentStep === 1) ? 'Enviar Código' : 'Verificar';
            }
             // Si tuvo éxito, el botón desaparece al cambiar de paso
        }
    });
}

function initLoginWizard() {
    // Sin cambios necesarios aquí para el texto del timer
    document.body.addEventListener('click', async e => {
        const button = e.target.closest('[data-auth-action]');
        if (!button) return;

        const loginForm = button.closest('#login-form');
        if (!loginForm) return;

        const errorDiv = loginForm.querySelector('#login-error'); // Mantener para validación
        // if (!errorDiv) return; // Permitir que funcione sin

        const action = button.getAttribute('data-auth-action');
        const currentStepEl = button.closest('.auth-step');
        if (!currentStepEl) return;

        const currentStep = parseInt(currentStepEl.getAttribute('data-step'), 10);

        if (action === 'prev-step') {
            const prevStepEl = loginForm.querySelector(`[data-step="${currentStep - 1}"]`);
            if (prevStepEl) {
                currentStepEl.style.display = 'none';
                prevStepEl.style.display = 'block';
                if(errorDiv) errorDiv.style.display = 'none'; // Ocultar si existe
            }
            return;
        }

        if (action === 'next-step') { // Solo aplica al paso 1
            const emailInput = currentStepEl.querySelector('#login-email');
            const passwordInput = currentStepEl.querySelector('#login-password');
            if (!emailInput.value || !passwordInput.value) {
                showAuthError(null, 'Por favor, completa email y contraseña.'); // Pasar null
                return;
            }

            if(errorDiv) errorDiv.style.display = 'none'; // Ocultar si existe
            button.disabled = true;
            button.textContent = 'Procesando...';

            // --- LÓGICA DE FETCH REFACTORIZADA (sin cambios) ---
            const formData = new FormData(loginForm);
            formData.append('action', 'login-check-credentials');

            const result = await callAuthApi(formData);

            if (result.success) {
                if (result.is_2fa_required) {
                    const nextStepEl = loginForm.querySelector(`[data-step="${currentStep + 1}"]`);
                    if (nextStepEl) {
                        currentStepEl.style.display = 'none';
                        nextStepEl.style.display = 'block';
                         // Enfocar el input del código 2FA
                         const nextInput = nextStepEl.querySelector('input#login-code');
                         if (nextInput) nextInput.focus();
                    }
                     // Opcional: Mostrar mensaje si el backend lo envía (ej. código enviado)
                     if (result.message) {
                        window.showAlert(result.message, 'info');
                     }
                } else {
                    // Login directo, redirigir
                     // Opcional: Mostrar mensaje de éxito antes de redirigir
                     if (result.message) {
                         window.showAlert(result.message, 'success');
                         await new Promise(resolve => setTimeout(resolve, 500)); // Pequeña pausa
                     }
                    window.location.href = window.projectBasePath + '/';
                }
            } else {
                showAuthError(null, result.message || 'Error desconocido.'); // Pasar null
                button.disabled = false; // Rehabilitar en caso de error
                button.textContent = 'Continuar';
            }
            // --- FIN DEL REFACTOR ---

             // No rehabilitar el botón si tuvo éxito y cambió de paso o redirigió
             if(result.success && result.is_2fa_required) {
                // El botón desapareció al cambiar de paso
             } else if (!result.success) {
                // Ya se rehabilitó en el bloque else
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
            // Asumiendo que el único submit es en el paso 2 (2FA)
            handleLoginFinalSubmit(e);
        } else if (e.target.id === 'register-form') {
             // Asumiendo que el único submit es en el paso 3 (verificación)
            handleRegistrationSubmit(e);
        } else if (e.target.id === 'reset-form') {
             // Asumiendo que el único submit es en el paso 3 (nueva contraseña)
            handleResetSubmit(e);
        }
    });

    // --- Lógica para iniciar timer al cargar página (sin cambios) ---
    try {
        const step3Fieldset = document.querySelector('#register-form [data-step="3"]');
        if (step3Fieldset && step3Fieldset.classList.contains('active')) {
            const link = document.getElementById('register-resend-code-link');
            if (link) {
                const cooldownSeconds = parseInt(link.dataset.cooldown || '0', 10);
                if (cooldownSeconds > 0) {
                    startResendTimer(link, cooldownSeconds);
                }
            }
        }
    } catch (e) {
        console.error("Error al iniciar el temporizador de reenvío:", e);
    }
}
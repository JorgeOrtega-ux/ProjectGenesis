/* ====================================== */
/* ========= AUTH-MANAGER.JS ============ */
/* ====================================== */

// URL del endpoint de PHP
const AUTH_ENDPOINT = `${window.projectBasePath}/auth_handler.php`;

/**
 * Función genérica para manejar el envío FINAL del formulario de registro
 * @param {Event} e - El evento de submit del formulario
 */
async function handleRegistrationSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const button = form.querySelector('button[type="submit"]');
    const errorDiv = document.getElementById('register-error');
    
    // Desactivar botón y ocultar error
    button.disabled = true;
    button.textContent = 'Verificando...';
    errorDiv.style.display = 'none';

    try {
        const formData = new FormData(form);
        // La acción final es 'register-verify'
        formData.append('action', 'register-verify'); 

        const response = await fetch(AUTH_ENDPOINT, {
            method: 'POST',
            body: formData,
        });

        if (!response.ok) {
            throw new Error('Error en la respuesta del servidor.');
        }

        const result = await response.json();

        if (result.success) {
            // ¡Éxito! Redirigir a home
            window.location.href = window.projectBasePath + '/';
        } else {
            showAuthError(errorDiv, result.message || 'Ha ocurrido un error.');
        }

    } catch (error) {
        console.error('Error en fetch:', error);
        showAuthError(errorDiv, 'No se pudo conectar con el servidor.');
    } finally {
        button.disabled = false;
        button.textContent = 'Verificar y Crear Cuenta';
    }
}

/**
 * Lógica para el formulario de LOGIN (sin cambios)
 */
async function handleLoginFormSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const button = form.querySelector('button[type="submit"]');
    const errorDiv = document.getElementById('login-error');
    
    button.disabled = true;
    button.textContent = 'Procesando...';
    errorDiv.style.display = 'none';

    try {
        const formData = new FormData(form);
        formData.append('action', 'login');

        const response = await fetch(AUTH_ENDPOINT, {
            method: 'POST',
            body: formData,
        });

        if (!response.ok) {
            throw new Error('Error en la respuesta del servidor.');
        }

        const result = await response.json();

        if (result.success) {
            window.location.href = window.projectBasePath + '/';
        } else {
            showAuthError(errorDiv, result.message || 'Ha ocurrido un error.');
        }

    } catch (error) {
        console.error('Error en fetch:', error);
        showAuthError(errorDiv, 'No se pudo conectar con el servidor.');
    } finally {
        button.disabled = false;
        button.textContent = 'Continuar';
    }
}

/** Muestra un mensaje de error en el div correspondiente */
function showAuthError(errorDiv, message) {
    if (errorDiv) {
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
    }
}

/**
 * Lógica para mostrar/ocultar contraseña
 */
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

/**
 * Inicializa la lógica del asistente de registro multi-paso
 * USANDO DELEGACIÓN DE EVENTOS
 */
function initRegisterWizard() {
    
    // 1. DELEGAR EL CLICK AL BODY
    document.body.addEventListener('click', async e => {
        // Buscar si el click fue en un botón de acción del wizard
        const button = e.target.closest('[data-auth-action]');
        if (!button) return; // Click en otro lugar, ignorar

        // Encontrar el formulario y el div de error RELATIVO al botón
        const registerForm = button.closest('#register-form');
        if (!registerForm) return; // No está dentro del formulario de registro, ignorar

        const errorDiv = registerForm.querySelector('#register-error');
        if (!errorDiv) return;

        const action = button.getAttribute('data-auth-action');
        const currentStepEl = button.closest('.auth-step');
        if (!currentStepEl) return;
        
        const currentStep = parseInt(currentStepEl.getAttribute('data-step'), 10);
        
        // --- Lógica para ir hacia ATRÁS ---
        if (action === 'prev-step') {
            const prevStepEl = registerForm.querySelector(`[data-step="${currentStep - 1}"]`);
            if (prevStepEl) {
                currentStepEl.style.display = 'none';
                prevStepEl.style.display = 'block';
                errorDiv.style.display = 'none'; // Ocultar errores
            }
            return;
        }

        // --- Lógica para ir hacia ADELANTE ---
        if (action === 'next-step') {
            
            // 1. Validar campos del paso actual (CLIENT-SIDE)
            let isValid = true;
            const inputs = currentStepEl.querySelectorAll('input[required]');
            inputs.forEach(input => {
                if (!input.value) {
                    isValid = false;
                }
                if (input.type === 'email' && !/^\S+@\S+\.\S+$/.test(input.value)) {
                    isValid = false;
                }
            });

            if (!isValid) {
                showAuthError(errorDiv, 'Por favor, completa todos los campos correctamente.');
                return;
            }

            // Ocultar error si todo está bien
            errorDiv.style.display = 'none';
            
            // 2. Realizar llamadas al BACKEND para verificar
            button.disabled = true;
            button.textContent = 'Verificando...';

            try {
                const formData = new FormData(registerForm);
                let fetchAction = '';

                // --- De Paso 1 a Paso 2 ---
                if (currentStep === 1) {
                    fetchAction = 'register-check-email';
                }
                // --- De Paso 2 a Paso 3 ---
                else if (currentStep === 2) {
                    fetchAction = 'register-check-username-and-generate-code';
                }

                formData.append('action', fetchAction);

                const response = await fetch(AUTH_ENDPOINT, {
                    method: 'POST',
                    body: formData,
                });

                if (!response.ok) throw new Error('Error de servidor.');
                
                const result = await response.json();

                if (result.success) {
                    // Éxito: Ocultar paso actual, mostrar el siguiente
                    const nextStepEl = registerForm.querySelector(`[data-step="${currentStep + 1}"]`);
                    if (nextStepEl) {
                        currentStepEl.style.display = 'none';
                        nextStepEl.style.display = 'block';
                    }
                } else {
                    showAuthError(errorDiv, result.message || 'Error desconocido.');
                }

            } catch (error) {
                showAuthError(errorDiv, 'No se pudo conectar con el servidor.');
            } finally {
                button.disabled = false;
                button.textContent = 'Continuar';
            }
        }
    });

    // 2. DELEGAR EL INPUT AL BODY (--- ¡BLOQUE CORREGIDO! ---)
    document.body.addEventListener('input', e => {
        // Asegurarse que el input es el de código Y está dentro del form de registro
        if (e.target.id === 'register-code' && e.target.closest('#register-form')) {
            
            // 1. Quitar CUALQUIER COSA que no sea un caracter hexadecimal (0-9, a-f, A-F)
            let input = e.target.value.replace(/[^0-9a-fA-F]/g, '');
            
            // 2. Convertir a mayúsculas para consistencia visual
            input = input.toUpperCase();

            // 3. Limitar a 12 caracteres (sin guiones)
            input = input.substring(0, 12);

            // 4. Reformatear con guiones
            let formatted = '';
            for (let i = 0; i < input.length; i++) {
                if (i > 0 && i % 4 === 0) {
                    formatted += '-';
                }
                formatted += input[i];
            }
            
            // 5. Asignar el valor formateado
            e.target.value = formatted;
        }
    });
}


/**
 * Inicializador principal del módulo de autenticación
 */
export function initAuthManager() {
    
    // Inicializar los botones de mostrar/ocultar contraseña
    initPasswordToggles();

    // Inicializar el asistente de registro (ahora usa delegación)
    initRegisterWizard();

    // Asignar listeners a los formularios (esto también usa delegación, está bien)
    document.body.addEventListener('submit', e => {
        if (e.target.id === 'login-form') {
            handleLoginFormSubmit(e);
        } else if (e.target.id === 'register-form') {
            // El submit solo se dispara en el último paso
            handleRegistrationSubmit(e);
        }
    });
}
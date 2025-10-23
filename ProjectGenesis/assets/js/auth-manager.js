/* ====================================== */
/* ========= AUTH-MANAGER.JS ============ */
/* ====================================== */

// URL del endpoint de PHP
// Usamos la variable global definida en index.php
const AUTH_ENDPOINT = `${window.projectBasePath}/auth_handler.php`;

/**
 * Función genérica para manejar el envío de formularios (login/register)
 * @param {Event} e - El evento de submit del formulario
 * @param {'login' | 'register'} action - La acción a realizar
 */
async function handleAuthFormSubmit(e, action) {
    e.preventDefault();
    const form = e.target;
    const button = form.querySelector('button[type="submit"]');
    const errorDiv = document.getElementById(`${action}-error`);
    
    // Desactivar botón y ocultar error
    button.disabled = true;
    button.textContent = 'Procesando...';
    errorDiv.style.display = 'none';

    try {
        const formData = new FormData(form);
        formData.append('action', action);

        const response = await fetch(AUTH_ENDPOINT, {
            method: 'POST',
            body: formData,
        });

        if (!response.ok) {
            throw new Error('Error en la respuesta del servidor.');
        }

        const result = await response.json();

        if (result.success) {
            // --- MODIFICACIÓN ---
            // ¡Éxito!
            // Tanto login como register (con auto-login) ahora redirigen a home.
            window.location.href = window.projectBasePath + '/';
            // --- FIN DE LA MODIFICACIÓN ---
            
        } else {
            // Mostrar error
            showAuthError(errorDiv, result.message || 'Ha ocurrido un error.');
        }

    } catch (error) {
        console.error('Error en fetch:', error);
        showAuthError(errorDiv, 'No se pudo conectar con el servidor.');
    } finally {
        // Reactivar botón
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
 * (Movida desde url-manager.js para centralizar lógica de auth)
 */
function initPasswordToggles() {
    // Usamos delegación de eventos en el body
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
 * Inicializador principal del módulo de autenticación
 */
export function initAuthManager() {
    
    // Inicializar los botones de mostrar/ocultar contraseña
    initPasswordToggles();

    // Asignar listeners a los formularios
    // Usamos delegación en el body porque los formularios se cargan dinámicamente
    document.body.addEventListener('submit', e => {
        if (e.target.id === 'login-form') {
            handleAuthFormSubmit(e, 'login');
        } else if (e.target.id === 'register-form') {
            handleAuthFormSubmit(e, 'register');
        }
    });
}
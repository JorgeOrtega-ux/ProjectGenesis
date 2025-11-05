// FILE: assets/js/modules/groups-manager.js

// IMPORTANTE: Debes añadir `callGroupsApi` a tu archivo 'api-service.js'
import { callGroupsApi } from '../services/api-service.js';
import { showAlert } from '../services/alert-manager.js';
import { getTranslation } from '../services/i18n-manager.js';

/**
 * Muestra un spinner en un botón de acción.
 * @param {HTMLElement} button El botón a modificar.
 * @param {string} text El texto original del botón (opcional, si no está en dataset).
 * @param {boolean} isLoading True para mostrar spinner, false para restaurar.
 */
function toggleButtonSpinner(button, text, isLoading) {
    if (!button) return;
    button.disabled = isLoading;
    if (isLoading) {
        button.dataset.originalText = button.textContent;
        // Spinner para botones primarios (fondo oscuro, spinner claro)
        const spinnerStyle = 'width: 20px; height: 20px; border-width: 2px; margin: 0 auto; border-top-color: #ffffff; border-left-color: #ffffff20; border-bottom-color: #ffffff20; border-right-color: #ffffff20;';
        button.innerHTML = `<span class="logout-spinner" style="${spinnerStyle}"></span>`;
    } else {
        button.innerHTML = button.dataset.originalText || text;
    }
}

/**
 * Muestra un error en línea dentro de la tarjeta.
 * @param {HTMLElement} cardElement La tarjeta (o contenedor) donde buscar el div de error.
 * @param {string} messageKey La clave i18n del error.
 */
function showInlineError(cardElement, messageKey) {
    if (!cardElement) return;
    const errorDiv = cardElement.querySelector('.component-card__error');
    if (!errorDiv) return;

    errorDiv.textContent = getTranslation(messageKey);
    errorDiv.classList.add('active');
    errorDiv.classList.remove('disabled');
}

/**
 * Oculta el error en línea.
 * @param {HTMLElement} cardElement La tarjeta (o contenedor) donde buscar el div de error.
 */
function hideInlineError(cardElement) {
    if (!cardElement) return;
    const errorDiv = cardElement.querySelector('.component-card__error');
    if (errorDiv) {
        errorDiv.classList.remove('active');
        errorDiv.classList.add('disabled');
    }
}

export function initGroupsManager() {

    document.body.addEventListener('click', async (event) => {
        
        // 1. Escuchar el clic en el botón de "Unirme"
        const joinButton = event.target.closest('#join-group-submit-btn');
        if (!joinButton) return;

        event.preventDefault();

        // 2. Obtener elementos del formulario
        const card = joinButton.closest('#join-group-card');
        const accessCodeInput = card.querySelector('#join-group-access-code');
        const csrfTokenInput = card.closest('.component-wrapper').querySelector('input[name="csrf_token"]');

        if (!card || !accessCodeInput || !csrfTokenInput) {
            console.error("Faltan elementos del formulario de unirse a grupo.");
            return;
        }

        hideInlineError(card);

        // 3. Validación del lado del cliente
        const accessCode = accessCodeInput.value.trim();
        if (!accessCode) {
            // (i18n key) groups.join.js.error.codeEmpty
            showInlineError(card, 'groups.join.js.error.codeEmpty');
            return;
        }

        // 4. Mostrar estado de carga y preparar FormData
        toggleButtonSpinner(joinButton, getTranslation('groups.join.button'), true);

        const formData = new FormData();
        formData.append('action', 'join-group');
        formData.append('access_code', accessCode);
        formData.append('csrf_token', csrfTokenInput.value);

        try {
            // 5. Llamar a la API (asumiendo que 'callGroupsApi' fue añadida a api-service.js)
            const result = await callGroupsApi(formData);

            if (result.success) {
                // 6. Éxito: Mostrar alerta y redirigir a Home
                // (i18n key) groups.join.js.success.joined
                showAlert(getTranslation(result.message || 'groups.join.js.success.joined'), 'success');

                // Redirigir a 'home' usando el router de JS
                setTimeout(() => {
                    const link = document.createElement('a');
                    link.href = window.projectBasePath + '/'; // Ir a la raíz (home)
                    link.setAttribute('data-nav-js', 'true');
                    document.body.appendChild(link);
                    link.click();
                    link.remove();
                }, 1500);

            } else {
                // 7. Error: Mostrar error en línea y restaurar botón
                showInlineError(card, result.message || 'js.auth.errorUnknown');
                toggleButtonSpinner(joinButton, getTranslation('groups.join.button'), false);
            }

        } catch (error) {
            // 8. Error de Red: Mostrar error y restaurar botón
            console.error('Error al unirse a grupo:', error);
            showInlineError(card, 'js.api.errorConnection');
            toggleButtonSpinner(joinButton, getTranslation('groups.join.button'), false);
        }
    });

    // --- ▼▼▼ INICIO DE MODIFICACIÓN: Listener de input actualizado ▼▼▼ ---
    // Limpiar errores y formatear input al escribir
    document.body.addEventListener('input', (event) => {
        const accessCodeInput = event.target.closest('#join-group-access-code');
        if (accessCodeInput) {
            // Ocultar error
            hideInlineError(accessCodeInput.closest('#join-group-card'));
            
            // Formatear: XXXX-XXXX-XXXX y Mayúsculas
            let input = accessCodeInput.value.replace(/[^0-9a-zA-Z]/g, '');
            input = input.toUpperCase();
            input = input.substring(0, 12); // 12 caracteres

            let formatted = '';
            for (let i = 0; i < input.length; i++) {
                if (i > 0 && i % 4 === 0) {
                    formatted += '-';
                }
                formatted += input[i];
            }
            accessCodeInput.value = formatted;
        }
    });
    // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
}
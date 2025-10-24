/* ====================================== */
/* ======== SETTINGS-MANAGER.JS ========= */
/* ====================================== */

const SETTINGS_ENDPOINT = `${window.projectBasePath}/api/settings_handler.php`;

// --- ▼▼▼ FUNCIONES MODIFICADAS ▼▼▼ ---

function showAvatarError(message) {
    // Oculta el div de error del formulario
    const errorDiv = document.getElementById('avatar-error');
    if (errorDiv) {
        // errorDiv.textContent = message; // Ya no es necesario
        errorDiv.style.display = 'none'; // Nos aseguramos que esté oculto
    }
    // Muestra la nueva alerta global
    window.showAlert(message, 'error');
}

function hideAvatarError() {
    // Esta función ahora solo oculta el div por si acaso
    const errorDiv = document.getElementById('avatar-error');
    if (errorDiv) {
        errorDiv.style.display = 'none';
    }
}
// --- ▲▲▲ FIN FUNCIONES MODIFICADAS ▲▲▲ ---

function toggleButtonSpinner(button, text, isLoading) {
    if (!button) return;
    
    button.disabled = isLoading;
    
    if (isLoading) {
        button.dataset.originalText = button.textContent;
        button.innerHTML = `
            <span class="logout-spinner" 
                  style="width: 20px; height: 20px; border-width: 2px; margin: 0 auto; border-top-color: inherit;">
            </span>`;
    } else {
        button.innerHTML = button.dataset.originalText || text;
    }
}

// --- ▼▼▼ NUEVA FUNCIÓN HELPER PARA EL CURSOR ▼▼▼ ---
/**
 * Enfoca un input y posiciona el cursor al final del texto.
 * @param {HTMLInputElement} inputElement El elemento input a enfocar.
 */
/**
 * Enfoca un input y posiciona el cursor al final del texto.
 * @param {HTMLInputElement} inputElement El elemento input a enfocar.
 */
function focusInputAndMoveCursorToEnd(inputElement) {
    if (!inputElement) return;
    
    const length = inputElement.value.length;
    const originalType = inputElement.type; // <-- 1. Guardar el tipo original

    try {
        // --- INICIO DE LA CORRECCIÓN ---
        // Cambiar temporalmente a 'text' para asegurar que setSelectionRange
        // funcione en todos los navegadores, especialmente con tipos como 'email'.
        // O con tipos de input numéricos (como el código)
        if (inputElement.type === 'email' || inputElement.type === 'text') {
             inputElement.type = 'text'; 
        }
        // --- FIN DE LA CORRECCIÓN ---

        inputElement.focus();
        
        // Mover el cursor al final
        setTimeout(() => {
            try {
                inputElement.setSelectionRange(length, length);
            } catch (e) {
                // Ignorar el error si falla, pero el focus ya está hecho
            }
            
            // --- INICIO DE LA CORRECCIÓN ---
            // Restaurar el tipo original después de establecer la selección
            inputElement.type = originalType; 
            // --- FIN DE LA CORRECCIÓN ---

        }, 0);

    } catch (e) {
        // Si todo falla, al menos restaurar el tipo
        inputElement.type = originalType;
    }
}
// --- ▲▲▲ FIN NUEVA FUNCIÓN HELPER ▲▲▲ ---


// --- FUNCIÓN DE INICIALIZACIÓN REFACTORIZADA ---

export function initSettingsManager() {

    // 1. Delegación para CLICS en botones
    document.body.addEventListener('click', async (e) => {
        const fileInput = document.getElementById('avatar-upload-input');

        // --- Lógica de Avatar (EXISTENTE) ---
        if (e.target.closest('#avatar-preview-container')) {
            e.preventDefault();
            hideAvatarError(); // Oculta errores antiguos
            if (fileInput) {
                fileInput.click();
            }
            return;
        }

        // Click en "Subir foto" o "Cambiar foto"
        if (e.target.closest('#avatar-upload-trigger') || e.target.closest('#avatar-change-trigger')) {
            e.preventDefault();
            hideAvatarError(); // Oculta errores antiguos
            if (fileInput) {
                fileInput.click();
            }
            return;
        }

        // Click en "Cancelar"
        if (e.target.closest('#avatar-cancel-trigger')) {
            e.preventDefault();
            const previewImage = document.getElementById('avatar-preview-image');
            const originalAvatarSrc = previewImage.dataset.originalSrc; 
            const avatarForm = document.getElementById('avatar-form'); // <-- MODIFICADO: Obtenemos el form

            if (previewImage && originalAvatarSrc) {
                previewImage.src = originalAvatarSrc;
            }
            if (avatarForm) {
                avatarForm.reset(); // <-- MODIFICADO: Ahora sí funciona
            }
            hideAvatarError(); // Oculta errores

            // Restaurar botones
            document.getElementById('avatar-actions-preview').style.display = 'none';
            
            // --- INICIO DE LA LÓGICA CORREGIDA ---
            // Leemos el estado que guardamos en el formulario
            const originalState = avatarForm.dataset.originalActions; 

            if (originalState === 'default') {
                document.getElementById('avatar-actions-default').style.display = 'flex';
            } else {
                document.getElementById('avatar-actions-custom').style.display = 'flex';
            }
            // --- FIN DE LA LÓGICA CORREGIDA ---
            
            return;
        }

        // Click en "Eliminar foto"
        if (e.target.closest('#avatar-remove-trigger')) {
            e.preventDefault();
            const avatarForm = document.getElementById('avatar-form'); // <-- AÑADIDO
            if (!avatarForm) return; // <-- AÑADIDO

            // --- ▼▼▼ LÍNEA ELIMINADA ▼▼▼ ---
            // if (!confirm('¿Estás seguro...')) { return; }
            // --- ▲▲▲ LÍNEA ELIMINADA ▲▲▲ ---

            hideAvatarError();
            const removeTrigger = e.target.closest('#avatar-remove-trigger');
            toggleButtonSpinner(removeTrigger, 'Eliminar foto', true);

            try {
                const formData = new FormData(avatarForm);
                formData.append('action', 'remove-avatar');

                const response = await fetch(SETTINGS_ENDPOINT, {
                    method: 'POST',
                    body: formData,
                });

                if (!response.ok) throw new Error('Error de conexión con el servidor.');

                const result = await response.json();
                if (result.success) {
                    window.showAlert(result.message || 'Avatar eliminado.', 'success'); // <-- NUEVO
                    // Recargar la página para ver todos los cambios (avatar en header, etc.)
                    setTimeout(() => location.reload(), 1500); // Dar tiempo a leer
                } else {
                    showAvatarError(result.message || 'Error desconocido al eliminar.'); // Usará la nueva alerta
                    toggleButtonSpinner(removeTrigger, 'Eliminar foto', false);
                }
            } catch (error) {
                showAvatarError(error.message); // Usará la nueva alerta
                toggleButtonSpinner(removeTrigger, 'Eliminar foto', false);
            }
        }
        // --- Fin Lógica de Avatar ---


        // --- LÓGICA PARA NOMBRE DE USUARIO ---

        // Click en "Editar" nombre de usuario
        if (e.target.closest('#username-edit-trigger')) {
            e.preventDefault();
            document.getElementById('username-view-state').style.display = 'none';
            document.getElementById('username-actions-view').style.display = 'none';
            
            document.getElementById('username-edit-state').style.display = 'flex';
            document.getElementById('username-actions-edit').style.display = 'flex';
            
            focusInputAndMoveCursorToEnd(document.getElementById('username-input'));
            return;
        }

        // Click en "Cancelar" edición de nombre
        if (e.target.closest('#username-cancel-trigger')) {
            e.preventDefault();
            
            // Resetear el valor del input al original
            const displayElement = document.getElementById('username-display-text');
            const inputElement = document.getElementById('username-input');
            if (displayElement && inputElement) {
                inputElement.value = displayElement.dataset.originalUsername;
            }

            document.getElementById('username-edit-state').style.display = 'none';
            document.getElementById('username-actions-edit').style.display = 'none';

            document.getElementById('username-view-state').style.display = 'flex';
            document.getElementById('username-actions-view').style.display = 'flex';
            return;
        }
        
        // --- ▼▼▼ LÓGICA PARA EMAIL (MODIFICADA) ▼▼▼ ---

        // Click en "Editar" email
        if (e.target.closest('#email-edit-trigger')) {
            e.preventDefault();
            const editTrigger = e.target.closest('#email-edit-trigger');
            
            // Mostrar spinner en el botón "Editar"
            toggleButtonSpinner(editTrigger, 'Editar', true);

            try {
                // 1. Crear FormData solo con lo necesario
                const csrfToken = document.querySelector('#email-form [name="csrf_token"]');
                const formData = new FormData();
                formData.append('action', 'request-email-change-code');
                formData.append('csrf_token', csrfToken ? csrfToken.value : '');

                // 2. Llamar al backend para solicitar el código
                const response = await fetch(SETTINGS_ENDPOINT, {
                    method: 'POST',
                    body: formData,
                });

                if (!response.ok) throw new Error('Error de conexión al solicitar código.');

                const result = await response.json();

                if (result.success) {
                    
                    // --- ▼▼▼ NUEVA LÓGICA PARA POPULAR EMAIL ▼▼▼ ---
                    const currentEmail = document.getElementById('email-display-text')?.dataset.originalEmail;
                    const modalEmailEl = document.getElementById('email-verify-modal-email');
                    if (modalEmailEl && currentEmail) {
                        modalEmailEl.textContent = currentEmail;
                    }
                    // --- ▲▲▲ FIN NUEVA LÓGICA ▲▲▲ ---

                    // 3. Mostrar el modal de verificación
                    const modal = document.getElementById('email-verify-modal');
                    if(modal) modal.style.display = 'flex';
                    // Enfocar el input del modal
                    focusInputAndMoveCursorToEnd(document.getElementById('email-verify-code'));
                    // Ocultar error (si había)
                    const modalError = document.getElementById('email-verify-error');
                    if(modalError) modalError.style.display = 'none';
                    // Limpiar input
                    const modalInput = document.getElementById('email-verify-code');
                    if(modalInput) modalInput.value = '';

                    window.showAlert('Se ha enviado (simulado) un código a tu correo actual.', 'info');
                } else {
                    // Si falla (ej. ENUM no existe), mostrar error
                    window.showAlert(result.message || 'Error al solicitar el código.', 'error');
                }

            } catch (error) {
                window.showAlert(error.message, 'error');
            } finally {
                // 4. Ocultar spinner del botón "Editar"
                toggleButtonSpinner(editTrigger, 'Editar', false);
            }
            return; // Detener aquí
        }

        // --- ▼▼▼ NUEVA LÓGICA PARA REENVIAR CÓDIGO ▼▼▼ ---
        // Click en "Reenviar código" (en el modal de verificación)
        if (e.target.closest('#email-verify-resend')) {
            e.preventDefault();
            const resendTrigger = e.target.closest('#email-verify-resend');
            const csrfToken = document.querySelector('#email-form [name="csrf_token"]');

            // Usamos 'disabled-interactive' de styles.css
            if (resendTrigger.classList.contains('disabled-interactive')) return;
            
            resendTrigger.classList.add('disabled-interactive');
            const originalText = resendTrigger.textContent;
            resendTrigger.textContent = 'Enviando...';
            
            try {
                const formData = new FormData();
                formData.append('action', 'request-email-change-code');
                formData.append('csrf_token', csrfToken ? csrfToken.value : '');

                const response = await fetch(SETTINGS_ENDPOINT, {
                    method: 'POST',
                    body: formData,
                });

                if (!response.ok) throw new Error('Error de conexión.');
                
                const result = await response.json();

                if (result.success) {
                    window.showAlert('Se ha reenviado (simulado) un nuevo código.', 'success');
                } else {
                    window.showAlert(result.message || 'Error al reenviar el código.', 'error');
                }

            } catch (error) {
                window.showAlert(error.message, 'error');
            } finally {
                resendTrigger.classList.remove('disabled-interactive');
                resendTrigger.textContent = originalText;
            }
            return;
        }
        // --- ▲▲▲ FIN NUEVA LÓGICA REENVIAR ▲▲▲ ---
        
        // --- ▼▼▼ NUEVO: Click en 'X' para cerrar modal ▼▼▼ ---
        if (e.target.closest('#email-verify-close')) {
            e.preventDefault();
            const modal = document.getElementById('email-verify-modal');
            if(modal) modal.style.display = 'none';
            return;
        }
        // --- ▲▲▲ FIN NUEVO BLOQUE ▲▲▲ ---

        // --- ▼▼▼ BLOQUE ELIMINADO ▼▼▼ ---
        // Click en "Cancelar" (en el modal de verificación)
        // if (e.target.closest('#email-verify-cancel')) {
        //     e.preventDefault();
        //     const modal = document.getElementById('email-verify-modal');
        //     if(modal) modal.style.display = 'none';
        //     return;
        // }
        // --- ▲▲▲ FIN BLOQUE ELIMINADO ▲▲▲ ---

        // Click en "Continuar" (en el modal de verificación)
        if (e.target.closest('#email-verify-continue')) {
            e.preventDefault();
            const continueTrigger = e.target.closest('#email-verify-continue');
            const modalError = document.getElementById('email-verify-error');
            const modalInput = document.getElementById('email-verify-code');
            const csrfToken = document.querySelector('#email-form [name="csrf_token"]');

            if (!modalInput || !modalInput.value) {
                if(modalError) {
                    modalError.textContent = 'Por favor, introduce el código.';
                    modalError.style.display = 'block';
                }
                return;
            }

            // Mostrar spinner en "Continuar"
            toggleButtonSpinner(continueTrigger, 'Continuar', true);
            if(modalError) modalError.style.display = 'none';

            try {
                const formData = new FormData();
                formData.append('action', 'verify-email-change-code');
                formData.append('csrf_token', csrfToken ? csrfToken.value : '');
                formData.append('verification_code', modalInput.value);

                const response = await fetch(SETTINGS_ENDPOINT, {
                    method: 'POST',
                    body: formData,
                });

                if (!response.ok) throw new Error('Error de conexión al verificar.');

                const result = await response.json();

                if (result.success) {
                    // ¡Éxito! Ocultar modal
                    const modal = document.getElementById('email-verify-modal');
                    if(modal) modal.style.display = 'none';

                    // Mostrar la sección de edición de email
                    document.getElementById('email-view-state').style.display = 'none';
                    document.getElementById('email-actions-view').style.display = 'none';
                    
                    document.getElementById('email-edit-state').style.display = 'flex';
                    document.getElementById('email-actions-edit').style.display = 'flex';
                    
                    focusInputAndMoveCursorToEnd(document.getElementById('email-input'));
                    
                    window.showAlert(result.message || 'Verificación correcta.', 'success');

                } else {
                    // Mostrar error DENTRO del modal
                    if(modalError) {
                        modalError.textContent = result.message || 'Error al verificar.';
                        modalError.style.display = 'block';
                    }
                }

            } catch (error) {
                 if(modalError) {
                    modalError.textContent = error.message;
                    modalError.style.display = 'block';
                }
            } finally {
                toggleButtonSpinner(continueTrigger, 'Continuar', false);
            }
            return;
        }

        // Click en "Cancelar" edición de email (el de la vista de input)
        if (e.target.closest('#email-cancel-trigger')) {
            e.preventDefault();
            
            // Resetear el valor del input al original
            const displayElement = document.getElementById('email-display-text');
            const inputElement = document.getElementById('email-input');
            if (displayElement && inputElement) {
                inputElement.value = displayElement.dataset.originalEmail;
            }

            document.getElementById('email-edit-state').style.display = 'none';
            document.getElementById('email-actions-edit').style.display = 'none';

            document.getElementById('email-view-state').style.display = 'flex';
            document.getElementById('email-actions-view').style.display = 'flex';
            return;
        }
        // --- ▲▲▲ FIN LÓGICA DE CLICS ▲▲▲ ---
    });

    // 2. Delegación para el evento SUBMIT del formulario
    document.body.addEventListener('submit', async (e) => {
        
        // --- Lógica de Avatar (EXISTENTE) ---
        if (e.target.id === 'avatar-form') {
            e.preventDefault();
            const avatarForm = e.target;
            const fileInput = document.getElementById('avatar-upload-input');
            const saveTrigger = document.getElementById('avatar-save-trigger');
            
            hideAvatarError();
            
            if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                showAvatarError('Por favor, selecciona un archivo primero.'); // Usará la nueva alerta
                return;
            }

            toggleButtonSpinner(saveTrigger, 'Guardar', true);

            try {
                const formData = new FormData(avatarForm);
                formData.append('action', 'upload-avatar');

                const response = await fetch(SETTINGS_ENDPOINT, {
                    method: 'POST',
                    body: formData,
                });

                if (!response.ok) throw new Error('Error de conexión con el servidor.');

                const result = await response.json();
                if (result.success) {
                    window.showAlert(result.message || 'Avatar actualizado.', 'success'); // <-- NUEVO
                    // Recargar la página para ver todos los cambios
                    setTimeout(() => location.reload(), 1500); // Dar tiempo a leer
                } else {
                    showAvatarError(result.message || 'Error desconocido al guardar.'); // Usará la nueva alerta
                    toggleButtonSpinner(saveTrigger, 'Guardar', false);
                }
            } catch (error) {
                showAvatarError(error.message); // Usará la nueva alerta
                toggleButtonSpinner(saveTrigger, 'Guardar', false);
            }
        }
        // --- Fin Lógica de Avatar ---


        // --- LÓGICA PARA NOMBRE DE USUARIO ---
        if (e.target.id === 'username-form') {
            e.preventDefault();
            const usernameForm = e.target;
            const saveTrigger = document.getElementById('username-save-trigger');
            const inputElement = document.getElementById('username-input');
            const newUsername = inputElement.value;

            // Validación simple en cliente
            if (newUsername.length < 6) {
                window.showAlert('El nombre de usuario debe tener al menos 6 caracteres.', 'error');
                return;
            }

            toggleButtonSpinner(saveTrigger, 'Guardar', true);

            try {
                const formData = new FormData(usernameForm);
                // La acción ya está en el formulario, pero la agregamos por seguridad
                formData.append('action', 'update-username'); 

                const response = await fetch(SETTINGS_ENDPOINT, {
                    method: 'POST',
                    body: formData,
                });

                if (!response.ok) throw new Error('Error de conexión con el servidor.');

                const result = await response.json();
                
                if (result.success) {
                    window.showAlert(result.message || 'Nombre de usuario actualizado.', 'success');
                    // Recargar la página para ver todos los cambios (header, alt text, etc.)
                    // Sigue el mismo patrón que el éxito del avatar.
                    setTimeout(() => location.reload(), 1500); 
                } else {
                    window.showAlert(result.message || 'Error desconocido al guardar.', 'error');
                    toggleButtonSpinner(saveTrigger, 'Guardar', false);
                }

            } catch (error) {
                window.showAlert(error.message, 'error');
                toggleButtonSpinner(saveTrigger, 'Guardar', false);
            }
        }
        
        // --- LÓGICA PARA EMAIL ---
        if (e.target.id === 'email-form') {
            e.preventDefault();
            const emailForm = e.target;
            const saveTrigger = document.getElementById('email-save-trigger');
            const inputElement = document.getElementById('email-input');
            const newEmail = inputElement.value;

            // Validación de email en cliente (regex simple)
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(newEmail)) {
                window.showAlert('Por favor, introduce un correo electrónico válido.', 'error');
                return;
            }
            
            // Validación de dominios en cliente (copia la lógica del servidor)
            const allowedDomains = /@(gmail\.com|outlook\.com|hotmail\.com|yahoo\.com|icloud\.com)$/i;
            if (!allowedDomains.test(newEmail)) {
                 window.showAlert('Solo se permiten correos @gmail, @outlook, @hotmail, @yahoo o @icloud.', 'error');
                return;
            }

            toggleButtonSpinner(saveTrigger, 'Guardar', true);

            try {
                const formData = new FormData(emailForm);
                formData.append('action', 'update-email'); 

                const response = await fetch(SETTINGS_ENDPOINT, {
                    method: 'POST',
                    body: formData,
                });

                if (!response.ok) throw new Error('Error de conexión con el servidor.');

                const result = await response.json();
                
                if (result.success) {
                    window.showAlert(result.message || 'Correo actualizado.', 'success');
                    // Recargar la página para que se actualice en todos lados
                    setTimeout(() => location.reload(), 1500); 
                } else {
                    window.showAlert(result.message || 'Error desconocido al guardar.', 'error');
                    toggleButtonSpinner(saveTrigger, 'Guardar', false);
                }

            } catch (error) {
                window.showAlert(error.message, 'error');
                toggleButtonSpinner(saveTrigger, 'Guardar', false);
            }
        }
        // --- ▲▲▲ FIN NUEVA LÓGICA DE SUBMIT ▲▲▲ ---
    });

    // 3. Delegación para el evento CHANGE del input de archivo
    document.body.addEventListener('change', (e) => {
        if (e.target.id === 'avatar-upload-input') {
            const fileInput = e.target;
            const previewImage = document.getElementById('avatar-preview-image');
            const file = fileInput.files[0];
            
            if (!file) return;

            // ... (validaciones de archivo) ...
            // --- MODIFICACIÓN: Aceptar GIF y WebP ---
            if (!['image/png', 'image/jpeg', 'image/gif', 'image/webp'].includes(file.type)) {
                showAvatarError('Formato no válido (solo PNG, JPEG, GIF o WebP).'); // Usará la nueva alerta
                fileInput.form.reset();
                return;
            }
            // --- MODIFICACIÓN: Límite a 2MB ---
            if (file.size > 2 * 1024 * 1024) {
                showAvatarError('El archivo es demasiado grande (máx 2MB).'); // Usará la nueva alerta
                fileInput.form.reset();
                return;
            }
            
            // Guardar la URL original en el dataset la primera vez
            if (!previewImage.dataset.originalSrc) {
                previewImage.dataset.originalSrc = previewImage.src;
            }

            const reader = new FileReader();
            reader.onload = (event) => {
                previewImage.src = event.target.result;
            };
            reader.readAsDataURL(file);

            // --- INICIO DE LA LÓGICA AÑADIDA ---
            const actionsDefault = document.getElementById('avatar-actions-default');
            const avatarForm = fileInput.form; // Obtenemos el formulario

            // Guardamos el estado actual antes de ocultar los botones
            if (actionsDefault.style.display !== 'none') {
                avatarForm.dataset.originalActions = 'default';
            } else {
                avatarForm.dataset.originalActions = 'custom';
            }
            // --- FIN DE LA LÓGICA AÑADIDA ---

            // Cambiar botones (oculta ambos)
            document.getElementById('avatar-actions-default').style.display = 'none';
            document.getElementById('avatar-actions-custom').style.display = 'none';
            document.getElementById('avatar-actions-preview').style.display = 'flex';
        }

        // --- ▼▼▼ NUEVA LÓGICA PARA FORMATEAR CÓDIGO DE VERIFICACIÓN EN MODAL ▼▼▼ ---
        if (e.target.id === 'email-verify-code') {
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
        // --- ▲▲▲ FIN NUEVA LÓGICA ▲▲▲ ---
    });
    
    // 4. Guardar la URL original (sin cambios)
    setTimeout(() => {
        const previewImage = document.getElementById('avatar-preview-image');
        if (previewImage && !previewImage.dataset.originalSrc) {
            previewImage.dataset.originalSrc = previewImage.src;
        }
    }, 100);
}
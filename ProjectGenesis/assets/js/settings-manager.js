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

// --- FUNCIÓN DE INICIALIZACIÓN REFACTORIZADA ---

export function initSettingsManager() {

    // 1. Delegación para CLICS en botones
    document.body.addEventListener('click', async (e) => {
        const fileInput = document.getElementById('avatar-upload-input');

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
    });

    // 2. Delegación para el evento SUBMIT del formulario (sin cambios)
    document.body.addEventListener('submit', async (e) => {
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
    });

    // 3. Delegación para el evento CHANGE del input de archivo
    document.body.addEventListener('change', (e) => {
        if (e.target.id === 'avatar-upload-input') {
            const fileInput = e.target;
            const previewImage = document.getElementById('avatar-preview-image');
            const file = fileInput.files[0];
            
            if (!file) return;

            // ... (validaciones de archivo) ...
            if (!['image/png', 'image/jpeg'].includes(file.type)) {
                showAvatarError('Formato de archivo no válido (solo PNG o JPEG).'); // Usará la nueva alerta
                fileInput.form.reset();
                return;
            }
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
    });
    
    // 4. Guardar la URL original (sin cambios)
    setTimeout(() => {
        const previewImage = document.getElementById('avatar-preview-image');
        if (previewImage && !previewImage.dataset.originalSrc) {
            previewImage.dataset.originalSrc = previewImage.src;
        }
    }, 100);
}
/* ====================================== */
/* ======== SETTINGS-MANAGER.JS ========= */
/* ====================================== */

const SETTINGS_ENDPOINT = `${window.projectBasePath}/api/settings_handler.php`;

function showAvatarError(message) {
    const errorDiv = document.getElementById('avatar-error');
    if (errorDiv) {
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
    }
}

function hideAvatarError() {
    const errorDiv = document.getElementById('avatar-error');
    if (errorDiv) {
        errorDiv.style.display = 'none';
    }
}

function toggleButtonSpinner(button, text, isLoading) {
    if (!button) return;
    
    button.disabled = isLoading;
    
    if (isLoading) {
        // Guardar texto original y añadir spinner
        button.dataset.originalText = button.textContent;
        button.innerHTML = `
            <span class="logout-spinner" 
                  style="width: 20px; height: 20px; border-width: 2px; margin: 0 auto; border-top-color: inherit;">
            </span>`;
    } else {
        // Restaurar texto
        button.innerHTML = button.dataset.originalText || text;
    }
}


export function initSettingsManager() {
    const avatarForm = document.getElementById('avatar-form');
    
    // Si no estamos en la página de perfil, no hacer nada
    if (!avatarForm) {
        return;
    }

    // --- Elementos del DOM ---
    const fileInput = document.getElementById('avatar-upload-input');
    const previewImage = document.getElementById('avatar-preview-image');
    const originalAvatarSrc = previewImage.src;

    // Triggers
    const uploadTrigger = document.getElementById('avatar-upload-trigger');
    const changeTrigger = document.getElementById('avatar-change-trigger');
    const removeTrigger = document.getElementById('avatar-remove-trigger');
    const cancelTrigger = document.getElementById('avatar-cancel-trigger');
    const saveTrigger = document.getElementById('avatar-save-trigger');

    // Grupos de acciones
    const actionsDefault = document.getElementById('avatar-actions-default');
    const actionsCustom = document.getElementById('avatar-actions-custom');
    const actionsPreview = document.getElementById('avatar-actions-preview');

    // --- Lógica de botones ---

    // 1. Click en "Subir foto" o "Cambiar foto"
    const triggerFileInput = (e) => {
        e.preventDefault();
        hideAvatarError();
        fileInput.click();
    };
    uploadTrigger.addEventListener('click', triggerFileInput);
    changeTrigger.addEventListener('click', triggerFileInput);

    // 2. Cuando se selecciona un archivo
    fileInput.addEventListener('change', () => {
        const file = fileInput.files[0];
        if (!file) {
            return;
        }

        // Validar tipo de archivo (simple)
        if (!['image/png', 'image/jpeg'].includes(file.type)) {
            showAvatarError('Formato de archivo no válido (solo PNG o JPEG).');
            avatarForm.reset();
            return;
        }

        // Validar tamaño (2MB)
        if (file.size > 2 * 1024 * 1024) {
            showAvatarError('El archivo es demasiado grande (máx 2MB).');
            avatarForm.reset();
            return;
        }

        // Mostrar previsualización
        const reader = new FileReader();
        reader.onload = (e) => {
            previewImage.src = e.target.result;
        };
        reader.readAsDataURL(file);

        // Cambiar botones
        actionsDefault.style.display = 'none';
        actionsCustom.style.display = 'none';
        actionsPreview.style.display = 'flex';
    });

    // 3. Click en "Cancelar"
    cancelTrigger.addEventListener('click', () => {
        // Restaurar imagen original
        previewImage.src = originalAvatarSrc;
        avatarForm.reset();
        hideAvatarError();

        // Restaurar botones
        actionsPreview.style.display = 'none';
        // Mostrar el grupo de botones que corresponda (Default o Custom)
        if (actionsDefault.getAttribute('style') === 'display: none;') {
            actionsCustom.style.display = 'flex';
        } else {
            actionsDefault.style.display = 'flex';
        }
    });

    // 4. Click en "Guardar" (Submit del formulario)
    avatarForm.addEventListener('submit', async () => {
        hideAvatarError();
        
        if (!fileInput.files || fileInput.files.length === 0) {
            showAvatarError('Por favor, selecciona un archivo primero.');
            return;
        }

        toggleButtonSpinner(saveTrigger, 'Guardar', true);

        try {
            const formData = new FormData(avatarForm);
            formData.append('action', 'upload-avatar');
            // 'avatar' y 'csrf_token' ya están en el formData

            const response = await fetch(SETTINGS_ENDPOINT, {
                method: 'POST',
                body: formData,
            });

            if (!response.ok) {
                throw new Error('Error de conexión con el servidor.');
            }

            const result = await response.json();

            if (result.success) {
                // ¡Éxito! Recargar la página para que se actualice la sesión
                // y la imagen del header.
                location.reload();
            } else {
                showAvatarError(result.message || 'Error desconocido al guardar.');
                toggleButtonSpinner(saveTrigger, 'Guardar', false);
            }

        } catch (error) {
            showAvatarError(error.message);
            toggleButtonSpinner(saveTrigger, 'Guardar', false);
        }
    });

    // 5. Click en "Eliminar foto"
    removeTrigger.addEventListener('click', async () => {
        if (!confirm('¿Estás seguro de que quieres eliminar tu foto de perfil? Se restaurará la imagen por defecto.')) {
            return;
        }

        hideAvatarError();
        toggleButtonSpinner(removeTrigger, 'Eliminar foto', true);

        try {
            const formData = new FormData(avatarForm);
            formData.append('action', 'remove-avatar');

            const response = await fetch(SETTINGS_ENDPOINT, {
                method: 'POST',
                body: formData,
            });

            if (!response.ok) {
                throw new Error('Error de conexión con el servidor.');
            }

            const result = await response.json();

            if (result.success) {
                // ¡Éxito! Recargar la página
                location.reload();
            } else {
                showAvatarError(result.message || 'Error desconocido al eliminar.');
                toggleButtonSpinner(removeTrigger, 'Eliminar foto', false);
            }

        } catch (error) {
            showAvatarError(error.message);
            toggleButtonSpinner(removeTrigger, 'Eliminar foto', false);
        }
    });
}
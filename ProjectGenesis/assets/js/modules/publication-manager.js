// FILE: assets/js/modules/publication-manager.js

/**
 * Gestiona la lógica de la página de creación de publicaciones.
 * (Por ahora, solo el toggle entre Pestañas)
 */
export function initPublicationManager() {

    // Usamos 'click' en el body para delegación de eventos
    document.body.addEventListener('click', (e) => {
        
        // 1. Buscar si el clic fue en un botón del toggle
        const toggleButton = e.target.closest('#post-type-toggle .component-toggle-tab');
        
        // 2. Asegurarnos de que estamos en la página correcta
        const section = e.target.closest('[data-section*="create-"]');

        if (!toggleButton || !section) {
            return; // No es un clic que nos interese
        }
        
        e.preventDefault();

        // 3. Si ya está activo, no hacer nada
        if (toggleButton.classList.contains('active')) {
            return;
        }

        // 4. Obtener el tipo (data-type)
        const newType = toggleButton.dataset.type; // "post" o "poll"

        // 5. Gestionar la UI
        const postArea = document.getElementById('post-content-area');
        const pollArea = document.getElementById('poll-content-area');
        const toggleContainer = document.getElementById('post-type-toggle');

        // Quitar 'active' de todos los botones
        toggleContainer.querySelectorAll('.component-toggle-tab').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Añadir 'active' al botón clickeado
        toggleButton.classList.add('active');

        // Mostrar/Ocultar las áreas de contenido
        if (newType === 'poll') {
            if (postArea) {
                // 'post-content-area' siempre es visible
                // postArea.classList.remove('active');
                // postArea.style.display = 'none';
            }
            if (pollArea) {
                pollArea.classList.add('active');
                pollArea.classList.remove('disabled');
                pollArea.style.display = 'flex'; // Usar flex para que se vea
            }
            // Actualizar la URL sin recargar
            history.pushState(null, '', window.projectBasePath + '/create-poll');

        } else { // 'post'
            if (postArea) {
                // 'post-content-area' siempre es visible
                // postArea.classList.add('active');
                // postArea.style.display = 'block'; // Usar block
            }
            if (pollArea) {
                pollArea.classList.remove('active');
                pollArea.classList.add('disabled');
                pollArea.style.display = 'none';
            }
            // Actualizar la URL sin recargar
            history.pushState(null, '', window.projectBasePath + '/create-publication');
        }
    });
}
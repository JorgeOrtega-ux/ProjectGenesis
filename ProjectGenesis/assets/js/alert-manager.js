/* ====================================== */
/* ======== ALERT-MANAGER.JS ============ */
/* ====================================== */

/**
 * Muestra una alerta global (toast) en la esquina inferior izquierda.
 * @param {string} message El texto a mostrar.
 * @param {string} type 'info' (default), 'success', o 'error'.
 * @param {number | null} duration Duración en ms. Si es null, usa el default.
 */
// --- ▼▼▼ ¡INICIO DE MODIFICACIÓN: 'duration = null'! ▼▼▼ ---
export function showAlert(message, type = 'info', duration = null) {
    
    // --- ▼▼▼ ¡INICIO DE MODIFICACIÓN: LÓGICA DE DURACIÓN! ▼▼▼ ---
    // Leer la preferencia global (0 = false, 1 = true)
    const isDurationIncreased = (window.userIncreaseMessageDuration == 1);
    
    // Si 'increase_message_duration' está activo, el default es 5000ms.
    // Si está inactivo, el default es 2000ms.
    const defaultDuration = isDurationIncreased ? 5000 : 2000;
    
    // Usar la duración pasada como parámetro, o el default que acabamos de calcular.
    const finalDuration = duration ?? defaultDuration;
    // --- ▲▲▲ ¡FIN DE MODIFICACIÓN! ▲▲▲ ---

    // Obtener el contenedor principal de alertas
    const container = document.getElementById('alert-container');
    if (!container) {
        console.error('No se encontró #alert-container. Asegúrate de añadirlo a tu index.php');
        return;
    }

    // 1. Crear el elemento de la alerta
    const alertElement = document.createElement('div');
    alertElement.className = `alert-toast alert-type-${type}`;
    
    // Icono basado en el tipo (usando Material Symbols)
    let iconName = 'info';
    if (type === 'success') iconName = 'check_circle';
    if (type === 'error') iconName = 'error';

    alertElement.innerHTML = `
        <div class="alert-toast-icon">
            <span class="material-symbols-rounded">${iconName}</span>
        </div>
        <div class="alert-toast-message">${message}</div>
    `;

    // 2. Añadir la alerta al contenedor
    container.appendChild(alertElement);

    // 3. Activar la animación de entrada
    // Usamos un pequeño timeout para asegurar que el CSS aplica la transición
    setTimeout(() => {
        alertElement.classList.add('enter');
    }, 10);

    // 4. Preparar la eliminación
    const removeAlert = () => {
        // Activar animación de salida
        alertElement.classList.add('exit');
        
        // Esperar a que termine la animación de salida para eliminar del DOM
        alertElement.addEventListener('animationend', () => {
            if (alertElement.parentNode === container) {
                container.removeChild(alertElement);
            }
        }, { once: true });
    };

    // 5. Iniciar temporizador para auto-eliminación
    // --- ▼▼▼ ¡INICIO DE MODIFICACIÓN: Usar finalDuration! ▼▼▼ ---
    setTimeout(removeAlert, finalDuration);
    // --- ▲▲▲ ¡FIN DE MODIFICACIÓN! ▲▲▲ ---
}
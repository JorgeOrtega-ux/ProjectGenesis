// --- ▼▼▼ INICIO DE LA MODIFICACIÓN ▼▼▼ ---
const MAX_ALERTS = 3;

/**
 * Inicia la animación de salida y elimina un elemento de alerta del DOM.
 * @param {HTMLElement} alertElement El elemento de alerta a eliminar.
 */
function removeAlert(alertElement) {
    if (!alertElement || !alertElement.parentNode) {
        return;
    }
    
    // ¡Añadido! Evita doble animación si ya se está borrando
    if (alertElement.classList.contains('exit')) {
        return;
    }

    const container = alertElement.parentNode;
    
    // Limpiar cualquier timer de auto-cierre si lo estamos forzando
    if (alertElement.dataset.timerId) {
        clearTimeout(alertElement.dataset.timerId);
    }

    alertElement.classList.add('exit');
    
    alertElement.addEventListener('animationend', () => {
        if (alertElement.parentNode === container) {
            container.removeChild(alertElement);
        }
    }, { once: true });
}
// --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---


export function showAlert(message, type = 'info', duration = null) {
    
    const isDurationIncreased = (window.userIncreaseMessageDuration == 1);
    
    const defaultDuration = isDurationIncreased ? 5000 : 2000;
    
    const finalDuration = duration ?? defaultDuration;

    const container = document.getElementById('alert-container');
    if (!container) {
        console.error('No se encontró #alert-container. Asegúrate de añadirlo a tu index.php');
        return;
    }
    
    // --- ▼▼▼ INICIO DE LA MODIFICACIÓN (LÍMITE DE ALERTAS CORREGIDO) ▼▼▼ ---
        
    // 1. Contar las alertas actuales (¡IGNORANDO las que ya se están borrando!)
    const currentAlerts = container.querySelectorAll('.alert-toast:not(.exit)');

    // 2. Si se alcanza el límite, eliminar la más antigua
    if (currentAlerts.length >= MAX_ALERTS) {
        
        // ¡ESTA ES LA LÍNEA CORREGIDA!
        // Selecciona la primera alerta que NO se esté borrando.
        const oldestAlert = container.querySelector('.alert-toast:not(.exit)'); 
        
        if (oldestAlert) {
            // Usamos la nueva función helper para eliminarla
            removeAlert(oldestAlert);
        }
    }
    
    // --- ▲▲▲ FIN DE LA MODIFICACIÓN (LÍMITE DE ALERTAS CORREGIDO) ▲▲▲ ---

    const alertElement = document.createElement('div');
    alertElement.className = `alert-toast alert-type-${type}`;
    
    let iconName = 'info';
    if (type === 'success') iconName = 'check_circle';
    if (type === 'error') iconName = 'error';

    alertElement.innerHTML = `
        <div class="alert-toast-icon">
            <span class="material-symbols-rounded">${iconName}</span>
        </div>
        <div class="alert-toast-message">${message}</div>
    `;

    container.appendChild(alertElement);

    setTimeout(() => {
        alertElement.classList.add('enter');
    }, 10);

    // --- ▼▼▼ MODIFICADO: Usar la función helper y guardar el ID ▼▼▼ ---
    const timerId = setTimeout(() => {
        removeAlert(alertElement);
    }, finalDuration);
    
    alertElement.dataset.timerId = timerId;
    // --- ▲▲▲ FIN DE MODIFICACIÓN ▼▼▼ ---
}
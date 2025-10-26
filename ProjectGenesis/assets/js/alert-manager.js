export function showAlert(message, type = 'info', duration = null) {
    
    const isDurationIncreased = (window.userIncreaseMessageDuration == 1);
    
    const defaultDuration = isDurationIncreased ? 5000 : 2000;
    
    const finalDuration = duration ?? defaultDuration;

    const container = document.getElementById('alert-container');
    if (!container) {
        console.error('No se encontró #alert-container. Asegúrate de añadirlo a tu index.php');
        return;
    }

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

    const removeAlert = () => {
        alertElement.classList.add('exit');
        
        alertElement.addEventListener('animationend', () => {
            if (alertElement.parentNode === container) {
                container.removeChild(alertElement);
            }
        }, { once: true });
    };

    setTimeout(removeAlert, finalDuration);
}
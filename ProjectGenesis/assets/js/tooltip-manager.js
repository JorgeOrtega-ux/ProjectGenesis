// assets/js/tooltip-manager.js

// Importar Popper.js desde un CDN
import { createPopper } from 'https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/esm/popper.min.js';

// Importar nuestro gestor de traducciones
import { getTranslation } from './i18n-manager.js';

let tooltipEl;
let popperInstance;

/**
 * Crea el elemento de tooltip reutilizable y lo añade al body.
 */
function createTooltipElement() {
    if (!document.getElementById('main-tooltip')) {
        tooltipEl = document.createElement('div');
        tooltipEl.id = 'main-tooltip';
        tooltipEl.className = 'tooltip';
        tooltipEl.setAttribute('role', 'tooltip');
        
        const textEl = document.createElement('div');
        textEl.className = 'tooltip-text';
        tooltipEl.appendChild(textEl);
        
        document.body.appendChild(tooltipEl);
    } else {
        tooltipEl = document.getElementById('main-tooltip');
    }
}

/**
 * Muestra el tooltip para un elemento específico.
 * @param {HTMLElement} target - El elemento que activa el tooltip.
 */
function showTooltip(target) {
    const tooltipKey = target.getAttribute('data-tooltip');
    if (!tooltipKey) return;

    const text = getTranslation(tooltipKey);
    tooltipEl.querySelector('.tooltip-text').textContent = text;
    tooltipEl.style.display = 'block';

    popperInstance = createPopper(target, tooltipEl, {
       placement: 'bottom',
       placement: 'auto',
        modifiers: [
            {
                name: 'offset',
                options: {
                    offset: [0, 8], // 8px de espacio
                },
            },
        ],
    });
}

/**
 * Oculta el tooltip y destruye la instancia de Popper.
 */
function hideTooltip() {
    tooltipEl.style.display = 'none';
    if (popperInstance) {
        popperInstance.destroy();
        popperInstance = null;
    }
}

/**
 * Inicializa los listeners para los tooltips.
 */
export function initTooltipManager() {
    createTooltipElement();

    document.body.addEventListener('mouseover', (e) => {
        const target = e.target.closest('[data-tooltip]');
        if (!target) return;

        // Mostrar instantáneamente
        showTooltip(target);
    });

    document.body.addEventListener('mouseout', (e) => {
        const target = e.target.closest('[data-tooltip]');
        if (!target) return;
        
        hideTooltip();
    });
    
    // Ocultar si se hace clic en cualquier lugar (para botones de menú)
    document.body.addEventListener('click', () => {
         hideTooltip();
    });
}
import { createPopper } from 'https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/esm/popper.min.js';
import { getTranslation } from './i18n-manager.js';

let tooltipEl;
let popperInstance;

function createTooltipElementInstance() {
    const newTooltipEl = document.createElement('div');
    newTooltipEl.id = 'main-tooltip';
    newTooltipEl.className = 'tooltip';
    newTooltipEl.setAttribute('role', 'tooltip');

    const textEl = document.createElement('div');
    textEl.className = 'tooltip-text';
    newTooltipEl.appendChild(textEl);

    return newTooltipEl;
}

function showTooltip(target) {
    const tooltipKey = target.getAttribute('data-tooltip');
    if (!tooltipKey) return;

    tooltipEl = createTooltipElementInstance();
    document.body.appendChild(tooltipEl);

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
                    offset: [0, 8],
                },
            },
        ],
    });
}

function hideTooltip() {
    if (popperInstance) {
        popperInstance.destroy();
        popperInstance = null;
    }

    if (tooltipEl && tooltipEl.parentNode) {
        tooltipEl.parentNode.removeChild(tooltipEl);
        tooltipEl = null;
    }
}

function initTooltipManager() {
    const isCoarsePointer = window.matchMedia && window.matchMedia("(pointer: coarse)").matches;

    if (isCoarsePointer) {
        return;
    }

    document.body.addEventListener('mouseover', (e) => {
        const target = e.target.closest('[data-tooltip]');
        if (!target) return;
        showTooltip(target);
    });

    document.body.addEventListener('mouseout', (e) => {
        const target = e.target.closest('[data-tooltip]');
        if (!target) return;
        hideTooltip();
    });
}

export { initTooltipManager, showTooltip, hideTooltip };
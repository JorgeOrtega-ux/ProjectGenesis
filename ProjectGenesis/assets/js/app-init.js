import { initMainController } from './main-controller.js';
import { initRouter } from './url-manager.js';
import { initAuthManager } from './auth-manager.js';
import { initSettingsManager } from './settings-manager.js';
import { showAlert } from './alert-manager.js'; 
import { initI18nManager } from './i18n-manager.js'; // <-- IMPORTACIÓN EXISTENTE
import { initTooltipManager } from './tooltip-manager.js'; // <-- NUEVA IMPORTACIÓN

const htmlEl = document.documentElement;
const systemThemeQuery = window.matchMedia('(prefers-color-scheme: dark)');

function applyTheme(theme) {
    if (theme === 'light') {
        htmlEl.classList.remove('dark-theme');
        htmlEl.classList.add('light-theme');
    } else if (theme === 'dark') {
        htmlEl.classList.remove('light-theme');
        htmlEl.classList.add('dark-theme');
    } else { 
        if (systemThemeQuery.matches) {
            htmlEl.classList.remove('light-theme');
            htmlEl.classList.add('dark-theme');
        } else {
            htmlEl.classList.remove('dark-theme');
            htmlEl.classList.add('light-theme');
        }
    }
}

window.applyCurrentTheme = applyTheme;

function initThemeManager() {
    applyTheme(window.userTheme || 'system');

    systemThemeQuery.addEventListener('change', (e) => {
        if ((window.userTheme || 'system') === 'system') {
            applyTheme('system');
        }
    });
}


document.addEventListener('DOMContentLoaded', async function () { // <-- CONVERTIDO A ASYNC
    
    window.showAlert = showAlert;

    // --- ▼▼▼ NUEVA MODIFICACIÓN ▼▼▼ ---
    // Carga las traducciones ANTES de inicializar el resto de la UI
    await initI18nManager();
    // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---

    initThemeManager();

    initMainController();
    initRouter(); // <-- Esta función llamará a handleNavigation, que llamará a loadPage (que activa el loader)
    initAuthManager();
    initSettingsManager();
    initTooltipManager(); // <-- NUEVA LLAMADA

    // --- ▼▼▼ INICIO DE LA MODIFICACIÓN ▼▼▼ ---
    // Ya no necesitamos ocultar el loader aquí.
    // loadPage() en url-manager.js se encarga de todo.
    // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
});
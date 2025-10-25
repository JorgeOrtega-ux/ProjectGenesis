/* ====================================== */
/* =========== APP-INIT.JS ============== */
/* ====================================== */
import { initMainController } from './main-controller.js';
import { initRouter } from './url-manager.js';
import { initAuthManager } from './auth-manager.js';
import { initSettingsManager } from './settings-manager.js';
import { showAlert } from './alert-manager.js'; 

// --- ▼▼▼ INICIO: LÓGICA DE TEMA ▼▼▼ ---
const htmlEl = document.documentElement;
const systemThemeQuery = window.matchMedia('(prefers-color-scheme: dark)');

/**
 * Aplica la clase de tema correcta al tag <html>.
 * @param {string} theme - 'system', 'light', o 'dark'
 */
function applyTheme(theme) {
    if (theme === 'light') {
        htmlEl.classList.remove('dark-theme');
        htmlEl.classList.add('light-theme');
    } else if (theme === 'dark') {
        htmlEl.classList.remove('light-theme');
        htmlEl.classList.add('dark-theme');
    } else { // 'system'
        // Si el tema es 'system', vemos qué prefiere el SO
        if (systemThemeQuery.matches) {
            // El SO prefiere dark
            htmlEl.classList.remove('light-theme');
            htmlEl.classList.add('dark-theme');
        } else {
            // El SO prefiere light
            htmlEl.classList.remove('dark-theme');
            htmlEl.classList.add('light-theme');
        }
    }
}

/**
 * Expone la función applyTheme globalmente para que settings-manager la use.
 */
window.applyCurrentTheme = applyTheme;

/**
 * Inicializa el tema al cargar la página y escucha los cambios del sistema.
 */
function initThemeManager() {
    // Aplicar el tema basado en la variable de 'index.php'
    // Nota: La clase inicial (light/dark) ya la pone el PHP.
    // Esto es crucial para aplicar 'system' correctamente al cargar.
    applyTheme(window.userTheme || 'system');

    // Escuchar cambios del sistema (ej. el SO cambia de modo claro a oscuro)
    systemThemeQuery.addEventListener('change', (e) => {
        // Solo aplicar el cambio si el usuario tiene 'system' seleccionado
        if ((window.userTheme || 'system') === 'system') {
            applyTheme('system');
        }
    });
}
// --- ▲▲▲ FIN: LÓGICA DE TEMA ▲▲▲ ---


document.addEventListener('DOMContentLoaded', function () {
    
    // Adjuntar utilidades globales a la ventana
    window.showAlert = showAlert;

    // --- ▼▼▼ ¡INICIO DE MODIFICACIÓN: INICIALIZAR TEMA! ▼▼▼ ---
    initThemeManager();
    // --- ▲▲▲ ¡FIN DE MODIFICACIÓN! ▲▲▲ ---

    // Inicializar los controladores principales
    initMainController();
    initRouter();
    initAuthManager();
    initSettingsManager();
});
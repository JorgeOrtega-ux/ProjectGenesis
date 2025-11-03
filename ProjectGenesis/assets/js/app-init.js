// FILE: assets/js/app-init.js
// (CÓDIGO MODIFICADO)

import { initMainController } from './app/main-controller.js';
import { initRouter } from './app/url-manager.js';
import { initAuthManager } from './modules/auth-manager.js';
import { initSettingsManager } from './modules/settings-manager.js';
import { initAdminManager } from './modules/admin-manager.js'; // <-- AÑADIDO
import { initAdminEditUserManager } from './modules/admin-edit-user-manager.js'; // <-- ¡NUEVA LÍNEA!
import { initAdminServerSettingsManager } from './modules/admin-server-settings-manager.js'; // <-- ¡NUEVA LÍNEA!
// --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
import { initAdminBackupsManager } from './modules/admin-backups-manager.js';
import { initAdminRestoreBackupManager } from './modules/admin-restore-backup-manager.js'; // <-- ¡AÑADIDO!
// --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
import { showAlert } from './services/alert-manager.js'; 
import { initI18nManager } from './services/i18n-manager.js'; 
import { initTooltipManager } from './services/tooltip-manager.js'; 

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


document.addEventListener('DOMContentLoaded', async function () { 
    
    window.showAlert = showAlert;

    await initI18nManager();

    initThemeManager();

    initMainController();
    
    // --- ▼▼▼ INICIO DE LA CORRECCIÓN ▼▼▼ ---
    // Los listeners de los módulos deben registrarse ANTES que el router
    // para que puedan interceptar clics específicos (como 'toggleSectionAdminEditUser')
    // antes de que el router general los capture.
    
    // initRouter(); // <-- MOVIDO MÁS ABAJO
    
    initAuthManager();
    initSettingsManager();
    initAdminManager(); // <-- AÑADIDO
    initAdminEditUserManager(); // <-- ¡NUEVA LÍNEA!
    initAdminServerSettingsManager(); // <-- ¡NUEVA LÍNEA!
    
    // --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
    initAdminBackupsManager();
    initAdminRestoreBackupManager(); // <-- ¡AÑADIDO!
    // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

    // El Router se inicializa al final
    initRouter(); 
    // --- ▲▲▲ FIN DE LA CORRECCIÓN ▲▲▲ ---
    
    initTooltipManager(); 
});
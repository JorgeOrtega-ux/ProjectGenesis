// FILE: assets/js/app-init.js
// (CÓDIGO MODIFICADO PARA USAR EL MÓDULO DE BACKUP COMBINADO)

import { initMainController } from './app/main-controller.js';
import { initRouter } from './app/url-manager.js';
import { initAuthManager } from './modules/auth-manager.js';
import { initSettingsManager } from './modules/settings-manager.js';
import { initAdminManager } from './modules/admin-manager.js';
import { initAdminEditUserManager } from './modules/admin-edit-user-manager.js';
import { initAdminServerSettingsManager } from './modules/admin-server-settings-manager.js';
// --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
// Se eliminan las importaciones de admin-backups-manager y admin-restore-backup-manager
// Se añade la importación del nuevo módulo combinado
import { initAdminBackupModule } from './modules/admin-backup-module.js'; 
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
    
    // Los listeners de los módulos deben registrarse ANTES que el router
    
    initAuthManager();
    initSettingsManager();
    initAdminManager();
    initAdminEditUserManager();
    initAdminServerSettingsManager();
    
    // --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
    // Se eliminan las llamadas a initAdminBackupsManager() y initAdminRestoreBackupManager()
    // Se añade la llamada al nuevo módulo combinado
    initAdminBackupModule();
    // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

    // El Router se inicializa al final
    initRouter(); 
    
    initTooltipManager(); 
});
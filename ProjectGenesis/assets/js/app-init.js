/* ====================================== */
/* =========== APP-INIT.JS ============== */
/* ====================================== */
import { initMainController } from './main-controller.js';
import { initRouter } from './url-manager.js';
import { initAuthManager } from './auth-manager.js';
import { initSettingsManager } from './settings-manager.js';
import { showAlert } from './alert-manager.js'; // <-- AÑADIDO

document.addEventListener('DOMContentLoaded', function () {
    
    // Adjuntar utilidades globales a la ventana
    window.showAlert = showAlert; // <-- AÑADIDO

    // Inicializar los controladores principales
    initMainController();
    initRouter();
    initAuthManager();
    initSettingsManager();
});
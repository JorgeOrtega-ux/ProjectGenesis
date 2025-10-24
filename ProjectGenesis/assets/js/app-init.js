/* ====================================== */
/* =========== APP-INIT.JS ============== */
/* ====================================== */
import { initMainController } from './main-controller.js';
import { initRouter } from './url-manager.js';
import { initAuthManager } from './auth-manager.js';
import { initSettingsManager } from './settings-manager.js'; // <-- AÑADIDO

document.addEventListener('DOMContentLoaded', function () {
    initMainController();
    initRouter();
    initAuthManager();
    initSettingsManager(); // <-- AÑADIDO
});
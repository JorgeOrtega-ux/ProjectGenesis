/* ====================================== */
/* =========== APP-INIT.JS ============== */
/* ====================================== */
import { initMainController } from './main-controller.js';
import { initRouter } from './url-manager.js';
import { initAuthManager } from './auth-manager.js'; // <-- AÑADIR IMPORTACIÓN

document.addEventListener('DOMContentLoaded', function () {
    initMainController();
    initRouter();
    initAuthManager(); // <-- AÑADIR INICIALIZADOR
});
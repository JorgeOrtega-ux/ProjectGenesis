/* ====================================== */
/* =========== APP-INIT.JS ============== */
/* ====================================== */
import { initMainController } from './main-controller.js';
import { initRouter } from './url-manager.js';

document.addEventListener('DOMContentLoaded', function () {
    initMainController();
    initRouter();
});
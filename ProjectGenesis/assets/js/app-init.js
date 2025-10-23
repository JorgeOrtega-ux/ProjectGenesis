import { initMainController } from './main-controller.js';
import { initRouter } from './url-manager.js'; // <-- AÑADIR ESTA LÍNEA

document.addEventListener('DOMContentLoaded', function () {
    initMainController();
    initRouter(); // <-- AÑADIR ESTA LÍNEA
});
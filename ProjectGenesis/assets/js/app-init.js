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
// --- ▲▲▲ FIN DE MODIFICACIÓN ▼▼▼ ---
import { showAlert } from './services/alert-manager.js'; 
import { initI18nManager, getTranslation } from './services/i18n-manager.js'; // <-- ¡MODIFICADO! IMPORTAR GETTRANSLATION
import { initTooltipManager } from './services/tooltip-manager.js'; 

const htmlEl = document.documentElement;
const systemThemeQuery = window.matchMedia('(prefers-color-scheme: dark)');

// --- ▼▼▼ INICIO DE MODIFICACIÓN (FIX CONTEO) ▼▼▼ ---
window.lastKnownUserCount = null; // Almacén global para el conteo
// --- ▲▲▲ FIN DE MODIFICACIÓN (FIX CONTEO) ▼▼▼ ---

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
    // --- ▲▲▲ FIN DE MODIFICACIÓN ▼▼▼ ---

    // El Router se inicializa al final
    initRouter(); 
    
    initTooltipManager(); 

    // --- ▼▼▼ INICIO DE MODIFICACIÓN (CLIENTE WEBSOCKET PARA CONTEO Y EXPULSIÓN) ▼▼▼ ---
    
    // window.isUserLoggedIn (definido en main-layout.php)
    if (window.isUserLoggedIn) {
        let ws;
        
        const wsHost = window.wsHost || '127.0.0.1';
        const wsUrl = `ws://${wsHost}:8765`;
        
        function connectWebSocket() {
            try {
                ws = new WebSocket(wsUrl);
                window.ws = ws; // Hacemos 'ws' global por si se necesita

                ws.onopen = () => {
                    console.log("[WS] Conectado al servidor en:", wsUrl);
                    
                    // --- ¡NUEVO! ENVIAR MENSAJE DE AUTENTICACIÓN ---
                    // window.userId y window.csrfToken vienen de main-layout.php
                    const authMessage = {
                        type: "auth",
                        user_id: window.userId || 0,       // Añadido en main-layout.php
                        session_id: window.csrfToken || "" // Usamos el token CSRF como ID de sesión
                    };
                    ws.send(JSON.stringify(authMessage));
                };

                ws.onmessage = (event) => {
                    try {
                        const data = JSON.parse(event.data);
                        
                        // Lógica existente para 'user_count'
                        if (data.type === 'user_count') {
                            window.lastKnownUserCount = data.count; 
                            const display = document.getElementById('concurrent-users-display');
                            if (display) {
                                display.textContent = data.count;
                                display.setAttribute('data-i18n', ''); 
                            }
                        } 
                        // --- ¡NUEVO! MANEJAR LA EXPULSIÓN FORZADA ---
                        else if (data.type === 'force_logout') {
                            console.log("[WS] Recibida orden de desconexión forzada desde otra sesión.");
                            
                            // (Necesitarás añadir 'js.logout.forced' a tus archivos JSON)
                            window.showAlert(getTranslation('js.logout.forced'), 'info', 5000);
                            
                            // Forzar un refresco. El bootstrapper.php (PHP)
                            // detectará el auth_token inválido y redirigirá al login.
                            setTimeout(() => {
                                window.location.reload();
                            }, 3000); // 3 seg para que el usuario lea el aviso
                        }
                    } catch (e) {
                        console.error("[WS] Error al parsear mensaje:", e);
                    }
                };
                
                ws.onclose = (event) => {
                    console.log("[WS] Desconectado del servidor de conteo.", event.reason);
                    
                    const display = document.getElementById('concurrent-users-display');
                    if (display) {
                        display.textContent = '---';
                        display.setAttribute('data-i18n', ''); 
                    }
                };

                ws.onerror = (error) => {
                    console.error("[WS] Error de WebSocket:", error);
                };

            } catch (e) {
                console.error("[WS] No se pudo crear la conexión WebSocket:", e);
            }
        }

        // Iniciar la conexión
        connectWebSocket();

        // Asegurarse de cerrar la conexión al cerrar la pestaña
        window.addEventListener('beforeunload', () => {
            if (ws && ws.readyState === WebSocket.OPEN) {
                ws.close(1000, "Navegación de usuario"); 
            }
        });
    }
    // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

});
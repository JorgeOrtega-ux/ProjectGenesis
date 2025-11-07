// FILE: assets/js/app-init.js
// (CÓDIGO MODIFICADO PARA USAR EL MÓDULO DE BACKUP COMBINADO)

import { initMainController } from './app/main-controller.js';
import { initRouter } from './app/url-manager.js';
import { initAuthManager } from './modules/auth-manager.js';
import { initSettingsManager } from './modules/settings-manager.js';
import { initAdminManager } from './modules/admin-manager.js';
import { initAdminEditUserManager } from './modules/admin-edit-user-manager.js';
import { initAdminServerSettingsManager } from './modules/admin-server-settings-manager.js';
import { initAdminBackupModule } from './modules/admin-backup-module.js'; 
// --- ▼▼▼ LÍNEA AÑADIDA ▼▼▼ ---
import { initCommunityManager } from './modules/community-manager.js';
import { initPublicationManager } from './modules/publication-manager.js'; // <-- NUEVA
// --- ▲▲▲ FIN LÍNEA AÑADIDA ▲▲▲ ---
import { showAlert } from './services/alert-manager.js'; 
import { initI18nManager, getTranslation } from './services/i18n-manager.js'; // <-- ¡MODIFICADO! IMPORTAR GETTRANSLATION
import { initTooltipManager } from './services/tooltip-manager.js'; 

const htmlEl = document.documentElement;
const systemThemeQuery = window.matchMedia('(prefers-color-scheme: dark)');

window.lastKnownUserCount = null; // Almacén global para el conteo

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
    initAdminBackupModule();
    // --- ▼▼▼ LÍNEA AÑADIDA ▼▼▼ ---
    initCommunityManager();
    initPublicationManager(); // <-- NUEVA
    // --- ▲▲▲ FIN LÍNEA AÑADIDA ▲▲▲ ---

    // El Router se inicializa al final
    initRouter(); 
    
    initTooltipManager(); 

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
                        
                        if (data.type === 'user_count') {
                            window.lastKnownUserCount = data.count; 
                            const display = document.getElementById('concurrent-users-display');
                            if (display) {
                                display.textContent = data.count;
                                display.setAttribute('data-i18n', ''); 
                            }
                        } 
                        else if (data.type === 'force_logout') {
                            console.log("[WS] Recibida orden de desconexión forzada (logout o reactivación).");
                            
                            window.showAlert(getTranslation('js.logout.forced') || 'Tu sesión ha caducado, por favor inicia sesión de nuevo.', 'info', 5000);
                            
                            setTimeout(() => {
                                window.location.reload();
                            }, 3000); // 3 seg para que el usuario lea el aviso
                        }
                        else if (data.type === 'account_status_update') {
                            const newStatus = data.status;
                            
                            if (newStatus === 'suspended' || newStatus === 'deleted') {
                                const msgKey = (newStatus === 'suspended') ? 'js.auth.errorAccountSuspended' : 'js.auth.errorAccountDeleted';
                                console.log(`[WS] Recibida orden de estado: ${newStatus}`);
                                window.showAlert(getTranslation(msgKey), 'error', 5000);

                                setTimeout(() => {
                                    window.location.href = `${window.projectBasePath}/account-status/${newStatus}`;
                                }, 3000);
                            }
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

        connectWebSocket();

        window.addEventListener('beforeunload', () => {
            if (ws && ws.readyState === WebSocket.OPEN) {
                ws.close(1000, "Navegación de usuario"); 
            }
        });
    }

});
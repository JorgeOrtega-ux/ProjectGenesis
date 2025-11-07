// RUTA: assets/js/app-init.js
// (CÓDIGO MODIFICADO)

import { initMainController } from './app/main-controller.js';
import { initRouter } from './app/url-manager.js';
import { initAuthManager } from './modules/auth-manager.js';
import { initSettingsManager } from './modules/settings-manager.js';
import { initAdminManager } from './modules/admin-manager.js';
import { initAdminEditUserManager } from './modules/admin-edit-user-manager.js';
import { initAdminServerSettingsManager } from './modules/admin-server-settings-manager.js';
import { initAdminBackupModule } from './modules/admin-backup-module.js'; 
import { initGroupsManager } from './modules/groups-manager.js'; 
// --- ▼▼▼ INICIO DE LÍNEA MODIFICADA ▼▼▼ ---
import { initChatManager, renderIncomingMessage } from './modules/chat-manager.js';
// --- ▲▲▲ FIN DE LÍNEA MODIFICADA ▲▲▲ ---
import { showAlert } from './services/alert-manager.js'; 
import { initI18nManager, getTranslation } from './services/i18n-manager.js'; 
import { initTooltipManager } from './services/tooltip-manager.js'; 

const htmlEl = document.documentElement;
const systemThemeQuery = window.matchMedia('(prefers-color-scheme: dark)');

window.lastKnownUserCount = null; 
window.onlineUserIds = new Set();

window.setMemberStatus = (userId, status) => {
    const avatars = document.querySelectorAll(`.member-avatar[data-user-id="${userId}"]`);
    if (avatars.length === 0) {
        return; 
    }
    avatars.forEach(avatar => {
        if (status === 'online') {
            avatar.classList.add('online');
        } else {
            avatar.classList.remove('online');
        }
    });
};

window.applyOnlineStatusToAllMembers = () => {
    document.querySelectorAll('.member-avatar.online').forEach(avatar => {
        avatar.classList.remove('online');
    });
    if (window.onlineUserIds && window.onlineUserIds.size > 0) {
        window.onlineUserIds.forEach(userId => {
            window.setMemberStatus(userId, 'online');
        });
    }
};

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
    // --- ▼▼▼ INICIO DE LÍNEA AÑADIDA ▼▼▼ ---
    window.renderIncomingMessage = renderIncomingMessage; // Exponer al WebSocket
    // --- ▲▲▲ FIN DE LÍNEA AÑADIDA ▲▲▲ ---

    await initI18nManager();

    initThemeManager();
    initMainController();
    initAuthManager();
    initSettingsManager();
    initAdminManager();
    initAdminEditUserManager();
    initAdminServerSettingsManager();
    initAdminBackupModule();
    initGroupsManager(); 
    initChatManager(); // Inicializa el nuevo módulo de chat

    initRouter(); 
    initTooltipManager(); 

    
    if (window.isUserLoggedIn) {
        let ws;
        
        const wsHost = window.wsHost || '127.0.0.1';
        const wsUrl = `ws://${wsHost}:8765`;
        
        // --- ▼▼▼ INICIO DE FUNCIÓN AÑADIDA ▼▼▼ ---
        /**
         * Envía un objeto JSON al WebSocket si está conectado.
         * @param {object} msgObject El objeto a enviar.
         */
        window.wsSend = (msgObject) => {
            if (ws && ws.readyState === WebSocket.OPEN) {
                try {
                    ws.send(JSON.stringify(msgObject));
                } catch (e) {
                    console.error("[WS] Error al enviar mensaje:", e);
                }
            } else {
                console.warn("[WS] Intento de envío mientras WS no está conectado.", msgObject);
            }
        };
        // --- ▲▲▲ FIN DE FUNCIÓN AÑADIDA ▲▲▲ ---

        function connectWebSocket() {
            try {
                ws = new WebSocket(wsUrl);
                window.ws = ws; 

                ws.onopen = () => {
                    console.log("[WS] Conectado al servidor en:", wsUrl);
                    
                    const authMessage = {
                        type: "auth",
                        user_id: window.userId || 0,       
                        session_id: window.csrfToken || "" 
                    };
                    window.wsSend(authMessage); // Usar la nueva función
                    
                    // --- ▼▼▼ INICIO DE LÍNEA AÑADIDA ▼▼▼ ---
                    // Al reconectar, informar al servidor de nuestro grupo actual (si lo hay)
                    const currentGroupUuid = document.body.dataset.currentGroupUuid || null;
                    if(currentGroupUuid) {
                        window.wsSend({ type: 'join_group', group_uuid: currentGroupUuid });
                    }
                    // --- ▲▲▲ FIN DE LÍNEA AÑADIDA ▲▲▲ ---
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
                        
                        else if (data.type === 'presence_list') {
                            if (data.user_ids && Array.isArray(data.user_ids)) {
                                window.onlineUserIds.clear(); 
                                data.user_ids.forEach(id => window.onlineUserIds.add(id));
                                console.log(`[WS-PRESENCE] Recibida lista de ${window.onlineUserIds.size} usuarios online.`);
                                if (window.applyOnlineStatusToAllMembers) {
                                    window.applyOnlineStatusToAllMembers();
                                }
                            }
                        }

                        else if (data.type === 'user_status') {
                            if (data.user_id && data.status) {
                                console.log(`[WS-STATUS] Usuario ${data.user_id} está ${data.status}`);
                                if (data.status === 'online') {
                                    window.onlineUserIds.add(data.user_id);
                                } else {
                                    window.onlineUserIds.delete(data.user_id);
                                }
                                if (window.setMemberStatus) {
                                    window.setMemberStatus(data.user_id, data.status);
                                }
                            }
                        }

                        // --- ▼▼▼ INICIO DE NUEVO BLOQUE ▼▼▼ ---
                        else if (data.type === 'new_chat_message') {
                            if (data.message && window.renderIncomingMessage) {
                                // Llamar a la función expuesta para renderizar el chat
                                window.renderIncomingMessage(data.message);
                            }
                        }
                        // --- ▲▲▲ FIN DE NUEVO BLOQUE ▲▲▲ ---

                        else if (data.type === 'force_logout') {
                            console.log("[WS] Recibida orden de desconexión forzada (logout o reactivación).");
                            window.showAlert(getTranslation('js.logout.forced') || 'Tu sesión ha caducado...', 'info', 5000);
                            setTimeout(() => {
                                window.location.reload();
                            }, 3000); 
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
                    console.log("[WS] Desconectado del servidor.", event.reason);
                    const display = document.getElementById('concurrent-users-display');
                    if (display) {
                        display.textContent = '---';
                        display.setAttribute('data-i18n', ''); 
                    }
                    window.onlineUserIds.clear();
                    if (window.applyOnlineStatusToAllMembers) {
                        window.applyOnlineStatusToAllMembers();
                    }
                    
                    // --- ▼▼▼ INICIO DE LÍNEA AÑADIDA (Reconexión) ▼▼▼ ---
                    // Intentar reconectar después de 5 segundos
                    setTimeout(connectWebSocket, 5000);
                    // --- ▲▲▲ FIN DE LÍNEA AÑADIDA (Reconexión) ▲▲▲ ---
                };

                ws.onerror = (error) => {
                    console.error("[WS] Error de WebSocket:", error);
                    // onclose se llamará automáticamente después de un error,
                    // lo que activará la lógica de reconexión.
                };

            } catch (e) {
                console.error("[WS] No se pudo crear la conexión WebSocket:", e);
                // Reintentar si la creación misma falla
                setTimeout(connectWebSocket, 5000);
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
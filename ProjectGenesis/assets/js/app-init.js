// FILE: assets/js/app-init.js
// (MODIFICADO)

import { initMainController } from './app/main-controller.js';
import { initRouter, loadPage } from './app/url-manager.js';
import { initAuthManager } from './modules/auth-manager.js';
import { initSettingsManager } from './modules/settings-manager.js';
import { initAdminManager } from './modules/admin-manager.js';
import { initAdminEditUserManager } from './modules/admin-edit-user-manager.js';
import { initAdminServerSettingsManager } from './modules/admin-server-settings-manager.js';
import { initAdminBackupModule } from './modules/admin-backup-module.js'; 
import { initCommunityManager } from './modules/community-manager.js';
// --- ▼▼▼ IMPORTACIÓN MODIFICADA ▼▼▼ ---
import { setupPublicationListeners } from './modules/publication-manager.js';
// --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

// --- ▼▼▼ INICIO DE IMPORTACIÓN MODIFICADA (CORREGIDA) ▼▼▼ ---
import { 
    initChatManager, 
    handleChatMessageReceived, 
    handleTypingEvent, 
    handleMessageDeleted // <--- ¡FUNCIÓN AÑADIDA!
} from './modules/chat-manager.js';
// --- ▲▲▲ FIN DE IMPORTACIÓN MODIFICADA ▲▲▲ ---

import { initFriendManager, initFriendList } from './modules/friend-manager.js';
import { showAlert } from './services/alert-manager.js'; 
import { initI18nManager, getTranslation } from './services/i18n-manager.js';
import { initTooltipManager } from './services/tooltip-manager.js';
import { initSearchManager } from './modules/search-manager.js';

// --- ▼▼▼ IMPORTACIÓN MODIFICADA ▼▼▼ ---
import { 
    initNotificationManager, 
    fetchInitialCount, 
    handleNotificationPing 
} from './modules/notification-manager.js';
// --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

// --- ▼▼▼ NUEVA LÍNEA AÑADIDA ▼▼▼ ---
import { initAdminCommunityManager } from './modules/admin-community-manager.js';
// --- ▲▲▲ FIN NUEVA LÍNEA ▲▲▲ ---


const htmlEl = document.documentElement;
const systemThemeQuery = window.matchMedia('(prefers-color-scheme: dark)');

window.lastKnownUserCount = null;

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

// =============================================
// ============ LÓGICA DE NOTIFICACIONES (MOVIDA) =
// =============================================
// ... El código ha sido MOVIDO a assets/js/modules/notification-manager.js
// =============================================


document.addEventListener('DOMContentLoaded', async function () { 
    
    window.showAlert = showAlert;

    await initI18nManager();

    initThemeManager();

    initMainController();
    
    initAuthManager();
    initSettingsManager();
    initAdminManager();
    initAdminEditUserManager();
    initAdminServerSettingsManager();
    initAdminBackupModule();
    initCommunityManager();
    
    // --- ▼▼▼ LLAMADA MODIFICADA ▼▼▼ ---
    setupPublicationListeners(); // Llama a la función que existe
    // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
    
    initFriendManager(); 
    initNotificationManager(); // <-- Se mantiene la inicialización
    initSearchManager();
    
    // --- ▼▼▼ NUEVA LÍNEA AÑADIDA ▼▼▼ ---
    initAdminCommunityManager();
    initChatManager();
    // --- ▲▲▲ FIN NUEVA LÍNEA ▲▲▲ ---

    initRouter(); 
    
    initTooltipManager(); 

    if (window.isUserLoggedIn) {
        // initFriendList(); // (movido a url-manager.js)

        // --- ▼▼▼ LLAMADA MODIFICADA ▼▼▼ ---
        // Carga el conteo inicial de notificaciones
        fetchInitialCount();
        // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---


        let ws;
        
        const wsHost = window.wsHost || '127.0.0.1';
        const wsUrl = `ws://${wsHost}:8765`;
        
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
                            console.log("[WS] Recibida orden de desconexión forzada.");
                            window.showAlert(getTranslation('js.logout.forced') || 'Tu sesión ha caducado, por favor inicia sesión de nuevo.', 'info', 5000);
                            setTimeout(() => { window.location.reload(); }, 3000);
                        }
                        else if (data.type === 'account_status_update') {
                            const newStatus = data.status;
                            if (newStatus === 'suspended' || newStatus === 'deleted') {
                                const msgKey = (newStatus === 'suspended') ? 'js.auth.errorAccountSuspended' : 'js.auth.errorAccountDeleted';
                                window.showAlert(getTranslation(msgKey), 'error', 5000);
                                setTimeout(() => {
                                    window.location.href = `${window.projectBasePath}/account-status/${newStatus}`;
                                }, 3000);
                            }
                        }
                        
                         else if (data.type === 'new_poll_vote' && data.payload) {
                            console.log("[WS] Notificación de nuevo voto");
                            showAlert(`📊 ${getTranslation('js.notifications.newPollVote').replace('{username}', data.payload.username)}`, 'info');
                        }
                        
                        // --- ▼▼▼ INICIO DE MODIFICACIÓN (LÓGICA DE PING) ▼▼▼ ---
                        else if (data.type === 'new_notification_ping') {
                            console.log("[WS] Ping de nueva notificación recibido");
                            // Delegar al manager
                            handleNotificationPing();
                        }

                        else if (data.type === 'new_chat_message') {
                            console.log("[WS] Mensaje de chat recibido");
                            handleChatMessageReceived(data.payload);
                        }
                        // --- ▼▼▼ ¡INICIO DE BLOQUE AÑADIDO! (CORRECCIÓN) ▼▼▼ ---
                        else if (data.type === 'message_deleted') {
                            console.log("[WS] Notificación de 'message_deleted' recibida");
                            handleMessageDeleted(data.payload);
                        }
                        // --- ▲▲▲ ¡FIN DE BLOQUE AÑADIDO! (CORRECCIÓN) ▲▲▲ ---
                        // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
                        
                        // --- ▼▼▼ INICIO DE NUEVA LÓGICA DE TYPING ▼▼▼ ---
                        else if (data.type === 'typing_start') {
                            console.log("[WS] 'typing_start' recibido de", data.sender_id);
                            // Delegar al chat manager
                            if (typeof handleTypingEvent === 'function') {
                                handleTypingEvent(data.sender_id, true);
                            }
                        }
                        else if (data.type === 'typing_stop') {
                            console.log("[WS] 'typing_stop' recibido de", data.sender_id);
                            // Delegar al chat manager
                            if (typeof handleTypingEvent === 'function') {
                                handleTypingEvent(data.sender_id, false);
                            }
                        }
                        // --- ▲▲▲ FIN DE NUEVA LÓGICA DE TYPING ▲▲▲ ---

                        else if (data.type === 'presence_update') {
                            console.log(`[WS] Actualización de estado: User ${data.user_id} está ${data.status}`);
                            document.dispatchEvent(new CustomEvent('user-presence-changed', {
                                detail: {
                                    userId: data.user_id,
                                    status: data.status
                                }
                            }));
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
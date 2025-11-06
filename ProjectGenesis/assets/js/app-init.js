// RUTA: assets/js/app-init.js
// (CÓDIGO COMPLETO CORREGIDO)

import { initMainController } from './app/main-controller.js';
import { initRouter } from './app/url-manager.js';
import { initAuthManager } from './modules/auth-manager.js';
import { initSettingsManager } from './modules/settings-manager.js';
import { initAdminManager } from './modules/admin-manager.js';
import { initAdminEditUserManager } from './modules/admin-edit-user-manager.js';
import { initAdminServerSettingsManager } from './modules/admin-server-settings-manager.js';
import { initAdminBackupModule } from './modules/admin-backup-module.js'; 
import { initGroupsManager } from './modules/groups-manager.js'; 
import { showAlert } from './services/alert-manager.js'; 
import { initI18nManager, getTranslation } from './services/i18n-manager.js'; 
import { initTooltipManager } from './services/tooltip-manager.js'; 

const htmlEl = document.documentElement;
const systemThemeQuery = window.matchMedia('(prefers-color-scheme: dark)');

window.lastKnownUserCount = null; 

// --- ▼▼▼ INICIO DE NUEVAS FUNCIONES DE PRESENCIA ▼▼▼ ---

/**
 * Almacén global para saber quién está conectado.
 * Se actualiza por WebSocket.
 */
window.onlineUserIds = new Set();

/**
 * Actualiza la UI para un usuario específico, añadiendo o quitando la clase 'online'.
 * @param {string|number} userId - El ID del usuario a actualizar.
 * @param {string} status - "online" o "offline".
 */
window.setMemberStatus = (userId, status) => {
    // Buscar *todos* los avatares de miembros que coincidan con este ID
    // (Puede estar en el panel de miembros, en listas de chat, etc.)
    const avatars = document.querySelectorAll(`.member-avatar[data-user-id="${userId}"]`);
    
    if (avatars.length === 0) {
        return; // El usuario no está visible en la UI
    }

    avatars.forEach(avatar => {
        if (status === 'online') {
            avatar.classList.add('online');
        } else {
            avatar.classList.remove('online');
        }
    });
};

/**
 * Itera sobre el Set global y aplica el estado 'online' a todos
 * los miembros visibles en la UI.
 * Se llama al cargar una página (ej. home) o al recibir la lista de presencia.
 */
window.applyOnlineStatusToAllMembers = () => {
    // 1. Limpiar todos los estados "online" existentes
    document.querySelectorAll('.member-avatar.online').forEach(avatar => {
        avatar.classList.remove('online');
    });

    // 2. Aplicar el estado "online" a los usuarios del Set
    if (window.onlineUserIds && window.onlineUserIds.size > 0) {
        window.onlineUserIds.forEach(userId => {
            window.setMemberStatus(userId, 'online');
        });
    }
};

// --- ▲▲▲ FIN DE NUEVAS FUNCIONES DE PRESENCIA ▲▲▲ ---


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
    
    initAuthManager();
    initSettingsManager();
    initAdminManager();
    initAdminEditUserManager();
    initAdminServerSettingsManager();
    initAdminBackupModule();
    initGroupsManager(); 

    initRouter(); 
    
    initTooltipManager(); 

    
    if (window.isUserLoggedIn) {
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

                // --- ▼▼▼ INICIO DE MANEJADOR DE MENSAJES MODIFICADO ▼▼▼ ---
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
                        
                        // --- ¡NUEVO! Caso 1: Lista inicial de presencia ---
                        else if (data.type === 'presence_list') {
                            if (data.user_ids && Array.isArray(data.user_ids)) {
                                window.onlineUserIds.clear(); // Limpiar el Set
                                data.user_ids.forEach(id => window.onlineUserIds.add(id));
                                
                                console.log(`[WS-PRESENCE] Recibida lista de ${window.onlineUserIds.size} usuarios online.`);
                                
                                // Aplicar a la UI (si la lista de miembros está visible)
                                if (window.applyOnlineStatusToAllMembers) {
                                    window.applyOnlineStatusToAllMembers();
                                }
                            }
                        }

                        // --- ¡NUEVO! Caso 2: Un usuario cambia de estado ---
                        else if (data.type === 'user_status') {
                            if (data.user_id && data.status) {
                                console.log(`[WS-STATUS] Usuario ${data.user_id} está ${data.status}`);
                                if (data.status === 'online') {
                                    window.onlineUserIds.add(data.user_id);
                                } else {
                                    window.onlineUserIds.delete(data.user_id);
                                }
                                
                                // Aplicar a la UI (si el miembro está visible)
                                if (window.setMemberStatus) {
                                    window.setMemberStatus(data.user_id, data.status);
                                }
                            }
                        }

                        // --- (Lógica existente) ---
                        else if (data.type === 'force_logout') {
                            console.log("[WS] Recibida orden de desconexión forzada (logout o reactivación).");
                            
                            window.showAlert(getTranslation('js.logout.forced') || 'Tu sesión ha caducado, por favor inicia sesión de nuevo.', 'info', 5000);
                            
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
                        // --- ▲▲▲ FIN DE MANEJADOR DE MENSAJES MODIFICADO ▲▲▲ ---
                        
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
                    
                    // Limpiar todos los indicadores de "online"
                    window.onlineUserIds.clear();
                    if (window.applyOnlineStatusToAllMembers) {
                        window.applyOnlineStatusToAllMembers();
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
        // (El resto de la lógica de 'beforeunload' se omite por brevedad,
        // pero debe permanecer en tu archivo si ya existía)
        
        window.addEventListener('beforeunload', () => {
            if (ws && ws.readyState === WebSocket.OPEN) {
                ws.close(1000, "Navegación de usuario"); 
            }
        });
    }
});
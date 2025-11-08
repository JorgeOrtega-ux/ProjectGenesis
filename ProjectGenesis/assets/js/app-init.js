// FILE: assets/js/app-init.js

import { initMainController } from './app/main-controller.js';
import { initRouter } from './app/url-manager.js';
import { initAuthManager } from './modules/auth-manager.js';
import { initSettingsManager } from './modules/settings-manager.js';
import { initAdminManager } from './modules/admin-manager.js';
import { initAdminEditUserManager } from './modules/admin-edit-user-manager.js';
import { initAdminServerSettingsManager } from './modules/admin-server-settings-manager.js';
import { initAdminBackupModule } from './modules/admin-backup-module.js'; 
import { initCommunityManager } from './modules/community-manager.js';
import { initPublicationManager } from './modules/publication-manager.js';
// --- ▼▼▼ LÍNEA MODIFICADA/AÑADIDA ▼▼▼ ---
import { initFriendManager, initFriendList } from './modules/friend-manager.js';
// --- ▲▲▲ FIN LÍNEA MODIFICADA/AÑADIDA ▲▲▲ ---
import { showAlert } from './services/alert-manager.js'; 
import { initI18nManager, getTranslation } from './services/i18n-manager.js';
import { initTooltipManager } from './services/tooltip-manager.js'; 
// --- ▼▼▼ ¡NUEVA IMPORTACIÓN! ▼▼▼ ---
import { callFriendApi } from './services/api-service.js';
// --- ▲▲▲ ¡FIN DE NUEVA IMPORTACIÓN! ▲▲▲ ---

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
// ============ LÓGICA DE NOTIFICACIONES =========
// =============================================
let hasLoadedNotifications = false;
let currentNotificationCount = 0;

/**
 * Actualiza el contador visual (badge) de notificaciones.
 * @param {number} count - El número total de notificaciones no leídas.
 */
function setNotificationCount(count) {
    currentNotificationCount = count;
    const badge = document.getElementById('notification-badge-count');
    if (!badge) return;

    badge.textContent = count;
    if (count > 0) {
        badge.classList.remove('disabled');
    } else {
        badge.classList.add('disabled');
    }
}

/**
 * Añade una notificación (HTML) al panel de notificaciones.
 * @param {object} request - Objeto con los datos de la solicitud (user_id, username, profile_image_url).
 */
function addNotificationToUI(request) {
    const listContainer = document.getElementById('notification-list-items');
    const placeholder = document.getElementById('notification-placeholder');
    if (!listContainer) return;

    // Ocultar el placeholder si está visible
    if (placeholder) {
        placeholder.style.display = 'none';
    }
    
    // Evitar duplicados si la notificación ya está en la lista
    if (document.querySelector(`.notification-item[data-user-id="${request.user_id}"]`)) {
        return;
    }

    const defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";
    const avatar = request.profile_image_url || defaultAvatar;

    const notificationHtml = `
        <div class="notification-item" data-user-id="${request.user_id}">
            <div class="notification-avatar">
                <img src="${avatar}" alt="${request.username}">
            </div>
            <div class="notification-content">
                <div class="notification-text">
                    <strong>${request.username}</strong>
                    <span data-i18n="notifications.friendRequestText">quiere ser tu amigo.</span>
                </div>
                <div class="notification-actions">
                    <button type="button" class="notification-action-button notification-action-button--secondary" 
                            data-action="friend-decline-request" data-user-id="${request.user_id}">
                        <span data-i18n="friends.declineRequest">Rechazar</span>
                    </button>
                    <button type="button" class="notification-action-button notification-action-button--primary" 
                            data-action="friend-accept-request" data-user-id="${request.user_id}">
                        <span data-i18n="friends.acceptRequest">Aceptar</span>
                    </button>
                </div>
            </div>
        </div>
    `;
    
    listContainer.insertAdjacentHTML('afterbegin', notificationHtml);
}

/**
 * Carga las solicitudes de amistad pendientes desde la API la primera vez que se abre el panel.
 */
async function loadPendingNotifications() {
    if (hasLoadedNotifications) {
        return; // Solo cargar una vez
    }
    hasLoadedNotifications = true; // Marcar como cargado
    
    const listContainer = document.getElementById('notification-list-items');
    const placeholder = document.getElementById('notification-placeholder');
    if (!listContainer || !placeholder) return;

    // Mostrar un estado de carga temporal
    placeholder.querySelector('.material-symbols-rounded').innerHTML = '<span class="logout-spinner" style="width: 32px; height: 32px; border-width: 3px;"></span>';
    placeholder.querySelector('span[data-i18n]').setAttribute('data-i18n', 'notifications.loading');
    placeholder.querySelector('span[data-i18n]').textContent = getTranslation('notifications.loading');
    
    const formData = new FormData();
    formData.append('action', 'get-pending-requests'); // Esta acción debe ser creada en friend_handler.php

    try {
        const result = await callFriendApi(formData);
        
        if (result.success && result.requests) {
            setNotificationCount(result.requests.length);
            
            if (result.requests.length === 0) {
                // No hay notificaciones, volver al placeholder original
                placeholder.querySelector('.material-symbols-rounded').innerHTML = 'notifications_off';
                placeholder.querySelector('span[data-i18n]').setAttribute('data-i18n', 'notifications.empty');
                placeholder.querySelector('span[data-i18n]').textContent = getTranslation('notifications.empty');
            } else {
                // Hay notificaciones, limpiar el placeholder y añadirlas
                placeholder.style.display = 'none';
                listContainer.innerHTML = ''; // Limpiar por si acaso
                
                result.requests.forEach(request => {
                    addNotificationToUI(request);
                });
            }
        } else {
             placeholder.querySelector('.material-symbols-rounded').innerHTML = 'error';
             placeholder.querySelector('span[data-i18n]').setAttribute('data-i18n', 'js.api.errorServer');
             placeholder.querySelector('span[data-i18n]').textContent = getTranslation('js.api.errorServer');
        }
    } catch (e) {
        console.error("Error al cargar notificaciones:", e);
         placeholder.querySelector('.material-symbols-rounded').innerHTML = 'error';
         placeholder.querySelector('span[data-i18n]').setAttribute('data-i18n', 'js.api.errorConnection');
         placeholder.querySelector('span[data-i18n]').textContent = getTranslation('js.api.errorConnection');
    }
}

/**
 * Inicializa los listeners para el panel de notificaciones.
 */
function initNotificationManager() {
    const notificationButton = document.querySelector('[data-action="toggleModuleNotifications"]');
    if (notificationButton) {
        notificationButton.addEventListener('click', () => {
            // Cargar notificaciones la primera vez que se hace clic
            if (!hasLoadedNotifications) {
                loadPendingNotifications();
            }
            // Resetear el contador (lógica de "marcar como leído")
            setNotificationCount(0);
        }, { once: false }); // 'once: false' para que el contador se resetee CADA vez que se abre
    }
    
    // Listener para remover el item de la lista cuando se acepta/rechaza
    // (Esto se añade al body para que funcione con elementos creados dinámicamente)
    document.body.addEventListener('click', (e) => {
        const targetButton = e.target.closest('[data-action="friend-accept-request"], [data-action="friend-decline-request"]');
        if (targetButton && targetButton.closest('.notification-item')) {
            // La lógica de API es manejada por friend-manager.js
            // Aquí solo nos encargamos de la UI del panel
            const item = targetButton.closest('.notification-item');
            if (item) {
                item.style.opacity = '0.5'; // Dar feedback visual
                // El 'friend-manager.js' debe remover el item al tener éxito
                // Para estar seguros, lo removemos aquí después de un delay
                setTimeout(() => item.remove(), 1000);
            }
        }
    });
}
// =============================================
// ============ FIN DE NOTIFICACIONES ============
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
    initPublicationManager();
    initFriendManager(); 
    // --- ▼▼▼ ¡NUEVA LLAMADA! ▼▼▼ ---
    initNotificationManager();
    // --- ▲▲▲ ¡FIN DE NUEVA LLAMADA! ▲▲▲ ---

    initRouter(); 
    
    initTooltipManager(); 

    if (window.isUserLoggedIn) {
        initFriendList();

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
                        // --- ▼▼▼ ¡NUEVO BLOQUE! ▼▼▼ ---
                        else if (data.type === 'friend_request_received' && data.payload) {
                            console.log("[WS] Notificación de amistad recibida");
                            addNotificationToUI(data.payload);
                            // Asumimos que el servidor envía el nuevo conteo total
                            if (data.new_count !== undefined) {
                                setNotificationCount(data.new_count);
                            } else {
                                // Opcionalmente, solo incrementamos
                                setNotificationCount(currentNotificationCount + 1);
                            }
                            // Opcional: mostrar una alerta toast
                            showAlert(`🔔 ${data.payload.username} ${getTranslation('notifications.friendRequestAlert')}`, 'info');
                        }
                        // --- ▲▲▲ ¡FIN DE NUEVO BLOQUE! ▲▲▲ ---
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
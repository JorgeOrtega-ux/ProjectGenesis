import { callNotificationApi } from '../services/api-service.js';
import { getTranslation, applyTranslations } from '../services/i18n-manager.js';
import { deactivateAllModules } from '../app/main-controller.js'; 

let hasLoadedNotifications = false;
let isLoading = false;
let currentNotificationCount = 0;

function formatTimeAgo(dateString) {
    if (!dateString) return '';
    try {
        const date = new Date(dateString.includes('Z') ? dateString : dateString + 'Z'); 
        const now = new Date();
        const seconds = Math.round((now - date) / 1000);
        
        const minutes = Math.round(seconds / 60);
        const hours = Math.round(minutes / 60);
        const days = Math.round(hours / 24);

        if (seconds < 60) {
            return 'Ahora';
        } else if (minutes < 60) {
            return `hace ${minutes}m`;
        } else if (hours < 24) {
            return `hace ${hours}h`;
        } else if (days === 1) {
            return 'Ayer';
        } else {
            return date.toLocaleDateString(window.userLanguage.split('-')[0] || 'es', {
                month: 'short',
                day: 'numeric'
            });
        }
    } catch (e) {
        console.error("Error al formatear fecha:", e);
        return dateString;
    }
}

function getRelativeDateGroup(date) {
    const today = new Date();
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);

    const lang = window.userLanguage.split('-')[0] || 'es';

    if (date.toDateString() === today.toDateString()) {
        return "Hoy";
    }
    if (date.toDateString() === yesterday.toDateString()) {
        return "Ayer";
    }
    return date.toLocaleDateString(lang, {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

// --- ▼▼▼ INICIO DE MODIFICACIÓN (99+) ▼▼▼ ---
export function setNotificationCount(count) {
    console.log(`[Notify] setNotificationCount: Actualizando contador a ${count}`);
    currentNotificationCount = count;
    const badge = document.getElementById('notification-badge-count');
    if (!badge) return;

    if (count > 99) {
        badge.textContent = '99+';
    } else {
        badge.textContent = count;
    }

    if (count > 0) {
        badge.classList.remove('disabled');
    } else {
        badge.classList.add('disabled');
    }
}
// --- ▲▲▲ FIN DE MODIFICACIÓN (99+) ▲▲▲ ---

function addNotificationToUI(notification) {
    const avatar = notification.actor_avatar || "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";
    let notificationHtml = '';
    let textKey = '';
    let href = '#'; 

    const timeAgo = formatTimeAgo(notification.created_at);
    const isUnread = notification.is_read == 0;
    const readClass = isUnread ? 'is-unread' : 'is-read';
    const unreadDot = isUnread ? '<span class="notification-unread-dot"></span>' : '';

    switch (notification.type) {
        case 'friend_request':
            textKey = 'notifications.friendRequestText';
            href = `${window.projectBasePath}/profile/${notification.actor_username}`;
            notificationHtml = `
                <div class="notification-item ${readClass}" data-id="${notification.id}" data-user-id="${notification.actor_user_id}">
                    <a href="${href}" data-nav-js="true" class="notification-avatar">
                        <img src="${avatar}" alt="${notification.actor_username}">
                    </a>
                    <div class="notification-content">
                        <div class="notification-text">
                            <a href="${href}" data-nav-js="true" style="text-decoration: none; color: inherit;">
                                <strong>${notification.actor_username}</strong>
                            </a>
                            <span data-i18n="${textKey}">quiere ser tu amigo.</span>
                        </div>
                        <div class="notification-timestamp">${timeAgo} ${unreadDot}</div>
                        <div class="notification-actions">
                            <button type="button" class="notification-action-button notification-action-button--secondary" 
                                    data-action="friend-decline-request" data-user-id="${notification.actor_user_id}">
                                <span data-i18n="friends.declineRequest">Rechazar</span>
                            </button>
                            <button type="button" class="notification-action-button notification-action-button--primary" 
                                    data-action="friend-accept-request" data-user-id="${notification.actor_user_id}">
                                <span data-i18n="friends.acceptRequest">Aceptar</span>
                            </button>
                        </div>
                    </div>
                </div>`;
            break;
        
        case 'friend_accept':
            textKey = 'js.notifications.friendAccepted';
            href = `${window.projectBasePath}/profile/${notification.actor_username}`;
            notificationHtml = `
                <a href="${href}" data-nav-js="true" class="notification-item ${readClass}" data-id="${notification.id}" data-user-id="${notification.actor_user_id}">
                    <div class="notification-avatar">
                        <img src="${avatar}" alt="${notification.actor_username}">
                    </div>
                    <div class="notification-content">
                        <div class="notification-text">
                            <span data-i18n="${textKey}">${getTranslation(textKey).replace('{username}', notification.actor_username)}</span>
                        </div>
                        <div class="notification-timestamp">${timeAgo} ${unreadDot}</div>
                        </div>
                </a>`;
            break;

        case 'like':
            textKey = 'js.notifications.newLike';
            href = `${window.projectBasePath}/post/${notification.reference_id}`;
            notificationHtml = `
                <a href="${href}" data-nav-js="true" class="notification-item ${readClass}" data-id="${notification.id}" data-user-id="${notification.actor_user_id}">
                    <div class="notification-avatar">
                        <img src="${avatar}" alt="${notification.actor_username}">
                    </div>
                    <div class="notification-content">
                        <div class="notification-text">
                            <span data-i18n="${textKey}">${getTranslation(textKey).replace('{username}', notification.actor_username)}</span>
                        </div>
                        <div class="notification-timestamp">${timeAgo} ${unreadDot}</div>
                        </div>
                </a>`;
            break;
            
        case 'comment':
            textKey = 'js.notifications.newComment';
            href = `${window.projectBasePath}/post/${notification.reference_id}`;
             notificationHtml = `
                <a href="${href}" data-nav-js="true" class="notification-item ${readClass}" data-id="${notification.id}" data-user-id="${notification.actor_user_id}">
                    <div class="notification-avatar">
                        <img src="${avatar}" alt="${notification.actor_username}">
                    </div>
                    <div class="notification-content">
                        <div class="notification-text">
                            <span data-i18n="${textKey}">${getTranslation(textKey).replace('{username}', notification.actor_username)}</span>
                        </div>
                        <div class="notification-timestamp">${timeAgo} ${unreadDot}</div>
                        </div>
                </a>`;
            break;

        case 'reply':
            textKey = 'js.notifications.newReply';
            href = `${window.projectBasePath}/post/${notification.reference_id}`;
             notificationHtml = `
                <a href="${href}" data-nav-js="true" class="notification-item ${readClass}" data-id="${notification.id}" data-user-id="${notification.actor_user_id}">
                    <div class="notification-avatar">
                        <img src="${avatar}" alt="${notification.actor_username}">
                    </div>
                    <div class="notification-content">
                        <div class="notification-text">
                            <span data-i18n="${textKey}">${getTranslation(textKey).replace('{username}', notification.actor_username)}</span>
                        </div>
                        <div class="notification-timestamp">${timeAgo} ${unreadDot}</div>
                        </div>
                </a>`;
            break;
    }
    
    return notificationHtml;
}

export async function loadAllNotifications() {
    
    if (isLoading) {
        console.log("%c[Notify] loadAllNotifications: Carga ya en progreso. Omitiendo fetch duplicado.", "color: #ff8c00;");
        return; 
    }
    isLoading = true; 
    console.log("%c[Notify] loadAllNotifications: 'isLoading' = true. Iniciando fetch...", "color: #007bff;");
    
    const listContainer = document.getElementById('notification-list-items');
    
    if (!listContainer) {
         console.error("[Notify] loadAllNotifications: No se encontró el CONTENEDOR DE LISTA (#notification-list-items). Abortando.");
         isLoading = false;
         return;
    }
    
    // --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
    const markAllButton = document.getElementById('notification-mark-all-btn');
    if (markAllButton) markAllButton.disabled = true; 
    // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
    
    listContainer.innerHTML = `
        <div class="notification-placeholder" id="notification-placeholder">
            <span class="material-symbols-rounded">
                <span class="logout-spinner" style="width: 32px; height: 32px; border-width: 3px;"></span>
            </span>
            <span data-i18n="notifications.loading">${getTranslation('notifications.loading')}</span>
        </div>
    `;

    const formData = new FormData();
    formData.append('action', 'get-notifications'); 

    try {
        const result = await callNotificationApi(formData);
        console.log("[Notify] loadAllNotifications: API respondió", result);
        
        listContainer.innerHTML = ''; 
        
        if (result.success && result.notifications) {
            setNotificationCount(result.unread_count || 0);
            
            if (result.notifications.length === 0) {
                console.log("[Notify] loadAllNotifications: No se encontraron notificaciones (vacío).");
                listContainer.innerHTML = `
                    <div class="notification-placeholder" id="notification-placeholder">
                        <span class="material-symbols-rounded">notifications_off</span>
                        <span data-i18n="notifications.empty">${getTranslation('notifications.empty')}</span>
                    </div>
                `;
                // --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
                if (markAllButton) markAllButton.disabled = true;
                // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
            } else {
                console.log(`[Notify] loadAllNotifications: Renderizando ${result.notifications.length} notificaciones.`);
                
                let lastDateGroup = null;
                result.notifications.forEach(notification => {
                    const notificationDate = new Date(notification.created_at + 'Z');
                    const currentGroup = getRelativeDateGroup(notificationDate);
                    
                    if (currentGroup !== lastDateGroup) {
                        const dividerHtml = `<div class="notification-date-divider">${currentGroup}</div>`;
                        listContainer.insertAdjacentHTML('beforeend', dividerHtml);
                        lastDateGroup = currentGroup;
                    }

                    const notificationHtml = addNotificationToUI(notification);
                    if (notificationHtml) {
                        listContainer.insertAdjacentHTML('beforeend', notificationHtml);
                    }
                });
                
                applyTranslations(listContainer);
                
                // --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
                if (markAllButton) {
                    if (result.unread_count > 0) {
                        console.log("[Notify] loadAllNotifications: Activando botón 'Marcar todas'.");
                        markAllButton.disabled = false;
                    } else {
                        console.log("[Notify] loadAllNotifications: Desactivando botón 'Marcar todas' (no hay no leídas).");
                        markAllButton.disabled = true;
                    }
                }
                // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
            }
            hasLoadedNotifications = true; 
            console.log("%c[Notify] loadAllNotifications: 'hasLoadedNotifications' = true.", "color: #28a745;");
        } else {
             console.error("[Notify] loadAllNotifications: La API falló (success=false).", result.message);
             listContainer.innerHTML = `
                <div class="notification-placeholder" id="notification-placeholder">
                    <span class="material-symbols-rounded">error</span>
                    <span data-i18n="js.api.errorServer">${getTranslation('js.api.errorServer')}</span>
                </div>
             `;
             hasLoadedNotifications = false; 
             console.log("%c[Notify] loadAllNotifications: 'hasLoadedNotifications' = false (API falló).", "color: #c62828;");
        }
    } catch (e) {
        console.error("[Notify] loadAllNotifications: Error de FETCH.", e);
         listContainer.innerHTML = `
            <div class="notification-placeholder" id="notification-placeholder">
                <span class="material-symbols-rounded">error</span>
                <span data-i18n="js.api.errorConnection">${getTranslation('js.api.errorConnection')}</span>
            </div>
         `;
         hasLoadedNotifications = false; 
         console.log("%c[Notify] loadAllNotifications: 'hasLoadedNotifications' = false (FETCH falló).", "color: #c62828;");
    } finally {
        isLoading = false; 
        console.log("%c[Notify] loadAllNotifications: 'isLoading' = false (finally).", "color: #007bff;");
    }
}

export async function fetchInitialCount() {
    console.log("[Notify] fetchInitialCount: Obteniendo conteo inicial...");
    const formData = new FormData();
    formData.append('action', 'get-notifications'); 
    const result = await callNotificationApi(formData);
    if (result.success && result.unread_count !== undefined) {
        console.log(`[Notify] fetchInitialCount: Conteo inicial es ${result.unread_count}`);
        setNotificationCount(result.unread_count);
    } else {
        console.error("[Notify] fetchInitialCount: No se pudo obtener el conteo inicial.");
    }
}

export function handleNotificationPing() {
    console.log("%c[Notify] handleNotificationPing: ¡PING RECIBIDO!", "color: #28a745; font-weight: bold;");
    
    setNotificationCount(currentNotificationCount + 1);
    
    hasLoadedNotifications = false;
    console.log("[Notify] handleNotificationPing: 'hasLoadedNotifications' = false (invalidado).");

    const notificationPanel = document.querySelector('[data-module="moduleNotifications"]');
    if (notificationPanel && notificationPanel.classList.contains('active')) {
        console.log("[Notify] handleNotificationPing: El panel está ABIERTO. Llamando a loadAllNotifications() para recarga en vivo...");
        loadAllNotifications(); 
    } else {
        console.log("[Notify] handleNotificationPing: El panel está CERRADO. Solo se invalidó la data.");
    }
}

export function initNotificationManager() {
    
    console.log("[Notify] initNotificationManager: Inicializando listeners...");

    const notificationButton = document.querySelector('[data-action="toggleModuleNotifications"]');
    if (notificationButton) {
        notificationButton.addEventListener('click', (e) => {
            console.log('[Notify] Clic en el botón de la campana.');
            e.stopPropagation(); 
            
            const module = document.querySelector('[data-module="moduleNotifications"]');
            if (!module) {
                console.error('[Notify] No se encontró el popover [data-module="moduleNotifications"]');
                return;
            }

            const isOpening = module.classList.contains('disabled');

            if (isOpening) {
                console.log('[Notify] El panel se está ABRIENDO.');
                deactivateAllModules(module); 
                module.classList.remove('disabled');
                module.classList.add('active');
                
                if (!hasLoadedNotifications) { 
                    console.log("[Notify] 'hasLoadedNotifications' es FALSO. Llamando a loadAllNotifications().");
                    loadAllNotifications();
                } else {
                    console.log("[Notify] 'hasLoadedNotifications' es VERDADERO. Mostrando datos cacheados.");
                }
            } else {
                console.log('[Notify] El panel se está CERRANDO.');
                deactivateAllModules(); 
            }
        });
    }
    
    const markAllButton = document.getElementById('notification-mark-all-btn');
    if (markAllButton) {
        markAllButton.addEventListener('click', async (e) => {
            console.log("[Notify] Clic en 'Marcar todas como leídas'.");
            e.preventDefault();
            e.stopPropagation();
            
            // --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
            markAllButton.disabled = true; 
            // --- ▲▲▲ FIN DE MODIFICACIÓN ▼▼▼ ---
            setNotificationCount(0); 

            document.querySelectorAll('#notification-list-items .notification-item.is-unread').forEach(item => {
                item.classList.remove('is-unread');
                item.classList.add('is-read');
                item.querySelector('.notification-unread-dot')?.remove();
            });

            console.log("[Notify] Llamando a API 'mark-all-read' en segundo plano...");
            const formData = new FormData();
            formData.append('action', 'mark-all-read');
            await callNotificationApi(formData); 
        });
    }

    const listContainer = document.getElementById('notification-list-items');
    if (listContainer) {
        listContainer.addEventListener('click', (e) => {
            
            if (e.target.closest('.notification-action-button')) {
                console.log("[Notify] Clic en un botón de acción (Aceptar/Rechazar). Omitiendo lectura.");
                return;
            }

            const item = e.target.closest('.notification-item.is-unread');
            
            if (item) {
                console.log(`[Notify] Clic en item no leído (ID: ${item.dataset.id}). Marcando como leído.`);
                const notificationId = item.dataset.id;
                const markAllButton = document.getElementById('notification-mark-all-btn');
                if (!notificationId) return; 

                item.classList.remove('is-unread');
                item.classList.add('is-read');
                item.querySelector('.notification-unread-dot')?.remove();

                // --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
                const newCount = Math.max(0, currentNotificationCount - 1);
                setNotificationCount(newCount);
                if (newCount === 0 && markAllButton) {
                    markAllButton.disabled = true;
                }
                // --- ▲▲▲ FIN DE MODIFICACIÓN ▼▼▼ ---

                console.log("[Notify] Llamando a API 'mark-one-read' en segundo plano...");
                const formData = new FormData();
                formData.append('action', 'mark-one-read');
                formData.append('notification_id', notificationId);
                
                callNotificationApi(formData).then(result => {
                    if (result.success) {
                        console.log(`[Notify] API 'mark-one-read' OK. Nuevo conteo: ${result.new_unread_count}`);
                        setNotificationCount(result.new_unread_count); 
                        // --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
                        if (result.new_unread_count === 0 && markAllButton) {
                            markAllButton.disabled = true;
                        }
                        // --- ▲▲▲ FIN DE MODIFICACIÓN ▼▼▼ ---
                    } else {
                        console.error("[Notify] Error al sincronizar 'mark-one-read' con el backend.");
                    }
                });
            } else {
                console.log("[Notify] Clic en item ya leído. Dejando que el router navegue.");
            }
        });
    }
    
    document.body.addEventListener('click', (e) => {
        const targetButton = e.target.closest('[data-action="friend-accept-request"], [data-action="friend-decline-request"]');
        
        if (targetButton && targetButton.closest('.notification-item')) {
            console.log("[Notify] Clic en Aceptar/Rechazar Amistad dentro del panel.");
            const item = targetButton.closest('.notification-item');
            if (item) {
                if (item.classList.contains('is-unread')) {
                    console.log("[Notify] El item de amistad no estaba leído. Marcando como leído primero.");
                    item.classList.remove('is-unread');
                    item.classList.add('is-read');
                    item.querySelector('.notification-unread-dot')?.remove();
                    
                    const formData = new FormData();
                    formData.append('action', 'mark-one-read');
                    formData.append('notification_id', item.dataset.id);
                    callNotificationApi(formData).then(result => {
                         if (result.success) setNotificationCount(result.new_unread_count);
                    });
                }
                
                item.style.opacity = '0.5'; 
                setTimeout(() => {
                    item.remove();
                    const listContainer = document.getElementById('notification-list-items');
                    const placeholder = listContainer ? listContainer.querySelector('.notification-placeholder') : null;
                    if (listContainer && listContainer.children.length === 0 && !placeholder) {
                         console.log("[Notify] Último item de amistad eliminado. Recargando para mostrar placeholder 'vacío'.");
                         hasLoadedNotifications = false; 
                         loadAllNotifications(); 
                    }
                }, 1000);
            }
        }
    });
}
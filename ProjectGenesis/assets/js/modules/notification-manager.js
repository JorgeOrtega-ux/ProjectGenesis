
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

export function setNotificationCount(count) {
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
                            <span>${getTranslation(textKey).replace('{username}', notification.actor_username)}</span>
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
                            <span>${getTranslation(textKey).replace('{username}', notification.actor_username)}</span>
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
                            <span>${getTranslation(textKey).replace('{username}', notification.actor_username)}</span>
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
                            <span>${getTranslation(textKey).replace('{username}', notification.actor_username)}</span>
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
        return; 
    }
    isLoading = true; 
    
    const listContainer = document.getElementById('notification-list-items');
    
    if (!listContainer) {
         isLoading = false;
         return;
    }
    
    const markAllButton = document.getElementById('notification-mark-all-btn');
    if (markAllButton) markAllButton.disabled = true; 
    
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
        
        listContainer.innerHTML = ''; 
        
        if (result.success && result.notifications) {
            setNotificationCount(result.unread_count || 0);
            
            if (result.notifications.length === 0) {
                listContainer.innerHTML = `
                    <div class="notification-placeholder" id="notification-placeholder">
                        <span class="material-symbols-rounded">notifications_off</span>
                        <span data-i18n="notifications.empty">${getTranslation('notifications.empty')}</span>
                    </div>
                `;
                if (markAllButton) markAllButton.disabled = true;
            } else {
                
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
                
                if (markAllButton) {
                    if (result.unread_count > 0) {
                        markAllButton.disabled = false;
                    } else {
                        markAllButton.disabled = true;
                    }
                }
            }
            hasLoadedNotifications = true; 
        } else {
             listContainer.innerHTML = `
                <div class="notification-placeholder" id="notification-placeholder">
                    <span class="material-symbols-rounded">error</span>
                    <span data-i18n="js.api.errorServer">${getTranslation('js.api.errorServer')}</span>
                </div>
             `;
             hasLoadedNotifications = false; 
        }
    } catch (e) {
         listContainer.innerHTML = `
            <div class="notification-placeholder" id="notification-placeholder">
                <span class="material-symbols-rounded">error</span>
                <span data-i18n="js.api.errorConnection">${getTranslation('js.api.errorConnection')}</span>
            </div>
         `;
         hasLoadedNotifications = false; 
    } finally {
        isLoading = false; 
    }
}

export async function fetchInitialCount() {
    const formData = new FormData();
    formData.append('action', 'get-notifications'); 
    const result = await callNotificationApi(formData);
    if (result.success && result.unread_count !== undefined) {
        setNotificationCount(result.unread_count);
    } else {
    }
}

export function handleNotificationPing() {
    
    setNotificationCount(currentNotificationCount + 1);
    
    hasLoadedNotifications = false;

    const notificationPanel = document.querySelector('[data-module="moduleNotifications"]');
    if (notificationPanel && notificationPanel.classList.contains('active')) {
        loadAllNotifications(); 
    } else {
    }
}

export function initNotificationManager() {
    
    const notificationButton = document.querySelector('[data-action="toggleModuleNotifications"]');
    if (notificationButton) {
        notificationButton.addEventListener('click', (e) => {
            e.stopPropagation(); 
            
            const module = document.querySelector('[data-module="moduleNotifications"]');
            if (!module) {
                return;
            }

            const isOpening = module.classList.contains('disabled');

            if (isOpening) {
                deactivateAllModules(module); 
                module.classList.remove('disabled');
                module.classList.add('active');
                
                if (!hasLoadedNotifications) { 
                    loadAllNotifications();
                } else {
                }
            } else {
                deactivateAllModules(); 
            }
        });
    }
    
    const markAllButton = document.getElementById('notification-mark-all-btn');
    if (markAllButton) {
        markAllButton.addEventListener('click', async (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            markAllButton.disabled = true; 
            setNotificationCount(0); 

            document.querySelectorAll('#notification-list-items .notification-item.is-unread').forEach(item => {
                item.classList.remove('is-unread');
                item.classList.add('is-read');
                item.querySelector('.notification-unread-dot')?.remove();
            });

            const formData = new FormData();
            formData.append('action', 'mark-all-read');
            await callNotificationApi(formData); 
        });
    }

    const listContainer = document.getElementById('notification-list-items');
    if (listContainer) {
        listContainer.addEventListener('click', (e) => {
            
            if (e.target.closest('.notification-action-button')) {
                return;
            }

            const item = e.target.closest('.notification-item.is-unread');
            
            if (item) {
                const notificationId = item.dataset.id;
                const markAllButton = document.getElementById('notification-mark-all-btn');
                if (!notificationId) return; 

                item.classList.remove('is-unread');
                item.classList.add('is-read');
                item.querySelector('.notification-unread-dot')?.remove();

                const newCount = Math.max(0, currentNotificationCount - 1);
                setNotificationCount(newCount);
                if (newCount === 0 && markAllButton) {
                    markAllButton.disabled = true;
                }

                const formData = new FormData();
                formData.append('action', 'mark-one-read');
                formData.append('notification_id', notificationId);
                
                callNotificationApi(formData).then(result => {
                    if (result.success) {
                        setNotificationCount(result.new_unread_count); 
                        if (result.new_unread_count === 0 && markAllButton) {
                            markAllButton.disabled = true;
                        }
                    } else {
                    }
                });
            } else {
            }
        });
    }
    
    document.body.addEventListener('click', (e) => {
        const targetButton = e.target.closest('[data-action="friend-accept-request"], [data-action="friend-decline-request"]');
        
        if (targetButton && targetButton.closest('.notification-item')) {
            const item = targetButton.closest('.notification-item');
            if (item) {
                if (item.classList.contains('is-unread')) {
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
                         hasLoadedNotifications = false; 
                         loadAllNotifications(); 
                    }
                }, 1000);
            }
        }
    });
}
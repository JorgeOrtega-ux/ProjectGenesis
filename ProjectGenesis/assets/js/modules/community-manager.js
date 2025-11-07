// FILE: assets/js/modules/community-manager.js

import { callCommunityApi } from '../services/api-service.js';
import { getTranslation } from '../services/i18n-manager.js';
import { deactivateAllModules } from '../app/main-controller.js';
import { loadPage } from '../app/url-manager.js';
let currentCommunityId = null;
let currentCommunityName = null;
// --- ▼▼▼ LÍNEA AÑADIDA ▼▼▼ ---
let currentCommunityUuid = null;
// --- ▲▲▲ FIN LÍNEA AÑADIDA ▲▲▲ ---

/**
 * Muestra u oculta un spinner simple en los botones de unirse/abandonar
 * @param {HTMLButtonElement} button El botón
 * @param {boolean} isLoading True para mostrar spinner, false para quitarlo
 */
function toggleJoinLeaveSpinner(button, isLoading) {
    if (!button) return;
    button.disabled = isLoading;
    if (isLoading) {
        button.dataset.originalText = button.innerHTML;
        button.innerHTML = `<span class="logout-spinner" style="width: 20px; height: 20px; border-width: 2px; margin: 0 auto; border-top-color: inherit;"></span>`;
    } else {
        button.innerHTML = button.dataset.originalText;
    }
}

/**
 * Muestra un spinner en el botón principal de "Unirse al Grupo"
 * @param {HTMLButtonElement} button El botón
 * @param {boolean} isLoading True para mostrar spinner, false para quitarlo
 */
function togglePrimaryButtonSpinner(button, isLoading) {
    if (!button) return;
    button.disabled = isLoading;
    if (isLoading) {
        button.dataset.originalText = button.innerHTML;
        button.innerHTML = `<span class"logout-spinner" style="width: 20px; height: 20px; border-width: 2px; margin: 0 auto; border-top-color: #ffffff; border-left-color: #ffffff20; border-bottom-color: #ffffff20; border-right-color: #ffffff20;"></span>`;
    } else {
        button.innerHTML = button.dataset.originalText;
    }
}

/**
 * Muestra un error inline en el formulario de unirse
 * @param {string} messageKey Clave de traducción del error
 */
function showJoinGroupError(messageKey) {
    const errorDiv = document.querySelector('#join-group-form .component-card__error');
    if (!errorDiv) return;
    errorDiv.textContent = getTranslation(messageKey);
    errorDiv.classList.remove('disabled');
    errorDiv.classList.add('active');
}

/**
 * Oculta el error inline
 */
function hideJoinGroupError() {
    const errorDiv = document.querySelector('#join-group-form .component-card__error');
    if (errorDiv) {
        errorDiv.classList.add('disabled');
        errorDiv.classList.remove('active');
    }
}

/**
 * Actualiza la UI de un botón de unirse/abandonar
 * @param {HTMLButtonElement} button El botón que se clickeó
 * @param {'join' | 'leave'} newAction La nueva acción que tendrá el botón
 */
function updateJoinButtonUI(button, newAction) {
    if (newAction === 'leave') {
        button.setAttribute('data-action', 'leave-community');
        button.innerHTML = getTranslation('join_group.leave');
        button.classList.add('danger');
    } else {
        button.setAttribute('data-action', 'join-community');
        button.innerHTML = getTranslation('join_group.join');
        button.classList.remove('danger');
    }
}

/**
 * Selecciona una comunidad y actualiza la toolbar
 * @param {string} communityId ID de la comunidad
 * @param {string} communityName Nombre de la comunidad
 * @param {string} communityUuid UUID de la comunidad
 */
// --- ▼▼▼ INICIO DE MODIFICACIÓN (FUNCIÓN selectCommunity) ▼▼▼ ---
function selectCommunity(communityId, communityName, communityUuid = null) {
    currentCommunityId = communityId;
    currentCommunityName = communityName;
    currentCommunityUuid = communityUuid;
    
    // Guardar en sessionStorage para persistencia entre recargas
    sessionStorage.setItem('currentCommunityId', communityId);
    sessionStorage.setItem('currentCommunityName', communityName);
    if (communityUuid) {
        sessionStorage.setItem('currentCommunityUuid', communityUuid);
    } else {
        sessionStorage.removeItem('currentCommunityUuid');
    }

    const displayDiv = document.getElementById('current-group-display');
    if (displayDiv) {
        displayDiv.textContent = communityName;
        displayDiv.setAttribute('data-community-id', communityId); // Guardar el ID aquí
        displayDiv.classList.add('active');
    }
    
    // Actualizar clase active en popover
    const popover = document.querySelector('[data-module="moduleSelectGroup"]');
    if (popover) {
        popover.querySelectorAll('.menu-link').forEach(link => {
            link.classList.remove('active');
        });
        const activeLink = popover.querySelector(`.menu-link[data-community-id="${communityId}"]`);
        if (activeLink) {
            activeLink.classList.add('active');
        }
    }
    
    // --- Lógica de URL ---
    let newPath;
    const basePath = window.projectBasePath || '/ProjectGenesis';

    if (communityId === 'main_feed' || !communityUuid) {
        newPath = basePath + '/';
    } else {
        newPath = basePath + '/c/' + communityUuid;
    }

    // Cambiar la URL solo si es diferente
    if (window.location.pathname !== newPath) {
        // Usar pushState para cambiar la URL sin recargar la página
        history.pushState({ communityId: communityId }, '', newPath);
        
        // --- ▼▼▼ INICIO DE LA CORRECCIÓN (Llamar a loadPage) ▼▼▼ ---
        // Después de cambiar la URL, forzar la recarga del contenido de la página 'home'
        // con los nuevos parámetros (o sin ellos para el feed principal).
        if (communityId === 'main_feed') {
            loadPage('home', 'toggleSectionHome');
        } else {
            // Pasamos el UUID como fetchParam para que router.php lo lea
            loadPage('home', 'toggleSectionHome', { community_uuid: communityUuid });
        }
        // --- ▲▲▲ FIN DE LA CORRECCIÓN ▲▲▲ ---
    }
    
    console.log(`Grupo seleccionado: ${communityName} (ID: ${communityId}, UUID: ${communityUuid})`);
    
    deactivateAllModules();
}
// --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

/**
 * Carga la comunidad guardada al iniciar la app
 */
// --- ▼▼▼ INICIO DE MODIFICACIÓN (FUNCIÓN loadSavedCommunity) ▼▼▼ ---
export function loadSavedCommunity() { // <--- ¡CAMBIO! Se añade "export"
    const mainFeedName = getTranslation('home.popover.mainFeed');
    const basePath = window.projectBasePath || '/ProjectGenesis';

    // Prioridad 1: Carga directa de URL de comunidad (PHP nos dio los datos)
    if (window.initialCommunityId && window.initialCommunityName && window.initialCommunityUuid) {
        selectCommunity(
            window.initialCommunityId,
            window.initialCommunityName,
            window.initialCommunityUuid
        );
        // Limpiar para que no se re-use en navegación SPA
        window.initialCommunityId = null;
        window.initialCommunityName = null;
        window.initialCommunityUuid = null;
        return;
    }
    
    // Prioridad 2: Carga directa de URL de Main Feed (/)
    if (window.location.pathname === basePath || window.location.pathname === basePath + '/') {
         selectCommunity(
            'main_feed',
            mainFeedName,
            null
         );
         return;
    }

    // Prioridad 3: Persistencia de Sesión (fallback)
    // (p.ej. recargar la página de /settings, el display debe recordar la última comunidad)
    const savedId = sessionStorage.getItem('currentCommunityId') || 'main_feed';
    const savedName = sessionStorage.getItem('currentCommunityName') || mainFeedName;
    const savedUuid = sessionStorage.getItem('currentCommunityUuid') || null;

    selectCommunity(savedId, savedName, savedUuid);
}
// --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---


export function initCommunityManager() {
    
    // Cargar la comunidad seleccionada al inicio
    // loadSavedCommunity(); // <--- ¡CAMBIO! Esta línea se elimina de aquí.

    document.body.addEventListener('click', async (e) => {
        const button = e.target.closest('button[data-action], button[data-auth-action]');
        
        // --- ▼▼▼ INICIO DE MODIFICACIÓN (Manejar clic en popover) ▼▼▼ ---
        if (!button) {
            // Lógica para seleccionar grupo del popover
            const groupLink = e.target.closest('[data-module="moduleSelectGroup"] .menu-link[data-community-id]');
            if (groupLink) {
                e.preventDefault();
                const communityId = groupLink.dataset.communityId;
                const communityName = groupLink.dataset.communityName; // Usar el nuevo atributo
                const communityUuid = groupLink.dataset.communityUuid || null; // Usar el nuevo atributo
                
                selectCommunity(communityId, communityName, communityUuid);
            }
            return;
        }
        // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

        const action = button.dataset.action;
        const authAction = button.dataset.authAction;

        // --- Unirse a comunidad pública ---
        if (action === 'join-community') {
            e.preventDefault();
            const communityId = button.dataset.communityId;
            if (!communityId) return;

            toggleJoinLeaveSpinner(button, true);
            
            const formData = new FormData();
            formData.append('action', 'join-community');
            formData.append('community_id', communityId);

            const result = await callCommunityApi(formData);
            
            if (result.success) {
                window.showAlert(getTranslation(result.message || 'js.join_group.joinSuccess'), 'success');
                updateJoinButtonUI(button, 'leave');
            } else {
                window.showAlert(getTranslation(result.message || 'js.join_group.apiError'), 'error');
            }
            toggleJoinLeaveSpinner(button, false);
        }
        
        // --- Abandonar comunidad pública ---
        else if (action === 'leave-community') {
            e.preventDefault();
            const communityId = button.dataset.communityId;
            if (!communityId) return;

            toggleJoinLeaveSpinner(button, true);

            const formData = new FormData();
            formData.append('action', 'leave-community');
            formData.append('community_id', communityId);

            const result = await callCommunityApi(formData);
            
            if (result.success) {
                window.showAlert(getTranslation(result.message || 'js.join_group.leaveSuccess'), 'success');
                updateJoinButtonUI(button, 'join');
            } else {
                window.showAlert(getTranslation(result.message || 'js.join_group.apiError'), 'error');
            }
            toggleJoinLeaveSpinner(button, false);
        }

        // --- Unirse a comunidad privada por código ---
        else if (authAction === 'submit-join-code') {
            e.preventDefault();
            hideJoinGroupError();
            
            const codeInput = document.getElementById('join-code');
            if (!codeInput || !codeInput.value) {
                showJoinGroupError('js.join_group.invalidCode');
                return;
            }
            
            togglePrimaryButtonSpinner(button, true);

            const formData = new FormData();
            formData.append('action', 'join-private-community');
            formData.append('join_code', codeInput.value);

            const result = await callCommunityApi(formData);

            if (result.success) {
                window.showAlert(getTranslation(result.message || 'js.join_group.joinSuccess'), 'success');
                codeInput.value = '';
                // Opcional: Redirigir a home y seleccionar el nuevo grupo
                // --- ▼▼▼ INICIO DE MODIFICACIÓN (Seleccionar al unirse) ▼▼▼ ---
                if (result.communityName && result.communityUuid && result.communityId) {
                     // Llama a selectCommunity para cambiar la URL y guardar el estado
                     selectCommunity(result.communityId, result.communityName, result.communityUuid);
                    // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
                    // Navegar a la página de inicio
                    const link = document.createElement('a');
                    link.href = window.projectBasePath + '/';
                    link.setAttribute('data-nav-js', 'true');
                    document.body.appendChild(link);
                    link.click();
                    link.remove();
                }
            } else {
                showJoinGroupError(result.message || 'js.join_group.apiError');
            }
            togglePrimaryButtonSpinner(button, false);
        }

        // --- ▼▼▼ INICIO DE MODIFICACIÓN (Abrir popover de "Mis Grupos") ▼▼▼ ---
        else if (action === 'home-select-group') {
            e.preventDefault();
            e.stopPropagation(); // Detener para que el 'click' de main-controller no lo cierre
            
            const popover = document.querySelector('[data-module="moduleSelectGroup"]');
            const listElement = document.getElementById('my-groups-list');
            
            if (!popover || !listElement) return;

            // Mostrar popover y estado de carga
            deactivateAllModules(popover); // Cerrar otros popovers
            popover.classList.toggle('disabled');
            popover.classList.toggle('active');

            if (!popover.classList.contains('active')) {
                return; // Si se está cerrando, no hacer nada
            }
            
            // 1. Obtener el ID seleccionado actualmente (desde la variable global)
            const currentSelectedId = currentCommunityId || 'main_feed';

            // 2. Añadir "Feed principal" estáticamente
            const mainFeedText = getTranslation('home.popover.mainFeed');
            const mainFeedActive = (currentSelectedId === 'main_feed') ? 'active' : '';
            listElement.innerHTML = `
                <div class="menu-link ${mainFeedActive}" 
                     data-community-id="main_feed" 
                     data-community-name="${mainFeedText}" 
                     data-community-uuid="">
                    <div class="menu-link-icon">
                        <span class="material-symbols-rounded">feed</span>
                    </div>
                    <div class="menu-link-text">
                        ${mainFeedText}
                    </div>
                </div>
            `;

            // 3. Añadir separador
            listElement.innerHTML += `<div style="height: 1px; background-color: #00000020; margin: 4px 8px;"></div>`;
            
            // 4. Cargar el resto de comunidades
            const formData = new FormData();
            formData.append('action', 'get-my-communities');

            const result = await callCommunityApi(formData);

            if (result.success && result.communities) {
                if (result.communities.length === 0) {
                    listElement.innerHTML += `<div class="menu-link" data-i18n="home.popover.noGroups" style="pointer-events: none; opacity: 0.7;">No estás en ningún grupo.</div>`;
                } else {
                    // 5. Añadir las comunidades (append)
                    result.communities.forEach(community => {
                        const communityActive = (currentSelectedId == community.id) ? 'active' : '';
                        listElement.innerHTML += `
                            <div class="menu-link ${communityActive}" 
                                 data-community-id="${community.id}" 
                                 data-community-name="${community.name}" 
                                 data-community-uuid="${community.uuid}">
                                <div class="menu-link-icon">
                                    <span class="material-symbols-rounded">group</span>
                                </div>
                                <div class="menu-link-text">
                                    ${community.name}
                                </div>
                            </div>
                        `;
                    });
                }
            } else {
                listElement.innerHTML += `<div class="menu-link" data-i18n="js.api.errorServer" style="pointer-events: none; opacity: 0.7;">Error al cargar grupos.</div>`;
            }
        }
        // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
    });

    // Listener para ocultar el error en `join-group.php`
    const joinCodeInput = document.getElementById('join-code');
    if (joinCodeInput) {
        joinCodeInput.addEventListener('input', hideJoinGroupError);
    }
}
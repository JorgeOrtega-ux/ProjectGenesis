// FILE: assets/js/modules/community-manager.js

import { callCommunityApi } from '../services/api-service.js';
import { getTranslation } from '../services/i18n-manager.js';
import { deactivateAllModules } from '../app/main-controller.js';

let currentCommunityId = null;
let currentCommunityName = null;

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
        button.innerHTML = `<span class="logout-spinner" style="width: 20px; height: 20px; border-width: 2px; margin: 0 auto; border-top-color: #ffffff; border-left-color: #ffffff20; border-bottom-color: #ffffff20; border-right-color: #ffffff20;"></span>`;
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
 */
function selectCommunity(communityId, communityName) {
    currentCommunityId = communityId;
    currentCommunityName = communityName;
    
    // Guardar en sessionStorage para persistencia entre recargas
    sessionStorage.setItem('currentCommunityId', communityId);
    sessionStorage.setItem('currentCommunityName', communityName);

    const displayDiv = document.getElementById('current-group-display');
    if (displayDiv) {
        displayDiv.textContent = communityName;
        displayDiv.classList.add('active');
    }
    
    // (Opcional: aquí podrías disparar una recarga de contenido para el grupo)
    console.log(`Grupo seleccionado: ${communityName} (ID: ${communityId})`);
    
    deactivateAllModules();
}

/**
 * Carga la comunidad guardada al iniciar la app
 */
function loadSavedCommunity() {
    const savedId = sessionStorage.getItem('currentCommunityId');
    const savedName = sessionStorage.getItem('currentCommunityName');

    if (savedId && savedName) {
        selectCommunity(savedId, savedName);
    }
}


export function initCommunityManager() {
    
    // Cargar la comunidad seleccionada al inicio
    loadSavedCommunity();

    document.body.addEventListener('click', async (e) => {
        const button = e.target.closest('button[data-action], button[data-auth-action]');
        if (!button) {
            
            // --- Lógica para seleccionar grupo del popover ---
            const groupLink = e.target.closest('[data-module="moduleSelectGroup"] .menu-link[data-community-id]');
            if (groupLink) {
                e.preventDefault();
                const communityId = groupLink.dataset.communityId;
                const communityName = groupLink.querySelector('.menu-link-text').textContent;
                selectCommunity(communityId, communityName);
            }
            return;
        }

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
                // Opcional: Redirigir a home
            } else {
                showJoinGroupError(result.message || 'js.join_group.apiError');
            }
            togglePrimaryButtonSpinner(button, false);
        }

        // --- Abrir popover de "Mis Grupos" ---
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

            listElement.innerHTML = `<div class="menu-link" data-i18n="home.popover.loading">Cargando...</div>`;

            const formData = new FormData();
            formData.append('action', 'get-my-communities');

            const result = await callCommunityApi(formData);

            if (result.success && result.communities) {
                if (result.communities.length === 0) {
                    listElement.innerHTML = `<div class="menu-link" data-i18n="home.popover.noGroups">No estás en ningún grupo.</div>`;
                } else {
                    listElement.innerHTML = result.communities.map(community => `
                        <div class="menu-link" data-community-id="${community.id}">
                            <div class="menu-link-icon">
                                <span class="material-symbols-rounded">group</span>
                            </div>
                            <div class="menu-link-text">
                                ${community.name}
                            </div>
                        </div>
                    `).join('');
                }
            } else {
                listElement.innerHTML = `<div class="menu-link" data-i18n="js.api.errorServer">Error al cargar grupos.</div>`;
            }
        }
    });

    // Listener para ocultar el error en `join-group.php`
    const joinCodeInput = document.getElementById('join-code');
    if (joinCodeInput) {
        joinCodeInput.addEventListener('input', hideJoinGroupError);
    }
}
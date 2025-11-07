// FILE: assets/js/modules/community-manager.js
// (MODIFICADO PARA MANEJAR VOTACIÓN DE ENCUESTAS)

import { callCommunityApi, callPublicationApi } from '../services/api-service.js';
import { getTranslation } from '../services/i18n-manager.js';
import { deactivateAllModules } from '../app/main-controller.js';
import { loadPage } from '../app/url-manager.js';

let currentCommunityId = null;
let currentCommunityName = null;
let currentCommunityUuid = null;

/**
 * Muestra u oculta un spinner simple en los botones de unirse/abandonar
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
 */
function selectCommunity(communityId, communityName, communityUuid = null) {
    currentCommunityId = communityId;
    currentCommunityName = communityName;
    currentCommunityUuid = communityUuid;
    
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
        displayDiv.setAttribute('data-community-id', communityId);
        displayDiv.classList.add('active');
    }
    
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
    
    let newPath;
    const basePath = window.projectBasePath || '/ProjectGenesis';

    if (communityId === 'main_feed' || !communityUuid) {
        newPath = basePath + '/';
    } else {
        newPath = basePath + '/c/' + communityUuid;
    }

    if (window.location.pathname !== newPath) {
        history.pushState({ communityId: communityId }, '', newPath);
        
        if (communityId === 'main_feed') {
            loadPage('home', 'toggleSectionHome');
        } else {
            loadPage('home', 'toggleSectionHome', { community_uuid: communityUuid });
        }
    }
    
    console.log(`Grupo seleccionado: ${communityName} (ID: ${communityId}, UUID: ${communityUuid})`);
    
    deactivateAllModules();
}

/**
 * Carga la comunidad guardada al iniciar la app
 */
export function loadSavedCommunity() { 
    const mainFeedName = getTranslation('home.popover.mainFeed');
    const basePath = window.projectBasePath || '/ProjectGenesis';

    if (window.initialCommunityId && window.initialCommunityName && window.initialCommunityUuid) {
        selectCommunity(
            window.initialCommunityId,
            window.initialCommunityName,
            window.initialCommunityUuid
        );
        window.initialCommunityId = null;
        window.initialCommunityName = null;
        window.initialCommunityUuid = null;
        return;
    }
    
    if (window.location.pathname === basePath || window.location.pathname === basePath + '/') {
         selectCommunity(
            'main_feed',
            mainFeedName,
            null
         );
         return;
    }

    const savedId = sessionStorage.getItem('currentCommunityId') || 'main_feed';
    const savedName = sessionStorage.getItem('currentCommunityName') || mainFeedName;
    const savedUuid = sessionStorage.getItem('currentCommunityUuid') || null;

    selectCommunity(savedId, savedName, savedUuid);
}

// --- ▼▼▼ INICIO DE NUEVA FUNCIÓN (RENDERIZAR RESULTADOS DE ENCUESTA) ▼▼▼ ---
/**
 * Reemplaza el formulario de votación por los resultados
 * @param {HTMLElement} pollContainer - El div .poll-container
 * @param {object} resultsData - Los datos devueltos por la API (results, totalVotes)
 */
function renderPollResults(pollContainer, resultsData) {
    const { results, totalVotes } = resultsData;
    const currentUserId = window.userId || 0;
    
    let resultsHtml = '<div class="poll-results">';
    
    // Encontrar la opción que votó el usuario (la API no nos dice esto, pero podemos suponer la última)
    // NOTA: La API *debería* devolver la opción votada. Asumiremos que está en resultsData.userVoteOptionId
    // PERO... la API `vote-poll` no devuelve `user_voted_option_id`.
    // Lo buscaremos en el formulario que *acaba de ser enviado*.
    const form = pollContainer.querySelector('.poll-form');
    let votedOptionId = null;
    if (form) {
        const formData = new FormData(form);
        votedOptionId = formData.get('poll_option_id');
    }

    results.forEach(option => {
        const voteCount = parseInt(option.vote_count, 10);
        const percentage = (totalVotes > 0) ? Math.round((voteCount / totalVotes) * 100) : 0;
        const isUserVote = (option.id == votedOptionId); // Comparar con la opción enviada
        
        resultsHtml += `
            <div class="poll-option-result ${isUserVote ? 'voted-by-user' : ''}">
                <div class="poll-option-bar" style="width: ${percentage}%;"></div>
                <div class="poll-option-text">
                    <span>${escapeHTML(option.option_text)}</span>
                    ${isUserVote ? '<span class="material-symbols-rounded poll-user-vote-icon">check_circle</span>' : ''}
                </div>
                <div class="poll-option-percent">${percentage}%</div>
            </div>
        `;
    });
    
    resultsHtml += `<p class="poll-total-votes" data-i18n="home.poll.totalVotes" data-count="${totalVotes}">${totalVotes} votos</p>`;
    resultsHtml += '</div>';

    pollContainer.innerHTML = resultsHtml;
}

function escapeHTML(str) {
    return str.replace(/[&<>"']/g, function(m) {
        return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        }[m];
    });
}
// --- ▲▲▲ FIN DE NUEVA FUNCIÓN ---


export function initCommunityManager() {
    
    document.body.addEventListener('click', async (e) => {
        const button = e.target.closest('button[data-action], button[data-auth-action], button[data-tooltip]');
        
        if (!button) {
            const groupLink = e.target.closest('[data-module="moduleSelectGroup"] .menu-link[data-community-id]');
            if (groupLink) {
                e.preventDefault();
                const communityId = groupLink.dataset.communityId;
                const communityName = groupLink.dataset.communityName; 
                const communityUuid = groupLink.dataset.communityUuid || null; 
                
                selectCommunity(communityId, communityName, communityUuid);
            }
            return;
        }

        const action = button.dataset.action;
        const authAction = button.dataset.authAction;
        const tooltipAction = button.dataset.tooltip;

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
                if (result.communityName && result.communityUuid && result.communityId) {
                     selectCommunity(result.communityId, result.communityName, result.communityUuid);
                }
            } else {
                showJoinGroupError(result.message || 'js.join_group.apiError');
            }
            togglePrimaryButtonSpinner(button, false);
        }

        else if (tooltipAction === 'home.actions.comment') {
            e.preventDefault();
            const postCard = button.closest('.component-card--post');
            if (!postCard) return;
            const commentContainer = postCard.querySelector('.post-comment-input-container');
            if (!commentContainer) return;
            commentContainer.classList.toggle('active');
            if (commentContainer.classList.contains('active')) {
                const commentInput = commentContainer.querySelector('.post-comment-input');
                if (commentInput) {
                    commentInput.focus();
                }
            }
        }

        else if (action === 'home-select-group') {
            e.preventDefault();
            e.stopPropagation(); 
            const popover = document.querySelector('[data-module="moduleSelectGroup"]');
            const listElement = document.getElementById('my-groups-list');
            if (!popover || !listElement) return;
            deactivateAllModules(popover); 
            popover.classList.toggle('disabled');
            popover.classList.toggle('active');
            if (!popover.classList.contains('active')) {
                return; 
            }
            const currentSelectedId = currentCommunityId || 'main_feed';
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
            listElement.innerHTML += `<div style="height: 1px; background-color: #00000020; margin: 4px 8px;"></div>`;
            const formData = new FormData();
            formData.append('action', 'get-my-communities');
            const result = await callCommunityApi(formData);
            if (result.success && result.communities) {
                if (result.communities.length === 0) {
                    listElement.innerHTML += `<div class="menu-link" data-i18n="home.popover.noGroups" style="pointer-events: none; opacity: 0.7;">No estás en ningún grupo.</div>`;
                } else {
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
    });

    // --- ▼▼▼ INICIO DE NUEVO LISTENER (VOTAR ENCUESTA) ▼▼▼ ---
    document.body.addEventListener('submit', async (e) => {
        // 1. Asegurarse de que es un formulario de encuesta
        const pollForm = e.target.closest('form.poll-form[data-action="submit-poll-vote"]');
        if (!pollForm) return;
        
        e.preventDefault();
        
        const submitButton = pollForm.querySelector('button[type="submit"]');
        const pollContainer = pollForm.closest('.poll-container');
        
        if (submitButton.disabled) return;
        
        const formData = new FormData(pollForm);
        formData.append('action', 'vote-poll'); // Acción de la API
        
        togglePrimaryButtonSpinner(submitButton, true);

        try {
            const result = await callPublicationApi(formData);
            
            if (result.success && result.results) {
                // Éxito: renderizar los resultados
                renderPollResults(pollContainer, {
                    results: result.results,
                    totalVotes: result.totalVotes
                });
            } else {
                // Error (ej. ya votó)
                window.showAlert(getTranslation(result.message || 'js.api.errorServer'), 'error');
                togglePrimaryButtonSpinner(submitButton, false);
            }
            
        } catch (error) {
            window.showAlert(getTranslation('js.api.errorConnection'), 'error');
            togglePrimaryButtonSpinner(submitButton, false);
        }
    });
    // --- ▲▲▲ FIN DE NUEVO LISTENER ---

    const joinCodeInput = document.getElementById('join-code');
    if (joinCodeInput) {
        joinCodeInput.addEventListener('input', hideJoinGroupError);
    }
}
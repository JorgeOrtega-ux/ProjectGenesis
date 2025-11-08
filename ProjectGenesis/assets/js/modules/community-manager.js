// FILE: assets/js/modules/community-manager.js
// (MODIFICADO PARA MANEJAR VOTACIÓN, LIKES Y COMENTARIOS)

import { callCommunityApi, callPublicationApi } from '../services/api-service.js';
import { getTranslation } from '../services/i18n-manager.js';
import { deactivateAllModules } from '../app/main-controller.js';
import { loadPage } from '../app/url-manager.js';

let currentCommunityId = null;
let currentCommunityName = null;
let currentCommunityUuid = null;

// --- Funciones auxiliares de UI (spinner, error, etc.) ---

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

function showJoinGroupError(messageKey) {
    const errorDiv = document.querySelector('#join-group-form .component-card__error');
    if (!errorDiv) return;
    errorDiv.textContent = getTranslation(messageKey);
    errorDiv.classList.remove('disabled');
    errorDiv.classList.add('active');
}

function hideJoinGroupError() {
    const errorDiv = document.querySelector('#join-group-form .component-card__error');
    if (errorDiv) {
        errorDiv.classList.add('disabled');
        errorDiv.classList.remove('active');
    }
}

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

// --- Funciones de Gestión de Comunidad (Sin cambios) ---

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

// --- Funciones de Encuesta (Sin cambios) ---

function renderPollResults(pollContainer, resultsData) {
    const { results, totalVotes } = resultsData;
    const currentUserId = window.userId || 0;
    
    let resultsHtml = '<div class="poll-results">';
    
    const form = pollContainer.querySelector('.poll-form');
    let votedOptionId = null;
    if (form) {
        const formData = new FormData(form);
        votedOptionId = formData.get('poll_option_id');
    }

    results.forEach(option => {
        const voteCount = parseInt(option.vote_count, 10);
        const percentage = (totalVotes > 0) ? Math.round((voteCount / totalVotes) * 100) : 0;
        const isUserVote = (option.id == votedOptionId); 
        
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
    if (!str) return '';
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

// --- ▼▼▼ INICIO DE NUEVAS FUNCIONES (LIKE Y COMENTARIOS) ▼▼▼ ---

/**
 * Maneja el clic en el botón "Me Gusta".
 */
async function handleLikeToggle(button) {
    const postId = button.dataset.postId;
    if (!postId || button.disabled) return;

    button.disabled = true; // Prevenir doble clic
    const icon = button.querySelector('.material-symbols-rounded');
    const text = button.querySelector('.action-text');
    const wasLiked = button.classList.contains('active');

    const formData = new FormData();
    formData.append('action', 'like-toggle');
    formData.append('publication_id', postId);

    try {
        const result = await callPublicationApi(formData);
        if (result.success) {
            text.textContent = result.newLikeCount;
            if (result.userHasLiked) {
                button.classList.add('active');
                icon.textContent = 'favorite';
            } else {
                button.classList.remove('active');
                icon.textContent = 'favorite_border';
            }
        } else {
            // Revertir en caso de error
            window.showAlert(getTranslation(result.message || 'js.api.errorServer'), 'error');
        }
    } catch (e) {
        window.showAlert(getTranslation('js.api.errorConnection'), 'error');
    } finally {
        button.disabled = false;
    }
}

/**
 * Maneja el clic en el botón "Comentar" (para mostrar/ocultar).
 */
async function handleToggleComments(button) {
    const postId = button.dataset.postId;
    const commentsContainer = document.getElementById(`comments-for-post-${postId}`);
    if (!commentsContainer || button.disabled) return;

    if (commentsContainer.classList.contains('active')) {
        // Si ya está abierto, simplemente ciérralo
        commentsContainer.classList.remove('active');
        commentsContainer.innerHTML = ''; // Limpiar contenido
    } else {
        // Si está cerrado, ábrelo y carga los comentarios
        commentsContainer.classList.add('active');
        commentsContainer.innerHTML = `<div class="comment-loader"><span class="logout-spinner"></span></div>`; // Mostrar spinner
        button.disabled = true;

        const formData = new FormData();
        formData.append('action', 'get-comments');
        formData.append('publication_id', postId);

        try {
            const result = await callPublicationApi(formData);
            if (result.success && result.comments) {
                renderComments(commentsContainer, result.comments);
            } else {
                commentsContainer.innerHTML = `<p class="comment-error">${getTranslation(result.message || 'js.api.errorServer')}</p>`;
            }
        } catch (e) {
            commentsContainer.innerHTML = `<p class="comment-error">${getTranslation('js.api.errorConnection')}</p>`;
        } finally {
            button.disabled = false;
        }
    }
}

/**
 * Renderiza la lista de comentarios (Nivel 1 y Nivel 2).
 */
function renderComments(container, comments) {
    container.innerHTML = ''; // Limpiar spinner
    if (comments.length === 0) {
        // (Añadir 'js.publication.noComments' a tus JSON)
        container.innerHTML = `<p class="comment-placeholder" data-i18n="js.publication.noComments">No hay comentarios todavía.</p>`;
        return;
    }

    const defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";
    
    comments.forEach(comment => {
        let repliesHtml = '<div class="comment-replies-container">';
        if (comment.replies && comment.replies.length > 0) {
            comment.replies.forEach(reply => {
                const replyAvatar = reply.profile_image_url || defaultAvatar;
                repliesHtml += `
                    <div class="comment-item is-reply" data-comment-id="${reply.id}">
                        <div class="comment-avatar"><img src="${escapeHTML(replyAvatar)}" alt="${escapeHTML(reply.username)}"></div>
                        <div class="comment-content">
                            <div class="comment-header">
                                <span class="comment-username">${escapeHTML(reply.username)}</span>
                                <span class="comment-timestamp">· ${formatTimeAgo(reply.created_at)}</span>
                            </div>
                            <div class="comment-body">${escapeHTML(reply.comment_text)}</div>
                            <div class="comment-actions">
                                </div>
                        </div>
                    </div>
                `;
            });
        }
        repliesHtml += '</div>';

        const commentAvatar = comment.profile_image_url || defaultAvatar;
        const commentHtml = `
            <div class="comment-item" data-comment-id="${comment.id}">
                <div class="comment-avatar"><img src="${escapeHTML(commentAvatar)}" alt="${escapeHTML(comment.username)}"></div>
                <div class="comment-content">
                    <div class="comment-header">
                        <span class="comment-username">${escapeHTML(comment.username)}</span>
                        <span class="comment-timestamp">· ${formatTimeAgo(comment.created_at)}</span>
                    </div>
                    <div class="comment-body">${escapeHTML(comment.comment_text)}</div>
                    <div class="comment-actions">
                        <button type="button" class="comment-action-btn" data-action="show-reply-form" data-comment-id="${comment.id}">Responder</button>
                    </div>
                    ${repliesHtml}
                    <div class="comment-reply-form-container" id="reply-form-for-${comment.id}"></div>
                </div>
            </div>
        `;
        container.innerHTML += commentHtml;
    });
}

/**
 * Maneja el envío de un formulario de comentario (Nivel 1 o Nivel 2).
 */
async function handlePostComment(form) {
    const submitButton = form.querySelector('button[type="submit"]');
    // --- ▼▼▼ LÍNEA MODIFICADA: Asegurar que seleccionamos el input correcto ▼▼▼ ---
    const input = form.querySelector('input[name="comment_text"]'); 
    // --- ▲▲▲ FIN LÍNEA MODIFICADA ▲▲▲ ---
    const publicationId = form.querySelector('input[name="publication_id"]').value;
    const parentCommentId = form.querySelector('input[name="parent_comment_id"]').value;
    
    if (submitButton.disabled || !input.value.trim()) {
        // En este punto el backend devuelve el error, pero el frontend no debería permitir llegar aquí.
        return;
    }

    togglePrimaryButtonSpinner(submitButton, true);
    input.disabled = true;

    // --- ▼▼▼ CÓDIGO MODIFICADO: Uso explícito de FormData para asegurar el valor ▼▼▼ ---
    const formData = new FormData();
    formData.append('action', 'post-comment');
    formData.append('publication_id', publicationId);
    formData.append('parent_comment_id', parentCommentId);
    formData.append('comment_text', input.value.trim());
    // --- ▲▲▲ FIN CÓDIGO MODIFICADO ▲▲▲ ---

    try {
        const result = await callPublicationApi(formData);
        if (result.success && result.newComment) {
            if (parentCommentId) {
                // Es una respuesta (Nivel 2)
                const repliesContainer = form.closest('.comment-item').querySelector('.comment-replies-container');
                renderNewComment(result.newComment, repliesContainer, true); // Renderizar como respuesta
                form.remove(); // Eliminar el formulario de respuesta
            } else {
                // Es un comentario (Nivel 1)
                const commentsContainer = document.getElementById(`comments-for-post-${publicationId}`);
                renderNewComment(result.newComment, commentsContainer, false); // Renderizar como Nivel 1
                input.value = ''; // Limpiar input principal
                input.dispatchEvent(new Event('input')); // Para que el botón de enviar se deshabilite
            }
            
            // Actualizar el contador de comentarios en el botón principal
            const commentButton = document.querySelector(`.post-action-comment[data-post-id="${publicationId}"] .action-text`);
            if (commentButton && result.newCommentCount !== undefined) {
                commentButton.textContent = result.newCommentCount;
            }

        } else {
            // Si el backend devuelve error (e.g., js.publication.errorMaxDepth)
            window.showAlert(getTranslation(result.message || 'js.api.errorServer'), 'error');
        }
    } catch (e) {
        window.showAlert(getTranslation('js.api.errorConnection'), 'error');
    } finally {
        togglePrimaryButtonSpinner(submitButton, false);
        input.disabled = false;
    }
}

/**
 * Inserta el HTML de un nuevo comentario en el contenedor apropiado.
 */
function renderNewComment(comment, container, isReply) {
    // Quitar el placeholder "no hay comentarios" si existe
    const placeholder = container.querySelector('.comment-placeholder');
    if(placeholder) placeholder.remove();

    const defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";
    const avatar = comment.profile_image_url || defaultAvatar;
    
    const commentEl = document.createElement('div');
    commentEl.className = `comment-item ${isReply ? 'is-reply' : ''}`;
    commentEl.dataset.commentId = comment.id;
    
    commentEl.innerHTML = `
        <div class="comment-avatar"><img src="${escapeHTML(avatar)}" alt="${escapeHTML(comment.username)}"></div>
        <div class="comment-content">
            <div class="comment-header">
                <span class="comment-username">${escapeHTML(comment.username)}</span>
                <span class="comment-timestamp">· ${formatTimeAgo(comment.created_at)}</span>
            </div>
            <div class="comment-body">${escapeHTML(comment.comment_text)}</div>
            <div class="comment-actions">
                ${!isReply ? `<button type="button" class="comment-action-btn" data-action="show-reply-form" data-comment-id="${comment.id}">Responder</button>` : ''}
            </div>
            ${!isReply ? `<div class="comment-replies-container"></div><div class="comment-reply-form-container" id="reply-form-for-${comment.id}"></div>` : ''}
        </div>
    `;
    
    container.appendChild(commentEl);
}

/**
 * Muestra el formulario de respuesta (Nivel 2).
 */
function handleShowReplyForm(button) {
    const commentId = button.dataset.commentId;
    const formContainer = document.getElementById(`reply-form-for-${commentId}`);
    if (!formContainer) return;

    // Si el formulario ya existe, solo dale focus
    const existingForm = formContainer.querySelector('form');
    if (existingForm) {
        existingForm.querySelector('input[name="comment_text"]').focus();
        return;
    }
    
    const postContainer = button.closest('.component-card--post');
    const publicationId = postContainer.querySelector('input[name="publication_id"]').value;
    const userAvatar = postContainer.querySelector('.post-comment-avatar img').src;

    formContainer.innerHTML = `
        <form class="post-comment-input-container comment-reply-form" data-action="post-comment">
            <input type="hidden" name="publication_id" value="${publicationId}">
            <input type="hidden" name="parent_comment_id" value="${commentId}">
            <div class="post-comment-avatar">
                <img src="${escapeHTML(userAvatar)}" alt="Tu avatar">
            </div>
            <input type="text" class="post-comment-input" name="comment_text" placeholder="Escribe una respuesta..." required>
            <button type="submit" class="post-comment-submit-btn" disabled>
                <span class="material-symbols-rounded">send</span>
            </button>
        </form>
    `;
    
    formContainer.querySelector('input[name="comment_text"]').focus();
}

/**
 * Formatea una fecha a "hace X tiempo".
 */
function formatTimeAgo(dateString) {
    const date = new Date(dateString.includes('Z') ? dateString : dateString + 'Z');
    const now = new Date();
    const seconds = Math.round((now - date) / 1000);

    if (seconds < 60) return `${seconds}s`;
    const minutes = Math.round(seconds / 60);
    if (minutes < 60) return `${minutes}m`;
    const hours = Math.round(minutes / 60);
    if (hours < 24) return `${hours}h`;
    const days = Math.round(hours / 24);
    return `${days}d`;
}

// --- ▲▲▲ FIN DE NUEVAS FUNCIONES ▲▲▲ ---

export function initCommunityManager() {
    
    document.body.addEventListener('click', async (e) => {
        // --- ▼▼▼ INICIO DE BLOQUE MODIFICADO (AÑADIR LIKES/COMMENTS) ▼▼▼ ---
        const likeButton = e.target.closest('[data-action="like-toggle"]');
        const commentButton = e.target.closest('[data-action="toggle-comments"]');
        const replyButton = e.target.closest('[data-action="show-reply-form"]');

        if (likeButton) {
            e.preventDefault();
            handleLikeToggle(likeButton);
            return;
        }
        if (commentButton) {
            e.preventDefault();
            handleToggleComments(commentButton);
            return;
        }
        if (replyButton) {
            e.preventDefault();
            handleShowReplyForm(replyButton);
            return;
        }
        // --- ▲▲▲ FIN DE BLOQUE MODIFICADO ▲▲▲ ---

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

    document.body.addEventListener('submit', async (e) => {
        const pollForm = e.target.closest('form.poll-form[data-action="submit-poll-vote"]');
        if (pollForm) {
            e.preventDefault();
            const submitButton = pollForm.querySelector('button[type="submit"]');
            const pollContainer = pollForm.closest('.poll-container');
            if (submitButton.disabled) return;
            const formData = new FormData(pollForm);
            formData.append('action', 'vote-poll');
            togglePrimaryButtonSpinner(submitButton, true);
            try {
                const result = await callPublicationApi(formData);
                if (result.success && result.results) {
                    renderPollResults(pollContainer, {
                        results: result.results,
                        totalVotes: result.totalVotes
                    });
                } else {
                    window.showAlert(getTranslation(result.message || 'js.api.errorServer'), 'error');
                    togglePrimaryButtonSpinner(submitButton, false);
                }
            } catch (error) {
                window.showAlert(getTranslation('js.api.errorConnection'), 'error');
                togglePrimaryButtonSpinner(submitButton, false);
            }
            return; 
        }

        const commentForm = e.target.closest('form[data-action="post-comment"]');
        if (commentForm) {
            e.preventDefault();
            await handlePostComment(commentForm);
            return; 
        }
    });
    
    // --- ▼▼▼ INICIO DE BLOQUE MODIFICADO (CORRECCIÓN DEL SELECTOR) ▼▼▼ ---
    document.body.addEventListener('input', (e) => {
        const commentInput = e.target; // Obtener el target directamente
        // Comprobar si el target es un input de comentario
        if (commentInput && commentInput.classList.contains('post-comment-input')) {
            const form = commentInput.closest('form');
            if (!form) return;
            const submitButton = form.querySelector('.post-comment-submit-btn');
            if (submitButton) {
                submitButton.disabled = commentInput.value.trim().length === 0;
            }
        }
    });
    // --- ▲▲▲ FIN DE BLOQUE MODIFICADO ▲▲▲ ---

    const joinCodeInput = document.getElementById('join-code');
    if (joinCodeInput) {
        joinCodeInput.addEventListener('input', hideJoinGroupError);
    }
}
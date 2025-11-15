
import { callSearchApi } from '../services/api-service.js';
import { deactivateAllModules } from '../app/main-controller.js';
import { getTranslation } from '../services/i18n-manager.js';

let searchDebounceTimer;
let currentSearchQuery = '';
const defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";

let pageFilter = 'all'; 

function applyPageFilter() {
    const userCardDiv = document.getElementById('search-results-users');
    const postCardDiv = document.getElementById('search-results-posts');
    const communityCardDiv = document.getElementById('search-results-communities'); 
    const noResultsCard = document.getElementById('search-no-results-card');

    if (!noResultsCard) return; 

    const usersAreEmpty = !userCardDiv || userCardDiv.style.display === 'none';
    const postsAreEmpty = !postCardDiv || postCardDiv.style.display === 'none';
    const communitiesAreEmpty = !communityCardDiv || communityCardDiv.style.display === 'none'; 

    let showUsers = (pageFilter === 'all' || pageFilter === 'people');
    let showPosts = (pageFilter === 'all' || pageFilter === 'posts');
    let showCommunities = (pageFilter === 'all' || pageFilter === 'communities'); 

    let usersWillBeVisible = showUsers && !usersAreEmpty;
    let postsWillBeVisible = showPosts && !postsAreEmpty;
    let communitiesWillBeVisible = showCommunities && !communitiesAreEmpty; 

    if (userCardDiv) {
        userCardDiv.style.display = usersWillBeVisible ? '' : 'none';
    }
    if (postCardDiv) {
        postCardDiv.style.display = postsWillBeVisible ? '' : 'none';
    }
    if (communityCardDiv) { 
        communityCardDiv.style.display = communitiesWillBeVisible ? '' : 'none';
    }

    if (!usersWillBeVisible && !postsWillBeVisible && !communitiesWillBeVisible) { 
        noResultsCard.style.display = '';
    } else {
        noResultsCard.style.display = 'none';
    }
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

function renderResults(data, query) {
    const content = document.getElementById('search-results-content');
    if (!content) return;

    if (!data.users.length && !data.posts.length && !data.communities.length) { 
        const noResultsText = getTranslation('header.search.noResults');
        content.innerHTML = `
            <div class="search-placeholder">
                <span class="material-symbols-rounded">search_off</span>
                <span>${noResultsText} "<strong>${escapeHTML(query)}</strong>"</span>
            </div>`;
        return;
    }

    let html = '';

    if (data.users.length > 0) {
        html += `<div class="menu-header" data-i18n="header.search.people">${getTranslation('header.search.people')}</div>`;
        html += '<div class="menu-list">';
        data.users.forEach(user => {
            html += `
                <a class="menu-link" href="${window.projectBasePath}/profile/${escapeHTML(user.username)}" data-nav-js="true">
                    <div class="menu-link-icon">
                        <div class="comment-avatar" data-role="${escapeHTML(user.role)}" style="width: 32px; height: 32px; margin-right: -10px; flex-shrink: 0;">
                            <img src="${escapeHTML(user.avatar || defaultAvatar)}" alt="${escapeHTML(user.username)}" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                        </div>
                    </div>
                    <div class="menu-link-text">
                        <span>${escapeHTML(user.username)}</span>
                    </div>
                </a>`;
        });
        html += '</div>';
    }

    if (data.posts.length > 0) {
        html += `<div class="menu-header" data-i18n="header.search.posts">${getTranslation('header.search.posts')}</div>`;
        html += '<div class="menu-list">';
        data.posts.forEach(post => {
            
            const title = post.title || `Por ${post.author}`;
            const text = post.text.length > 80 ? post.text.substring(0, 80) + '...' : post.text;
            
            html += `
                <a class="menu-link" href="${window.projectBasePath}/post/${post.id}" data-nav-js="true" style="height: auto; padding: 8px 0;">
                    <div class="menu-link-icon">
                        <span class="material-symbols-rounded">chat_bubble_outline</span>
                    </div>
                    <div class="menu-link-text" style="display: flex; flex-direction: column; line-height: 1.4;">
                        <span style="font-weight: 700; color: #1f2937; font-size: 14px;">${escapeHTML(title)}</span>
                        ${text ? `<span style="font-size: 13px; color: #6b7280; font-weight: 400; white-space: normal;">${escapeHTML(text)}</span>` : ''}
                    </div>
                </a>`;
        });
        html += '</div>';
    }
    
    if (data.communities.length > 0) {
        html += `<div class="menu-header" data-i18n="header.search.communities">${getTranslation('header.search.communities') || 'Comunidades'}</div>`;
        html += '<div class="menu-list">';
        data.communities.forEach(community => {
            html += `
                <a class="menu-link" href="${window.projectBasePath}/c/${escapeHTML(community.uuid)}" data-nav-js="true">
                    <div class="menu-link-icon">
                        <div class="comment-avatar" style="width: 32px; height: 32px; margin-right: -10px; flex-shrink: 0; border-radius: 6px;">
                            <img src="${escapeHTML(community.icon_url || defaultAvatar)}" alt="${escapeHTML(community.name)}" style="width: 100%; height: 100%; border-radius: 6px; object-fit: cover;">
                        </div>
                    </div>
                    <div class="menu-link-text">
                        <span>${escapeHTML(community.name)}</span>
                    </div>
                </a>`;
        });
        html += '</div>';
    }
    
    html += `<div style="height: 1px; background-color: #00000020; margin: 8px;"></div>`;
    html += `
        <a class="menu-link" href="${window.projectBasePath}/search?q=${encodeURIComponent(query)}" data-nav-js="true">
            <div class="menu-link-icon">
                <span class="material-symbols-rounded">search</span>
            </div>
            <div class="menu-link-text">
                <span data-i18n="header.search.allResults">${getTranslation('header.search.allResults')}</span>
            </div>
        </a>`;

    content.innerHTML = html;
}

function showSearchPopover() {
    const popover = document.getElementById('search-results-popover');
    if (popover && popover.classList.contains('disabled')) {
        deactivateAllModules(popover); 
        popover.classList.remove('disabled');
        popover.classList.add('active');
    }
}

async function performSearch() {
    const query = document.getElementById('header-search-input').value.trim();
    const content = document.getElementById('search-results-content');
    currentSearchQuery = query;

    if (!content) return;
    
    if (query.length < 2) {
        content.innerHTML = `<div class="search-placeholder"><span>Busca para encontrar resultados.</span></div>`;
        return;
    }

    content.innerHTML = `<div class="comment-loader" style="padding: 40px 0;"><span class="logout-spinner"></span></div>`;
    
    const formData = new FormData();
    formData.append('action', 'search-popover');
    formData.append('q', query);

    try {
        const result = await callSearchApi(formData);
        if (result.success && currentSearchQuery === query) {
            renderResults(result, query);
        } else if (!result.success) {
            throw new Error(result.message);
        }
    } catch (e) {
        content.innerHTML = `<div class="search-placeholder"><span>Error: ${getTranslation(e.message || 'js.api.errorServer')}</span></div>`;
    }
}

export function initSearchManager() {
    const searchInput = document.getElementById('header-search-input');
    if (!searchInput) return;

    

    
    
    searchInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            const query = searchInput.value.trim();
            if (query.length > 0) {
                deactivateAllModules(); 
                
                const link = document.createElement('a');
                link.href = `${window.projectBasePath}/search?q=${encodeURIComponent(query)}`;
                link.setAttribute('data-nav-js', 'true');
                document.body.appendChild(link);
                link.click();
                link.remove();
                
                searchInput.blur(); 
            }
        }
    });

    document.body.addEventListener('click', (e) => {
        const filterToggleButton = e.target.closest('[data-action="toggleModuleSearchFilter"]');
        const filterSetButton = e.target.closest('[data-action="search-set-filter"]');

        if (filterToggleButton) {
            e.stopPropagation();
            const module = document.querySelector('[data-module="moduleSearchFilter"]');
            if (module) {
                deactivateAllModules(module);
                module.classList.toggle('disabled');
                module.classList.toggle('active');
            }
            return;
        }

        if (filterSetButton) {
            e.preventDefault();
            const newFilter = filterSetButton.dataset.filter;
            if (newFilter === pageFilter) {
                deactivateAllModules();
                return; 
            }
            
            pageFilter = newFilter;

            const menuList = filterSetButton.closest('.menu-list');
            if (menuList) {
                menuList.querySelectorAll('.menu-link').forEach(link => {
                    link.classList.remove('active');
                    const icon = link.querySelector('.menu-link-check-icon');
                    if (icon) icon.innerHTML = '';
                });
                filterSetButton.classList.add('active');
                const iconContainer = filterSetButton.querySelector('.menu-link-check-icon');
                if (iconContainer) iconContainer.innerHTML = '<span class="material-symbols-rounded">check</span>';
            }

            applyPageFilter();
            
            deactivateAllModules();
            return;
        }
    });
    
    if (document.querySelector('[data-section="search-results"]')) {
        applyPageFilter();
    }
}
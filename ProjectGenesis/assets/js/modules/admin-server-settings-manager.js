import { callAdminApi } from '../services/api-service.js';
import { showAlert } from '../services/alert-manager.js';
import { getTranslation } from '../services/i18n-manager.js';
import { sendSocketMessage } from '../services/socket-service.js';

function getCsrfTokenFromPage() {
    const csrfInput = document.querySelector('input[name="csrf_token"]');
    return csrfInput ? csrfInput.value : (window.csrfToken || '');
}

function hideInlineError(cardElement) {
    if (!cardElement) return;
    const nextElement = cardElement.nextElementSibling;
    if (nextElement && nextElement.classList.contains('component-card__error')) {
        nextElement.remove();
    }
}

function showInlineError(cardElement, messageKey, data = null) {
    if (!cardElement) return;
    hideInlineError(cardElement); 
    const errorDiv = document.createElement('div');
    errorDiv.className = 'component-card__error';
    let message = getTranslation(messageKey);
    if (data) {
        Object.keys(data).forEach(key => {
            message = message.replace(`%${key}%`, data[key]);
        });
    }
    errorDiv.textContent = message;
    cardElement.parentNode.insertBefore(errorDiv, cardElement.nextSibling);
}

async function fetchAndUpdateUserCount() {
    const display = document.getElementById('concurrent-users-display');
    const refreshBtn = document.getElementById('refresh-concurrent-users');
    if (!display || !refreshBtn) return;

    const originalBtnText = refreshBtn.innerHTML;
    
    display.textContent = getTranslation('admin.server.concurrentUsersLoading');
    refreshBtn.disabled = true;
    refreshBtn.innerHTML = `<span class="logout-spinner" style="width: 20px; height: 20px; border-width: 2px; margin: 0 auto; border-top-color: inherit;"></span>`;

    const formData = new FormData();
    formData.append('action', 'get-concurrent-users');
    formData.append('csrf_token', getCsrfTokenFromPage());

    try {
        const result = await callAdminApi(formData);
        if (result.success) {
            display.textContent = result.count;
        } else {
            display.textContent = 'Error';
            showAlert(getTranslation(result.message || 'js.admin.errorUserCount'), 'error');
        }
    } catch (error) {
        display.textContent = 'Error';
        showAlert(getTranslation('js.api.errorServer'), 'error');
    } finally {
        refreshBtn.disabled = false;
        refreshBtn.innerHTML = originalBtnText;
    }
}

async function handleSettingUpdate(element, action, newValue) {
    const formData = new FormData();
    formData.append('action', action);
    formData.append('new_value', newValue);
    formData.append('csrf_token', getCsrfTokenFromPage());

    element.classList.add('disabled-interactive');

    try {
        const result = await callAdminApi(formData);

        if (result.success) {
            showAlert(getTranslation(result.message || 'js.admin.settingUpdateSuccess'), 'success');

            
            if (action === 'update-maintenance-mode') {
                const regToggle = document.getElementById('toggle-allow-registration');
                if (regToggle) {
                    if (newValue === '1') {
                        regToggle.checked = false;
                        regToggle.disabled = true;
                        regToggle.closest('.component-toggle-switch')?.classList.add('disabled-interactive');
                    } else {
                        regToggle.disabled = false;
                        regToggle.closest('.component-toggle-switch')?.classList.remove('disabled-interactive');
                    }
                }
            }
            
            if (element.classList.contains('component-stepper')) {
                element.dataset.currentValue = newValue;
            }

        } else {
            showAlert(getTranslation(result.message || 'js.admin.settingUpdateError'), 'error');
            
            if (element.type === 'checkbox') {
                element.checked = !element.checked;
            } else if (element.classList.contains('component-stepper')) {
                const originalValue = element.dataset.currentValue;
                const valueDisplay = element.querySelector('.stepper-value');
                if (valueDisplay) valueDisplay.textContent = originalValue;
                
                const min = parseInt(element.dataset.min, 10);
                const max = parseInt(element.dataset.max, 10);
                const step1 = parseInt(element.dataset.step1 || '1', 10);
                const step10 = parseInt(element.dataset.step10 || '10', 10);
                const currentValInt = parseInt(originalValue, 10);

                element.querySelector('[data-step-action="decrement-10"]').disabled = currentValInt < min + step10;
                element.querySelector('[data-step-action="decrement-1"]').disabled = currentValInt <= min;
                element.querySelector('[data-step-action="increment-1"]').disabled = currentValInt >= max;
                element.querySelector('[data-step-action="increment-10"]').disabled = currentValInt > max - step10;
            }
        }

    } catch (error) {
        showAlert(getTranslation('js.api.errorServer'), 'error');
        if (element.type === 'checkbox') {
            element.checked = !element.checked;
        } else if (element.classList.contains('component-stepper')) {
            const originalValue = element.dataset.currentValue;
            const valueDisplay = element.querySelector('.stepper-value');
            if (valueDisplay) valueDisplay.textContent = originalValue;
        }
    } finally {
        if (element.id === 'toggle-allow-registration') {
            const maintenanceToggle = document.getElementById('toggle-maintenance-mode');
            if (!maintenanceToggle || !maintenanceToggle.checked) {
                element.classList.remove('disabled-interactive');
            }
        } else {
            element.classList.remove('disabled-interactive');
        }
    }
}

export function initAdminServerSettingsManager() {

    document.body.addEventListener('click', async (e) => {
        
        const accordionHeader = e.target.closest('.component-accordion__header[data-action="toggle-accordion"]');
        if (accordionHeader) {
            e.preventDefault();
            
            if (e.target.closest('.component-stepper')) {
                return;
            }
            
            accordionHeader.classList.toggle('active');
            const isActive = accordionHeader.classList.contains('active');

            let nextElement = accordionHeader.nextElementSibling;
            
            while (nextElement) {
                if (nextElement.classList.contains('component-accordion__header')) {
                    break;
                }
                
                if (nextElement.classList.contains('component-card')) {
                    if (isActive) {
                        nextElement.classList.add('active');
                        nextElement.classList.remove('disabled');
                    } else {
                        nextElement.classList.add('disabled');
                        nextElement.classList.remove('active');
                    }
                }
                
                nextElement = nextElement.nextElementSibling;
            }
            return; 
        }

        const button = e.target.closest('button[data-step-action], button[data-action]');
        if (!button) return;
        
        const section = button.closest('.section-content[data-section="admin-server-settings"]');
        if (!section) return;

        const action = button.dataset.action;
        const stepAction = button.dataset.stepAction;

        if (action === 'admin-refresh-user-count') {
            const display = document.getElementById('concurrent-users-display');
            if (display) {
                display.textContent = getTranslation('admin.server.concurrentUsersLoading');
                display.setAttribute('data-i18n', 'admin.server.concurrentUsersLoading');
            }
            sendSocketMessage({ type: 'admin_get_count' });
            return;
        }
        
        if (stepAction) {
            const wrapper = button.closest('.component-stepper');
            if (!wrapper || wrapper.classList.contains('disabled-interactive')) return;

            const stepperAction = wrapper.dataset.action;
            if (!stepperAction) return;

            const valueDisplay = wrapper.querySelector('.stepper-value');
            const min = parseInt(wrapper.dataset.min, 10);
            const max = parseInt(wrapper.dataset.max, 10);
            
            const step1 = parseInt(wrapper.dataset.step1 || '1', 10);
            const step10 = parseInt(wrapper.dataset.step10 || '10', 10);

            let currentValue = parseInt(wrapper.dataset.currentValue, 10);
            let newValue = currentValue;
            let stepAmount = 0;

            switch (stepAction) {
                case 'increment-1':
                    stepAmount = step1;
                    break;
                case 'increment-10':
                    stepAmount = step10;
                    break;
                case 'decrement-1':
                    stepAmount = -step1;
                    break;
                case 'decrement-10':
                    stepAmount = -step10;
                    break;
            }

            newValue = currentValue + stepAmount;
            
            if (!isNaN(min) && newValue < min) newValue = min;
            if (!isNaN(max) && newValue > max) newValue = max;
            
            if (newValue === currentValue) return;

            if (valueDisplay) valueDisplay.textContent = newValue;
            
            wrapper.querySelector('[data-step-action="decrement-10"]').disabled = newValue < min + step10;
            wrapper.querySelector('[data-step-action="decrement-1"]').disabled = newValue <= min;
            wrapper.querySelector('[data-step-action="increment-1"]').disabled = newValue >= max;
            wrapper.querySelector('[data-step-action="increment-10"]').disabled = newValue > max - step10;
            
            await handleSettingUpdate(wrapper, stepperAction, newValue.toString());
            return;
        }

        if (action) {
            const domainCard = button.closest('#admin-domain-card');
            if (!domainCard) return;

            const viewState = domainCard.querySelector('#domain-view-state');
            const addState = domainCard.querySelector('#domain-add-state');
            hideInlineError(domainCard); 

            if (action === 'admin-domain-show-add') {
                if (viewState) viewState.style.display = 'none';
                if (addState) addState.style.display = 'block';
                domainCard.querySelector('#setting-new-domain-input')?.focus();
            }

            else if (action === 'admin-domain-cancel-add') {
                if (viewState) viewState.style.display = 'block';
                if (addState) addState.style.display = 'none';
                const input = domainCard.querySelector('#setting-new-domain-input');
                if (input) input.value = '';
            }

            else if (action === 'admin-domain-save-add') {
                const input = domainCard.querySelector('#setting-new-domain-input');
                const newDomain = input ? input.value.trim().toLowerCase() : '';

                if (!newDomain) {
                    showInlineError(domainCard, 'js.admin.domainEmpty');
                    return;
                }
                if (!newDomain.match(/^[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,}$/i)) {
                    showInlineError(domainCard, 'js.admin.domainInvalid');
                    return;
                }

                button.classList.add('disabled-interactive'); 
                
                const formData = new FormData();
                formData.append('action', 'admin-add-domain');
                formData.append('new_domain', newDomain);
                formData.append('csrf_token', getCsrfTokenFromPage());

                const result = await callAdminApi(formData);
                if (result.success) {
                    showAlert(getTranslation('js.admin.domainAdded'), 'success');
                    const list = domainCard.querySelector('.domain-card-list');
                    if (list) {
                        const emptyMsg = list.querySelector('p.component-card__description');
                        if (emptyMsg) emptyMsg.remove();

                        const newCardItem = document.createElement('div');
                        newCardItem.className = 'domain-card-item';
                        newCardItem.dataset.domain = result.domain;
                        newCardItem.innerHTML = `
                            <span class="material-symbols-rounded">language</span>
                            <span class="domain-card-text">${result.domain}</span>
                            <button type="button" class="domain-card-delete" data-action="admin-domain-delete" data-domain="${result.domain}" data-tooltip="admin.server.deleteDomainTooltip">
                                <span class="material-symbols-rounded">delete</span>
                            </button>
                        `;
                        list.appendChild(newCardItem);
                    }
                    if (viewState) viewState.style.display = 'block';
                    if (addState) addState.style.display = 'none';
                    if (input) input.value = '';

                } else {
                    showInlineError(domainCard, result.message || 'js.admin.domainAddError');
                }
                button.classList.remove('disabled-interactive');
            }

            else if (action === 'admin-domain-delete') {
                const domainToRemove = button.dataset.domain;
                if (!domainToRemove) return;

                if (!confirm(`¿Estás seguro de que deseas eliminar el dominio "${domainToRemove}"?`)) {
                    return;
                }

                button.classList.add('disabled-interactive');

                const formData = new FormData();
                formData.append('action', 'admin-remove-domain');
                formData.append('domain_to_remove', domainToRemove);
                formData.append('csrf_token', getCsrfTokenFromPage());

                const result = await callAdminApi(formData);
                if (result.success) {
                    showAlert(getTranslation('js.admin.domainRemoved'), 'success');
                    button.closest('.domain-card-item')?.remove();
                } else {
                    showAlert(getTranslation(result.message || 'js.admin.domainRemoveError'), 'error');
                }
            }
        }
    });

    document.body.addEventListener('change', async (e) => {
        const input = e.target;
        
        if (input.closest('.component-stepper') || input.closest('#admin-domain-card')) return;

        const action = input.dataset.action;
        if (!action) return;

        const section = input.closest('.section-content[data-section="admin-server-settings"]');
        if (!section) return;

        let newValue = '';

        if (input.type === 'checkbox') {
            newValue = input.checked ? '1' : '0';
        } else {
            return;
        }
        
        if (action === 'update-maintenance-mode' || action === 'update-registration-mode' || action === 'update-messaging-service') {
            await handleSettingUpdate(input, action, newValue);
        }
    });
    
    document.body.addEventListener('blur', async (e) => {
        const input = e.target;
        
        if (input.id !== 'setting-allowed-email-domains') return;
        
        const action = input.dataset.action;
        if (!action) return;

        const section = input.closest('.section-content[data-section="admin-server-settings"]');
        if (!section) return;

        let newValue = input.value;
        
        await handleSettingUpdate(input, action, newValue);

    }, true); 

    if (document.querySelector('.section-content[data-section="admin-server-settings"]')) {
    }
}
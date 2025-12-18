class ConsentManager {
    constructor(root) {
        this.cookieName = 'consent_manager';
        this.cookieExpiry = 180;
        this.cookiePath = '/';
        this.cookieDomain = null;
        this.cookieSecure = false;
        this.cookieSameSite = 'lax';
        this.consentData = { services: {}, revision_date: null };
        this.categories = [];
        this.services = [];
        this.categoryIndex = {};
        this.currentRevisionDate = null;
        this.pendingReload = false;
        this.debug = false;

        this.integrations = [];
        this.integrationsPayload = {};

        this.init();
    }

    registerIntegration(integration) {
        if (integration && 
            typeof integration.loadConfig === 'function' &&
            typeof integration.getName === 'function' &&
            typeof integration.isEnabled === 'function' &&
            typeof integration.applyConsentUpdate === 'function') {
            integration.loadConfig(this.integrationsPayload);
            if (integration.isEnabled()) {
                this.integrations.push(integration);
                if (this.debug) {
                    console.log(`[Consent Manager] Registered integration: ${integration.getName()}`);
                }
                integration.applyConsentUpdate();
            }
        }
    }

    init() {
        this.loadPayload();
        this.loadConsentFromCookie();
        this.bindEvents();
        this.syncUIFromConsent();
        this.updateDialogVisibility();
        this.activateDeferredScripts();
    }

    loadPayload() {
        const payloadElement = document.querySelector('[data-consent-manager="payload"]');
        if (payloadElement) {
            try {
                const payload = JSON.parse(payloadElement.textContent);
                this.categories = Array.isArray(payload.categories) ? payload.categories : [];
                this.buildCategoryIndex();
                this.services = Array.isArray(payload.services) ? payload.services : [];
                this.currentRevisionDate = payload.consent_revision_date || null;
                
                this.cookieName = payload.cookie_name || 'consent_manager';
                this.cookieExpiry = payload.cookie_expiry || 180;
                this.cookiePath = payload.cookie_path || '/';
                this.cookieDomain = payload.cookie_domain || null;
                this.cookieSecure = payload.cookie_secure === true;
                this.cookieSameSite = payload.cookie_same_site || 'lax';
                this.debug = payload.debug === true;

                if (this.debug) {
                    console.log('[Consent Manager] Debug mode enabled');
                    console.log('[Consent Manager] Current revision date:', this.currentRevisionDate);
                    console.log('[Consent Manager] Cookie name:', this.cookieName);
                    console.log('[Consent Manager] Cookie expiry (days):', this.cookieExpiry);
                    console.log('[Consent Manager] Cookie secure:', this.cookieSecure);
                    console.log('[Consent Manager] Cookie same site:', this.cookieSameSite);
                }

                const integrationsPayload = payload.integrations;
                this.integrationsPayload = integrationsPayload && typeof integrationsPayload === 'object' && !Array.isArray(integrationsPayload) ? integrationsPayload : {};
            } catch (e) {
                console.warn('Invalid consent manager payload:', e);
            }
        }
    }

    buildCategoryIndex() {
        this.categoryIndex = {};
        this.categories.forEach(category => {
            if (category && typeof category.handle === 'string' && category.handle.length) {
                this.categoryIndex[category.handle] = category;
            }
        });
    }

    ensureServicePreferences() {
        if (!this.consentData.services || typeof this.consentData.services !== 'object') {
            this.consentData.services = {};
        }

        this.services.forEach(service => {
            if (!service || typeof service.handle !== 'string' || !service.handle.length) {
                return;
            }

            if (!(service.handle in this.consentData.services)) {
                let defaultValue = false;
                if (service.categories && service.categories.length > 0) {
                    defaultValue = service.categories.some(catId => {
                        const category = this.categoryIndex[catId];
                        return category && category.required;
                    });
                }
                this.consentData.services[service.handle] = defaultValue;
            }
        });
    }

    applyAllIntegrationUpdates() {
        if (this.debug) {
            console.log(`[Consent Manager] Applying updates to ${this.integrations.length} integration(s)`);
        }
        
        this.integrations.forEach(integration => {
            if (integration.isEnabled()) {
                integration.applyConsentUpdate();
            }
        });
    }

    loadConsentFromCookie() {
        const cookieValue = this.getCookie(this.cookieName);
        if (cookieValue) {
            try {
                const parsed = JSON.parse(cookieValue);
                this.consentData = {
                    services: parsed.services || {},
                    revision_date: parsed.revision_date || null
                };
            } catch (e) {
                console.warn('Invalid consent cookie:', e);
            }
        } else {
            this.consentData.services = this.consentData.services || {};
        }

        this.ensureServicePreferences();
    }

    saveConsentToCookie() {
        this.consentData.revision_date = this.currentRevisionDate;
        
        const value = JSON.stringify(this.consentData);
        const d = new Date();
        d.setTime(d.getTime() + (this.cookieExpiry * 24 * 60 * 60 * 1000));
        const expires = 'expires=' + d.toUTCString();
        
        let cookieString = `${this.cookieName}=${value};${expires};path=${this.cookiePath}`;
        
        if (this.cookieDomain) {
            cookieString += `;domain=${this.cookieDomain}`;
        }
        
        if (this.cookieSecure) {
            cookieString += ';secure';
        }
        
        cookieString += `;SameSite=${this.cookieSameSite.charAt(0).toUpperCase() + this.cookieSameSite.slice(1)}`;
        
        document.cookie = cookieString;
        
        if (this.debug) {
            console.log('[Consent Manager] Saved consent with revision date:', this.currentRevisionDate);
            console.log('[Consent Manager] Cookie attributes:', {
                path: this.cookiePath,
                domain: this.cookieDomain,
                secure: this.cookieSecure,
                sameSite: this.cookieSameSite
            });
        }
    }

    getCookie(name) {
        const cname = name + '=';
        const ca = (document.cookie || '').split(';');
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) === ' ') {
                c = c.substring(1);
            }
            if (c.indexOf(cname) === 0) {
                return c.substring(cname.length, c.length);
            }
        }
        return null;
    }

    bindEvents() {
        const banner = document.querySelector('[data-consent-manager="banner"]');
        const preferences = document.querySelector('[data-consent-manager="preferences"]');

        banner?.querySelectorAll('[data-consent-manager-action]')?.forEach(button => {
            button.addEventListener('click', () => {
                const actionType = button.getAttribute('data-consent-manager-action');
                if (actionType === 'customize') {
                    this.openPreferencesDialog();
                    return;
                }
                const hasRevoked = this.handleAction(actionType);
                if (['accept-all', 'reject-all'].includes(actionType)) this.closeDialogs();
                if (hasRevoked) this.requestPageReload();
            });
        });

        preferences?.querySelectorAll('[data-consent-manager-action]')?.forEach(button => {
            button.addEventListener('click', () => {
                const actionType = button.getAttribute('data-consent-manager-action');
                const hasRevoked = this.handleAction(actionType);
                if (['save', 'accept-all', 'reject-all'].includes(actionType)) this.closeDialogs();
                if (hasRevoked) this.requestPageReload();
            });
        });

        const accordionButtons = preferences?.querySelectorAll('[data-consent-accordion-toggle]') || [];
        accordionButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const panel = btn.closest('[data-consent-category]')?.querySelector('[data-consent-accordion-panel]');
                if (!panel) return;
                const isHidden = panel.hasAttribute('hidden');
                if (isHidden) {
                    panel.removeAttribute('hidden');
                    btn.setAttribute('data-open', 'true');
                } else {
                    panel.setAttribute('hidden', '');
                    btn.removeAttribute('data-open');
                }
                const label = btn.querySelector('[data-accordion-label]');
                if (label) {
                    const showText = btn.getAttribute('data-label-show') || 'Show services';
                    const hideText = btn.getAttribute('data-label-hide') || 'Hide services';
                    label.textContent = isHidden ? hideText : showText;
                }
            });
        });

        const serviceToggles = preferences?.querySelectorAll('[data-consent-manager-service-toggle]') || [];
        serviceToggles.forEach(toggle => {
            toggle.addEventListener('change', () => {
                const serviceHandle = toggle.getAttribute('data-consent-manager-service-toggle');
                const checked = toggle.checked;
                this.syncServiceToggles(serviceHandle, checked, toggle);
            });
        });

        const categoryToggles = preferences?.querySelectorAll('[data-consent-manager-toggle]') || [];
        categoryToggles.forEach(toggle => {
            toggle.addEventListener('click', (event) => {
                const isRequired = toggle.hasAttribute('data-required') && toggle.getAttribute('data-required') === 'true';
                if (isRequired) return;
                if (!toggle.indeterminate) return;
                const catId = toggle.getAttribute('data-consent-manager-toggle');
                const container = toggle.closest(`[data-consent-category="${catId}"]`);
                if (!container) return;
                event.preventDefault();
                toggle.dataset.skipNextCategoryChange = 'true';
                this.applyCategoryToggle(container, toggle, true);
                toggle.dispatchEvent(new Event('change', { bubbles: true }));
            });

            toggle.addEventListener('change', () => {
                const isRequired = toggle.hasAttribute('data-required') && toggle.getAttribute('data-required') === 'true';
                if (isRequired) return;
                if (toggle.dataset.skipNextCategoryChange === 'true') {
                    delete toggle.dataset.skipNextCategoryChange;
                    return;
                }
                const catId = toggle.getAttribute('data-consent-manager-toggle');
                const container = toggle.closest(`[data-consent-category="${catId}"]`);
                if (!container) return;
                this.applyCategoryToggle(container, toggle, toggle.checked);
            });
        });

        preferences?.addEventListener('cancel', (e) => {
            e.preventDefault();
            this.closePreferencesDialog();
        });

        preferences?.addEventListener('click', (e) => {
            if (e.target !== preferences) return;

            // check if clicking outside the dialog (backdrop)
            const rect = preferences.getBoundingClientRect();
            const isInDialog = (
                e.clientX >= rect.left &&
                e.clientX <= rect.right &&
                e.clientY >= rect.top &&
                e.clientY <= rect.bottom
            );
            if (!isInDialog) {
                this.closePreferencesDialog();
            }
        });


        this.bindGiveConsentButtons();
    }

    bindGiveConsentButtons() {
        document.querySelectorAll('[data-give-consent]').forEach(button => {
            button.addEventListener('click', () => {
                const serviceHandle = button.getAttribute('data-give-consent');
                if (serviceHandle) {
                    this.grantConsentToService(serviceHandle);
                }
            });
        });
    }

    openBannerDialog() {
        const banner = document.querySelector('[data-consent-manager="banner"]');
        if (banner && !banner.open) {
            banner.show();
        }
    }

    openPreferencesDialog() {
        const banner = document.querySelector('[data-consent-manager="banner"]');
        const preferences = document.querySelector('[data-consent-manager="preferences"]');
        
        banner?.close();
        preferences?.showModal();
        preferences?.focus();
        this.syncUIFromConsent();
    }

    closePreferencesDialog() {
        const preferences = document.querySelector('[data-consent-manager="preferences"]');
        preferences?.close();
    }

    closeDialogs() {
        const banner = document.querySelector('[data-consent-manager="banner"]');
        const preferences = document.querySelector('[data-consent-manager="preferences"]');
        
        banner?.close();
        preferences?.close();
    }

    handleAction(actionType) {
        const hasCookie = this.getCookie(this.cookieName) !== null;
        const previousConsent = this.getAllConsent();
        switch (actionType) {
            case 'accept-all':
                this.services.forEach(service => {
                    if (!service || !service.handle) {
                        return;
                    }
                    this.consentData.services[service.handle] = true;
                });
                break;
            case 'reject-all':
                this.consentData = { services: {}, revision_date: null };
                this.services.forEach(service => {
                    if (!service || !service.handle) {
                        return;
                    }
                    this.consentData.services[service.handle] = false;
                });
                break;
            case 'save':
                // Update consent data based on current service toggle states (preferences dialog)
                const serviceToggles = document.querySelectorAll('[data-consent-manager="preferences"] [data-consent-manager-service-toggle]');
                serviceToggles?.forEach(toggle => {
                    const serviceHandle = toggle.getAttribute('data-consent-manager-service-toggle');
                    if (!serviceHandle) {
                        return;
                    }
                    const isChecked = toggle.checked;
                    this.consentData.services[serviceHandle] = isChecked;
                });
                break;
            default:
                return false;
        }

        this.ensureServicePreferences();

        this.saveConsentToCookie();
        this.syncUIFromConsent();
        this.updateDialogVisibility();
        this.dispatchUpdateEvent();
        this.activateDeferredScripts();
        this.applyAllIntegrationUpdates();
        
        const hasRevoked = hasCookie ? this.detectRevocations(previousConsent, this.consentData) : false;
        this.pendingReload = hasRevoked;
        return hasRevoked;
    }

    syncServiceToggles(serviceHandle, checked, originToggle = null) {
        if (!serviceHandle) return;
        const others = document.querySelectorAll(`[data-consent-manager-service-toggle="${serviceHandle}"]`) || [];
        others.forEach(el => {
            if (originToggle && el === originToggle) return;
            if (el instanceof HTMLInputElement) {
                if (!el.disabled) el.checked = checked;
            }
        });
        this.recalculateCategoriesForService(serviceHandle);
    }

    applyCategoryToggle(container, catToggle, targetState) {
        const isRequired = catToggle.hasAttribute('data-required') && catToggle.getAttribute('data-required') === 'true';
        if (isRequired) return;

        catToggle.indeterminate = false;
        catToggle.checked = targetState;

        const svcToggles = container.querySelectorAll('[data-consent-manager-service-toggle]');
        svcToggles.forEach(svc => {
            if (svc instanceof HTMLInputElement && !svc.disabled) {
                svc.checked = targetState;
                const serviceHandle = svc.getAttribute('data-consent-manager-service-toggle');
                this.syncServiceToggles(serviceHandle, svc.checked, svc);
            }
        });

        this.recalculateCategoryState(container);
    }

    syncUIFromConsent() {
        const svcToggles = document.querySelectorAll('[data-consent-manager-service-toggle]');
        svcToggles.forEach(toggle => {
            const handle = toggle.getAttribute('data-consent-manager-service-toggle');
            
            if (handle in (this.consentData.services || {})) {
                toggle.checked = !!this.consentData.services[handle];
            } else {
                const service = this.services.find(s => s.handle === handle);
                if (service && service.categories && service.categories.length > 0) {
                    const hasDefaultEnabledCategory = service.categories.some(catId => {
                        const category = this.categoryIndex[catId];
                        return category && category.default_enabled;
                    });
                    toggle.checked = hasDefaultEnabledCategory;
                }
            }
        });

        this.recalculateAllCategoryStates();
    }

    recalculateAllCategoryStates() {
        const containers = document.querySelectorAll('[data-consent-category]') || [];
        containers.forEach(container => this.recalculateCategoryState(container));
    }

    recalculateCategoriesForService(serviceHandle) {
        const toggles = document.querySelectorAll(`[data-consent-manager-service-toggle="${serviceHandle}"]`) || [];
        const containers = new Set();
        toggles.forEach(t => {
            const container = t.closest('[data-consent-category]');
            if (container) containers.add(container);
        });
        containers.forEach(container => this.recalculateCategoryState(container));
    }

    recalculateCategoryState(container) {
        const catToggle = container.querySelector('[data-consent-manager-toggle]');
        if (!catToggle) return;
        const isRequired = catToggle.hasAttribute('data-required') && catToggle.getAttribute('data-required') === 'true';
        const services = Array.from(container.querySelectorAll('[data-consent-manager-service-toggle]')).filter(s => s instanceof HTMLInputElement);

        if (isRequired) {
            catToggle.checked = true;
            catToggle.indeterminate = false;
            return;
        }

        if (services.length === 0) {
            catToggle.indeterminate = false;
            catToggle.checked = false;
            return;
        }

        const checkedCount = services.reduce((total, svc) => total + (svc.checked ? 1 : 0), 0);
        const allChecked = checkedCount === services.length;
        const noneChecked = checkedCount === 0;

        catToggle.indeterminate = false;

        if (allChecked) {
            catToggle.checked = true;
        } else if (noneChecked) {
            catToggle.checked = false;
        } else {
            catToggle.checked = false;
            catToggle.indeterminate = true;
        }
    }

    updateDialogVisibility() {
        const hasCookie = this.getCookie(this.cookieName) !== null;
        const needsReconsent = this.needsReconsent();
        
        const banner = document.querySelector('[data-consent-manager="banner"]');
        const preferences = document.querySelector('[data-consent-manager="preferences"]');
        
        if (hasCookie && !needsReconsent) {
            banner?.close();
            preferences?.close();
        } else {
            this.openBannerDialog();
            preferences?.close();
        }
        
        if (this.debug && needsReconsent) {
            console.log('[Consent Manager] Re-consent required - revision date changed');
        }
    }

    needsReconsent() {
        if (!this.currentRevisionDate) {
            return false;
        }
        
        if (!this.consentData.revision_date) {
            return true;
        }
        
        return this.consentData.revision_date !== this.currentRevisionDate;
    }

    dispatchUpdateEvent() {
        const detail = { consentData: this.consentData };

        const enabledIntegrations = this.integrations.filter(i => i.isEnabled());
        if (enabledIntegrations.length > 0) {
            detail.integrations = {};
            enabledIntegrations.forEach(integration => {
                const state = integration.getState();
                if (state) {
                    detail.integrations[integration.getName()] = state;
                }
            });
        }

        const event = new CustomEvent('consent-manager:preferences-updated', {
            detail
        });
        document.dispatchEvent(event);
    }

    activateDeferredScripts() {
        const hasCookie = this.getCookie(this.cookieName) !== null;
        if (!hasCookie) {
            return;
        }

        this.services.forEach(service => {
            if (service.handle && this.getServiceConsent(service.handle)) {
                this.activateTemplatesForService(service.handle);
            }
        });
    }

    /**
     * Activate all templates for a specific service
     * @param {string} serviceHandle - The service handle
     */
    activateTemplatesForService(serviceHandle) {
        if (!serviceHandle) return;

        const templates = document.querySelectorAll(`template[data-consent-manager-script][data-service-handle="${serviceHandle}"]`);
        
        templates.forEach(template => {
            const placeholder = document.querySelector(`[data-consent-placeholder="${serviceHandle}"]`);
            placeholder?.remove();

            template.before(template.content);
            template.remove();
        });
    }

    detectRevocations(previousConsent, currentConsent) {
        const prevServices = previousConsent?.services || {};
        const currServices = currentConsent?.services || {};

        const serviceRevoked = Object.keys(prevServices).some(key => prevServices[key] === true && currServices[key] !== true);

        return serviceRevoked;
    }

    requestPageReload() {
        if (!this.pendingReload) return;
        this.pendingReload = false;
        window.setTimeout(() => window.location.reload(), 100);
    }

    // =================================================================
    // Public API Methods
    // =================================================================

    /**
     * Grant consent to a specific service and activate its templates
     * @param {string} serviceHandle - The service handle
     */
    grantConsentToService(serviceHandle) {
        if (!serviceHandle) return;

        if (!this.consentData.services) {
            this.consentData.services = {};
        }
        this.consentData.services[serviceHandle] = true;
        this.saveConsentToCookie();
        this.activateTemplatesForService(serviceHandle);
        this.dispatchUpdateEvent();
        this.applyAllIntegrationUpdates();

        if (this.debug) {
            console.log(`[Consent Manager] Granted consent to service: ${serviceHandle}`);
        }
    }

    /**
     * Check if user has consented to a specific service
     * @param {string} serviceHandle - The service handle to check
     * @returns {boolean} True if consent is granted for the service
     */
    getServiceConsent(serviceHandle) {
        if (!this.consentData || !this.consentData.services) return false;
        return this.consentData.services[serviceHandle] === true;
    }

    /**
     * Show the consent banner dialog
     */
    showBanner() {
        this.openBannerDialog();
    }

    /**
     * Show the preferences dialog
     */
    showPreferences() {
        this.openPreferencesDialog();
    }

    /**
     * Get a copy of all consent data
     * @returns {object} Copy of the consent preferences object
     */
    getAllConsent() {
        return JSON.parse(JSON.stringify(this.consentData));
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.consentManager = new ConsentManager();
    });
} else {
    window.consentManager = new ConsentManager();
}

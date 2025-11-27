/**
 * Google Tag Manager - Consent Mode v2 Integration
 */
export class GoogleTagIntegration {
    constructor(consentManager) {
        this.consentManager = consentManager;
        this.config = null;
        this.lastStateSerialized = null;
    }

    getName() {
        return 'google_tag';
    }

    loadConfig(integrationsPayload) {
        this.config = integrationsPayload.google_tag || null;
    }

    isEnabled() {
        return !!(
            this.config &&
            this.config.enabled &&
            this.config.measurement_id
        );
    }

    computeState() {
        if (!this.isEnabled()) {
            return null;
        }

        const baseState = {
            ad_storage: 'denied',
            ad_user_data: 'denied',
            ad_personalization: 'denied',
            analytics_storage: 'denied',
            functionality_storage: 'denied',
            personalization_storage: 'denied',
            security_storage: 'granted'
        };

        const serviceMapping = this.config.service_mapping || {};

        Object.entries(serviceMapping).forEach(([serviceHandle, storages]) => {
            if (!Array.isArray(storages)) {
                return;
            }

            const granted = this.consentManager.getServiceConsent(serviceHandle);
            const value = granted ? 'granted' : 'denied';

            storages.forEach(storageKey => {
                if (storageKey) {
                    baseState[storageKey] = value;
                }
            });
        });

        return baseState;
    }

    applyConsentUpdate() {
        if (!this.isEnabled()) {
            return;
        }

        window.dataLayer = window.dataLayer || [];

        if (typeof window.gtag !== 'function') {
            window.gtag = function gtag() {
                window.dataLayer.push(arguments);
            };
        }

        const state = this.computeState();

        if (!state) {
            return;
        }

        const serialized = JSON.stringify(state);

        if (serialized === this.lastStateSerialized) {
            return;
        }

        this.lastStateSerialized = serialized;

        window.gtag('consent', 'update', state);
    }

    getState() {
        if (!this.isEnabled()) {
            return null;
        }

        return {
            measurement_id: this.config.measurement_id,
            state: this.computeState()
        };
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if (window.consentManager) {
        window.consentManager.registerIntegration(new GoogleTagIntegration(window.consentManager));
    }
});

/**
 * Meta Pixel (Facebook) Integration
 */
export class MetaPixelIntegration {
    constructor(consentManager) {
        this.consentManager = consentManager;
        this.config = null;
        this.lastState = null;
    }

    getName() {
        return 'meta_pixel';
    }

    loadConfig(integrationsPayload) {
        this.config = integrationsPayload.meta_pixel || null;
    }

    isEnabled() {
        return !!(
            this.config &&
            this.config.enabled &&
            this.config.pixel_id &&
            typeof window.fbq === 'function'
        );
    }

    shouldGrantConsent() {
        if (!this.isEnabled()) {
            return false;
        }

        return this.consentManager.getServiceConsent('meta_pixel');
    }

    applyConsentUpdate() {
        if (!this.isEnabled()) {
            return;
        }

        const shouldGrant = this.shouldGrantConsent();

        if (this.lastState === shouldGrant) {
            return;
        }

        this.lastState = shouldGrant;

        if (shouldGrant) {
            window.fbq('consent', 'grant');
        } else {
            window.fbq('consent', 'revoke');
        }
    }

    getState() {
        if (!this.isEnabled()) {
            return null;
        }

        return {
            pixel_id: this.config.pixel_id,
            state: this.shouldGrantConsent() ? 'granted' : 'revoked'
        };
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if (window.consentManager) {
        window.consentManager.registerIntegration(new MetaPixelIntegration(window.consentManager));
    }
});

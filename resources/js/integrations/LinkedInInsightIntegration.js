/**
 * LinkedIn Insight Tag Integration
 */
export class LinkedInInsightIntegration {
    constructor(consentManager) {
        this.consentManager = consentManager;
        this.config = null;
        this.lastState = null;
    }

    getName() {
        return 'linkedin_insight';
    }

    loadConfig(integrationsPayload) {
        this.config = integrationsPayload.linkedin_insight || null;
    }

    isEnabled() {
        return !!(
            this.config &&
            this.config.enabled &&
            this.config.partner_id &&
            typeof window.lintrk === 'function'
        );
    }

    shouldGrantConsent() {
        if (!this.isEnabled()) {
            return false;
        }

        return this.consentManager.getServiceConsent('linkedin_insight');
    }

    applyConsentUpdate() {
        if (!this.isEnabled()) {
            return;
        }

        const shouldGrant = this.shouldGrantConsent();

        // Don't update if state hasn't changed
        if (this.lastState === shouldGrant) {
            return;
        }

        this.lastState = shouldGrant;

        // LinkedIn doesn't have explicit consent API like Meta
        // The script loads with the page, we just track state for events
        // If consent is granted, conversions can be tracked via window.lintrk('track', { conversion_id: ... })
    }

    getState() {
        if (!this.isEnabled()) {
            return null;
        }

        return {
            partner_id: this.config.partner_id,
            state: this.shouldGrantConsent() ? 'granted' : 'revoked'
        };
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if (window.consentManager) {
        window.consentManager.registerIntegration(new LinkedInInsightIntegration(window.consentManager));
    }
});

import { test, expect } from '@playwright/test';
import { readFileSync } from 'node:fs';

const manifest = JSON.parse(readFileSync(new URL('../../dist/frontend/manifest.json', import.meta.url)));
const assets = Object.fromEntries(['resources/js/consent-manager.js', 'resources/js/integrations/GoogleTagIntegration.js'].map(entry => [
    `/${manifest[entry].file}`,
    readFileSync(new URL(`../../dist/frontend/${manifest[entry].file}`, import.meta.url), 'utf8'),
]));
const payload = {
    categories: [{ handle: 'necessary', required: true }, { handle: 'analytics', required: false }],
    services: [
        { handle: 'required', categories: ['necessary'] },
        { handle: 'mixed', categories: ['necessary', 'analytics'] },
        { handle: 'optional', categories: ['analytics'] },
    ],
    integrations: { google_tag: { enabled: true, measurement_id: 'G-TEST', service_mapping: { required: ['security_storage'], optional: ['analytics_storage'] } } },
    consent_revision_date: '2026-01-01',
};
const fixture = `
    <script type="application/json" data-consent-manager="payload">${JSON.stringify(payload)}</script>
    <dialog data-consent-manager="banner"><button data-consent-manager-action="reject-all">Reject All</button></dialog>
    <dialog data-consent-manager="preferences">
        <div data-consent-category="necessary">
            <input type="checkbox" data-consent-manager-toggle="necessary" data-required="true" disabled checked>
            <input type="checkbox" data-consent-manager-service-toggle="required" disabled checked>
            <input type="checkbox" data-consent-manager-service-toggle="mixed" disabled checked>
        </div>
        <div data-consent-category="analytics">
            <input type="checkbox" data-consent-manager-toggle="analytics">
            <input type="checkbox" data-consent-manager-service-toggle="mixed">
            <input type="checkbox" data-consent-manager-service-toggle="optional">
        </div>
        <button data-consent-manager-action="save">Save</button>
    </dialog>
    ${Object.keys(assets).map(path => `<script type="module" src="${path}"></script>`).join('')}
`;

test.beforeEach(async ({ page }) => {
    await page.route('https://consent.test/**', route => {
        const path = new URL(route.request().url()).pathname;
        return route.fulfill({ contentType: assets[path] ? 'application/javascript' : 'text/html', body: assets[path] || fixture });
    });
});

async function loadManager(page) {
    await page.goto('https://consent.test/');
    await page.waitForFunction(() => window.consentManager?.integrations.length === 1);
}

async function expectConsent(page, optional = false) {
    const state = await page.evaluate(() => ({ services: window.consentManager.getAllConsent().services, google: window.consentManager.integrations[0].getState().state }));
    expect(state.services).toEqual({ required: true, mixed: true, optional });
    expect(state.google.security_storage).toBe('granted');
    expect(state.google.analytics_storage).toBe(optional ? 'granted' : 'denied');
    for (const input of await page.locator('[data-consent-manager-service-toggle="required"], [data-consent-manager-service-toggle="mixed"]').all()) {
        await expect(input).toBeChecked();
        await expect(input).toBeDisabled();
    }
}

test('Reject All preserves required services in memory, the cookie, the UI and Google consent', async ({ page }) => {
    await loadManager(page);
    await page.getByRole('button', { name: 'Reject All' }).click();
    await expectConsent(page);
    const cookie = (await page.context().cookies()).find(cookie => cookie.name === 'consent_manager');
    expect(JSON.parse(cookie.value).services).toEqual({ required: true, mixed: true, optional: false });
    await loadManager(page);
    await expectConsent(page);
});

test('normalizes required services from an old cookie without granting optional consent', async ({ page }) => {
    await page.context().addCookies([{ name: 'consent_manager', value: JSON.stringify({ services: { required: false, mixed: false, optional: false }, revision_date: payload.consent_revision_date }), domain: 'consent.test', path: '/' }]);
    await loadManager(page);
    await expectConsent(page);
});

test('saving optional choices cannot revoke a required service, including a service shared across categories', async ({ page }) => {
    await loadManager(page);
    await page.evaluate(() => window.consentManager.showPreferences());
    await page.locator('[data-consent-manager-service-toggle="optional"]').check();
    await page.getByRole('button', { name: 'Save', exact: true }).click();
    await expectConsent(page, true);

    await page.evaluate(() => {
        window.consentManager.showPreferences();
        document.querySelectorAll('[data-consent-manager-service-toggle]').forEach(input => { input.checked = false; });
    });
    await Promise.all([
        page.waitForEvent('load'),
        page.getByRole('button', { name: 'Save', exact: true }).click(),
    ]);
    await page.waitForFunction(() => window.consentManager?.integrations.length === 1);
    await expectConsent(page);
});

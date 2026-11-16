import { test, expect } from '@playwright/test';
import { compile } from '@tailwindcss/node';
import { readFileSync } from 'node:fs';

const manifest = JSON.parse(readFileSync(new URL('../../dist/frontend/manifest.json', import.meta.url)));
const addonCss = readFileSync(new URL(`../../dist/frontend/${manifest['resources/css/consent-manager.css'].file}`, import.meta.url), 'utf8');
const dialogHtml = readFileSync(new URL('../../resources/views/dialog/consent-dialog.antlers.html', import.meta.url), 'utf8')
    .replace(/<script[\s\S]*?<\/script>|<link[^>]*>/g, '')
    .replace(/\{\{[\s\S]*?\}\}/g, '');

const fixture = `
    <main>
        <h1 id="host-heading">Host heading</h1>
        <div id="host-card" class="bg-blue-600 p-4 flex rounded-lg">Host content</div>
        <div style="--tw-translate-x: 17px"><span id="host-transform" style="translate: var(--tw-translate-x) 0px">Host transform</span></div>
    </main>
    <div data-consent-placeholder="youtube">Host placeholder</div>
    ${dialogHtml}
`;

async function hostStyles(page) {
    return page.evaluate(() => Object.fromEntries(['host-heading', 'host-card', 'host-transform'].map(id => {
        const style = getComputedStyle(document.getElementById(id));
        return [id, Object.fromEntries(['backgroundColor', 'padding', 'display', 'fontSize', 'fontWeight', 'margin', 'translate'].map(property => [property, style[property]]))];
    })));
}

for (const addonFirst of [false, true]) {
    test(`preserves a Tailwind site's theme with addon CSS ${addonFirst ? 'first' : 'last'}`, async ({ page }) => {
        const compiler = await compile('@import "tailwindcss"; @theme { --color-blue-600: #e11d48; --spacing: 8px; }', {
            base: process.cwd(),
            onDependency() {},
        });
        const hostCss = compiler.build(['bg-blue-600', 'p-4', 'flex', 'rounded-lg']);
        await page.setContent(fixture);
        const hostStyle = await page.addStyleTag({ content: hostCss });
        const before = await hostStyles(page);
        await page.addStyleTag({ content: addonCss });
        if (addonFirst) await hostStyle.evaluate(style => document.head.append(style));

        expect(await hostStyles(page)).toEqual(before);
        await expect(page.locator('#host-card')).toHaveCSS('background-color', 'rgb(225, 29, 72)');
        await expect(page.locator('#host-card')).toHaveCSS('padding', '32px');

        await page.locator('dialog[data-consent-manager="preferences"]').evaluate(dialog => dialog.showModal());
        await expect(page.locator('dialog[data-consent-manager="preferences"]')).toHaveCSS('padding', '24px');
        await expect(page.locator('[data-consent-manager-action="accept-all"]').first()).toHaveCSS('background-color', 'rgb(37, 99, 235)');
    });
}

test('preserves a plain site while styling the dialog and its toggle states', async ({ page }) => {
    await page.setContent(fixture);
    const before = await hostStyles(page);
    await page.addStyleTag({ content: addonCss });
    expect(await hostStyles(page)).toEqual(before);
    await expect(page.locator('[data-consent-placeholder]')).toHaveCSS('display', 'contents');

    const preferences = page.locator('dialog[data-consent-manager="preferences"]');
    await preferences.evaluate(dialog => dialog.showModal());
    await expect(preferences).toHaveCSS('font-size', '16px');
    await expect(preferences).toHaveCSS('padding', '24px');
    const toggle = page.locator('[data-consent-manager-toggle]').first();
    await toggle.evaluate(input => { input.disabled = false; input.checked = false; });
    const label = toggle.locator('+ label');
    await expect(label).toHaveCSS('width', '40px');
    await expect(label).toHaveCSS('height', '24px');
    await toggle.evaluate(input => { input.checked = true; });
    await expect(label).toHaveCSS('background-color', 'rgb(37, 99, 235)');
    await expect.poll(() => label.evaluate(element => getComputedStyle(element, '::after').translate)).toBe('16px');
    await toggle.evaluate(input => { input.checked = false; input.indeterminate = true; });
    await expect.poll(() => label.evaluate(element => getComputedStyle(element, '::after').translate)).toBe('8px');
});

test('supports using only the host Tailwind build and placeholder helper', async ({ page }) => {
    const compiler = await compile('@import "tailwindcss"; [data-consent-placeholder] { display: contents; }', {
        base: process.cwd(),
        onDependency() {},
    });
    const classes = [...dialogHtml.matchAll(/\bclass="([^"]*)"/g)].flatMap(match => match[1].split(/\s+/));
    await page.setContent(fixture);
    await page.addStyleTag({ content: compiler.build(classes) });
    const preferences = page.locator('dialog[data-consent-manager="preferences"]');
    await preferences.evaluate(dialog => dialog.showModal());
    await expect(preferences).toHaveCSS('padding', '24px');
    await expect(page.locator('[data-consent-placeholder]')).toHaveCSS('display', 'contents');
    await expect(page.locator('[data-consent-manager-action="accept-all"]').first()).not.toHaveCSS('background-color', 'rgba(0, 0, 0, 0)');
});

import selectorParser from 'postcss-selector-parser';

const dialog = 'dialog[data-consent-manager]';
const scope = selectorParser().astSync(`:where(${dialog}, ${dialog} *)`).first.first;
const rootScope = selectorParser().astSync(`:where(${dialog})`).first.first;
const namespaceProperties = value => value.replace(/--[\w-]+/g, property => `--consent-manager-${property.slice(2)}`);

// Keep the published template usable with the site's own Tailwind build, while
// confining the bundled Tailwind utilities, reset and theme to our dialogs.
export default function scopeConsentStyles() {
    return {
        postcssPlugin: 'consent-manager-scoped-styles',
        Once(root) {
            root.walkAtRules('layer', rule => {
                // Host layer order must not put its reset above our utilities.
                // Keep only the low-priority property fallback in its own layer.
                if (rule.params === 'properties') rule.params = 'consent-manager.properties';
                else if (rule.nodes) rule.replaceWith(rule.nodes);
                else rule.remove();
            });

            root.walkAtRules('property', rule => {
                rule.params = namespaceProperties(rule.params);
            });

            root.walkDecls(declaration => {
                if (declaration.prop.startsWith('--')) {
                    declaration.prop = namespaceProperties(declaration.prop);
                }
                declaration.value = namespaceProperties(declaration.value);
            });

            root.walkRules(rule => {
                // Placeholders live in the page, outside the consent dialogs.
                if (rule.selector === '[data-consent-placeholder]') return;

                for (let parent = rule.parent; parent; parent = parent.parent) {
                    if (parent.type === 'atrule' && /keyframes$/.test(parent.name)) return;
                }

                rule.selector = selectorParser(selectors => {
                    selectors.each(selector => {
                        selector.each(node => {
                            if ((node.type === 'tag' && node.value === 'html') ||
                                (node.type === 'pseudo' && [':root', ':host'].includes(node.value))) {
                                node.replaceWith(rootScope.clone());
                            }
                        });

                        const pseudoElement = selector.nodes.find(node => node.type === 'pseudo' &&
                            (node.value.startsWith('::') || [':before', ':after', ':first-letter', ':first-line'].includes(node.value)));

                        if (pseudoElement) selector.insertBefore(pseudoElement, scope.clone());
                        else selector.append(scope.clone());
                    });
                }).processSync(rule.selector);
            });
        },
    };
}

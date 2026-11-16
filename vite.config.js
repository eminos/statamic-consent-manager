import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import statamic from '@statamic/cms/vite-plugin';
import { glob } from 'glob';
import scopeConsentStyles from './build/scope-consent-styles.js';
 
export default defineConfig(({ command, mode }) => {
    const isCp = mode === 'cp';
    
    return {
        css: {
            postcss: {
                plugins: isCp ? [] : [scopeConsentStyles()],
            },
        },
        plugins: [
            ...(isCp ? [statamic()] : []),
            tailwindcss(),
            laravel({
                input: isCp 
                    ? ['resources/js/cp.js']
                    : [
                        'resources/js/consent-manager.js',
                        ...glob.sync('resources/js/integrations/*Integration.js'),
                        'resources/css/consent-manager.css',
                    ],
                hotFile: isCp ? 'dist/cp/vite.hot' : 'dist/frontend/vite.hot',
                publicDirectory: isCp ? 'dist/cp' : 'dist/frontend',
                buildDirectory: '.',
            }),
        ],
    };
});

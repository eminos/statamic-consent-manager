import { defineConfig } from '@playwright/test';

export default defineConfig({
    testDir: './tests/frontend',
    fullyParallel: true,
    use: { browserName: 'chromium', channel: process.env.PLAYWRIGHT_CHANNEL, headless: true },
});

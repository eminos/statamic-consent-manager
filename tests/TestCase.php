<?php

namespace Eminos\StatamicConsentManager\Tests;

use Eminos\StatamicConsentManager\ServiceProvider;
use Statamic\Facades\YAML;
use Statamic\Testing\AddonTestCase;

abstract class TestCase extends AddonTestCase
{
    protected string $addonServiceProvider = ServiceProvider::class;

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        // Configure consent manager
        $app['config']->set('consent-manager.cookie_name', 'consent_manager');
        $app['config']->set('consent-manager.cookie_duration_days', 180);
        $app['config']->set('consent-manager.enable_in_live_preview', false);
        $app['config']->set('consent-manager.debug', false);

        // Register view namespace
        $app['config']->set('view.paths', [
            __DIR__.'/../resources/views',
            resource_path('views'),
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Register view namespace for addon
        $this->app['view']->addNamespace('consent-manager', __DIR__.'/../resources/views');
    }

    /**
     * Create a test configuration file
     */
    protected function createTestConfig(array $config): void
    {
        $path = base_path('content/consent-manager.yaml');

        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, YAML::dump($config));

        $this->clearConsentManagerCache();
    }

    /**
     * Clean up test configuration
     */
    protected function cleanupTestConfig(): void
    {
        $path = base_path('content/consent-manager.yaml');

        if (file_exists($path)) {
            unlink($path);
        }

        $this->clearConsentManagerCache();
    }

    /**
     * Clear the static cache in ConsentManagerTag
     */
    private function clearConsentManagerCache(): void
    {
        $reflection = new \ReflectionClass(\Eminos\StatamicConsentManager\Tags\ConsentManagerTag::class);

        $servicesProperty = $reflection->getProperty('cachedServices');
        $servicesProperty->setAccessible(true);
        $servicesProperty->setValue(null, null);

        $configProperty = $reflection->getProperty('cachedConfig');
        $configProperty->setAccessible(true);
        $configProperty->setValue(null, null);
    }
}

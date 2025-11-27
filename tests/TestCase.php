<?php

namespace Eminos\StatamicConsentManager\Tests;

use Eminos\StatamicConsentManager\ServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Statamic\Extend\Manifest;
use Statamic\Facades\YAML;
use Statamic\Providers\StatamicServiceProvider;
use Statamic\Statamic;

abstract class TestCase extends OrchestraTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Disable Vite in tests (no manifest file needed)
        $this->withoutVite();

        // Set up Statamic
        $this->setupStatamic();
        
        // Register view namespace for addon
        $this->app['view']->addNamespace('consent-manager', __DIR__.'/../resources/views');
    }

    protected function getPackageProviders($app)
    {
        return [
            StatamicServiceProvider::class,
            ServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'Statamic' => Statamic::class,
        ];
    }

    protected function resolveApplicationConfiguration($app)
    {
        parent::resolveApplicationConfiguration($app);

        // Configure Statamic for testing
        $app['config']->set('statamic.users.repository', 'file');
        $app['config']->set('statamic.stache.stores.users.directory', __DIR__.'/__fixtures__/users');
        
        // Configure consent manager
        $app['config']->set('consent-manager.cookie_name', 'consent_manager');
        $app['config']->set('consent-manager.cookie_duration_days', 180);
        $app['config']->set('consent-manager.enable_in_live_preview', false);
        $app['config']->set('consent-manager.debug', false);
        
        // Disable Vite in tests
        $app['config']->set('app.env', 'testing');

        // Register view namespace
        $app['config']->set('view.paths', [
            __DIR__.'/../resources/views',
            resource_path('views'),
        ]);
    }

    protected function setupStatamic()
    {
        // Set up necessary Statamic directories
        $this->app->booted(function () {
            $this->artisan('view:clear');
        });
    }

    protected function defineEnvironment($app)
    {
        $app->make(Manifest::class)->manifest = [
            'eminos/statamic-consent-manager' => [
                'id' => 'eminos/statamic-consent-manager',
                'namespace' => 'Eminos\\StatamicConsentManager',
            ],
        ];
    }

    /**
     * Create a test configuration file
     * Uses the standard content path and cleans up after each test
     */
    protected function createTestConfig(array $config): void
    {
        $path = base_path('content/consent-manager.yaml');
        
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, YAML::dump($config));
        
        // Clear the static cache in ConsentManagerTag
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
        
        // Clear the static cache in ConsentManagerTag
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

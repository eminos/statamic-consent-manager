<?php

namespace Eminos\StatamicConsentManager\Tags\Concerns;

use Eminos\StatamicConsentManager\Integrations\IntegrationManager;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Statamic\Facades\YAML;

trait ManagesConsentConfiguration
{
    private const CONFIG_FILENAME = 'consent-manager';

    private static $cachedServices = null;
    private static $cachedConfig = null;

    protected function getContentPath(): string
    {
        return base_path('content/'.self::CONFIG_FILENAME.'.yaml');
    }

    protected function configuration(): ?array
    {
        if (self::$cachedConfig !== null) {
            return self::$cachedConfig;
        }

        $path = $this->getContentPath();

        if (!File::exists($path)) {
            return self::$cachedConfig = null;
        }

        return self::$cachedConfig = YAML::parse(File::get($path)) ?? [];
    }

    protected function shouldSkipInLivePreview(): bool
    {
        return request()->has('live-preview') && !config('consent-manager.enable_in_live_preview', false);
    }

    protected function normalizeServices(array $services): array
    {
        $normalized = [];

        foreach ($services as $service) {
            if (!is_array($service)) {
                continue;
            }

            $name = $service['name'] ?? '';
            $handle = $service['handle'] ?? Str::slug($name);

            if (!$handle) {
                continue;
            }

            $normalized[] = [
                'handle' => $handle,
                'name' => $name,
                'description' => $service['description'] ?? '',
                'categories' => array_values(array_filter($service['categories'] ?? [])),
                'scripts' => $service['scripts'] ?? [],
            ];
        }

        return $normalized;
    }

    protected function integrationsConfig(array $config, array $categories): array
    {
        $manager = new IntegrationManager();
        $integrationsData = $config['integrations'] ?? [];

        if (!is_array($integrationsData)) {
            $integrationsData = [];
        }

        return $manager->process($integrationsData, $categories);
    }

    protected function generateIntegrationServices(array $integrations): array
    {
        $manager = new IntegrationManager();
        return $manager->generateServices($integrations);
    }

    protected function getAllAvailableServices(): array
    {
        if (self::$cachedServices !== null) {
            return self::$cachedServices;
        }

        $config = $this->configuration();
        $categories = $config['categories'] ?? [];
        $services = $config['services'] ?? [];
        $integrations = $this->integrationsConfig($config, $categories);

        $integrationServices = $this->generateIntegrationServices($integrations);
        $normalizedServices = $this->normalizeServices($services);
        
        self::$cachedServices = array_merge($integrationServices, $normalizedServices);
        
        return self::$cachedServices;
    }

    protected function hasConsentForService(string $serviceId): bool
    {
        $cookieName = config('consent-manager.cookie_name', 'consent_manager');
        
        if (!isset($_COOKIE[$cookieName])) {
            return false;
        }

        try {
            $consentData = json_decode($_COOKIE[$cookieName], true);
            $servicePrefs = $consentData['services'] ?? [];

            return isset($servicePrefs[$serviceId]) && $servicePrefs[$serviceId] === true;
        } catch (\Exception $e) {
            return false;
        }
    }
}


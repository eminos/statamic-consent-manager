<?php

namespace Eminos\StatamicConsentManager\Integrations;

class IntegrationManager
{
    private array $integrations = [];

    public function __construct()
    {
        $this->register();
    }

    /**
     * Register all available integrations
     */
    private function register(): void
    {
        $this->integrations = [
            new GoogleTagIntegration(),
            new MetaPixelIntegration(),
            new LinkedInInsightIntegration(),
        ];
    }

    /**
     * Get all registered integrations
     * 
     * @return Integration[]
     */
    public function all(): array
    {
        return $this->integrations;
    }

    /**
     * Process integration configurations
     * 
     * @param array $integrationsData Raw integration data from YAML
     * @param array $categories Available consent categories
     * @return array Processed integrations config
     */
    public function process(array $integrationsData, array $categories): array
    {
        $processed = [];

        foreach ($this->integrations as $integration) {
            $settings = $integrationsData[$integration->key()] ?? [];
            
            if (!is_array($settings)) {
                $settings = [];
            }

            $config = $integration->process($settings, $categories);

            if ($config !== null) {
                $processed[$integration->key()] = $config;
            }
        }

        return $processed;
    }

    /**
     * Generate service entries for all active integrations
     * 
     * @param array $integrations Processed integrations from process()
     * @return array Array of service definitions
     */
    public function generateServices(array $integrations): array
    {
        $services = [];

        foreach ($this->integrations as $integration) {
            $key = $integration->key();
            
            if (isset($integrations[$key])) {
                $integrationServices = $integration->generateServices($integrations[$key]);
                $services = array_merge($services, $integrationServices);
            }
        }

        return $services;
    }
}

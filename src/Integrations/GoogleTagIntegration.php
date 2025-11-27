<?php

namespace Eminos\StatamicConsentManager\Integrations;

class GoogleTagIntegration extends Integration
{
    protected string $loadingStrategy = 'always'; // Uses Google Consent Mode

    public function key(): string
    {
        return 'google_tag';
    }

    public function name(): string
    {
        return 'Google Tag';
    }

    public function process(array $settings, array $categories): ?array
    {
        if (!$this->isEnabled($settings, 'measurement_id')) {
            return null;
        }

        $serviceMapping = [];
        
        foreach ($settings['consent_mapping'] ?? [] as $item) {
            if (!is_array($item)) {
                continue;
            }

            $category = $item['category'] ?? null;
            $storageTypes = $item['storage_types'] ?? [];

            if (!$category || !is_array($storageTypes) || empty($storageTypes)) {
                continue;
            }

            $serviceHandle = 'google_tag_' . $category;
            $serviceMapping[$serviceHandle] = array_values($storageTypes);
        }

        return [
            'enabled' => true,
            'measurement_id' => trim($settings['measurement_id']),
            'service_mapping' => $serviceMapping,
        ];
    }

    public function generateServices(array $config): array
    {
        $services = [];

        foreach ($config['service_mapping'] ?? [] as $serviceHandle => $storageTypes) {
            $category = str_replace('google_tag_', '', $serviceHandle);
            
            $services[] = [
                'handle' => $serviceHandle,
                'name' => $this->name(),
                'description' => $this->description(),
                'type' => 'integration',
                'integration_key' => $this->key(),
                'categories' => [$category],
                'storage_types' => $storageTypes,
            ];
        }

        return $services;
    }
}

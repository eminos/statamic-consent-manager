<?php

namespace Eminos\StatamicConsentManager\Integrations;

class GoogleTagIntegration extends Integration
{
    private const STORAGE_PURPOSES = [
        'functionality_storage' => 'functionality',
        'personalization_storage' => 'personalization',
        'security_storage' => 'security',
        'analytics_storage' => 'analytics',
        'ad_storage' => 'advertising',
        'ad_user_data' => 'advertising',
        'ad_personalization' => 'advertising',
    ];

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
            $presentation = $this->describeStorageTypes($storageTypes);
            
            $services[] = [
                'handle' => $serviceHandle,
                'name' => $presentation['name'],
                'description' => $presentation['description'],
                'type' => 'integration',
                'integration_key' => $this->key(),
                'categories' => [$category],
                'storage_types' => $storageTypes,
            ];
        }

        return $services;
    }

    private function describeStorageTypes(array $storageTypes): array
    {
        $purposes = [];
        $descriptions = [];

        foreach (array_unique($storageTypes) as $storageType) {
            $purpose = self::STORAGE_PURPOSES[$storageType] ?? null;
            if ($purpose === null) {
                continue;
            }

            $purposes[$purpose] = __('consent-manager::dialog.integrations.google_tag.purposes.'.$purpose);
            $descriptions[] = __('consent-manager::dialog.integrations.google_tag.storage_descriptions.'.$storageType);
        }

        return [
            'name' => $purposes ? __('consent-manager::dialog.integrations.google_tag.service_name', [
                'name' => $this->name(),
                'purposes' => implode(', ', $purposes),
            ]) : $this->name(),
            'description' => $descriptions ? implode(' ', $descriptions) : $this->description(),
        ];
    }
}

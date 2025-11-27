<?php

namespace Eminos\StatamicConsentManager\Integrations;

class MetaPixelIntegration extends Integration
{
    protected string $loadingStrategy = 'always'; // Uses Meta's consent management

    public function key(): string
    {
        return 'meta_pixel';
    }

    public function name(): string
    {
        return 'Facebook Pixel';
    }

    public function process(array $settings, array $categories): ?array
    {
        if (!$this->isEnabled($settings, 'pixel_id')) {
            return null;
        }

        $requiredCategories = $settings['required_categories'] ?? [];
        
        if (!is_array($requiredCategories)) {
            $requiredCategories = [];
        }

        return [
            'enabled' => true,
            'pixel_id' => trim($settings['pixel_id']),
            'required_categories' => array_values($requiredCategories),
        ];
    }

    public function generateServices(array $config): array
    {
        return [
            [
                'handle' => $this->key(),
                'name' => $this->name(),
                'description' => $this->description(),
                'type' => 'integration',
                'integration_key' => $this->key(),
                'categories' => $config['required_categories'] ?? [],
            ]
        ];
    }
}

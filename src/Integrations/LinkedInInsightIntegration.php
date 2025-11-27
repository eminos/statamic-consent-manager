<?php

namespace Eminos\StatamicConsentManager\Integrations;

class LinkedInInsightIntegration extends Integration
{
    protected string $loadingStrategy = 'after_consent'; // Only loads after consent

    public function key(): string
    {
        return 'linkedin_insight';
    }

    public function name(): string
    {
        return 'LinkedIn Insight';
    }

    public function process(array $settings, array $categories): ?array
    {
        if (!$this->isEnabled($settings, 'partner_id')) {
            return null;
        }

        $requiredCategories = $settings['required_categories'] ?? [];
        
        if (!is_array($requiredCategories)) {
            $requiredCategories = [];
        }

        return [
            'enabled' => true,
            'partner_id' => trim($settings['partner_id']),
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

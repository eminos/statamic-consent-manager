<?php

namespace Eminos\StatamicConsentManager\Integrations;

use Illuminate\Support\Str;

abstract class Integration
{
    /**
     * Defines when the integration script should be loaded
     * 
     * - 'always': Load immediately, integration handles consent internally (e.g., Google Consent Mode)
     * - 'after_consent': Only load after user has consented to required categories
     */
    protected string $loadingStrategy = 'after_consent';

    /**
     * Get the unique key for this integration
     */
    abstract public function key(): string;

    /**
     * Get the display name for this integration
     */
    abstract public function name(): string;

    /**
     * Get the loading strategy for this integration
     */
    public function getLoadingStrategy(): string
    {
        return $this->loadingStrategy;
    }

    /**
     * Get the description for this integration from translations
     */
    public function description(): string
    {
        return __('consent-manager::dialog.integrations.' . $this->key() . '.description');
    }

    /**
     * Process the integration settings and return configuration
     * 
     * @param array $settings Raw settings from YAML
     * @param array $categories Available consent categories
     * @return array|null Processed configuration or null if disabled/invalid
     */
    abstract public function process(array $settings, array $categories): ?array;

    /**
     * Generate service entries for the consent dialog
     * 
     * @param array $config Processed configuration from process()
     * @return array Array of service definitions
     */
    abstract public function generateServices(array $config): array;

    /**
     * Find a category by its handle
     */
    protected function findCategoryByHandle(array $categories, string $handle): ?array
    {
        foreach ($categories as $category) {
            if (!is_array($category)) {
                continue;
            }

            if (($category['handle'] ?? null) === $handle) {
                return $category;
            }
        }

        return null;
    }

    /**
     * Validate and normalize array settings
     */
    protected function normalizeSettings(array|null $settings): array
    {
        if (!is_array($settings)) {
            return [];
        }

        return $settings;
    }

    /**
     * Check if integration is enabled with required field
     */
    protected function isEnabled(array $settings, string $requiredField): bool
    {
        $enabled = $settings['enabled'] ?? false;
        $fieldValue = isset($settings[$requiredField]) ? trim((string) $settings[$requiredField]) : '';

        return $enabled && $fieldValue !== '';
    }
}

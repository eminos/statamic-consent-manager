<?php

namespace Eminos\StatamicConsentManager\Tags;

use Eminos\StatamicConsentManager\Integrations\IntegrationManager;
use Eminos\StatamicConsentManager\Tags\Concerns\HandlesRequireTag;
use Eminos\StatamicConsentManager\Tags\Concerns\ManagesConsentConfiguration;
use Illuminate\Support\Str;
use Statamic\Tags\Tags;

class ConsentManagerTag extends Tags
{
    use ManagesConsentConfiguration;
    use HandlesRequireTag;

    protected static $handle = 'consent_manager';

    private function shouldRenderTag(): bool
    {
        if ($this->shouldSkipInLivePreview()) {
            return false;
        }

        return $this->configuration() !== null;
    }

    private function groupServicesByCategory(array $categories, array $services): array
    {
        return array_map(function ($category) use ($services) {
            if (!is_array($category)) {
                return $category;
            }

            $handle = $category['handle'] ?? Str::slug($category['name'] ?? '');
            $categoryServices = array_values(array_filter($services, function ($service) use ($handle) {
                return $handle !== '' && in_array($handle, $service['categories'] ?? [], true);
            }));

            return array_merge($category, [
                'handle' => $handle,
                'services' => $categoryServices,
            ]);
        }, $categories);
    }

    public function dialog()
    {
        if (!$this->shouldRenderTag()) {
            return '';
        }

        $config = $this->configuration();
        $categories = $config['categories'] ?? [];
        $integrations = $this->integrationsConfig($config, $categories);

        $allServices = $this->getAllAvailableServices();
        
        $categoriesWithServices = $this->groupServicesByCategory($categories, $allServices);

        $payload = [
            'categories' => array_map(function ($category) {
                return [
                    'handle' => $category['handle'] ?? Str::slug($category['name'] ?? ''),
                    'required' => $category['required'] ?? false,
                    'default_enabled' => $category['default_enabled'] ?? false,
                    'description' => $category['description'] ?? '',
                    'name' => $category['name'] ?? '',
                ];
            }, $categories),
            'services' => array_map(function ($service) {
                return [
                    'handle' => $service['handle'] ?? Str::slug($service['name'] ?? ''),
                    'name' => $service['name'] ?? '',
                    'categories' => $service['categories'] ?? [],
                    'type' => $service['type'] ?? 'custom',
                ];
            }, $allServices),
            'integrations' => $integrations,
            'consent_revision_date' => $config['consent_revision_date'] ?? null,
            'cookie_name' => config('consent-manager.cookie_name', 'consent_manager'),
            'cookie_expiry' => config('consent-manager.cookie_duration_days', 180),
            'cookie_path' => config('consent-manager.cookie_path', '/'),
            'cookie_domain' => config('consent-manager.cookie_domain'),
            'cookie_secure' => config('consent-manager.cookie_secure', false),
            'cookie_same_site' => config('consent-manager.cookie_same_site', 'lax'),
            'debug' => config('consent-manager.debug', false),
        ];

        $jsonPayload = json_encode($payload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        return "<script type=\"application/json\" data-consent-manager=\"payload\">{$jsonPayload}</script>\n"
            . view('consent-manager::dialog.consent-dialog', [
                'categories' => $categoriesWithServices,
                'services' => $allServices,
                'integrations' => $integrations,
            ])->render();
    }

    private function renderIntegrations(array $integrations, ?string $placement = null): string
    {
        $output = '';
        $manager = new IntegrationManager();

        foreach ($manager->all() as $integration) {
            $key = $integration->key();
            $config = $integrations[$key] ?? null;

            if (!$config || !isset($config['enabled']) || !$config['enabled']) {
                continue;
            }

            $viewName = match ($key) {
                'google_tag' => 'consent-manager::integrations.google-tag',
                'meta_pixel' => 'consent-manager::integrations.meta-pixel',
                'linkedin_insight' => 'consent-manager::integrations.linkedin-insight',
                default => null,
            };

            if (!$viewName || !view()->exists($viewName)) {
                continue;
            }

            if ($placement !== null) {
                $config['placement'] = $placement;
            }
            
            $scriptContent = view($viewName, ['config' => $config])->render();

            // Skip if template didn't render anything (wrong placement)
            if (trim($scriptContent) === '') {
                continue;
            }

            // Wrap in template tag if this integration needs consent before loading
            if ($integration->getLoadingStrategy() === 'after_consent') {
                $requiredCategories = $config['required_categories'] ?? [];
                $output .= $this->wrapInTemplateIfNeeded(
                    $scriptContent,
                    $requiredCategories,
                    $key, // service identifier
                    $integration->name(),
                    $placement
                );
            } else {
                // Always load strategy - render directly with comment
                $output .= "<!-- {$integration->name()} -->\n{$scriptContent}\n";
            }
        }

        return $output;
    }

    public function head()
    {
        if (!$this->shouldRenderTag()) {
            return '';
        }

        $config = $this->configuration();
        $integrations = $this->integrationsConfig($config, $config['categories'] ?? []);
        
        return $this->renderIntegrations($integrations, 'head')
            . $this->renderScriptsForPlacement('head');
    }

    public function body()
    {
        if (!$this->shouldRenderTag()) {
            return '';
        }

        $config = $this->configuration();
        $integrations = $this->integrationsConfig($config, $config['categories'] ?? []);
        
        return $this->renderIntegrations($integrations, 'body')
            . $this->renderScriptsForPlacement('body');
    }

    private function renderScriptsForPlacement(string $placement): string
    {
        $services = $this->getAllAvailableServices();

        $output = '';

        foreach ($services as $service) {
            foreach ($service['scripts'] ?? [] as $script) {
                if (($script['placement'] ?? null) !== $placement) {
                    continue;
                }

                $content = $this->normalizeScriptContent($script['script'] ?? '');
                if ($content === '') {
                    continue;
                }

                $output .= $this->wrapInTemplateIfNeeded(
                    $content,
                    $service['categories'] ?? [],
                    $service['handle'] ?? '',
                    $service['name'] ?? 'unknown',
                    $placement
                );
            }
        }

        return $output;
    }

    private function wrapInTemplateIfNeeded(
        string $scriptContent,
        array $categories,
        string $serviceId,
        string $serviceName,
        string $placement
    ): string {
        $escapedServiceId = htmlspecialchars($serviceId, ENT_QUOTES, 'UTF-8');
        $escapedServiceName = htmlspecialchars($serviceName, ENT_QUOTES, 'UTF-8');
        
        if ($this->hasConsentForService($serviceId)) {
            return "<!-- {$serviceName} -->\n{$scriptContent}\n";
        }

        // User doesn't have consent yet, wrap in template tag
        $serviceAttr = $serviceId !== '' ? ' data-service-handle="'.$escapedServiceId.'"' : '';
        
        return "<!-- {$serviceName} -->\n<template data-consent-manager-script{$serviceAttr} data-placement=\"{$placement}\" data-service=\"{$escapedServiceName}\">{$scriptContent}\n</template>\n";
    }

    private function normalizeScriptContent($content): string
    {
        if (is_string($content)) {
            return $content;
        }

        if (!is_array($content)) {
            return '';
        }

        return $content['code'] ?? '';
    }
}

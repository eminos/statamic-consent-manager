<?php

namespace Eminos\StatamicConsentManager\Tags\Concerns;

use Illuminate\Support\Str;

/**
 * Trait for handling the consent_manager:require tag functionality
 */
trait HandlesRequireTag
{
    /**
     * Conditional content tag - shows placeholder until user consents to service
     * 
     * Usage:
     * {{ consent_manager:require service="youtube_embed" }}
     *     {{ placeholder }}
     *         <div>Please accept YouTube cookies to view this video.</div>
     *         <button data-give-consent="youtube_embed">Accept</button>
     *     {{ /placeholder }}
     *     <iframe src="https://youtube.com/embed/..."></iframe>
     * {{ /consent_manager:require }}
     * 
     * Note: Also supports {{ slot:placeholder }} syntax if preferred
     */
    public function require()
    {
        $service = $this->params->get('service');
        $rawContent = $this->content ?? '';
        
        // Extract placeholder content from nested {{ placeholder }} or {{ slot:placeholder }} tags
        // and remove them from the main content
        $placeholder = '';
        $content = $rawContent;
        
        // Try to extract {{ slot:placeholder }} first (named slot syntax)
        if (preg_match('/\{\{\s*slot:placeholder\s*\}\}(.*?)\{\{\s*\/slot:placeholder\s*\}\}/s', $rawContent, $matches)) {
            $placeholder = trim($matches[1]);
            $content = preg_replace('/\{\{\s*slot:placeholder\s*\}\}.*?\{\{\s*\/slot:placeholder\s*\}\}/s', '', $rawContent);
        }
        // Fall back to {{ placeholder }} syntax
        elseif (preg_match('/\{\{\s*placeholder\s*\}\}(.*?)\{\{\s*\/placeholder\s*\}\}/s', $rawContent, $matches)) {
            $placeholder = trim($matches[1]);
            $content = preg_replace('/\{\{\s*placeholder\s*\}\}.*?\{\{\s*\/placeholder\s*\}\}/s', '', $rawContent);
        }
        
        $content = trim($content);

        if (!$service) {
            return '<!-- consent_manager:require - service parameter required -->';
        }

        $availableServices = $this->getAllAvailableServices();
        
        $serviceExists = false;
        $serviceCategories = [];
        foreach ($availableServices as $s) {
            if (($s['handle'] ?? '') === $service) {
                $serviceExists = true;
                $serviceCategories = $s['categories'] ?? [];
                break;
            }
        }
        
        if (!$serviceExists) {
            $availableHandles = array_map(function ($s) {
                return $s['handle'] ?? '';
            }, $availableServices);
            $availableHandles = array_filter($availableHandles);
            
            return sprintf(
                '<!-- consent_manager:require - service "%s" not found. Available services: %s -->',
                htmlspecialchars($service, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars(implode(', ', $availableHandles), ENT_QUOTES, 'UTF-8')
            );
        }

        $hasConsent = $this->hasConsentForService($service);
        
        if (!$hasConsent) {
            $config = $this->configuration();
            $categories = $config['categories'] ?? [];
            foreach ($categories as $category) {
                $categoryHandle = $category['handle'] ?? Str::slug($category['name'] ?? '');
                $isRequired = $category['required'] ?? false;
                if ($isRequired && in_array($categoryHandle, $serviceCategories, true)) {
                    $hasConsent = true;
                    break;
                }
            }
        }
        
        if ($hasConsent) {
            return $content;
        }

        $hasAttribute = !empty($placeholder) && str_contains($placeholder, 'data-consent-placeholder');
        
        if (!$hasAttribute && !empty($placeholder)) {
            $placeholder = sprintf(
                '<div data-consent-placeholder="%s">%s</div>',
                htmlspecialchars($service, ENT_QUOTES, 'UTF-8'),
                $placeholder
            );
        }

        $wrappedContent = sprintf(
            '<template data-consent-manager-script data-service-handle="%s">%s</template>',
            htmlspecialchars($service, ENT_QUOTES, 'UTF-8'),
            $content
        );

        return $placeholder . "\n" . $wrappedContent;
    }
}


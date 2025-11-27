<?php

namespace Eminos\StatamicConsentManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Statamic\Facades\Blueprint as BlueprintFacade;
use Statamic\Fields\Blueprint;
use Statamic\Facades\YAML;
use Statamic\Http\Controllers\CP\CpController;

class ConsentManagerController extends CpController
{
    private const CONFIG_FILENAME = 'consent-manager';

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless($request->user()->can('manage consent manager'), 403, 'You do not have permission to manage the Consent Manager.');
            return $next($request);
        });
    }

    private function getContentDirectory(): string
    {
        return base_path('content');
    }

    private function getContentPath(): string
    {
        return $this->getContentDirectory().'/'.self::CONFIG_FILENAME.'.yaml';
    }

    private function getPackageContentPath(): string
    {
        return __DIR__.'/../../../content/'.self::CONFIG_FILENAME.'.yaml';
    }

    private function getConfiguration(): array
    {
        $path = $this->getContentPath();

        if (File::exists($path)) {
            return YAML::parse(File::get($path)) ?? [];
        }

        $packagePath = $this->getPackageContentPath();

        if (File::exists($packagePath)) {
            return YAML::parse(File::get($packagePath)) ?? [];
        }

        return [];
    }

    private function validateUniqueHandles(array $values): void
    {
        // Validate unique category handles
        if (isset($values['categories']) && is_array($values['categories'])) {
            $categoryHandles = [];
            
            foreach ($values['categories'] as $category) {
                if (!is_array($category)) {
                    continue;
                }
                
                $name = trim($category['name'] ?? '');
                $handle = trim($category['handle'] ?? '');
                
                if (empty($name)) {
                    continue;
                }

                // Auto-generate handle if empty
                if (empty($handle)) {
                    $handle = Str::slug($name);
                }

                $lowerHandle = strtolower($handle);
                
                if (in_array($lowerHandle, $categoryHandles)) {
                    throw ValidationException::withMessages([
                        'categories' => ["Category handle '{$handle}' is not unique. Each category must have a unique handle."]
                    ]);
                }
                
                $categoryHandles[] = $lowerHandle;
            }
        }

        // Validate unique service handles
        if (isset($values['services']) && is_array($values['services'])) {
            $serviceHandles = [];
            
            foreach ($values['services'] as $service) {
                if (!is_array($service)) {
                    continue;
                }
                
                $name = trim($service['name'] ?? '');
                $handle = trim($service['handle'] ?? '');
                
                if (empty($name)) {
                    continue;
                }

                // Auto-generate handle if empty
                if (empty($handle)) {
                    $handle = Str::slug($name);
                }

                $lowerHandle = strtolower($handle);
                
                if (in_array($lowerHandle, $serviceHandles)) {
                    throw ValidationException::withMessages([
                        'services' => ["Service handle '{$handle}' is not unique. Each service must have a unique handle."]
                    ]);
                }
                
                $serviceHandles[] = $lowerHandle;
            }
        }
    }

    private function validateServicesInRequiredCategories(array $values): void
    {
        // Build a list of required category handles
        $requiredCategories = [];
        
        if (isset($values['categories']) && is_array($values['categories'])) {
            foreach ($values['categories'] as $category) {
                if (!is_array($category)) {
                    continue;
                }
                
                $name = $category['name'] ?? '';
                $handle = trim($category['handle'] ?? '');
                $isRequired = $category['required'] ?? false;
                
                // Auto-generate handle if empty
                if (empty($handle) && !empty($name)) {
                    $handle = Str::slug($name);
                }
                
                if ($isRequired && !empty($handle)) {
                    $requiredCategories[] = $handle;
                }
            }
        }

        // If no required categories, nothing to validate
        if (empty($requiredCategories)) {
            return;
        }

        // Check each service
        if (isset($values['services']) && is_array($values['services'])) {
            foreach ($values['services'] as $service) {
                if (!is_array($service)) {
                    continue;
                }
                
                $serviceName = $service['name'] ?? 'Unnamed service';
                $serviceCategories = $service['categories'] ?? [];
                
                if (!is_array($serviceCategories)) {
                    continue;
                }

                // Check if this service is in any required category
                $inRequiredCategory = false;
                foreach ($serviceCategories as $categorySlug) {
                    if (in_array($categorySlug, $requiredCategories)) {
                        $inRequiredCategory = true;
                        break;
                    }
                }

                // If in a required category and has multiple categories, that's invalid
                if ($inRequiredCategory && count($serviceCategories) > 1) {
                    throw ValidationException::withMessages([
                        'services' => ["Service '{$serviceName}' is in a required category and cannot be assigned to other categories. Services in required categories must only belong to required categories."]
                    ]);
                }
            }
        }
    }

    private function extractCategoryOptions(array $values): array
    {
        $options = [];
        $categories = $values['categories'] ?? [];

        if (!is_array($categories)) {
            return $options;
        }

        foreach ($categories as $category) {
            if (!is_array($category)) {
                continue;
            }

            $name = $category['name'] ?? null;

            if ($name) {
                $slug = Str::slug($name);
                $options[$slug] = (string) $name;
            }
        }

        return $options;
    }

    private function updateSelectOptions(array &$nodes, string $handle, array $options): void
    {
        foreach ($nodes as &$node) {
            if (!is_array($node)) {
                continue;
            }

            if (isset($node['handle'], $node['field']) && $node['handle'] === $handle) {
                $field = $node['field'];

                if (is_array($field) && ($field['type'] ?? null) === 'select') {
                    $node['field']['options'] = $options;
                }
            }

            // Continue recursing to find all matches
            $this->updateSelectOptions($node, $handle, $options);
        }
    }

    private function populateCategoriesOptions(Blueprint $blueprint, array $categoryOptions): Blueprint
    {
        if (empty($categoryOptions)) {
            return $blueprint;
        }

        $contents = $blueprint->contents();
        
        $this->updateSelectOptions($contents, 'categories', $categoryOptions);
        $this->updateSelectOptions($contents, 'category', $categoryOptions);
        $this->updateSelectOptions($contents, 'required_categories', $categoryOptions);

        return BlueprintFacade::make()->setContents($contents);
    }

    private function blueprint(array $configuration = null): Blueprint
    {
        $path = __DIR__.'/../../../resources/blueprints/'.self::CONFIG_FILENAME.'.yaml';

        if (!File::exists($path)) {
            abort(404, 'Consent Manager blueprint not found.');
        }

        $contents = YAML::parse(File::get($path)) ?? [];
        $blueprint = BlueprintFacade::make()->setContents($contents);

        $configuration = $configuration ?? $this->getConfiguration();
        $categoryOptions = $this->extractCategoryOptions($configuration);

        return $this->populateCategoriesOptions($blueprint, $categoryOptions);
    }

    private function writeConfiguration(array $values): void
    {
        $path = $this->getContentPath();

        File::ensureDirectoryExists(dirname($path));
        File::put($path, YAML::dump($values));
    }

    private function ensureHandles(array &$values): void
    {
        // Ensure categories have handles
        if (isset($values['categories']) && is_array($values['categories'])) {
            foreach ($values['categories'] as &$category) {
                if (!is_array($category)) {
                    continue;
                }
                
                $handle = trim($category['handle'] ?? '');
                $name = trim($category['name'] ?? '');
                
                if (empty($handle) && !empty($name)) {
                    $category['handle'] = Str::slug($name);
                }
            }
        }

        // Ensure services have handles
        if (isset($values['services']) && is_array($values['services'])) {
            foreach ($values['services'] as &$service) {
                if (!is_array($service)) {
                    continue;
                }
                
                $handle = trim($service['handle'] ?? '');
                $name = trim($service['name'] ?? '');
                
                if (empty($handle) && !empty($name)) {
                    $service['handle'] = Str::slug($name);
                }
            }
        }
    }

    private function processBlueprint(array $values, bool $requireReconsent = false): array
    {
        $this->ensureHandles($values);
        
        $blueprint = $this->blueprint();
        $fields = $blueprint->fields()->addValues($values);
        $fields->validate();

        $this->validateUniqueHandles($values);
        $this->validateServicesInRequiredCategories($values);

        $processed = $fields->process()->values()->all();
        
        if ($requireReconsent) {
            $processed['consent_revision_date'] = now()->toIso8601String();
        } else {
            $oldConfig = $this->getConfiguration();
            $processed['consent_revision_date'] = $oldConfig['consent_revision_date'] ?? now()->toIso8601String();
        }

        return $processed;
    }

    public function edit()
    {
        $configuration = $this->getConfiguration();
        $blueprint = $this->blueprint($configuration);
        $fields = $blueprint->fields()->addValues($configuration)->preProcess();

        return view('consent-manager::cp.index', [
            'blueprint' => $blueprint->toPublishArray(),
            'values' => $fields->values(),
            'meta' => $fields->meta(),
        ]);
    }

    public function update(Request $request)
    {
        $requireReconsent = $request->boolean('require_reconsent');
        
        $processed = $this->processBlueprint($request->all(), $requireReconsent);
        $this->writeConfiguration($processed);

        $message = $requireReconsent 
            ? __('Consent Manager settings saved. Users will be prompted for consent again.')
            : __('Consent Manager settings saved.');

        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }
}

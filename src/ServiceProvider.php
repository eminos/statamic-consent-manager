<?php

namespace Eminos\StatamicConsentManager;

use Statamic\Statamic;
use Statamic\Facades\CP\Nav;
use Statamic\Facades\Permission;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    protected $viewNamespace = 'consent-manager';

    protected $publishAfterInstall = false;

    protected $updateScripts = [
        \Eminos\StatamicConsentManager\UpdateScripts\PublishAssets::class,
    ];

    protected $vite = [
        'hotFile' => __DIR__ . '/../dist/cp/vite.hot',
        'publicDirectory' => 'dist',
        'buildDirectory' => 'cp',
        'input' => [
            'resources/js/cp.js',
        ],
    ];

    public function boot(): void
    {
        parent::boot();
        
        $this->loadViewsFrom([
            resource_path('views/vendor/consent-manager'),
            __DIR__.'/../resources/views',
        ], 'consent-manager');
        
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'consent-manager');
        if (is_dir(lang_path('vendor/consent-manager'))) {
            $this->loadTranslationsFrom(lang_path('vendor/consent-manager'), 'consent-manager');
        }
    }

    public function bootAddon(): void
    {
        // Publish frontend & CP assets
        $this->publishes([
            __DIR__.'/../dist/' => public_path('vendor/statamic-consent-manager/'),
        ], 'consent-manager-assets');
        
        $this->publishes([
            __DIR__.'/../config/consent-manager.php' => config_path('consent-manager.php'),
        ], 'consent-manager-config');

        $this->publishes([
            __DIR__.'/../resources/views/dialog/consent-dialog.antlers.html' => resource_path('views/vendor/consent-manager/dialog/consent-dialog.antlers.html'),
        ], 'consent-manager-views');

        $this->publishes([
            __DIR__.'/../resources/views/integrations' => resource_path('views/vendor/consent-manager/integrations'),
        ], 'consent-manager-integrations');

        $this->publishes([
            __DIR__.'/../resources/lang' => lang_path('vendor/consent-manager'),
        ], 'consent-manager-lang');

        $this->mergeConfigFrom(__DIR__.'/../config/consent-manager.php', 'consent-manager');

        Permission::register('manage consent manager')
            ->label('Manage Consent Manager')
            ->description('Access to configure consent categories, services, and integrations');

        Nav::extend(function ($nav) {
            $iconPath = __DIR__.'/../resources/svg/consent-manager.svg';
            $iconSvg = file_exists($iconPath) ? file_get_contents($iconPath) : '';
            
            $nav->create('Consent Manager')
                ->section('Tools')
                ->icon($iconSvg)
                ->route('consent-manager.edit')
                ->can('manage consent manager');
        });

    }
}
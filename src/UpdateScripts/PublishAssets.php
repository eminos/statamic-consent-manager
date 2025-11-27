<?php

namespace Eminos\StatamicConsentManager\UpdateScripts;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Statamic\UpdateScripts\UpdateScript;

class PublishAssets extends UpdateScript
{
    public function shouldUpdate($newVersion, $oldVersion)
    {
        return true;
    }

    public function update()
    {
        $files = app(Filesystem::class);
        $publishedPath = public_path('vendor/statamic-consent-manager');

        // Remove old assets directory
        if ($files->isDirectory($publishedPath)) {
            $files->deleteDirectory($publishedPath);
            $this->console()->info('Removed old assets directory.');
        }

        // Publish fresh assets
        Artisan::call('vendor:publish', [
            '--tag' => 'consent-manager-assets',
            '--force' => true,
        ]);

        $this->console()->info('Published new assets.');
    }
}

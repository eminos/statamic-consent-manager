<?php

namespace Eminos\StatamicConsentManager\Tests\Translations;

use Eminos\StatamicConsentManager\Integrations\GoogleTagIntegration;
use Eminos\StatamicConsentManager\Tests\TestCase;

class PublishedTranslationsTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app->useLangPath(__DIR__.'/../fixtures/lang');
        parent::getEnvironmentSetUp($app);
    }

    public function test_published_overrides_merge_with_the_new_purpose_defaults(): void
    {
        $service = (new GoogleTagIntegration())->generateServices(['service_mapping' => ['google_tag_custom' => ['analytics_storage']]])[0];

        $this->assertSame('Your privacy choices', __('consent-manager::dialog.basic_headline'));
        $this->assertSame('Google Tag (Statistics)', $service['name']);
        $this->assertSame('Stores information to measure website visits and usage.', $service['description']);
    }
}

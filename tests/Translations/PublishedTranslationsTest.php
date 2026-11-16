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

    public function test_a_custom_legacy_description_is_preserved(): void
    {
        __('consent-manager::dialog.basic_headline');
        app('translator')->addLines([
            'dialog.integrations.google_tag.description' => 'Our custom Google privacy description.',
        ], 'en', 'consent-manager');

        $services = (new GoogleTagIntegration())->generateServices(['service_mapping' => [
            'google_tag_analytics' => ['analytics_storage'],
            'google_tag_marketing' => ['ad_storage', 'ad_user_data'],
        ]]);

        foreach ($services as $service) {
            $this->assertSame('Our custom Google privacy description.', $service['description']);
        }
        $this->assertSame('Google Tag (Statistics)', $services[0]['name']);
    }

    public function test_custom_purpose_descriptions_take_precedence_over_a_legacy_override(): void
    {
        __('consent-manager::dialog.basic_headline');
        app('translator')->addLines([
            'dialog.integrations.google_tag.description' => 'Our custom Google privacy description.',
            'dialog.integrations.google_tag.storage_descriptions.analytics_storage' => 'Our analytics description.',
        ], 'en', 'consent-manager');

        $service = (new GoogleTagIntegration())->generateServices(['service_mapping' => [
            'google_tag_analytics' => ['analytics_storage', 'ad_storage'],
        ]])[0];

        $this->assertSame('Our analytics description. Stores information for advertising.', $service['description']);
    }
}

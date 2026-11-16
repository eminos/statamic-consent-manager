<?php

use Eminos\StatamicConsentManager\Integrations\GoogleTagIntegration;
use Statamic\Facades\Parse;

afterEach(function () {
    $this->cleanupTestConfig();
});

it('shows distinct Google Tag purposes under custom category handles', function () {
    $mapping = [
        ['category' => 'experience', 'storage_types' => ['functionality_storage']],
        ['category' => 'measurement', 'storage_types' => ['analytics_storage']],
        ['category' => 'advertising', 'storage_types' => ['ad_storage', 'ad_user_data', 'ad_personalization']],
    ];
    $this->createTestConfig([
        'categories' => array_map(fn ($item) => ['handle' => $item['category'], 'name' => ucfirst($item['category'])], $mapping),
        'services' => [],
        'integrations' => ['google_tag' => [
            'enabled' => true,
            'measurement_id' => 'G-EXAMPLE',
            'consent_mapping' => $mapping,
        ]],
    ]);

    $output = (string) Parse::template('{{ consent_manager:dialog }}');
    expect($output)
        ->toContain('Google Tag (Functionality)')
        ->toContain('Google Tag (Analytics)')
        ->toContain('Google Tag (Advertising)')
        ->toContain('Stores preferences for website features, such as language settings.')
        ->not->toContain((new GoogleTagIntegration())->description());

    foreach (['experience', 'measurement', 'advertising'] as $handle) {
        expect(substr_count($output, 'data-consent-manager-service-toggle="google_tag_'.$handle.'"'))->toBe(1);
    }

    preg_match('/<script type="application\/json" data-consent-manager="payload">(.*?)<\/script>/s', $output, $matches);
    $payload = json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);
    expect(array_column($payload['services'], 'handle'))->toBe(['google_tag_experience', 'google_tag_measurement', 'google_tag_advertising']);
    expect($payload['integrations']['google_tag']['service_mapping'])->toBe([
        'google_tag_experience' => ['functionality_storage'],
        'google_tag_measurement' => ['analytics_storage'],
        'google_tag_advertising' => ['ad_storage', 'ad_user_data', 'ad_personalization'],
    ]);
});

it('describes each mapped consent type', function (string $storage, string $purpose, string $description) {
    $service = (new GoogleTagIntegration())->generateServices(['service_mapping' => ['google_tag_custom' => [$storage]]])[0];

    expect($service['name'])->toBe('Google Tag ('.$purpose.')');
    expect($service['description'])->toBe($description);
    expect($service['handle'])->toBe('google_tag_custom');
    expect($service['storage_types'])->toBe([$storage]);
})->with([
    ['functionality_storage', 'Functionality', 'Stores preferences for website features, such as language settings.'],
    ['personalization_storage', 'Personalization', 'Stores information for personalized content, such as video recommendations.'],
    ['security_storage', 'Security', 'Stores information for authentication, fraud prevention, and other security features.'],
    ['analytics_storage', 'Analytics', 'Stores information to measure website visits and usage.'],
    ['ad_storage', 'Advertising', 'Stores information for advertising.'],
    ['ad_user_data', 'Advertising', 'Allows user data to be sent to Google for advertising.'],
    ['ad_personalization', 'Advertising', 'Allows personalized advertising.'],
]);

it('combines mixed purposes without repeating advertising labels or descriptions', function () {
    $storageTypes = ['analytics_storage', 'ad_storage', 'ad_user_data', 'ad_personalization', 'analytics_storage'];
    $service = (new GoogleTagIntegration())->generateServices(['service_mapping' => ['google_tag_custom' => $storageTypes]])[0];

    expect($service['name'])->toBe('Google Tag (Analytics, Advertising)');
    expect(substr_count($service['description'], 'Stores information to measure website visits and usage.'))->toBe(1);
    expect($service['description'])->toContain('Allows user data to be sent to Google for advertising.', 'Allows personalized advertising.');
    expect($service['storage_types'])->toBe($storageTypes);
});

it('falls back to the integration description for unrecognized consent types', function () {
    $integration = new GoogleTagIntegration();
    $service = $integration->generateServices(['service_mapping' => ['google_tag_custom' => ['future_storage']]])[0];

    expect($service['name'])->toBe($integration->name());
    expect($service['description'])->toBe($integration->description());
});

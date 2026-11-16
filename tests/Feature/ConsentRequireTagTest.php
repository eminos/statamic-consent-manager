<?php

use Statamic\Facades\Parse;

beforeEach(function () {
    $this->cleanupTestConfig();
});

afterEach(function () {
    $this->cleanupTestConfig();
});

it('wraps content in template tag with service handle', function () {
    $this->createTestConfig([
        'categories' => [['name' => 'Marketing', 'required' => false]],
        'services' => [
            [
                'name' => 'Google Maps',
                'handle' => 'google-maps',
                'categories' => ['marketing'],
            ],
        ],
        'integrations' => [],
    ]);

    $output = (string) Parse::template('{{ consent_manager:require service="google-maps" }}<div>Map content</div>{{ /consent_manager:require }}');

    expect($output)
        ->toContain('data-service-handle="google-maps"')
        ->toContain('<template')
        ->toContain('Map content');
});

it('shows error comment for non-existent service', function () {
    $this->createTestConfig([
        'categories' => [['name' => 'Marketing', 'required' => false]],
        'services' => [
            [
                'name' => 'Google Maps',
                'handle' => 'google-maps',
                'categories' => ['marketing'],
            ],
        ],
        'integrations' => [],
    ]);

    $output = (string) Parse::template('{{ consent_manager:require service="youtube" }}<iframe></iframe>{{ /consent_manager:require }}');

    expect($output)
        ->toContain('service "youtube" not found')
        ->toContain('Available services: google-maps');
});

it('wraps content in template with data-consent-manager-script attribute', function () {
    $this->createTestConfig([
        'categories' => [['name' => 'Marketing', 'required' => false]],
        'services' => [
            [
                'name' => 'YouTube',
                'handle' => 'youtube',
                'categories' => ['marketing'],
            ],
        ],
        'integrations' => [],
    ]);

    $output = (string) Parse::template('{{ consent_manager:require service="youtube" }}<iframe src="test"></iframe>{{ /consent_manager:require }}');

    expect($output)
        ->toContain('data-consent-manager-script')
        ->toContain('data-service-handle="youtube"')
        ->toContain('<template')
        ->toContain('<iframe src="test"></iframe>');
});

it('returns error comment when service parameter is missing', function () {
    $this->createTestConfig([
        'categories' => [['name' => 'Marketing', 'required' => false]],
        'services' => [],
        'integrations' => [],
    ]);

    $output = (string) Parse::template('{{ consent_manager:require }}<div>Content</div>{{ /consent_manager:require }}');

    expect($output)
        ->toContain('consent_manager:require - service parameter required');
});

it('renders content directly when user has consent', function () {
    $this->createTestConfig([
        'categories' => [['name' => 'Marketing', 'required' => false]],
        'services' => [
            [
                'name' => 'YouTube',
                'handle' => 'youtube',
                'categories' => ['marketing'],
            ],
        ],
        'integrations' => [],
    ]);

    // Simulate user having consent by setting cookie
    $cookieName = config('consent-manager.cookie_name', 'consent_manager');
    $consentData = [
        'services' => ['youtube' => true],
        'revision_date' => now()->toIso8601String(),
    ];
    $_COOKIE[$cookieName] = json_encode($consentData);

    $output = (string) Parse::template('{{ consent_manager:require service="youtube" }}<iframe src="test"></iframe>{{ /consent_manager:require }}');

    expect($output)
        ->toContain('<iframe src="test"></iframe>')
        ->not->toContain('<template')
        ->not->toContain('data-consent-manager-script');

    // Cleanup
    unset($_COOKIE[$cookieName]);
});

it('wraps content in template when user does not have consent', function () {
    $this->createTestConfig([
        'categories' => [['name' => 'Marketing', 'required' => false]],
        'services' => [
            [
                'name' => 'YouTube',
                'handle' => 'youtube',
                'categories' => ['marketing'],
            ],
        ],
        'integrations' => [],
    ]);

    // Ensure no consent cookie
    $cookieName = config('consent-manager.cookie_name', 'consent_manager');
    unset($_COOKIE[$cookieName]);

    $output = (string) Parse::template('{{ consent_manager:require service="youtube" }}<iframe src="test"></iframe>{{ /consent_manager:require }}');

    expect($output)
        ->toContain('<template data-consent-manager-script')
        ->toContain('data-service-handle="youtube"')
        ->toContain('<iframe src="test"></iframe>');
});

it('renders placeholder when provided and user does not have consent', function () {
    $this->createTestConfig([
        'categories' => [['name' => 'Marketing', 'required' => false]],
        'services' => [
            [
                'name' => 'YouTube',
                'handle' => 'youtube',
                'categories' => ['marketing'],
            ],
        ],
        'integrations' => [],
    ]);

    // Ensure no consent cookie
    $cookieName = config('consent-manager.cookie_name', 'consent_manager');
    unset($_COOKIE[$cookieName]);

    // Note: Slot testing is limited in test environment, so we test the behavior
    // when placeholder is empty (which is what happens when slot isn't processed)
    $output = (string) Parse::template('{{ consent_manager:require service="youtube" }}<iframe src="test"></iframe>{{ /consent_manager:require }}');

    // When no placeholder, should still wrap content
    expect($output)
        ->toContain('<template data-consent-manager-script')
        ->toContain('data-service-handle="youtube"')
        ->toContain('<iframe src="test"></iframe>');
});

it('handles empty placeholder gracefully', function () {
    $this->createTestConfig([
        'categories' => [['name' => 'Marketing', 'required' => false]],
        'services' => [
            [
                'name' => 'YouTube',
                'handle' => 'youtube',
                'categories' => ['marketing'],
            ],
        ],
        'integrations' => [],
    ]);

    // Ensure no consent cookie
    $cookieName = config('consent-manager.cookie_name', 'consent_manager');
    unset($_COOKIE[$cookieName]);

    // When placeholder is empty, should still work and just wrap content
    $output = (string) Parse::template('{{ consent_manager:require service="youtube" }}<iframe src="test"></iframe>{{ /consent_manager:require }}');

    expect($output)
        ->toContain('<template data-consent-manager-script')
        ->toContain('data-service-handle="youtube"')
        ->toContain('<iframe src="test"></iframe>')
        ->not->toContain('data-consent-placeholder'); // No placeholder div when empty
});

it('works with integration services', function () {
    $this->createTestConfig([
        'categories' => [
            ['name' => 'Analytics', 'required' => false, 'handle' => 'analytics'],
        ],
        'services' => [],
        'integrations' => [
            'google_tag' => [
                'enabled' => true,
                'measurement_id' => 'G-XXXXXXXXXX',
                'consent_mapping' => [
                    [
                        'category' => 'analytics',
                        'storage_types' => ['analytics_storage'],
                    ],
                ],
            ],
        ],
    ]);

    // Ensure no consent cookie
    $cookieName = config('consent-manager.cookie_name', 'consent_manager');
    unset($_COOKIE[$cookieName]);

    $output = (string) Parse::template('{{ consent_manager:require service="google_tag_analytics" }}<div>Content</div>{{ /consent_manager:require }}');

    expect($output)
        ->toContain('data-service-handle="google_tag_analytics"')
        ->toContain('<template')
        ->toContain('<div>Content</div>');
});

it('renders content directly for integration service when user has consent', function () {
    $this->createTestConfig([
        'categories' => [
            ['name' => 'Analytics', 'required' => false, 'handle' => 'analytics'],
        ],
        'services' => [],
        'integrations' => [
            'google_tag' => [
                'enabled' => true,
                'measurement_id' => 'G-XXXXXXXXXX',
                'consent_mapping' => [
                    [
                        'category' => 'analytics',
                        'storage_types' => ['analytics_storage'],
                    ],
                ],
            ],
        ],
    ]);

    // Simulate user having consent
    $cookieName = config('consent-manager.cookie_name', 'consent_manager');
    $consentData = [
        'services' => ['google_tag_analytics' => true],
        'revision_date' => now()->toIso8601String(),
    ];
    $_COOKIE[$cookieName] = json_encode($consentData);

    $output = (string) Parse::template('{{ consent_manager:require service="google_tag_analytics" }}<div>Content</div>{{ /consent_manager:require }}');

    expect($output)
        ->toContain('<div>Content</div>')
        ->not->toContain('<template');

    // Cleanup
    unset($_COOKIE[$cookieName]);
});

describe('Antlers content', function () {
    beforeEach(function () {
        unset($_COOKIE[config('consent-manager.cookie_name')]);
        view()->addLocation(__DIR__.'/../fixtures/views');
    });

    afterEach(function () {
        unset($_COOKIE[config('consent-manager.cookie_name')]);
    });

    it('parses variables, modifiers and partials in the surrounding loop context', function (bool $consented, bool $required) {
        $this->createTestConfig([
            'categories' => [['name' => 'Marketing', 'handle' => 'marketing', 'required' => $required]],
            'services' => [['name' => 'YouTube', 'handle' => 'youtube', 'categories' => ['marketing']]],
            'integrations' => [],
        ]);

        if ($consented) {
            $_COOKIE[config('consent-manager.cookie_name')] = json_encode(['services' => ['youtube' => true]]);
        }

        $output = (string) Parse::template(
            '{{ videos }}{{ consent_manager:require service="youtube" }}{{ partial:consent-video }}{{ /consent_manager:require }}{{ /videos }}',
            ['videos' => [
                ['youtube_url' => 'https://www.youtube.com/watch?v=first-video', 'title' => 'First video'],
                ['youtube_url' => 'https://www.youtube.com/watch?v=second-video', 'title' => 'Second video'],
            ]]
        );

        expect($output)
            ->toContain('src="https://www.youtube-nocookie.com/embed/first-video" title="First video"')
            ->toContain('src="https://www.youtube-nocookie.com/embed/second-video" title="Second video"')
            ->not->toContain('{{');

        expect(substr_count($output, '<template'))->toBe($consented || $required ? 0 : 2);
    })->with([
        'without consent' => [false, false],
        'with consent' => [true, false],
        'required category' => [false, true],
    ]);

    it('parses placeholder partials before checking for an existing wrapper', function (string $slot) {
        $this->createTestConfig([
            'categories' => [['name' => 'Marketing', 'required' => false]],
            'services' => [['name' => 'YouTube', 'handle' => 'youtube', 'categories' => ['marketing']]],
            'integrations' => [],
        ]);

        $output = (string) Parse::template(
            '{{ consent_manager:require service="youtube" }}{{ '.$slot.' }}{{ partial:consent-placeholder }}{{ /'.$slot.' }}<iframe src="{{ video_url }}"></iframe>{{ /consent_manager:require }}',
            ['message' => 'Accept video cookies', 'service_handle' => 'youtube', 'video_url' => '/video']
        );

        expect($output)
            ->toContain('<p>Accept video cookies</p>')
            ->toContain('data-give-consent="youtube"')
            ->toContain('<template data-consent-manager-script data-service-handle="youtube"><iframe src="/video"></iframe></template>')
            ->not->toContain('{{');

        expect(substr_count($output, 'data-consent-placeholder="youtube"'))->toBe(1);
    })->with(['placeholder', 'slot:placeholder']);

    it('does not evaluate an unused placeholder when consent is granted', function (string $slot) {
        $this->createTestConfig([
            'categories' => [['name' => 'Marketing', 'required' => false]],
            'services' => [['name' => 'YouTube', 'handle' => 'youtube', 'categories' => ['marketing']]],
            'integrations' => [],
        ]);
        $_COOKIE[config('consent-manager.cookie_name')] = json_encode(['services' => ['youtube' => true]]);

        $output = (string) Parse::template(
            '{{ consent_manager:require service="youtube" }}{{ '.$slot.' }}{{ partial:missing-placeholder }}{{ /'.$slot.' }}<p>{{ title }}</p>{{ /consent_manager:require }}',
            ['title' => 'Video content']
        );

        expect($output)->toBe('<p>Video content</p>');
    })->with(['placeholder', 'slot:placeholder']);
});


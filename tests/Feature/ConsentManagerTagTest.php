<?php

use Statamic\Facades\Parse;

afterEach(function () {
    $this->cleanupTestConfig();
});

it('renders dialog with categories and services', function () {
    $this->createTestConfig([
        'categories' => [
            ['name' => 'Essential', 'required' => true, 'description' => 'Required for site functionality'],
            ['name' => 'Analytics', 'required' => false, 'description' => 'Usage tracking'],
        ],
        'services' => [
            [
                'name' => 'Google Analytics',
                'handle' => 'google-analytics',
                'description' => 'Website analytics',
                'categories' => ['analytics'],
            ],
        ],
        'integrations' => [],
    ]);

    $output = (string) Parse::template('{{ consent_manager:dialog }}');

    expect($output)
        ->toContain('data-consent-manager="payload"')
        ->toContain('Essential')
        ->toContain('Analytics')
        ->toContain('Google Analytics')
        ->toContain('google-analytics');
});

it('normalizes category and service handles', function () {
    $this->createTestConfig([
        'categories' => [
            ['name' => 'Marketing & Tracking', 'required' => false],
        ],
        'services' => [
            [
                'name' => 'Google Analytics 4',
                'categories' => ['marketing-tracking'],
            ],
        ],
        'integrations' => [],
    ]);

    $output = (string) Parse::template('{{ consent_manager:dialog }}');

    expect($output)
        ->toContain('marketing-tracking')
        ->toContain('google-analytics-4');
});

it('includes consent revision date in payload', function () {
    $revisionDate = '2024-11-11T15:30:00+00:00';
    
    $this->createTestConfig([
        'categories' => [],
        'services' => [],
        'integrations' => [],
        'consent_revision_date' => $revisionDate,
    ]);

    $output = (string) Parse::template('{{ consent_manager:dialog }}');

    expect($output)->toContain($revisionDate);
});

it('renders head scripts with correct placement', function () {
    $this->createTestConfig([
        'categories' => [['name' => 'Analytics', 'required' => false]],
        'services' => [
            [
                'name' => 'Head Script',
                'handle' => 'head-script',
                'categories' => ['analytics'],
                'scripts' => [
                    [
                        'placement' => 'head',
                        'script' => '<script>console.log("head");</script>',
                    ],
                ],
            ],
        ],
        'integrations' => [],
    ]);

    $output = (string) Parse::template('{{ consent_manager:head }}');

    expect($output)
        ->toContain('data-consent-manager-script')
        ->toContain('data-service-handle="head-script"')
        ->toContain('console.log("head")');
});

it('renders body scripts with correct placement', function () {
    $this->createTestConfig([
        'categories' => [['name' => 'Analytics', 'required' => false]],
        'services' => [
            [
                'name' => 'Body Script',
                'handle' => 'body-script',
                'categories' => ['analytics'],
                'scripts' => [
                    [
                        'placement' => 'body',
                        'script' => '<script>console.log("body");</script>',
                    ],
                ],
            ],
        ],
        'integrations' => [],
    ]);

    $output = (string) Parse::template('{{ consent_manager:body }}');

    expect($output)
        ->toContain('data-consent-manager-script')
        ->toContain('data-service-handle="body-script"')
        ->toContain('console.log("body")');
});

it('does not render head scripts in body tag', function () {
    $this->createTestConfig([
        'categories' => [['name' => 'Analytics', 'required' => false]],
        'services' => [
            [
                'name' => 'Head Script',
                'handle' => 'head-script',
                'categories' => ['analytics'],
                'scripts' => [
                    [
                        'placement' => 'head',
                        'script' => '<script>console.log("head only")</script>',
                    ],
                ],
            ],
        ],
        'integrations' => [],
    ]);

    $output = (string) Parse::template('{{ consent_manager:body }}');

    expect($output)->not->toContain('head only');
});

it('does not render body scripts in head tag', function () {
    $this->createTestConfig([
        'categories' => [['name' => 'Analytics', 'required' => false]],
        'services' => [
            [
                'name' => 'Body Script',
                'handle' => 'body-script',
                'categories' => ['analytics'],
                'scripts' => [
                    [
                        'placement' => 'body',
                        'script' => '<script>console.log("body only")</script>',
                    ],
                ],
            ],
        ],
        'integrations' => [],
    ]);

    $output = (string) Parse::template('{{ consent_manager:head }}');

    expect($output)->not->toContain('body only');
});

it('renders multiple scripts for a service', function () {
    $this->createTestConfig([
        'categories' => [['name' => 'Analytics', 'required' => false]],
        'services' => [
            [
                'name' => 'Multi Script',
                'handle' => 'multi-script',
                'categories' => ['analytics'],
                'scripts' => [
                    [
                        'placement' => 'head',
                        'script' => '<script>console.log("first");</script>',
                    ],
                    [
                        'placement' => 'head',
                        'script' => '<script>console.log("second");</script>',
                    ],
                ],
            ],
        ],
        'integrations' => [],
    ]);

    $output = (string) Parse::template('{{ consent_manager:head }}');

    expect($output)
        ->toContain('console.log("first")')
        ->toContain('console.log("second")');
});

it('skips rendering in live preview when disabled', function () {
    config()->set('consent-manager.enable_in_live_preview', false);

    $this->createTestConfig([
        'categories' => [['name' => 'Essential', 'required' => true]],
        'services' => [],
        'integrations' => [],
    ]);

    request()->merge(['live-preview' => true]);
    $output = (string) Parse::template('{{ consent_manager:dialog }}');

    expect($output)->toBe('');
    
    request()->merge(['live-preview' => false]);
});

it('renders in live preview when enabled', function () {
    config()->set('consent-manager.enable_in_live_preview', true);

    $this->createTestConfig([
        'categories' => [['name' => 'Essential', 'required' => true]],
        'services' => [],
        'integrations' => [],
    ]);

    request()->merge(['live-preview' => true]);
    $output = (string) Parse::template('{{ consent_manager:dialog }}');

    expect($output)
        ->toContain('data-consent-manager="payload"')
        ->toContain('Essential');
        
    request()->merge(['live-preview' => false]);
});


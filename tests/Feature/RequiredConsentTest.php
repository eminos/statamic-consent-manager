<?php

use Statamic\Facades\Parse;

beforeEach(function () {
    unset($_COOKIE[config('consent-manager.cookie_name')]);
    $this->createTestConfig([
        'categories' => [
            ['name' => 'Necessary', 'required' => true],
            ['name' => 'Analytics', 'required' => false],
        ],
        'services' => [
            ['name' => 'Required', 'handle' => 'required', 'categories' => ['necessary'], 'scripts' => [['placement' => 'head', 'script' => '<script>requiredFeature()</script>']]],
            ['name' => 'Mixed', 'handle' => 'mixed', 'categories' => ['necessary', 'analytics'], 'scripts' => [['placement' => 'head', 'script' => '<script>mixedFeature()</script>']]],
            ['name' => 'Optional', 'handle' => 'optional', 'categories' => ['analytics'], 'scripts' => [['placement' => 'head', 'script' => '<script>optionalFeature()</script>']]],
        ],
        'integrations' => [],
    ]);
});

afterEach(function () {
    unset($_COOKIE[config('consent-manager.cookie_name')]);
    $this->cleanupTestConfig();
});

it('renders required scripts directly without consent or with an old denied preference', function (bool $hasCookie) {
    if ($hasCookie) {
        $_COOKIE[config('consent-manager.cookie_name')] = json_encode(['services' => ['required' => false, 'mixed' => false, 'optional' => false]]);
    }

    $output = (string) Parse::template('{{ consent_manager:head }}');
    expect($output)
        ->toContain('<script>requiredFeature()</script>', '<script>mixedFeature()</script>')
        ->not->toContain('data-service-handle="required"', 'data-service-handle="mixed"')
        ->toContain('data-service-handle="optional"');
})->with([false, true]);

it('renders required conditional content even with an old denied preference', function (string $service) {
    $_COOKIE[config('consent-manager.cookie_name')] = json_encode(['services' => [$service => false]]);

    $output = (string) Parse::template('{{ consent_manager:require service="'.$service.'" }}<p>{{ title }}</p>{{ /consent_manager:require }}', ['title' => 'Required content']);
    expect($output)->toBe('<p>Required content</p>');
})->with(['required', 'mixed']);

it('continues to defer optional scripts until consent is granted', function (bool $consented) {
    $_COOKIE[config('consent-manager.cookie_name')] = json_encode(['services' => ['optional' => $consented]]);
    $output = (string) Parse::template('{{ consent_manager:head }}');

    expect(str_contains($output, 'data-service-handle="optional"'))->toBe(! $consented);
})->with([false, true]);

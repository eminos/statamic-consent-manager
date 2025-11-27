<?php

use Illuminate\Support\Facades\Config;
use Statamic\Facades\Parse;

afterEach(function () {
    $this->cleanupTestConfig();
});

it('includes config values in dialog payload', function () {
    Config::set('consent-manager.cookie_name', 'custom_consent');
    Config::set('consent-manager.cookie_duration_days', 365);
    Config::set('consent-manager.debug', true);

    $this->createTestConfig([
        'categories' => [],
        'services' => [],
        'integrations' => [],
    ]);

    $output = (string) Parse::template('{{ consent_manager:dialog }}');

    expect($output)
        ->toContain('"cookie_name":"custom_consent"')
        ->toContain('"cookie_expiry":365')
        ->toContain('"debug":true');
});

it('respects enable_in_live_preview config', function () {
    Config::set('consent-manager.enable_in_live_preview', false);

    $this->createTestConfig([
        'categories' => [['name' => 'Essential', 'required' => true]],
        'services' => [],
        'integrations' => [],
    ]);

    request()->merge(['live-preview' => true]);
    $output = (string) Parse::template('{{ consent_manager:dialog }}');

    expect($output)->toBe('');
});


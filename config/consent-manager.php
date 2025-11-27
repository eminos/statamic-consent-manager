<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cookie Name
    |--------------------------------------------------------------------------
    |
    | The name of the cookie used to store user consent preferences.
    | Change this if you need to avoid conflicts with other cookies.
    |
    */

    'cookie_name' => env('CONSENT_MANAGER_COOKIE_NAME', 'consent_manager'),

    /*
    |--------------------------------------------------------------------------
    | Cookie Duration (Days)
    |--------------------------------------------------------------------------
    |
    | How long user consent preferences are stored before requiring re-consent.
    | GDPR recommends 6-12 months. Default is 180 days (6 months).
    |
    */

    'cookie_duration_days' => env('CONSENT_MANAGER_COOKIE_DAYS', 180),

    /*
    |--------------------------------------------------------------------------
    | Cookie Path
    |--------------------------------------------------------------------------
    |
    | The path for which the cookie is available. Use '/' for site-wide availability.
    |
    */

    'cookie_path' => '/',

    /*
    |--------------------------------------------------------------------------
    | Cookie Domain
    |--------------------------------------------------------------------------
    |
    | The domain for which the cookie is available. Set to null for current domain,
    | or specify a domain like '.example.com' to share cookies across subdomains.
    |
    */

    'cookie_domain' => env('CONSENT_MANAGER_COOKIE_DOMAIN', null),

    /*
    |--------------------------------------------------------------------------
    | Cookie Secure
    |--------------------------------------------------------------------------
    |
    | Whether the cookie should only be transmitted over HTTPS.
    | Automatically enabled in production environments for security.
    |
    */

    'cookie_secure' => env('CONSENT_MANAGER_COOKIE_SECURE', env('APP_ENV') === 'production'),

    /*
    |--------------------------------------------------------------------------
    | Cookie SameSite
    |--------------------------------------------------------------------------
    |
    | The SameSite attribute for the cookie. Options: 'lax', 'strict', 'none'.
    | 'lax' is recommended for most use cases and provides a good balance
    | between security and functionality.
    |
    */

    'cookie_same_site' => env('CONSENT_MANAGER_COOKIE_SAMESITE', 'lax'),

    /*
    |--------------------------------------------------------------------------
    | Enable in Live Preview
    |--------------------------------------------------------------------------
    |
    | Whether the consent manager should be active in Statamic's Live Preview mode.
    | When disabled (default), the consent dialog and tracking scripts won't load
    | during content editing for a cleaner preview experience.
    | Enable this if you need to test tracking integrations in Live Preview.
    |
    */

    'enable_in_live_preview' => env('CONSENT_MANAGER_LIVE_PREVIEW', false),

    /*
    |--------------------------------------------------------------------------
    | Debug Mode
    |--------------------------------------------------------------------------
    |
    | Enable console logging for debugging consent manager behavior.
    | Automatically uses Laravel's APP_DEBUG setting.
    |
    */

    'debug' => env('APP_DEBUG', false),

];

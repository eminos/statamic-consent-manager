# Changelog

All notable changes to Statamic Consent Manager will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.1.0] - 2026-11-16

### Added
- Purpose-specific Google Tag service labels and translated descriptions based on the mapped consent types (#3).
- Browser regression coverage for stylesheet isolation and required-service consent behavior.

### Fixed
- Parse Antlers variables, modifiers, and partials inside `consent_manager:require`, including both placeholder syntaxes, while preserving the surrounding context (#4).
- Isolate the bundled Tailwind reset, utilities, theme variables, and custom property registrations from the host website (#5).
- Keep required services enabled through Reject All, saving preferences, stale cookies, and Google consent updates, including services assigned to both required and optional categories.
- Render required scripts consistently on the server without waiting for a consent cookie.
- Merge published translation overrides with package defaults and preserve customized legacy Google Tag descriptions.

### Upgrade Notes
- PHP and Statamic requirements remain unchanged: PHP 8.3+ and Statamic 6.
- Compiled frontend assets and their Vite manifest are included. Assets publish automatically during the Statamic install/update process; no npm build is needed on the consuming site.
- Existing dialog classes, data attributes, service handles, and consent cookie structure are preserved.
- Sites using their own Tailwind v4 build can still remove the bundled stylesheet after including the dialog classes and placeholder helper in their build. See the customization documentation.
- Required services now remain active regardless of previously denied cookie values. Optional services remain subject to consent.

## [2.0.2] - 2026-05-25

### Fixed
- Fix PHP 8.4 deprecation warning from implicit nullable controller parameter.

## [2.0.1] - 2026-02-19

### Fixed
- Fix automatic asset publishing after installation.

## [2.0.0] - 2026-02-06

### Changed
- Statamic 6 support (Vue 3)

## [1.0.0] - 2025-12-18

### Added
- Initial release
- Service-level consent management with category organization
- Control Panel interface for managing categories, services, and tracking scripts
- Built-in integrations: Google Tag (Consent Mode v2), Meta Pixel, LinkedIn Insight Tag
- Custom script support with conditional loading based on consent
- Native HTML `<dialog>` elements for accessibility
- Tailwind CSS styled dialogs (fully customizable)
- Permission-based Control Panel access
- Configurable cookie settings via config file
- Live Preview mode control
- Consent versioning with ISO 8601 timestamps
- "Save & Require Re-consent" functionality for GDPR compliance
- Required categories (automatically consented, cannot be disabled)
- Conditional content rendering with `{{ consent_manager:require }}` tag
- JavaScript API for programmatic consent management
- Multi-language support via Laravel translation files
- Debug mode tied to `APP_DEBUG`
- Zero dependencies (vanilla JavaScript)

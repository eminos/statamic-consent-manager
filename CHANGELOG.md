# Changelog

All notable changes to Statamic Consent Manager will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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

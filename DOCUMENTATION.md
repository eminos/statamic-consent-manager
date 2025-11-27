# Consent Manager Documentation

Complete guide for installing, configuring, and using the Statamic Consent Manager addon.

## Table of Contents

- [Installation](#installation)
- [Configuration](#configuration)
- [Control Panel Setup](#control-panel-setup)
- [Usage](#usage)
- [API Reference](#api-reference)
- [Customization](#customization)

## Installation

### Requirements

- Statamic 5.0 or higher
- PHP 8.2 or higher
- A valid license from the Statamic Marketplace

### Install via Composer

```bash
composer require eminos/statamic-consent-manager
```

The addon automatically publishes its assets (CSS and JavaScript) to `public/vendor/statamic-consent-manager/` during installation.

**Important:** After installing, be sure to configure and save the Consent Manager configuration in the Control Panel. This action creates the required content file; without it, the consent manager will not appear on your frontend.

### Publish Views (Optional)

Customize the consent dialog appearance:

```bash
php artisan vendor:publish --tag=consent-manager-views
```

Views will be published to `resources/views/vendor/statamic-consent-manager/dialog/`

Customize the templates for built-in integrations (Google Tag, Meta Pixel, LinkedIn):

```bash
php artisan vendor:publish --tag=consent-manager-integrations
```

Integration views will be published to `resources/views/vendor/statamic-consent-manager/integrations/`

### Publish Config (Optional)

Customize cookie settings and behavior:

```bash
php artisan vendor:publish --tag=consent-manager-config
```

Config file will be published to `config/consent-manager.php`

You can customize:
- Cookie name and duration
- Cookie domain, path, secure, and SameSite attributes
- Whether consent manager loads in Live Preview mode
- Debug mode settings

### Publish Translations (Optional)

Customize dialog text and button labels:

```bash
php artisan vendor:publish --tag=consent-manager-lang
```

Translation files will be published to `lang/vendor/statamic-consent-manager/`

Edit `lang/vendor/statamic-consent-manager/en/consent-manager.php` to customize:
- Dialog headlines and body text
- Button labels
- And more

## Configuration

All consent manager settings are stored in `content/consent-manager.yaml`, keeping your configuration in version control alongside your other content.

### Permissions

The addon registers a `manage consent manager` permission in Statamic. By default, only Super Users can access the Consent Manager in the Control Panel.

To grant access to other users or roles:
1. Navigate to **Users > Roles** in the Statamic Control Panel
2. Edit a role (e.g., "Editor")
3. Enable the **"Manage Consent Manager"** permission
4. Save the role

Users with this permission can:
- Configure consent categories and services
- Add and manage tracking scripts
- Enable and configure integrations
- Require re-consent when policies change

### Cookie Settings

Configure cookie behavior in `config/consent-manager.php`:

```php
return [
    // Cookie name for storing consent
    'cookie_name' => env('CONSENT_MANAGER_COOKIE_NAME', 'consent_manager'),
    
    // Cookie duration in days
    'cookie_duration_days' => env('CONSENT_MANAGER_COOKIE_DURATION', 180),
    
    // Cookie path
    'cookie_path' => '/',
    
    // Cookie domain (null = current domain)
    'cookie_domain' => null,
    
    // Only send cookie over HTTPS
    'cookie_secure' => true,
    
    // SameSite attribute
    'cookie_same_site' => 'Lax',
    
    // Enable consent manager in Live Preview mode
    'enable_in_live_preview' => false,
    
    // Debug mode (uses APP_DEBUG by default)
    'debug' => env('APP_DEBUG', false),
];
```

## Control Panel Setup

### Accessing the Consent Manager

1. Navigate to **Tools > Consent Manager** in the Statamic Control Panel
2. You'll see the configuration interface with categories, services, and integrations

### Managing Categories

Categories organize your tracking services into logical groups. The addon includes these default categories:

| Category | Required | Description |
|----------|----------|-------------|
| **Essential** | ✅ Yes | Security, session management, core functionality |
| **Functional** | ❌ No | User preferences, personalization |
| **Analytics** | ❌ No | Usage statistics, performance monitoring |
| **Marketing** | ❌ No | Advertising, remarketing, conversion tracking |

**Services in required categories** are automatically consented and cannot be disabled by users. You can add, remove, or modify categories directly in the Control Panel.

### Managing Services

Services represent individual tracking scripts or third-party tools that require consent.

**To add a custom service:**

1. Click **"Add Service"** in the Control Panel
2. Fill in the service details:
   - **Name**: Display name (e.g., "Hotjar")
   - **Description**: What this service does
   - **Category**: Which category it belongs to
   - **Placement**: Load in `<head>` or before `</body>`
   - **Scripts**: The actual tracking code
3. Save the configuration

**Example service configuration:**

```yaml
name: Hotjar
description: Session recording and heatmap tracking
category: analytics
placement: body  # or "head"
scripts:
  - |
    <script>
      (function(h,o,t,j,a,r){
        h.hj=h.hj||function(){(h.hj.q=h.hj.q||[]).push(arguments)};
        h._hjSettings={hjid:YOUR_HOTJAR_ID,hjsv:6};
        a=o.getElementsByTagName('head')[0];
        r=o.createElement('script');r.async=1;
        r.src=t+h._hjSettings.hjid+j+h._hjSettings.hjsv;
        a.appendChild(r);
      })(window,document,'https://static.hotjar.com/c/hotjar-','.js?sv=');
    </script>
```

### Built-in Integrations

Some tracking services require more sophisticated integration. Use these pre-built integrations that handle the implementation details for you.

#### Google Tag (Consent Mode v2)

Configure in Control Panel:

- **Container ID**: Your Google Tag container ID (e.g., `GTM-XXXXXXX` or `G-XXXXXXX`)
- **Consent Mapping**: Map consent categories to Google's storage types:
  - `ad_storage` → Marketing category
  - `analytics_storage` → Analytics category
  - `functionality_storage` → Preferences category
  - etc.

The integration automatically sends consent updates via `gtag('consent', 'update', {...})`.

#### Meta Pixel

Configure in Control Panel:

- **Pixel ID**: Your Facebook Pixel ID
- **Required Category**: Assign to a consent category (typically Marketing)

The integration manages `fbq('consent', 'grant'|'revoke')` based on user consent.

#### LinkedIn Insight Tag

Configure in Control Panel:

- **Partner ID**: Your LinkedIn Partner ID
- **Required Category**: Assign to a consent category (typically Marketing)

### Consent Versioning

The addon tracks consent revisions using ISO 8601 timestamps. When you make significant changes to your privacy policy or tracking setup:

1. Click **"Save & Require Re-consent"** in the Control Panel
2. The `consent_revision_date` is updated automatically (e.g., `2024-11-11T15:30:00+00:00`)
3. All users will see the consent dialog again on their next visit, even if they previously provided consent
4. Users must provide fresh consent with the updated terms

This ensures GDPR compliance when your data processing practices change.

**When to use "Save & Require Re-consent":**
- Adding new tracking categories or services
- Changing data retention policies
- Updating your privacy policy significantly
- Adding new third-party data processors
- Changing the purpose of data collection

## Usage

### Basic Implementation

Add the consent dialog Antlers tags to your layout (typically in `layout.antlers.html`):

```antlers
<!DOCTYPE html>
<html>
<head>
    <title>{{ title }}</title>
    <link rel="stylesheet" href="{{ mix src='css/site.css' }}">
    
    {{# Load consent manager scripts in head #}}
    {{ consent_manager:head }}
</head>
<body>
    {{ template_content }}
    
    {{# Render consent dialog and body scripts #}}
    {{ consent_manager:dialog }}
    {{ consent_manager:body }}
</body>
</html>
```

### What Gets Rendered

Use all three tags together in your layout for full functionality:

- `{{ consent_manager:head }}`:
  - Renders integration and custom service scripts intended for the `<head>`, respecting consent strategies.
- `{{ consent_manager:dialog }}`:
  - Renders the JSON payload (configuration for the JavaScript) and the consent dialog UI markup.
  - Includes the main Consent Manager JavaScript via the dialog view.
- `{{ consent_manager:body }}`:
  - Renders integration and custom service scripts intended for placement before `</body>`, wrapped so that execution depends on consent.

### Triggering the Dialogs

Users can reopen the consent dialogs to change their preferences:

```antlers
{{# Show the banner #}}
<button onclick="window.consentManager.showBanner()">
    Show Consent Banner
</button>

{{# Show the preferences dialog #}}
<button onclick="window.consentManager.showPreferences()">
    Manage Cookie Preferences
</button>
```

### Conditional Content (Embeds)

Use the `{{ consent_manager:require }}` tag to conditionally render content that requires consent, like YouTube embeds or Google Maps.

**First, add the service in the Control Panel:**
1. Navigate to **Tools > Consent Manager > Services**
2. Add a new service (e.g., "YouTube") and assign it to a category (e.g., Marketing)
3. Leave the scripts field empty - you're only creating the service for consent tracking
4. Save the configuration

**Then use the tag in your templates:**

```antlers
{{ consent_manager:require service="youtube" }}
    <iframe src="https://www.youtube.com/embed/VIDEO_ID" 
            width="560" 
            height="315" 
            frameborder="0" 
            allowfullscreen>
    </iframe>
    
    {{ placeholder }}
        <div class="consent-placeholder">
            <p>This content requires consent to the YouTube service.</p>
            <button data-give-consent="youtube">
                Allow YouTube
            </button>
        </div>
    {{ /placeholder }}
{{ /consent_manager:require }}
```

**How it works:**
- If the user has already consented to the service, the content renders immediately
- If not, the placeholder is shown instead
- When the user clicks a button with `data-give-consent="youtube"`, consent is granted and the content loads without a page refresh
- The placeholder wrapper uses `display: contents` so it won't affect your layout

## API Reference

### JavaScript API

Access the consent manager globally:

```javascript
// Check if user has consented to a specific service
const hasYouTube = window.consentManager.getServiceConsent('youtube'); // true or false
const hasGoogleTag = window.consentManager.getServiceConsent('google_tag'); // true or false

// Grant consent to a specific service programmatically
window.consentManager.grantConsentToService('youtube');

// Get all consent data
const allConsent = window.consentManager.getAllConsent();
console.log(allConsent.services); // { youtube: true, google_tag: false, ... }
console.log(allConsent.revision_date); // ISO 8601 timestamp or null

// Show dialogs programmatically
window.consentManager.showBanner();      // Show the consent banner
window.consentManager.showPreferences(); // Show the preferences dialog

// Listen for consent changes
document.addEventListener('consent-manager:preferences-updated', (event) => {
    console.log('Consent updated:', event.detail.consentData);
    console.log('Integration states:', event.detail.integrations);
});
```

### Antlers Tags

#### `{{ consent_manager:head }}`

Renders tracking scripts that should be placed in the `<head>` section.

```antlers
<head>
    {{ consent_manager:head }}
</head>
```

#### `{{ consent_manager:dialog }}`

Renders the consent dialog UI and configuration payload.

```antlers
<body>
    {{ consent_manager:dialog }}
</body>
```

#### `{{ consent_manager:body }}`

Renders tracking scripts that should be placed before `</body>`.

```antlers
<body>
    {{ consent_manager:body }}
</body>
```

#### `{{ consent_manager:require }}`

Conditionally renders content based on consent to a specific service.

**Parameters:**
- `service` (required): The service identifier (slug)

**Example:**

```antlers
{{ consent_manager:require service="youtube" }}
    <iframe src="https://www.youtube.com/embed/VIDEO_ID"></iframe>
    
    {{ placeholder }}
        <div class="consent-placeholder">
            <p>Please consent to YouTube to view this content.</p>
            <button data-give-consent="youtube">Allow YouTube</button>
        </div>
    {{ /placeholder }}
{{ /consent_manager:require }}
```

**Note:** You can also use `{{ slot:placeholder }}` syntax if you prefer the explicit slot naming.

## Customization

### Custom Dialog Styling

Publish the views to customize the consent dialog appearance:

```bash
php artisan vendor:publish --tag=consent-manager-views
```

Then edit `resources/views/vendor/statamic-consent-manager/dialog/consent-dialog.antlers.html`

The dialog uses Tailwind CSS by default, but you can replace it with your own styling framework.

### Customizing Text and Labels

All dialog text, button labels, and descriptions are stored in translation files. Publish the language files to customize them:

```bash
php artisan vendor:publish --tag=consent-manager-lang
```

Then edit `lang/vendor/statamic-consent-manager/en/dialog.php` to customize:

- Dialog headline and body text
- Button labels (Accept All, Save Preferences, etc.)
- Category and integration descriptions
- Any other user-facing text

This allows you to translate the consent dialog into multiple languages or adjust the messaging to match your brand voice.

### Debug Mode

Enable debug logging by setting `APP_DEBUG=true` in your `.env` file:

```env
APP_DEBUG=true
```

**Console output:**
```
[Consent Manager] Debug mode enabled
[Consent Manager] Current revision date: 2024-11-11T15:30:00+00:00
[Consent Manager] Registered integration: google_tag
[Consent Manager] Registered integration: meta_pixel
[Consent Manager] Applying updates to 2 integration(s)
[Consent Manager] Saved consent with revision date: 2024-11-11T15:30:00+00:00
[Consent Manager] Re-consent required - revision date changed
```

## Support

For support, questions, or feature requests, please visit the GitHub repository.

## License

This is a commercial addon that requires a license for production use.

[Purchase a license on the Statamic Marketplace →](https://statamic.com/addons/kiwikiwi/consent-manager)

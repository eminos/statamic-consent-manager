# Contributing to Statamic Consent Manager

Thank you for your interest in contributing! This guide covers how to run, write, and troubleshoot tests for this addon.

## 🧪 Testing

This addon uses [Pest](https://pestphp.com/) for testing, which provides a beautiful and expressive testing experience.

### Installation

Install test dependencies:

```bash
composer install
```

### Running Tests

Run all tests:

```bash
composer test
```

Or use Pest directly:

```bash
./vendor/bin/pest
```

#### Run Specific Test Files

```bash
./vendor/bin/pest tests/Feature/ConsentManagerTagTest.php
```

#### Run Tests with Coverage

```bash
./vendor/bin/pest --coverage
```

#### Frontend Style Regression Tests

The browser tests build the shipped CSS and check isolation from a host Tailwind theme and a plain page, plus dialog toggle styles:

```bash
npm install
npx playwright install chromium
npm run test:frontend
```

To use an installed Chrome browser instead, run `PLAYWRIGHT_CHANNEL=chrome npm run test:frontend`.

#### Run Tests in Parallel

```bash
./vendor/bin/pest --parallel
```

### Test Structure

```
tests/
├── Pest.php                           # Pest configuration
├── TestCase.php                       # Base test case extending Statamic's AddonTestCase
├── Feature/
│   ├── ConsentManagerTagTest.php     # Tests for dialog, head, and body tags
│   ├── ConsentRequireTagTest.php     # Tests for conditional content rendering
│   └── ConfigTest.php                # Tests for configuration values
└── Unit/                             # Unit tests (if needed)
```

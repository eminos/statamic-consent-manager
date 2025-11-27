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

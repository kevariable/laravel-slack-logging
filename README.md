# Laravel Slack Logging

[![Latest Version on Packagist](https://img.shields.io/packagist/v/kevariable/laravel-slack-logging.svg?style=flat-square)](https://packagist.org/packages/kevariable/laravel-slack-logging)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/kevariable/laravel-slack-logging/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/kevariable/laravel-slack-logging/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/kevariable/laravel-slack-logging/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/kevariable/laravel-slack-logging/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/kevariable/laravel-slack-logging.svg?style=flat-square)](https://packagist.org/packages/kevariable/laravel-slack-logging)

Laravel Slack Logging is a package that sends exception notifications directly to a Slack channel using webhooks. It includes request payload in notifications, deduplicates repeated exceptions with escalating sleep intervals, and lets you skip specific exception classes or environments.

## Preview
<img width="691" alt="image" src="https://github.com/user-attachments/assets/b3911287-0f0d-4422-8b4e-bccb0607e04f" />

## Installation

You can install the package via Composer:

```bash
composer require kevariable/laravel-slack-logging
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="laravel-slack-logging-config"
```

## Usage

Add the Slack webhook URL to your `.env` file:

```env
SLACK_LOGGING_WEBHOOK_URL=https://hooks.slack.com/services/YOUR/SLACK/WEBHOOK
```

Then add the Slack logging channel to your `config/logging.php`:

```php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['single', 'slack'],
        ...
    ],

    'slack' => [
        'driver' => 'slack-logging',
        ...
    ],
],
```

Now any exception thrown in your application will be sent to Slack.

## Configuration

The published config file (`config/slack-logging.php`) provides the following options:

### Environments

Only send notifications in specified environments:

```php
'environments' => [
    'production',
],
```

### Except

Skip specific exception classes:

```php
'except' => [
    Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
],
```

### Sleep (Deduplication)

Prevent duplicate exceptions from spamming your Slack channel. When the same exception is raised repeatedly, the sleep duration escalates through the configured steps:

```php
'sleep' => [60, 120, 300],
```

| Occurrence | Action | Next cooldown |
|---|---|---|
| 1st | Sent to Slack | 60s |
| 2nd (within 60s) | Suppressed | 120s |
| 3rd (within 120s) | Suppressed | 300s |
| 4th+ (within 300s) | Suppressed | 300s (capped) |
| After cooldown expires | Sent again | Resets to 60s |

Set to an empty array to disable deduplication:

```php
'sleep' => [],
```

### Payload

Request parameters are automatically included in the Slack notification when available, making it easier to debug the exception context.

## Testing

```bash
composer test
```

### Testing in Your Application

Use the facade fake to assert exceptions in your tests:

```php
use Kevariable\SlackLogging\Facades\SlackLogging;

SlackLogging::fake();

// trigger exception...

SlackLogging::assertRequestsSent(1);
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [kevariable](https://github.com/kevariable)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

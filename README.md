# Laravel Slack Logging

[![Latest Version on Packagist](https://img.shields.io/packagist/v/kevariable/laravel-slack-logging.svg?style=flat-square)](https://packagist.org/packages/kevariable/laravel-slack-logging)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/kevariable/laravel-slack-logging/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/kevariable/laravel-slack-logging/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/kevariable/laravel-slack-logging/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/kevariable/laravel-slack-logging/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/kevariable/laravel-slack-logging.svg?style=flat-square)](https://packagist.org/packages/kevariable/laravel-slack-logging)

Laravel Slack Logging is a package that allows you to send Laravel logs directly to a Slack channel using webhooks. It provides an easy and efficient way to monitor your application's logs in real-time within your Slack workspace.

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

After installing the package, configure the Slack webhook URL in your `.env` file:

```env
SLACK_LOGGING_WEBHOOK_URL=https://hooks.slack.com/services/YOUR/SLACK/WEBHOOK
```

Then, add the Slack logging channel to your `config/logging.php` file:

```php
'channels' => [
    'stack' => [
            'driver' => 'stack',
            'channels' => ['single', 'slack'],
            'ignore_exceptions' => false,

      'slack' => [
            'driver' => 'slack-logging',
            ...
        ],
],
```

Now, Laravel will send logs to Slack based on the configured log level.

## Testing

```bash
composer test
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


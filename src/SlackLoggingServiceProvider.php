<?php

namespace Kevariable\SlackLogging;

use Illuminate\Log\LogManager;
use Monolog\Logger;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class SlackLoggingServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name(name: 'laravel-slack-logging')
            ->hasConfigFile();
    }

    public function register(): void
    {
        parent::register();

        $this->app->singleton('slack-logging', function () {
            return new SlackLogging;
        });

        if ($this->app['log'] instanceof LogManager) {
            $this->app['log']->extend('slack-logging', function ($app) {
                $handler = new SlackLoggingHandler(
                    $app['slack-logging'],
                );
                return new Logger('slack-logging', [$handler]);
            });
        }
    }
}

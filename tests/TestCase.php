<?php

namespace Kevariable\SlackLogging\Tests;

use Kevariable\SlackLogging\SlackLoggingServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected $enablesPackageDiscoveries = true;

    protected function getPackageProviders($app): array
    {
        return [
            SlackLoggingServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');

        /*
         foreach (\Illuminate\Support\Facades\File::allFiles(__DIR__ . '/database/migrations') as $migration) {
            (include $migration->getRealPath())->up();
         }
         */
    }
}

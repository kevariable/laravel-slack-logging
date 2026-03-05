<?php

namespace Kevariable\SlackLogging\Facades;

use Illuminate\Support\Facades\Facade;
use Kevariable\SlackLogging\SlackLoggingFake;

/**
 * @method static void assertRequestsSent(int $expectedCount)
 *
 * @see \Kevariable\SlackLogging\SlackLogging
 */
class SlackLogging extends Facade
{
    /**
     * Replace the bound instance with a fake.
     */
    public static function fake(): void
    {
        static::swap(new SlackLoggingFake);
    }

    protected static function getFacadeAccessor(): string
    {
        return 'slack-logging';
    }
}

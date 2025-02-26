<?php

namespace Kevariable\SlackLogging\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Kevariable\SlackLogging\SlackLogging
 */
class SlackLogging extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Kevariable\SlackLogging\SlackLogging::class;
    }
}

<?php

namespace Kevariable\SlackLogging;

use PHPUnit\Framework\Assert as PHPUnit;

class SlackLoggingFake extends SlackLogging
{
    /** @var class-string[][] */
    public array $exceptions = [];

    public function assertRequestsSent(int $expectedCount): void
    {
        PHPUnit::assertCount($expectedCount, $this->exceptions);
    }

    public function handle(\Throwable $exception): bool
    {
        $this->exceptions[get_class($exception)][] = $exception;

        return true;
    }
}

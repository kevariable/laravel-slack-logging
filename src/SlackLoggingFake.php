<?php

namespace Kevariable\SlackLogging;

use PHPUnit\Framework\Assert as PHPUnit;

class SlackLoggingFake extends SlackLogging
{
    /** @var class-string[] $exceptions */
    public array $exceptions = [];

    public function assertRequestsSent(int $expectedCount): void
    {
        PHPUnit::assertCount($expectedCount, $this->exceptions);
    }

    /**
     * @param mixed $throwable
     * @param callable|null $callback
     */
    public function assertNotSent($throwable, $callback = null): void
    {
        $collect = collect($this->exceptions[$throwable] ?? []);

        $callback = $callback ?: function () {
            return true;
        };

        $filtered = $collect->filter(function ($arguments) use ($callback) {
            return $callback($arguments);
        });

        PHPUnit::assertTrue($filtered->count() == 0);
    }

    public function assertNothingSent(): void
    {
        PHPUnit::assertCount(0, $this->exceptions);
    }

    /**
     * @param mixed $throwable
     * @param callable|null $callback
     */
    public function assertSent($throwable, $callback = null): void
    {
        $collect = collect($this->exceptions[$throwable] ?? []);

        $callback = $callback ?: function () {
            return true;
        };

        $filtered = $collect->filter(function ($arguments) use ($callback) {
            return $callback($arguments);
        });

        PHPUnit::assertTrue($filtered->count() > 0);
    }

    public function handle(\Throwable $exception): bool
    {
        $this->exceptions[get_class($exception)][] = $exception;

        return true;
    }
}
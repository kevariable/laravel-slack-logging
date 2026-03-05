<?php

namespace Kevariable\SlackLogging;

use PHPUnit\Framework\Assert as PHPUnit;

class SlackLoggingFake extends SlackLogging
{
    /** @var \Throwable[] */
    public array $exceptions = [];

    public function assertRequestsSent(int $expectedCount): void
    {
        PHPUnit::assertCount($expectedCount, $this->exceptions);
    }

    public function handle(\Throwable $exception): bool
    {
        if ($this->isSkipEnvironment()) {
            return false;
        }

        $data = $this->getExceptionData($exception);

        if ($this->isSkipException($data['class'])) {
            return false;
        }

        if ($this->isSleepingException($data)) {
            return false;
        }

        $this->exceptions[] = $exception;

        $this->putExceptionToSleep($data);

        return true;
    }
}

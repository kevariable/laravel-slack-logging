<?php

namespace Kevariable\SlackLogging;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Throwable;

class SlackLoggingHandler extends AbstractProcessingHandler
{
    public function __construct(public SlackLogging $slackLogging, $level = Level::Error, bool $bubble = true)
    {
        parent::__construct($level, $bubble);
    }

    /**
     * @throws \Illuminate\Http\Client\ConnectionException
     */
    protected function write($record): void
    {
        if (isset($record['context']['exception']) && $record['context']['exception'] instanceof Throwable) {
            $this->slackLogging->handle(
                $record['context']['exception']
            );
        }
    }
}

<?php

namespace Kevariable\SlackLogging\Tests;

use Exception;
use Illuminate\Support\Facades\Log;
use Kevariable\SlackLogging\Facades\SlackLogging;
use PHPUnit\Framework\Attributes\Test;

class LoggingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        SlackLogging::fake();

        config()->set('logging.channels.slack.driver', 'slack-logging');
        config()->set('logging.channels.stack.channels', ['single', 'slack']);
        config()->set('slack-logging.environments', ['testing']);
    }

    #[Test]
    public function it_will_not_send_log_information_to_slack()
    {
        $this->app['router']->get('/log-information-via-route/{type}', function (string $type) {
            Log::{$type}('log');
        });

        $this->get('/log-information-via-route/debug');
        $this->get('/log-information-via-route/info');
        $this->get('/log-information-via-route/notice');
        $this->get('/log-information-via-route/warning');
        $this->get('/log-information-via-route/error');
        $this->get('/log-information-via-route/critical');
        $this->get('/log-information-via-route/alert');
        $this->get('/log-information-via-route/emergency');

        SlackLogging::assertRequestsSent(0);
    }

    #[Test]
    public function it_will_send_log_information_to_slack()
    {
        $this->app['router']->get('/throwables-via-route', function () {
            throw new Exception('Sent to Slack Logging.');
        });

        $this->get('/throwables-via-route');

        SlackLogging::assertRequestsSent(1);
    }
}
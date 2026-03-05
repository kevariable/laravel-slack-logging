<?php

namespace Kevariable\SlackLogging\Tests;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Kevariable\SlackLogging\Facades\SlackLogging;
use Kevariable\SlackLogging\SlackLogging as SlackLoggingInstance;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

    #[Test]
    public function it_will_not_send_duplicate_exceptions_to_slack()
    {
        config()->set('slack-logging.sleep', [60, 120, 300]);

        $this->app['router']->get('/duplicate-exception', function () {
            throw new Exception('Duplicate exception.');
        });

        $this->get('/duplicate-exception');
        $this->get('/duplicate-exception');
        $this->get('/duplicate-exception');

        SlackLogging::assertRequestsSent(1);
    }

    #[Test]
    public function it_will_send_duplicate_exceptions_when_sleep_is_disabled()
    {
        config()->set('slack-logging.sleep', []);

        $this->app['router']->get('/no-sleep-exception', function () {
            throw new Exception('No sleep exception.');
        });

        $this->get('/no-sleep-exception');
        $this->get('/no-sleep-exception');

        SlackLogging::assertRequestsSent(2);
    }

    #[Test]
    public function it_will_send_different_exceptions_even_with_sleep()
    {
        config()->set('slack-logging.sleep', [60, 120, 300]);

        $this->app['router']->get('/exception-a', function () {
            throw new Exception('Exception A.');
        });

        $this->app['router']->get('/exception-b', function () {
            throw new \RuntimeException('Exception B.');
        });

        $this->get('/exception-a');
        $this->get('/exception-b');

        SlackLogging::assertRequestsSent(2);
    }

    #[Test]
    public function it_will_not_send_skipped_exceptions()
    {
        config()->set('slack-logging.except', [
            NotFoundHttpException::class,
        ]);

        $this->app['router']->get('/not-found', function () {
            throw new NotFoundHttpException('Not found.');
        });

        $this->get('/not-found');

        SlackLogging::assertRequestsSent(0);
    }

    #[Test]
    public function it_will_not_send_when_environment_is_not_configured()
    {
        config()->set('slack-logging.environments', ['production']);

        $this->app['router']->get('/wrong-env', function () {
            throw new Exception('Wrong environment.');
        });

        $this->get('/wrong-env');

        SlackLogging::assertRequestsSent(0);
    }

    #[Test]
    public function it_will_not_send_when_environments_is_empty()
    {
        config()->set('slack-logging.environments', []);

        $this->app['router']->get('/empty-env', function () {
            throw new Exception('Empty environments.');
        });

        $this->get('/empty-env');

        SlackLogging::assertRequestsSent(0);
    }

    #[Test]
    public function it_will_send_again_after_cache_expires()
    {
        config()->set('slack-logging.sleep', [60]);

        $this->app['router']->get('/cache-expire', function () {
            throw new Exception('Cache expire exception.');
        });

        $this->get('/cache-expire');
        SlackLogging::assertRequestsSent(1);

        Cache::flush();

        $this->get('/cache-expire');
        SlackLogging::assertRequestsSent(2);
    }

    #[Test]
    public function it_escalates_sleep_duration_on_repeated_exceptions()
    {
        config()->set('slack-logging.sleep', [60, 120, 300]);

        /** @var SlackLoggingInstance $instance */
        $instance = app('slack-logging');

        $exception = new Exception('Escalating exception.');
        $data = $instance->getExceptionData($exception);

        // Not sleeping yet
        $this->assertFalse($instance->isSleepingException($data));

        // First send — puts to sleep with 60s TTL (occurrence=1)
        $instance->handle($exception);

        // Now sleeping — occurrence bumps to 2, TTL extended to 120s
        $this->assertTrue($instance->isSleepingException($data));

        // Still sleeping — occurrence bumps to 3, TTL extended to 300s (max step)
        $this->assertTrue($instance->isSleepingException($data));

        // Still sleeping — stays at 300s (capped at last step)
        $this->assertTrue($instance->isSleepingException($data));
    }

    #[Test]
    public function it_supports_legacy_integer_sleep_config()
    {
        config()->set('slack-logging.sleep', 60);

        $this->app['router']->get('/legacy-sleep', function () {
            throw new Exception('Legacy sleep.');
        });

        $this->get('/legacy-sleep');
        $this->get('/legacy-sleep');

        SlackLogging::assertRequestsSent(1);
    }
}

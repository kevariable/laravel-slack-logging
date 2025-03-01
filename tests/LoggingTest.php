<?php

use Illuminate\Support\Facades\Log;
use Kevariable\SlackLogging\Facades\SlackLogging;

use function Pest\Laravel\get;

beforeEach(function () {
    SlackLogging::fake();

    config()->set('logging.channels.slack.driver', 'slack-logging');
    config()->set('logging.channels.stack.channels', ['single', 'slack']);
    config()->set('slack-logging.environments', ['testing']);
});

it(description: 'will not send log information to slack')
    ->defer(function (): void {
        app()['router']->get('/log-information-via-route/{type}', function (string $type) {
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

        SlackLogging::assertRequestsSent(expectedCount: 0);
    });

it(description: 'will send log information to slack')
    ->defer(function (): void {
        app()['router']->get('/throwables-via-route', function () {
            throw new Exception('Sent to Slack Logging.');
        });

        get('/throwables-via-route');

        SlackLogging::assertRequestsSent(expectedCount: 1);
    });

<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Lines near exception
    |--------------------------------------------------------------------------
    |
    | How many lines to show near exception line. The more you specify the bigger
    | the displayed code will be. Max value can be 50, will be defaulted to
    | 12 if higher than 50 automatically.
    |
    */
    'lines_count' => 12,

    /*
    |--------------------------------------------------------------------------
    | Sleep (deduplication)
    |--------------------------------------------------------------------------
    |
    | After sending an exception to Slack, subsequent identical exceptions
    | will be suppressed for an escalating duration (in seconds).
    | First duplicate sleeps for 60s, second for 120s, third+ for 300s.
    | Set to an empty array or [0] to disable.
    |
    */
    'sleep' => [60, 120, 300],

    'webhook_url' => env('SLACK_LOGGING_WEBHOOK_URL', env('LOG_SLACK_WEBHOOK_URL')),

    'except' => [
        Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
    ],

    'environments' => [
        'production',
    ],
];

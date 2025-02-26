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

    'sleep' => 60,

    'webhook_url' => env('SLACK_LOGGING_WEBHOOK_URL', env('LOG_SLACK_WEBHOOK_URL')),

    'except' => [
        Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
    ],

    'environments' => [
        'production',
    ],
];

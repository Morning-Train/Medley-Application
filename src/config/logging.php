<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that gets used when writing
    | messages to the logs. The name specified in this option should match
    | one of the channels defined in the "channels" configuration array.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Deprecations Log Channel
    |--------------------------------------------------------------------------
    |
    | This option controls the log channel that should be used to log warnings
    | regarding deprecated PHP and library features. This allows you to get
    | your application ready for upcoming major versions of dependencies.
    |
    */

    'deprecations' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog",
    |                    "custom", "stack", "bugsnag"
    |
    */

    'channels' => [
        // No channel logs to a file: PHP and WordPress write the log file themselves. A site adds the
        // destinations it wants to this stack, for example 'bugsnag'.
        'stack' => [
            'driver' => 'stack',
            'channels' => ['null'],
            'ignore_exceptions' => false,
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => \Monolog\Handler\NullHandler::class,
        ],

        // Defined here instead of letting Laravel create it on the first deprecation. PHP logs
        // deprecations itself, so this is null by default.
        'deprecations' => [
            'driver' => 'stack',
            'channels' => [env('LOG_DEPRECATIONS_CHANNEL', 'null')],
            'ignore_exceptions' => false,
        ],

        // Requires package "bugsnag/bugsnag-laravel"
        'bugsnag' => [
            'driver' => 'bugsnag',
        ],

        // Fallback logger (used when the configured stack can't be built). It writes to the PHP or
        // web server log, instead of a file in the theme's storage folder.
        'emergency' => [
            'path' => 'php://stderr',
        ],
    ],

];

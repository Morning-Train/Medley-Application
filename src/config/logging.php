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
        // The stack does not log to a file: PHP and WordPress write the log file themselves. A site adds
        // the destinations it wants to this stack, for example 'bugsnag'.
        //
        // Medley sends every kind of PHP problem to the stack. A channel chooses which ones it gets
        // with its 'level', which means that level and above:
        //
        //   exception, fatal error, E_USER_ERROR   error
        //   warning                                warning
        //   notice, deprecation                    notice
        //
        // 'level' => 'error' gives a channel the errors only, 'warning' adds the warnings, and 'notice'
        // gives it everything. The bugsnag channel sets its level with logger_notify_level in
        // config/bugsnag.php.
        'stack' => [
            'driver' => 'stack',
            'channels' => ['null'],
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => \Monolog\Handler\NullHandler::class,
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

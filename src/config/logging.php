<?php

$debugLog = (defined('WP_DEBUG') && WP_DEBUG) && (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG);
$debugLogPath = (defined('WP_DEBUG_LOG') && is_string(WP_DEBUG_LOG)) ? WP_DEBUG_LOG : 'wp-content/debug.log';

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
        'stack' => [
            'driver' => 'stack',
            'channels' => ['debug'],
            'ignore_exceptions' => false,
        ],

        // Errors are always reported, but only written to disk when debug logging is on.
        'debug' => $debugLog ? [
            'driver' => 'single',
            'path' => ABSPATH . $debugLogPath,
            'level' => env('LOG_LEVEL', 'debug'),
        ] : [
            // NoopHandler, not NullHandler: NullHandler counts as "handled" and would stop
            // the rest of the stack, so channels after this one (Bugsnag) would never run.
            'driver' => 'monolog',
            'handler' => \Monolog\Handler\NoopHandler::class,
        ],

        // Requires package "bugsnag/bugsnag-laravel"
        'bugsnag' => [
            'driver' => 'bugsnag',
        ],

        // Fallback logger (used when the configured stack can't be built).
        // Point it at the same file so nothing ends up in storage/logs/debug.log.
        'emergency' => [
            'path' => ABSPATH . $debugLogPath,
        ],
    ],

];

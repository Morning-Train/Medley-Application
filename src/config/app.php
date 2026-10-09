<?php

$env = \wp_get_environment_type();
$wpDebug = defined('WP_DEBUG') && (bool) WP_DEBUG;
$display = $wpDebug && defined('WP_DEBUG_DISPLAY') && (bool) WP_DEBUG_DISPLAY;

return [
    'env' => $env,

    // Install Medley's error and exception handlers. Set to false to leave PHP and WordPress in charge.
    'handle_exceptions' => env('HANDLE_EXCEPTIONS', true),

    // WP_DEBUG and WP_DEBUG_DISPLAY: errors are shown. This is what Laravel means by debug, and it
    // decides if error pages include the exception and its trace, so it is off when display is off.
    'debug' => $display,

    // Render errors with Ignition. Only ever on locally.
    'show_ignition' => $env === 'local' && $display,
];

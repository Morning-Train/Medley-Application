<?php

$env = \wp_get_environment_type();
$debug = defined('WP_DEBUG') && (bool) WP_DEBUG;
$debugLog = $debug && defined('WP_DEBUG_LOG') && (bool) WP_DEBUG_LOG;
$debugDisplay = $debug && defined('WP_DEBUG_DISPLAY') && (bool) WP_DEBUG_DISPLAY;

return [
    'env' => $env,

    // Install Medley's error and exception handlers. Set to false to leave PHP and WordPress in charge.
    'handle_exceptions' => env('HANDLE_EXCEPTIONS', true),

    // WP_DEBUG: WordPress reports notices and deprecations too, so they reach the log.
    'debug' => $debug,

    // WP_DEBUG_LOG: write reported errors to debug.log.
    'debug_log' => $debugLog,

    // WP_DEBUG_DISPLAY: errors are displayed.
    'debug_display' => $debugDisplay,

    // Render errors with Ignition. Only ever on locally.
    'show_ignition' => $env === 'local' && $debugDisplay,
];

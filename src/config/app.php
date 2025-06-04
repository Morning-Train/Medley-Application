<?php return [
    'env' => \wp_get_environment_type(),
    'debug' => (defined('WP_DEBUG') && !!WP_DEBUG) && (defined('WP_DEBUG_DISPLAY') && !!WP_DEBUG_DISPLAY),
    'handle_exceptions' => env('HANDLE_EXCEPTIONS', true),
];

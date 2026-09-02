<?php return [
    'env' => \wp_get_environment_type(),
    'debug' => (defined('WP_DEBUG') && !!WP_DEBUG) && (defined('WP_DEBUG_LOG') && !!WP_DEBUG_LOG),
    'show_ignition' => \wp_get_environment_type() === 'local' && (defined('WP_DEBUG') && !!WP_DEBUG) && (defined('WP_DEBUG_DISPLAY') && !!WP_DEBUG_DISPLAY),
];

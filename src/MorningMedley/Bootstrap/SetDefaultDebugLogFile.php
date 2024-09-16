<?php

namespace MorningMedley\Application\Bootstrap;

class SetDefaultDebugLogFile
{
    public function bootstrap()
    {
        if (! defined('WP_DEBUG_LOG') || empty(WP_DEBUG_LOG) || WP_DEBUG_LOG === true) {
            ini_set('error_log', storage_path('framework/debug.log'));
        }
    }
}

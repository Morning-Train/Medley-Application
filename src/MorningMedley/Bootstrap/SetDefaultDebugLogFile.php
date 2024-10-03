<?php

namespace MorningMedley\Application\Bootstrap;

class SetDefaultDebugLogFile
{
    public function bootstrap()
    {
        if (WP_DEBUG === true && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG === true) {
            $logsDir = storage_path('logs');
            if (! is_dir($logsDir)) {
                mkdir($logsDir);
            }
            
            ini_set('error_log', $logsDir . '/debug.log');
        }
    }
}

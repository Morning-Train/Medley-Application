<?php

namespace MorningMedley\Application\Console;

    use Illuminate\Support\ServiceProvider;

    class OptimizeClearCommand extends \Illuminate\Foundation\Console\OptimizeClearCommand
    {
        /**
         * Get the commands that should be run to clear the "optimization" files.
         *
         * @return array
         */
        public function getOptimizeClearTasks()
        {
            return [
//                'cache' => 'cache:clear',
//                'compiled' => 'clear-compiled',
                'config' => 'config:clear',
//                'events' => 'event:clear',
//                'routes' => 'route:clear',
                'views' => 'view:clear',
                ...ServiceProvider::$optimizeClearCommands,
            ];
        }
    }

<?php

namespace MorningMedley\Application\Console;

    use Illuminate\Support\ServiceProvider;

    class OptimizeCommand extends \Illuminate\Foundation\Console\OptimizeCommand
    {
        /**
         * Get the commands that should be run to optimize the framework.
         *
         * @return array
         */
        protected function getOptimizeTasks()
        {
            return [
                'config' => 'config:cache',
//                'events' => 'event:cache',
//                'routes' => 'route:cache',
                'views' => 'view:cache',
                ...ServiceProvider::$optimizeCommands,
            ];
        }
    }

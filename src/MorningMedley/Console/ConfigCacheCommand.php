<?php

namespace MorningMedley\Application\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel as ConsoleKernelContract;
use Illuminate\Filesystem\Filesystem;
use LogicException;
use Symfony\Component\Console\Attribute\AsCommand;
use Throwable;

#[AsCommand(name: 'config:cache')]
class ConfigCacheCommand extends \Illuminate\Foundation\Console\ConfigCacheCommand
{
    /**
     * Boot a fresh copy of the application configuration.
     *
     * @return array
     */
    protected function getFreshConfiguration()
    {
        $basePath = $this->laravel->basePath();
        $medley = \MorningMedley::configure($basePath)->create();
        $app = $medley->app;

        $app->useStoragePath($this->laravel->storagePath());

        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app['config']->all();
    }
}

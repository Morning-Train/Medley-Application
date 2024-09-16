<?php

namespace MorningMedley\Application\Providers;

use Illuminate\Console\Signals;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Foundation\Console\AboutCommand;

use Illuminate\Foundation\Console\ConfigClearCommand;
use Illuminate\Foundation\Console\ConfigPublishCommand;
use Illuminate\Foundation\Console\ConfigShowCommand;
use Illuminate\Foundation\Console\EnvironmentCommand;
use Illuminate\Support\ServiceProvider;
use MorningMedley\Application\Console\ConfigCacheCommand;

class ArtisanServiceProvider extends ServiceProvider implements DeferrableProvider
{
    protected $commands = [
        'About' => AboutCommand::class,
        'ConfigCache' => ConfigCacheCommand::class,
        'ConfigClear' => ConfigClearCommand::class,
        'ConfigShow' => ConfigShowCommand::class,
        'Environment' => EnvironmentCommand::class
    ];

    protected $devCommands = [
    ];

    public function register()
    {
        $this->registerCommands(array_merge(
            $this->commands,
            $this->devCommands
        ));

        Signals::resolveAvailabilityUsing(function () {
            return $this->app->runningInConsole()
                && ! $this->app->runningUnitTests()
                && extension_loaded('pcntl');
        });
    }

    protected function registerCommands(array $commands)
    {
        foreach ($commands as $commandName => $command) {
            $method = "register{$commandName}Command";

            if (method_exists($this, $method)) {
                $this->{$method}();
            } else {
                $this->app->singleton($command);
            }
        }

        $this->commands(array_values($commands));
    }

    protected function registerAboutCommand()
    {
        $this->app->singleton(AboutCommand::class, function ($app) {
            return new AboutCommand($app['composer']);
        });
    }

    protected function registerConfigCacheCommand()
    {
        $this->app->singleton(ConfigCacheCommand::class, function ($app) {
            return new ConfigCacheCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerConfigClearCommand()
    {
        $this->app->singleton(ConfigClearCommand::class, function ($app) {
            return new ConfigClearCommand($app['files']);
        });
    }

}

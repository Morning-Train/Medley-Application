<?php

namespace MorningMedley\Application\Providers;

use Illuminate\Console\Signals;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Foundation\Console\EnvironmentCommand;
use Illuminate\Support\ServiceProvider;

class ArtisanServiceProvider extends ServiceProvider implements DeferrableProvider
{
    protected $commands = [
        'About' => AboutCommand::class,
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
}

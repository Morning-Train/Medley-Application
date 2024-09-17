<?php

namespace MorningMedley\Application\Providers;

use Illuminate\Console\Signals;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Foundation\Console\AboutCommand;

use Illuminate\Foundation\Console\ConfigClearCommand;
use Illuminate\Foundation\Console\ConfigPublishCommand;
use Illuminate\Foundation\Console\ConfigShowCommand;
use Illuminate\Foundation\Console\EnvironmentCommand;
use Illuminate\Foundation\Console\PackageDiscoverCommand;
use Illuminate\Support\ServiceProvider;
use MorningMedley\Application\Console\ConfigCacheCommand;
use MorningMedley\Application\UrlGenerator;
use MorningMedley\Application\WpContext\PluginContext;
use MorningMedley\Application\WpContext\ThemeContext;
use MorningMedley\Application\WpContext\WpContextContract;

class UrlGeneratorServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function boot()
    {
        $this->app->singleton('url', UrlGenerator::class);
        $this->app->alias('url', \Illuminate\Contracts\Routing\UrlGenerator::class);
    }

    public function provides()
    {
        return ['url'];
    }
}

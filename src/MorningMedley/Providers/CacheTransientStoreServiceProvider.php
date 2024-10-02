<?php

namespace MorningMedley\Application\Providers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use MorningMedley\Application\CacheTransientStore;

class CacheTransientStoreServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app['cache']->extend('transient', function (Application $application) {
            $prefix = $this->app['config']->get('cache.prefix');

            return $application['cache']->repository(new CacheTransientStore($prefix));
        });
    }
}

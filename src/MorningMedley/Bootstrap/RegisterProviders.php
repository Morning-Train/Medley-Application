<?php

namespace MorningMedley\Application\Bootstrap;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Events\EventServiceProvider;
use Illuminate\Filesystem\FilesystemServiceProvider;
use Illuminate\Support\ServiceProvider;

class RegisterProviders extends \Illuminate\Foundation\Bootstrap\RegisterProviders
{
    protected function mergeAdditionalProviders(Application $app)
    {
        if (static::$bootstrapProviderPath &&
            file_exists(static::$bootstrapProviderPath)) {
            $packageProviders = require static::$bootstrapProviderPath;

            foreach ($packageProviders as $index => $provider) {
                if (! class_exists($provider)) {
                    unset($packageProviders[$index]);
                }
            }
        }

        $app->make('config')->set(
            'app.providers',
            array_merge(
                $app->make('config')->get('app.providers') ?? [],
                static::$merge,
                array_values($packageProviders ?? []),
            ),
        );
    }
}

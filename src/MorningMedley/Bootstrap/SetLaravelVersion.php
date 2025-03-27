<?php

namespace MorningMedley\Application\Bootstrap;

use  \Illuminate\Contracts\Foundation\Application;

class SetLaravelVersion
{
    /**
     * Locate the laravel version and add to config
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function bootstrap(Application $app)
    {
        if ($app->configurationIsCached()) {
            return;
        }
        
        try {
            $version = \Composer\InstalledVersions::getPrettyVersion('illuminate/container');
        } catch (\Throwable $e) {
            $version = '-';
        }

        $app['config']->set('laravelversion', $version);
    }
}

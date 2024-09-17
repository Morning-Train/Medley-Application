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
use MorningMedley\Application\WpContext\PluginContext;
use MorningMedley\Application\WpContext\ThemeContext;
use MorningMedley\Application\WpContext\WpContextContract;

class WpContextServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function boot()
    {
        if ($this->app->configurationIsCached()) {
            $this->setWpContext();

            return;
        }
        
        $this->generateConfig();
        $this->setWpContext();
    }

    protected function setWpContext()
    {
        $appConfig = $this->app['config']->get('app.wpcontext');
        $configClass = $appConfig['type'] === 'theme' ? ThemeContext::class : PluginContext::class;
        $wpContext = $this->app->makeWith($configClass, $appConfig);

        $this->app->instance('wpcontext', $wpContext);
        $this->app->alias('wpcontext', ThemeContext::class);
        $this->app->alias('wpcontext', PluginContext::class);
        $this->app->alias('wpcontext', WpContextContract::class);
    }

    protected function generateConfig()
    {
        if (file_exists($this->app->basePath('style.css'))) {
            $styleContents = file_get_contents($this->app->basePath('style.css'));
            if (str_contains($styleContents, 'Theme Name:')) {
                // This is a theme
                $this->setConfigByTheme($styleContents);

                return;
            }
        }
        $pluginFiles = [
            $this->app->basePath('plugin.php'),
            $this->app->basePath(basename($this->app->basePath()) . '.php'),
        ];
        foreach ($pluginFiles as $pluginFile) {
            if (file_exists($pluginFile)) {
                $this->setConfigByPlugin(file_get_contents($pluginFile));
                break;
            }
        }
    }

    protected function setConfigByTheme(string $styleContents)
    {
        $this->app['config']->set('app.wpcontext.name', $this->extractParamValue($styleContents, 'Theme Name'));
        $this->app['config']->set('app.wpcontext.description', $this->extractParamValue($styleContents, 'Description'));
        $this->app['config']->set('app.wpcontext.version', $this->extractParamValue($styleContents, 'Version'));
        $this->app['config']->set('app.wpcontext.textDomain', $this->extractParamValue($styleContents, 'Text Domain'));
        $this->app['config']->set('app.wpcontext.type', 'theme');
    }

    protected function setConfigByPlugin(string $pluginFileContents)
    {
        $this->app['config']->set('app.wpcontext.name', $this->extractParamValue($pluginFileContents, 'Plugin Name'));
        $this->app['config']->set('app.wpcontext.description',
            $this->extractParamValue($pluginFileContents, 'Description'));
        $this->app['config']->set('app.wpcontext.version', $this->extractParamValue($pluginFileContents, 'Version'));
        $this->app['config']->set('app.wpcontext.textDomain',
            $this->extractParamValue($pluginFileContents, 'Text Domain'));
        $this->app['config']->set('app.wpcontext.type', 'plugin');
    }

    protected function extractParamValue(string $source, string $param): mixed
    {
        $matches = [];
        $pattern = '/(?<=' . $param . ':).*/';
        if (preg_match($pattern, $source, $matches) === false) {
            return false;
        }

        return trim($matches[0]);
    }

    public function provides()
    {
        return ['wpcontext'];
    }
}

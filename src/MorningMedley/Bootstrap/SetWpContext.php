<?php

namespace MorningMedley\Application\Bootstrap;

use Illuminate\Contracts\Foundation\Application;
use MorningMedley\Application\WpContext\PluginContext;
use MorningMedley\Application\WpContext\ThemeContext;
use MorningMedley\Application\WpContext\WpContextContract;

class SetWpContext
{
    protected Application $app;

    public function bootstrap(Application $app)
    {
        $this->app = $app;

        if (! $this->app->configurationIsCached()) {
            $this->generateConfig();
        }

        $wpContext = $this->setWpContext();

        // Config:cache may have stored the wrong protocol.
        // Here we set the correct protocol/url scheme to avoid mixed-content blocking of assets
        $this->app->make(\Illuminate\Contracts\Config\Repository::class)->set(
            'app.asset_url',
            trailingslashit(\set_url_scheme($wpContext->url())) . "public"
        );
    }

    protected function setWpContext()
    {
        $appConfig = $this->app->make(\Illuminate\Contracts\Config\Repository::class)->get('app.wpcontext');
        $appConfig['url'] = \content_url($appConfig['relpath']);
        $configClass = $appConfig['type'] === 'theme' ? ThemeContext::class : PluginContext::class;
        $wpContext = $this->app->makeWith($configClass, $appConfig);

        $this->app->instance('wpcontext', $wpContext);
        $this->app->alias('wpcontext', ThemeContext::class);
        $this->app->alias('wpcontext', PluginContext::class);
        $this->app->alias('wpcontext', WpContextContract::class);

        return $wpContext;
    }

    protected function generateConfig()
    {
        if (file_exists($this->app->basePath('style.css'))) {
            $styleContents = file_get_contents($this->app->basePath('style.css'));
            if (str_contains($styleContents, 'Theme Name:')) {
                // This is a theme
                $this->setConfigByTheme($this->app->basePath('style.css'));

                return;
            }
        }
        $pluginFiles = [
            $this->app->basePath('plugin.php'),
            $this->app->basePath(basename($this->app->basePath()) . '.php'),
        ];
        foreach ($pluginFiles as $pluginFile) {
            if (file_exists($pluginFile)) {
                $this->setConfigByPlugin($pluginFile);
                break;
            }
        }
    }

    protected function setConfigByTheme(string $stylePath)
    {
        $styleContents = file_get_contents($stylePath);
        $themeDirName = basename(dirname($stylePath));
        $this->app->make(\Illuminate\Contracts\Config\Repository::class)->set('app.wpcontext', [
            'name' => $this->extractParamValue($styleContents, 'Theme Name'),
            'relpath' => str_replace(\content_url(), '', \get_theme_root_uri($stylePath) . "/" . $themeDirName . "/"),
            'description' => $this->extractParamValue($styleContents, 'Description'),
            'version' => $this->extractParamValue($styleContents, 'Version'),
            'textDomain' => $this->extractParamValue($styleContents, 'Text Domain'),
            'type' => 'theme',
        ]);
    }

    protected function setConfigByPlugin(string $pluginFilePath)
    {
        $pluginFileContents = file_get_contents($pluginFilePath);
        $this->app->make(\Illuminate\Contracts\Config\Repository::class)->set('app.wpcontext', [
            'name' => $this->extractParamValue($pluginFileContents, 'Plugin Name'),
            'relpath' => str_replace(\content_url(), '', \plugin_dir_url($pluginFilePath)),
            'description' => $this->extractParamValue($pluginFileContents, 'Description'),
            'version' => $this->extractParamValue($pluginFileContents, 'Version'),
            'textDomain' => $this->extractParamValue($pluginFileContents, 'Text Domain'),
            'type' => 'plugin',
        ]);
    }

    protected function extractParamValue(string $source, string $param): mixed
    {
        $matches = [];
        $pattern = '/(?<=' . $param . ':).*/';
        if (preg_match($pattern, $source, $matches) === false) {
            return false;
        }

        if (empty($matches)) {
            return "";
        }

        return trim($matches[0]);
    }
}

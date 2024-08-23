<?php

namespace MorningMedley\Application;

use Illuminate\Container\Container;
use function MorningMedley\Functions\app;

class Cli
{
    private Container $app;

    public function __construct(Container $app)
    {
        $this->app = $app;
    }

    private function ask(string $question): string
    {
        \WP_Cli::line($question);

        return trim(fgets(STDIN));
    }

    private function lineOut(string $string, bool $newlineAfter = false): void
    {
        \WP_Cli::line($string);
        if ($newlineAfter) {
            \WP_Cli::line('');
        }
    }

    private function colorLineOut(string $string, bool $newlineAfter = false): void
    {
        $this->lineOut(\WP_Cli::colorize($string), $newlineAfter);
    }

    public function clearCache()
    {
        if ($this->app->make('filecachemanager')->clearAllCaches()) {
            \WP_Cli::success('All file caches have been cleared');
        } else {
            \WP_Cli::error('Some file caches may not have been cleared');
        }
    }

    public function setup()
    {
        $this->colorLineOut('%GLet\'s get this project set up!%n', true);

        // Locate basepath and inform user
        $basePath = $this->app->basePath();
        $this->colorLineOut("Found app at %b{$basePath}%n", true);

        // Ask for the namespace
        $this->lineOut("What is the app's main namespace?");
        while (! isset($namespace) || preg_match('/^[A-Z]\w*(\\\\[A-Z]\w*)*$/', $namespace) !== 1) {
            $namespace = $this->ask("Please provide a valid PSR-4 namespace without leading slashes.");
        }
        $this->colorLineOut("Using %b{$namespace}%n as namespace.", true);

        // Ask for the text domain
        $this->lineOut("What is the app's text domain?");
        while (! isset($domain) || preg_match('/^[a-z\-]+$/', $domain) !== 1) {
            $domain = $this->ask("Please provide a text-domain consisting of only lowercase letters and dashes.");
        }
        $this->colorLineOut("Using %b{$domain}%n as text domain.", true);

        $appConfigFile = $this->app->configPath('app.php');
        $appConfig = $this->app->make('config')->get('app');

        $appConfig['namespace'] = $namespace;
        $appConfig['domain'] = $domain;

        if ($this->updateConfig($appConfigFile, $appConfig)) {
            \WP_Cli::success(\WP_Cli::colorize("%b$appConfigFile%n successfully updated."));
        }

        \WP_Cli::success('Successfully prepared the app!');
    }

    private function updateConfig(string $file, mixed $value): bool
    {
        if (file_exists($file)) {
            $export = var_export($value, true);

            return file_put_contents($file, "<?php return {$export};") !== false;
        }

        return false;
    }

}

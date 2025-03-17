<?php

namespace MorningMedley\Application\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'medley:setup')]
class SetupMedleyCommand extends Command
{
    protected $description = 'Setup Medley app';

    protected $signature = 'medley:setup
                            {--name= : App name}
                            {--domain= : App text domain}
                            {--theme : Skip auto-detection. This is a theme}
                            {--plugin : Skip auto-detection. This is a plugin}
                            {--auto : Auto resolve name and domain by app dir}
                            {--force : Overwrite existing files if they exist}';

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    protected string $appName;
    protected string $appDomain;
    protected string $basename;

    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    public function handle(): void
    {
        $this->basename = basename(base_path());

        // Figure out if this is theme or plugin
        $type = $this->getType();
        if ($type === null) {
            \WP_CLI::error("Could not determine app type.");
        }

        if ($type === 'theme') {
            $this->setupTheme();
            \WP_CLI::success(\WP_CLI::colorize("\n%p{$this->appName}%n theme has been setup. \nRun %ynpm run build%n to prepare assets."));
        }

        if ($type === 'plugin') {
            $this->setupPlugin();
            \WP_CLI::success(\WP_CLI::colorize("%p{$this->appName}%n plugin has been setup."));
        }
    }

    /**
     * Get app type
     * @return string|null 'theme', 'plugin' or null
     */
    public function getType(): ?string
    {
        if ($this->option('theme')) {
            return 'theme';
        }

        if ($this->option('plugin')) {
            return 'plugin';
        }

        if (! defined('WP_CONTENT_DIR')) {
            return null;
        }

        if (str_starts_with(__FILE__, WP_CONTENT_DIR . '/themes/')) {
            return 'theme';
        }

        if (str_starts_with(__FILE__, WP_CONTENT_DIR . '/plugins/')) {
            return 'plugin';
        }

        return null;
    }

    public function loadOptions(): void
    {
        if ($this->option('auto')) {
            $this->appName = Str::title($this->basename);
            $this->appDomain = $this->basename;

            return;
        }

        $this->appName = $this->option('name') ?? $this->ask('What is your app\'s name?');
        $this->appDomain = $this->option('domain') ?? $this->ask('What is your app\'s text-domain?');
    }

    public function getStubFile(string $stub): string
    {
        return dirname(__FILE__) . "/stubs/{$stub}.stub";
    }

    public function getStubContents(string $stub): string
    {
        return \file_get_contents($this->getStubFile($stub));
    }

    public function replaceStubVariables(string $stub, array $variables): string
    {
        return str_replace(
            array_map(fn($key) => "{{ $key }}", array_keys($variables)),
            array_values($variables),
            $this->getStubContents($stub)
        );
    }

    public function putStub(string $file, string $stub, ?array $variables = null): void
    {
        $fileName = base_path($file);
        $content = $variables === null ? $this->getStubContents($stub) : $this->replaceStubVariables($stub, $variables);
        $status = $this->files->put($fileName, $content);
        if ($status !== false) {
            \WP_CLI::line(\WP_CLI::colorize("Updated %y{$fileName}%n"));
        } else {
            \WP_CLI::line(\WP_CLI::colorize("Failed to update %r{$fileName}%n"));
        }
    }

    public function setupTheme(): void
    {
        if (file_exists(base_path('style.css')) && ! $this->option('force')) {
            \WP_CLI::success('Theme already seems to be setup');
        }

        $this->loadOptions();

        $this->putStub(
            'style.css',
            'style',
            [
                'name' => $this->appName,
                'domain' => $this->appDomain,
            ]
        );

        $this->putStub(
            'functions.php',
            'functions'
        );

        $this->putStub(
            'theme.json',
            'theme-json'
        );

        $this->putStub(
            'templates/index.html',
            'index-template'
        );

        $this->files->delete(base_path('plugin.php'));
    }

    public function setupPlugin(): void
    {
        if (file_exists(base_path('plugin.php')) && ! $this->option('force')) {
            \WP_CLI::success('Plugin already seems to be setup');
        }

        $this->loadOptions();

        $this->putStub(
            'plugin.php',
            'plugin',
            [
                'name' => $this->appName,
                'domain' => $this->appDomain,
            ]
        );
        
        $this->files->delete(base_path('functions.php'));
    }
}

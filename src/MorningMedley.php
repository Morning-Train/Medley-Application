<?php

use Illuminate\Contracts\Debug\ExceptionHandler as ExceptionHandlerContract;
use Illuminate\Foundation\Configuration\Exceptions;

class MorningMedley
{
    readonly public \MorningMedley\Application\Application $app;
    protected string $baseDir;

    public static function configure(string $baseDir): static
    {
        return new static($baseDir);
    }

    public function __construct(string $baseDir)
    {
        $_ENV['APP_ENV'] = \wp_get_environment_type();

        $this->baseDir = $_ENV['APP_BASE_PATH'] ?? $baseDir;
        $this->app = new \MorningMedley\Application\Application($this->baseDir);
    }

    public function create(): static
    {
        if ($this->app->runningInConsole()) {
            $this->bootConsole();
        } else {
            $this->bootHttp();
        }

        return $this;
    }

    protected function bootHttp()
    {
        $this->app->singleton(
            Illuminate\Contracts\Http\Kernel::class,
            \MorningMedley\Application\Http\Kernel::class);

        $this->app->bind(
            ExceptionHandlerContract::class,
            \MorningMedley\Application\Exceptions\Handler::class);

        $this->app->make(Illuminate\Contracts\Http\Kernel::class)
            ->bootstrap();

    }

    protected function bootConsole()
    {
        $this->app->singleton(
            Illuminate\Contracts\Console\Kernel::class,
            \MorningMedley\Application\Console\Kernel::class);

        $this->app->bind(
            ExceptionHandlerContract::class,
            \MorningMedley\Application\Exceptions\Handler::class);

        $this->app->bind(
            'composer',
            \Illuminate\Support\Composer::class);

        $this->app->register(
            \MorningMedley\Application\Providers\ArtisanServiceProvider::class);

        // Kernel is bootstrapped in its handle() method
        \WP_CLI::add_command('artisan', [$this, 'artisan']);
    }

    public function artisan()
    {
        define('LARAVEL_START', microtime(true));

        $kernel = $this->app->make(Illuminate\Contracts\Console\Kernel::class);

        // Find the index of "artisan" and remove anything preceding it, such as "wp"
        $index = array_search('artisan', $_SERVER['argv']);
        $argv = array_slice($_SERVER['argv'], $index); // Trim WordPress args

        // Replace args matching keys with their values
        // If the value is null then the arg is removed
        // See: https://make.wordpress.org/cli/handbook/references/config/#global-parameters
        $argMap = [
            '--path' => null,
            // --path is only for WordPress and illegal in Artisan
            '--ssh' => null,
            // Perform operation against a remote server
            '--http' => null,
            // Perform operation against a remote WordPress
            '--url' => null,
            // Pretend request came from given URL - For Multisite installs
            '--user' => null,
            // Set the WordPress user
            '--skip-plugins' => null,
            // Skip loading all or some plugins - MU Plugins are still loaded
            '--skip-themes' => null,
            // Skip loading all or some themes
            '--skip-packages' => null,
            // Skip loading all installed packages
            '--require' => null,
            // Load PHP file before running the command
            '--exec' => null,
            // Execute PHP code before running the command
            '--context' => null,
            // Load WordPress in a given context
            '--no-color' => null,
            // Whether to colorize the output
            '--color' => null,
            '--debug' => null,
            // Show all PHP errors; add verbosity to WP-CLI
            '--prompt' => null,
            // Prompt the user to enter values for all command arguments, or a subset specified as comma-separated values
            '--quiet' => null,
            // Suppress informational messages

            '--doc' => '--help',
            // --doc is an alias for --help, since --help is reserved for WordPress
        ];

        foreach ($argv as $index => $value) {
            // Get the arg without value
            $rawValue = explode("=", $value)[0];
            if (array_key_exists($rawValue, $argMap)) {
                if ($argMap[$rawValue] === null) {
                    // Alias is null, so remove the arg
                    unset($argv[$index]);
                } else {
                    // Replace the arg with its alias
                    $argv[$index] = str_replace($rawValue, $argMap[$rawValue], $value);
                }
            }
        }

        $argv = array_filter($argv);

        $status = $kernel->handle(
            $input = new Symfony\Component\Console\Input\ArgvInput($argv),
            new Symfony\Component\Console\Output\ConsoleOutput
        );

        $kernel->terminate($input, $status);

        exit($status);
    }

    /**
     * Register and configure the application's exception handler.
     *
     * @param  callable|null  $using
     * @return $this
     */
    public function withExceptions(?callable $using = null)
    {
        $using ??= fn() => true;

        $this->app->afterResolving(
            \Illuminate\Foundation\Exceptions\Handler::class,
            fn($handler) => $using(new Exceptions($handler)),
        );

        return $this;
    }
}

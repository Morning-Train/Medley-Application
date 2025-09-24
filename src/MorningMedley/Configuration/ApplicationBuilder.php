<?php

namespace MorningMedley\Application\Configuration;

use Illuminate\Contracts\Console\Kernel as ConsoleKernelContract;
use Illuminate\Contracts\Http\Kernel as HttpKernelContract;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\RegisterProviders;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as AppEventServiceProvider;
use Illuminate\Support\Collection;

use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

class ApplicationBuilder
{
    /**
     * The service provider that are marked for registration.
     *
     * @var array
     */
    protected array $pendingProviders = [];

    public function __construct(protected Application $app)
    {
    }

    public function withKernels()
    {
        $this->app->singleton(
            HttpKernelContract::class,
            \MorningMedley\Application\Http\Kernel::class
        );

        $this->app->singleton(
            ConsoleKernelContract::class,
            \MorningMedley\Application\Console\Kernel::class
        );

        return $this;
    }

    /**
     * Register additional service providers.
     *
     * @param  array  $providers
     * @param  bool  $withBootstrapProviders
     * @return $this
     */
    public function withProviders(array $providers = [], bool $withBootstrapProviders = true)
    {
        RegisterProviders::merge(
            $providers,
            $withBootstrapProviders
                ? $this->app->getBootstrapProvidersPath()
                : null
        );

        return $this;
    }

    /**
     * Register the core event service provider for the application.
     *
     * @param  iterable<int, string>|bool  $discover
     * @return $this
     */
    public function withEvents(iterable|bool $discover = true)
    {
        if (is_iterable($discover)) {
            AppEventServiceProvider::setEventDiscoveryPaths($discover);
        }

        if ($discover === false) {
            AppEventServiceProvider::disableEventDiscovery();
        }

        if (! isset($this->pendingProviders[AppEventServiceProvider::class])) {
            $this->app->booting(function () {
                $this->app->register(AppEventServiceProvider::class);
            });
        }

        $this->pendingProviders[AppEventServiceProvider::class] = true;

        return $this;
    }

    /**
     * Register additional Artisan commands with the application.
     *
     * @param  array  $commands
     * @return $this
     */
    public function withCommands(array $commands = [])
    {
        if (empty($commands)) {
            $commands = [$this->app->path('Console/Commands')];
        }

        $this->app->afterResolving(ConsoleKernelContract::class, function ($kernel) use ($commands) {
            [$commands, $paths] = (new Collection($commands))->partition(fn($command) => class_exists($command));
            [$routes, $paths] = $paths->partition(fn($path) => is_file($path));

            $this->app->booted(static function () use ($kernel, $commands, $paths, $routes) {
                $kernel->addCommands($commands->all());
                $kernel->addCommandPaths($paths->all());
                $kernel->addCommandRoutePaths($routes->all());
            });
        });

        return $this;
    }

    /**
     * Register and configure the application's exception handler.
     *
     * @param  callable|null  $using
     * @return $this
     */
    public function withExceptions(?callable $using = null)
    {
        $this->app->singleton(
            \Illuminate\Contracts\Debug\ExceptionHandler::class,
            \MorningMedley\Application\Exceptions\Handler::class
        );

        $using ??= fn() => true;

        $this->app->afterResolving(
            \MorningMedley\Application\Exceptions\Handler::class,
            fn($handler) => $using(new Exceptions($handler)),
        );

        return $this;
    }

    /**
     * Register an array of container bindings to be bound when the application is booting.
     *
     * @param  array  $bindings
     * @return $this
     */
    public function withBindings(array $bindings)
    {
        return $this->registered(function ($app) use ($bindings) {
            foreach ($bindings as $abstract => $concrete) {
                $app->bind($abstract, $concrete);
            }
        });
    }

    /**
     * Register an array of singleton container bindings to be bound when the application is booting.
     *
     * @param  array  $singletons
     * @return $this
     */
    public function withSingletons(array $singletons)
    {
        return $this->registered(function ($app) use ($singletons) {
            foreach ($singletons as $abstract => $concrete) {
                if (is_string($abstract)) {
                    $app->singleton($abstract, $concrete);
                } else {
                    $app->singleton($concrete);
                }
            }
        });
    }

    /**
     * Register an array of scoped singleton container bindings to be bound when the application is booting.
     *
     * @param  array  $scopedSingletons
     * @return $this
     */
    public function withScopedSingletons(array $scopedSingletons)
    {
        return $this->registered(function ($app) use ($scopedSingletons) {
            foreach ($scopedSingletons as $abstract => $concrete) {
                if (is_string($abstract)) {
                    $app->scoped($abstract, $concrete);
                } else {
                    $app->scoped($concrete);
                }
            }
        });
    }

    /**
     * Register a callback to be invoked when the application's service providers are registered.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function registered(callable $callback)
    {
        $this->app->registered($callback);

        return $this;
    }

    /**
     * Register a callback to be invoked when the application is "booting".
     *
     * @param  callable  $callback
     * @return $this
     */
    public function booting(callable $callback)
    {
        $this->app->booting($callback);

        return $this;
    }

    /**
     * Register a callback to be invoked when the application is "booted".
     *
     * @param  callable  $callback
     * @return $this
     */
    public function booted(callable $callback)
    {
        $this->app->booted($callback);

        return $this;
    }

    public function create()
    {
        if ($this->app->runningInConsole()) {
            $this->bootConsole();
        } else {
            $this->bootHttp();
        }

        return $this->app;
    }

    protected function bootHttp()
    {

        $this->app->make(HttpKernelContract::class)
            ->bootstrap();

    }

    protected function bootConsole()
    {
        $this->app->bind(
            'composer',
            \Illuminate\Support\Composer::class);

        $this->app->register(
            \MorningMedley\Application\Providers\ArtisanServiceProvider::class);

        $this->app->make(ConsoleKernelContract::class)->bootstrap();
        \WP_CLI::add_command('artisan', [$this, 'artisan']);
    }

    public function artisan()
    {
        define('LARAVEL_START', microtime(true));

        $kernel = $this->app->make(ConsoleKernelContract::class);

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
            $input = new ArgvInput($argv),
            new ConsoleOutput
        );

        $kernel->terminate($input, $status);

        exit($status);
    }
}

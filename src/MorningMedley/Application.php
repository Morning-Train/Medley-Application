<?php

namespace MorningMedley\Application;

use Illuminate\Cache\CacheServiceProvider;
use Illuminate\Container\Container;
use Illuminate\Events\EventServiceProvider;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Events\LocaleUpdated;
use Illuminate\Http\Client\Request;
use Illuminate\Log\LogServiceProvider;
use Illuminate\Support\Env;
use Illuminate\Support\ServiceProvider;
use MorningMedley\Application\Providers\CacheTransientStoreServiceProvider;
use MorningMedley\Application\Providers\DebugInformationServiceProvider;
use MorningMedley\Application\Providers\IgnitionServiceProvider;
use MorningMedley\Application\Translation\NullTranslator;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class Application extends \Illuminate\Foundation\Application
{
    /**
     * The Medley framework version.
     *
     * @var string
     */
    const VERSION = '0.4.0';

    /**
     * The current locale initializes as WordPress \get_locale()
     *
     * @var string
     */
    protected string $locale = '';

    /**
     * Begin configuring a new Laravel application instance.
     *
     * @param  string|null  $basePath
     * @return \Illuminate\Foundation\Configuration\ApplicationBuilder
     */
    public static function configure(?string $basePath = null)
    {
        $basePath = match (true) {
            is_string($basePath) => $basePath,
            default => static::inferBasePath(),
        };

        return (new Configuration\ApplicationBuilder(new static($basePath)))
            ->withKernels()
            ->withEvents()
            ->withCommands()
            ->withProviders();
    }

    /**
     * Get the version number of the laravel application.
     *
     * @return string
     */
    public function version()
    {
        return config('laravelversion', '^12');
    }

    /**
     * Get the version number of the medley application.
     *
     * @return string
     */
    public function medleyVersion()
    {
        return static::VERSION;
    }

    /**
     * Register the basic bindings into the container.
     *
     * @return void
     */
    protected function registerBaseBindings()
    {
        $this->bind('request', function (Application $app) {
            $request = \Illuminate\Http\Request::capture();
            global $wp_query;
            $request->query->add($wp_query->query_vars);

            return $request;
        });

        $this->singleton('translator', NullTranslator::class);

        static::setInstance($this);

        $this->instance('app', $this);

        $this->instance(Container::class, $this);

        $this->singleton(PackageManifest::class, fn() => new PackageManifest(
            new Filesystem, $this->basePath(), $this->getCachedPackagesPath()
        ));
    }

    /**
     * Register all of the base service providers.
     *
     * @return void
     */
    protected function registerBaseServiceProviders()
    {
        $this->register(new EventServiceProvider($this));
        $this->register(new LogServiceProvider($this));
        $this->register(new DebugInformationServiceProvider($this));
        $this->register(new \Illuminate\Filesystem\FilesystemServiceProvider($this));
        $this->register(new CacheServiceProvider($this));
        $this->register(new CacheTransientStoreServiceProvider($this));

        // Delay this slightly
        $this->booted(fn() => $this->register(new IgnitionServiceProvider($this)));

        // ContextServiceProvider & RoutingServiceProvider have been removed since Queue and Routing are separate packages
    }

    /**
     * Get the path to the storage directory.
     *
     * @param  string  $path
     * @return string
     */
    public function storagePath($path = '')
    {
        if (isset($_ENV['MORNINGMEDLEY_STORAGE_PATH'])) {
            return $this->joinPaths($this->storagePath ?: $_ENV['MORNINGMEDLEY_STORAGE_PATH'], $path);
        }

        if (isset($_SERVER['MORNINGMEDLEY_STORAGE_PATH'])) {
            return $this->joinPaths($this->storagePath ?: $_SERVER['MORNINGMEDLEY_STORAGE_PATH'], $path);
        }

        return $this->joinPaths($this->storagePath ?: $this->basePath('storage'), $path);
    }

    /**
     * Determine if the application is running in the console.
     *
     * @return bool
     */
    public function runningInConsole()
    {
        if ($this->isRunningInConsole === null) {
            $this->isRunningInConsole = Env::get('APP_RUNNING_IN_CONSOLE') ?? (defined('WP_CLI') && constant('WP_CLI'));
        }

        return $this->isRunningInConsole;
    }

    /**
     * Determine if the application is running any of the given console commands.
     *
     * @param  string|array  ...$commands
     * @return bool
     */
    public function runningConsoleCommand(...$commands)
    {
        if (! $this->runningInConsole()) {
            return false;
        }

        return in_array(
            $_SERVER['argv'][2] ?? null,
            is_array($commands[0]) ? $commands[0] : $commands
        );
    }

    /**
     * Determine if the application is running with debug mode enabled.
     *
     * @return bool
     */
    public function hasDebugModeEnabled()
    {
        return (bool) $this['config']->get('app.debug') ?? (defined('WP_DEBUG') && WP_DEBUG == true);
    }

    protected function bootProvider(ServiceProvider $provider)
    {
        parent::bootProvider($provider);

        if (method_exists($provider, 'hookClass')) {
            $this->call([$provider, 'hookClass']);
        }
    }

    public function handle(
        SymfonyRequest $request,
        int $type = \Illuminate\Foundation\Application::MAIN_REQUEST,
        bool $catch = true
    ): SymfonyResponse {
        return \Illuminate\Support\Facades\Route::handle($request, $type, $catch);
    }

    public function handleRequest(\Illuminate\Http\Request $request)
    {
        return \Illuminate\Support\Facades\Route::handleRequest($request);
    }

    //handleCommand ??
    public function routesAreCached()
    {
        return \Illuminate\Support\Facades\Route::routesAreCached();
    }

    public function getCachedRoutesPath()
    {
        return \Illuminate\Support\Facades\Route::getCachedRoutesPath();
    }

    /**
     * Get an instance of the maintenance mode manager implementation.
     *
     * @return \Illuminate\Contracts\Foundation\MaintenanceMode
     */
    public function maintenanceMode()
    {
        throw new \Exception("maintenanceMode is not part of Medley at this point");
        //        return $this->make(MaintenanceModeContract::class);
    }

    /**
     * Determine if the application is currently down for maintenance.
     *
     * @return bool
     */
    public function isDownForMaintenance()
    {
        return \wp_is_maintenance_mode();
    }

    /**
     * Get the current application locale.
     *
     * @return string
     */
    public function getLocale()
    {
        $this->locale = \get_locale();

        return $this->locale;
    }

    public function setLocale($locale)
    {
        parent::setLocale($locale);

        $this->locale = $locale;
        \add_filter('locale', fn() => $this->locale, 20);
    }

    /**
     * Determine if the application locale is the given locale.
     *
     * @param  string  $locale
     * @return bool
     */
    public function isLocale($locale)
    {
        return $this->getLocale() == $locale;
    }

    /**
     * Register the core class aliases in the container.
     *
     * @return void
     */
    public function registerCoreContainerAliases()
    {
        foreach ([
                     'app' => [
                         self::class,
                         \Illuminate\Contracts\Container\Container::class,
                         \Illuminate\Contracts\Foundation\Application::class,
                         \Psr\Container\ContainerInterface::class,
                     ],
                     //                     'auth' => [\Illuminate\Auth\AuthManager::class, \Illuminate\Contracts\Auth\Factory::class],
                     //                     'auth.driver' => [\Illuminate\Contracts\Auth\Guard::class],
                     //                     'blade.compiler' => [\Illuminate\View\Compilers\BladeCompiler::class],
                     'cache' => [\Illuminate\Cache\CacheManager::class, \Illuminate\Contracts\Cache\Factory::class],
                     'cache.store' => [
                         \Illuminate\Cache\Repository::class,
                         \Illuminate\Contracts\Cache\Repository::class,
                         \Psr\SimpleCache\CacheInterface::class,
                     ],
                     //                     'cache.psr6' => [
                     //                         \Symfony\Component\Cache\Adapter\Psr16Adapter::class,
                     //                         \Symfony\Component\Cache\Adapter\AdapterInterface::class,
                     //                         \Psr\Cache\CacheItemPoolInterface::class,
                     //                     ],
                     'config' => [\Illuminate\Config\Repository::class, \Illuminate\Contracts\Config\Repository::class],
                     //                     'cookie' => [
                     //                         \Illuminate\Cookie\CookieJar::class,
                     //                         \Illuminate\Contracts\Cookie\Factory::class,
                     //                         \Illuminate\Contracts\Cookie\QueueingFactory::class,
                     //                     ],
                     'events' => [\Illuminate\Events\Dispatcher::class, \Illuminate\Contracts\Events\Dispatcher::class],
                     'files' => [\Illuminate\Filesystem\Filesystem::class],
                     'filesystem' => [
                         \Illuminate\Filesystem\FilesystemManager::class,
                         \Illuminate\Contracts\Filesystem\Factory::class,
                     ],
                     //                     'filesystem.disk' => [\Illuminate\Contracts\Filesystem\Filesystem::class],
                     //                     'filesystem.cloud' => [\Illuminate\Contracts\Filesystem\Cloud::class],
                     //                     'hash' => [\Illuminate\Hashing\HashManager::class],
                     //                     'hash.driver' => [\Illuminate\Contracts\Hashing\Hasher::class],
                     'translator' => [
                         \Illuminate\Contracts\Translation\Translator::class,
                     ],
                     'log' => [\Illuminate\Log\LogManager::class, \Psr\Log\LoggerInterface::class],
                     //                     'mail.manager' => [\Illuminate\Mail\MailManager::class, \Illuminate\Contracts\Mail\Factory::class],
                     //                     'mailer' => [
                     //                         \Illuminate\Mail\Mailer::class,
                     //                         \Illuminate\Contracts\Mail\Mailer::class,
                     //                         \Illuminate\Contracts\Mail\MailQueue::class,
                     //                     ],
                     //                     'auth.password' => [
                     //                         \Illuminate\Auth\Passwords\PasswordBrokerManager::class,
                     //                         \Illuminate\Contracts\Auth\PasswordBrokerFactory::class,
                     //                     ],
                     //                     'auth.password.broker' => [
                     //                         \Illuminate\Auth\Passwords\PasswordBroker::class,
                     //                         \Illuminate\Contracts\Auth\PasswordBroker::class,
                     //                     ],
                     //                     'redirect' => [\Illuminate\Routing\Redirector::class],
                     //                     'redis' => [\Illuminate\Redis\RedisManager::class, \Illuminate\Contracts\Redis\Factory::class],
                     //                     'redis.connection' => [
                     //                         \Illuminate\Redis\Connections\Connection::class,
                     //                         \Illuminate\Contracts\Redis\Connection::class,
                     //                     ],
                     'request' => [\Illuminate\Http\Request::class, \Symfony\Component\HttpFoundation\Request::class],
                     //                     'router' => [
                     //                         \Illuminate\Routing\Router::class,
                     //                         \Illuminate\Contracts\Routing\Registrar::class,
                     //                         \Illuminate\Contracts\Routing\BindingRegistrar::class,
                     //                     ],
                     //                     'session' => [\Illuminate\Session\SessionManager::class],
                     //                     'session.store' => [
                     //                         \Illuminate\Session\Store::class,
                     //                         \Illuminate\Contracts\Session\Session::class,
                     //                     ],
                     'url' => [
                         \Illuminate\Contracts\Routing\UrlGenerator::class,
                     ],
                     //                                          'validator' => [
                     //                         \Illuminate\Validation\Factory::class,
                     //                         \Illuminate\Contracts\Validation\Factory::class,
                     //                     ],
                     PackageManifest::class => [\Illuminate\Foundation\PackageManifest::class],
                     \MorningMedley\Application\Http\ResponseFactory::class => [\Illuminate\Contracts\Routing\ResponseFactory::class],
                     \Illuminate\Log\Context\ContextLogProcessor::class => [\Illuminate\Contracts\Log\ContextLogProcessor::class],
                 ] as $key => $aliases) {
            foreach ($aliases as $alias) {
                $this->alias($key, $alias);
            }
        }
    }
}

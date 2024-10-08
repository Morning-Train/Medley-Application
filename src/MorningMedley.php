<?php

use Illuminate\Contracts\Debug\ExceptionHandler as ExceptionHandlerContract;

class MorningMedley
{
    readonly public \MorningMedley\Application\Application $app;
    protected string $baseDir;

    public function __construct(string $baseDir)
    {
        $this->baseDir = $_ENV['APP_BASE_PATH'] ?? $baseDir;
        $_ENV['APP_ENV'] = \wp_get_environment_type();

        $this->app = new \MorningMedley\Application\Application($this->baseDir);

        if ($this->app->runningInConsole()) {
            $this->bootConsole();
        } else {
            $this->bootHttp();
        }
    }

    protected function bootHttp()
    {
        $this->app->singleton(
            Illuminate\Contracts\Http\Kernel::class,
            \MorningMedley\Application\Http\Kernel::class);

        $this->app->bind(
            ExceptionHandlerContract::class,
            \MorningMedley\Application\Http\ExceptionHandler::class);

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
            \MorningMedley\Application\Console\ExceptionHandler::class);

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
        $argMap = [
            '--path' => null, // --path is only for WordPress and illegal in Artisan
            '--doc' => '--help', // --doc is an alias for --help, since --help is reserved for WordPress
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
}

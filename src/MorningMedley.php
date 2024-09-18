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
        $this->app->singleton(Illuminate\Contracts\Http\Kernel::class, \MorningMedley\Application\Http\Kernel::class);

        if ($this->app->runningInConsole()) {
            \WP_CLI::add_command('artisan', [$this, 'artisan']);
        } else {
            $this->app->bind(ExceptionHandlerContract::class,
                \MorningMedley\Application\Http\ExceptionHandler::class);
        }
        $this->app->make(Illuminate\Contracts\Http\Kernel::class)->bootstrap();
    }

    public function artisan()
    {
        define('LARAVEL_START', microtime(true));

        $this->app->bind(ExceptionHandlerContract::class,
            \MorningMedley\Application\Console\ExceptionHandler::class);
        $this->app->singleton(Illuminate\Contracts\Console\Kernel::class,
            \MorningMedley\Application\Console\Kernel::class);
        $kernel = $this->app->make(Illuminate\Contracts\Console\Kernel::class);
        $this->app->bind('composer', \Illuminate\Support\Composer::class);
        $this->app->register(\MorningMedley\Application\Providers\ArtisanServiceProvider::class);

        // Find the index of "artisan" and remove anything preceding it, such as "wp"
        $index = array_search('artisan', $_SERVER['argv']);
        $argv = array_slice($_SERVER['argv'], $index); // Trim WordPress args

        // Since WordPress treats the --help flag as the help command with no simple way of telling it to do otherwise,
        // Hotfix for using --help as --doc
        if (in_array('--doc', $argv)) {
            foreach ($argv as $index => $value) {
                if ($value === '--doc') {
                    $argv[$index] = '--help';
                }
            }
        }

        $status = $kernel->handle(
            $input = new Symfony\Component\Console\Input\ArgvInput($argv),
            new Symfony\Component\Console\Output\ConsoleOutput
        );
        $kernel->terminate($input, $status);
        exit($status);
    }
}

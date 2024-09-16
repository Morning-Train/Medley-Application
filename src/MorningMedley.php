<?php

use Illuminate\Contracts\Debug\ExceptionHandler as ExceptionHandlerContract;

class MorningMedley
{
    readonly public \MorningMedley\Application\Application $app;
    protected string $baseDir;

    public function __construct(string $baseDir)
    {
        $this->baseDir = $_ENV['APP_BASE_PATH'] ?? $baseDir;

        $this->app = new \MorningMedley\Application\Application($this->baseDir);
        $this->app->singleton(Illuminate\Contracts\Http\Kernel::class, \MorningMedley\Application\Http\Kernel::class);

        $this->app->make(Illuminate\Contracts\Http\Kernel::class)->bootstrap();

        if ($this->app->runningInConsole()) {
            \WP_CLI::add_command('artisan', [$this, 'artisan']);
        }
    }

    public function artisan()
    {
        define('LARAVEL_START', microtime(true));

        $this->app->bind(ExceptionHandlerContract::class,
            \MorningMedley\Application\Console\ExceptionHandler::class);
        $this->app->singleton(Illuminate\Contracts\Console\Kernel::class,
            \MorningMedley\Application\Console\Kernel::class);
        $kernel = $this->app->make(Illuminate\Contracts\Console\Kernel::class);

        $argv = array_slice($_SERVER['argv'], 2); // Trim WordPress args

        $status = $kernel->handle(
            $input = new Symfony\Component\Console\Input\ArgvInput($argv),
            new Symfony\Component\Console\Output\ConsoleOutput
        );
        $kernel->terminate($input, $status);
        exit($status);
    }
}

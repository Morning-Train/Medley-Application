<?php

use Illuminate\Contracts\Debug\ExceptionHandler as ExceptionHandlerContract;
use Illuminate\Foundation\Configuration\Exceptions;
use MorningMedley\Application\Configuration\ApplicationBuilder;

class MorningMedley
{
    readonly public \MorningMedley\Application\Application $app;
    protected string $baseDir;

    public static function configure(string $baseDir): ApplicationBuilder
    {
        $medley = new static($baseDir);

        return (new ApplicationBuilder($medley->app))
            ->withKernels()
            ->withEvents()
            ->withCommands()
            ->withProviders()
            ->withExceptions();
    }

    public function __construct(string $baseDir)
    {
        $_ENV['APP_ENV'] = \wp_get_environment_type();

        $this->baseDir = $_ENV['APP_BASE_PATH'] ?? $baseDir;
        $this->app = new \MorningMedley\Application\Application($this->baseDir);
    }

}

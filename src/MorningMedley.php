<?php

class MorningMedley
{
    readonly public \MorningMedley\Application\Application $app;
    protected string $baseDir;

    public function __construct(string $baseDir)
    {
        $this->baseDir = $_ENV['APP_BASE_PATH'] ?? $baseDir;

        $this->app = new \MorningMedley\Application\Application($this->baseDir);
        $this->app->singleton(Illuminate\Contracts\Http\Kernel::class, \MorningMedley\Application\Http\Kernel::class);

        $this->app->singleton(
            Illuminate\Contracts\Debug\ExceptionHandler::class,
            App\Exceptions\Handler::class
        );

        $this->app->make(Illuminate\Contracts\Http\Kernel::class)->bootstrap();
        ray($this->app);
    }

}

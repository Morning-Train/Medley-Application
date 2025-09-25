<?php

namespace MorningMedley\Application\Bootstrap;

use ErrorException;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Log\LogManager;
use Throwable;

class HandleExceptions extends \Illuminate\Foundation\Bootstrap\HandleExceptions
{
    protected $defaultExceptionHandler = null;

    /**
     * Bootstrap the given application.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function bootstrap(Application $app)
    {
        // Allow bypass
        if (! $app['config']->get('app.handle_exceptions', true)) {
            return;
        }

        // For easy ray
        if ($app['config']->get('app.send_exceptions_to_ray', false) && function_exists('ray')) {
            $app->make(\Illuminate\Contracts\Debug\ExceptionHandler::class)->reportable(function (\Throwable $e) {
                ray()->exception($e);
            });
        }

        parent::bootstrap($app);
    }

    /**
     * Report PHP deprecations, or convert PHP errors to ErrorException instances.
     *
     * @param  int  $level
     * @param  string  $message
     * @param  string  $file
     * @param  int  $line
     * @return void
     *
     * @throws \ErrorException
     */
    public function handleError($level, $message, $file = '', $line = 0)
    {
        if ($this->isDeprecation($level)) {
            $this->handleDeprecationError($message, $file, $line, $level);
        } elseif ($this->isWarning($level)) {
            $this->handleWarningError($message, $file, $line, $level);
        } elseif (error_reporting() & $level) {
            throw new ErrorException($message, 0, $level, $file, $line);
        }
    }

    /**
     * Determine if the error level is a warning.
     *
     * @param  int  $level
     * @return bool
     */
    protected function isWarning($level)
    {
        return in_array($level, [E_WARNING, E_USER_WARNING]);
    }

    /**
     * Reports a deprecation to the "deprecations" logger.
     *
     * @param  string  $message
     * @param  string  $file
     * @param  int  $line
     * @param  int  $level
     * @return void
     */
    public function handleWarningError($message, $file, $line, $level = E_DEPRECATED)
    {
        // If debug then throw
        if (error_reporting() & $level && config('app.debug')) {
            throw new ErrorException($message, 0, $level, $file, $line);
        }

        // We still want to report!
        try {
            $logger = static::$app->make(LogManager::class);
        } catch (Exception) {
            return;
        }

        static::$reservedMemory = null;
        $e = new ErrorException($message, 0, $level, $file, $line);
        try {
            $this->getExceptionHandler()->report($e);
        } catch (Exception) {
            $exceptionHandlerFailed = true;
        }

        if (static::$app->runningInConsole()) {
            if ($exceptionHandlerFailed ?? false) {
                exit(1);
            }
        }
    }
}

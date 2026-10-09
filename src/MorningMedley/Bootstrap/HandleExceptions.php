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


        // Laravel forces error_reporting(-1) and display_errors=Off. Keep what WordPress
        // configured, so WP_DEBUG and WP_DEBUG_DISPLAY keep controlling those.
        $errorReporting = error_reporting();
        $displayErrors = ini_get('display_errors');

        parent::bootstrap($app);

        error_reporting($errorReporting);
        ini_set('display_errors', $displayErrors);
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
            // Respect @-suppression and error_reporting settings
            if (error_reporting() & $level) {
                $this->handleDeprecationError($message, $file, $line, $level);
            }
        } elseif ($this->isWarning($level)) {
            $this->handleWarningError($message, $file, $line, $level);
        } elseif ($this->isNotice($level)) {
            $this->handleNoticeError($message, $file, $line, $level);
        } elseif (error_reporting() & $level) {
            throw new ErrorException($message, 0, $level, $file, $line);
        }
    }

    /**
     * Report an error without throwing it.
     *
     * Bails out if logging is unavailable, otherwise a failing report would
     * exit(1) a console command.
     *
     * @param  string  $message
     * @param  string  $file
     * @param  int  $line
     * @param  int  $level
     * @return void
     */
    protected function reportError($message, $file, $line, $level)
    {
        try {
            static::$app->make(LogManager::class);
        } catch (Exception) {
            return;
        }

        static::$reservedMemory = null;

        try {
            $this->getExceptionHandler()
                ->report(new ErrorException($message, 0, $level, $file, $line));
        } catch (Exception) {
            $exceptionHandlerFailed = true;
        }

        if (static::$app->runningInConsole() && ($exceptionHandlerFailed ?? false)) {
            exit(1);
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
     * Determine if the error level is a notice.
     *
     * @param  int  $level
     * @return bool
     */
    protected function isNotice($level)
    {
        return in_array($level, [E_NOTICE, E_USER_NOTICE]);
    }

    /**
     * Report a warning without throwing.
     *
     * PHP and WordPress carry on after a warning, so we do as well.
     *
     * @param  string  $message
     * @param  string  $file
     * @param  int  $line
     * @param  int  $level
     * @return void
     */
    public function handleWarningError($message, $file, $line, $level = E_WARNING)
    {
        // Respect @-suppression and error_reporting settings
        if (! (error_reporting() & $level)) {
            return;
        }

        $this->reportError($message, $file, $line, $level);
    }

    /**
     * Report a notice without throwing.
     *
     * @param  string  $message
     * @param  string  $file
     * @param  int  $line
     * @param  int  $level
     * @return void
     */
    public function handleNoticeError($message, $file, $line, $level = E_NOTICE)
    {
        // Respect @-suppression and error_reporting settings
        if (! (error_reporting() & $level)) {
            return;
        }

        $this->reportError($message, $file, $line, $level);
    }
}

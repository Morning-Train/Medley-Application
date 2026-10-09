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
     * The error handler that was registered before ours.
     *
     * @var callable|null
     */
    protected $previousErrorHandler = null;

    /**
     * Whether an uncaught exception has already been handled.
     *
     * Handing it to WordPress by rethrowing it raises a fatal error, which would
     * otherwise bring us back here through the shutdown handler and report it twice.
     */
    protected bool $handledException = false;

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

        $this->previousErrorHandler = $this->currentErrorHandler();

        // Laravel forces error_reporting(-1) and display_errors=Off. Keep what WordPress
        // configured, so WP_DEBUG and WP_DEBUG_DISPLAY keep controlling those.
        $errorReporting = error_reporting();
        $displayErrors = ini_get('display_errors');

        parent::bootstrap($app);

        error_reporting($errorReporting);
        ini_set('display_errors', $displayErrors);

        // We write everything to debug.log ourselves, so PHP's own logging would only duplicate it.
        if ($this->logsToDebugFile($app)) {
            ini_set('log_errors', '0');
        }
    }

    /**
     * Handle an uncaught exception, once.
     *
     * @param  \Throwable  $e
     * @return void
     */
    public function handleException(Throwable $e)
    {
        if ($this->handledException) {
            return;
        }

        $this->handledException = true;

        parent::handleException($e);
    }

    /**
     * Report PHP errors, and let PHP and WordPress keep their normal handling of them.
     *
     * Deprecations go to the deprecations logger. Warnings and notices are reported, not thrown,
     * as PHP carries on after them. Other levels are reported, and thrown when Ignition is shown.
     *
     * @param  int  $level
     * @param  string  $message
     * @param  string  $file
     * @param  int  $line
     * @return bool|null false lets PHP's standard error handling continue, anything else ends it
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
            // E_USER_ERROR and the like end the request anyway. Always report them, as code that
            // catches the exception would otherwise hide them. When errors are shown with
            // Ignition, also throw the same exception, so they get the error page. Otherwise PHP ends it.
            $exception = new ErrorException($message, 0, $level, $file, $line);

            $this->reportException($exception);

            if (config('app.show_ignition')) {
                throw $exception;
            }
        }

        // Everything is reported, but PHP and WordPress keep their normal handling.
        return $this->forwardError($level, $message, $file, $line);
    }

    /**
     * Determine if the default log channel writes to debug.log.
     */
    protected function logsToDebugFile(Application $app): bool
    {
        $config = $app['config'];

        if (! $config->get('app.debug_log')) {
            return false;
        }

        $default = $config->get('logging.default');
        $channels = $config->get("logging.channels.{$default}.channels", [$default]);

        return in_array('debug', (array) $channels, true);
    }

    /**
     * Get the currently registered error handler without changing it.
     */
    protected function currentErrorHandler(): ?callable
    {
        $handler = set_error_handler(static fn () => false);
        restore_error_handler();

        return $handler;
    }

    /**
     * Hand the error on to the previous handler, or to PHP, so WordPress and PHP behave as usual.
     *
     * @param  int  $level
     * @param  string  $message
     * @param  string  $file
     * @param  int  $line
     * @return bool|null
     */
    protected function forwardError($level, $message, $file, $line)
    {
        if ($this->previousErrorHandler) {
            return ($this->previousErrorHandler)($level, $message, $file, $line);
        }

        // false lets PHP's standard handling continue. That displays errors on screen,
        // and it is what terminates the script on E_USER_ERROR.
        $continues = $this->isWarning($level) || $this->isNotice($level) || $this->isDeprecation($level);

        return ! $continues || config('app.debug_display') ? false : null;
    }

    /**
     * Report an error without throwing it.
     *
     * @param  string  $message
     * @param  string  $file
     * @param  int  $line
     * @param  int  $level
     * @return void
     */
    protected function reportError($message, $file, $line, $level)
    {
        $this->reportException(new ErrorException($message, 0, $level, $file, $line));
    }

    /**
     * Report an error exception without throwing it.
     *
     * Bails out if logging is unavailable, otherwise a failing report would
     * exit(1) a console command.
     *
     * @param  \ErrorException  $exception
     * @return void
     */
    protected function reportException(ErrorException $exception)
    {
        try {
            static::$app->make(LogManager::class);
        } catch (Exception) {
            return;
        }

        static::$reservedMemory = null;

        try {
            $this->getExceptionHandler()->report($exception);
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

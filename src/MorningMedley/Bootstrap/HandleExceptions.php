<?php

namespace MorningMedley\Application\Bootstrap;

use ErrorException;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Log\LogManager;
use MorningMedley\Application\Exceptions\PhpNotice;
use MorningMedley\Application\Exceptions\PhpWarning;
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

        $this->handleRayExceptions($app);
        $this->previousErrorHandler = $this->currentErrorHandler();

        // Laravel forces error_reporting(-1) and display_errors=Off. Keep what WordPress
        // configured, so WP_DEBUG and WP_DEBUG_DISPLAY keep controlling those.
        $errorReporting = error_reporting();
        $displayErrors = ini_get('display_errors');

        parent::bootstrap($app);

        error_reporting($errorReporting);
        ini_set('display_errors', $displayErrors);

        $this->labelWarningsForBugsnag($app);

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
            $this->handleDeprecationError($message, $file, $line, $level);
        } elseif ($this->isWarning($level)) {
            $this->handleWarningError($message, $file, $line, $level);
        } elseif ($this->isNotice($level)) {
            $this->handleNoticeError($message, $file, $line, $level);
        } elseif (error_reporting() & $level) {
            // E_USER_ERROR and the like end the request anyway. Always report them, as code that
            // catches the exception would otherwise hide them. When errors are shown with
            // Ignition, also throw the same exception, so they get the error page. Otherwise PHP ends it.
            $exception = $this->makeErrorException($message, $file, $line, $level);

            $this->reportException($exception);

            if (config('app.show_ignition')) {
                throw $exception;
            }
        }

        // Everything is reported, but PHP and WordPress keep their normal handling.
        return $this->forwardError($level, $message, $file, $line);
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
    public function handleDeprecationError($message, $file, $line, $level = E_DEPRECATED)
    {
        // Respect @-suppression and error_reporting settings
        if (! (error_reporting() & $level)) {
            return;
        }

        parent::handleDeprecationError($message, $file, $line);
    }

    // Custom methods

    /**
     * Send a reported throwable to Ray, if it is an exception or a fatal error.
     *
     * Warnings, notices and deprecations are reported as ErrorExceptions, but nothing was
     * thrown, and Ray paints every exception item red. Those stay out of Ray, so a red item
     * means something actually went wrong. They are still logged and sent to Bugsnag.
     */
    protected function handleRayExceptions(Application $app): void
    {
        if ($app['config']->get('app.send_exceptions_to_ray', false) && function_exists('ray')) {
            $app->make(\Illuminate\Contracts\Debug\ExceptionHandler::class)->reportable(function (\Throwable $e) {
                if (
                    $e instanceof ErrorException &&
                    ($this->isWarning($e->getSeverity()) || $this->isNotice($e->getSeverity()) || $this->isDeprecation($e->getSeverity()))
                ) {
                    return;
                }

                ray()->exception($e);
            });
        }
    }

    /**
     * Show reported warnings and notices in Bugsnag as "Log warning" and "Log notice".
     *
     * That is how Bugsnag titles log messages. Only the title changes: the event keeps
     * its real file, line and stack trace, so grouping stays per origin.
     */
    protected function labelWarningsForBugsnag(Application $app): void
    {
        $app->booted(function () use ($app) {
            if (! $app->bound('bugsnag')) {
                return;
            }

            $app->make('bugsnag')->registerCallback(function ($report) {
                $name = match ($report->getName()) {
                    PhpWarning::class => 'Log warning',
                    PhpNotice::class => 'Log notice',
                    default => null,
                };

                if ($name !== null) {
                    $report->setName($name);
                }
            });
        });
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
        $carriesOn = $this->isWarning($level) || $this->isNotice($level) || $this->isDeprecation($level);

        if (! $carriesOn) {
            return false;
        }

        // PHP carries on after warnings, notices and deprecations. Show them if errors are shown.
        return config('app.debug') ? false : null;
    }

    /**
     * Create the exception an error is reported as.
     *
     * Warnings and notices get their own class, so reporting tools label them
     * as such and do not group them with real errors.
     *
     * @param  string  $message
     * @param  string  $file
     * @param  int  $line
     * @param  int  $level
     * @return \ErrorException
     */
    protected function makeErrorException($message, $file, $line, $level): ErrorException
    {
        $class = match (true) {
            $this->isWarning($level) => PhpWarning::class,
            $this->isNotice($level) => PhpNotice::class,
            default => ErrorException::class,
        };

        return new $class($message, 0, $level, $file, $line);
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
        $this->reportException($this->makeErrorException($message, $file, $line, $level));
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

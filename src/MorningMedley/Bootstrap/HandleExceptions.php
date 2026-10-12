<?php

namespace MorningMedley\Application\Bootstrap;

use ErrorException;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Log\LogManager;
use MorningMedley\Application\Exceptions\PhpDeprecation;
use MorningMedley\Application\Exceptions\PhpNotice;
use MorningMedley\Application\Exceptions\PhpWarning;
use Throwable;

class HandleExceptions extends \Illuminate\Foundation\Bootstrap\HandleExceptions
{
    /**
     * The levels that PHP carries on after, and the exception each is reported as.
     *
     * Reporting tools such as Bugsnag then label them as a warning, a notice or a deprecation,
     * and do not group them with real errors.
     *
     * @var array<int, class-string<\ErrorException>>
     */
    protected const NON_FATAL_ERRORS = [
        E_WARNING => PhpWarning::class,
        E_USER_WARNING => PhpWarning::class,
        E_NOTICE => PhpNotice::class,
        E_USER_NOTICE => PhpNotice::class,
        E_DEPRECATED => PhpDeprecation::class,
        E_USER_DEPRECATED => PhpDeprecation::class,
    ];

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
     *
     * It is never reset on purpose. PHP ends the process once the exception handler has run, so
     * there is no second uncaught exception to handle. Exceptions in a queue worker are caught by
     * the worker and reported through the exception handler, not through this method. Every
     * bootstrap creates a new instance, so the flag starts out false in tests too.
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
     * Warnings, notices and deprecations are reported, not thrown, as PHP carries on after them.
     * Other levels, such as E_USER_ERROR, end the request anyway. They are also thrown when
     * errors are shown with Ignition, so they get the error page.
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
        // Respect @-suppression and error_reporting settings
        if (error_reporting() & $level) {
            $exception = $this->makeErrorException($message, $file, $line, $level);

            // Report it before it is thrown, as code that catches the exception would hide it. This is cheap
            // enough to do for every error, about 8 µs with no channel configured, so there is no check for a
            // channel that accepts the level. PHP does not call this handler again for an error raised while
            // it runs, so a channel that raises a warning or a deprecation cannot make this recurse.
            $this->reportException($exception);

            if (! isset(static::NON_FATAL_ERRORS[$level]) && config('app.show_ignition')) {
                throw $exception;
            }
        }

        return $this->forwardError($level, $message, $file, $line);
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
                if ($e instanceof ErrorException && isset(static::NON_FATAL_ERRORS[$e->getSeverity()])) {
                    return;
                }

                ray()->exception($e);
            });
        }
    }

    /**
     * Show reported warnings, notices and deprecations in Bugsnag as "Log warning", "Log notice"
     * and "Log deprecation".
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
                    PhpDeprecation::class => 'Log deprecation',
                    default => null,
                };

                if ($name !== null) {
                    $report->setName($name);
                }
            });
        });
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

        // false lets PHP's standard handling continue. That writes the error to the log file, shows
        // it when errors are displayed and ends the script on E_USER_ERROR, as WordPress configured.
        return false;
    }

    /**
     * Create the exception an error is reported as.
     *
     * @param  string  $message
     * @param  string  $file
     * @param  int  $line
     * @param  int  $level
     * @return \ErrorException
     */
    protected function makeErrorException($message, $file, $line, $level): ErrorException
    {
        $class = static::NON_FATAL_ERRORS[$level] ?? ErrorException::class;

        return new $class($message, 0, $level, $file, $line);
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

}

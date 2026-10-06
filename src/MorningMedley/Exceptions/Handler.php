<?php

namespace MorningMedley\Application\Exceptions;

use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Foundation\ExceptionRenderer;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\MultipleRecordsFoundException;
use Illuminate\Database\RecordNotFoundException;
use Illuminate\Database\RecordsNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\HttpFoundation\Exception\RequestExceptionInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends \Illuminate\Foundation\Exceptions\Handler
{
    /**
     * A list of the internal exception types that should not be reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $internalDontReport = [
        HttpException::class,
        HttpResponseException::class,
        ModelNotFoundException::class,
        MultipleRecordsFoundException::class,
        RecordNotFoundException::class,
        RecordsNotFoundException::class,
        RequestExceptionInterface::class,
        TokenMismatchException::class,
    ];

    protected $severity = [
        E_ERROR => LogLevel::ERROR,
        E_CORE_ERROR => LogLevel::ERROR,
        E_COMPILE_ERROR => LogLevel::ERROR,
        E_USER_ERROR => LogLevel::ERROR,
        E_RECOVERABLE_ERROR => LogLevel::ERROR,

        E_WARNING => LogLevel::WARNING,
        E_CORE_WARNING => LogLevel::WARNING,
        E_COMPILE_WARNING => LogLevel::WARNING,
        E_USER_WARNING => LogLevel::WARNING,

        E_NOTICE => LogLevel::NOTICE,
        E_USER_NOTICE => LogLevel::NOTICE,

        E_DEPRECATED => LogLevel::NOTICE,
        E_USER_DEPRECATED => LogLevel::NOTICE,
    ];

    /**
     * Get the default context variables for logging.
     *
     * @return array
     */
    protected function context()
    {
        try {
            return array_filter([
                'userId' => \get_current_user_id(),
            ]);
        } catch (\Throwable) {
            return [];
        }
    }

    public function report(\Throwable $e): void
    {
        if (! config('app.debug')) {
            return; // logging disabled
        }

        parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, \Throwable $e)
    {
        $e = $this->mapException($e);
        if (method_exists($e, 'render') && $response = $e->render($request)) {
            return $response;
        }

        if ($e instanceof Responsable) {
            return $this->finalizeRenderedResponse($request, $e->toResponse($request), $e);
        }

        $e = $this->prepareException($e);

        if ($response = $this->renderViaCallbacks($request, $e)) {
            return $this->finalizeRenderedResponse($request, $response, $e);
        }

        if (config('app.show_ignition') && app()->has(ExceptionRenderer::class)) {
            return new Response(
                app(ExceptionRenderer::class)->render($e),
                $this->isHttpException($e) ? $e->getStatusCode() : 500,
                $this->isHttpException($e) ? $e->getHeaders() : [],
            );
        }

        if ($this->isHttpException($e)) {
            return $this->renderHttpException($e);
        }

        // Never render a Laravel/Symfony error page — hand off to WordPress.
        throw $e;
    }

    /**
     * Map the exception to a log level.
     *
     * @param  \Throwable  $e
     * @return \Psr\Log\LogLevel::*
     */
    protected function mapLogLevel(Throwable $e)
    {
        if ($e instanceof \ErrorException) {
            return $this->severity[$e->getSeverity()] ?? LogLevel::ERROR;
        }

        return parent::mapLogLevel($e);
    }
}

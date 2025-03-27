<?php

namespace MorningMedley\Application\Http;

use Closure;
use \Illuminate\Contracts\Routing\ResponseFactory as FactoryContract;
use Illuminate\Http\JsonResponse;
use \Illuminate\Http\Response;
use Illuminate\Http\StreamedEvent;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedJsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResponseFactory implements FactoryContract
{

    /**
     * The view factory instance.
     *
     * @var ?\Illuminate\Contracts\View\Factory
     */
    protected $view;

    /**
     * Create a new response factory instance.
     *
     * @param  \Illuminate\Contracts\View\Factory  $view
     * @return void
     */
    public function __construct(?ViewFactory $view)
    {
        $this->view = $view;
    }

    /**
     * Create a new response instance.
     *
     * @param  mixed  $content
     * @param  int  $status
     * @param  array  $headers
     * @return \Illuminate\Http\Response
     */
    public function make($content = '', $status = 200, array $headers = [])
    {
        return new Response($content, $status, $headers);
    }

    /**
     * Create a new "no content" response.
     *
     * @param  int  $status
     * @param  array  $headers
     * @return \Illuminate\Http\Response
     */
    public function noContent($status = 204, array $headers = [])
    {
        return $this->make('', $status, $headers);
    }

    /**
     * Create a new response for a given view.
     *
     * @param  string|array  $view
     * @param  array  $data
     * @param  int  $status
     * @param  array  $headers
     * @return \Illuminate\Http\Response
     */
    public function view($view, $data = [], $status = 200, array $headers = [])
    {
        if(!$this->view){
            throw new \Exception("\Illuminate\Contracts\View\Factory not found");
        }

        if (is_array($view)) {
            return $this->make($this->view->first($view, $data), $status, $headers);
        }

        return $this->make($this->view->make($view, $data), $status, $headers);
    }

    /**
     * Create a new JSON response instance.
     *
     * @param  mixed  $data
     * @param  int  $status
     * @param  array  $headers
     * @param  int  $options
     * @return \Illuminate\Http\JsonResponse
     */
    public function json($data = [], $status = 200, array $headers = [], $options = 0)
    {
        return new JsonResponse($data, $status, $headers, $options);
    }

    /**
     * Create a new JSONP response instance.
     *
     * @param  string  $callback
     * @param  mixed  $data
     * @param  int  $status
     * @param  array  $headers
     * @param  int  $options
     * @return \Illuminate\Http\JsonResponse
     */
    public function jsonp($callback, $data = [], $status = 200, array $headers = [], $options = 0)
    {
        return $this->json($data, $status, $headers, $options)->setCallback($callback);
    }

    /**
     * Create a new event stream response.
     *
     * @param  \Closure  $callback
     * @param  array  $headers
     * @param  string  $endStreamWith
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function eventStream(Closure $callback, array $headers = [], string $endStreamWith = '</stream>')
    {
        return $this->stream(function () use ($callback, $endStreamWith) {
            foreach ($callback() as $message) {
                if (connection_aborted()) {
                    break;
                }

                if (! is_string($message) && ! is_numeric($message)) {
                    $message = Js::encode($message);
                }

                echo "event: update\n";
                echo 'data: '.$message;
                echo "\n\n";

                ob_flush();
                flush();
            }

            echo "event: update\n";
            echo 'data: '.$endStreamWith;
            echo "\n\n";

            ob_flush();
            flush();
        }, 200, array_merge($headers, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]));
    }

    /**
     * Create a new streamed response instance.
     *
     * @param  callable  $callback
     * @param  int  $status
     * @param  array  $headers
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function stream($callback, $status = 200, array $headers = [])
    {
        return new StreamedResponse($callback, $status, $headers);
    }

    /**
     * Create a new streamed response instance.
     *
     * @param  array  $data
     * @param  int  $status
     * @param  array  $headers
     * @param  int  $encodingOptions
     * @return \Symfony\Component\HttpFoundation\StreamedJsonResponse
     */
    public function streamJson($data, $status = 200, $headers = [], $encodingOptions = JsonResponse::DEFAULT_ENCODING_OPTIONS)
    {
        return new StreamedJsonResponse($data, $status, $headers, $encodingOptions);
    }

    /**
     * Create a new streamed response instance as a file download.
     *
     * @param  callable  $callback
     * @param  string|null  $name
     * @param  array  $headers
     * @param  string|null  $disposition
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function streamDownload($callback, $name = null, array $headers = [], $disposition = 'attachment')
    {
        $withWrappedException = function () use ($callback) {
            $callback();
        };

        $response = new StreamedResponse($withWrappedException, 200, $headers);

        if (! is_null($name)) {
            $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
                $disposition,
                $name,
                $this->fallbackName($name)
            ));
        }

        return $response;
    }

    /**
     * Create a new file download response.
     *
     * @param  \SplFileInfo|string  $file
     * @param  string|null  $name
     * @param  array  $headers
     * @param  string|null  $disposition
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function download($file, $name = null, array $headers = [], $disposition = 'attachment')
    {
        $response = new BinaryFileResponse($file, 200, $headers, true, $disposition);

        if (! is_null($name)) {
            return $response->setContentDisposition($disposition, $name, $this->fallbackName($name));
        }

        return $response;
    }

    /**
     * Convert the string to ASCII characters that are equivalent to the given name.
     *
     * @param  string  $name
     * @return string
     */
    protected function fallbackName($name)
    {
        return str_replace('%', '', Str::ascii($name));
    }

    /**
     * Return the raw contents of a binary file.
     *
     * @param  \SplFileInfo|string  $file
     * @param  array  $headers
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function file($file, array $headers = [])
    {
        return new BinaryFileResponse($file, 200, $headers);
    }

    public function redirectTo($path, $status = 302, $headers = [], $secure = null)
    {
        // TODO: Implement redirectTo() method.
    }

    public function redirectToRoute($route, $parameters = [], $status = 302, $headers = [])
    {
        // TODO: Implement redirectToRoute() method.
    }

    public function redirectToAction($action, $parameters = [], $status = 302, $headers = [])
    {
        // TODO: Implement redirectToAction() method.
    }

    public function redirectGuest($path, $status = 302, $headers = [], $secure = null)
    {
        // TODO: Implement redirectGuest() method.
    }

    public function redirectToIntended($default = '/', $status = 302, $headers = [], $secure = null)
    {
        // TODO: Implement redirectToIntended() method.
    }
}

<?php

namespace MorningMedley\Application\Bootstrap;

use Illuminate\Contracts\Foundation\Application;
use Throwable;

class HandleExceptions extends \Illuminate\Foundation\Bootstrap\HandleExceptions
{
    /**
     * Render an exception as an HTTP response and send it.
     *
     * @param  \Throwable  $e
     * @return void
     */
    protected function renderHttpResponse(Throwable $e)
    {
        $this->getExceptionHandler()->render(null, $e)->send();
    }
}

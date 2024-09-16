<?php

namespace MorningMedley\Application\Http;

use Throwable;

class ExceptionHandler implements \Illuminate\Contracts\Debug\ExceptionHandler
{

    /**
     * @inheritDoc
     */
    public function report(Throwable $e)
    {
        if (! $this->shouldReport($e)) {
            return;
        }
        $errorStr = "<pre><h2>A exception occurred.</h2>" . $e->getMessage() . "<br/><hr/><br/>";
        $errorStr .= print_r(debug_backtrace(), true);
        $errorStr .= "</pre>";
        \wp_die($errorStr);
    }

    /**
     * @inheritDoc
     */
    public function shouldReport(Throwable $e)
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function render($request, Throwable $e)
    {

        $this->report($e);
    }

    /**
     * @inheritDoc
     */
    public function renderForConsole($output, Throwable $e)
    {
        \WP_CLI::error($e->getMessage());
    }
}

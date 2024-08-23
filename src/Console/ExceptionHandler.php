<?php

    namespace MorningMedley\Application\Console;

    use Throwable;

    class ExceptionHandler implements \Illuminate\Contracts\Debug\ExceptionHandler
    {

        /**
         * @inheritDoc
         */
        public function report(Throwable $e)
        {
            \WP_CLI::error($e->getMessage());
        }

        /**
         * @inheritDoc
         */
        public function shouldReport(Throwable $e)
        {
            \WP_CLI::error($e->getMessage());
        }

        /**
         * @inheritDoc
         */
        public function render($request, Throwable $e)
        {
            echo $e->getMessage();
        }

        /**
         * @inheritDoc
         */
        public function renderForConsole($output, Throwable $e)
        {
            \WP_CLI::error($e->getMessage());
        }
    }

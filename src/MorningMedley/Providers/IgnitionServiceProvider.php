<?php

namespace MorningMedley\Application\Providers;

    use Illuminate\Contracts\Foundation\Application;
    use Spatie\Ignition\Ignition;
    use Spatie\LaravelIgnition\FlareMiddleware\AddJobs;
    use Spatie\LaravelIgnition\FlareMiddleware\AddLogs;
    use Spatie\LaravelIgnition\FlareMiddleware\AddQueries;
    use Spatie\LaravelIgnition\Recorders\DumpRecorder\DumpRecorder;
    use Spatie\LaravelIgnition\Recorders\JobRecorder\JobRecorder;
    use Spatie\LaravelIgnition\Recorders\LogRecorder\LogRecorder;
    use Spatie\LaravelIgnition\Recorders\QueryRecorder\QueryRecorder;
    use Spatie\LaravelIgnition\Renderers\IgnitionExceptionRenderer;

    class IgnitionServiceProvider extends \Spatie\LaravelIgnition\IgnitionServiceProvider
    {
        public function register(): void
        {
            parent::register();
            $this->app->make(Ignition::class)->addCustomHtmlToHead("
<style>
    #app{position: absolute; top:0; right:0;bottom:0;left:0;z-index:999999}
    #app, html.dark body{background-color:#061d1e;} 
    html.dark .\~bg-body{background-color:#061d1e; box-shadow: 0 3px 32px #0000000a}
    html.dark .\~text-indigo-600{color:#ddffd7}
    html.dark .\~bg-white{background-color: rgb(31 55 53)}
    .bg-red-500{background-color: #ddffd7; color: black}
    html.dark .dark\:bg-gray-800\/50 {
        background-color: rgb(31 55 47 / 50%);
    }
    .\~bg-red-500\/20, .dark .hover\:\~bg-red-500\/10:hover {
        background-color: rgb(68 239 172 / 20%);
    }
    .text-red-500{color:#ddffd7;}
    .hover\:text-red-500:hover{color:#ddffd7;}
</style>");
        }

        protected function registerRenderer(): void
        {
            $this->app->bind(
                'Illuminate\Contracts\Foundation\ExceptionRenderer',
                fn (Application $app) => $app->make(IgnitionExceptionRenderer::class)
            );
        }

        protected function registerRecorders(): void
        {
            $this->app->singleton(DumpRecorder::class);

            $this->app->singleton(LogRecorder::class, function (Application $app): LogRecorder {
                return new LogRecorder(
                    $app,
                    config()->get('flare.flare_middleware.' . AddLogs::class . '.maximum_number_of_collected_logs')
                );
            });

            $this->app->singleton(
                QueryRecorder::class,
                function (Application $app): QueryRecorder {
                    return new QueryRecorder(
                        $app,
                        config('flare.flare_middleware.' . AddQueries::class . '.report_query_bindings', true),
                        config('flare.flare_middleware.' . AddQueries::class . '.maximum_number_of_collected_queries', 200)
                    );
                }
            );

            $this->app->singleton(JobRecorder::class, function (Application $app): JobRecorder {
                return new JobRecorder(
                    $app,
                    config('flare.flare_middleware.' . AddJobs::class . '.max_chained_job_reporting_depth', 5)
                );
            });
        }

        protected function registerRoutes(): void
        {
//            $this->loadRoutesFrom(realpath(__DIR__ . '/ignition-routes.php'));
        }
    }

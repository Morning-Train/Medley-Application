<?php

    namespace MorningMedley\Application;

    use Illuminate\Container\Container;

    class Cli
    {
        private Container $app;

        public function __construct(Container $app)
        {
            $this->app = $app;
        }

        public function clearCache()
        {
            if($this->app->make('filecachemanager')->clearAllCaches()){
                \WP_Cli::success('All file caches have been cleared');
            }else{
                \WP_Cli::error('Some file caches may not have been cleared');
            }
        }
    }

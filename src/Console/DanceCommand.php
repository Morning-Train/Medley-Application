<?php

    namespace MorningMedley\Application\Console;

    use Illuminate\Console\Command;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Output\OutputInterface;

    class DanceCommand extends Command
    {
        protected $signature = 'dance';
        protected $description = 'Do a little dance';


        public function handle()
        {
            dump('IM DAAANCING!!!');
        }
    }

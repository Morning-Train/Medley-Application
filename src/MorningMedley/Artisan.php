<?php

namespace MorningMedley\Application;

class Artisan extends \Illuminate\Console\Application
{
    public function getLongVersion(): string
    {
        $name = parent::getLongVersion();
        $medleyVersion = $this->laravel?->medleyVersion();
        if ($medleyVersion) {
            $name .= ' - ';
            $name .= \sprintf('%s <info>%s</info>', 'MorningMedley', $medleyVersion);
        }

        return $name;
    }
}

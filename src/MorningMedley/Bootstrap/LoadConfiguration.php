<?php

namespace MorningMedley\Application\Bootstrap;

use Symfony\Component\Finder\Finder;

class LoadConfiguration extends \Illuminate\Foundation\Bootstrap\LoadConfiguration
{
    protected function getBaseConfiguration()
    {
        $config = [];

        foreach (Finder::create()->files()->name('*.php')->in(__DIR__ . '/../../config') as $file) {
            $config[basename($file->getRealPath(), '.php')] = require $file->getRealPath();
        }

        return $config;
    }
}

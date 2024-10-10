<?php

namespace MorningMedley\Application\Translation;

use Illuminate\Contracts\Foundation\Application;

class NullTranslator implements \Illuminate\Contracts\Translation\Translator
{

    public function __construct(private Application $application)
    {
    }

    public function get($key, array $replace = [], $locale = null)
    {
        return '';
    }

    public function choice($key, $number, array $replace = [], $locale = null)
    {
        return '';
    }

    public function getLocale()
    {
        return $this->application->getLocale();
    }

    public function setLocale($locale)
    {
        $this->application->setLocale($locale);
    }
}

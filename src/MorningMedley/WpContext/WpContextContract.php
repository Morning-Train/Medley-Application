<?php

namespace MorningMedley\Application\WpContext;

interface WpContextContract
{
    public function __construct(string $name, string $description, string $version, string $textDomain);
}

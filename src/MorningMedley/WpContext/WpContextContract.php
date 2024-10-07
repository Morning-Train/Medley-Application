<?php

namespace MorningMedley\Application\WpContext;

interface WpContextContract
{
    public function __construct(string $name, string $url, string $description, string $version, string $textDomain);

    public function name(): string;

    public function url(): string;

    public function description(): string;

    public function version(): string;

    public function textDomain(): string;
}

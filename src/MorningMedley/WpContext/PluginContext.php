<?php

namespace MorningMedley\Application\WpContext;

class PluginContext implements WpContextContract
{
    public function __construct(
        public readonly string $name,
        public readonly string $url,
        public readonly string $description,
        public readonly string $version,
        public readonly string $textDomain
    ) {
    }
}

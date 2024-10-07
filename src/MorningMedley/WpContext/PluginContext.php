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

    public function name(): string
    {
        return $this->name;
    }

    public function url(): string
    {
        return $this->url;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function version(): string
    {
        return $this->version;
    }

    public function textDomain(): string
    {
        return $this->textDomain;
    }
}

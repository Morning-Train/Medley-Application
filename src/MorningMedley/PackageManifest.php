<?php

    namespace MorningMedley\Application;

    class PackageManifest extends \Illuminate\Foundation\PackageManifest
    {
        public function build()
        {
            $packages = [];

            if ($this->files->exists($path = $this->vendorPath.'/composer/installed.json')) {
                $installed = json_decode($this->files->get($path), true);

                $packages = $installed['packages'] ?? $installed;
            }

            $ignoreAll = in_array('*', $ignore = $this->packagesToIgnore());

            $this->write(collect($packages)->mapWithKeys(function ($package) {
                return [$this->format($package['name']) => $package['extra']['morningmedley'] ?? []];
            })->each(function ($configuration) use (&$ignore) {
                $ignore = array_merge($ignore, $configuration['dont-discover'] ?? []);
            })->reject(function ($configuration, $package) use ($ignore, $ignoreAll) {
                return $ignoreAll || in_array($package, $ignore);
            })->filter()->all());
        }

        protected function packagesToIgnore()
        {
            if (! is_file($this->basePath.'/composer.json')) {
                return [];
            }

            return json_decode(file_get_contents(
                $this->basePath.'/composer.json'
            ), true)['extra']['morningmedley']['dont-discover'] ?? [];
        }
    }

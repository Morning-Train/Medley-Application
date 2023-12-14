<?php

namespace MorningMedley\Application;

use Illuminate\Container\Container;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;

class FileCacheManager
{
    private Container $app;
    /** @var PhpFilesAdapter[] $caches */
    protected array $caches = [];

    public function __construct(Container $app)
    {
        $this->app = $app;
    }

    public function registerCache(string $namespace, $lifeTime = DAY_IN_SECONDS): PhpFilesAdapter
    {
        $this->caches[$namespace] = $this->app->makeWith(PhpFilesAdapter::class,
            [
                'namespace' => $namespace,
                'defaultLifetime' => $lifeTime,
                'directory' => $this->app->getCachedConfigPath(),
            ]);

        return $this->caches[$namespace];
    }

    public function getCache(string $namespace): ?PhpFilesAdapter
    {
        if(!isset($this->caches[$namespace])){
            $this->registerCache($namespace);
        }

        return $this->caches[$namespace] ?? null;
    }

    public function getRegisteredCaches(): array
    {
        return $this->caches;
    }

    public function clearAllCaches(): bool
    {
        $success = true;
        foreach ($this->caches as $cache) {
            if (! $cache->clear()) {
                $success = false;
            }
        }

        return $success;
    }
}

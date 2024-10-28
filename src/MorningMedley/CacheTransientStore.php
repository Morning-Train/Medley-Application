<?php

namespace MorningMedley\Application;

use MorningMedley\Application\WpContext\WpContextContract;
use Illuminate\Contracts\Cache\Store;

class CacheTransientStore implements Store
{

    public function __construct(private string $prefix)
    {
    }

    public function get($key): mixed
    {
        $value = \get_transient($this->prefix . $key);
        if ($value === false) {
            $value = null;
        }

        return $value;
    }

    public function many(array $keys): array
    {
        return array_map([$this, 'get'], array_combine($keys, $keys));
    }

    public function put($key, $value, $seconds): bool
    {
        return \set_transient($this->prefix . $key, $value, $seconds);
    }

    public function putMany(array $values, $seconds): bool
    {
        $success = true;
        foreach ($values as $key => $value) {
            if (! $this->put($key, $value, $seconds)) {
                $success = false;
            }
        }

        return $success;
    }

    public function increment($key, $value = 1): false|int
    {
        return $this->incrementOrDecrement($key, $value, function ($current, $value) {
            return $current + $value;
        });
    }

    public function decrement($key, $value = 1): false|int
    {
        return $this->incrementOrDecrement($key, $value, function ($current, $value) {
            return $current - $value;
        });
    }

    public function incrementOrDecrement($key, $value, \Closure $callback): false|int
    {
        $current = $this->get($key);

        $new = $callback((int) $current, $value);

        if (! is_numeric($current)) {
            return false;
        }

        $this->put($key, $new, 0);

        return $new;
    }

    public function forever($key, $value): bool
    {
        return \set_transient($this->prefix . $key, $value);
    }

    public function forget($key): bool
    {
        return \delete_transient($this->prefix . $key);
    }

    public function flush(): bool
    {
        // This is not supported through WordPress transients and could, potentially, be unreliable
        return false;
    }

    public function getPrefix()
    {
        return $this->prefix;
    }

    public function setPrefix(string $prefix)
    {
        $this->prefix = $prefix;
    }
}

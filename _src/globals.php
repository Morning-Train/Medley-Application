<?php

namespace MorningMedley\Functions;

use Illuminate\Support\Env;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Facades\View;

/**
 * Get the Medley Application Object
 *
 * @return \MorningMedley\Application\Application|mixed
 */
function app(?string $abstract = null, $makeWithArgs = null): mixed
{
    if ($abstract === null) {
        return $GLOBALS['medleyApp'];
    }

    if ($makeWithArgs !== null) {
        return $GLOBALS['medleyApp']->makeWith($abstract, $makeWithArgs);
    }

    return $GLOBALS['medleyApp']->make($abstract);
}

/**
 * Access the config.
 *
 * @param  string|array|null  $key
 * @param  mixed  $default
 * @return mixed|void|null
 */
function config(null|string|array $key = null, $default = null)
{
    $config = $GLOBALS['medleyApp']->make('config');

    if ($key === null) {
        return $config;
    }

    if (is_array($key)) {
        foreach ($key as $k => $v) {
            $config->set($k, $v);
        }
    } else {
        $value = $config->get($key);

        return $value !== null ? $value : $default;
    }
}

function view(
    string $view,
    Arrayable|array $data = [],
    array $mergeData = []
): \Illuminate\Contracts\View\View {
    return View::make($view, $data, $mergeData);
}

function compose(array|string $views, \Closure|string $callback): array
{
    return View::composer($views, $callback);
}

    /**
     * Gets the value of an environment variable.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    function env($key, $default = null)
    {
        if(defined($key)){
            return constant($key);
        }

        return Env::get($key, $default);
    }

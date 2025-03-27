<?php

namespace MorningMedley\Application;

use MorningMedley\Application\WpContext\WpContextContract;

class UrlGenerator implements \Illuminate\Contracts\Routing\UrlGenerator
{

    public function __construct(protected WpContextContract $wpcontext)
    {
    }

    public function current()
    {
        // TODO: Implement current() method.
    }

    public function previous($fallback = false)
    {
        // TODO: Implement previous() method.
    }

    public function to($path, $extra = [], $secure = null)
    {
        // TODO: Implement to() method.
    }

    public function secure($path, $parameters = [])
    {
        // TODO: Implement secure() method.
    }

    public function asset($path, $secure = null)
    {
        return $this->wpcontext->url . "public/" . $path;
    }

    public function route($name, $parameters = [], $absolute = true)
    {
        // TODO: Implement route() method.
    }

    public function action($action, $parameters = [], $absolute = true)
    {
        // TODO: Implement action() method.
    }

    public function getRootControllerNamespace()
    {
        // TODO: Implement getRootControllerNamespace() method.
    }

    public function setRootControllerNamespace($rootNamespace)
    {
        // TODO: Implement setRootControllerNamespace() method.
    }

    public function signedRoute($name, $parameters = [], $expiration = null, $absolute = true)
    {
        // TODO: Implement signedRoute() method.
    }

    public function temporarySignedRoute($name, $expiration, $parameters = [], $absolute = true)
    {
        // TODO: Implement temporarySignedRoute() method.
    }

    public function query($path, $query = [], $extra = [], $secure = null)
    {
        // TODO: Implement query() method.
    }
}

<?php

if (! function_exists('register_script')) {
    function register_script(string $handle, string $file, $args = [])
    {
        $assetFile = base_path("public/" . substr($file, 0, strlen($file) - 3)) . '.asset.php';

        if (file_exists($assetFile)) {
            $details = require $assetFile;
            $version = $details['version'];
            $dependencies = $details['dependencies'];
        } else {
            $version = app(\MorningMedley\Application\WpContext\WpContextContract::class)->version();
            $dependencies = [];
        }

        \wp_enqueue_script(
            $handle,
            asset($file),
            $dependencies,
            $version,
            $args
        );
    }
}

if (! function_exists('enqueue_script')) {
    function enqueue_script(string $handle, string $file, $args = [])
    {
        register_script($handle, $file, $args);
        \wp_enqueue_script($handle);
    }
}

if (! function_exists('register_style')) {
    function register_style(string $handle, string $file, array $deps = [], $media = 'all')
    {
        $version = app(\MorningMedley\Application\WpContext\WpContextContract::class)->version();

        \wp_register_style(
            $handle,
            asset($file),
            $deps,
            $version,
            $media
        );
    }
}

if (! function_exists('enqueue_style')) {
    function enqueue_style(string $handle, string $file, array $deps = [], $media = 'all')
    {
        register_style($handle, $file, $deps, $media);
        \wp_enqueue_style($handle);
    }
}

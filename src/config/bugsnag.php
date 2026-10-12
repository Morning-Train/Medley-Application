<?php

return [
    // Bugsnag gets warnings and errors. Notices and deprecations are kept as breadcrumbs.
    // A site that wants those too sets BUGSNAG_LOGGER_LEVEL=notice, or this key in its config/bugsnag.php.
    'logger_notify_level' => env('BUGSNAG_LOGGER_LEVEL', 'warning'),
];

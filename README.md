# Application

An Illuminate Application for Morningmedley.

## Breaking changes since 0.4.8

- **Nothing logs to a file by default.** The `debug` channel and the `WP_DEBUG_LOG` path handling are removed, and the
  default stack is `null`. PHP and WordPress write the log file. A `single` channel (`storage/logs/laravel.log`) is
  defined, but it is not in the stack. A site that lists `debug` in its own `channels.stack` must change it, for example
  to `bugsnag`. With a channel that does not exist, Laravel falls back to the emergency logger, which now writes to
  `php://stderr`.
- **`app.debug` is now `WP_DEBUG && WP_DEBUG_DISPLAY`**, where 0.4.8 used `WP_DEBUG && WP_DEBUG_LOG`. It decides if
  error pages include the exception and its trace. `app.debug_log` is removed.
- **`HandleExceptions` lost `handleWarningError()`, `handleNoticeError()` and `handleDeprecationError()`.**
  `handleError()` covers every level.

Warnings, notices, deprecations, exceptions and fatal errors are reported to the channels in the stack, and a channel
chooses which it gets with its `level`. Warnings, notices and deprecations are never thrown, and PHP and WordPress
keep logging, displaying and ending the script on `E_USER_ERROR`. See the comment above the stack in
`src/config/logging.php`.

## Resources

- [Documentation](https://morningmedley.dev/)
- [Contributing](https://morningmedley.dev/docs/contributing/)

# WP Application Container

A simple Illuminate Application Container to boot your project.

## Getting started

1. Install the package with composer
2. Create a `config/config.php` file.
   - Return an array in the config file `<?php return [];`
3. Then boot up the application with `(new Application(__DIR__))->boot();`

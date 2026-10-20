<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

/*
 * ⚠️ pestphp/pest-plugin-browser 5.1 cleans up at start-up with
 * `@rmdir(tests/Browser/Traces)`. When the directory does not exist, PHP raises a
 * warning that PHPUnit 13 still records despite the `@`, and with failOnWarning (which
 * this suite keeps — constitution I) a fully green browser run exits 1. Creating the
 * directory first lets the plugin remove an empty directory without a warning.
 * Upstream behaviour, worked around here rather than by loosening the configuration.
 */
if (! is_dir(__DIR__.'/Browser/Traces')) {
    mkdir(__DIR__.'/Browser/Traces', 0777, true);
}

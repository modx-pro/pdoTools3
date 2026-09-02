<?php

/**
 * Strip test/dev files from the component tree before packaging.
 * Intended for CI release builds (mutates the working copy).
 */

$root = dirname(__FILE__, 2) . '/';
require_once $root . '_build/includes/functions.php';

$component = $root . 'core/components/pdotools/';
$fenom = $component . 'vendor/fenom/fenom/';

// Fenom package: keep only runtime sources.
if (is_dir($fenom) && ($dirs = @scandir($fenom))) {
    foreach ($dirs as $dir) {
        if (in_array($dir, ['src', 'config', 'vendor', '.', '..'], true)) {
            continue;
        }
        $path = $fenom . $dir;
        if (is_dir($path)) {
            removeDir($path);
        } elseif (is_file($path)) {
            unlink($path);
        }
    }
}

// pdoTools component: drop test harness from the transport.
foreach ([
    $component . 'tests',
    $component . '.phpunit.cache',
    $component . 'coverage',
] as $path) {
    if (is_dir($path)) {
        removeDir($path);
    }
}
foreach ([
    $component . 'phpunit.xml.dist',
    $component . 'phpunit.xml',
    $component . '.phpunit.result.cache',
] as $path) {
    if (is_file($path)) {
        unlink($path);
    }
}

echo "Prepared component tree for packaging.\n";

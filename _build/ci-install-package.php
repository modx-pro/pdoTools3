<?php

/**
 * Builds the transport package against a CLI-installed MODX tree.
 * Used by .github/workflows/phpunit.yml so the workflow has no inline php -r.
 */
$workspace = getenv('GITHUB_WORKSPACE');
if ($workspace === false || $workspace === '') {
    fwrite(STDERR, "GITHUB_WORKSPACE is not set\n");
    exit(1);
}

$_SERVER['MODX_BASE_PATH'] = rtrim($workspace, '/') . '/modx/';
require $workspace . '/_build/build.transport.php';

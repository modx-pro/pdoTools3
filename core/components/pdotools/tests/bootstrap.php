<?php

declare(strict_types=1);

$vendor = dirname(__DIR__) . '/vendor/autoload.php';
if (!is_file($vendor)) {
    fwrite(STDERR, "Run composer install in core/components/pdotools first.\n");
    exit(1);
}

require $vendor;

error_reporting(E_ALL & ~E_DEPRECATED);

$tmp = __DIR__ . '/tmp';
if (!is_dir($tmp) && !mkdir($tmp, 0777, true) && !is_dir($tmp)) {
    fwrite(STDERR, "Cannot create {$tmp}\n");
    exit(1);
}

$modxBase = getenv('MODX_TEST_BASE');
if (is_string($modxBase) && $modxBase !== '') {
    $modxBase = rtrim($modxBase, '/') . '/';
    $config = $modxBase . 'config.core.php';
    if (!is_file($config)) {
        fwrite(STDERR, "MODX_TEST_BASE is set but {$config} is missing.\n");
        exit(1);
    }
    define('MODX_API_MODE', true);
    require $config;
    require MODX_CORE_PATH . 'vendor/autoload.php';

    return;
}

if (!defined('MODX_CORE_PATH')) {
    $core = $tmp . '/core/';
    if (!is_dir($core . 'cache') && !mkdir($core . 'cache', 0777, true) && !is_dir($core . 'cache')) {
        fwrite(STDERR, "Cannot create {$core}cache\n");
        exit(1);
    }
    define('MODX_CORE_PATH', $core);
}
if (!defined('MODX_ASSETS_PATH')) {
    define('MODX_ASSETS_PATH', $tmp . '/assets/');
}
if (!defined('MODX_BASE_PATH')) {
    define('MODX_BASE_PATH', $tmp . '/');
}
if (!defined('MODX_ASSETS_URL')) {
    define('MODX_ASSETS_URL', '/assets/');
}

require __DIR__ . '/Stubs/ModxStub.php';

<?php

declare(strict_types=1);

namespace ModxPro\PdoTools\Tests\Integration;

use MODX\Revolution\modResource;
use MODX\Revolution\modSnippet;
use MODX\Revolution\modX;
use PHPUnit\Framework\TestCase;

abstract class ModxTestCase extends TestCase
{
    /** @var modX|null */
    protected static $modx;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $base = getenv('MODX_TEST_BASE');
        if (!is_string($base) || $base === '' || !defined('MODX_CORE_PATH')) {
            return;
        }

        $modx = new modX();
        $modx->initialize('web');
        $siteStart = (int)$modx->getOption('site_start', null, 1);
        $resource = $modx->getObject(modResource::class, $siteStart);
        if ($resource) {
            $modx->resource = $resource;
        }
        self::$modx = $modx;
    }

    protected function setUp(): void
    {
        parent::setUp();
        if (!self::$modx instanceof modX) {
            $this->markTestSkipped('Set MODX_TEST_BASE to a live MODX 3 root to run integration tests.');
        }
        $this->assertNotEmpty(
            self::$modx->getObject(modSnippet::class, ['name' => 'pdoResources']),
            'pdoTools snippets are not installed'
        );
    }

    /**
     * @param array<string, mixed> $properties
     * @return mixed
     */
    protected function runPdoSnippet(string $name, array $properties = [])
    {
        return self::$modx->runSnippet($name, $properties);
    }
}

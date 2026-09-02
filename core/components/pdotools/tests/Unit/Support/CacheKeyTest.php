<?php

declare(strict_types=1);

namespace ModxPro\PdoTools\Tests\Unit\Support;

use ModxPro\PdoTools\Support\CacheKey;
use PHPUnit\Framework\TestCase;

class CacheKeyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (!class_exists(CacheKey::class)) {
            $this->markTestSkipped('CacheKey is not on this branch yet (pdoTools3#24).');
        }
    }

    public function testAppendsContextOnce(): void
    {
        $hash = sha1('menu');
        $this->assertSame(
            'pdomenu/' . $hash . '/web',
            CacheKey::withContext('pdomenu/' . $hash, 'web')
        );
    }

    public function testIsIdempotentWhenContextAlreadyPresent(): void
    {
        $key = 'pdomenu/' . sha1('menu') . '/web';
        $this->assertSame($key, CacheKey::withContext($key, 'web'));
    }

    public function testEmptyContextLeavesKey(): void
    {
        $this->assertSame('pdomenu/abc', CacheKey::withContext('pdomenu/abc', ''));
    }
}

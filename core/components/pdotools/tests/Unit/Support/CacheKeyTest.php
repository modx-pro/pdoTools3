<?php

declare(strict_types=1);

namespace ModxPro\PdoTools\Tests\Unit\Support;

use ModxPro\PdoTools\Support\CacheKey;
use PHPUnit\Framework\TestCase;

class CacheKeyTest extends TestCase
{
    public function testAppendsContextOnce(): void
    {
        $hash = sha1('menu');
        $this->assertSame(
            'pdomenu/' . $hash . '/web',
            CacheKey::withContext('pdomenu/' . $hash, 'web')
        );
    }

    public function testSitemapStyleKey(): void
    {
        $hash = md5('sitemap');
        $this->assertSame(
            'sitemap/' . $hash . '/de',
            CacheKey::withContext('sitemap/' . $hash, 'de')
        );
    }

    public function testIsIdempotentWhenContextAlreadyPresent(): void
    {
        $key = 'pdomenu/' . sha1('menu') . '/web';
        $this->assertSame($key, CacheKey::withContext($key, 'web'));
    }

    public function testDifferentContextsStayDistinct(): void
    {
        $base = 'pdomenu/' . sha1('menu');
        $this->assertSame($base . '/web', CacheKey::withContext($base, 'web'));
        $this->assertSame($base . '/de', CacheKey::withContext($base, 'de'));
    }

    public function testEmptyContextLeavesKey(): void
    {
        $this->assertSame('pdomenu/abc', CacheKey::withContext('pdomenu/abc', ''));
        $this->assertSame('pdomenu/abc', CacheKey::withContext('pdomenu/abc', '   '));
    }

    public function testEmptyKeyStaysEmpty(): void
    {
        $this->assertSame('', CacheKey::withContext('', 'web'));
    }

    public function testStripsTrailingSlashBeforeSuffix(): void
    {
        $this->assertSame('pdomenu/abc/web', CacheKey::withContext('pdomenu/abc/', 'web'));
    }
}

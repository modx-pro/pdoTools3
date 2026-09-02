<?php

declare(strict_types=1);

namespace ModxPro\PdoTools\Tests\Unit\CoreTools;

use ModxPro\PdoTools\Tests\Support\CoreToolsHarness;
use ModxPro\PdoTools\Tests\TestCase;

class CacheKeyTest extends TestCase
{
    public function testExplicitCacheKeyGetsContext(): void
    {
        $tools = new CoreToolsHarness($this->modx);
        $this->assertSame('pdoMenu/custom/web', $tools->publicGetCacheKey(['cache_key' => 'pdoMenu/custom']));
        $this->assertSame('legacy/web', $tools->publicGetCacheKey(['cacheKey' => 'legacy']));
    }

    public function testDefaultKeyIncludesUserSha1AndContext(): void
    {
        $this->modx->user->id = 12;
        $tools = new CoreToolsHarness($this->modx, ['limit' => 10]);
        $key = $tools->publicGetCacheKey();

        $this->assertIsString($key);
        $this->assertMatchesRegularExpression('#^/[a-f0-9]{40}/web$#', $key);
    }

    public function testContextChangeProducesAnotherKey(): void
    {
        $tools = new CoreToolsHarness($this->modx);
        $options = ['cache_key' => 'pdomenu/' . sha1('menu')];

        $this->assertSame($options['cache_key'] . '/web', $tools->publicGetCacheKey($options));

        $this->modx->context->key = 'de';
        $this->assertSame($options['cache_key'] . '/de', $tools->publicGetCacheKey($options));
    }

    public function testEmptyContextLeavesExplicitKey(): void
    {
        $this->modx->context->key = '';
        $tools = new CoreToolsHarness($this->modx);
        $this->assertSame('pdoMenu/custom', $tools->publicGetCacheKey(['cache_key' => 'pdoMenu/custom']));
    }

    public function testReturnedKeyIsNotSuffixedTwice(): void
    {
        $tools = new CoreToolsHarness($this->modx);
        $stored = $tools->publicGetCacheKey(['cache_key' => 'pdomenu/menu']);
        $this->assertSame('pdomenu/menu/web', $stored);
        $this->assertSame($stored, $tools->publicGetCacheKey(['cache_key' => $stored]));
    }

    public function testSnippetCacheIsIsolatedByContext(): void
    {
        $this->attachCacheManager();
        $tools = new CoreToolsHarness($this->modx);
        $options = ['cache_key' => 'pdomenu/menu'];

        $this->assertSame('pdomenu/menu/web', $tools->setCache(['tree' => 'web'], $options));
        $this->assertSame(['tree' => 'web'], $tools->getCache($options));

        $this->modx->context->key = 'de';
        $this->assertEmpty($tools->getCache($options));
        $this->assertSame('pdomenu/menu/de', $tools->setCache(['tree' => 'de'], $options));
        $this->assertSame(['tree' => 'de'], $tools->getCache($options));

        $this->modx->context->key = 'web';
        $this->assertSame(['tree' => 'web'], $tools->getCache($options));
    }

    public function testExactCacheIgnoresContext(): void
    {
        $this->attachCacheManager();
        $tools = new CoreToolsHarness($this->modx);

        $this->assertSame('pdotools/chunk', $tools->setExactCache('pdotools/chunk', 'compiled'));
        $this->modx->context->key = 'de';
        $this->assertSame('compiled', $tools->getExactCache('pdotools/chunk'));
        $this->assertSame('error/chunk', $tools->setExactCache('error/chunk', 'dump'));
        $this->assertSame('dump', $tools->getExactCache('error/chunk'));
    }

    private function attachCacheManager(): void
    {
        $this->modx->cacheManager = $this->modx->getCacheManager();
    }
}

<?php

declare(strict_types=1);

namespace ModxPro\PdoTools\Tests\Unit\CoreTools;

use ModxPro\PdoTools\Tests\Support\CoreToolsHarness;
use ModxPro\PdoTools\Tests\TestCase;

class CacheKeyTest extends TestCase
{
    public function testExplicitCacheKeyWins(): void
    {
        $tools = new CoreToolsHarness($this->modx);
        $this->assertSame('pdoMenu/custom', $tools->publicGetCacheKey(['cache_key' => 'pdoMenu/custom']));
        $this->assertSame('legacy', $tools->publicGetCacheKey(['cacheKey' => 'legacy']));
    }

    public function testDefaultKeyIncludesUserAndSha1(): void
    {
        $this->modx->user->id = 12;
        $tools = new CoreToolsHarness($this->modx, ['limit' => 10]);
        $key = $tools->publicGetCacheKey();

        $this->assertIsString($key);
        $this->assertMatchesRegularExpression('#^/[a-f0-9]{40}$#', $key);
    }
}

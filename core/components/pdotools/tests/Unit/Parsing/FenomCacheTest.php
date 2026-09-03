<?php

declare(strict_types=1);

namespace ModxPro\PdoTools\Tests\Unit\Parsing;

use ModxPro\PdoTools\CoreTools;
use ModxPro\PdoTools\Parsing\Fenom\Fenom;
use ModxPro\PdoTools\Tests\TestCase;

class FenomCacheTest extends TestCase
{
    public function testUseFenomCacheLeavesNativeOptionsOn(): void
    {
        $cachePath = dirname(__DIR__, 3) . '/tmp/cache/pdotools-cache';
        $fileDir = $cachePath . '/file';
        if (!is_dir($fileDir) && !mkdir($fileDir, 0777, true) && !is_dir($fileDir)) {
            $this->fail('Cannot create Fenom compile dir');
        }

        $tools = new CoreTools($this->modx, [
            'useFenom' => true,
            'cachePath' => $cachePath,
        ]);
        // CoreTools overwrites useFenom* from system settings after construct.
        $tools->config(['useFenomCache' => true]);
        $fenom = new Fenom($this->modx, $tools);

        $flags = $fenom->getOptions();

        $this->assertSame(0, $flags & \Fenom::FORCE_COMPILE);
        $this->assertSame(0, $flags & \Fenom::DISABLE_CACHE);
        $this->assertNotSame(0, $flags & \Fenom::AUTO_RELOAD);

        $first = $fenom->process('{var $x = 1}{$x}');
        $second = $fenom->process('{var $x = 1}{$x}');
        $this->assertSame('1', $first);
        $this->assertSame('1', $second);
    }
}

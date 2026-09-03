<?php

declare(strict_types=1);

namespace ModxPro\PdoTools\Tests\Unit\Parsing;

use ModxPro\PdoTools\CoreTools;
use ModxPro\PdoTools\Parsing\Fenom\Fenom;
use ModxPro\PdoTools\Tests\TestCase;
use Fenom\Render;
use ReflectionClass;

class FenomCacheTest extends TestCase
{
    public function testUseFenomCacheWritesAndReloadsCompileFile(): void
    {
        // setCompileDir() always rebuilds paths under MODX_CORE_PATH.
        $cachePath = rtrim(MODX_CORE_PATH, '/') . '/cache/pdotools-filecache';
        $fileDir = $cachePath . '/file';
        if (is_dir($fileDir)) {
            foreach (glob($fileDir . '/*.php') ?: [] as $f) {
                @unlink($f);
            }
        } elseif (!mkdir($fileDir, 0777, true) && !is_dir($fileDir)) {
            $this->fail('Cannot create Fenom compile dir');
        }

        $tools = new CoreTools($this->modx, ['useFenom' => true]);
        $tools->config([
            'useFenomCache' => true,
            'cachePath' => $cachePath,
        ]);

        $fenom1 = new Fenom($this->modx, $tools);
        $this->assertSame(0, $fenom1->getOptions() & \Fenom::FORCE_COMPILE);
        $this->assertSame(0, $fenom1->getOptions() & \Fenom::DISABLE_CACHE);
        $this->assertNotSame(0, $fenom1->getOptions() & \Fenom::AUTO_RELOAD);

        $this->assertSame('42', $fenom1->process('{var $x = 42}{$x}'));

        $compileDir = $this->compileDir($fenom1);
        $files = glob(rtrim($compileDir, '/') . '/*.php') ?: [];
        $this->assertNotEmpty($files, 'useFenomCache must write a compile file');

        $fenom2 = new Fenom($this->modx, $tools);
        $name = md5('{var $x = 42}{$x}');
        $file = rtrim($this->compileDir($fenom2), '/') . '/' . $fenom2->getCompileName($name);
        $this->assertFileExists($file);

        $fenom = $fenom2;
        $loaded = include $file;
        $this->assertInstanceOf(Render::class, $loaded);
        $this->assertSame('42', $loaded->fetch([]));
    }

    private function compileDir(Fenom $fenom): string
    {
        $ref = new ReflectionClass(\Fenom::class);
        $prop = $ref->getProperty('_compile_dir');
        $prop->setAccessible(true);

        return (string)$prop->getValue($fenom);
    }
}

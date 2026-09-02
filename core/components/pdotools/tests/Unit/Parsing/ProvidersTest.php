<?php

declare(strict_types=1);

namespace ModxPro\PdoTools\Tests\Unit\Parsing;

use ModxPro\PdoTools\Parsing\Fenom\Providers\Chunk;
use ModxPro\PdoTools\Parsing\Fenom\Providers\File;
use ModxPro\PdoTools\Parsing\Fenom\Providers\Template;
use ModxPro\PdoTools\Tests\TestCase;

class ProvidersTest extends TestCase
{
    public function testFileProviderUsesElementsPath(): void
    {
        $dir = dirname(__DIR__, 3) . '/tmp/elements';
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            $this->fail('Cannot create elements dir');
        }
        $this->pdoTools->config(['elementsPath' => $dir]);
        $provider = new File($this->modx, $this->pdoTools);

        $this->assertFalse($provider->templateExists('missing.tpl'));
    }

    public function testChunkProviderReportsMissingChunk(): void
    {
        $provider = new Chunk($this->modx, $this->pdoTools);
        $this->assertFalse($provider->templateExists('NoSuchChunk'));
    }

    public function testTemplateProviderReportsMissingTemplate(): void
    {
        $provider = new Template($this->modx, $this->pdoTools);
        $this->assertFalse($provider->templateExists('NoSuchTemplate'));
    }
}

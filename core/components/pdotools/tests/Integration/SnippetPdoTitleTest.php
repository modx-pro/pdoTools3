<?php

declare(strict_types=1);

namespace ModxPro\PdoTools\Tests\Integration;

class SnippetPdoTitleTest extends ModxTestCase
{
    public function testBuildsTitle(): void
    {
        $out = $this->runPdoSnippet('pdoTitle', [
            'id' => self::$modx->resource->id,
        ]);

        $this->assertIsString($out);
        $this->assertNotSame('', trim((string)$out));
    }
}

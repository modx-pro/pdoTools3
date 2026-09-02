<?php

declare(strict_types=1);

namespace ModxPro\PdoTools\Tests\Integration;

class SnippetPdoFieldTest extends ModxTestCase
{
    public function testReadsPagetitle(): void
    {
        $out = $this->runPdoSnippet('pdoField', [
            'id' => self::$modx->resource->id,
            'field' => 'pagetitle',
        ]);

        $this->assertIsString($out);
        $this->assertSame(self::$modx->resource->get('pagetitle'), $out);
    }
}

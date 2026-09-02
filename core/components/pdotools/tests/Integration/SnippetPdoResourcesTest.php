<?php

declare(strict_types=1);

namespace ModxPro\PdoTools\Tests\Integration;

class SnippetPdoResourcesTest extends ModxTestCase
{
    public function testListsSiteStart(): void
    {
        $out = $this->runPdoSnippet('pdoResources', [
            'parents' => 0,
            'limit' => 5,
            'return' => 'ids',
            'showHidden' => 1,
            'showUnpublished' => 1,
        ]);

        $this->assertIsString($out);
        $this->assertNotSame('', trim((string)$out));
    }
}

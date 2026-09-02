<?php

declare(strict_types=1);

namespace ModxPro\PdoTools\Tests\Integration;

class SnippetPdoPageTest extends ModxTestCase
{
    public function testPaginatesResourcesWithoutAjax(): void
    {
        $out = $this->runPdoSnippet('pdoPage', [
            'element' => 'pdoResources',
            'parents' => 0,
            'limit' => 1,
            'page' => 1,
            'return' => 'ids',
            'showHidden' => 1,
            'showUnpublished' => 1,
        ]);

        $this->assertIsString($out);
    }
}

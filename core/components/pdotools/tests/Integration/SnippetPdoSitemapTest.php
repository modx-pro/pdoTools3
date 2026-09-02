<?php

declare(strict_types=1);

namespace ModxPro\PdoTools\Tests\Integration;

class SnippetPdoSitemapTest extends ModxTestCase
{
    public function testBuildsXml(): void
    {
        $out = $this->runPdoSnippet('pdoSitemap', [
            'parents' => 0,
            'limit' => 5,
            'showHidden' => 1,
        ]);

        $this->assertIsString($out);
        $this->assertStringContainsString('urlset', (string)$out);
    }
}

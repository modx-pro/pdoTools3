<?php

declare(strict_types=1);

namespace ModxPro\PdoTools\Tests\Integration;

class SnippetPdoCrumbsTest extends ModxTestCase
{
    public function testShowHomeWithStringParentsDoesNotFatal(): void
    {
        $out = $this->runPdoSnippet('pdoCrumbs', [
            'showHome' => 1,
            'parents' => '0',
            'tpl' => '@INLINE [[+pagetitle]]',
            'tplCurrent' => '@INLINE [[+pagetitle]]',
            'tplHome' => '@INLINE [[+pagetitle]]',
            'outputSeparator' => ' / ',
        ]);

        $this->assertIsString($out);
    }
}

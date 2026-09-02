<?php

declare(strict_types=1);

namespace ModxPro\PdoTools\Tests\Integration;

class SnippetPdoArchiveTest extends ModxTestCase
{
    public function testRunsWithoutFatal(): void
    {
        $out = $this->runPdoSnippet('pdoArchive', [
            'parents' => 0,
            'limit' => 5,
            'showHidden' => 1,
        ]);

        $this->assertTrue(is_string($out) || $out === null);
    }
}

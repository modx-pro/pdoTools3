<?php

declare(strict_types=1);

namespace ModxPro\PdoTools\Tests\Integration;

class SnippetPdoNeighborsTest extends ModxTestCase
{
    public function testRunsForCurrentResource(): void
    {
        $out = $this->runPdoSnippet('pdoNeighbors', [
            'id' => self::$modx->resource->id,
            'tplWrapper' => '@INLINE [[+prev]][[+next]]',
        ]);

        $this->assertTrue(is_string($out) || $out === null);
    }
}

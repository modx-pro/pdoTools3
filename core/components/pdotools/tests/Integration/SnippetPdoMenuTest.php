<?php

declare(strict_types=1);

namespace ModxPro\PdoTools\Tests\Integration;

class SnippetPdoMenuTest extends ModxTestCase
{
    public function testCachedMenuInWebContext(): void
    {
        $props = [
            'parents' => 0,
            'limit' => 5,
            'cache' => 1,
            'showHidden' => 1,
        ];
        $first = $this->runPdoSnippet('pdoMenu', $props);
        $second = $this->runPdoSnippet('pdoMenu', $props);

        $this->assertIsString($first);
        $this->assertSame($first, $second);
    }
}

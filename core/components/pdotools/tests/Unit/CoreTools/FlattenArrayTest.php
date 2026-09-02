<?php

declare(strict_types=1);

namespace ModxPro\PdoTools\Tests\Unit\CoreTools;

use ModxPro\PdoTools\Tests\Support\CoreToolsHarness;
use ModxPro\PdoTools\Tests\TestCase;

class FlattenArrayTest extends TestCase
{
    public function testFlattensNestedArrays(): void
    {
        $tools = new CoreToolsHarness($this->modx);
        $flat = $tools->publicFlattenArray([
            'a' => 1,
            'b' => ['c' => 2, 'd' => ['e' => 3]],
        ]);

        $this->assertSame([
            'a' => 1,
            'b.c' => 2,
            'b.d.e' => 3,
        ], $flat);
    }
}

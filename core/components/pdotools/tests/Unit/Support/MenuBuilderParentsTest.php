<?php

declare(strict_types=1);

namespace ModxPro\PdoTools\Tests\Unit\Support;

use PHPUnit\Framework\TestCase;

/**
 * Mirrors snippet.pdomenu.php: parents stay a comma list, then explode.
 */
class MenuBuilderParentsTest extends TestCase
{
    /**
     * @return array{0:list<string>,1:list<int>,2:list<int>}
     */
    private function splitParents(string $parents): array
    {
        $items = array_map('trim', explode(',', $parents));
        $parentsIn = $parentsOut = [];
        foreach ($items as $v) {
            if (!is_numeric($v)) {
                continue;
            }
            if (isset($v[0]) && $v[0] === '-') {
                $parentsOut[] = (int)abs((int)$v);
            } else {
                $parentsIn[] = (int)abs((int)$v);
            }
        }

        return [$items, $parentsIn, $parentsOut];
    }

    public function testStringParentsSplitAndTrim(): void
    {
        [$items, $in, $out] = $this->splitParents('1, 2, -3');

        $this->assertSame(['1', '2', '-3'], $items);
        $this->assertSame([1, 2], $in);
        $this->assertSame([3], $out);
    }

    public function testZeroParentIsNumeric(): void
    {
        [, $in, $out] = $this->splitParents('0');
        $this->assertSame([0], $in);
        $this->assertSame([], $out);
    }
}

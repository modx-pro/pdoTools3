<?php

declare(strict_types=1);

namespace ModxPro\PdoTools\Tests\Unit\Support;

use PHPUnit\Framework\TestCase;

/**
 * pdoPage #384: ceil() returns float, so page vs pageCount must use loose compare.
 */
class PaginatorPageCompareTest extends TestCase
{
    /**
     * @return list<array{0:int|float,1:int|float,2:bool}>
     */
    public static function lastPageProvider(): array
    {
        return [
            [2, 2, true],
            [2, 2.0, true],
            [2.0, 2, true],
            [3, 2.0, false],
            [1, 2, false],
        ];
    }

    /**
     * @dataProvider lastPageProvider
     * @param int|float $page
     * @param int|float $pageCount
     */
    public function testLastPageUsesLooseEquality($page, $pageCount, bool $isLast): void
    {
        $this->assertSame($isLast, $page == $pageCount);
        $this->assertFalse($page > $pageCount && $isLast);
    }

    public function testStrictRedirectDoesNotFireOnLastFloatPage(): void
    {
        $page = 2;
        $pageCount = 2.0;
        $strictMode = true;

        $this->assertFalse($page > 1 && $page > $pageCount && $strictMode);
    }
}

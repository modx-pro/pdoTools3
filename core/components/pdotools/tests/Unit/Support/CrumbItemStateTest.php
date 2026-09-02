<?php

declare(strict_types=1);

namespace ModxPro\PdoTools\Tests\Unit\Support;

use ModxPro\PdoTools\Support\CrumbItemState;
use PHPUnit\Framework\TestCase;

class CrumbItemStateTest extends TestCase
{
    public function testHomeCurrentAndEdges(): void
    {
        $home = CrumbItemState::placeholders(1, 9, 1, 0, 3);
        $this->assertSame(1, $home['isFirst']);
        $this->assertSame(1, $home['isHome']);
        $this->assertSame(0, $home['isActive']);

        $current = CrumbItemState::placeholders(9, 9, 1, 2, 3);
        $this->assertSame(1, $current['isLast']);
        $this->assertSame(1, $current['isActive']);
        $this->assertSame(0, $current['isHome']);
    }
}

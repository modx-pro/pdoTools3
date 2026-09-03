<?php

declare(strict_types=1);

namespace ModxPro\PdoTools\Tests\Unit\Support;

use ModxPro\PdoTools\Support\DateFormat;
use PHPUnit\Framework\TestCase;

class DateFormatTest extends TestCase
{
    public function testPassthroughWhenNoPercent(): void
    {
        $this->assertSame('Y-m-d', DateFormat::toDate('Y-m-d'));
    }

    public function testConvertsCommonStrftimeTokens(): void
    {
        $this->assertSame('Y-m-d H:i', DateFormat::toDate('%Y-%m-%d %H:%M'));
        $this->assertSame('F j', DateFormat::toDate('%B %e'));
    }
}

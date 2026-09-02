<?php

declare(strict_types=1);

namespace ModxPro\PdoTools\Tests\Unit\Support;

use ModxPro\PdoTools\Support\TemplateFlags;
use PHPUnit\Framework\TestCase;

class TemplateFlagsTest extends TestCase
{
    public function testBoolsBecomeOnesAndZeros(): void
    {
        $this->assertSame(
            ['isActive' => 1, 'isFirst' => 0],
            TemplateFlags::toPlaceholders(['isActive' => true, 'isFirst' => false])
        );
    }
}

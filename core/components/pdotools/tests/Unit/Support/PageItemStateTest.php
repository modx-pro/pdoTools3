<?php

declare(strict_types=1);

namespace ModxPro\PdoTools\Tests\Unit\Support;

use ModxPro\PdoTools\Support\PageItemState;
use PHPUnit\Framework\TestCase;

class PageItemStateTest extends TestCase
{
    public function testActiveMiddlePage(): void
    {
        $flags = PageItemState::placeholders(3, 3, 10);
        $this->assertSame(1, $flags['isActive']);
        $this->assertSame(0, $flags['isFirst']);
        $this->assertSame(0, $flags['isLast']);
        $this->assertSame(0, $flags['isSkip']);
    }

    public function testFirstAndLast(): void
    {
        $this->assertSame(1, PageItemState::placeholders(1, 5, 10)['isFirst']);
        $this->assertSame(1, PageItemState::placeholders(10, 5, 10)['isLast']);
    }

    public function testSkipClearsActive(): void
    {
        $flags = PageItemState::placeholders(5, 5, 10, true);
        $this->assertSame(1, $flags['isSkip']);
        $this->assertSame(0, $flags['isActive']);
    }

    public function testTplSelection(): void
    {
        $config = [
            'tplPage' => 'page',
            'tplPageActive' => 'active',
            'tplPageSkip' => 'skip',
        ];
        $this->assertSame('active', PageItemState::tpl($config, 2, 2));
        $this->assertSame('page', PageItemState::tpl($config, 3, 2));
        $this->assertSame('skip', PageItemState::tpl($config, 4, 2, true));
        $this->assertSame('', PageItemState::tpl(['tplPage' => 'page'], 4, 2, true));
    }
}

<?php

declare(strict_types=1);

namespace ModxPro\PdoTools\Tests\Unit\Support;

use ModxPro\PdoTools\Tests\Support\CapturingTools;
use ModxPro\PdoTools\Tests\Support\PaginatorHarness;
use ModxPro\PdoTools\Tests\TestCase;

class PaginatorFlagsTest extends TestCase
{
    /** @var CapturingTools */
    private $tools;

    /** @var PaginatorHarness */
    private $paginator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tools = new CapturingTools($this->modx, [
            'pageVarKey' => 'page',
            'tplPage' => '@INLINE page',
            'tplPageActive' => '@INLINE active',
            'tplPageSkip' => '@INLINE skip',
        ]);
        $this->paginator = new PaginatorHarness($this->modx, $this->tools);
    }

    public function testMakePageLinkWithoutContextReturnsHrefOnly(): void
    {
        $href = $this->paginator->makePageLink('/list', 3);
        $this->assertSame('/list?page=3', $href);
    }

    public function testMakePageLinkMergesFlagsIntoChunk(): void
    {
        $this->paginator->makePageLink('/list', 3, '@INLINE page', 3, 10);

        $this->assertSame('@INLINE page', $this->tools->lastChunkName);
        $pls = $this->tools->lastChunkProperties;
        $this->assertSame(3, $pls['page']);
        $this->assertSame('/list?page=3', $pls['href']);
        $this->assertSame(1, $pls['isActive']);
        $this->assertSame(0, $pls['isFirst']);
        $this->assertSame(0, $pls['isLast']);
        $this->assertSame(0, $pls['isSkip']);
    }

    public function testMakePageLinkSkipFlag(): void
    {
        $this->paginator->makePageLink('/list', 5, '@INLINE skip', 3, 10, true);
        $this->assertSame(1, $this->tools->lastChunkProperties['isSkip']);
        $this->assertSame(0, $this->tools->lastChunkProperties['isActive']);
    }

    public function testRenderPageItemPicksActiveTpl(): void
    {
        $out = $this->paginator->publicRenderPageItem('/list', 2, 2, 5);
        $this->assertSame('@INLINE active', $out);
        $this->assertSame(1, $this->tools->lastChunkProperties['isActive']);
    }

    public function testRenderPageItemSkipWithoutTplReturnsEmpty(): void
    {
        $this->tools->setConfig([
            'pageVarKey' => 'page',
            'tplPage' => '@INLINE page',
        ]);
        $this->assertSame('', $this->paginator->publicRenderPageItem('/list', 4, 2, 10, true));
    }

    public function testRenderPageItemSkipUsesSkipTpl(): void
    {
        $out = $this->paginator->publicRenderPageItem('/list', 4, 2, 10, true);
        $this->assertSame('@INLINE skip', $out);
        $this->assertSame(1, $this->tools->lastChunkProperties['isSkip']);
    }

    public function testRenderPageSkipWithoutTplReturnsEmpty(): void
    {
        $this->tools->setConfig(['pageVarKey' => 'page']);
        $this->assertSame('', $this->paginator->publicRenderPageSkip(10));
    }

    public function testRenderPageSkipPassesFlags(): void
    {
        $out = $this->paginator->publicRenderPageSkip(8);
        $this->assertSame('@INLINE skip', $out);
        $this->assertSame(1, $this->tools->lastChunkProperties['isSkip']);
        $this->assertSame(0, $this->tools->lastChunkProperties['isActive']);
    }
}

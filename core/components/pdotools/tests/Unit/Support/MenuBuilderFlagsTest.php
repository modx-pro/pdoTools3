<?php

declare(strict_types=1);

namespace ModxPro\PdoTools\Tests\Unit\Support;

use ModxPro\PdoTools\Tests\Support\CapturingTools;
use ModxPro\PdoTools\Tests\Support\MenuBuilderHarness;
use ModxPro\PdoTools\Tests\TestCase;

class MenuBuilderFlagsTest extends TestCase
{
    public function testTemplateBranchMergesFlagsAndClasses(): void
    {
        $tools = new CapturingTools($this->modx, [
            'hereId' => 5,
            'firstClass' => 'first',
            'selfClass' => 'self',
            'hereClass' => 'active',
            'tplHere' => '@INLINE here',
            'tpl' => '@INLINE row',
        ]);
        $menu = new MenuBuilderHarness($this->modx, $tools, [5 => 0]);

        $out = $menu->templateBranch([
            'id' => 5,
            'idx' => 1,
            'last' => false,
            'pagetitle' => 'Home',
            'children' => [],
        ]);

        $this->assertSame('@INLINE here', $out);
        $this->assertSame('@INLINE here', $tools->lastChunkName);
        $pls = $tools->lastChunkProperties;
        $this->assertSame(1, $pls['isActive']);
        $this->assertSame(1, $pls['isFirst']);
        $this->assertSame(1, $pls['isHere']);
        $this->assertSame(0, $pls['hasChildren']);
        $this->assertSame(0, $pls['hasChilds']);
        $this->assertSame('Home', $pls['menutitle']);
        $this->assertSame('/id/5', $pls['link']);
        $this->assertStringContainsString('first', $pls['classNames']);
        $this->assertStringContainsString('self', $pls['classNames']);
        $this->assertStringContainsString('active', $pls['classNames']);
    }

    public function testGetTplPrefersSpecializedChunk(): void
    {
        $tools = new CapturingTools($this->modx, [
            'hereId' => 9,
            'tplHere' => '@INLINE here',
            'tpl' => '@INLINE row',
        ]);
        $menu = new MenuBuilderHarness($this->modx, $tools, [9 => 0]);

        $this->assertSame(
            '@INLINE here',
            $menu->getTpl(['id' => 9, 'idx' => 2, 'level' => 1, 'children' => 0])
        );
    }

    public function testGetClassesUsesFrozenConfig(): void
    {
        $tools = new CapturingTools($this->modx, [
            'hereId' => 1,
            'firstClass' => 'first',
            'lastClass' => 'last',
        ]);
        $menu = new MenuBuilderHarness($this->modx, $tools);

        $classes = $menu->getClasses([
            'id' => 2,
            'idx' => 1,
            'last' => false,
            'level' => 1,
            'children' => 0,
        ]);
        $this->assertStringContainsString('first', $classes);
    }
}

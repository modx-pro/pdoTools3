<?php

declare(strict_types=1);

namespace ModxPro\PdoTools\Tests\Unit\CoreTools;

use ModxPro\PdoTools\Tests\TestCase;

class BuildTreeTest extends TestCase
{
    public function testSingleRowWrapsUnderParent(): void
    {
        $tree = $this->pdoTools->buildTree([
            ['id' => 2, 'parent' => 1, 'pagetitle' => 'Child'],
        ]);

        $this->assertArrayHasKey(1, $tree);
        $this->assertSame('Child', $tree[1]['children'][2]['pagetitle']);
    }

    public function testBuildsNestedChildren(): void
    {
        $tree = $this->pdoTools->buildTree([
            ['id' => 1, 'parent' => 0, 'pagetitle' => 'Root'],
            ['id' => 2, 'parent' => 1, 'pagetitle' => 'Child'],
            ['id' => 3, 'parent' => 2, 'pagetitle' => 'Leaf'],
        ], 'id', 'parent', [1]);

        $this->assertArrayHasKey(1, $tree);
        $this->assertSame('Child', $tree[1]['children'][2]['pagetitle']);
        $this->assertSame('Leaf', $tree[1]['children'][2]['children'][3]['pagetitle']);
    }
}

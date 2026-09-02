<?php

declare(strict_types=1);

namespace ModxPro\PdoTools\Tests\Unit\CoreTools;

use ModxPro\PdoTools\Tests\TestCase;

class MakePlaceholdersTest extends TestCase
{
    public function testFlatKeys(): void
    {
        $result = $this->pdoTools->makePlaceholders(['pagetitle' => 'Home'], '', '[[+', ']]', false);

        $this->assertSame(['pagetitle' => '[[+pagetitle]]'], $result['pl']);
        $this->assertSame(['pagetitle' => 'Home'], $result['vl']);
    }

    public function testNestedKeysAndUncacheableAliases(): void
    {
        $result = $this->pdoTools->makePlaceholders([
            'user' => ['id' => 5, 'name' => 'Ada'],
        ]);

        $this->assertSame('[[+user.id]]', $result['pl']['user.id']);
        $this->assertSame(5, $result['vl']['user.id']);
        $this->assertSame('[[!+user.name]]', $result['pl']['!user.name']);
        $this->assertSame('Ada', $result['vl']['!user.name']);
    }
}

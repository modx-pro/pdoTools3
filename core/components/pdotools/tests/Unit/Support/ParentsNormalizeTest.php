<?php

declare(strict_types=1);

namespace ModxPro\PdoTools\Tests\Unit\Support;

use PHPUnit\Framework\TestCase;

/**
 * Mirrors snippet.pdocrumbs.php: string &parents must become a list
 * before showHome appends the site start (pdoTools3#16).
 */
class ParentsNormalizeTest extends TestCase
{
    /**
     * @param mixed $parents
     * @return list<string|int>
     */
    private function normalizeParents($parents): array
    {
        if (!empty($parents) && !is_array($parents)) {
            $parents = array_map('trim', explode(',', (string)$parents));
        }
        if (empty($parents)) {
            $parents = [];
        }

        return $parents;
    }

    public function testStringParentsBecomeList(): void
    {
        $parents = $this->normalizeParents('1, 2, 3');
        $parents[] = 0;

        $this->assertSame(['1', '2', '3', 0], $parents);
    }

    public function testArrayParentsStayArray(): void
    {
        $parents = $this->normalizeParents([4, 5]);
        $this->assertSame([4, 5], $parents);
    }

    public function testEmptyStringIsEmptyList(): void
    {
        $this->assertSame([], $this->normalizeParents(''));
    }
}

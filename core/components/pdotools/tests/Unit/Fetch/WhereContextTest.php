<?php

declare(strict_types=1);

namespace ModxPro\PdoTools\Tests\Unit\Fetch;

use PHPUnit\Framework\TestCase;

/**
 * Mirrors Fetch::addWhere() cases context / resources / parents explode.
 */
class WhereContextTest extends TestCase
{
    /**
     * @param mixed $value
     * @return array<string, mixed>
     */
    private function contextWhere($value, string $alias = 'modResource'): array
    {
        $where = [];
        if (!empty($value)) {
            $context = array_map('trim', explode(',', (string)$value));
            if (count($context) === 1) {
                $where[$alias . '.context_key'] = $context[0];
            } else {
                $where[$alias . '.context_key:IN'] = $context;
            }
        }

        return $where;
    }

    /**
     * @return array<string, mixed>
     */
    private function resourcesWhere(string $value, string $alias = 'modResource'): array
    {
        $where = [];
        $resources = array_map('trim', explode(',', $value));
        $resourcesIn = $resourcesOut = [];
        foreach ($resources as $v) {
            if (!is_numeric($v)) {
                continue;
            }
            if ($v < 0) {
                $resourcesOut[] = abs((int)$v);
            } else {
                $resourcesIn[] = abs((int)$v);
            }
        }
        if ($resourcesIn) {
            $where[$alias . '.id:IN'] = $resourcesIn;
        }
        if ($resourcesOut) {
            $where[$alias . '.id:NOT IN'] = $resourcesOut;
        }

        return $where;
    }

    public function testSingleContextIsEquality(): void
    {
        $this->assertSame(
            ['modResource.context_key' => 'web'],
            $this->contextWhere('web')
        );
    }

    public function testManyContextsUseIn(): void
    {
        $this->assertSame(
            ['modResource.context_key:IN' => ['web', 'shop']],
            $this->contextWhere('web, shop')
        );
    }

    public function testResourcesSplitIncludeAndExclude(): void
    {
        $this->assertSame(
            [
                'modResource.id:IN' => [1, 2],
                'modResource.id:NOT IN' => [9],
            ],
            $this->resourcesWhere('1, 2, -9, skip')
        );
    }
}

<?php

declare(strict_types=1);

namespace ModxPro\PdoTools\Tests\Unit\Fetch;

use PHPUnit\Framework\TestCase;

/**
 * Mirrors Fetch extra-category gate (pdoTools3#9).
 */
class DisableMs3Test extends TestCase
{
    /**
     * @param array<int, string> $resourceMap
     */
    private function shouldJoinMs3(array $config, array $resourceMap): bool
    {
        $ms3Category = 'MiniShop3\\Model\\' . 'msCategory';

        return empty($config['disableMS3']) && in_array($ms3Category, $resourceMap, true);
    }

    public function testNoJoinWithoutClassMap(): void
    {
        $this->assertFalse($this->shouldJoinMs3([], []));
    }

    public function testJoinWhenMs3IsRegistered(): void
    {
        $this->assertTrue($this->shouldJoinMs3([], [
            'MiniShop3\\Model\\msCategory',
        ]));
    }

    public function testDisableMs3SkipsJoin(): void
    {
        $this->assertFalse($this->shouldJoinMs3(['disableMS3' => 1], [
            'MiniShop3\\Model\\msCategory',
        ]));
    }
}

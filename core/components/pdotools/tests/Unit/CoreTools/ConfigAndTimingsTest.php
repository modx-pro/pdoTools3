<?php

declare(strict_types=1);

namespace ModxPro\PdoTools\Tests\Unit\CoreTools;

use ModxPro\PdoTools\Tests\TestCase;

class ConfigAndTimingsTest extends TestCase
{
    public function testConfigGetSetAndRead(): void
    {
        $this->pdoTools->config(['limit' => 25]);
        $this->assertSame(25, $this->pdoTools->config('limit'));
        $this->assertIsArray($this->pdoTools->config());
    }

    public function testAddTimeGrowsTheLog(): void
    {
        $this->pdoTools->addTime('unit step');
        $log = $this->pdoTools->getTime(false);
        $this->assertIsArray($log);
        $this->assertNotEmpty($log);
        $this->assertStringContainsString('unit step', $this->pdoTools->getTime());
    }

    public function testPrepareRowsDecodesJson(): void
    {
        $this->pdoTools->config(['decodeJSON' => true, 'includeTVs' => '']);
        $rows = $this->pdoTools->prepareRows([
            ['id' => 1, 'props' => '{"a":1}'],
        ]);

        $this->assertSame(['a' => 1], $rows[0]['props']);
    }

}

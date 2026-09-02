<?php

declare(strict_types=1);

namespace ModxPro\PdoTools\Tests\Support;

use ModxPro\PdoTools\CoreTools;
use ModxPro\PdoTools\Support\MenuBuilder;
use MODX\Revolution\modX;

class MenuBuilderHarness extends MenuBuilder
{
    /**
     * @param array<int, mixed> $parentTree
     */
    public function __construct(modX $modx, CoreTools $pdoTools, array $parentTree = [])
    {
        $this->modx = $modx;
        $this->pdoTools = $pdoTools;
        $this->parentTree = $parentTree;
        $this->level = 1;
    }
}

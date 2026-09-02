<?php

declare(strict_types=1);

namespace ModxPro\PdoTools\Tests\Support;

use ModxPro\PdoTools\CoreTools;
use ModxPro\PdoTools\Support\Paginator;
use MODX\Revolution\modX;

class PaginatorHarness extends Paginator
{
    public function __construct(modX $modx, CoreTools $pdoTools)
    {
        $this->modx = $modx;
        $this->pdoTools = $pdoTools;
    }

    /**
     * @param string $url
     * @param int $page
     * @param int $current
     * @param int $pages
     * @param bool $skip
     */
    public function publicRenderPageItem($url, $page, $current, $pages, $skip = false): string
    {
        return $this->renderPageItem($url, $page, $current, $pages, $skip);
    }

    /**
     * @param int $pages
     */
    public function publicRenderPageSkip($pages = 0): string
    {
        return $this->renderPageSkip($pages);
    }
}

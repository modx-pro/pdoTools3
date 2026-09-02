<?php

declare(strict_types=1);

namespace ModxPro\PdoTools\Tests\Support;

use ModxPro\PdoTools\CoreTools;

class CoreToolsHarness extends CoreTools
{
    /**
     * @param array<string, mixed> $array
     * @return array<string, mixed>
     */
    public function publicFlattenArray(array $array, string $plPrefix = ''): array
    {
        return $this->flattenArray($array, $plPrefix);
    }

    /**
     * @param array<string, mixed>|mixed $options
     * @return bool|string
     */
    public function publicGetCacheKey($options = [])
    {
        return $this->getCacheKey($options);
    }
}

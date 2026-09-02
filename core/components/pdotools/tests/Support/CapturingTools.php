<?php

declare(strict_types=1);

namespace ModxPro\PdoTools\Tests\Support;

use ModxPro\PdoTools\CoreTools;

/**
 * Captures getChunk calls so Support classes can be unit-tested without MODX chunks.
 */
class CapturingTools extends CoreTools
{
    /** @var string */
    public $lastChunkName = '';

    /** @var array<string, mixed> */
    public $lastChunkProperties = [];

    /**
     * @param array<string, mixed> $properties
     */
    public function getChunk($name = '', array $properties = [], $fastMode = false)
    {
        $properties = $this->prepareRow($properties);
        $this->lastChunkName = (string)$name;
        $this->lastChunkProperties = $properties;

        return (string)$name;
    }

    public function makeUrl($id, $options = [], $args = [])
    {
        return '/id/' . (int)$id;
    }

    public function defineChunk(array $properties = [])
    {
        return !empty($this->config['tpl']) ? (string)$this->config['tpl'] : '@INLINE default';
    }
}

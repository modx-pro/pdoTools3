<?php

namespace ModxPro\PdoTools\Parsing\Fenom\Providers;

use MODX\Revolution\modChunk;

class Chunk extends ElementProvider
{
    protected function elementClass(): string
    {
        return modChunk::class;
    }

    protected function nameField(): string
    {
        return 'name';
    }

    protected function listColumn(): string
    {
        return 'name';
    }
}

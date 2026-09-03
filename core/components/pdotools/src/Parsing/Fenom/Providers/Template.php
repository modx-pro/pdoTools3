<?php

namespace ModxPro\PdoTools\Parsing\Fenom\Providers;

use MODX\Revolution\modTemplate;

class Template extends ElementProvider
{
    protected function elementClass(): string
    {
        return modTemplate::class;
    }

    protected function nameField(): string
    {
        return 'templatename';
    }

    protected function listColumn(): string
    {
        return 'templatename';
    }
}

<?php

declare(strict_types=1);

namespace ModxPro\PdoTools\Tests\Support;

use Exception;
use ModxPro\PdoTools\Parsing\Fenom\Fenom;

class FenomHarness extends Fenom
{
    /**
     * @param array<string, mixed>|string $chunk
     */
    public function publicResolveSourceLabel($chunk, string $name, string $content): string
    {
        return $this->resolveSourceLabel($chunk, $name, $content);
    }

    public function publicFormatFenomError(
        Exception $e,
        string $name,
        string $content,
        string $label,
        string $phase
    ): string {
        return $this->formatFenomError($e, $name, $content, $label, $phase);
    }
}

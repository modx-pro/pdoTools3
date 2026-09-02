<?php

declare(strict_types=1);

namespace ModxPro\PdoTools\Tests\Unit\Parsing;

use PHPUnit\Framework\TestCase;

/**
 * Mirrors Parser::processElementTags() {ignore} replacement.
 */
class ParserIgnoreTest extends TestCase
{
    /**
     * @return array{0:string,1:array<string, string>}
     */
    private function stashIgnores(string $content): array
    {
        $ignores = [];
        if (preg_match_all('#{ignore}(.*?){/ignore}#is', $content, $matches)) {
            foreach ($matches[1] as $ignore) {
                $key = 'ignore_' . md5($ignore);
                $ignores[$key] = $ignore;
                $content = str_replace($ignore, $key, $content);
            }
        }

        return [$content, $ignores];
    }

    public function testIgnoreBodyIsStashedAndRestorable(): void
    {
        $source = 'before {ignore}[[+foo]]{/ignore} after';
        [$masked, $ignores] = $this->stashIgnores($source);

        $this->assertCount(1, $ignores);
        $this->assertStringNotContainsString('[[+foo]]', $masked);

        $restored = $masked;
        foreach ($ignores as $key => $val) {
            $restored = str_replace($key, $val, $restored);
        }

        $this->assertSame($source, $restored);
    }
}

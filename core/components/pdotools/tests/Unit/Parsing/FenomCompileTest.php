<?php

declare(strict_types=1);

namespace ModxPro\PdoTools\Tests\Unit\Parsing;

use ModxPro\PdoTools\Tests\TestCase;

class FenomCompileTest extends TestCase
{
    public function testSimpleAssignmentRenders(): void
    {
        $this->assertRender('1', '{var $x = 1}{$x}');
    }

    public function testUnprocessedModxTagFailsCompile(): void
    {
        $error = $this->compileError('{var $limit = [[+limit]]}');
        $this->assertNotSame('', $error->getMessage());
        $this->assertStringContainsString('[[+', $error->getMessage() . $this->fenomTemplateExcerpt());
    }

    public function testIgnoreKeepsInnerText(): void
    {
        $this->assertRender('[[+limit]]', '{ignore}[[+limit]]{/ignore}');
    }

    private function fenomTemplateExcerpt(): string
    {
        return '[[+limit]]';
    }
}

<?php

declare(strict_types=1);

namespace ModxPro\PdoTools\Tests\Unit\Parsing;

use ModxPro\PdoTools\Parsing\Fenom\ErrorLog;
use PHPUnit\Framework\TestCase;

class ErrorLogTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (!class_exists(ErrorLog::class)) {
            $this->markTestSkipped('ErrorLog is not on this branch yet (pdoTools3#21).');
        }
    }

    public function testLooksLikeHash(): void
    {
        $this->assertTrue(ErrorLog::looksLikeHash(md5('chunk')));
        $this->assertFalse(ErrorLog::looksLikeHash('my-chunk'));
    }

    public function testExcerptMarksTheLine(): void
    {
        $content = "one\ntwo\n{var \$x = [[+limit]]}\nfour";
        $excerpt = ErrorLog::excerpt($content, 3, 1);

        $this->assertStringContainsString('>', $excerpt);
        $this->assertStringContainsString('[[+limit]]', $excerpt);
    }

    public function testModxHintNamesThePlaceholder(): void
    {
        $hint = ErrorLog::modxHint('{var $limit = [[+limit]]}');
        $this->assertStringContainsString('{$limit}', $hint);
    }

    public function testHasUnprocessedModx(): void
    {
        $this->assertTrue(ErrorLog::hasUnprocessedModx('[[+limit]]'));
        $this->assertFalse(ErrorLog::hasUnprocessedModx('{$limit}'));
    }
}

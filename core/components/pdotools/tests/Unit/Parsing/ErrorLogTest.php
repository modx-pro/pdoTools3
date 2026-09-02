<?php

declare(strict_types=1);

namespace ModxPro\PdoTools\Tests\Unit\Parsing;

use ErrorException;
use Exception;
use ModxPro\PdoTools\Parsing\Fenom\ErrorLog;
use PHPUnit\Framework\TestCase;

class ErrorLogTest extends TestCase
{
    public function testLooksLikeHash(): void
    {
        $this->assertTrue(ErrorLog::looksLikeHash(md5('chunk')));
        $this->assertFalse(ErrorLog::looksLikeHash('my-chunk'));
        $this->assertFalse(ErrorLog::looksLikeHash('modchunk/12'));
    }

    public function testExcerptMarksTheLine(): void
    {
        $content = "one\ntwo\n{var \$x = [[+limit]]}\nfour";
        $excerpt = ErrorLog::excerpt($content, 3, 1);

        $this->assertStringContainsString('> 3:', $excerpt);
        $this->assertStringContainsString('[[+limit]]', $excerpt);
        $this->assertStringContainsString(' 2: two', $excerpt);
    }

    public function testExcerptRejectsMissingLine(): void
    {
        $this->assertSame('', ErrorLog::excerpt("one\ntwo", 9));
        $this->assertSame('', ErrorLog::excerpt('', 1));
        $this->assertSame('', ErrorLog::excerpt('one', 0));
    }

    public function testModxHintNamesThePlaceholder(): void
    {
        $hint = ErrorLog::modxHint('{var $limit = [[+limit]]}');
        $this->assertStringContainsString('{$limit}', $hint);
    }

    public function testModxHintGenericWhenTagHasNoName(): void
    {
        $hint = ErrorLog::modxHint('[[$other]]');
        $this->assertStringContainsString('{$placeholder}', $hint);
    }

    public function testHasUnprocessedModx(): void
    {
        $this->assertTrue(ErrorLog::hasUnprocessedModx('[[+limit]]'));
        $this->assertTrue(ErrorLog::hasUnprocessedModx('[[*pagetitle]]'));
        $this->assertFalse(ErrorLog::hasUnprocessedModx('{$limit}'));
    }

    public function testReplaceTemplateNameKeepsHashOutOfTheMessage(): void
    {
        $hash = 'ee058690d9fd7413748b95b0960e006b';
        $message = "Unexpected token '+' in expression in {$hash} line 6, near '{var \$limit = [[+' <- there";
        $replaced = ErrorLog::replaceTemplateName($message, $hash, 'chunk:tpl.product.row (#12)');

        $this->assertStringContainsString('chunk:tpl.product.row (#12)', $replaced);
        $this->assertStringNotContainsString($hash, $replaced);
    }

    public function testReplaceTemplateNameLeavesMessageWhenLabelMatchesName(): void
    {
        $message = 'error in inline line 1';
        $this->assertSame($message, ErrorLog::replaceTemplateName($message, 'inline', 'inline'));
    }

    public function testExtractLineFromMessage(): void
    {
        $e = new Exception("Unexpected token '+' in expression in chunk:row line 6, near '{var'");
        $this->assertSame(6, ErrorLog::extractLine($e));
    }

    public function testExtractNear(): void
    {
        $this->assertSame(
            '{var $limit = [[+',
            ErrorLog::extractNear("near '{var \$limit = [[+' <- there")
        );
        $this->assertSame('', ErrorLog::extractNear('no near clause'));
    }

    public function testRelativePathUsesCorePrefix(): void
    {
        $path = rtrim(str_replace('\\', '/', MODX_CORE_PATH), '/') . '/cache/pdotools/error/foo';
        $this->assertSame('core/cache/pdotools/error/foo', ErrorLog::relativePath($path));
    }

    public function testExtractLineFromErrorExceptionWithoutPhpFile(): void
    {
        $e = new ErrorException('boom', 0, E_ERROR, 'inline-template', 4);
        $this->assertSame(4, ErrorLog::extractLine($e));
    }
}

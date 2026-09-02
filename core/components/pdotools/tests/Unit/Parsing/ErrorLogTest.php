<?php

declare(strict_types=1);

namespace ModxPro\PdoTools\Tests\Unit\Parsing;

use Exception;
use ModxPro\PdoTools\Parsing\Fenom\ErrorLog;
use PHPUnit\Framework\TestCase;

class ErrorLogTest extends TestCase
{
    public function testLabelForNamedChunk(): void
    {
        $this->assertSame(
            'chunk:tpl.product.row (#12)',
            ErrorLog::label([
                'binding' => 'modchunk',
                'id' => 12,
                'elementName' => 'tpl.product.row',
            ], 'modchunk/12')
        );
    }

    public function testLabelForFileAndInlineOrigins(): void
    {
        $file = rtrim(str_replace('\\', '/', MODX_CORE_PATH), '/') . '/elements/chunks/item.tpl';
        $this->assertSame(
            'file:core/elements/chunks/item.tpl',
            ErrorLog::label([
                'binding' => 'modchunk',
                'origin' => 'FILE',
                'sourceFile' => $file,
            ], 'modchunk/' . md5('file'))
        );
        $this->assertSame(
            'inline',
            ErrorLog::label(['binding' => 'modchunk', 'origin' => 'INLINE'], 'inline')
        );
    }

    public function testLabelFallsBackToFileWithoutOrigin(): void
    {
        $file = rtrim(str_replace('\\', '/', MODX_CORE_PATH), '/') . '/elements/chunks/item.tpl';
        $this->assertSame(
            'file:core/elements/chunks/item.tpl',
            ErrorLog::label([
                'binding' => 'modchunk',
                'sourceFile' => $file,
            ], 'modchunk/' . md5('file'))
        );
    }

    public function testLabelForResourceWithTemplate(): void
    {
        $this->assertSame(
            'resource:#42 (web:catalog/item), template:#5',
            ErrorLog::label([
                'resourceId' => 42,
                'resourceContext' => 'web',
                'resourceUri' => 'catalog/item',
                'templateId' => 5,
            ], md5('page'))
        );
    }

    public function testFormatReplacesHashAndAddsHint(): void
    {
        $hash = 'ee058690d9fd7413748b95b0960e006b';
        $content = "one\ntwo\nthree\nfour\nfive\n{var \$limit = [[+limit]]}\nseven";
        $e = new Exception(
            "Unexpected token '+' in expression in {$hash} line 6, near '{var \$limit = [[+' <- there"
        );
        $message = ErrorLog::format(
            $e,
            $hash,
            $content,
            'chunk:tpl.product.row (#12)',
            'compile'
        );

        $this->assertStringContainsString('[pdoTools][Fenom] compile error in chunk:tpl.product.row (#12)', $message);
        $this->assertStringContainsString('cache name: ' . $hash, $message);
        $this->assertStringContainsString('chunk:tpl.product.row (#12) line 6', $message);
        $this->assertStringNotContainsString(' in ' . $hash . ' ', $message);
        $this->assertStringContainsString('> 6:', $message);
        $this->assertStringContainsString('{$limit}', $message);
    }

    public function testFormatAddsResourceLineAndSourceDump(): void
    {
        $hash = md5('broken');
        $e = new Exception("Unexpected token '+' in expression in {$hash} line 1, near '{var'");
        $dump = rtrim(str_replace('\\', '/', MODX_CORE_PATH), '/') . '/cache/pdotools/error/' . $hash;
        $message = ErrorLog::format(
            $e,
            $hash,
            '{var $limit = [[+limit]]}',
            'inline',
            'compile',
            [
                'resource' => [
                    'resourceId' => 42,
                    'resourceContext' => 'web',
                    'resourceUri' => 'catalog/item',
                ],
                'sourceDump' => $dump,
            ]
        );

        $this->assertStringContainsString('resource:#42 (web:catalog/item)', $message);
        $this->assertStringContainsString('source dump: core/cache/pdotools/error/' . $hash, $message);
        $this->assertStringContainsString('{$limit}', $message);
    }

    public function testFormatSkipsResourceLineWhenLabelIsResource(): void
    {
        $e = new Exception('syntax error near token');
        $message = ErrorLog::format(
            $e,
            md5('x'),
            '{var $x = 1}',
            'resource:#1 (web:home), template:#2',
            'compile',
            [
                'resource' => [
                    'resourceId' => 1,
                    'resourceContext' => 'web',
                    'resourceUri' => 'home',
                ],
            ]
        );

        $lines = explode("\n", $message);
        $resourceLines = array_filter($lines, static function ($line) {
            return strpos($line, 'resource:#') === 0;
        });
        $this->assertCount(0, $resourceLines);
        $this->assertStringContainsString('compile error in resource:#1 (web:home), template:#2', $message);
    }
}

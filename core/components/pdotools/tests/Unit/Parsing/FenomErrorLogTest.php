<?php

declare(strict_types=1);

namespace ModxPro\PdoTools\Tests\Unit\Parsing;

use Exception;
use ModxPro\PdoTools\Tests\Support\FenomHarness;
use ModxPro\PdoTools\Tests\TestCase;

class FenomErrorLogTest extends TestCase
{
    public function testSourceLabelPrefersExplicitField(): void
    {
        $fenom = $this->harness();
        $this->assertSame(
            'chunk:tpl.product.row (#12)',
            $fenom->publicResolveSourceLabel(
                ['sourceLabel' => 'chunk:tpl.product.row (#12)', 'name' => md5('x')],
                md5('x'),
                '{var $x = 1}'
            )
        );
    }

    public function testSourceLabelFromChunkBinding(): void
    {
        $fenom = $this->harness();
        $this->assertSame(
            'chunk:tpl.product.row (#12)',
            $fenom->publicResolveSourceLabel(
                [
                    'binding' => 'modchunk',
                    'id' => 12,
                    'name' => 'tpl.product.row',
                    'elementName' => 'tpl.product.row',
                ],
                'modchunk/12',
                '{var $x = 1}'
            )
        );
    }

    public function testSourceLabelForFileAndInline(): void
    {
        $fenom = $this->harness();
        $file = rtrim(str_replace('\\', '/', MODX_CORE_PATH), '/') . '/elements/chunks/item.tpl';
        $this->assertSame(
            'chunk file:core/elements/chunks/item.tpl',
            $fenom->publicResolveSourceLabel(
                ['binding' => 'modchunk', 'sourceFile' => $file],
                'modchunk/' . md5('file'),
                '{var $x = 1}'
            )
        );
        $this->assertSame(
            'inline',
            $fenom->publicResolveSourceLabel(['sourceLabel' => 'inline'], 'inline', '{var $x = 1}')
        );
    }

    public function testFormatReplacesHashAndAddsHint(): void
    {
        $hash = 'ee058690d9fd7413748b95b0960e006b';
        $content = "one\ntwo\nthree\nfour\nfive\n{var \$limit = [[+limit]]}\nseven";
        $e = new Exception(
            "Unexpected token '+' in expression in {$hash} line 6, near '{var \$limit = [[+' <- there"
        );
        $message = $this->harness()->publicFormatFenomError(
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

    public function testProcessLogsCompileErrorWithoutChangingCacheName(): void
    {
        $chunk = [
            'content' => '{var $limit = [[+limit]]}',
            'binding' => 'modchunk',
            'id' => 12,
            'name' => 'tpl.product.row',
            'elementName' => 'tpl.product.row',
            'sourceLabel' => 'chunk:tpl.product.row (#12)',
        ];
        $this->fenom()->process($chunk);

        $this->assertNotEmpty($this->modx->logs);
        $logged = (string)$this->modx->logs[0]['message'];
        $this->assertStringContainsString('chunk:tpl.product.row (#12)', $logged);
        $this->assertStringContainsString('cache name: modchunk/12', $logged);
        $this->assertStringContainsString('{$limit}', $logged);
        $this->assertNotNull($this->pdoTools->getStore('modchunk/12', 'fenom'));
    }

    public function testSaveOnErrorsListsSourceDump(): void
    {
        $this->modx->config['pdotools_fenom_save_on_errors'] = true;
        $this->modx->cacheManager = $this->modx->getCacheManager();
        $hash = md5('broken');
        $e = new Exception("Unexpected token '+' in expression in {$hash} line 1, near '{var'");
        $message = $this->harness()->publicFormatFenomError(
            $e,
            $hash,
            '{var $limit = [[+limit]]}',
            'inline',
            'compile'
        );

        $this->assertStringContainsString('source dump:', $message);
        $this->assertStringContainsString('error/' . $hash, $message);
    }

    private function harness(): FenomHarness
    {
        return new FenomHarness($this->modx, $this->pdoTools);
    }
}

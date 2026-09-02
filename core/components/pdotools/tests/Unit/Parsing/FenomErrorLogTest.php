<?php

declare(strict_types=1);

namespace ModxPro\PdoTools\Tests\Unit\Parsing;

use ModxPro\PdoTools\Tests\TestCase;

class FenomErrorLogTest extends TestCase
{
    public function testProcessLogsCompileErrorWithoutChangingCacheName(): void
    {
        $chunk = [
            'content' => '{var $limit = [[+limit]]}',
            'binding' => 'modchunk',
            'origin' => '',
            'id' => 12,
            'name' => 'tpl.product.row',
            'elementName' => 'tpl.product.row',
        ];
        $this->fenom()->process($chunk);

        $this->assertNotEmpty($this->modx->logs);
        $logged = (string)$this->modx->logs[0]['message'];
        $this->assertStringContainsString('chunk:tpl.product.row (#12)', $logged);
        $this->assertStringContainsString('cache name: modchunk/12', $logged);
        $this->assertStringContainsString('{$limit}', $logged);
        $this->assertNotNull($this->pdoTools->getStore('modchunk/12', 'fenom'));
    }

    public function testProcessFileOriginUsesCanonicalFileLabel(): void
    {
        $file = rtrim(str_replace('\\', '/', MODX_CORE_PATH), '/') . '/elements/chunks/item.tpl';
        $this->fenom()->process([
            'content' => '{var $limit = [[+limit]]}',
            'binding' => 'modchunk',
            'origin' => 'FILE',
            'sourceFile' => $file,
            'name' => md5('@FILE item'),
        ]);

        $logged = (string)$this->modx->logs[0]['message'];
        $this->assertStringContainsString('compile error in file:core/elements/chunks/item.tpl', $logged);
        $this->assertStringNotContainsString('chunk file:', $logged);
    }

    public function testSaveOnErrorsListsSourceDump(): void
    {
        $this->modx->config['pdotools_fenom_save_on_errors'] = true;
        $this->modx->cacheManager = $this->modx->getCacheManager();
        $this->fenom()->process([
            'content' => '{var $limit = [[+limit]]}',
            'binding' => 'modchunk',
            'origin' => 'INLINE',
            'name' => 'inline-broken',
        ]);

        $logged = (string)$this->modx->logs[0]['message'];
        $this->assertStringContainsString('compile error in inline', $logged);
        $this->assertStringContainsString('source dump:', $logged);
        $this->assertStringContainsString('error/', $logged);
    }
}

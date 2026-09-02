<?php

declare(strict_types=1);

namespace ModxPro\PdoTools\Tests\Unit\CoreTools;

use ModxPro\PdoTools\Tests\Support\CoreToolsHarness;
use ModxPro\PdoTools\Tests\TestCase;

class SourceLabelTest extends TestCase
{
    public function testChunkLabelIncludesNameAndId(): void
    {
        $tools = new CoreToolsHarness($this->modx);
        $this->assertSame(
            'chunk:tpl.product.row (#12)',
            $tools->publicBuildElementSourceLabel('modChunk', '', $this->element(12), 'tpl.product.row', '')
        );
    }

    public function testFileBindingUsesCoreRelativePath(): void
    {
        $tools = new CoreToolsHarness($this->modx);
        $file = rtrim(str_replace('\\', '/', MODX_CORE_PATH), '/') . '/elements/chunks/item.tpl';
        $this->assertSame(
            'file:core/elements/chunks/item.tpl',
            $tools->publicBuildElementSourceLabel('modChunk', 'FILE', $this->element(0), '', $file)
        );
    }

    public function testInlineBinding(): void
    {
        $tools = new CoreToolsHarness($this->modx);
        $this->assertSame(
            'inline',
            $tools->publicBuildElementSourceLabel('modChunk', 'INLINE', $this->element(0), '', '')
        );
    }

    /**
     * @return object
     */
    private function element(int $id)
    {
        return new class ($id) {
            /** @var int */
            private $id;

            public function __construct(int $id)
            {
                $this->id = $id;
            }

            public function get($key)
            {
                return $key === 'id' ? $this->id : null;
            }
        };
    }
}

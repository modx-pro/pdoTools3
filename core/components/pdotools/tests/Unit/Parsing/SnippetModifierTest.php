<?php

declare(strict_types=1);

namespace ModxPro\PdoTools\Tests\Unit\Parsing;

use ModxPro\PdoTools\CoreTools;
use ModxPro\PdoTools\Parsing\Fenom\Fenom;
use ModxPro\PdoTools\Tests\TestCase;

class SnippetModifierTest extends TestCase
{
    public function testUnknownModifierRunsSnippet(): void
    {
        $tools = new class($this->modx, [
            'useFenom' => true,
            'useFenomCache' => false,
            'cachePath' => dirname(__DIR__, 3) . '/tmp/cache/pdotools',
        ]) extends CoreTools {
            /** @var array<string, mixed>|null */
            public $lastSnippetProps = null;

            public $lastSnippetName = '';

            public function runSnippet($name, array $properties = [])
            {
                $this->lastSnippetName = (string)$name;
                $this->lastSnippetProps = $properties;

                return 'SNIP:' . $properties['input'];
            }
        };

        $compile = dirname(__DIR__, 3) . '/tmp/cache/pdotools/file';
        if (!is_dir($compile) && !mkdir($compile, 0777, true) && !is_dir($compile)) {
            $this->fail('Cannot create Fenom compile dir');
        }

        $fenom = new Fenom($this->modx, $tools);
        $tpl = $fenom->compileCode('{$v|mySnippet}', 'snippet-mod.tpl');
        $out = $tpl->fetch(['v' => 'hello']);

        $this->assertSame('SNIP:hello', $out);
        $this->assertSame('mySnippet', $tools->lastSnippetName);
        $this->assertSame('hello', $tools->lastSnippetProps['input'] ?? null);
    }
}

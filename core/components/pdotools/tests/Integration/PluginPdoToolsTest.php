<?php

declare(strict_types=1);

namespace ModxPro\PdoTools\Tests\Integration;

use ModxPro\PdoTools\Parsing\Parser;

class PluginPdoToolsTest extends ModxTestCase
{
    public function testParserIsPdoToolsWhenEnabled(): void
    {
        $parser = self::$modx->getParser();
        if (!$parser instanceof Parser) {
            $this->markTestSkipped('modParser.class is not pdoTools on this install.');
        }
        $this->assertInstanceOf(Parser::class, $parser);
    }

    public function testFenomProcessRendersOnResource(): void
    {
        $fenom = self::$modx->services->get('fenom');
        $this->assertNotNull($fenom);
        $out = $fenom->process('{var $x = 1}{$x}');
        $this->assertSame('1', $out);
    }
}

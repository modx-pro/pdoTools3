<?php

declare(strict_types=1);

namespace ModxPro\PdoTools\Tests\Unit\Parsing;

use ModxPro\PdoTools\Tests\TestCase;

class FenomModifiersTest extends TestCase
{
    /**
     * One case per default pdoTools modifier family, not every alias.
     *
     * @return array<string, array{0:string,1:string,2:array<string, mixed>}>
     */
    public static function modifierProvider(): array
    {
        return [
            'intval' => ['42', '{$v|intval}', ['v' => '42.8']],
            'boolval' => ['1', '{$v|boolval}', ['v' => '1']],
            'strval' => ['9', '{$v|strval}', ['v' => 9]],
            'floatval' => ['1.5', '{$v|floatval}', ['v' => '1.5']],
            'lower' => ['ada', '{$v|lower}', ['v' => 'ADA']],
            'upper' => ['ADA', '{$v|upper}', ['v' => 'ada']],
            'md5' => [md5('x'), '{$v|md5}', ['v' => 'x']],
            'nl2br' => ["a<br />\nb", '{$v|nl2br}', ['v' => "a\nb"]],
            'notags' => ['hi', '{$v|notags}', ['v' => '<b>hi</b>']],
            'htmlentities' => ['&lt;b&gt;', '{$v|htmlentities}', ['v' => '<b>']],
            'limit' => ['Hel', '{$v|limit:3}', ['v' => 'Hello']],
            'esc' => ['&#91;x&#93;', '{$v|esc}', ['v' => '[x]']],
        ];
    }

    /**
     * @dataProvider modifierProvider
     * @param array<string, mixed> $vars
     */
    public function testDefaultModifier(string $expected, string $code, array $vars): void
    {
        $this->assertRender($expected, $code, $vars);
    }

    public function testGetModifierWithNullTemplateResolvesSnippet(): void
    {
        $tools = new class($this->modx, [
            'useFenom' => true,
            'useFenomCache' => false,
            'cachePath' => dirname(__DIR__, 3) . '/tmp/cache/pdotools',
        ]) extends \ModxPro\PdoTools\CoreTools {
            public function runSnippet($name, array $properties = [])
            {
                return 'OK:' . $properties['input'];
            }
        };

        $compile = dirname(__DIR__, 3) . '/tmp/cache/pdotools/file';
        if (!is_dir($compile) && !mkdir($compile, 0777, true) && !is_dir($compile)) {
            $this->fail('Cannot create Fenom compile dir');
        }

        $fenom = new \ModxPro\PdoTools\Parsing\Fenom\Fenom($this->modx, $tools);
        $cb = $fenom->getModifier('ghostSnippet', null);
        $this->assertIsCallable($cb);
        $this->assertSame('OK:x', $cb('x'));
    }

    public function testFuzzydateUsesDateFormat(): void
    {
        $old = strtotime('2020-01-15 12:00:00');
        $this->assertRender(
            date('M j', $old),
            '{$v|fuzzydate}',
            ['v' => $old]
        );
    }

    public function testFuzzydateAcceptsLegacyStrftimeFormat(): void
    {
        $old = strtotime('2020-01-15 12:00:00');
        $this->assertRender(
            date('Y-m-d', $old),
            '{$v|fuzzydate:"%Y-%m-%d"}',
            ['v' => $old]
        );
    }
}

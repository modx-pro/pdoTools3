<?php

declare(strict_types=1);

namespace ModxPro\PdoTools\Tests\Unit;

use PHPUnit\Framework\TestCase;

class SourceMapTest extends TestCase
{
    /**
     * @return list<array{0:string,1:string}>
     */
    public static function srcClassProvider(): array
    {
        return [
            ['ModxPro\\PdoTools\\CoreTools', 'src/CoreTools.php'],
            ['ModxPro\\PdoTools\\Fetch', 'src/Fetch.php'],
            ['ModxPro\\PdoTools\\Support\\Paginator', 'src/Support/Paginator.php'],
            ['ModxPro\\PdoTools\\Support\\MenuBuilder', 'src/Support/MenuBuilder.php'],
            ['ModxPro\\PdoTools\\Parsing\\Parser', 'src/Parsing/Parser.php'],
            ['ModxPro\\PdoTools\\Parsing\\Tag', 'src/Parsing/Tag.php'],
            ['ModxPro\\PdoTools\\Parsing\\Fenom\\Fenom', 'src/Parsing/Fenom/Fenom.php'],
            ['ModxPro\\PdoTools\\Parsing\\Fenom\\Providers\\Chunk', 'src/Parsing/Fenom/Providers/Chunk.php'],
            ['ModxPro\\PdoTools\\Parsing\\Fenom\\Providers\\File', 'src/Parsing/Fenom/Providers/File.php'],
            ['ModxPro\\PdoTools\\Parsing\\Fenom\\Providers\\Template', 'src/Parsing/Fenom/Providers/Template.php'],
            ['ModxPro\\PdoTools\\Parsing\\Fenom\\Support\\App', 'src/Parsing/Fenom/Support/App.php'],
            ['ModxPro\\PdoTools\\Parsing\\Fenom\\Support\\Lexicon', 'src/Parsing/Fenom/Support/Lexicon.php'],
            ['ModxPro\\PdoTools\\Parsing\\Fenom\\Support\\CacheManager', 'src/Parsing/Fenom/Support/CacheManager.php'],
        ];
    }

    /**
     * @dataProvider srcClassProvider
     */
    public function testSrcClassIsAutoloadable(string $class, string $relative): void
    {
        $path = dirname(__DIR__, 2) . '/' . $relative;
        $this->assertFileExists($path);
        $this->assertTrue(class_exists($class), $class . ' must autoload');
    }

    /**
     * @return list<array{0:string}>
     */
    public static function snippetFileProvider(): array
    {
        $dir = dirname(__DIR__, 2) . '/elements/snippets';
        $out = [];
        foreach (glob($dir . '/snippet.*.php') ?: [] as $file) {
            $out[] = [$file];
        }
        $plugin = dirname(__DIR__, 2) . '/elements/plugins/plugin.pdotools.php';
        $out[] = [$plugin];

        return $out;
    }

    /**
     * @dataProvider snippetFileProvider
     */
    public function testElementFileIsParseable(string $file): void
    {
        $this->assertFileExists($file);
        $tokens = token_get_all((string)file_get_contents($file));
        $this->assertNotEmpty($tokens);
        $this->assertSame(T_OPEN_TAG, $tokens[0][0]);
    }
}

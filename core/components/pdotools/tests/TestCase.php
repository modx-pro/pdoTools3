<?php

declare(strict_types=1);

namespace ModxPro\PdoTools\Tests;

use Fenom\Render;
use MODX\Revolution\modX;
use ModxPro\PdoTools\CoreTools;
use ModxPro\PdoTools\Parsing\Fenom\Fenom;
use PHPUnit\Framework\TestCase as PhpUnitTestCase;
use Throwable;

abstract class TestCase extends PhpUnitTestCase
{
    /** @var Fenom|null */
    protected $fenom;

    /** @var CoreTools|null */
    protected $pdoTools;

    /** @var modX|null */
    protected $modx;

    protected function setUp(): void
    {
        parent::setUp();
        $this->modx = $this->createModx();
        $this->pdoTools = new CoreTools($this->modx, [
            'useFenom' => true,
            'useFenomCache' => false,
            'cachePath' => dirname(__DIR__) . '/tests/tmp/cache/pdotools',
        ]);
    }

    protected function createModx(): modX
    {
        return new modX();
    }

    protected function fenom(): Fenom
    {
        if ($this->fenom instanceof Fenom) {
            return $this->fenom;
        }

        $compile = dirname(__DIR__) . '/tests/tmp/cache/pdotools/file';
        if (!is_dir($compile) && !mkdir($compile, 0777, true) && !is_dir($compile)) {
            $this->fail('Cannot create Fenom compile dir');
        }

        $this->fenom = new Fenom($this->modx, $this->pdoTools);

        return $this->fenom;
    }

    /**
     * Compile and fetch a Fenom template (fenom-template/fenom exec()).
     *
     * @param array<string, mixed> $vars
     */
    protected function compileFetch(string $code, array $vars = []): string
    {
        $tpl = $this->fenom()->compileCode($code, 'inline.tpl');
        $this->assertInstanceOf(Render::class, $tpl);

        return (string)$tpl->fetch($vars);
    }

    /**
     * @param array<string, mixed> $vars
     */
    protected function compileError(string $code, array $vars = []): Throwable
    {
        try {
            $this->compileFetch($code, $vars);
            $this->fail('Expected a Fenom compile or runtime error');
        } catch (Throwable $e) {
            return $e;
        }
    }

    /**
     * @param array<string, mixed> $vars
     */
    protected function assertRender(string $expected, string $code, array $vars = []): void
    {
        $this->assertSame($expected, $this->compileFetch($code, $vars));
    }
}

<?php

declare(strict_types=1);

namespace ModxPro\PdoTools\Tests\Unit\Parsing;

use ModxPro\PdoTools\Parsing\Fenom\Providers\Chunk;
use ModxPro\PdoTools\Parsing\Fenom\Providers\File;
use ModxPro\PdoTools\Parsing\Fenom\Providers\Template;
use ModxPro\PdoTools\Tests\TestCase;
use MODX\Revolution\modChunk;
use MODX\Revolution\modTemplate;

class ProvidersTest extends TestCase
{
    public function testFileProviderUsesElementsPath(): void
    {
        $dir = dirname(__DIR__, 3) . '/tmp/elements';
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            $this->fail('Cannot create elements dir');
        }
        $this->pdoTools->config(['elementsPath' => $dir]);
        $provider = new File($this->modx, $this->pdoTools);

        $this->assertFalse($provider->templateExists('missing.tpl'));
    }

    public function testChunkProviderReportsMissingChunk(): void
    {
        $provider = new Chunk($this->modx, $this->pdoTools);
        $this->assertFalse($provider->templateExists('NoSuchChunk'));
    }

    public function testTemplateProviderReportsMissingTemplate(): void
    {
        $provider = new Template($this->modx, $this->pdoTools);
        $this->assertFalse($provider->templateExists('NoSuchTemplate'));
    }

    public function testChunkProviderEmptyPathsWhenMissing(): void
    {
        $provider = new Chunk($this->modx, $this->pdoTools);
        $time = 0.0;

        $this->assertSame('', $provider->getSource('Missing', $time));
        $this->assertSame('', $provider->getSource('12@props', $time));
        $this->assertGreaterThan(0.0, $provider->getLastModified('Missing'));
        $this->assertGreaterThan(0.0, $provider->getLastModified('42'));
        $this->assertTrue($provider->verify(['Missing' => 1.0]));
        $this->assertSame([], $provider->getList());
        $this->assertTrue($provider->templateExists('7') === false);
    }

    public function testTemplateProviderEmptyPathsWhenMissing(): void
    {
        $provider = new Template($this->modx, $this->pdoTools);
        $time = 0.0;

        $this->assertSame('', $provider->getSource('MissingTpl', $time));
        $this->assertSame('', $provider->getSource('9@props', $time));
        $this->assertGreaterThan(0.0, $provider->getLastModified('MissingTpl'));
        $this->assertGreaterThan(0.0, $provider->getLastModified('9'));
        $this->assertTrue($provider->verify(['MissingTpl' => 1.0]));
        $this->assertSame([], $provider->getList());
    }

    public function testChunkProviderReadsObjectContent(): void
    {
        $element = new class {
            public function getContent(): string
            {
                return 'chunk-body';
            }

            public function getProperties(): array
            {
                return [];
            }

            public function getPropertySet($name)
            {
                return null;
            }

            public function isStatic(): bool
            {
                return false;
            }

            public function getSourceFile()
            {
                return false;
            }
        };

        $this->modx = $this->modxWithObjects([
            modChunk::class => $element,
        ]);
        $this->pdoTools = new \ModxPro\PdoTools\CoreTools($this->modx, [
            'useFenom' => true,
            'useFenomCache' => false,
            'cachePath' => dirname(__DIR__, 3) . '/tmp/cache/pdotools',
        ]);

        $provider = new Chunk($this->modx, $this->pdoTools);
        $time = 0.0;
        $this->assertSame('chunk-body', $provider->getSource('Home', $time));
        $this->assertSame('chunk-body', $provider->getSource('Home@set', $time));
        $this->assertGreaterThan(0.0, $provider->getLastModified('Home'));
    }

    public function testTemplateProviderReadsObjectContent(): void
    {
        $element = new class {
            public function getContent(): string
            {
                return 'tpl-body';
            }

            public function getProperties(): array
            {
                return [];
            }

            public function getPropertySet($name)
            {
                return null;
            }

            public function isStatic(): bool
            {
                return false;
            }

            public function getSourceFile()
            {
                return false;
            }
        };

        $this->modx = $this->modxWithObjects([
            modTemplate::class => $element,
        ]);
        $this->pdoTools = new \ModxPro\PdoTools\CoreTools($this->modx, [
            'useFenom' => true,
            'useFenomCache' => false,
            'cachePath' => dirname(__DIR__, 3) . '/tmp/cache/pdotools',
        ]);

        $provider = new Template($this->modx, $this->pdoTools);
        $time = 0.0;
        $this->assertSame('tpl-body', $provider->getSource('Base', $time));
        $this->assertSame('tpl-body', $provider->getSource('Base@set', $time));
        $this->assertGreaterThan(0.0, $provider->getLastModified('Base'));
    }

    /**
     * @param array<string, object> $objects
     */
    private function modxWithObjects(array $objects): \MODX\Revolution\modX
    {
        return new class($objects) extends \MODX\Revolution\modX {
            /** @var array<string, object> */
            private $map;

            public function __construct(array $map)
            {
                parent::__construct();
                $this->map = $map;
            }

            public function getCount($class, $criteria = null)
            {
                return isset($this->map[$class]) ? 1 : 0;
            }

            public function getObject($class, $criteria = null)
            {
                return $this->map[$class] ?? null;
            }

            public function newQuery($class, $criteria = null)
            {
                return new class {
                    public $stmt;

                    public function select($columns): void
                    {
                    }

                    public function prepare(): bool
                    {
                        return false;
                    }
                };
            }
        };
    }
}

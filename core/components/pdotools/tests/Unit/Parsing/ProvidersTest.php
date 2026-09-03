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
        $time = -1.0;

        $this->assertSame('', $provider->getSource('Missing', $time));
        $this->assertSame(0.0, $time);
        $this->assertSame(0.0, $provider->getLastModified('Missing'));
        $this->assertSame(0.0, $provider->getLastModified('42'));
        $this->assertTrue($provider->verify(['Missing' => 1.0]));
        $this->assertSame([], $provider->getList());
    }

    public function testTemplateProviderEmptyPathsWhenMissing(): void
    {
        $provider = new Template($this->modx, $this->pdoTools);
        $time = -1.0;

        $this->assertSame('', $provider->getSource('MissingTpl', $time));
        $this->assertSame(0.0, $time);
        $this->assertSame(0.0, $provider->getLastModified('MissingTpl'));
        $this->assertTrue($provider->verify(['MissingTpl' => 1.0]));
        $this->assertSame([], $provider->getList());
    }

    public function testChunkProviderStableMtimeFromEditedon(): void
    {
        $element = $this->elementStub('chunk-body', 1_700_000_000);
        $this->modx = $this->modxWithObjects([modChunk::class => $element]);
        $this->pdoTools = new \ModxPro\PdoTools\CoreTools($this->modx, [
            'useFenom' => true,
            'useFenomCache' => false,
            'cachePath' => dirname(__DIR__, 3) . '/tmp/cache/pdotools',
        ]);

        $provider = new Chunk($this->modx, $this->pdoTools);
        $time = 0.0;
        $this->assertSame('chunk-body', $provider->getSource('Home', $time));
        $this->assertSame(1_700_000_000.0, $time);
        $this->assertSame(1_700_000_000.0, $provider->getLastModified('Home'));
        $this->assertSame('chunk-body', $provider->getSource('Home@set', $time));
        $this->assertSame(1_700_000_000.0, $time);
    }

    public function testTemplateProviderStableMtimeFromEditedon(): void
    {
        $element = $this->elementStub('tpl-body', 1_700_000_111);
        $this->modx = $this->modxWithObjects([modTemplate::class => $element]);
        $this->pdoTools = new \ModxPro\PdoTools\CoreTools($this->modx, [
            'useFenom' => true,
            'useFenomCache' => false,
            'cachePath' => dirname(__DIR__, 3) . '/tmp/cache/pdotools',
        ]);

        $provider = new Template($this->modx, $this->pdoTools);
        $time = 0.0;
        $this->assertSame('tpl-body', $provider->getSource('Base', $time));
        $this->assertSame(1_700_000_111.0, $time);
        $this->assertSame(1_700_000_111.0, $provider->getLastModified('Base'));
    }

    private function elementStub(string $content, int $editedon): object
    {
        return new class($content, $editedon) {
            private $content;
            private $editedon;

            public function __construct(string $content, int $editedon)
            {
                $this->content = $content;
                $this->editedon = $editedon;
            }

            public function getContent(): string
            {
                return $this->content;
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

            public function get($key)
            {
                return $key === 'editedon' ? $this->editedon : null;
            }
        };
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

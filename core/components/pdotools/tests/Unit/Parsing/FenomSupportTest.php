<?php

declare(strict_types=1);

namespace ModxPro\PdoTools\Tests\Unit\Parsing;

use ModxPro\PdoTools\Parsing\Fenom\Support\App;
use ModxPro\PdoTools\Parsing\Fenom\Support\CacheManager;
use ModxPro\PdoTools\Parsing\Fenom\Support\Lexicon;
use ModxPro\PdoTools\Tests\TestCase;

class FenomSupportTest extends TestCase
{
    public function testLexiconDelegatesToModx(): void
    {
        $app = new App($this->modx, $this->pdoTools);
        $this->assertSame('setting_foo', $app->lexicon('setting_foo'));
    }

    public function testLexiconLoadDoesNotThrow(): void
    {
        $lexicon = new Lexicon($this->modx);
        $lexicon->load('pdotools:default');
        $this->assertTrue(true);
    }

    public function testCacheManagerRoundTrip(): void
    {
        $cache = new CacheManager($this->modx);
        $value = 'stored';
        $this->assertTrue($cache->set('pdo/test', $value));
        $this->assertSame('stored', $cache->get('pdo/test'));
    }
}

<?php

declare(strict_types=1);

namespace ModxPro\PdoTools\Tests\Unit\Parsing;

use ModxPro\PdoTools\Parsing\Tag;
use ModxPro\PdoTools\Tests\TestCase;

class TagTest extends TestCase
{
    public function testProcessReturnsResultAndKeepsContent(): void
    {
        $tag = new Tag();
        $tag->modx = $this->modx;

        $content = 'plain';
        $ok = $tag->process([], $content);

        $this->assertTrue($ok);
        $this->assertSame('', $tag->getTag());
    }
}

<?php

declare(strict_types=1);

namespace ModxPro\PdoTools\Tests\Unit\Support;

use MODX\Revolution\modWebLink;
use ModxPro\PdoTools\Support\MenuItemState;
use PHPUnit\Framework\TestCase;

class MenuItemStateTest extends TestCase
{
    public function testSelfRowFlags(): void
    {
        $state = MenuItemState::fromRow(
            [
                'id' => 42,
                'idx' => 1,
                'last' => false,
                'level' => 1,
                'children' => 3,
                'template' => 1,
                'link_attributes' => '',
            ],
            ['hereId' => 42, 'displayStart' => true],
            static function ($id) {
                return (int)$id === 42;
            }
        );

        $this->assertTrue($state->isFirst);
        $this->assertFalse($state->isLast);
        $this->assertTrue($state->isActive);
        $this->assertTrue($state->isHere);
        $this->assertTrue($state->hasChildren);
        $this->assertTrue($state->isStart);
        $this->assertFalse($state->isInner);
        $this->assertSame(1, $state->placeholders()['hasChilds']);
    }

    public function testTplKeyPrefersTplHereWhenConfigured(): void
    {
        $state = MenuItemState::fromRow(
            [
                'id' => 5,
                'idx' => 2,
                'last' => true,
                'level' => 1,
                'children' => 0,
            ],
            ['hereId' => 5, 'tplHere' => '@INLINE here'],
            static function () {
                return true;
            }
        );

        $this->assertSame('tplHere', $state->tplKey(['hereId' => 5, 'tplHere' => '@INLINE here']));
        $this->assertNull($state->tplKey(['hereId' => 5]));
    }

    public function testCategoryAndParentTpl(): void
    {
        $state = MenuItemState::fromRow(
            [
                'id' => 9,
                'idx' => 2,
                'last' => false,
                'level' => 2,
                'children' => 2,
                'template' => 0,
                'link_attributes' => '',
            ],
            ['hereId' => 99],
            static function () {
                return false;
            }
        );

        $this->assertTrue($state->isCategory);
        $this->assertTrue($state->isInner);
        $this->assertSame(
            'tplCategoryFolder',
            $state->tplKey(['tplCategoryFolder' => '@INLINE cat', 'hereId' => 99])
        );
        $this->assertSame(
            'tplParentRow',
            $state->tplKey(['tplParentRow' => '@INLINE parent', 'hereId' => 99])
        );
    }

    public function testWeblinkRowIdAndClasses(): void
    {
        $state = MenuItemState::fromRow(
            [
                'id' => 10,
                'idx' => 1,
                'last' => false,
                'level' => 1,
                'children' => 0,
                'class_key' => modWebLink::class,
                'content' => '[[~20]]',
            ],
            [
                'hereId' => 20,
                'useWeblinkUrl' => true,
                'firstClass' => 'first',
                'selfClass' => 'self',
                'hereClass' => 'active',
                'webLinkClass' => 'weblink',
            ],
            static function ($id) {
                return (int)$id === 20;
            }
        );

        $this->assertSame(20, $state->rowId);
        $this->assertTrue($state->isActive);
        $this->assertTrue($state->isWebLink);
        $classes = $state->classes([
            'firstClass' => 'first',
            'selfClass' => 'self',
            'hereClass' => 'active',
            'webLinkClass' => 'weblink',
        ]);
        $this->assertStringContainsString('first', $classes);
        $this->assertStringContainsString('self', $classes);
        $this->assertStringContainsString('active', $classes);
        $this->assertStringContainsString('weblink', $classes);
    }
}

<?php

namespace ModxPro\PdoTools\Support;

use MODX\Revolution\modWebLink;

/**
 * One pdoMenu row: flags, CSS classes, and which tpl* key to use.
 */
class MenuItemState
{
    /** @var int */
    public $rowId;
    /** @var bool */
    public $isFirst;
    /** @var bool */
    public $isLast;
    /** @var bool */
    public $hasChildren;
    /** @var bool */
    public $isActive;
    /** @var bool */
    public $isHere;
    /** @var bool */
    public $isStart;
    /** @var bool */
    public $isCategory;
    /** @var bool */
    public $isInner;
    /** @var bool */
    public $isWebLink;
    /** @var int */
    public $level;
    /** @var array */
    public $row;

    /**
     * @param array $row
     * @param array $config
     * @param callable $isHere fn(int $id): bool
     * @return self
     */
    public static function fromRow(array $row, array $config, callable $isHere)
    {
        $state = new self();
        $state->row = $row;
        $state->level = (int)($row['level'] ?? 1);
        $state->rowId = self::resolveRowId($row, $config);
        $state->isFirst = isset($row['idx']) && (int)$row['idx'] === 1;
        $state->isLast = !empty($row['last']);
        $state->hasChildren = !empty($row['children']);
        $state->isActive = $state->rowId == ($config['hereId'] ?? 0);
        $state->isHere = (bool)$isHere($state->rowId);
        $state->isStart = $state->level === 1 && !empty($config['displayStart']);
        $state->isInner = $state->level > 1;
        $state->isWebLink = !empty($row['class_key']) && $row['class_key'] === modWebLink::class;
        $state->isCategory = $state->hasChildren && (
            empty($row['template'])
            || (isset($row['link_attributes']) && strpos((string)$row['link_attributes'], 'category') !== false)
        );

        return $state;
    }

    /**
     * @param array $row
     * @param array $config
     * @return int
     */
    public static function resolveRowId(array $row, array $config)
    {
        if (
            !empty($config['useWeblinkUrl'])
            && !empty($row['class_key'])
            && !empty($row['content'])
            && $row['class_key'] === modWebLink::class
            && is_numeric(trim($row['content'], '[]~ '))
        ) {
            return (int)trim($row['content'], '[]~ ');
        }

        return (int)($row['id'] ?? 0);
    }

    /**
     * @return array<string, bool>
     */
    public function boolFlags()
    {
        return [
            'isFirst' => $this->isFirst,
            'isLast' => $this->isLast,
            'isActive' => $this->isActive,
            'hasChildren' => $this->hasChildren,
            'isHere' => $this->isHere,
            'isStart' => $this->isStart,
            'isCategory' => $this->isCategory,
            'isInner' => $this->isInner,
        ];
    }

    /**
     * @return array<string, int>
     */
    public function placeholders()
    {
        return TemplateFlags::toPlaceholders($this->boolFlags());
    }

    /**
     * @param array $config
     * @return string
     */
    public function classes(array $config)
    {
        $classes = [];

        if (!empty($config['rowClass'])) {
            $classes[] = $config['rowClass'];
        }
        if ($this->isFirst && !empty($config['firstClass'])) {
            $classes[] = $config['firstClass'];
        } elseif ($this->isLast && !empty($config['lastClass'])) {
            $classes[] = $config['lastClass'];
        }
        if (!empty($config['levelClass'])) {
            $classes[] = $config['levelClass'] . $this->level;
        }
        if (
            $this->hasChildren
            && !empty($config['parentClass'])
            && ($this->level < ($config['level'] ?? 0) || empty($config['level']))
        ) {
            $classes[] = $config['parentClass'];
        }
        if ($this->isHere && !empty($config['hereClass'])) {
            $classes[] = $config['hereClass'];
        }
        if ($this->isActive && !empty($config['selfClass'])) {
            $classes[] = $config['selfClass'];
        }
        if ($this->isWebLink && !empty($config['webLinkClass'])) {
            $classes[] = $config['webLinkClass'];
        }

        return implode(' ', $classes);
    }

    /**
     * Config key for a specialized tpl*, or null to fall back to defineChunk().
     *
     * @param array $config
     * @return string|null
     */
    public function tplKey(array $config)
    {
        if ($this->isStart && !empty($config['tplStart'])) {
            return 'tplStart';
        }
        if ($this->hasChildren && $this->isActive && !empty($config['tplParentRowHere'])) {
            return 'tplParentRowHere';
        }
        if ($this->isInner && $this->isActive && !empty($config['tplInnerHere'])) {
            return 'tplInnerHere';
        }
        if ($this->isActive && !empty($config['tplHere'])) {
            return 'tplHere';
        }
        if ($this->hasChildren && $this->isHere && !empty($config['tplParentRowActive'])) {
            return 'tplParentRowActive';
        }
        if ($this->isCategory && !empty($config['tplCategoryFolder'])) {
            return 'tplCategoryFolder';
        }
        // Typo kept for backward compatibility
        if ($this->isCategory && !empty($config['tplCategoryFolders'])) {
            return 'tplCategoryFolders';
        }
        if ($this->hasChildren && !empty($config['tplParentRow'])) {
            return 'tplParentRow';
        }
        if ($this->isInner && !empty($config['tplInnerRow'])) {
            return 'tplInnerRow';
        }

        return null;
    }
}

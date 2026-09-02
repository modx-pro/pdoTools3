<?php

namespace ModxPro\PdoTools\Support;

/**
 * Flags for one pdoPage pagination slot.
 */
class PageItemState
{
    /**
     * @param int $page Slot page number
     * @param int $current Current page
     * @param int $pages Total pages
     * @param bool $skip Skip/ellipsis slot
     * @return array<string, bool>
     */
    public static function boolFlags($page, $current, $pages, $skip = false)
    {
        $page = (int)$page;
        $current = (int)$current;
        $pages = (int)$pages;

        return [
            'isFirst' => $page === 1,
            'isLast' => $pages > 0 && $page === $pages,
            'isActive' => !$skip && $page === $current,
            'isSkip' => (bool)$skip,
        ];
    }

    /**
     * @param int $page
     * @param int $current
     * @param int $pages
     * @param bool $skip
     * @return array<string, int>
     */
    public static function placeholders($page, $current, $pages, $skip = false)
    {
        return TemplateFlags::toPlaceholders(self::boolFlags($page, $current, $pages, $skip));
    }

    /**
     * Pick tplPageActive / tplPage / tplPageSkip from config.
     *
     * @param array $config
     * @param int $page
     * @param int $current
     * @param bool $skip
     * @return string
     */
    public static function tpl(array $config, $page, $current, $skip = false)
    {
        if ($skip && !empty($config['tplPageSkip'])) {
            return (string)$config['tplPageSkip'];
        }
        if ((int)$page === (int)$current && !empty($config['tplPageActive'])) {
            return (string)$config['tplPageActive'];
        }
        if (!empty($config['tplPage'])) {
            return (string)$config['tplPage'];
        }

        return '';
    }
}

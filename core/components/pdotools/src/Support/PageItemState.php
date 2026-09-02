<?php

namespace ModxPro\PdoTools\Support;

/**
 * Flags and tpl pick for one pdoPage pagination slot.
 */
class PageItemState
{
    /**
     * @param int $page Slot page number
     * @param int $current Current page
     * @param int $pages Total pages
     * @param bool $skip Skip/ellipsis slot
     * @return array<string, int>
     */
    public static function placeholders($page, $current, $pages, $skip = false)
    {
        $page = (int)$page;
        $current = (int)$current;
        $pages = (int)$pages;

        return TemplateFlags::toPlaceholders([
            'isFirst' => $page === 1,
            'isLast' => $pages > 0 && $page === $pages,
            'isActive' => !$skip && $page === $current,
            'isSkip' => (bool)$skip,
        ]);
    }

    /**
     * Pick tplPageActive / tplPage / tplPageSkip from config.
     * Skip slots only return tplPageSkip (or empty), never fall back to page tpls.
     *
     * @param array $config
     * @param int $page
     * @param int $current
     * @param bool $skip
     * @return string
     */
    public static function tpl(array $config, $page, $current, $skip = false)
    {
        if ($skip) {
            return !empty($config['tplPageSkip']) ? (string)$config['tplPageSkip'] : '';
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

<?php

namespace ModxPro\PdoTools\Support;

/**
 * Flags for one pdoCrumbs row.
 */
class CrumbItemState
{
    /**
     * @param int $id Resource id
     * @param int $currentId Destination resource id
     * @param int $siteStart
     * @param int $index 0-based position in the rendered list
     * @param int $total Count of rendered crumbs
     * @return array<string, bool>
     */
    public static function boolFlags($id, $currentId, $siteStart, $index, $total)
    {
        return [
            'isFirst' => (int)$index === 0,
            'isLast' => $total > 0 && (int)$index === (int)$total - 1,
            'isActive' => (int)$id === (int)$currentId,
            'isHome' => (int)$id === (int)$siteStart,
        ];
    }

    /**
     * @param int $id
     * @param int $currentId
     * @param int $siteStart
     * @param int $index
     * @param int $total
     * @return array<string, int>
     */
    public static function placeholders($id, $currentId, $siteStart, $index, $total)
    {
        return TemplateFlags::toPlaceholders(
            self::boolFlags($id, $currentId, $siteStart, $index, $total)
        );
    }
}

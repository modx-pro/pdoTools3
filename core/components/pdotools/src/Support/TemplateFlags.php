<?php

namespace ModxPro\PdoTools\Support;

/**
 * Bool row flags as 1/0 for Fenom and MODX placeholders.
 */
class TemplateFlags
{
    /**
     * @param array<string, bool> $bools
     * @return array<string, int>
     */
    public static function toPlaceholders(array $bools)
    {
        $out = [];
        foreach ($bools as $key => $value) {
            $out[$key] = $value ? 1 : 0;
        }
        if (isset($out['hasChildren']) && !isset($out['hasChilds'])) {
            $out['hasChilds'] = $out['hasChildren'];
        }

        return $out;
    }
}

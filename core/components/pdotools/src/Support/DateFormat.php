<?php

namespace ModxPro\PdoTools\Support;

/**
 * Converts legacy strftime() tokens to date() format for PHP 8.1+.
 */
class DateFormat
{
    private const MAP = [
        '%Y' => 'Y',
        '%y' => 'y',
        '%m' => 'm',
        '%d' => 'd',
        '%e' => 'j',
        '%B' => 'F',
        '%b' => 'M',
        '%H' => 'H',
        '%I' => 'h',
        '%M' => 'i',
        '%S' => 's',
        '%p' => 'A',
        '%P' => 'a',
        '%%' => '%',
    ];

    public static function toDate(string $format): string
    {
        if ($format === '' || !str_contains($format, '%')) {
            return $format;
        }

        return strtr($format, self::MAP);
    }
}

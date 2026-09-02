<?php

namespace ModxPro\PdoTools\Parsing\Fenom;

use ErrorException;
use Throwable;

/**
 * Formats Fenom compile/runtime errors for the MODX log.
 * Does not change template names or cache keys.
 */
class ErrorLog
{
    /**
     * @param string $value
     * @return bool
     */
    public static function looksLikeHash($value)
    {
        return is_string($value) && (bool)preg_match('/^[a-f0-9]{32}$/i', $value);
    }

    /**
     * @param string $path
     * @return string
     */
    public static function relativePath($path)
    {
        if (!is_string($path) || $path === '') {
            return '';
        }
        $path = str_replace('\\', '/', $path);
        if (defined('MODX_CORE_PATH') && MODX_CORE_PATH !== '' && strpos($path, str_replace('\\', '/', MODX_CORE_PATH)) === 0) {
            return 'core/' . ltrim(substr($path, strlen(str_replace('\\', '/', MODX_CORE_PATH))), '/');
        }
        if (defined('MODX_BASE_PATH') && MODX_BASE_PATH !== '' && strpos($path, str_replace('\\', '/', MODX_BASE_PATH)) === 0) {
            return ltrim(substr($path, strlen(str_replace('\\', '/', MODX_BASE_PATH))), '/');
        }

        return $path;
    }

    /**
     * @param Throwable $e
     * @return int
     */
    public static function extractLine(Throwable $e)
    {
        if (preg_match('/\bline\s+(\d+)/i', $e->getMessage(), $m)) {
            return (int)$m[1];
        }
        if ($e instanceof ErrorException) {
            $file = str_replace('\\', '/', (string)$e->getFile());
            if ($file !== '' && substr($file, -4) !== '.php' && strpos($file, '/') === false) {
                return (int)$e->getLine();
            }
        }

        return 0;
    }

    /**
     * @param string $message
     * @return string
     */
    public static function extractNear($message)
    {
        if (!is_string($message) || !preg_match("/near '([^']*)'/s", $message, $m)) {
            return '';
        }

        return $m[1];
    }

    /**
     * @param string $text
     * @return bool
     */
    public static function hasUnprocessedModx($text)
    {
        return is_string($text) && (bool)preg_match('/\[\[(?:\+|\*|\$|%|~|#|&)?/', $text);
    }

    /**
     * @param string $text
     * @return string
     */
    public static function modxHint($text)
    {
        if (!self::hasUnprocessedModx($text)) {
            return '';
        }
        if (preg_match('/\[\[\+([a-zA-Z0-9._-]+)/', $text, $m)) {
            return 'Unprocessed MODX tag inside Fenom. Use {$' . $m[1] . '} or parse MODX before Fenom.';
        }

        return 'Unprocessed MODX tag inside Fenom. Use {$placeholder} or parse MODX before Fenom.';
    }

    /**
     * @param string $content
     * @param int $line
     * @param int $radius
     * @return string
     */
    public static function excerpt($content, $line, $radius = 2)
    {
        if (!is_string($content) || $content === '' || $line < 1) {
            return '';
        }
        $lines = preg_split("/\r\n|\n|\r/", $content);
        $index = $line - 1;
        if (!isset($lines[$index])) {
            return '';
        }
        $start = max(0, $index - $radius);
        $end = min(count($lines) - 1, $index + $radius);
        $out = [];
        for ($i = $start; $i <= $end; $i++) {
            $mark = ($i === $index) ? '>' : ' ';
            $out[] = sprintf('%s %d: %s', $mark, $i + 1, $lines[$i]);
        }

        return implode("\n", $out);
    }

    /**
     * @param string $message
     * @param string $name
     * @param string $label
     * @return string
     */
    public static function replaceTemplateName($message, $name, $label)
    {
        if (!is_string($message) || $name === '' || $label === '' || $name === $label) {
            return $message;
        }

        return str_replace(' in ' . $name . ' ', ' in ' . $label . ' ', $message);
    }
}

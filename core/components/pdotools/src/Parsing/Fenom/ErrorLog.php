<?php

namespace ModxPro\PdoTools\Parsing\Fenom;

use Throwable;

/**
 * Builds Fenom error labels and log messages. Does not change cache keys.
 */
class ErrorLog
{
    /**
     * Human-readable source from element/resource facts.
     *
     * Expected keys: binding (modchunk|…), origin (FILE|INLINE|CODE|''),
     * elementName, sourceFile, id, name, resourceId, resourceUri,
     * resourceContext, templateId.
     *
     * @param array $source
     * @param string $cacheName Fenom template / store name
     * @return string
     */
    public static function label(array $source, $cacheName = '')
    {
        $origin = isset($source['origin']) ? strtoupper((string)$source['origin']) : '';
        $binding = isset($source['binding']) ? (string)$source['binding'] : '';
        $id = !empty($source['id']) ? (int)$source['id'] : 0;
        $elementName = isset($source['elementName']) ? (string)$source['elementName'] : '';
        if (
            $elementName === ''
            && !empty($source['name'])
            && !self::looksLikeHash((string)$source['name'])
        ) {
            $elementName = (string)$source['name'];
        }
        $file = self::relativePath(isset($source['sourceFile']) ? (string)$source['sourceFile'] : '');
        $kind = self::elementKind($binding);

        if ($origin === 'FILE' && $file !== '') {
            return 'file:' . $file;
        }
        if ($origin === 'INLINE' || $origin === 'CODE') {
            return 'inline';
        }
        if ($elementName !== '' && !self::looksLikeHash($elementName)) {
            $label = $kind !== '' ? $kind . ':' . $elementName : $elementName;
            if ($id > 0) {
                $label .= ' (#' . $id . ')';
            }
            if ($file !== '') {
                $label .= ' file:' . $file;
            }

            return $label;
        }
        if ($id > 0) {
            return ($kind !== '' ? $kind : 'element') . ':#' . $id;
        }
        if ($file !== '') {
            return 'file:' . $file;
        }

        $resource = self::formatResource($source);
        if ($resource !== '') {
            return $resource;
        }

        return $cacheName !== '' ? $cacheName : 'unknown';
    }

    /**
     * Full MODX / &showLog error block.
     *
     * @param Throwable $e
     * @param string $cacheName
     * @param string $content
     * @param string $label
     * @param string $phase compile|runtime
     * @param array $extra compiled, sourceDump (paths), resource (facts for a second line)
     * @return string
     */
    public static function format(Throwable $e, $cacheName, $content, $label, $phase, array $extra = [])
    {
        if ($label === '') {
            $label = $cacheName !== '' ? $cacheName : 'unknown';
        }
        $raw = self::replaceTemplateName($e->getMessage(), $cacheName, $label);
        $line = self::extractLine($e);
        $near = self::extractNear($e->getMessage());
        $excerpt = self::excerpt($content, $line);
        $hint = self::modxHint($near . "\n" . $excerpt);

        $lines = [
            '[pdoTools][Fenom] ' . $phase . ' error in ' . $label,
        ];
        if (!empty($extra['resource']) && is_array($extra['resource']) && strpos($label, 'resource:') !== 0) {
            $resourceLine = self::formatResource($extra['resource']);
            if ($resourceLine !== '') {
                $lines[] = $resourceLine;
            }
        }
        if ($cacheName !== '' && $cacheName !== $label) {
            $lines[] = 'cache name: ' . $cacheName;
        }
        $lines[] = $raw;
        if ($excerpt !== '') {
            $lines[] = $excerpt;
        }
        if ($hint !== '') {
            $lines[] = $hint;
        }
        if (!empty($extra['compiled'])) {
            $compiled = self::relativePath((string)$extra['compiled']);
            if ($compiled !== '') {
                $lines[] = 'compiled: ' . $compiled;
            }
        }
        if (!empty($extra['sourceDump'])) {
            $dump = self::relativePath((string)$extra['sourceDump']);
            if ($dump !== '') {
                $lines[] = 'source dump: ' . $dump;
            }
        }

        return implode("\n", $lines);
    }

    /**
     * @param string $binding
     * @return string
     */
    private static function elementKind($binding)
    {
        switch (strtolower((string)$binding)) {
            case 'modchunk':
                return 'chunk';
            case 'modtemplate':
                return 'template';
            case 'modsnippet':
                return 'snippet';
            default:
                return '';
        }
    }

    /**
     * @param array $source
     * @return string
     */
    private static function formatResource(array $source)
    {
        if (!array_key_exists('resourceId', $source)) {
            return '';
        }
        $id = (int)$source['resourceId'];
        $ctx = isset($source['resourceContext']) ? (string)$source['resourceContext'] : '';
        $uri = isset($source['resourceUri']) ? (string)$source['resourceUri'] : '';
        $label = 'resource:#' . $id;
        if ($ctx !== '' || $uri !== '') {
            $label .= ' (' . $ctx . ':' . $uri . ')';
        }
        if (!empty($source['templateId'])) {
            $label .= ', template:#' . (int)$source['templateId'];
        }

        return $label;
    }

    /**
     * @param string $value
     * @return bool
     */
    private static function looksLikeHash($value)
    {
        return is_string($value) && (bool)preg_match('/^[a-f0-9]{32}$/i', $value);
    }

    /**
     * @param string $path
     * @return string
     */
    private static function relativePath($path)
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
    private static function extractLine(Throwable $e)
    {
        if (preg_match('/\bline\s+(\d+)/i', $e->getMessage(), $m)) {
            return (int)$m[1];
        }

        return 0;
    }

    /**
     * @param string $message
     * @return string
     */
    private static function extractNear($message)
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
    private static function hasUnprocessedModx($text)
    {
        return is_string($text) && (bool)preg_match('/\[\[(?:\+|\*|\$|%|~|#|&)?/', $text);
    }

    /**
     * @param string $text
     * @return string
     */
    private static function modxHint($text)
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
    private static function excerpt($content, $line, $radius = 2)
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
    private static function replaceTemplateName($message, $name, $label)
    {
        if (!is_string($message) || $name === '' || $label === '' || $name === $label) {
            return $message;
        }

        return str_replace(' in ' . $name . ' ', ' in ' . $label . ' ', $message);
    }
}

<?php

namespace ModxPro\PdoTools\Support;

/**
 * Appends the request context to a snippet result cache key.
 */
class CacheKey
{
    /**
     * Adds /{context} once. setCache() returns the storage key, so a later
     * getCache(['cache_key' => $returned]) must not become …/web/web.
     *
     * @param string $key
     * @param string $context
     * @return string
     */
    public static function withContext($key, $context)
    {
        $key = (string)$key;
        $context = trim((string)$context);
        if ($key === '' || $context === '') {
            return $key;
        }
        $suffix = '/' . $context;
        if (substr($key, -strlen($suffix)) === $suffix) {
            return $key;
        }

        return rtrim($key, '/') . $suffix;
    }
}

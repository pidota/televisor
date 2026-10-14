<?php

namespace App\Support;

class SubdirectoryUrl
{
    /**
     * Asegura que URLs absolutas del mismo host incluyan el prefijo APP_URL (p. ej. /televisor).
     * Necesario porque index.php recorta REQUEST_URI y session("url.intended") queda sin subdirectorio.
     */
    public static function ensureAbsolute(string $url): string
    {
        $base = rtrim((string) config('app.url'), '/');
        $subdir = rtrim(parse_url($base, PHP_URL_PATH) ?: '', '/');

        if ($subdir === '') {
            return $url;
        }

        $parsed = parse_url($url);

        if ($parsed === false || ! isset($parsed['host'])) {
            return $url;
        }

        $baseHost = parse_url($base, PHP_URL_HOST);

        if ($baseHost !== $parsed['host']) {
            return $url;
        }

        $path = $parsed['path'] ?? '/';

        if ($path === $subdir || str_starts_with($path, $subdir.'/')) {
            return $url;
        }

        $fixedPath = $subdir.($path === '/' ? '' : $path);
        $scheme = $parsed['scheme'] ?? 'https';
        $query = isset($parsed['query']) ? '?'.$parsed['query'] : '';
        $fragment = isset($parsed['fragment']) ? '#'.$parsed['fragment'] : '';

        return $scheme.'://'.$parsed['host'].$fixedPath.$query.$fragment;
    }
}

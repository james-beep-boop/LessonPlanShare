<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Provides the current application version string.
 *
 * Reads storage/app/version.txt once and caches the result indefinitely.
 * The cache key must be explicitly invalidated on each deploy:
 *
 *   php artisan cache:forget app_version
 *
 * The version.txt file is written by UPDATE_SITE.sh (short git hash)
 * and kept up-to-date by the .git/hooks/post-merge hook installed
 * by that same script.
 */
class VersionService
{
    /** Cache key used to store the current version string. */
    public const CACHE_KEY = 'app_version';

    public static function get(): string
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return trim(@file_get_contents(storage_path('app/version.txt')) ?: 'dev');
        });
    }
}

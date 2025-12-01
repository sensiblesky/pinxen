<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
    ];

    /**
     * Cache key for all settings
     */
    const CACHE_KEY = 'app_settings_all';

    /**
     * Cache duration in seconds (24 hours)
     */
    const CACHE_DURATION = 86400;

    /**
     * Get all settings from cache or database.
     */
    public static function getAllCached()
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_DURATION, function () {
            return self::all()->pluck('value', 'key')->toArray();
        });
    }

    /**
     * Get setting value by key (from cache).
     */
    public static function get($key, $default = null)
    {
        $settings = self::getAllCached();
        return $settings[$key] ?? $default;
    }

    /**
     * Set setting value by key and clear cache.
     */
    public static function set($key, $value, $type = 'text')
    {
        $result = self::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'type' => $type]
        );

        // Clear cache after update
        self::clearCache();

        return $result;
    }

    /**
     * Clear settings cache.
     */
    public static function clearCache()
    {
        // Try multiple methods to ensure cache is cleared
        Cache::forget(self::CACHE_KEY);
        Cache::forget('app_settings_all');
        
        // If using file cache, also try to clear the cache file directly
        $cachePath = storage_path('framework/cache/data');
        if (is_dir($cachePath)) {
            $cacheFile = $cachePath . '/' . md5('laravel_cache:' . self::CACHE_KEY);
            if (file_exists($cacheFile)) {
                @unlink($cacheFile);
            }
        }
    }

    /**
     * Get all settings (from cache).
     */
    public static function allCached()
    {
        return self::getAllCached();
    }
}

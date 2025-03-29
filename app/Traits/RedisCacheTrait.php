<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

trait RedisCacheTrait
{
    /**
     * Get cached book-related data or retrieve it using the callback
     */
    protected function getCachedBookData($key, $ttl, $callback)
    {
        try {
            return Cache::remember($key, $ttl, $callback);
        } catch (\Exception $e) {
            // If Redis fails, fallback to callback directly
            return $callback();
        }
    }

    /**
     * Invalidate book-related cache by pattern
     */
    protected function invalidateBookCache($pattern)
    {
        try {
            $keys = Redis::keys($pattern);
            if (!empty($keys)) {
                Redis::del($keys);
            }
        } catch (\Exception $e) {
            // Silently fail if Redis is unavailable
        }
    }
} 
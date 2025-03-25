<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

trait RedisCacheTrait
{
    protected function getCachedData($key, $ttl, $callback)
    {
        try {
            return Cache::remember($key, $ttl, $callback);
        } catch (\Exception $e) {
            // If Redis fails, fallback to callback directly
            return $callback();
        }
    }

    protected function invalidateCache($pattern)
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
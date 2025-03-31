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
            // Check if Redis is available
            if (!Redis::connection()->ping()) {
                \Log::warning("Redis not available for key: $key");
                return $callback();
            }
            
            return Cache::remember($key, $ttl, $callback);
        } catch (\Exception $e) {
            \Log::warning("Redis cache error for key $key: " . $e->getMessage());
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
            // Check if Redis is available
            if (!Redis::connection()->ping()) {
                \Log::warning("Redis not available for pattern deletion: $pattern");
                return;
            }
            
            $keys = Redis::keys($pattern);
            if (!empty($keys)) {
                // Process keys in smaller batches to avoid timeouts
                $chunks = array_chunk($keys, 10);
                foreach ($chunks as $chunk) {
                    try {
                        Redis::del($chunk);
                    } catch (\Exception $innerEx) {
                        \Log::warning("Failed to delete Redis keys batch: " . $innerEx->getMessage());
                        // Try individual deletions if batch fails
                        foreach ($chunk as $key) {
                            try {
                                Cache::forget($key);
                            } catch (\Exception $e) {
                                \Log::warning("Failed to delete individual Redis key: $key");
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            \Log::warning("Redis cache invalidation failed for pattern $pattern: " . $e->getMessage());
        }
    }
} 
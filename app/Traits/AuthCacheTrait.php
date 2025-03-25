<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

trait AuthCacheTrait
{
    protected function getCachedAuthData($key, $ttl, $callback)
    {
        try {
            return Cache::remember($key, $ttl, $callback);
        } catch (\Exception $e) {
            // If Redis fails, fallback to callback directly
            return $callback();
        }
    }

    protected function invalidateAuthCache($pattern)
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

    protected function getUserPermissionsCacheKey($userId)
    {
        return "auth:user:{$userId}:permissions";
    }

    protected function getUserRolesCacheKey($userId)
    {
        return "auth:user:{$userId}:roles";
    }
} 
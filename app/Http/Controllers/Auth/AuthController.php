<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    // Cache keys
    private const CACHE_KEY_PREFIX = 'bookcafe';
    private const CACHE_USER_PREFIX = 'user';
    private const CACHE_ROLE_PREFIX = 'role';
    private const CACHE_PERMISSION_PREFIX = 'perm';
    
    private $cacheExpiration = 3600; // 1 hour
    private $permissionsCacheExpiration = 7200; // 2 hours

    private function getCacheKey($type, $identifier) {
        return sprintf('%s:%s:%s', self::CACHE_KEY_PREFIX, $type, $identifier);
    }

    private function getUserCacheKey($userId) {
        return sprintf('%s:%s:%s', self::CACHE_KEY_PREFIX, self::CACHE_USER_PREFIX, $userId);
    }

    private function getRoleCacheKey($userId) {
        return sprintf('%s:%s:%s', self::CACHE_KEY_PREFIX, self::CACHE_ROLE_PREFIX, $userId);
    }

    private function getPermissionCacheKey($userId) {
        return sprintf('%s:%s:%s', self::CACHE_KEY_PREFIX, self::CACHE_PERMISSION_PREFIX, $userId);
    }

    private function clearUserCache($user) {
        Cache::forget($this->getUserCacheKey($user->id));
        Cache::forget($this->getRoleCacheKey($user->id));
        Cache::forget($this->getPermissionCacheKey($user->id));
    }

    private function cacheUserData($user) {
        $userData = [
            'id' => $user->id,
            'email' => $user->email,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'role' => $user->role
        ];
        Cache::put($this->getUserCacheKey($user->id), $userData, $this->cacheExpiration);
        
        Cache::put($this->getRoleCacheKey($user->id), $user->role, $this->permissionsCacheExpiration);
        
        $permissions = $this->getRolePermissions($user->role);
        Cache::put($this->getPermissionCacheKey($user->id), $permissions, $this->permissionsCacheExpiration);
    }

    private function getRolePermissions($role) {
        $permissions = [
            'teacher' => [
                'books' => [
                    'borrow' => true,
                    'add' => true,
                    'edit' => true,
                    'delete' => true,
                ],
                'library' => [
                    'access' => true,
                    'manage' => true
                ]
            ],
            'student' => [
                'books' => [
                    'borrow' => true,
                    'add' => false,
                    'edit' => false,
                    'delete' => false,
                ],
                'library' => [
                    'access' => true,
                    'manage' => false
                ]
            ]
        ];

        return $permissions[$role] ?? [];
    }

    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'firstname' => 'required|string|max:50',
            'lastname' => 'required|string|max:50',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:teacher,student'
        ]);

        $user = User::create([
            'firstname' => $validatedData['firstname'],
            'lastname' => $validatedData['lastname'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
            'role' => $validatedData['role']
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        // Cache user data including role and permissions
        $this->cacheUserData($user);

        return response()->json([
            'status' => 'success',
            'user' => $user,
            'token' => $token,
            'permissions' => $this->getRolePermissions($user->role)
        ], Response::HTTP_CREATED);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'error' => 'Invalid credentials'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        // Cache user data including role and permissions
        $this->cacheUserData($user);

        // Get permissions from cache or generate if not exists
        $permissions = Cache::remember(
            $this->getPermissionCacheKey($user->id),
            $this->permissionsCacheExpiration,
            function () use ($user) {
                return $this->getRolePermissions($user->role);
            }
        );

        return response()->json([
            'status' => 'success',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'role' => $user->role
            ],
            'permissions' => $permissions
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'error' => 'Email not found'
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            $reset_token = Str::random(64);
            $user->reset_token = $reset_token;
            $user->save();

            $this->clearUserCache($user);

            Mail::to($user->email)->send(new \App\Mail\ResetPasswordMail($user, $reset_token));

            return response()->json([
                'status' => 'success',
                'message' => 'Password reset instructions have been sent to your email'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send reset password email',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'password' => 'required|string|min:6'
        ]);

        $user = User::where('reset_token', $request->token)->first();

        if (!$user) {
            return response()->json([
                'error' => 'Invalid or expired reset token'
            ], Response::HTTP_BAD_REQUEST);
        }

        $user->password = Hash::make($request->password);
        $user->reset_token = null;
        $user->save();

        $this->clearUserCache($user);

        return response()->json([
            'status' => 'success',
            'message' => 'Password reset successful'
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        $user->tokens()->delete();

        $this->clearUserCache($user);

        return response()->json([
            'status' => 'success'
        ]);
    }
}
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * API Authentication Controller
 *
 * Token-based auth via Laravel Sanctum.
 * POST /api/v1/{slug}/auth/login
 */
class AuthController extends Controller
{
    /** Unified hospital auth guard */
    private string $guard = 'hospital_user';

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::guard($this->guard)->attempt($credentials)) {
            $user = Auth::guard($this->guard)->user();

            if ($user->tenant_id !== app('tenant')->id || $user->status !== 'active') {
                Auth::guard($this->guard)->logout();

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials.',
                ], 401);
            }

            $token = $user->createToken('api-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'data'    => [
                    'token' => $token,
                    'user'  => [
                        'id'    => $user->id,
                        'name'  => $user->name,
                        'email' => $user->email,
                        'role'  => $user->role?->slug,
                    ],
                ],
                'message' => 'Login successful.',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid credentials.',
        ], 401);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $request->user(),
        ]);
    }
}

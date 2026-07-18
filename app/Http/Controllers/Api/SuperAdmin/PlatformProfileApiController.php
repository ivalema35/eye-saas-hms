<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Support\EmailRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PlatformProfileApiController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->format($request->user()),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $admin = $request->user();

        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:100'],
            'email' => [
                ...EmailRules::required(100),
                'unique:platform_admins,email,' . $admin->id,
            ],
        ], EmailRules::messages('email'));

        $admin->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data'    => $this->format($admin->fresh()),
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $admin = $request->user();

        $request->validate([
            'current_password'      => ['required', 'string'],
            'password'              => ['required', 'confirmed', Password::min(8)],
            'password_confirmation' => ['required', 'string'],
        ]);

        if (! Hash::check($request->current_password, $admin->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect.',
                'errors'  => ['current_password' => ['Current password is incorrect.']],
            ], 422);
        }

        $admin->update(['password' => $request->password]);

        return response()->json(['success' => true, 'message' => 'Password changed successfully.']);
    }

    private function format($admin): array
    {
        return [
            'id'            => $admin->id,
            'name'          => $admin->name,
            'email'         => $admin->email,
            'role'          => $admin->role,
            'last_login_at' => $admin->last_login_at?->toISOString(),
            'last_login_ip' => $admin->last_login_ip,
        ];
    }
}

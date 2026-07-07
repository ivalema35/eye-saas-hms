<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hospital\HospitalUser;
use App\Models\Platform\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * API Authentication Controller
 *
 * Token-based auth via Laravel Sanctum.
 *
 * POST   /api/v1/{slug}/auth/login   → no auth required
 * POST   /api/v1/{slug}/auth/logout  → auth:sanctum required
 * GET    /api/v1/{slug}/auth/me      → auth:sanctum + subscription.active
 */
class AuthController extends Controller
{
    /**
     * Find Hospital — given an email or phone, returns the tenant slug.
     * Mobile app uses this to discover the slug before calling /auth/login.
     *
     * GET /api/v1/find-hospital?email={email_or_phone}
     */
    public function findHospital(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|string']);

        $login             = trim($request->input('email'));
        $normalizedContact = preg_replace('/\D+/', '', $login) ?? '';

        $user = HospitalUser::withoutTenantScope()
            ->where(function ($query) use ($login, $normalizedContact) {
                $query->where('email', $login)
                      ->orWhere('contact', $login);
                if ($normalizedContact !== '' && $normalizedContact !== $login) {
                    $query->orWhere('contact', $normalizedContact);
                }
            })
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->with('tenant')
            ->first();

        if (! $user || ! $user->tenant) {
            return response()->json([
                'success' => false,
                'message' => 'No hospital account found for this email or phone number.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'slug'          => $user->tenant->slug,
                'hospital_name' => $user->tenant->name,
            ],
            'message' => 'Hospital found.',
        ]);
    }

    /**
     * Login — supports email OR contact number.
     *
     * Request body:
     *   login    (required) — email address or phone number
     *   password (required)
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'login'    => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $login             = trim($request->input('login'));
        $normalizedContact = preg_replace('/\D+/', '', $login) ?? '';
        $tenant            = app('tenant');

        // Lookup by email OR contact, same logic as web LoginController
        $user = HospitalUser::query()
            ->with(['role.grantedPermissions'])
            ->where('tenant_id', $tenant->id)
            ->where(function ($query) use ($login, $normalizedContact) {
                $query->where('email', $login)
                      ->orWhere('contact', $login);

                if ($normalizedContact !== '' && $normalizedContact !== $login) {
                    $query->orWhere('contact', $normalizedContact);
                }
            })
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->first();

        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials.',
            ], 401);
        }

        // Stamp last login time
        $user->update(['last_login_at' => now()]);

        $token = $user->createToken('mobile-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data'    => [
                'token'    => $token,
                'user'     => $this->formatUser($user),
                'hospital' => $this->formatHospital($tenant),
            ],
            'message' => 'Login successful.',
        ]);
    }

    /**
     * Logout — revokes the current Sanctum token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Me — returns the authenticated user's profile.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->loadMissing(['role.grantedPermissions']);

        return response()->json([
            'success' => true,
            'data'    => [
                'user'     => $this->formatUser($user),
                'hospital' => $this->formatHospital(app('tenant')),
            ],
        ]);
    }

    // ── Private helpers ───────────────────────────────────────────────────

    private function formatUser(HospitalUser $user): array
    {
        $user->loadMissing(['role.grantedPermissions']);

        return [
            'id'                 => $user->id,
            'name'               => $user->name,
            'email'              => $user->email,
            'contact'            => $user->contact,
            'status'             => $user->status,
            'doctor_prefix'      => $user->doctor_prefix,
            'profile_photo_path' => $user->profile_photo_path,
            'profile_photo_url'  => $user->profile_photo_path
                ? asset('storage/' . $user->profile_photo_path)
                : null,
            'role' => $user->role ? [
                'id'          => $user->role->id,
                'name'        => $user->role->name,
                'slug'        => $user->role->slug,
                'color'       => $user->role->color,
                'is_super'    => (bool) $user->role->is_super,
                'permissions' => $user->role->is_super
                    ? ['*']
                    : $user->role->getGrantedPermissionKeys(),
            ] : null,
        ];
    }

    private function formatHospital(Tenant $tenant): array
    {
        return [
            'name' => $tenant->name,
            'slug' => $tenant->slug,
        ];
    }
}

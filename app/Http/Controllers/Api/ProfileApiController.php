<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\EmailRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileApiController extends Controller
{
    /**
     * GET profile — returns authenticated user's full profile.
     *
     * GET v1/{slug}/profile
     */
    public function profileShow(Request $request): JsonResponse
    {
        $user     = $request->user()->loadMissing('role');
        $isDoctor = $user->role?->slug === 'doctor';

        return response()->json([
            'success' => true,
            'data'    => $this->formatProfile($user, $isDoctor),
        ]);
    }

    /**
     * PUT profile — updates name, email, optional password, optional doctor fields.
     * File uploads (signature, profile_photo) are web-only for now.
     *
     * PUT v1/{slug}/profile
     */
    public function profileUpdate(Request $request): JsonResponse
    {
        $user     = $request->user()->loadMissing('role');
        $isDoctor = $user->role?->slug === 'doctor';

        $rules = [
            'name'  => ['required', 'string', 'max:255'],
            'email' => [
                ...EmailRules::required(),
                Rule::unique('hospital_users', 'email')
                    ->where('tenant_id', $user->tenant_id)
                    ->ignore($user->id)
                    ->whereNull('deleted_at'),
            ],
            'current_password'          => ['nullable', 'string', 'required_with:new_password'],
            'new_password'              => ['nullable', 'string', 'min:8', 'confirmed'],
            'new_password_confirmation' => ['nullable', 'string'],
        ];

        if ($isDoctor) {
            $rules['registration_no']  = ['nullable', 'string', 'max:100'];
            $rules['experience_years'] = ['nullable', 'integer', 'min:0', 'max:60'];
        }

        $validated = $request->validate($rules, EmailRules::messages('email'));

        // Verify current password manually before updating (Sanctum doesn't auto-check)
        if ($request->filled('new_password')) {
            if (! Hash::check($request->input('current_password'), $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current password is incorrect.',
                    'errors'  => ['current_password' => ['The current password is incorrect.']],
                ], 422);
            }
        }

        $updateData = [
            'name'  => $validated['name'],
            'email' => $validated['email'],
        ];

        if ($request->filled('new_password')) {
            $updateData['password'] = $validated['new_password'];
        }

        if ($isDoctor) {
            $updateData['registration_no']  = $request->input('registration_no');
            $updateData['experience_years'] = $request->filled('experience_years')
                ? (int) $request->input('experience_years')
                : null;
        }

        $user->update($updateData);

        return response()->json([
            'success' => true,
            'data'    => $this->formatProfile($user->fresh()->loadMissing('role'), $isDoctor),
            'message' => 'Profile updated successfully.',
        ]);
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function formatProfile($user, bool $isDoctor): array
    {
        $data = [
            'id'        => $user->id,
            'name'      => $user->name,
            'email'     => $user->email,
            'contact'   => $user->contact,
            'status'    => $user->status,
            'is_doctor' => $isDoctor,
            'role'      => $user->role ? [
                'id'       => $user->role->id,
                'name'     => $user->role->name,
                'slug'     => $user->role->slug,
                'color'    => $user->role->color ?? '#1B4F72',
                'is_super' => (bool) $user->role->is_super,
            ] : null,
        ];

        if ($isDoctor) {
            $data['doctor_type']      = $user->doctor_type;
            $data['doctor_prefix']    = $user->doctor_prefix;
            $data['foc_permission']   = (bool) $user->foc_permission;
            $data['registration_no']  = $user->registration_no;
            $data['experience_years'] = $user->experience_years;
        }

        return $data;
    }
}

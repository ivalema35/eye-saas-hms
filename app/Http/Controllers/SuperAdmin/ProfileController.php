<?php

/**
 * ProfileController.php
 *
 * PURPOSE: SuperAdmin profile management — name, email, password change.
 *
 * ROUTES:
 *   GET  /superadmin/profile         → show()
 *   PUT  /superadmin/profile         → update()
 *   PUT  /superadmin/profile/password → updatePassword()
 *
 * GUARD: superadmin
 */

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Support\EmailRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(): View
    {
        $admin = auth('superadmin')->user();

        return view('superadmin.profile.index', compact('admin'));
    }

    public function update(Request $request): RedirectResponse
    {
        $admin = auth('superadmin')->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => [
                ...EmailRules::required(150),
                'unique:platform_admins,email,'.$admin->id,
            ],
        ], EmailRules::messages('email'));

        $admin->update($validated);

        return back()->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);

        $admin = auth('superadmin')->user();

        if (! Hash::check($request->current_password, $admin->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $admin->update(['password' => Hash::make($request->password)]);

        return back()->with('success', 'Password changed successfully.');
    }
}

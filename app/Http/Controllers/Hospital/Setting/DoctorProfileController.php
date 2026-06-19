<?php

namespace App\Http\Controllers\Hospital\Setting;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DoctorProfileController extends Controller
{
    public function show(): View
    {
        $slug = request()->route('slug');
        $user = auth('hospital_user')->user();

        return view('hospital.profile.index', compact('slug', 'user'));
    }

    public function update(Request $request): RedirectResponse
    {
        $slug    = $request->route('slug');
        $user    = auth('hospital_user')->user();
        $isDoctor = $user->role?->slug === 'doctor';

        $rules = [
            'name'             => ['required', 'string', 'max:255'],
            'email'            => ['required', 'email', 'max:255'],
            'current_password' => ['nullable', 'string', 'required_with:new_password'],
            'new_password'     => ['nullable', 'string', 'min:8', 'confirmed'],
        ];

        if ($isDoctor) {
            $rules['registration_no']  = ['nullable', 'string', 'max:100'];
            $rules['experience_years'] = ['nullable', 'integer', 'min:0', 'max:60'];
            $rules['signature']        = ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:20'];
            $rules['profile_photo']    = ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:20'];
        }

        $validated = $request->validate($rules);

        $updateData = [
            'name'  => $validated['name'],
            'email' => $validated['email'],
        ];

        if ($isDoctor) {
            $tenantId = (int) config('app.tenant_id');

            $updateData['registration_no']  = $validated['registration_no'] ?? null;
            $updateData['experience_years'] = $validated['experience_years'] ?? null;

            if ($request->hasFile('signature')) {
                if ($user->signature_path && Storage::disk('public')->exists($user->signature_path)) {
                    Storage::disk('public')->delete($user->signature_path);
                }
                $updateData['signature_path'] = $request->file('signature')
                    ->store("tenants/{$tenantId}/staff", 'public');
            }

            if ($request->hasFile('profile_photo')) {
                if ($user->profile_photo_path && Storage::disk('public')->exists($user->profile_photo_path)) {
                    Storage::disk('public')->delete($user->profile_photo_path);
                }
                $updateData['profile_photo_path'] = $request->file('profile_photo')
                    ->store("tenants/{$tenantId}/staff", 'public');
            }
        }

        if ($request->filled('new_password')) {
            if (! Hash::check($request->current_password, $user->password)) {
                return back()
                    ->withErrors(['current_password' => 'The current password is incorrect.'])
                    ->withInput();
            }
            $updateData['password'] = $validated['new_password'];
        }

        $user->update($updateData);

        return redirect()
            ->route('hospital.profile.show', ['slug' => $slug])
            ->with('success', 'Profile updated successfully.');
    }
}

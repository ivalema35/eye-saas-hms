<?php

namespace App\Http\Controllers\Hospital\Setting;

use App\Http\Controllers\Controller;
use App\Models\Hospital\HospitalSetting;
use App\Models\Platform\Tenant;
use App\Services\Auth\RolePermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Hospital Settings Controller
 *
 * Manage hospital-level configuration: name, logo, working hours, etc.
 * URL: hmssaas.com/{slug}/settings
 */
class HospitalSettingController extends Controller
{
    public function __construct(private readonly RolePermissionService $permissionService) {}

    public function index(): View
    {
        $this->authorizePermission('settings.hospital');

        $slug = request()->route('slug');

        // Resolve tenant so defaults can be seeded from registration data
        $tenant = app()->bound('tenant') ? app('tenant') : null;

        $defaults = [
            'hospital_name'     => $tenant?->name ?? '',
            'hospital_email'    => $tenant?->admin_email ?? '',
            'hospital_phone'    => $tenant?->admin_phone ?? '',
            'hospital_address'  => '',
            'hospital_city'     => $tenant?->city ?? '',
            'hospital_district' => $tenant?->district ?? '',
            'hospital_state'    => $tenant?->state ?? '',
            'invoice_prefix'    => 'INV-',
            'tax_percentage'    => '0',
            'print_header_note' => '',
            'print_footer_note' => '',
            'pagination_limit'  => '25',
            'hospital_logo'     => '',
        ];

        // Saved settings override defaults; new hospitals see sensible pre-fills
        $settings = array_merge($defaults, hospital_settings());

        return view('hospital.settings.index', compact('settings', 'slug'));
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizePermission('settings.hospital');

        $slug = $request->route('slug');
        $tenantId = (int) config('app.tenant_id');

        $validated = $request->validate([
            'hospital_name'     => ['required', 'string', 'max:255'],
            'hospital_email'    => ['required', 'email', 'max:255'],
            'hospital_phone'    => ['required', 'string', 'max:20'],
            'hospital_address'  => ['required', 'string'],
            'hospital_city'     => ['nullable', 'string', 'max:100'],
            'hospital_district' => ['nullable', 'string', 'max:100'],
            'hospital_state'    => ['nullable', 'string', 'max:100'],
            'invoice_prefix'    => ['required', 'string', 'max:10'],
            'tax_percentage'    => ['required', 'numeric', 'min:0', 'max:100'],
            'print_header_note' => ['nullable', 'string', 'max:255'],
            'print_footer_note' => ['nullable', 'string', 'max:255'],
            'pagination_limit'  => ['required', 'integer', 'in:10,25,50,100'],
            'logo_sidebar_style'        => ['nullable', 'in:white,original_blur'],
            'logo_processed_base64'     => ['nullable', 'string'],
            'default_dilation_time' => ['nullable', 'integer', 'min:1', 'max:180'],
            'wait_green_max'    => ['nullable', 'integer', 'min:1', 'max:999'],
            'wait_orange_max'   => ['nullable', 'integer', 'min:1', 'max:999'],
            'wait_red_max'      => ['nullable', 'integer', 'min:1', 'max:999'],
            'wait_d_green_max'  => ['nullable', 'integer', 'min:1', 'max:999'],
            'wait_d_orange_max' => ['nullable', 'integer', 'min:1', 'max:999'],
            'wait_d_red_max'    => ['nullable', 'integer', 'min:1', 'max:999'],
            'wait_nd_green_max' => ['nullable', 'integer', 'min:1', 'max:999'],
            'wait_nd_orange_max'=> ['nullable', 'integer', 'min:1', 'max:999'],
            'wait_nd_red_max'   => ['nullable', 'integer', 'min:1', 'max:999'],
            'hospital_logo'     => ['nullable', 'image', 'mimes:jpg,jpeg,png,svg,webp', 'max:2048'],
            'current_password'  => ['nullable', 'string', 'required_with:new_password'],
            'new_password'      => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        DB::transaction(function () use ($request, $validated, $tenantId): void {
            $settingsData = collect($validated)
                ->except(['hospital_logo', 'logo_processed_base64'])
                ->toArray();

            if ($request->hasFile('hospital_logo')) {
                // Delete old originals
                foreach (['hospital_logo', 'hospital_logo_nobg'] as $key) {
                    $old = HospitalSetting::get($key);
                    if ($old && Storage::disk('public')->exists($old)) {
                        Storage::disk('public')->delete($old);
                    }
                }

                // Save original file
                $file     = $request->file('hospital_logo');
                $filename = 'logo_'.time().'.'.$file->getClientOriginalExtension();
                $origPath = $file->storeAs("tenants/{$tenantId}/logo", $filename, 'public');
                $settingsData['hospital_logo'] = $origPath;

                // Save bg-removed version if provided
                if ($request->filled('logo_processed_base64') && str_starts_with($request->logo_processed_base64, 'data:image')) {
                    $base64    = preg_replace('#^data:image/\w+;base64,#i', '', $request->logo_processed_base64);
                    $imageData = base64_decode($base64);
                    $nobgPath  = "tenants/{$tenantId}/logo/logo_nobg_".time().'.png';
                    Storage::disk('public')->put($nobgPath, $imageData);
                    $settingsData['hospital_logo_nobg'] = $nobgPath;
                }
            }

            foreach ($settingsData as $key => $value) {
                HospitalSetting::set($key, $value);
            }

            // Sync city / district / state back to the tenants table
            // so Hospital History page always shows up-to-date location data.
            $tenantRecord = Tenant::find($tenantId);
            if ($tenantRecord) {
                $tenantRecord->update([
                    'city'     => $validated['hospital_city']     ?? $tenantRecord->city,
                    'district' => $validated['hospital_district'] ?? $tenantRecord->district,
                    'state'    => $validated['hospital_state']    ?? $tenantRecord->state,
                ]);
            }
        });

        // Password change — only if new_password is provided
        if ($request->filled('new_password')) {
            $user = auth('hospital_user')->user();
            if (! Hash::check($request->current_password, $user->password)) {
                return back()
                    ->withErrors(['current_password' => 'The current password is incorrect.'])
                    ->withInput();
            }
            $user->password = $request->new_password;
            $user->save();
        }

        return redirect()->route('hospital.settings.index', ['slug' => $slug])
            ->with('success', 'Settings saved successfully.');
    }

    private function authorizePermission(string $permissionKey): void
    {
        abort_unless($this->permissionService->can($permissionKey), 403, 'Access denied.');
    }
}

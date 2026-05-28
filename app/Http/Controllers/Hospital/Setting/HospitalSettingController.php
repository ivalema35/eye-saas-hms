<?php

namespace App\Http\Controllers\Hospital\Setting;

use App\Http\Controllers\Controller;
use App\Models\Hospital\HospitalSetting;
use App\Services\Auth\RolePermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            'hospital_name' => ['required', 'string', 'max:255'],
            'hospital_email' => ['required', 'email', 'max:255'],
            'hospital_phone' => ['required', 'string', 'max:20'],
            'hospital_address' => ['required', 'string'],
            'invoice_prefix' => ['required', 'string', 'max:10'],
            'tax_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'print_header_note' => ['nullable', 'string', 'max:255'],
            'print_footer_note' => ['nullable', 'string', 'max:255'],
            'pagination_limit' => ['required', 'integer', 'in:10,25,50,100'],
            'hospital_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,svg,webp', 'max:2048'],
        ]);

        DB::transaction(function () use ($request, $validated, $tenantId): void {
            $settingsData = collect($validated)
                ->except('hospital_logo')
                ->toArray();

            if ($request->hasFile('hospital_logo')) {
                $existingLogo = HospitalSetting::get('hospital_logo');

                if ($existingLogo && Storage::disk('public')->exists($existingLogo)) {
                    Storage::disk('public')->delete($existingLogo);
                }

                $file = $request->file('hospital_logo');
                $filename = 'logo_'.time().'.'.$file->getClientOriginalExtension();
                $path = $file->storeAs("tenants/{$tenantId}/logo", $filename, 'public');

                $settingsData['hospital_logo'] = $path;
            }

            foreach ($settingsData as $key => $value) {
                HospitalSetting::set($key, $value);
            }
        });

        return redirect()->route('hospital.settings.index', ['slug' => $slug])
            ->with('success', 'Settings updated successfully.');
    }

    private function authorizePermission(string $permissionKey): void
    {
        abort_unless($this->permissionService->can($permissionKey), 403, 'Access denied.');
    }
}

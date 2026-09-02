<?php

namespace App\Http\Controllers\Hospital\Setting;

use App\Http\Controllers\Controller;
use App\Models\Hospital\HospitalSetting;
use App\Models\Platform\MasterCity;
use App\Models\Platform\MasterCountry;
use App\Models\Platform\MasterDistrict;
use App\Models\Platform\MasterState;
use App\Models\Platform\Tenant;
use App\Services\Auth\RolePermissionService;
use App\Support\EmailRules;
use App\Support\PhoneRules;
use App\Support\PublicStorage;
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

        $slug   = request()->route('slug');
        $tenant = app()->bound('tenant') ? app('tenant') : null;

        $defaults = [
            'hospital_name'     => $tenant?->name ?? '',
            'hospital_email'    => $tenant?->admin_email ?? '',
            'hospital_phone'    => $tenant?->admin_phone ?? '',
            'hospital_address'  => '',
            'hospital_country'  => $tenant?->country ?? '',
            'hospital_state'    => $tenant?->state ?? '',
            'hospital_district' => $tenant?->district ?? '',
            'hospital_city'     => $tenant?->city ?? '',
            'hospital_timezone' => $tenant?->timezone ?? 'UTC',
            'invoice_prefix'    => 'INV-',
            'tax_percentage'    => '0',
            'print_header_note' => '',
            'print_footer_note' => '',
            'pagination_limit'  => '25',
            'hospital_logo'     => '',
        ];

        $settings = array_merge($defaults, hospital_settings());

        // All countries for dropdown
        $countries = MasterCountry::active()->orderBy('name')->get();

        // Pre-load cascade: find saved country → load its states
        $selectedCountry = $settings['hospital_country']
            ? MasterCountry::whereRaw('LOWER(name) = ?', [strtolower($settings['hospital_country'])])->first()
            : null;

        $states = $selectedCountry
            ? MasterState::where('country_id', $selectedCountry->id)->active()->orderBy('name')->get()
            : collect();

        // Pre-load cascade: find saved state → load its districts
        $selectedState = ($selectedCountry && $settings['hospital_state'])
            ? MasterState::where('country_id', $selectedCountry->id)
                ->whereRaw('LOWER(name) = ?', [strtolower($settings['hospital_state'])])->first()
            : null;

        $districts = $selectedState
            ? MasterDistrict::where('state_id', $selectedState->id)->active()->orderBy('name')->get()
            : collect();

        // Pre-load cascade: find saved district → load its cities
        $selectedDistrict = ($selectedState && $settings['hospital_district'])
            ? MasterDistrict::where('state_id', $selectedState->id)
                ->whereRaw('LOWER(name) = ?', [strtolower($settings['hospital_district'])])->first()
            : null;

        $cities = $selectedState
            ? MasterCity::where('state_id', $selectedState->id)
                ->when($selectedDistrict, fn ($q) => $q->where('district_id', $selectedDistrict->id))
                ->active()->orderBy('name')->get()
            : collect();

        return view('hospital.settings.index', compact(
            'settings', 'slug',
            'countries', 'states', 'districts', 'cities',
            'selectedCountry', 'selectedState', 'selectedDistrict'
        ));
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizePermission('settings.hospital');

        $slug = $request->route('slug');
        $tenantId = (int) config('app.tenant_id');

        $validated = $request->validate([
            'hospital_name'     => ['required', 'string', 'max:255'],
            'hospital_email'    => EmailRules::required(),
            'hospital_phone'    => PhoneRules::required(),
            'hospital_address'  => ['required', 'string'],
            'hospital_country'  => ['nullable', 'string', 'max:100'],
            'hospital_state'    => ['nullable', 'string', 'max:150'],
            'hospital_district' => ['nullable', 'string', 'max:150'],
            'hospital_city'     => ['nullable', 'string', 'max:150'],
            'hospital_timezone' => ['nullable', 'string', 'timezone'],
            'invoice_prefix'    => ['required', 'string', 'max:10'],
            'tax_percentage'    => ['required', 'numeric', 'min:0', 'max:100'],
            'letter_pad'        => ['nullable', 'in:available,unavailable'],
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
        ], array_merge(EmailRules::messages('hospital_email'), PhoneRules::messages('hospital_phone')));

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
                PublicStorage::mirror($origPath);

                // Save bg-removed version if provided
                if ($request->filled('logo_processed_base64') && str_starts_with($request->logo_processed_base64, 'data:image')) {
                    $base64    = preg_replace('#^data:image/\w+;base64,#i', '', $request->logo_processed_base64);
                    $imageData = base64_decode($base64);
                    $nobgPath  = "tenants/{$tenantId}/logo/logo_nobg_".time().'.png';
                    Storage::disk('public')->put($nobgPath, $imageData);
                    $settingsData['hospital_logo_nobg'] = $nobgPath;
                    PublicStorage::mirror($nobgPath);
                }
            }

            foreach ($settingsData as $key => $value) {
                HospitalSetting::set($key, $value);
            }

                // Sync location + timezone + currency from Super Admin country master
            $tenantRecord = Tenant::find($tenantId);
            if ($tenantRecord) {
                $newTimezone = $validated['hospital_timezone'] ?? null;
                $countryName = $validated['hospital_country'] ?? $tenantRecord->country;
                $master = $countryName
                    ? MasterCountry::whereRaw('LOWER(name) = ?', [strtolower(trim($countryName))])->first()
                    : null;

                $currencyUpdate = [];
                if ($master) {
                    // Always follow country master currency when country is set on settings
                    $currencyUpdate = [
                        'currency_code' => $master->currency_code ?: 'INR',
                        'currency_symbol' => $master->currency_symbol ?: '₹',
                        'is_currency_override' => false,
                    ];
                }

                // Timezone: prefer submitted value; if empty, pull country default
                $resolvedTimezone = $newTimezone
                    ?: ($master?->default_timezone)
                    ?: $tenantRecord->timezone
                    ?: 'UTC';

                $manualTzOverride = $newTimezone
                    && $master
                    && $newTimezone !== $master->default_timezone;

                $tenantRecord->update(array_merge([
                    'name'                 => $validated['hospital_name'],
                    'country'              => $validated['hospital_country']  ?? $tenantRecord->country,
                    'state'                => $validated['hospital_state']    ?? $tenantRecord->state,
                    'district'             => $validated['hospital_district'] ?? $tenantRecord->district,
                    'city'                 => $validated['hospital_city']     ?? $tenantRecord->city,
                    'timezone'             => $resolvedTimezone,
                    'is_timezone_override' => $manualTzOverride,
                ], $currencyUpdate));
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

        return redirect()->route('hospital.dashboard', ['slug' => $slug])
            ->with('success', 'Settings saved successfully.');
    }

    private function authorizePermission(string $permissionKey): void
    {
        abort_unless($this->permissionService->can($permissionKey), 403, 'Access denied.');
    }
}

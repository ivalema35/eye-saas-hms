<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hospital\HospitalSetting;
use App\Models\Platform\MasterCity;
use App\Models\Platform\MasterCountry;
use App\Models\Platform\MasterDistrict;
use App\Models\Platform\MasterState;
use App\Models\Platform\Tenant;
use App\Support\EmailRules;
use App\Support\PhoneRules;
use DateTimeZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class SettingsApiController extends Controller
{
    // ── GET /settings ──────────────────────────────────────────────────────────

    public function settingsShow(): JsonResponse
    {
        $tenant = app()->bound('tenant') ? app('tenant') : null;
        $stored = collect(hospital_settings());

        // Resolve location IDs from saved names so mobile cascade pickers work
        $countryRecord = $stored->get('hospital_country')
            ? MasterCountry::whereRaw('LOWER(name) = ?', [strtolower($stored->get('hospital_country'))])->first()
            : null;

        $stateRecord = ($countryRecord && $stored->get('hospital_state'))
            ? MasterState::where('country_id', $countryRecord->id)
                ->whereRaw('LOWER(name) = ?', [strtolower($stored->get('hospital_state'))])->first()
            : null;

        $districtRecord = ($stateRecord && $stored->get('hospital_district'))
            ? MasterDistrict::where('state_id', $stateRecord->id)
                ->whereRaw('LOWER(name) = ?', [strtolower($stored->get('hospital_district'))])->first()
            : null;

        // Build logo URL from stored path
        $logoPath = $stored->get('hospital_logo', '');
        $logoUrl  = ($logoPath && Storage::disk('public')->exists($logoPath))
            ? Storage::disk('public')->url($logoPath)
            : '';

        $data = [
            // General
            'hospital_name'      => $stored->get('hospital_name', $tenant?->name ?? ''),
            'hospital_email'     => $stored->get('hospital_email', $tenant?->admin_email ?? ''),
            'hospital_phone'     => $stored->get('hospital_phone', $tenant?->admin_phone ?? ''),
            'hospital_address'   => $stored->get('hospital_address', ''),
            // Logo
            'hospital_logo_url'  => $logoUrl,
            // Location (names + resolved IDs for cascade pickers)
            'hospital_country'    => $stored->get('hospital_country', $tenant?->country ?? ''),
            'hospital_country_id' => $countryRecord?->id,
            'hospital_state'      => $stored->get('hospital_state', $tenant?->state ?? ''),
            'hospital_state_id'   => $stateRecord?->id,
            'hospital_district'   => $stored->get('hospital_district', $tenant?->district ?? ''),
            'hospital_district_id'=> $districtRecord?->id,
            'hospital_city'       => $stored->get('hospital_city', $tenant?->city ?? ''),
            'hospital_timezone'   => $stored->get('hospital_timezone', $tenant?->timezone ?? 'UTC'),
            // Billing
            'invoice_prefix'     => $stored->get('invoice_prefix', 'INV-'),
            'tax_percentage'     => (float) $stored->get('tax_percentage', '0'),
            // Print
            'letter_pad'         => $stored->get('letter_pad', 'unavailable'),
            'print_header_note'  => $stored->get('print_header_note', ''),
            'print_footer_note'  => $stored->get('print_footer_note', ''),
            // Pagination
            'pagination_limit'   => (int) $stored->get('pagination_limit', '25'),
            // Queue
            'default_dilation_time' => (int) $stored->get('default_dilation_time', '40'),
            // Wait — R
            'wait_green_max'     => (int) $stored->get('wait_green_max', '30'),
            'wait_orange_max'    => (int) $stored->get('wait_orange_max', '60'),
            'wait_red_max'       => (int) $stored->get('wait_red_max', '120'),
            // Wait — D
            'wait_d_green_max'   => (int) $stored->get('wait_d_green_max', '40'),
            'wait_d_orange_max'  => (int) $stored->get('wait_d_orange_max', '90'),
            'wait_d_red_max'     => (int) $stored->get('wait_d_red_max', '120'),
            // Wait — ND
            'wait_nd_green_max'  => (int) $stored->get('wait_nd_green_max', '20'),
            'wait_nd_orange_max' => (int) $stored->get('wait_nd_orange_max', '60'),
            'wait_nd_red_max'    => (int) $stored->get('wait_nd_red_max', '120'),
        ];

        return response()->json(['success' => true, 'data' => $data]);
    }

    // ── PUT /settings ──────────────────────────────────────────────────────────

    public function settingsUpdate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            // General
            'hospital_name'         => ['required', 'string', 'max:255'],
            'hospital_email'        => EmailRules::required(),
            'hospital_phone'        => PhoneRules::required(),
            'hospital_address'      => ['required', 'string'],
            // Location
            'hospital_country'      => ['nullable', 'string', 'max:100'],
            'hospital_state'        => ['nullable', 'string', 'max:150'],
            'hospital_district'     => ['nullable', 'string', 'max:150'],
            'hospital_city'         => ['nullable', 'string', 'max:150'],
            'hospital_timezone'     => ['nullable', 'string', 'timezone'],
            // Billing
            'invoice_prefix'        => ['required', 'string', 'max:10'],
            'tax_percentage'        => ['required', 'numeric', 'min:0', 'max:100'],
            // Print
            'letter_pad'            => ['nullable', 'in:available,unavailable'],
            'print_header_note'     => ['nullable', 'string', 'max:255'],
            'print_footer_note'     => ['nullable', 'string', 'max:255'],
            // Pagination
            'pagination_limit'      => ['required', 'integer', 'in:10,25,50,100'],
            // Queue
            'default_dilation_time' => ['nullable', 'integer', 'min:1', 'max:180'],
            // Wait — R
            'wait_green_max'        => ['nullable', 'integer', 'min:1', 'max:999'],
            'wait_orange_max'       => ['nullable', 'integer', 'min:1', 'max:999'],
            'wait_red_max'          => ['nullable', 'integer', 'min:1', 'max:999'],
            // Wait — D
            'wait_d_green_max'      => ['nullable', 'integer', 'min:1', 'max:999'],
            'wait_d_orange_max'     => ['nullable', 'integer', 'min:1', 'max:999'],
            'wait_d_red_max'        => ['nullable', 'integer', 'min:1', 'max:999'],
            // Wait — ND
            'wait_nd_green_max'     => ['nullable', 'integer', 'min:1', 'max:999'],
            'wait_nd_orange_max'    => ['nullable', 'integer', 'min:1', 'max:999'],
            'wait_nd_red_max'       => ['nullable', 'integer', 'min:1', 'max:999'],
        ], array_merge(EmailRules::messages('hospital_email'), PhoneRules::messages('hospital_phone')));

        foreach ($validated as $key => $value) {
            if ($value !== null) {
                HospitalSetting::set($key, $value);
            }
        }

        // Sync location + timezone back to tenants table
        $tenantId     = (int) config('app.tenant_id');
        $tenantRecord = Tenant::find($tenantId);
        if ($tenantRecord) {
            $tenantRecord->update([
                'country'  => $validated['hospital_country']  ?? $tenantRecord->country,
                'state'    => $validated['hospital_state']    ?? $tenantRecord->state,
                'district' => $validated['hospital_district'] ?? $tenantRecord->district,
                'city'     => $validated['hospital_city']     ?? $tenantRecord->city,
                'timezone' => $validated['hospital_timezone'] ?? $tenantRecord->timezone,
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Settings saved successfully.']);
    }

    // ── POST /settings/logo ────────────────────────────────────────────────────

    public function logoUpload(Request $request): JsonResponse
    {
        $request->validate([
            'logo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $tenantId = (int) config('app.tenant_id');

        // Delete old logo file
        $oldPath = HospitalSetting::get('hospital_logo');
        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        // Store new logo
        $file     = $request->file('logo');
        $filename = 'logo_' . time() . '.' . $file->getClientOriginalExtension();
        $path     = $file->storeAs("tenants/{$tenantId}/logo", $filename, 'public');

        HospitalSetting::set('hospital_logo', $path);

        return response()->json([
            'success' => true,
            'data'    => ['url' => Storage::disk('public')->url($path)],
            'message' => 'Logo updated successfully.',
        ]);
    }

    // ── POST /settings/change-password ────────────────────────────────────────

    public function changePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password'          => ['required', 'string'],
            'new_password'              => ['required', 'string', 'min:8', 'confirmed'],
            'new_password_confirmation' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'errors'  => ['current_password' => ['Current password is incorrect.']],
            ], 422);
        }

        $user->password = $validated['new_password'];
        $user->save();

        return response()->json(['success' => true, 'message' => 'Password changed successfully.']);
    }

    // ── Location Cascade APIs ─────────────────────────────────────────────────

    public function locationCountries(): JsonResponse
    {
        $countries = MasterCountry::active()->orderBy('name')->get(['id', 'name', 'default_timezone']);

        return response()->json([
            'success' => true,
            'data'    => $countries->map(fn ($c) => [
                'id'               => $c->id,
                'name'             => $c->name,
                'default_timezone' => $c->default_timezone,
            ]),
        ]);
    }

    public function locationStates(Request $request): JsonResponse
    {
        $request->validate(['country_id' => ['required', 'integer']]);

        $states = MasterState::where('country_id', $request->country_id)
            ->active()->orderBy('name')->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'data'    => $states->map(fn ($s) => ['id' => $s->id, 'name' => $s->name]),
        ]);
    }

    public function locationDistricts(Request $request): JsonResponse
    {
        $request->validate(['state_id' => ['required', 'integer']]);

        $districts = MasterDistrict::where('state_id', $request->state_id)
            ->active()->orderBy('name')->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'data'    => $districts->map(fn ($d) => ['id' => $d->id, 'name' => $d->name]),
        ]);
    }

    public function locationCities(Request $request): JsonResponse
    {
        $request->validate(['district_id' => ['required', 'integer']]);

        $cities = MasterCity::where('district_id', $request->district_id)
            ->active()->orderBy('name')->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'data'    => $cities->map(fn ($c) => ['id' => $c->id, 'name' => $c->name]),
        ]);
    }

    // ── GET /masters/location/timezones ───────────────────────────────────────

    public function locationTimezones(): JsonResponse
    {
        $timezones = collect(DateTimeZone::listIdentifiers(DateTimeZone::ALL))
            ->map(function (string $tz) {
                $offset = (new \DateTime('now', new DateTimeZone($tz)))->format('P');
                return [
                    'value'  => $tz,
                    'label'  => str_replace('_', ' ', $tz),
                    'offset' => $offset,
                ];
            })
            ->sortBy('offset')
            ->values();

        return response()->json(['success' => true, 'data' => $timezones]);
    }
}

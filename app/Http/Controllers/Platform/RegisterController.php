<?php

/**
 * RegisterController.php
 *
 * PURPOSE: Naye hospital ka self-service registration handle karta hai.
 *          Tenant create karta hai, welcome email bhejta hai.
 *
 * ROUTES:
 *   GET  /register        → show()
 *   POST /register        → store()
 *   GET  /check-slug      → checkSlug() [AJAX]
 *
 * SERVICE: TenantService::createTenant()
 * ACCESSIBLE BY: Public (no auth)
 */

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Platform\MasterCity;
use App\Models\Platform\MasterDistrict;
use App\Models\Platform\MasterCountry;
use App\Models\Platform\MasterState;
use App\Models\Platform\Tenant;
use App\Services\Platform\TenantService;
use App\Support\EmailRules;
use App\Support\PhoneRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function __construct(
        protected TenantService $tenantService
    ) {
    }

    /**
     * show() — Registration form dikhao
     */
    public function show(): View
    {
        $countries = MasterCountry::active()->orderBy('name')->get(['id', 'name']);
        return view('landing.register', compact('countries'));
    }

    /**
     * store() — Registration form submit process karo
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'hospital_name' => ['required', 'string', 'min:3', 'max:100'],
            'hospital_code' => [
                'required',
                'string',
                'min:3',
                'max:4',
                'regex:/^[A-Za-z]{3,4}$/',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (Tenant::where('hospital_code', strtoupper($value))->exists()) {
                        $fail('This hospital code is already taken by another hospital.');
                    }
                },
            ],
            'slug' => [
                'required',
                'string',
                'min:3',
                'max:30',
                'regex:/^[a-z0-9\-]+$/',
                'unique:tenants,slug',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $reserved = ['superadmin', 'admin', 'register', 'login', 'pricing', 'api', 'health', 'webhook'];
                    if (in_array(strtolower($value), $reserved)) {
                        $fail('This URL slug is reserved and cannot be used.');
                    }
                },
            ],
            'admin_name' => ['required', 'string', 'max:100'],
            'admin_email' => [
                ...EmailRules::required(),
                'unique:tenants,admin_email',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $existsInUsers = DB::table('hospital_users')
                        ->where('email', $value)
                        ->exists();
                    if ($existsInUsers) {
                        $fail('This email address is already registered to a staff account on the platform.');
                    }
                },
            ],
            'admin_phone' => [
                ...PhoneRules::required(),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $existsInTenants = Tenant::where('admin_phone', $value)->exists();
                    if ($existsInTenants) {
                        $fail('This phone number is already registered to another hospital account.');
                    }

                    $existsInUsers = DB::table('hospital_users')
                        ->where('contact', $value)
                        ->exists();
                    if ($existsInUsers) {
                        $fail('This phone number is already registered to a staff account on the platform.');
                    }
                },
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'country' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:150'],
            'plan' => ['required', 'in:monthly,quarterly,yearly'],
            'start_trial' => ['nullable', 'in:1'],
        ], array_merge(EmailRules::messages('admin_email'), PhoneRules::messages('admin_phone')));

        $tenant = $this->tenantService->createTenant($validated);

        return redirect()
            ->route('hospital.login', ['slug' => $tenant->slug])
            ->with('success', 'Hospital registered successfully! Please login to continue.');
    }

    public function getStates(Request $request): JsonResponse
    {
        $states = MasterState::where('country_id', $request->country_id)
            ->active()->orderBy('name')->get(['id', 'name']);
        return response()->json($states);
    }

    public function getDistricts(Request $request): JsonResponse
    {
        $districts = MasterDistrict::where('state_id', $request->state_id)
            ->active()->orderBy('name')->get(['id', 'name']);
        return response()->json($districts);
    }

    public function getCities(Request $request): JsonResponse
    {
        $cities = MasterCity::where('district_id', $request->district_id)
            ->active()->orderBy('name')->get(['id', 'name']);
        return response()->json($cities);
    }

    public function createCountry(Request $request): JsonResponse
    {
        $request->validate(['name' => ['required', 'string', 'min:2', 'max:100']]);
        $name = MasterCountry::normalize($request->name);
        $country = MasterCountry::firstOrCreate(['name' => $name], ['is_active' => true]);
        return response()->json(['id' => $country->id, 'name' => $country->name]);
    }

    public function createState(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'country_id' => ['required', 'integer', 'exists:tbl_master_countries,id'],
        ]);
        $name = MasterState::normalize($request->name);
        $state = MasterState::firstOrCreate(
            ['country_id' => $request->country_id, 'name' => $name],
            ['is_active' => true]
        );
        return response()->json(['id' => $state->id, 'name' => $state->name]);
    }

    public function createDistrict(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'state_id' => ['required', 'integer', 'exists:tbl_master_states,id'],
        ]);
        $name = MasterDistrict::normalize($request->name);
        $district = MasterDistrict::firstOrCreate(
            ['state_id' => $request->state_id, 'name' => $name],
            ['is_active' => true]
        );
        return response()->json(['id' => $district->id, 'name' => $district->name]);
    }

    public function createCity(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:150'],
            'district_id' => ['required', 'integer', 'exists:tbl_master_districts,id'],
            'state_id' => ['required', 'integer', 'exists:tbl_master_states,id'],
        ]);
        $name = MasterCity::normalize($request->name);
        $city = MasterCity::firstOrCreate(
            ['district_id' => $request->district_id, 'state_id' => $request->state_id, 'name' => $name],
            ['is_active' => true]
        );
        return response()->json(['id' => $city->id, 'name' => $city->name]);
    }

    /**
     * checkCode() — AJAX: hospital code availability check karo
     */
    public function checkCode(Request $request): JsonResponse
    {
        $code = strtoupper(preg_replace('/[^a-zA-Z]/', '', $request->input('code', '')));

        if (strlen($code) < 3 || strlen($code) > 4) {
            return response()->json([
                'available' => false,
                'message' => 'Code must be 3 or 4 letters'
            ]);
        }

        $exists = Tenant::where('hospital_code', $code)->exists();

        if ($exists) {
            return response()->json([
                'available' => false,
                'message' => 'This code is already taken by another hospital',
            ]);
        }

        return response()->json(['available' => true, 'code' => $code]);
    }

    /**
     * checkSlug() — AJAX: slug availability check karo
     */
    public function checkSlug(Request $request): JsonResponse
    {
        $slug = strtolower(preg_replace('/[^a-z0-9\-]/', '', $request->get('slug', '')));

        if (strlen($slug) < 3) {
            return response()->json(['available' => false, 'message' => 'Minimum 3 characters']);
        }

        $reserved = ['superadmin', 'admin', 'register', 'login', 'pricing', 'api', 'health', 'webhook'];
        if (in_array($slug, $reserved)) {
            return response()->json(['available' => false, 'message' => 'This slug is reserved']);
        }

        $exists = Tenant::where('slug', $slug)->exists();

        if ($exists) {
            $suggestion = $slug;
            $i = 2;
            while (Tenant::where('slug', $suggestion)->exists()) {
                $suggestion = $slug . $i;
                $i++;
            }

            return response()->json([
                'available' => false,
                'message' => 'This slug is already taken',
                'suggestion' => $suggestion,
            ]);
        }

        return response()->json(['available' => true, 'slug' => $slug]);
    }
}

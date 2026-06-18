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
                'required',
                'email',
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
                'required',
                'string',
                'regex:/^[0-9]{10}$/',
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
        ]);

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

<?php

namespace App\Http\Controllers\Hospital\Setting;

use App\Http\Controllers\Controller;
use App\Models\Hospital\HospitalSetting;
use App\Models\Hospital\HospitalUser;
use App\Models\Platform\Tenant;
use App\Support\EmailRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Hospital Setup Wizard Controller
 *
 * Compulsory on first admin login (is_setup_done = false).
 * Steps: 1=Hospital Profile, 2=Add Doctor, 3=Add Receptionist, 4=Add Cases.
 * Each step is skippable.
 */
class SetupWizardController extends Controller
{
    private const MAX_STEPS = 4;

    public function show(string $slug, int $step): View|RedirectResponse
    {
        $tenant = app('tenant');

        if ($tenant->is_setup_done) {
            return redirect()->route('hospital.dashboard', ['slug' => $slug]);
        }

        $step = max(1, min($step, self::MAX_STEPS));

        $viewData = [
            'slug' => $slug,
            'step' => $step,
            'maxSteps' => self::MAX_STEPS,
            'tenant' => $tenant,
        ];

        // If step 4 (cases), preload existing tenant cases so the wizard shows current masters
        if ($step === 4) {
            $cases = DB::table('tbl_cases')
                ->where('tenant_id', $tenant->id)
                ->whereNull('deleted_at')
                ->orderBy('id')
                ->get()
                ->map(function ($r) {
                    return ['name' => $r->case_type, 'fee' => (string) $r->case_fee];
                })
                ->toArray();

            $viewData['existingCases'] = $cases;
        }

        return view("hospital.setup.step{$step}", $viewData);
    }

    public function store(Request $request, string $slug, int $step): RedirectResponse
    {
        $tenant = app('tenant');

        match ($step) {
            1 => $this->saveProfile($request, $tenant),
            2 => $this->saveDoctor($request, $tenant),
            3 => $this->saveReceptionist($request, $tenant),
            4 => $this->saveCases($request, $tenant),
            default => null,
        };

        // After last step, mark setup done
        if ($step >= self::MAX_STEPS) {
            return $this->finishSetup($tenant, $slug);
        }

        return redirect()->route('hospital.setup.show', ['slug' => $slug, 'step' => $step + 1])
            ->with('success', 'Step ' . $step . ' saved.');
    }

    public function skip(string $slug, int $step): RedirectResponse
    {
        $tenant = app('tenant');

        if ($step >= self::MAX_STEPS) {
            return $this->finishSetup($tenant, $slug);
        }

        return redirect()->route('hospital.setup.show', ['slug' => $slug, 'step' => $step + 1]);
    }

    private function finishSetup(Tenant $tenant, string $slug): RedirectResponse
    {
        $tenant->update([
            'is_setup_done' => true,
            'setup_completed_at' => now(),
        ]);

        return redirect()->route('hospital.dashboard', ['slug' => $slug])
            ->with('success', 'Setup completed! Welcome to your hospital dashboard.');
    }

    private function saveProfile(Request $request, Tenant $tenant): void
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'admin_phone' => ['nullable', 'string', 'max:15'],
            'hospital_address' => ['nullable', 'string', 'max:500'],
        ]);

        $tenant->update([
            'name' => $data['name'],
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'admin_phone' => $data['admin_phone'] ?? null,
        ]);

        HospitalSetting::set('hospital_address', $data['hospital_address'] ?? '');
        HospitalSetting::set('hospital_name', $data['name']);
        HospitalSetting::set('hospital_phone', $data['admin_phone'] ?? $tenant->admin_phone ?? '');
        HospitalSetting::set('hospital_email', $tenant->admin_email ?? '');
    }

    private function saveDoctor(Request $request, Tenant $tenant): void
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                ...EmailRules::required(),
                Rule::unique('hospital_users', 'email')
                    ->where(fn($query) => $query->where('tenant_id', $tenant->id)),
            ],
            'contact' => [
                'nullable',
                'regex:/^\d{10}$/',
                Rule::unique('hospital_users', 'contact')
                    ->where(fn($query) => $query->where('tenant_id', $tenant->id)),
            ],
            'password' => ['required', 'string', 'min:8'],
            // 'doctor_type' => ['nullable', 'in:primary,secondary'],
        ], [
            ...EmailRules::messages('email'),
            'contact.regex' => 'Contact number must be exactly 10 digits.',
            'email.unique' => 'This email is already registered.',
            'contact.unique' => 'This phone number is already registered.',
        ]);

        $doctorRole = DB::table('roles')
            ->where('tenant_id', $tenant->id)
            ->where('slug', 'doctor')
            ->first();

        HospitalUser::create([
            'tenant_id' => $tenant->id,
            'role_id' => $doctorRole?->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'contact' => $data['contact'] ?? null,
            'password' => Hash::make($data['password']),
            // 'doctor_type' => $data['doctor_type'] ?? null,
            'status' => 'active',
        ]);
    }

    private function saveReceptionist(Request $request, Tenant $tenant): void
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                ...EmailRules::required(),
                Rule::unique('hospital_users', 'email')
                    ->where(fn($query) => $query->where('tenant_id', $tenant->id)),
            ],
            'contact' => [
                'nullable',
                'regex:/^\d{10}$/',
                Rule::unique('hospital_users', 'contact')
                    ->where(fn($query) => $query->where('tenant_id', $tenant->id)),
            ],
            'password' => ['required', 'string', 'min:8'],
        ], [
            ...EmailRules::messages('email'),
            'contact.regex' => 'Contact number must be exactly 10 digits.',
            'email.unique' => 'This email is already registered.',
            'contact.unique' => 'This phone number is already registered.',
        ]);

        $receptionRole = DB::table('roles')
            ->where('tenant_id', $tenant->id)
            ->where('slug', 'receptionist')
            ->first();

        HospitalUser::create([
            'tenant_id' => $tenant->id,
            'role_id' => $receptionRole?->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'contact' => $data['contact'] ?? null,
            'password' => Hash::make($data['password']),
            'status' => 'active',
        ]);
    }

    private function saveCases(Request $request, Tenant $tenant): void
    {
        $data = $request->validate([
            'cases' => ['required', 'array', 'min:1'],
            'cases.*.name' => ['required', 'string', 'max:100'],
            'cases.*.fee' => ['required', 'numeric', 'min:0'],
        ]);

        // Replace existing cases for this tenant with the submitted list
        DB::table('tbl_cases')->where('tenant_id', $tenant->id)->delete();

        $rows = [];
        $ts = now();
        foreach ($data['cases'] as $case) {
            $rows[] = [
                'tenant_id' => $tenant->id,
                'case_type' => $case['name'],
                'case_fee' => $case['fee'],
                'created_at' => $ts,
                'updated_at' => $ts,
            ];
        }

        if (!empty($rows)) {
            DB::table('tbl_cases')->insert($rows);
        }
    }
}

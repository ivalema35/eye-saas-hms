<?php

namespace App\Http\Controllers\Hospital\Master;

use App\Http\Controllers\Controller;
use App\Models\Hospital\CaseType;
use App\Models\Hospital\Duration;
use App\Models\Hospital\Location;
use App\Models\Hospital\MasterAdvice;
use App\Models\Hospital\MasterMedicineInstruction;
use App\Models\Hospital\Referrer;
use App\Models\Platform\MasterCity;
use App\Models\Platform\MasterCountry;
use App\Models\Platform\MasterDistrict;
use App\Models\Platform\MasterState;
use App\Services\Auth\RolePermissionService;
use App\Support\PhoneRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BasicMasterController extends Controller
{
    public function __construct(private readonly RolePermissionService $perm) {}

    /**
     * Maps URL {type} slugs to their Eloquent model classes.
     *
     * @return array<string, class-string>
     */
    protected function modelMap(): array
    {
        return [
            'cases' => CaseType::class,
            'durations' => Duration::class,
            'referrers' => Referrer::class,
            'locations' => Location::class,
            'advices' => MasterAdvice::class,
            'instructions' => MasterMedicineInstruction::class,
        ];
    }

    /** @return class-string */
    protected function resolveModel(string $type): string
    {
        $map = $this->modelMap();
        abort_if(! array_key_exists($type, $map), 404, 'Master type not found.');

        return $map[$type];
    }

    public function landing(string $slug): View
    {
        return view('hospital.masters.index', compact('slug'));
    }

    public function index(string $slug, string $type): View
    {
        $modelClass = $this->resolveModel($type);
        $instance = new $modelClass;
        if ($type === 'locations') {

            $user = auth('hospital_user')->user();

            $hospitalCountry = $user->tenant->country;

            $records = MasterCity::with([
                'district',
                'state',
                'state.country',
            ])
                ->whereHas('state.country', function ($q) use ($hospitalCountry) {
                    $q->where('name', $hospitalCountry);
                })
                ->get();

        } else {

            $records = $modelClass::latest()->get();

        }
        $columns = array_values(array_diff($instance->getFillable(), ['tenant_id']));
        $title = Str::headline($type);
        $routeGroup = 'hospital.masters.basic';

        return view(
            'hospital.masters.dynamic_index',
            compact('records', 'columns', 'title', 'type', 'slug', 'routeGroup')
        );
    }

    public function store(Request $request, string $slug, string $type): RedirectResponse
    {
        if ($type === 'locations') {
            return $this->storeLocation($request);
        }

        $modelClass = $this->resolveModel($type);
        $columns = array_values(array_diff((new $modelClass)->getFillable(), ['tenant_id']));
        $rules = [];
        foreach ($columns as $col) {
            $rules[$col] = $col === 'contact' ? PhoneRules::required() : ['required', 'string', 'max:255'];
        }
        $validated = $request->validate($rules);

        $modelClass::create($validated);

        return redirect()->back()->with('success', Str::headline($type).' added successfully.');
    }

    private function storeLocation(Request $request): RedirectResponse
    {
        $request->validate([
            'city' => 'required|string|max:150',
            'state' => 'required|string|max:150',
            'district' => 'nullable|string|max:150',
        ]);

        $user = auth('hospital_user')->user();
        $country = MasterCountry::whereRaw('LOWER(name) = ?', [strtolower($user->tenant->country)])->first();

        if (! $country) {
            return back()->with('error', 'Hospital country is not configured in the global master.')->withInput();
        }

        $stateName = MasterState::normalize($request->state);
        $state = MasterState::where('country_id', $country->id)
            ->whereRaw('LOWER(name) = ?', [strtolower($stateName)])
            ->first()
            ?? MasterState::create(['country_id' => $country->id, 'name' => $stateName, 'is_active' => true]);

        $district = null;
        if ($request->filled('district')) {
            $districtName = MasterDistrict::normalize($request->district);
            $district = MasterDistrict::where('state_id', $state->id)
                ->whereRaw('LOWER(name) = ?', [strtolower($districtName)])
                ->first()
                ?? MasterDistrict::create(['state_id' => $state->id, 'name' => $districtName, 'is_active' => true]);
        }

        $cityName = MasterCity::normalize($request->city);
        $exists = MasterCity::where('state_id', $state->id)
            ->where('district_id', $district?->id)
            ->whereRaw('LOWER(name) = ?', [strtolower($cityName)])
            ->exists();

        if ($exists) {
            return back()->with('error', "City \"{$cityName}\" already exists in this location.")->withInput();
        }

        MasterCity::create([
            'state_id' => $state->id,
            'district_id' => $district?->id,
            'name' => $cityName,
            'is_active' => true,
        ]);

        return back()->with('success', 'Location added successfully.');
    }

    /**
     * AJAX create endpoint. Returns JSON with created record.
     */
    public function ajaxStore(Request $request, string $slug, string $type)
    {
        $user = auth('hospital_user')->user();
        $roleSlug = $user?->role?->slug;

        $allowedByRole = in_array($roleSlug, ['hospital_admin', 'reception', 'receptionist', 'receptionist_opd'], true);
        $allowedByPermission = $this->perm->canAny(['opd.patient.register', 'opd.patient.register_phone', 'master.locations', 'ot.appointment.create', 'ot.appointment.edit']);

        if (! $allowedByRole && ! $allowedByPermission) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        if ($type === 'locations') {
            return $this->ajaxStoreLocation($request, $user);
        }

        $modelClass = $this->resolveModel($type);
        $instance = new $modelClass;
        $columns = array_values(array_diff($instance->getFillable(), ['tenant_id']));

        // Columns that are nullable on their model (optional in AJAX forms)
        $nullableCols = method_exists($instance, 'getNullable')
            ? $instance->getNullable()
            : [];

        $rules = [];
        foreach ($columns as $col) {
            $isNullable = in_array($col, $nullableCols, true);
            $rules[$col] = $isNullable ? ['nullable'] : ['required'];

            if ($col === 'contact') {
                $rules[$col] = $isNullable ? PhoneRules::nullable() : PhoneRules::required();
            } elseif (str_contains($col, 'fee') || str_contains($col, 'percentage')) {
                $rules[$col][] = 'numeric';
            } else {
                $rules[$col][] = 'string';
                $rules[$col][] = 'max:255';
            }
        }

        $validated = $request->validate($rules);

        $record = $modelClass::create($validated);

        return response()->json([
            'success' => true,
            'id' => $record->id,
            'data' => $validated,
        ]);
    }

    private function ajaxStoreLocation(Request $request, $user): JsonResponse
    {
        $validated = $request->validate([
            'city' => 'required|string|max:150',
            'state' => 'required|string|max:150',
            'district' => 'nullable|string|max:150',
        ]);

        $country = MasterCountry::whereRaw('LOWER(name) = ?', [strtolower($user->tenant->country)])->first();

        if (! $country) {
            return response()->json(['success' => false, 'message' => 'Hospital country not configured in master.'], 422);
        }

        // Find or create state
        $stateName = MasterState::normalize($validated['state']);
        $state = MasterState::where('country_id', $country->id)
            ->whereRaw('LOWER(name) = ?', [strtolower($stateName)])
            ->first()
            ?? MasterState::create(['country_id' => $country->id, 'name' => $stateName, 'is_active' => true]);

        // Find or create district (optional)
        $district = null;
        if ($request->filled('district')) {
            $districtName = MasterDistrict::normalize($request->district);
            $district = MasterDistrict::where('state_id', $state->id)
                ->whereRaw('LOWER(name) = ?', [strtolower($districtName)])
                ->first()
                ?? MasterDistrict::create(['state_id' => $state->id, 'name' => $districtName, 'is_active' => true]);
        }

        $cityName = MasterCity::normalize($request->city);
        $existing = MasterCity::where('state_id', $state->id)
            ->where('district_id', $district?->id)
            ->whereRaw('LOWER(name) = ?', [strtolower($cityName)])
            ->first();

        if ($existing) {
            // Return existing city so the dropdown can still select it
            return response()->json([
                'success' => true,
                'id' => $existing->id,
                'data' => ['city' => $existing->name, 'district' => $district?->name, 'state' => $state->name],
            ]);
        }

        $masterCity = MasterCity::create([
            'state_id' => $state->id,
            'district_id' => $district?->id,
            'name' => $cityName,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'id' => $masterCity->id,
            'data' => ['city' => $cityName, 'district' => $district?->name, 'state' => $state->name],
        ]);
    }

    public function update(Request $request, string $slug, string $type, int $id): RedirectResponse
    {
        if ($type === 'locations') {
            return $this->updateLocation($request, $id);
        }

        $modelClass = $this->resolveModel($type);
        $record = $modelClass::findOrFail($id);
        $columns = array_values(array_diff((new $modelClass)->getFillable(), ['tenant_id']));
        $rules = [];
        foreach ($columns as $col) {
            $rules[$col] = $col === 'contact' ? PhoneRules::required() : ['required', 'string', 'max:255'];
        }
        $validated = $request->validate($rules);

        $record->update($validated);

        return redirect()->back()->with('success', Str::headline($type).' updated successfully.');
    }

    private function updateLocation(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'city' => 'required|string|max:150',
            'state' => 'required|string|max:150',
            'district' => 'nullable|string|max:150',
        ]);

        $masterCity = MasterCity::with('state.country')->findOrFail($id);
        $country = $masterCity->state->country;

        $stateName = MasterState::normalize($request->state);
        $state = MasterState::where('country_id', $country->id)
            ->whereRaw('LOWER(name) = ?', [strtolower($stateName)])
            ->first()
            ?? MasterState::create(['country_id' => $country->id, 'name' => $stateName, 'is_active' => true]);

        $district = null;
        if ($request->filled('district')) {
            $districtName = MasterDistrict::normalize($request->district);
            $district = MasterDistrict::where('state_id', $state->id)
                ->whereRaw('LOWER(name) = ?', [strtolower($districtName)])
                ->first()
                ?? MasterDistrict::create(['state_id' => $state->id, 'name' => $districtName, 'is_active' => true]);
        }

        $cityName = MasterCity::normalize($request->city);
        $dup = MasterCity::where('state_id', $state->id)
            ->where('district_id', $district?->id)
            ->whereRaw('LOWER(name) = ?', [strtolower($cityName)])
            ->where('id', '!=', $id)
            ->exists();

        if ($dup) {
            return back()->with('error', "City \"{$cityName}\" already exists in this location.")->withInput();
        }

        $masterCity->update([
            'state_id' => $state->id,
            'district_id' => $district?->id,
            'name' => $cityName,
        ]);

        return back()->with('success', 'Location updated successfully.');
    }

    public function destroy(string $slug, string $type, int $id): RedirectResponse
    {
        if ($type === 'locations') {
            MasterCity::findOrFail($id)->delete();

            return back()->with('success', 'Location deleted successfully.');
        }

        $modelClass = $this->resolveModel($type);
        $modelClass::findOrFail($id)->delete();

        return redirect()->back()->with('success', Str::headline($type).' deleted successfully.');
    }
}

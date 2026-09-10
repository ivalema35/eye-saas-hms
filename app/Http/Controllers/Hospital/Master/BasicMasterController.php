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
use App\Services\Auth\PermissionMatrix;
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
        $basicMasters = collect([
            ['type' => 'cases', 'label' => 'Case Types', 'icon' => 'bi-folder2-open', 'color' => 'primary', 'feature' => 'casetype'],
            ['type' => 'locations', 'label' => 'Locations', 'icon' => 'bi-geo-alt-fill', 'color' => 'success', 'feature' => 'location'],
            ['type' => 'referrers', 'label' => 'Referrers', 'icon' => 'bi-person-lines-fill', 'color' => 'warning', 'feature' => 'referrer'],
            ['type' => 'durations', 'label' => 'Durations', 'icon' => 'bi-hourglass-split', 'color' => 'secondary', 'feature' => 'duration'],
        ])->filter(fn (array $item) => $this->perm->canAny(PermissionMatrix::crudKeys($item['feature'])))->values();

        $otMasters = collect([
            ['route' => 'hospital.masters.ot.lens-options.index', 'label' => 'Lens Options', 'icon' => 'bi-eyeglasses', 'color' => 'dark', 'feature' => 'ot_lens_option'],
            ['route' => 'hospital.masters.ot.lens-powers.index', 'label' => 'Lens Powers', 'icon' => 'bi-rulers', 'color' => 'dark', 'feature' => 'ot_lens_power'],
            ['route' => 'hospital.masters.ot.lens-inventory.index', 'label' => 'Lens Inventory', 'icon' => 'bi-box-seam', 'color' => 'dark', 'feature' => 'ot_inventory'],
            ['route' => 'hospital.masters.ot.slots.index', 'label' => 'OT Slots', 'icon' => 'bi-clock-history', 'color' => 'primary', 'feature' => 'ot_slot'],
            ['route' => 'hospital.masters.ot.types.index', 'label' => 'OT Types', 'icon' => 'bi-tags-fill', 'color' => 'success', 'feature' => 'ot_type'],
            ['route' => 'hospital.masters.ot.surgery-types.index', 'label' => 'Surgery Types', 'icon' => 'bi-scissors', 'color' => 'warning', 'feature' => 'ot_type'],
            ['route' => 'hospital.masters.ot.charge-heads.index', 'label' => 'Charge Heads', 'icon' => 'bi-cash-stack', 'color' => 'info', 'feature' => 'ot_charge'],
            ['route' => 'hospital.masters.ot.packages.index', 'label' => 'OT Packages', 'icon' => 'bi-box2-heart', 'color' => 'primary', 'feature' => 'ot_package_master'],
        ])->filter(fn (array $item) => $this->perm->canAny(PermissionMatrix::crudKeys($item['feature'])))->values();

        $showEyeExamMasters = $this->perm->canAny(PermissionMatrix::crudKeys('eye_exam_master'));

        $eyeExamGroups = [];
        if ($showEyeExamMasters) {
            $eyeExamGroups = [
                [
                    'title' => 'Clinical',
                    'items' => [
                        ['type' => 'chief-complaints', 'label' => 'Chief Complaints', 'icon' => 'bi-clipboard2-pulse', 'color' => 'danger'],
                        ['type' => 'kcos', 'label' => 'K/C/O', 'icon' => 'bi-heart-pulse', 'color' => 'warning'],
                        ['type' => 'hno', 'label' => 'H/O', 'icon' => 'bi-clock-history', 'color' => 'info'],
                        ['type' => 'diagnosis', 'label' => 'Diagnoses', 'icon' => 'bi-patch-check', 'color' => 'success'],
                        ['type' => 'advice', 'label' => 'Advice', 'icon' => 'bi-chat-left-text', 'color' => 'primary'],
                    ],
                ],
                [
                    'title' => 'Vision Values',
                    'items' => [
                        ['type' => 'vn', 'label' => 'V/N', 'icon' => 'bi-eye', 'color' => 'info'],
                        ['type' => 'vngl', 'label' => 'Vn C GL', 'icon' => 'bi-eyeglasses', 'color' => 'primary'],
                        ['type' => 'vnst', 'label' => 'Vn C ST', 'icon' => 'bi-view-list', 'color' => 'primary'],
                        ['type' => 'pnvn', 'label' => 'PH NV/N', 'icon' => 'bi-eye-fill', 'color' => 'info'],
                        ['type' => 'nrvn', 'label' => 'NR V/N', 'icon' => 'bi-binoculars', 'color' => 'info'],
                        ['type' => 'sph_cyl', 'label' => 'SPH / CYL', 'icon' => 'bi-circle-half', 'color' => 'primary'],
                        ['type' => 'axis', 'label' => 'Axis', 'icon' => 'bi-arrows-angle-expand', 'color' => 'secondary'],
                        ['type' => 'nct', 'label' => 'NCT (IOP)', 'icon' => 'bi-activity', 'color' => 'warning'],
                    ],
                ],
                [
                    'title' => 'Anterior Segment (O/E)',
                    'items' => [
                        ['type' => 'sac', 'label' => 'SAC', 'icon' => 'bi-droplet', 'color' => 'info'],
                        ['type' => 'lid', 'label' => 'Lid', 'icon' => 'bi-eye-slash', 'color' => 'secondary'],
                        ['type' => 'conj', 'label' => 'Conjunctiva', 'icon' => 'bi-circle-fill', 'color' => 'danger'],
                        ['type' => 'cornea', 'label' => 'Cornea', 'icon' => 'bi-record-circle', 'color' => 'primary'],
                        ['type' => 'ac', 'label' => 'A/C', 'icon' => 'bi-layers', 'color' => 'info'],
                        ['type' => 'iris', 'label' => 'Iris', 'icon' => 'bi-bullseye', 'color' => 'warning'],
                        ['type' => 'pupil', 'label' => 'Pupil', 'icon' => 'bi-dot', 'color' => 'dark'],
                        ['type' => 'lens', 'label' => 'Lens', 'icon' => 'bi-camera-lens', 'color' => 'success'],
                        ['type' => 'em', 'label' => 'E/M', 'icon' => 'bi-arrows-move', 'color' => 'secondary'],
                        ['type' => 'covertest', 'label' => 'Cover Test', 'icon' => 'bi-shield-check', 'color' => 'success'],
                    ],
                ],
                [
                    'title' => 'Posterior Segment (FUNDUS)',
                    'items' => [
                        ['type' => 'disc', 'label' => 'Disc', 'icon' => 'bi-circle', 'color' => 'secondary'],
                        ['type' => 'fr', 'label' => 'F/R', 'icon' => 'bi-reception-4', 'color' => 'secondary'],
                    ],
                ],
            ];
        }

        return view('hospital.masters.index', [
            'slug' => $slug,
            'basicMasters' => $basicMasters,
            'showBasicMasters' => $basicMasters->isNotEmpty(),
            'otMasters' => $otMasters,
            'showOtMasters' => $otMasters->isNotEmpty(),
            'showEyeExamMasters' => $showEyeExamMasters,
            'eyeExamGroups' => $eyeExamGroups,
        ]);
    }

    private function featureForBasicType(string $type): string
    {
        return match ($type) {
            'cases' => 'casetype',
            'locations' => 'location',
            'referrers' => 'referrer',
            'durations' => 'duration',
            'advices', 'instructions' => 'casetype',
            default => 'casetype',
        };
    }

    private function authorizeBasicAction(string $type, string $action): void
    {
        $feature = $this->featureForBasicType($type);
        abort_unless($this->perm->can("{$feature}_{$action}"), 403, 'Access denied.');
    }

    public function index(string $slug, string $type): View
    {
        $feature = $this->featureForBasicType($type);
        abort_unless(
            $this->perm->canAny([
                "{$feature}_view",
                "{$feature}_add",
                "{$feature}_edit",
                "{$feature}_delete",
            ]),
            403,
            'Access denied.'
        );

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
        $feature = $this->featureForBasicType($type);
        $canAdd = $this->perm->can("{$feature}_add");
        $canEdit = $this->perm->can("{$feature}_edit");
        $canDelete = $this->perm->can("{$feature}_delete");
        $canWrite = $canAdd || $canEdit || $canDelete;

        return view(
            'hospital.masters.dynamic_index',
            compact('records', 'columns', 'title', 'type', 'slug', 'routeGroup', 'canWrite', 'canAdd', 'canEdit', 'canDelete')
        );
    }

    public function store(Request $request, string $slug, string $type): RedirectResponse
    {
        $this->authorizeBasicAction($type, 'add');

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
        $allowedByPermission = $this->perm->canAny(['patient_register', 'patient_register_phone', 'location_manage', 'ot_appointment_create', 'ot_appointment_edit']);

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
        $this->authorizeBasicAction($type, 'edit');

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
        $this->authorizeBasicAction($type, 'delete');

        if ($type === 'locations') {
            MasterCity::findOrFail($id)->delete();

            return back()->with('success', 'Location deleted successfully.');
        }

        $modelClass = $this->resolveModel($type);
        $modelClass::findOrFail($id)->delete();

        return redirect()->back()->with('success', Str::headline($type).' deleted successfully.');
    }
}

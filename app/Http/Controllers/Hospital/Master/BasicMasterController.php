<?php

namespace App\Http\Controllers\Hospital\Master;

use App\Http\Controllers\Controller;
use App\Models\Hospital\CaseType;
use App\Models\Hospital\Duration;
use App\Models\Hospital\Location;
use App\Models\Hospital\MasterAdvice;
use App\Models\Hospital\MasterMedicineInstruction;
use App\Models\Hospital\Referrer;
use App\Services\Auth\RolePermissionService;
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
        $records = $modelClass::latest()->get();
        $columns = array_values(array_diff($instance->getFillable(), ['tenant_id']));
        $title = Str::headline($type);
        $routeGroup = 'hospital.masters.basic';

        return view('hospital.masters.dynamic_index',
            compact('records', 'columns', 'title', 'type', 'slug', 'routeGroup'));
    }

    public function store(Request $request, string $slug, string $type): RedirectResponse
    {
        $modelClass = $this->resolveModel($type);
        $columns = array_values(array_diff((new $modelClass)->getFillable(), ['tenant_id']));
        $validated = $request->validate(array_fill_keys($columns, ['required', 'string', 'max:255']));

        $modelClass::create($validated);

        return redirect()->back()->with('success', Str::headline($type).' added successfully.');
    }

    /**
     * AJAX create endpoint. Returns JSON with created record.
     */
    public function ajaxStore(Request $request, string $slug, string $type)
    {
        $user = auth('hospital_user')->user();
        $roleSlug = $user?->role?->slug;

        $allowedByRole = in_array($roleSlug, ['hospital_admin', 'reception', 'receptionist', 'receptionist_opd'], true);
        $allowedByPermission = $this->perm->canAny(['opd.patient.register', 'opd.patient.register_phone', 'master.locations']);

        if (! $allowedByRole && ! $allowedByPermission) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $modelClass = $this->resolveModel($type);
        $instance   = new $modelClass;
        $columns    = array_values(array_diff($instance->getFillable(), ['tenant_id']));

        // Columns that are nullable on their model (optional in AJAX forms)
        $nullableCols = method_exists($instance, 'getNullable')
            ? $instance->getNullable()
            : [];

        // Locations: district + state are optional
        if ($type === 'locations') {
            $nullableCols = array_merge($nullableCols, ['district', 'state']);
        }

        $rules = [];
        foreach ($columns as $col) {
            $isNullable = in_array($col, $nullableCols, true);
            $rules[$col] = $isNullable ? ['nullable'] : ['required'];

            if (str_contains($col, 'fee') || str_contains($col, 'percentage')) {
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

    public function update(Request $request, string $slug, string $type, int $id): RedirectResponse
    {
        $modelClass = $this->resolveModel($type);
        $record = $modelClass::findOrFail($id);
        $columns = array_values(array_diff((new $modelClass)->getFillable(), ['tenant_id']));
        $validated = $request->validate(array_fill_keys($columns, ['required', 'string', 'max:255']));

        $record->update($validated);

        return redirect()->back()->with('success', Str::headline($type).' updated successfully.');
    }

    public function destroy(string $slug, string $type, int $id): RedirectResponse
    {
        $modelClass = $this->resolveModel($type);
        $modelClass::findOrFail($id)->delete();

        return redirect()->back()->with('success', Str::headline($type).' deleted successfully.');
    }
}

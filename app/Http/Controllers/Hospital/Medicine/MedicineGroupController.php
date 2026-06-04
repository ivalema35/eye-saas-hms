<?php

namespace App\Http\Controllers\Hospital\Medicine;

use App\Http\Controllers\Controller;
use App\Models\Hospital\Dosage;
use App\Models\Hospital\MasterMedicineInstruction;
use App\Models\Hospital\Medicine;
use App\Models\Hospital\MedicineGroup;
use App\Models\Hospital\MedicineGroupItem;
use App\Models\Hospital\MedicineRoute;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MedicineGroupController extends Controller
{
    public function index(string $slug): View
    {
        $groups = MedicineGroup::with(['items'])
            ->withCount('items')
            ->latest()
            ->paginate((int) config('app.pagination_limit', 25));
        $medicines    = Medicine::orderBy('name')->get();
        $dosages      = Dosage::orderBy('dosage')->get();
        $routes       = MedicineRoute::orderBy('name')->get();
        $instructions = MasterMedicineInstruction::where('tenant_id', app('tenant')->id)->get();

        return view('hospital.medicine_groups.index', compact('slug', 'groups', 'medicines', 'dosages', 'routes', 'instructions'));
    }

    public function create(string $slug): View
    {
        $medicines    = Medicine::orderBy('name')->get();
        $dosages      = Dosage::orderBy('dosage')->get();
        $routes       = MedicineRoute::orderBy('name')->get();
        $instructions = MasterMedicineInstruction::where('tenant_id', app('tenant')->id)->get();

        return view('hospital.medicine_groups.create', compact('slug', 'medicines', 'dosages', 'routes', 'instructions'));
    }

    public function store(Request $request, string $slug): RedirectResponse
    {
        $request->validate([
            'name'                   => ['required', 'string', 'max:255'],
            'group_code'             => ['nullable', 'string', 'max:50'],
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.medicine_id'    => ['required', 'exists:medicines,id'],
            'items.*.dosage_id'      => ['nullable', 'exists:dosages,id'],
            'items.*.route_id'       => ['nullable', 'exists:medicine_routes,id'],
            'items.*.frequency'      => ['nullable', 'string', 'max:50'],
            'items.*.duration'       => ['nullable', 'string', 'max:100'],
            'items.*.instructions'   => ['nullable', 'string', 'max:255'],
            'items.*.quantity'       => ['required', 'integer', 'min:1'],
        ]);

        DB::transaction(function () use ($request) {
            $group = MedicineGroup::create([
                'name'       => $request->name,
                'group_code' => $request->group_code ?? null,
            ]);

            $tenantId = config('app.tenant_id');
            $now = now();

            $items = collect($request->items)->map(fn ($item) => [
                'tenant_id' => $tenantId,
                'medicine_group_id' => $group->id,
                'medicine_id' => $item['medicine_id'],
                'dosage_id'    => $item['dosage_id'] ?: null,
                'route_id'     => $item['route_id'] ?: null,
                'frequency'    => $item['frequency'] ?? null,
                'duration'     => $item['duration'] ?? null,
                'instructions' => $item['instructions'] ?? null,
                'quantity'     => $item['quantity'],
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

            MedicineGroupItem::insert($items);
        });

        return redirect()->route('hospital.medicine-groups.index', compact('slug'))
            ->with('success', 'Medicine Group created successfully.');
    }

    public function show(string $slug, int $id): View
    {
        $group = MedicineGroup::with(['items.medicine', 'items.dosage', 'items.route'])->findOrFail($id);
        $medicines    = Medicine::orderBy('name')->get();
        $dosages      = Dosage::orderBy('dosage')->get();
        $routes       = MedicineRoute::orderBy('name')->get();
        $instructions = MasterMedicineInstruction::where('tenant_id', app('tenant')->id)->get();

        return view('hospital.medicine_groups.show', compact('slug', 'group', 'medicines', 'dosages', 'routes', 'instructions'));
    }

    public function edit(string $slug, int $id): View
    {
        $group = MedicineGroup::with('items')->findOrFail($id);
        $medicines    = Medicine::orderBy('name')->get();
        $dosages      = Dosage::orderBy('dosage')->get();
        $routes       = MedicineRoute::orderBy('name')->get();
        $instructions = MasterMedicineInstruction::where('tenant_id', app('tenant')->id)->get();

        return view('hospital.medicine_groups.edit', compact('slug', 'group', 'medicines', 'dosages', 'routes', 'instructions'));
    }

    public function update(Request $request, string $slug, int $id): RedirectResponse
    {
        $group = MedicineGroup::findOrFail($id);

        $request->validate([
            'name'                   => ['required', 'string', 'max:255'],
            'group_code'             => ['nullable', 'string', 'max:50'],
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.medicine_id'    => ['required', 'exists:medicines,id'],
            'items.*.dosage_id'      => ['nullable', 'exists:dosages,id'],
            'items.*.route_id'       => ['nullable', 'exists:medicine_routes,id'],
            'items.*.frequency'      => ['nullable', 'string', 'max:50'],
            'items.*.duration'       => ['nullable', 'string', 'max:100'],
            'items.*.instructions'   => ['nullable', 'string', 'max:255'],
            'items.*.quantity'       => ['required', 'integer', 'min:1'],
        ]);

        DB::transaction(function () use ($request, $group) {
            $group->update([
                'name'       => $request->name,
                'group_code' => $request->group_code ?? null,
            ]);

            $group->items()->delete();

            $tenantId = config('app.tenant_id');
            $now = now();

            $items = collect($request->items)->map(fn ($item) => [
                'tenant_id' => $tenantId,
                'medicine_group_id' => $group->id,
                'medicine_id' => $item['medicine_id'],
                'dosage_id'    => $item['dosage_id'] ?: null,
                'route_id'     => $item['route_id'] ?: null,
                'frequency'    => $item['frequency'] ?? null,
                'duration'     => $item['duration'] ?? null,
                'instructions' => $item['instructions'] ?? null,
                'quantity'     => $item['quantity'],
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

            MedicineGroupItem::insert($items);
        });

        return redirect()->route('hospital.medicine-groups.index', compact('slug'))
            ->with('success', 'Medicine Group updated successfully.');
    }

    public function destroy(string $slug, int $id): RedirectResponse
    {
        MedicineGroup::findOrFail($id)->delete();

        return redirect()->route('hospital.medicine-groups.index', compact('slug'))
            ->with('success', 'Medicine Group deleted successfully.');
    }
}

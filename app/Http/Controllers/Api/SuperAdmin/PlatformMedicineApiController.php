<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Hospital\Dosage;
use App\Models\Hospital\MedicineCategory;
use App\Models\Hospital\MedicineRoute;
use App\Models\Hospital\MedicineType;
use App\Models\Platform\MasterDosage;
use App\Models\Platform\MasterMedicine;
use App\Models\Platform\MasterMedicineCategory;
use App\Models\Platform\MasterMedicineRoute;
use App\Models\Platform\MasterMedicineType;
use App\Models\Platform\Tenant;
use App\Services\Platform\MedicineTenantSync;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlatformMedicineApiController extends Controller
{
    // ── Form data for medicine create/edit ────────────────────────────────────

    public function formData(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => [
            'types'   => MasterMedicineType::orderBy('name')->get(['id', 'name']),
            'dosages' => MasterDosage::orderBy('dosage')->get(['id', 'dosage']),
        ]]);
    }

    // ── Dosages ───────────────────────────────────────────────────────────────

    public function dosages(Request $request): JsonResponse
    {
        $q = MasterDosage::query();
        if ($s = $request->search) $q->where('dosage', 'like', "%{$s}%");
        $page = $q->orderBy('dosage')->paginate(25);
        return $this->paginatedResponse($page);
    }

    public function storeDosage(Request $request): JsonResponse
    {
        $request->validate(['dosage' => 'required|string|max:100']);
        $value = trim($request->dosage);

        if (MasterDosage::whereRaw('LOWER(dosage) = ?', [strtolower($value)])->exists()) {
            return response()->json(['success' => false, 'message' => "Dosage \"{$value}\" already exists."], 422);
        }

        MasterDosage::create(['dosage' => $value, 'is_active' => true]);
        $this->cascadeCreateDosage($value);

        return response()->json(['success' => true, 'message' => "Dosage \"{$value}\" added."]);
    }

    public function updateDosage(Request $request, int $id): JsonResponse
    {
        $dosage = MasterDosage::findOrFail($id);
        $request->validate(['dosage' => 'required|string|max:100']);
        $value = trim($request->dosage);

        if (MasterDosage::whereRaw('LOWER(dosage) = ?', [strtolower($value)])->where('id', '!=', $id)->exists()) {
            return response()->json(['success' => false, 'message' => "Dosage \"{$value}\" already exists."], 422);
        }

        $old = $dosage->dosage;
        $dosage->update(['dosage' => $value]);
        $this->cascadeRenameDosage($old, $value);

        return response()->json(['success' => true, 'message' => 'Dosage updated.']);
    }

    public function destroyDosage(int $id): JsonResponse
    {
        MasterDosage::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Dosage deleted.']);
    }

    public function toggleDosage(int $id): JsonResponse
    {
        $item = MasterDosage::findOrFail($id);
        $item->update(['is_active' => !$item->is_active]);
        return response()->json(['success' => true, 'is_active' => $item->is_active]);
    }

    // ── Medicine Types ────────────────────────────────────────────────────────

    public function types(Request $request): JsonResponse
    {
        $q = MasterMedicineType::query();
        if ($s = $request->search) $q->where('name', 'like', "%{$s}%");
        $page = $q->orderBy('name')->paginate(25);
        return $this->paginatedResponse($page);
    }

    public function storeType(Request $request): JsonResponse
    {
        $request->validate(['name' => 'required|string|max:150']);
        $name = trim($request->name);

        if (MasterMedicineType::whereRaw('LOWER(name) = ?', [strtolower($name)])->exists()) {
            return response()->json(['success' => false, 'message' => "Type \"{$name}\" already exists."], 422);
        }

        MasterMedicineType::create(['name' => $name, 'is_active' => true]);
        $this->cascadeCreateNamed(MedicineType::class, $name);

        return response()->json(['success' => true, 'message' => "Type \"{$name}\" added."]);
    }

    public function updateType(Request $request, int $id): JsonResponse
    {
        $type = MasterMedicineType::findOrFail($id);
        $request->validate(['name' => 'required|string|max:150']);
        $name = trim($request->name);

        if (MasterMedicineType::whereRaw('LOWER(name) = ?', [strtolower($name)])->where('id', '!=', $id)->exists()) {
            return response()->json(['success' => false, 'message' => "Type \"{$name}\" already exists."], 422);
        }

        $old = $type->name;
        $type->update(['name' => $name]);
        $this->cascadeRenameNamed(MedicineType::class, $old, $name);

        return response()->json(['success' => true, 'message' => 'Type updated.']);
    }

    public function destroyType(int $id): JsonResponse
    {
        MasterMedicineType::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Type deleted.']);
    }

    public function toggleType(int $id): JsonResponse
    {
        $item = MasterMedicineType::findOrFail($id);
        $item->update(['is_active' => !$item->is_active]);
        return response()->json(['success' => true, 'is_active' => $item->is_active]);
    }

    // ── Medicine Categories ───────────────────────────────────────────────────

    public function categories(Request $request): JsonResponse
    {
        $q = MasterMedicineCategory::query();
        if ($s = $request->search) $q->where('name', 'like', "%{$s}%");
        $page = $q->orderBy('name')->paginate(25);
        return $this->paginatedResponse($page);
    }

    public function storeCategory(Request $request): JsonResponse
    {
        $request->validate(['name' => 'required|string|max:150']);
        $name = trim($request->name);

        if (MasterMedicineCategory::whereRaw('LOWER(name) = ?', [strtolower($name)])->exists()) {
            return response()->json(['success' => false, 'message' => "Category \"{$name}\" already exists."], 422);
        }

        MasterMedicineCategory::create(['name' => $name, 'is_active' => true]);
        $this->cascadeCreateNamed(MedicineCategory::class, $name);

        return response()->json(['success' => true, 'message' => "Category \"{$name}\" added."]);
    }

    public function updateCategory(Request $request, int $id): JsonResponse
    {
        $cat = MasterMedicineCategory::findOrFail($id);
        $request->validate(['name' => 'required|string|max:150']);
        $name = trim($request->name);

        if (MasterMedicineCategory::whereRaw('LOWER(name) = ?', [strtolower($name)])->where('id', '!=', $id)->exists()) {
            return response()->json(['success' => false, 'message' => "Category \"{$name}\" already exists."], 422);
        }

        $old = $cat->name;
        $cat->update(['name' => $name]);
        $this->cascadeRenameNamed(MedicineCategory::class, $old, $name);

        return response()->json(['success' => true, 'message' => 'Category updated.']);
    }

    public function destroyCategory(int $id): JsonResponse
    {
        MasterMedicineCategory::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Category deleted.']);
    }

    public function toggleCategory(int $id): JsonResponse
    {
        $item = MasterMedicineCategory::findOrFail($id);
        $item->update(['is_active' => !$item->is_active]);
        return response()->json(['success' => true, 'is_active' => $item->is_active]);
    }

    // ── Medicine Routes ───────────────────────────────────────────────────────

    public function routes(Request $request): JsonResponse
    {
        $q = MasterMedicineRoute::query();
        if ($s = $request->search) $q->where('name', 'like', "%{$s}%");
        $page = $q->orderBy('name')->paginate(25);
        return $this->paginatedResponse($page);
    }

    public function storeRoute(Request $request): JsonResponse
    {
        $request->validate(['name' => 'required|string|max:150']);
        $name = trim($request->name);

        if (MasterMedicineRoute::whereRaw('LOWER(name) = ?', [strtolower($name)])->exists()) {
            return response()->json(['success' => false, 'message' => "Route \"{$name}\" already exists."], 422);
        }

        MasterMedicineRoute::create(['name' => $name, 'is_active' => true]);
        $this->cascadeCreateNamed(MedicineRoute::class, $name);

        return response()->json(['success' => true, 'message' => "Route \"{$name}\" added."]);
    }

    public function updateRoute(Request $request, int $id): JsonResponse
    {
        $route = MasterMedicineRoute::findOrFail($id);
        $request->validate(['name' => 'required|string|max:150']);
        $name = trim($request->name);

        if (MasterMedicineRoute::whereRaw('LOWER(name) = ?', [strtolower($name)])->where('id', '!=', $id)->exists()) {
            return response()->json(['success' => false, 'message' => "Route \"{$name}\" already exists."], 422);
        }

        $old = $route->name;
        $route->update(['name' => $name]);
        $this->cascadeRenameNamed(MedicineRoute::class, $old, $name);

        return response()->json(['success' => true, 'message' => 'Route updated.']);
    }

    public function destroyRoute(int $id): JsonResponse
    {
        MasterMedicineRoute::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Route deleted.']);
    }

    public function toggleRoute(int $id): JsonResponse
    {
        $item = MasterMedicineRoute::findOrFail($id);
        $item->update(['is_active' => !$item->is_active]);
        return response()->json(['success' => true, 'is_active' => $item->is_active]);
    }

    // ── Medicines ─────────────────────────────────────────────────────────────

    public function medicines(Request $request): JsonResponse
    {
        $q = MasterMedicine::with('medicineType:id,name', 'dosage:id,dosage');
        if ($s = $request->search)  $q->where('name', 'like', "%{$s}%");
        if ($t = $request->type_id) $q->where('master_medicine_type_id', $t);
        $page = $q->latest()->paginate(25);

        return response()->json(['success' => true, 'data' => [
            'items'     => $page->map(fn ($m) => [
                'id'           => $m->id,
                'name'         => $m->name,
                'type_id'      => $m->master_medicine_type_id,
                'type_name'    => $m->medicineType?->name,
                'dosage_id'    => $m->master_dosage_id,
                'dosage_name'  => $m->dosage?->dosage,
                'duration'     => $m->duration,
                'qty'          => $m->qty,
                'composition'  => $m->composition,
                'company'      => $m->company,
                'price'        => $m->price ? (float) $m->price : null,
                'is_active'    => $m->is_active,
            ]),
            'total'     => $page->total(),
            'last_page' => $page->lastPage(),
        ]]);
    }

    public function storeMedicine(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'master_medicine_type_id' => ['required', 'exists:tbl_master_medicine_types,id'],
            'master_dosage_id'        => ['required', 'exists:tbl_master_dosages,id'],
            'name'                    => ['required', 'string', 'max:255'],
            'duration'                => ['nullable', 'string', 'max:100'],
            'qty'                     => ['nullable', 'string', 'max:50'],
            'composition'             => ['nullable', 'string'],
            'company'                 => ['nullable', 'string', 'max:255'],
            'price'                   => ['nullable', 'numeric', 'min:0'],
        ]);

        $medicine = MasterMedicine::create($validated + ['is_active' => true]);
        MedicineTenantSync::pushCreate($medicine);

        return response()->json(['success' => true, 'message' => "Medicine \"{$validated['name']}\" added."]);
    }

    public function updateMedicine(Request $request, int $id): JsonResponse
    {
        $medicine = MasterMedicine::findOrFail($id);
        $validated = $request->validate([
            'master_medicine_type_id' => ['required', 'exists:tbl_master_medicine_types,id'],
            'master_dosage_id'        => ['required', 'exists:tbl_master_dosages,id'],
            'name'                    => ['required', 'string', 'max:255'],
            'duration'                => ['nullable', 'string', 'max:100'],
            'qty'                     => ['nullable', 'string', 'max:50'],
            'composition'             => ['nullable', 'string'],
            'company'                 => ['nullable', 'string', 'max:255'],
            'price'                   => ['nullable', 'numeric', 'min:0'],
        ]);

        $oldName = $medicine->name;
        $medicine->update($validated);
        MedicineTenantSync::pushUpdate($medicine->fresh(['medicineType', 'dosage']), $oldName);

        return response()->json(['success' => true, 'message' => 'Medicine updated.']);
    }

    public function destroyMedicine(int $id): JsonResponse
    {
        MasterMedicine::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Medicine deleted.']);
    }

    public function toggleMedicine(int $id): JsonResponse
    {
        $item = MasterMedicine::findOrFail($id);
        $item->update(['is_active' => !$item->is_active]);
        return response()->json(['success' => true, 'is_active' => $item->is_active]);
    }

    // ── Private: cascade helpers ──────────────────────────────────────────────

    private function paginatedResponse($page): JsonResponse
    {
        return response()->json(['success' => true, 'data' => [
            'items'     => $page->items(),
            'total'     => $page->total(),
            'last_page' => $page->lastPage(),
        ]]);
    }

    private function cascadeCreateDosage(string $value): void
    {
        foreach (Tenant::query()->pluck('id') as $tenantId) {
            Dosage::withoutTenantScope()->firstOrCreate(['tenant_id' => $tenantId, 'dosage' => $value]);
        }
    }

    private function cascadeRenameDosage(string $old, string $new): void
    {
        foreach (Tenant::query()->pluck('id') as $tenantId) {
            $existing = Dosage::withoutTenantScope()
                ->where('tenant_id', $tenantId)->whereRaw('LOWER(dosage) = ?', [strtolower($old)])->first();
            if (! $existing) continue;
            $conflict = Dosage::withoutTenantScope()
                ->where('tenant_id', $tenantId)->whereRaw('LOWER(dosage) = ?', [strtolower($new)])
                ->where('id', '!=', $existing->id)->exists();
            if (! $conflict) $existing->update(['dosage' => $new]);
        }
    }

    private function cascadeCreateNamed(string $modelClass, string $name): void
    {
        foreach (Tenant::query()->pluck('id') as $tenantId) {
            $existing = $modelClass::withoutTenantScope()->withTrashed()
                ->where('tenant_id', $tenantId)->whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
            if ($existing) { if ($existing->trashed()) $existing->restore(); continue; }
            $modelClass::create(['tenant_id' => $tenantId, 'name' => $name]);
        }
    }

    private function cascadeRenameNamed(string $modelClass, string $old, string $new): void
    {
        foreach (Tenant::query()->pluck('id') as $tenantId) {
            $existing = $modelClass::withoutTenantScope()->withTrashed()
                ->where('tenant_id', $tenantId)->whereRaw('LOWER(name) = ?', [strtolower($old)])->first();
            if (! $existing) continue;
            $conflict = $modelClass::withoutTenantScope()->withTrashed()
                ->where('tenant_id', $tenantId)->whereRaw('LOWER(name) = ?', [strtolower($new)])
                ->where('id', '!=', $existing->id)->exists();
            if (! $conflict) $existing->update(['name' => $new]);
        }
    }
}

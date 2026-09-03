<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Exports\MedicineMasterSampleExport;
use App\Http\Controllers\Controller;
use App\Imports\MedicineMasterImport;
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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class MedicineMasterController extends Controller
{
    // ═══════════════════════════════════════════════════
    // INDEX — 5 tabs on one page
    // ═══════════════════════════════════════════════════

    public function index(): View
    {
        $tab = request('tab', 'dosages');

        $dosages = collect();
        $types = collect();
        $categories = collect();
        $routes = collect();
        $medicines = collect();

        if ($tab === 'dosages') {
            $dosages = MasterDosage::orderBy('dosage')->get();
        }

        if ($tab === 'types') {
            $types = MasterMedicineType::orderBy('name')->get();
        }

        if ($tab === 'categories') {
            $categories = MasterMedicineCategory::orderBy('name')->get();
        }

        if ($tab === 'routes') {
            $routes = MasterMedicineRoute::orderBy('name')->get();
        }

        if ($tab === 'medicines') {
            $medicines = MasterMedicine::with('medicineType', 'dosage')->latest()->get();
        }

        $allTypes = MasterMedicineType::orderBy('name')->get();
        $allDosages = MasterDosage::orderBy('dosage')->get();
        $tenants = Tenant::loginable();

        return view('superadmin.medicine_master.index', compact(
            'tab',
            'dosages',
            'types',
            'categories',
            'routes',
            'medicines',
            'allTypes',
            'allDosages',
            'tenants',
        ));
    }

    // ═══════════════════════════════════════════════════
    // DOSAGE CRUD
    // ═══════════════════════════════════════════════════

    public function storeDosage(Request $request): RedirectResponse
    {
        $request->validate(['dosage' => 'required|string|max:100']);
        $value = trim($request->dosage);

        if (MasterDosage::whereRaw('LOWER(dosage) = ?', [strtolower($value)])->exists()) {
            return back()->with('error', "Dosage \"{$value}\" already exists.")->withInput();
        }

        MasterDosage::create(['dosage' => $value, 'is_active' => true]);
        $this->cascadeCreateDosage($value);

        return back()->with('success', "Dosage \"{$value}\" added.")->with('open_tab', 'dosages');
    }

    public function updateDosage(Request $request, MasterDosage $dosage): RedirectResponse
    {
        $request->validate(['dosage' => 'required|string|max:100']);
        $value = trim($request->dosage);

        $dup = MasterDosage::whereRaw('LOWER(dosage) = ?', [strtolower($value)])
            ->where('id', '!=', $dosage->id)->exists();

        if ($dup) {
            return back()->with('error', "Dosage \"{$value}\" already exists.")->withInput();
        }

        $oldValue = $dosage->dosage;
        $dosage->update(['dosage' => $value]);
        $this->cascadeRenameDosage($oldValue, $value);

        return back()->with('success', 'Dosage updated.')->with('open_tab', 'dosages');
    }

    public function destroyDosage(MasterDosage $dosage): RedirectResponse
    {
        $dosage->delete();
        return back()->with('success', 'Dosage deleted.')->with('open_tab', 'dosages');
    }

    public function toggleDosage(MasterDosage $dosage): JsonResponse
    {
        $dosage->update(['is_active' => !$dosage->is_active]);
        return response()->json(['is_active' => $dosage->is_active]);
    }

    // ═══════════════════════════════════════════════════
    // MEDICINE TYPE CRUD
    // ═══════════════════════════════════════════════════

    public function storeType(Request $request): RedirectResponse
    {
        $request->validate(['name' => 'required|string|max:150']);
        $name = trim($request->name);

        if (MasterMedicineType::whereRaw('LOWER(name) = ?', [strtolower($name)])->exists()) {
            return back()->with('error', "Medicine type \"{$name}\" already exists.")->withInput();
        }

        MasterMedicineType::create(['name' => $name, 'is_active' => true]);
        $this->cascadeCreateNamedMaster(MedicineType::class, $name);

        return back()->with('success', "Medicine type \"{$name}\" added.")->with('open_tab', 'types');
    }

    public function updateType(Request $request, MasterMedicineType $type): RedirectResponse
    {
        $request->validate(['name' => 'required|string|max:150']);
        $name = trim($request->name);

        $dup = MasterMedicineType::whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->where('id', '!=', $type->id)->exists();

        if ($dup) {
            return back()->with('error', "Medicine type \"{$name}\" already exists.")->withInput();
        }

        $oldName = $type->name;
        $type->update(['name' => $name]);
        $this->cascadeRenameNamedMaster(MedicineType::class, $oldName, $name);

        return back()->with('success', 'Medicine type updated.')->with('open_tab', 'types');
    }

    public function destroyType(MasterMedicineType $type): RedirectResponse
    {
        $type->delete();
        return back()->with('success', 'Medicine type deleted.')->with('open_tab', 'types');
    }

    public function toggleType(MasterMedicineType $type): JsonResponse
    {
        $type->update(['is_active' => !$type->is_active]);
        return response()->json(['is_active' => $type->is_active]);
    }

    // ═══════════════════════════════════════════════════
    // MEDICINE CATEGORY CRUD
    // ═══════════════════════════════════════════════════

    public function storeCategory(Request $request): RedirectResponse
    {
        $request->validate(['name' => 'required|string|max:150']);
        $name = trim($request->name);

        if (MasterMedicineCategory::whereRaw('LOWER(name) = ?', [strtolower($name)])->exists()) {
            return back()->with('error', "Medicine category \"{$name}\" already exists.")->withInput();
        }

        MasterMedicineCategory::create(['name' => $name, 'is_active' => true]);
        $this->cascadeCreateNamedMaster(MedicineCategory::class, $name);

        return back()->with('success', "Medicine category \"{$name}\" added.")->with('open_tab', 'categories');
    }

    public function updateCategory(Request $request, MasterMedicineCategory $category): RedirectResponse
    {
        $request->validate(['name' => 'required|string|max:150']);
        $name = trim($request->name);

        $dup = MasterMedicineCategory::whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->where('id', '!=', $category->id)->exists();

        if ($dup) {
            return back()->with('error', "Medicine category \"{$name}\" already exists.")->withInput();
        }

        $oldName = $category->name;
        $category->update(['name' => $name]);
        $this->cascadeRenameNamedMaster(MedicineCategory::class, $oldName, $name);

        return back()->with('success', 'Medicine category updated.')->with('open_tab', 'categories');
    }

    public function destroyCategory(MasterMedicineCategory $category): RedirectResponse
    {
        $category->delete();
        return back()->with('success', 'Medicine category deleted.')->with('open_tab', 'categories');
    }

    public function toggleCategory(MasterMedicineCategory $category): JsonResponse
    {
        $category->update(['is_active' => !$category->is_active]);
        return response()->json(['is_active' => $category->is_active]);
    }

    // ═══════════════════════════════════════════════════
    // MEDICINE ROUTE CRUD
    // ═══════════════════════════════════════════════════

    public function storeRoute(Request $request): RedirectResponse
    {
        $request->validate(['name' => 'required|string|max:150']);
        $name = trim($request->name);

        if (MasterMedicineRoute::whereRaw('LOWER(name) = ?', [strtolower($name)])->exists()) {
            return back()->with('error', "Route \"{$name}\" already exists.")->withInput();
        }

        MasterMedicineRoute::create(['name' => $name, 'is_active' => true]);
        $this->cascadeCreateNamedMaster(MedicineRoute::class, $name);

        return back()->with('success', "Route \"{$name}\" added.")->with('open_tab', 'routes');
    }

    public function updateRoute(Request $request, MasterMedicineRoute $route): RedirectResponse
    {
        $request->validate(['name' => 'required|string|max:150']);
        $name = trim($request->name);

        $dup = MasterMedicineRoute::whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->where('id', '!=', $route->id)->exists();

        if ($dup) {
            return back()->with('error', "Route \"{$name}\" already exists.")->withInput();
        }

        $oldName = $route->name;
        $route->update(['name' => $name]);
        $this->cascadeRenameNamedMaster(MedicineRoute::class, $oldName, $name);

        return back()->with('success', 'Route updated.')->with('open_tab', 'routes');
    }

    public function destroyRoute(MasterMedicineRoute $route): RedirectResponse
    {
        $route->delete();
        return back()->with('success', 'Route deleted.')->with('open_tab', 'routes');
    }

    public function toggleRoute(MasterMedicineRoute $route): JsonResponse
    {
        $route->update(['is_active' => !$route->is_active]);
        return response()->json(['is_active' => $route->is_active]);
    }

    // ═══════════════════════════════════════════════════
    // MEDICINE CRUD
    // ═══════════════════════════════════════════════════

    public function storeMedicine(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'master_medicine_type_id' => ['required', 'exists:tbl_master_medicine_types,id'],
            'master_dosage_id' => ['required', 'exists:tbl_master_dosages,id'],
            'name' => ['required', 'string', 'max:255'],
            'duration' => ['nullable', 'string', 'max:100'],
            'qty' => ['nullable', 'string', 'max:50'],
            'composition' => ['nullable', 'string'],
            'company' => ['nullable', 'string', 'max:255'],
            'price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $medicine = MasterMedicine::create($validated + ['is_active' => true]);
        MedicineTenantSync::pushCreate($medicine);

        return back()->with('success', "Medicine \"{$validated['name']}\" added.")->with('open_tab', 'medicines');
    }

    public function updateMedicine(Request $request, MasterMedicine $medicine): RedirectResponse
    {
        $validated = $request->validate([
            'master_medicine_type_id' => ['required', 'exists:tbl_master_medicine_types,id'],
            'master_dosage_id' => ['required', 'exists:tbl_master_dosages,id'],
            'name' => ['required', 'string', 'max:255'],
            'duration' => ['nullable', 'string', 'max:100'],
            'qty' => ['nullable', 'string', 'max:50'],
            'composition' => ['nullable', 'string'],
            'company' => ['nullable', 'string', 'max:255'],
            'price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $oldName = $medicine->name;
        $medicine->update($validated);
        MedicineTenantSync::pushUpdate($medicine->fresh(['medicineType', 'dosage']), $oldName);

        return back()->with('success', 'Medicine updated.')->with('open_tab', 'medicines');
    }

    public function destroyMedicine(MasterMedicine $medicine): RedirectResponse
    {
        $medicine->delete();
        return back()->with('success', 'Medicine deleted.')->with('open_tab', 'medicines');
    }

    public function toggleMedicine(MasterMedicine $medicine): JsonResponse
    {
        $medicine->update(['is_active' => !$medicine->is_active]);
        return response()->json(['is_active' => $medicine->is_active]);
    }

    // ═══════════════════════════════════════════════════
    // MEDICINE — EXCEL IMPORT / SAMPLE DOWNLOAD
    // ═══════════════════════════════════════════════════

    public function importMedicines(Request $request): RedirectResponse
    {
        $loginableTenantIds = Tenant::loginable()->pluck('id')->all();

        $request->validate([
            'tenant_ids' => ['required', 'array', 'min:1'],
            'tenant_ids.*' => ['integer', Rule::in($loginableTenantIds)],
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        $tenantIds = array_map('intval', $request->input('tenant_ids', []));
        $import = new MedicineMasterImport($tenantIds);
        Excel::import($import, $request->file('file'));

        $hospitalCount = count(array_unique($tenantIds));
        $summary = "Imported {$import->imported}, skipped {$import->skipped} (synced to {$hospitalCount} hospital(s)).";

        if (empty($import->errors)) {
            return back()->with('success', $summary)->with('open_tab', 'medicines');
        }

        $details = implode(' ', array_slice($import->errors, 0, 5));
        if (count($import->errors) > 5) {
            $details .= ' (+' . (count($import->errors) - 5) . ' more)';
        }

        return back()
            ->with($import->imported > 0 ? 'warning' : 'error', "{$summary} {$details}")
            ->with('open_tab', 'medicines');
    }

    public function downloadSampleMedicine()
    {
        return Excel::download(new MedicineMasterSampleExport(), 'medicine-master-sample.xlsx');
    }

    // ═══════════════════════════════════════════════════
    // TENANT CASCADE — push global master add/rename down to every hospital
    // so it shows up in the Hospital admin's own Medicine Master screens.
    // Deletes are intentionally NOT cascaded (a hospital may already have
    // medicines referencing that row; removing it under them is destructive).
    // ═══════════════════════════════════════════════════

    private function cascadeCreateDosage(string $value): void
    {
        foreach (Tenant::query()->pluck('id') as $tenantId) {
            Dosage::withoutTenantScope()
                ->firstOrCreate(['tenant_id' => $tenantId, 'dosage' => $value]);
        }
    }

    private function cascadeRenameDosage(string $oldValue, string $newValue): void
    {
        foreach (Tenant::query()->pluck('id') as $tenantId) {
            $existing = Dosage::withoutTenantScope()
                ->where('tenant_id', $tenantId)
                ->whereRaw('LOWER(dosage) = ?', [strtolower($oldValue)])
                ->first();

            if (!$existing) {
                continue;
            }

            $conflict = Dosage::withoutTenantScope()
                ->where('tenant_id', $tenantId)
                ->whereRaw('LOWER(dosage) = ?', [strtolower($newValue)])
                ->where('id', '!=', $existing->id)
                ->exists();

            if (!$conflict) {
                $existing->update(['dosage' => $newValue]);
            }
        }
    }

    /**
     * @param class-string<MedicineType|MedicineCategory|MedicineRoute> $modelClass
     */
    private function cascadeCreateNamedMaster(string $modelClass, string $name): void
    {
        foreach (Tenant::query()->pluck('id') as $tenantId) {
            $existing = $modelClass::withoutTenantScope()
                ->withTrashed()
                ->where('tenant_id', $tenantId)
                ->whereRaw('LOWER(name) = ?', [strtolower($name)])
                ->first();

            if ($existing) {
                if ($existing->trashed()) {
                    $existing->restore();
                }
                continue;
            }

            $modelClass::create(['tenant_id' => $tenantId, 'name' => $name]);
        }
    }

    /**
     * @param class-string<MedicineType|MedicineCategory|MedicineRoute> $modelClass
     */
    private function cascadeRenameNamedMaster(string $modelClass, string $oldName, string $newName): void
    {
        foreach (Tenant::query()->pluck('id') as $tenantId) {
            $existing = $modelClass::withoutTenantScope()
                ->withTrashed()
                ->where('tenant_id', $tenantId)
                ->whereRaw('LOWER(name) = ?', [strtolower($oldName)])
                ->first();

            if (!$existing) {
                continue;
            }

            $conflict = $modelClass::withoutTenantScope()
                ->withTrashed()
                ->where('tenant_id', $tenantId)
                ->whereRaw('LOWER(name) = ?', [strtolower($newName)])
                ->where('id', '!=', $existing->id)
                ->exists();

            if (!$conflict) {
                $existing->update(['name' => $newName]);
            }
        }
    }
}

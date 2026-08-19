<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Hospital\MasterDiagnosis;
use App\Models\Platform\GlobalMasterDiagnosis;
use App\Models\Platform\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Super Admin — Diagnosis Master.
 *
 * Global diagnosis catalog. Every create/rename is pushed down into each
 * hospital's own tenant-scoped MasterDiagnosis list (tbl_master_diagnosis),
 * mirroring how MedicineMasterController cascades Medicine Type/Category/Route.
 * Deletes are intentionally never cascaded — a hospital may already have
 * exams referencing the diagnosis.
 */
class DiagnosisMasterController extends Controller
{
    public function index(): View
    {
        $diagnoses = GlobalMasterDiagnosis::orderBy('value')->get();

        return view('superadmin.diagnosis_master.index', compact('diagnoses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['value' => 'required|string|max:255']);
        $value = trim($request->value);

        if (GlobalMasterDiagnosis::whereRaw('LOWER(value) = ?', [strtolower($value)])->exists()) {
            return back()->with('error', "Diagnosis \"{$value}\" already exists.")->withInput();
        }

        GlobalMasterDiagnosis::create(['value' => $value, 'is_active' => true]);
        $this->cascadeCreateDiagnosis($value);

        return back()->with('success', "Diagnosis \"{$value}\" added.");
    }

    public function update(Request $request, GlobalMasterDiagnosis $diagnosis): RedirectResponse
    {
        $request->validate(['value' => 'required|string|max:255']);
        $value = trim($request->value);

        $dup = GlobalMasterDiagnosis::whereRaw('LOWER(value) = ?', [strtolower($value)])
            ->where('id', '!=', $diagnosis->id)->exists();

        if ($dup) {
            return back()->with('error', "Diagnosis \"{$value}\" already exists.")->withInput();
        }

        $oldValue = $diagnosis->value;
        $diagnosis->update(['value' => $value]);
        $this->cascadeRenameDiagnosis($oldValue, $value);

        return back()->with('success', 'Diagnosis updated.');
    }

    public function destroy(GlobalMasterDiagnosis $diagnosis): RedirectResponse
    {
        $diagnosis->delete();
        $this->cascadeDeleteDiagnosis($diagnosis->value);

        return back()->with('success', 'Diagnosis deleted.');
    }

    public function toggle(GlobalMasterDiagnosis $diagnosis): JsonResponse
    {
        $diagnosis->update(['is_active' => !$diagnosis->is_active]);

        return response()->json(['is_active' => $diagnosis->is_active]);
    }

    /**
     * Pushes a newly-added global diagnosis into every hospital's own
     * tenant-scoped MasterDiagnosis list (skips a tenant that already has it).
     *
     * MasterDiagnosis::$fillable does not include tenant_id, so the create is
     * wrapped in unguarded() — same approach MasterDiagnosisSeeder already uses.
     */
    private function cascadeCreateDiagnosis(string $value): void
    {
        foreach (Tenant::query()->pluck('id') as $tenantId) {
            $existing = MasterDiagnosis::withoutTenantScope()
                ->withTrashed()
                ->where('tenant_id', $tenantId)
                ->whereRaw('LOWER(value) = ?', [strtolower($value)])
                ->first();

            if ($existing) {
                if ($existing->trashed()) {
                    $existing->restore();
                }
                continue;
            }

            MasterDiagnosis::unguarded(function () use ($tenantId, $value): void {
                MasterDiagnosis::create(['tenant_id' => $tenantId, 'value' => $value]);
            });
        }
    }

    /**
     * Deletes the matching diagnosis from every tenant that has it (soft
     * delete, same as the Super Admin's own row). Unlike Medicine, a Super
     * Admin delete here is meant to cascade down — a hospital admin's own
     * add/delete stays local to their tenant and never reaches this class.
     */
    private function cascadeDeleteDiagnosis(string $value): void
    {
        MasterDiagnosis::withoutTenantScope()
            ->whereRaw('LOWER(value) = ?', [strtolower($value)])
            ->delete();
    }

    /**
     * Renames the matching diagnosis in every tenant that already has the old
     * value. Skips a tenant where the new value already exists (avoids a dup).
     */
    private function cascadeRenameDiagnosis(string $oldValue, string $newValue): void
    {
        foreach (Tenant::query()->pluck('id') as $tenantId) {
            $existing = MasterDiagnosis::withoutTenantScope()
                ->withTrashed()
                ->where('tenant_id', $tenantId)
                ->whereRaw('LOWER(value) = ?', [strtolower($oldValue)])
                ->first();

            if (!$existing) {
                continue;
            }

            $conflict = MasterDiagnosis::withoutTenantScope()
                ->withTrashed()
                ->where('tenant_id', $tenantId)
                ->whereRaw('LOWER(value) = ?', [strtolower($newValue)])
                ->where('id', '!=', $existing->id)
                ->exists();

            if (!$conflict) {
                $existing->update(['value' => $newValue]);
            }
        }
    }
}

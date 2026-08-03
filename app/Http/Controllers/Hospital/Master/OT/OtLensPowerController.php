<?php

/**
 * OtLensPowerController.php
 *
 * PURPOSE: OT Workflow Upgrade — Phase 4. Lens Power master CRUD (PDF §10:
 *          "Power (master and favourite)") — mirrors OtLensOptionController's
 *          pattern exactly for consistency.
 */

namespace App\Http\Controllers\Hospital\Master\OT;

use App\Http\Controllers\Controller;
use App\Models\Hospital\OT\OtLensPower;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OtLensPowerController extends Controller
{
    public function index(string $slug): View
    {
        $tenantId = (int) config('app.tenant_id');

        $records = OtLensPower::query()
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->orderByDesc('is_favourite')
            ->orderBy('power')
            ->get();

        return view('hospital.masters.ot.lens_powers', compact('slug', 'records'));
    }

    public function store(Request $request, string $slug): RedirectResponse
    {
        $tenantId = (int) config('app.tenant_id');

        $validated = $request->validate([
            'power' => [
                'required',
                'numeric',
                'between:-99.99,999.99',
                Rule::unique('ot_lens_powers', 'power')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId)->whereNull('deleted_at')),
            ],
            'is_favourite' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        OtLensPower::query()->create([
            'tenant_id' => $tenantId,
            'power' => $validated['power'],
            'is_favourite' => (bool) ($validated['is_favourite'] ?? false),
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return redirect()->back()->with('success', 'Lens power added successfully.');
    }

    public function update(Request $request, string $slug, int $id): RedirectResponse
    {
        $tenantId = (int) config('app.tenant_id');

        $record = OtLensPower::query()
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->findOrFail($id);

        $validated = $request->validate([
            'power' => [
                'required',
                'numeric',
                'between:-99.99,999.99',
                Rule::unique('ot_lens_powers', 'power')
                    ->ignore($record->id)
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId)->whereNull('deleted_at')),
            ],
            'is_favourite' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $record->update([
            'power' => $validated['power'],
            'is_favourite' => (bool) ($validated['is_favourite'] ?? false),
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return redirect()->back()->with('success', 'Lens power updated successfully.');
    }

    public function destroy(string $slug, int $id): RedirectResponse
    {
        $tenantId = (int) config('app.tenant_id');

        OtLensPower::query()
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->whereKey($id)
            ->update([
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);

        return redirect()->back()->with('success', 'Lens power deleted successfully.');
    }
}

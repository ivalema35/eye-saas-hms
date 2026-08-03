<?php

/**
 * LensInventoryController.php
 *
 * PURPOSE: OT Workflow Upgrade — Phase 7. Lens stock master CRUD (PDF §10
 *          Inventory Module) — mirrors the sibling OT master controllers
 *          (OtLensOptionController, OtLensPowerController) for consistency.
 */

namespace App\Http\Controllers\Hospital\Master\OT;

use App\Http\Controllers\Controller;
use App\Models\Hospital\OT\LensInventory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LensInventoryController extends Controller
{
    public function index(string $slug): View
    {
        $tenantId = (int) config('app.tenant_id');

        $records = LensInventory::query()
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->orderBy('lens_name')
            ->get();

        return view('hospital.masters.ot.lens_inventory', compact('slug', 'records'));
    }

    public function store(Request $request, string $slug): RedirectResponse
    {
        $tenantId = (int) config('app.tenant_id');

        $validated = $this->validated($request, $tenantId);

        LensInventory::query()->create([
            'tenant_id' => $tenantId,
            ...$validated,
        ]);

        return redirect()->back()->with('success', 'Lens inventory item added successfully.');
    }

    public function update(Request $request, string $slug, int $id): RedirectResponse
    {
        $tenantId = (int) config('app.tenant_id');

        $record = LensInventory::query()
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->findOrFail($id);

        $record->update($this->validated($request, $tenantId, $record->id));

        return redirect()->back()->with('success', 'Lens inventory item updated successfully.');
    }

    public function destroy(string $slug, int $id): RedirectResponse
    {
        $tenantId = (int) config('app.tenant_id');

        LensInventory::query()
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->whereKey($id)
            ->update([
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);

        return redirect()->back()->with('success', 'Lens inventory item deleted successfully.');
    }

    private function validated(Request $request, int $tenantId, ?int $ignoreId = null): array
    {
        $validated = $request->validate([
            'lens_code' => [
                'required', 'string', 'max:100',
                Rule::unique('lens_inventory', 'lens_code')
                    ->ignore($ignoreId)
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId)->whereNull('deleted_at')),
            ],
            'manufacturer' => ['nullable', 'string', 'max:150'],
            'lens_name' => ['required', 'string', 'max:200'],
            'type' => ['nullable', 'string', 'max:100'],
            'power' => ['nullable', 'numeric', 'between:-99.99,999.99'],
            'batch_number' => ['nullable', 'string', 'max:100'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'mrp' => ['required', 'numeric', 'min:0'],
            'purchase_cost' => ['nullable', 'numeric', 'min:0'],
            'supplier' => ['nullable', 'string', 'max:150'],
            'expiry_date' => ['nullable', 'date'],
            'available_stock' => ['required', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = (bool) ($validated['is_active'] ?? false);

        return $validated;
    }
}

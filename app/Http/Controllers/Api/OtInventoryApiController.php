<?php

/**
 * OtInventoryApiController.php
 *
 * PURPOSE: Mobile/tablet API mirror of Hospital\Master\OT\LensInventoryController +
 *          OtLensPowerController + OtPackageMasterController (web) — Phase 7
 *          (Lens Inventory Master). See docs/OT_WORKFLOW_UPGRADE_PRD.md §7 and
 *          docs/ROUND3_OT_MOBILE_API_PRD_PLAN.md §11 (FR-OT-37/38).
 *
 * PERMISSION GOTCHA (see migration 2026_08_04_120000_add_and_grant_ot_inventory_manage_permission.php
 * for the full explanation): on the web, this whole master group is gated by
 * `middleware('role:admin')`, which hardcodes the SESSION guard and cannot
 * authenticate a Sanctum bearer-token mobile request at all. A new permission key,
 * `master.ot_inventory`, was added and granted to `hospital_admin` (the same role
 * `role:admin` maps to) so mobile can use the standard `permission:` gate instead —
 * additive only, the web route's `role:admin` gate is untouched.
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hospital\OT\LensInventory;
use App\Models\Hospital\OT\OtLensPower;
use App\Models\Hospital\OT\OtPackageMaster;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OtInventoryApiController extends Controller
{
    // ── Lens Inventory (stock) ──────────────────────────────────────────────

    public function lensInventoryIndex(): JsonResponse
    {
        $tenantId = (int) app('tenant')->id;

        $records = LensInventory::query()
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->orderBy('lens_name')
            ->get();

        return response()->json(['success' => true, 'data' => $records]);
    }

    public function lensInventoryStore(Request $request): JsonResponse
    {
        $tenantId = (int) app('tenant')->id;
        $validated = $this->validatedLensInventory($request, $tenantId);

        $record = LensInventory::query()->create(['tenant_id' => $tenantId, ...$validated]);

        return response()->json(['success' => true, 'message' => 'Lens inventory item added successfully.', 'data' => $record], 201);
    }

    public function lensInventoryUpdate(string $slug, Request $request, int $id): JsonResponse
    {
        $tenantId = (int) app('tenant')->id;

        $record = LensInventory::query()->where('tenant_id', $tenantId)->whereNull('deleted_at')->find($id);
        if (! $record) {
            return response()->json(['success' => false, 'message' => 'Lens inventory item not found.'], 404);
        }

        $record->update($this->validatedLensInventory($request, $tenantId, $record->id));

        return response()->json(['success' => true, 'message' => 'Lens inventory item updated successfully.', 'data' => $record->fresh()]);
    }

    public function lensInventoryDestroy(string $slug, int $id): JsonResponse
    {
        $tenantId = (int) app('tenant')->id;

        $affected = LensInventory::query()
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->whereKey($id)
            ->update(['deleted_at' => now(), 'updated_at' => now()]);

        if ($affected === 0) {
            return response()->json(['success' => false, 'message' => 'Lens inventory item not found.'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Lens inventory item deleted successfully.']);
    }

    /**
     * FR-OT-38 — typeahead used by Counselling (FR-OT-19) and OT lens form (FR-OT-32)
     * pickers. In-stock only by default, ?include_out_of_stock=1 to override.
     */
    public function lensInventorySearch(Request $request): JsonResponse
    {
        $tenantId = (int) app('tenant')->id;

        $query = LensInventory::query()
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->where('is_active', true);

        if (! $request->boolean('include_out_of_stock')) {
            $query->where('available_stock', '>', 0);
        }

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($q) use ($search): void {
                $q->where('lens_name', 'like', "%{$search}%")
                    ->orWhere('lens_code', 'like', "%{$search}%")
                    ->orWhere('manufacturer', 'like', "%{$search}%");
            });
        }

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        if ($power = $request->query('power')) {
            $query->where('power', (float) $power);
        }

        $records = $query->orderBy('lens_name')->limit(25)->get();

        return response()->json(['success' => true, 'data' => $records]);
    }

    private function validatedLensInventory(Request $request, int $tenantId, ?int $ignoreId = null): array
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

    // ── Lens Power master ────────────────────────────────────────────────

    public function lensPowerIndex(): JsonResponse
    {
        $tenantId = (int) app('tenant')->id;

        $records = OtLensPower::query()
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->orderByDesc('is_favourite')
            ->orderBy('power')
            ->get();

        return response()->json(['success' => true, 'data' => $records]);
    }

    public function lensPowerStore(Request $request): JsonResponse
    {
        $tenantId = (int) app('tenant')->id;

        $validated = $request->validate([
            'power' => [
                'required', 'numeric', 'between:-99.99,999.99',
                Rule::unique('ot_lens_powers', 'power')->where(fn ($q) => $q->where('tenant_id', $tenantId)->whereNull('deleted_at')),
            ],
            'is_favourite' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $record = OtLensPower::query()->create([
            'tenant_id' => $tenantId,
            'power' => $validated['power'],
            'is_favourite' => (bool) ($validated['is_favourite'] ?? false),
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return response()->json(['success' => true, 'message' => 'Lens power added successfully.', 'data' => $record], 201);
    }

    public function lensPowerUpdate(string $slug, Request $request, int $id): JsonResponse
    {
        $tenantId = (int) app('tenant')->id;

        $record = OtLensPower::query()->where('tenant_id', $tenantId)->whereNull('deleted_at')->find($id);
        if (! $record) {
            return response()->json(['success' => false, 'message' => 'Lens power not found.'], 404);
        }

        $validated = $request->validate([
            'power' => [
                'required', 'numeric', 'between:-99.99,999.99',
                Rule::unique('ot_lens_powers', 'power')->ignore($record->id)->where(fn ($q) => $q->where('tenant_id', $tenantId)->whereNull('deleted_at')),
            ],
            'is_favourite' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $record->update([
            'power' => $validated['power'],
            'is_favourite' => (bool) ($validated['is_favourite'] ?? false),
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return response()->json(['success' => true, 'message' => 'Lens power updated successfully.', 'data' => $record->fresh()]);
    }

    public function lensPowerDestroy(string $slug, int $id): JsonResponse
    {
        $tenantId = (int) app('tenant')->id;

        $affected = OtLensPower::query()
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->whereKey($id)
            ->update(['deleted_at' => now(), 'updated_at' => now()]);

        if ($affected === 0) {
            return response()->json(['success' => false, 'message' => 'Lens power not found.'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Lens power deleted successfully.']);
    }

    // ── OT Package master ────────────────────────────────────────────────

    public function packageIndex(): JsonResponse
    {
        $tenantId = (int) app('tenant')->id;

        $records = OtPackageMaster::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('lens_cost')
            ->orderBy('room_category')
            ->orderBy('package_name')
            ->get();

        return response()->json(['success' => true, 'data' => $records]);
    }

    public function packageStore(Request $request): JsonResponse
    {
        $tenantId = (int) app('tenant')->id;
        $validated = $this->validatedPackage($request, $tenantId);

        $record = OtPackageMaster::query()->create([
            'tenant_id' => $tenantId,
            'package_name' => $validated['package_name'],
            'lens_cost' => $validated['lens_cost'],
            'room_category' => $validated['room_category'],
            'ot_charges' => $validated['ot_charges'] ?? 0,
            'surgeon_charges' => $validated['surgeon_charges'] ?? 0,
            'nursing_charges' => $validated['nursing_charges'] ?? 0,
            'consumables_charges' => $validated['consumables_charges'] ?? 0,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return response()->json(['success' => true, 'message' => 'OT package added successfully.', 'data' => $record], 201);
    }

    public function packageUpdate(string $slug, Request $request, int $id): JsonResponse
    {
        $tenantId = (int) app('tenant')->id;

        $record = OtPackageMaster::query()->where('tenant_id', $tenantId)->find($id);
        if (! $record) {
            return response()->json(['success' => false, 'message' => 'OT package not found.'], 404);
        }

        $validated = $this->validatedPackage($request, $tenantId, $record->id);

        $record->update([
            'package_name' => $validated['package_name'],
            'lens_cost' => $validated['lens_cost'],
            'room_category' => $validated['room_category'],
            'ot_charges' => $validated['ot_charges'] ?? 0,
            'surgeon_charges' => $validated['surgeon_charges'] ?? 0,
            'nursing_charges' => $validated['nursing_charges'] ?? 0,
            'consumables_charges' => $validated['consumables_charges'] ?? 0,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return response()->json(['success' => true, 'message' => 'OT package updated successfully.', 'data' => $record->fresh()]);
    }

    public function packageDestroy(string $slug, int $id): JsonResponse
    {
        $tenantId = (int) app('tenant')->id;

        $record = OtPackageMaster::query()->where('tenant_id', $tenantId)->find($id);
        if (! $record) {
            return response()->json(['success' => false, 'message' => 'OT package not found.'], 404);
        }

        $record->delete();

        return response()->json(['success' => true, 'message' => 'OT package deleted successfully.']);
    }

    private function validatedPackage(Request $request, int $tenantId, ?int $ignoreId = null): array
    {
        return $request->validate([
            'package_name' => ['required', 'string', 'max:150'],
            'lens_cost' => [
                'required', 'numeric', 'min:0',
                Rule::unique('ot_package_masters', 'lens_cost')
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId)->where('room_category', (string) $request->input('room_category'))->whereNull('deleted_at'))
                    ->ignore($ignoreId),
            ],
            'room_category' => ['required', Rule::in([OtPackageMaster::ROOM_GENERAL, OtPackageMaster::ROOM_PRIVATE])],
            'ot_charges' => ['nullable', 'numeric', 'min:0'],
            'surgeon_charges' => ['nullable', 'numeric', 'min:0'],
            'nursing_charges' => ['nullable', 'numeric', 'min:0'],
            'consumables_charges' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }
}

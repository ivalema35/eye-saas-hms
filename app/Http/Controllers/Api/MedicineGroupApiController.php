<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hospital\Dosage;
use App\Models\Hospital\MasterDiagnosis;
use App\Models\Hospital\Medicine;
use App\Models\Hospital\MedicineGroup;
use App\Models\Hospital\MedicineGroupItem;
use App\Models\Hospital\MedicineRoute;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MedicineGroupApiController extends Controller
{
    /**
     * @param  Request  $request  Optional `scope` query param (e.g. `ot`) — filters by
     *                            `usage_scope` (OT Workflow Upgrade, docs/OT_WORKFLOW_UPGRADE_PRD.md §4)
     *                            so OT-only groups don't leak into OPD prescription pickers and
     *                            vice versa. Omitted = unfiltered, unchanged from before this scope
     *                            column existed — existing callers are unaffected.
     */
    public function index(Request $request): JsonResponse
    {
        $groups = MedicineGroup::with(['items.medicine', 'items.dosage', 'items.route', 'diagnosis'])
            ->withCount('items')
            ->when($request->filled('scope'), fn ($q) => $q->whereIn('usage_scope', [(string) $request->query('scope'), 'both']))
            ->latest()
            ->paginate(25);

        return response()->json([
            'success' => true,
            'data'    => $groups->map(fn ($g) => $this->format($g))->values(),
            'meta'    => [
                'current_page' => $groups->currentPage(),
                'last_page'    => $groups->lastPage(),
                'total'        => $groups->total(),
                'per_page'     => $groups->perPage(),
            ],
        ]);
    }

    public function formData(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => [
                'medicines' => Medicine::orderBy('name')->get(['id', 'name', 'dosage_id', 'duration', 'qty']),
                'dosages'   => Dosage::orderBy('dosage')->get(['id', 'dosage']),
                'routes'    => MedicineRoute::orderBy('name')->get(['id', 'name']),
                'diagnoses' => MasterDiagnosis::orderBy('value')->get(['id', 'value']),
            ],
        ]);
    }

    public function show(string $slug, $id): JsonResponse
    {
        $group = MedicineGroup::with(['items.medicine', 'items.dosage', 'items.route', 'diagnosis'])
            ->withCount('items')
            ->findOrFail((int) $id);

        return response()->json(['success' => true, 'data' => $this->format($group)]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'                => ['required', 'string', 'max:255'],
            'group_code'          => ['nullable', 'string', 'max:50'],
            'diagnosis_id'        => ['nullable', 'exists:tbl_master_diagnosis,id'],
            'items'               => ['required', 'array', 'min:1'],
            'items.*.medicine_id' => ['required', 'exists:medicines,id'],
            'items.*.dosage_id'   => ['nullable', 'exists:dosages,id'],
            'items.*.route_id'    => ['nullable', 'exists:medicine_routes,id'],
            'items.*.duration'    => ['nullable', 'string', 'max:100'],
            'items.*.quantity'    => ['required', 'integer', 'min:1'],
        ]);

        $group = DB::transaction(function () use ($request) {
            $g = MedicineGroup::create([
                'name'         => $request->name,
                'group_code'   => $request->group_code ?? null,
                'diagnosis_id' => $request->diagnosis_id ?: null,
            ]);

            MedicineGroupItem::insert($this->buildItems($request->items, $g->id));

            return $g;
        });

        return response()->json(['success' => true, 'message' => 'Medicine Group created successfully.', 'id' => $group->id], 201);
    }

    public function update(string $slug, Request $request, $id): JsonResponse
    {
        $group = MedicineGroup::findOrFail((int) $id);

        $request->validate([
            'name'                => ['required', 'string', 'max:255'],
            'group_code'          => ['nullable', 'string', 'max:50'],
            'diagnosis_id'        => ['nullable', 'exists:tbl_master_diagnosis,id'],
            'items'               => ['required', 'array', 'min:1'],
            'items.*.medicine_id' => ['required', 'exists:medicines,id'],
            'items.*.dosage_id'   => ['nullable', 'exists:dosages,id'],
            'items.*.route_id'    => ['nullable', 'exists:medicine_routes,id'],
            'items.*.duration'    => ['nullable', 'string', 'max:100'],
            'items.*.quantity'    => ['required', 'integer', 'min:1'],
        ]);

        DB::transaction(function () use ($request, $group) {
            $group->update([
                'name'         => $request->name,
                'group_code'   => $request->group_code ?? null,
                'diagnosis_id' => $request->diagnosis_id ?: null,
            ]);

            $group->items()->delete();
            MedicineGroupItem::insert($this->buildItems($request->items, $group->id));
        });

        return response()->json(['success' => true, 'message' => 'Medicine Group updated successfully.']);
    }

    public function destroy(string $slug, $id): JsonResponse
    {
        MedicineGroup::findOrFail((int) $id)->delete();

        return response()->json(['success' => true, 'message' => 'Medicine Group deleted successfully.']);
    }

    private function buildItems(array $items, int $groupId): array
    {
        $tenantId = config('app.tenant_id');
        $now      = now();

        return collect($items)->map(fn ($item) => [
            'tenant_id'         => $tenantId,
            'medicine_group_id' => $groupId,
            'medicine_id'       => $item['medicine_id'],
            'dosage_id'         => $item['dosage_id'] ?: null,
            'route_id'          => $item['route_id'] ?: null,
            'duration'          => $item['duration'] ?? null,
            'quantity'          => $item['quantity'],
            'created_at'        => $now,
            'updated_at'        => $now,
        ])->all();
    }

    private function format(MedicineGroup $g): array
    {
        return [
            'id'           => $g->id,
            'name'         => $g->name,
            'group_code'   => $g->group_code,
            'diagnosis_id' => $g->diagnosis_id,
            'diagnosis'    => $g->diagnosis ? ['id' => $g->diagnosis->id, 'value' => $g->diagnosis->value] : null,
            'items_count'  => $g->items_count ?? $g->items->count(),
            'items'        => $g->items->map(fn ($item) => [
                'medicine_id' => $item->medicine_id,
                'medicine'    => $item->medicine ? ['name' => $item->medicine->name] : null,
                'dosage_id'   => $item->dosage_id,
                'dosage'      => $item->dosage ? ['dosage' => $item->dosage->dosage] : null,
                'route_id'    => $item->route_id,
                'route'       => $item->route ? ['name' => $item->route->name] : null,
                'duration'    => $item->duration,
                'quantity'    => $item->quantity,
            ])->values(),
        ];
    }
}

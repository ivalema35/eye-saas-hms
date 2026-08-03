<?php

namespace App\Http\Controllers\Hospital\Master\OT;

use App\Http\Controllers\Controller;
use App\Models\Hospital\OT\OtPackageMaster;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OtPackageMasterController extends Controller
{
    public function index(string $slug): View
    {
        $tenantId = (int) config('app.tenant_id');

        $records = OtPackageMaster::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('lens_cost')
            ->orderBy('room_category')
            ->orderBy('package_name')
            ->get();

        return view('hospital.masters.ot.packages', compact('slug', 'records'));
    }

    public function store(Request $request, string $slug): RedirectResponse
    {
        $tenantId = (int) config('app.tenant_id');
        $validated = $this->validated($request, $tenantId);

        OtPackageMaster::query()->create([
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

        return redirect()->back()->with('success', 'OT package added successfully.');
    }

    public function update(Request $request, string $slug, int $id): RedirectResponse
    {
        $tenantId = (int) config('app.tenant_id');

        $record = OtPackageMaster::query()
            ->where('tenant_id', $tenantId)
            ->findOrFail($id);

        $validated = $this->validated($request, $tenantId, $record->id);

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

        return redirect()->back()->with('success', 'OT package updated successfully.');
    }

    public function destroy(string $slug, int $id): RedirectResponse
    {
        $tenantId = (int) config('app.tenant_id');

        OtPackageMaster::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($id)
            ->delete();

        return redirect()->back()->with('success', 'OT package deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, int $tenantId, ?int $ignoreId = null): array
    {
        return $request->validate([
            'package_name' => ['required', 'string', 'max:150'],
            'lens_cost' => [
                'required',
                'numeric',
                'min:0',
                Rule::unique('ot_package_masters', 'lens_cost')
                    ->where(fn ($q) => $q
                        ->where('tenant_id', $tenantId)
                        ->where('room_category', (string) $request->input('room_category'))
                        ->whereNull('deleted_at'))
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

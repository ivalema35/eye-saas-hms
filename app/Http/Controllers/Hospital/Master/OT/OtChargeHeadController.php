<?php

namespace App\Http\Controllers\Hospital\Master\OT;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OtChargeHeadController extends Controller
{
    public function index(string $slug): View
    {
        $tenantId = (int) config('app.tenant_id');

        $records = DB::table('ot_charge_heads')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->orderBy('charge_name')
            ->get();

        return view('hospital.masters.ot.charge_heads', compact('slug', 'records'));
    }

    public function store(Request $request, string $slug): RedirectResponse
    {
        $tenantId = (int) config('app.tenant_id');

        $validated = $request->validate([
            'charge_name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('ot_charge_heads', 'charge_name')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId)->whereNull('deleted_at')),
            ],
            'percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        DB::table('ot_charge_heads')->insert([
            'tenant_id' => $tenantId,
            'charge_name' => $validated['charge_name'],
            'percentage' => $validated['percentage'],
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]);

        return redirect()->back()->with('success', 'OT charge head added successfully.');
    }

    public function update(Request $request, string $slug, int $id): RedirectResponse
    {
        $tenantId = (int) config('app.tenant_id');

        $record = DB::table('ot_charge_heads')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->where('id', $id)
            ->first();

        abort_if(! $record, 404);

        $validated = $request->validate([
            'charge_name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('ot_charge_heads', 'charge_name')
                    ->ignore($id)
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId)->whereNull('deleted_at')),
            ],
            'percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        DB::table('ot_charge_heads')
            ->where('id', $id)
            ->update([
                'charge_name' => $validated['charge_name'],
                'percentage' => $validated['percentage'],
                'is_active' => (bool) ($validated['is_active'] ?? false),
                'updated_at' => now()->toDateTimeString(),
            ]);

        return redirect()->back()->with('success', 'OT charge head updated successfully.');
    }

    public function destroy(string $slug, int $id): RedirectResponse
    {
        $tenantId = (int) config('app.tenant_id');

        DB::table('ot_charge_heads')
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->update([
                'deleted_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ]);

        return redirect()->back()->with('success', 'OT charge head deleted successfully.');
    }
}

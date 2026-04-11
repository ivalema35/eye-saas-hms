<?php

namespace App\Http\Controllers\Hospital\Master\OT;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OtSurgeryTypeController extends Controller
{
    public function index(string $slug): View
    {
        $tenantId = (int) config('app.tenant_id');

        $types = DB::table('ot_types')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        $records = DB::table('ot_surgery_types as ost')
            ->join('ot_types as ot', 'ot.id', '=', 'ost.ot_type_id')
            ->where('ost.tenant_id', $tenantId)
            ->whereNull('ost.deleted_at')
            ->select('ost.*', 'ot.name as ot_type_name')
            ->orderBy('ot.name')
            ->orderBy('ost.surgery_name')
            ->get();

        return view('hospital.masters.ot.surgery_types', compact('slug', 'records', 'types'));
    }

    public function store(Request $request, string $slug): RedirectResponse
    {
        $tenantId = (int) config('app.tenant_id');

        $validated = $request->validate([
            'ot_type_id' => [
                'required',
                'integer',
                Rule::exists('ot_types', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)->whereNull('deleted_at')),
            ],
            'surgery_name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('ot_surgery_types', 'surgery_name')
                    ->where(fn ($query) => $query
                        ->where('tenant_id', $tenantId)
                        ->where('ot_type_id', (int) $request->input('ot_type_id'))
                        ->whereNull('deleted_at')),
            ],
        ]);

        DB::table('ot_surgery_types')->insert([
            'tenant_id' => $tenantId,
            'ot_type_id' => (int) $validated['ot_type_id'],
            'surgery_name' => $validated['surgery_name'],
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]);

        return redirect()->back()->with('success', 'OT surgery type added successfully.');
    }

    public function update(Request $request, string $slug, int $id): RedirectResponse
    {
        $tenantId = (int) config('app.tenant_id');

        $record = DB::table('ot_surgery_types')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->where('id', $id)
            ->first();

        abort_if(! $record, 404);

        $validated = $request->validate([
            'ot_type_id' => [
                'required',
                'integer',
                Rule::exists('ot_types', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)->whereNull('deleted_at')),
            ],
            'surgery_name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('ot_surgery_types', 'surgery_name')
                    ->ignore($id)
                    ->where(fn ($query) => $query
                        ->where('tenant_id', $tenantId)
                        ->where('ot_type_id', (int) $request->input('ot_type_id'))
                        ->whereNull('deleted_at')),
            ],
        ]);

        DB::table('ot_surgery_types')
            ->where('id', $id)
            ->update([
                'ot_type_id' => (int) $validated['ot_type_id'],
                'surgery_name' => $validated['surgery_name'],
                'updated_at' => now()->toDateTimeString(),
            ]);

        return redirect()->back()->with('success', 'OT surgery type updated successfully.');
    }

    public function destroy(string $slug, int $id): RedirectResponse
    {
        $tenantId = (int) config('app.tenant_id');

        DB::table('ot_surgery_types')
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->update([
                'deleted_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ]);

        return redirect()->back()->with('success', 'OT surgery type deleted successfully.');
    }
}

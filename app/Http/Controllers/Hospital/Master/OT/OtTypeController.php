<?php

namespace App\Http\Controllers\Hospital\Master\OT;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OtTypeController extends Controller
{
    public function index(string $slug): View
    {
        $tenantId = (int) config('app.tenant_id');

        $records = DB::table('ot_types')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        return view('hospital.masters.ot.types', compact('slug', 'records'));
    }

    public function store(Request $request, string $slug): RedirectResponse
    {
        $tenantId = (int) config('app.tenant_id');

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('ot_types', 'name')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId)->whereNull('deleted_at')),
            ],
        ]);

        DB::table('ot_types')->insert([
            'tenant_id' => $tenantId,
            'name' => $validated['name'],
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]);

        return redirect()->back()->with('success', 'OT type added successfully.');
    }

    public function update(Request $request, string $slug, int $id): RedirectResponse
    {
        $tenantId = (int) config('app.tenant_id');

        $record = DB::table('ot_types')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->where('id', $id)
            ->first();

        abort_if(! $record, 404);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('ot_types', 'name')
                    ->ignore($id)
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId)->whereNull('deleted_at')),
            ],
        ]);

        DB::table('ot_types')
            ->where('id', $id)
            ->update([
                'name' => $validated['name'],
                'updated_at' => now()->toDateTimeString(),
            ]);

        return redirect()->back()->with('success', 'OT type updated successfully.');
    }

    public function destroy(string $slug, int $id): RedirectResponse
    {
        $tenantId = (int) config('app.tenant_id');

        DB::table('ot_types')
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->update([
                'deleted_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ]);

        return redirect()->back()->with('success', 'OT type deleted successfully.');
    }
}

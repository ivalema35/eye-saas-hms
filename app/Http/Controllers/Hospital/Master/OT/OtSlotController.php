<?php

namespace App\Http\Controllers\Hospital\Master\OT;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OtSlotController extends Controller
{
    public function index(string $slug): View
    {
        $tenantId = (int) config('app.tenant_id');

        $records = DB::table('tbl_ot_slots')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->orderBy('slot_name')
            ->get();

        return view('hospital.masters.ot.slots', compact('slug', 'records'));
    }

    public function store(Request $request, string $slug): RedirectResponse
    {
        $tenantId = (int) config('app.tenant_id');

        $validated = $request->validate([
            'slot_name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('tbl_ot_slots', 'slot_name')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId)->whereNull('deleted_at')),
            ],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ]);

        $ts = now()->toDateTimeString();

        DB::table('tbl_ot_slots')->insert([
            'tenant_id' => $tenantId,
            'slot_name' => $validated['slot_name'],
            'start_time' => $validated['start_time'] ?? null,
            'end_time' => $validated['end_time'] ?? null,
            'created_at' => $ts,
            'updated_at' => $ts,
        ]);

        return redirect()->back()->with('success', 'OT slot added successfully.');
    }

    public function update(Request $request, string $slug, int $id): RedirectResponse
    {
        $tenantId = (int) config('app.tenant_id');

        $record = DB::table('tbl_ot_slots')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->where('id', $id)
            ->first();

        abort_if(! $record, 404);

        $validated = $request->validate([
            'slot_name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('tbl_ot_slots', 'slot_name')
                    ->ignore($id)
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId)->whereNull('deleted_at')),
            ],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ]);

        DB::table('tbl_ot_slots')
            ->where('id', $id)
            ->update([
                'slot_name' => $validated['slot_name'],
                'start_time' => $validated['start_time'] ?? null,
                'end_time' => $validated['end_time'] ?? null,
                'updated_at' => now()->toDateTimeString(),
            ]);

        return redirect()->back()->with('success', 'OT slot updated successfully.');
    }

    public function destroy(string $slug, int $id): RedirectResponse
    {
        $tenantId = (int) config('app.tenant_id');

        DB::table('tbl_ot_slots')
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->update([
                'deleted_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ]);

        return redirect()->back()->with('success', 'OT slot deleted successfully.');
    }
}

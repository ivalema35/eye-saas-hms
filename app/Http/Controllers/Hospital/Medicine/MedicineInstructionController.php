<?php

namespace App\Http\Controllers\Hospital\Medicine;

use App\Http\Controllers\Controller;
use App\Models\Hospital\MasterMedicineInstruction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MedicineInstructionController extends Controller
{
    public function index(string $slug): View
    {
        $instructions = MasterMedicineInstruction::where('tenant_id', app('tenant')->id)
            ->latest()
            ->get();

        return view('hospital.medicine_instructions.index', compact('instructions', 'slug'));
    }

    public function store(Request $request, string $slug): RedirectResponse
    {
        $validated = $request->validate([
            'value' => ['required', 'string', 'max:255'],
        ]);

        MasterMedicineInstruction::create($validated);

        return redirect()
            ->route('hospital.medicine_instructions.index', ['slug' => $slug])
            ->with('success', 'Instruction added successfully.');
    }

    public function update(Request $request, string $slug, int $id): RedirectResponse
    {
        $instruction = MasterMedicineInstruction::where('tenant_id', app('tenant')->id)
            ->findOrFail($id);

        $validated = $request->validate([
            'value' => ['required', 'string', 'max:255'],
        ]);

        $instruction->update($validated);

        return redirect()
            ->route('hospital.medicine_instructions.index', ['slug' => $slug])
            ->with('success', 'Instruction updated successfully.');
    }

    public function destroy(string $slug, int $id): RedirectResponse
    {
        MasterMedicineInstruction::where('tenant_id', app('tenant')->id)
            ->findOrFail($id)
            ->delete();

        return redirect()
            ->route('hospital.medicine_instructions.index', ['slug' => $slug])
            ->with('success', 'Instruction deleted successfully.');
    }
}

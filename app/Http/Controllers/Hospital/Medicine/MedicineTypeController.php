<?php

namespace App\Http\Controllers\Hospital\Medicine;

use App\Http\Controllers\Controller;
use App\Models\Hospital\MedicineType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MedicineTypeController extends Controller
{
    public function index(string $slug): View
    {
        $types = MedicineType::latest()->get();

        return view('hospital.medicine_types.index', compact('types', 'slug'));
    }

    public function store(Request $request, string $slug): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        MedicineType::create($validated);

        return redirect()
            ->route('hospital.medicine-types.index', ['slug' => $slug])
            ->with('success', 'Medicine type added successfully.');
    }

    public function update(Request $request, string $slug, int $id): RedirectResponse
    {
        $type = MedicineType::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $type->update($validated);

        return redirect()
            ->route('hospital.medicine-types.index', ['slug' => $slug])
            ->with('success', 'Medicine type updated successfully.');
    }

    public function destroy(string $slug, int $id): RedirectResponse
    {
        MedicineType::findOrFail($id)->delete();

        return redirect()
            ->route('hospital.medicine-types.index', ['slug' => $slug])
            ->with('success', 'Medicine type deleted successfully.');
    }
}

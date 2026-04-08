<?php

namespace App\Http\Controllers\Hospital\Medicine;

use App\Http\Controllers\Controller;
use App\Models\Hospital\Dosage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MedicineDosageController extends Controller
{
    public function index(string $slug): View
    {
        $dosages = Dosage::orderBy('dosage')->get();

        return view('hospital.medicine_dosages.index', compact('dosages', 'slug'));
    }

    public function store(Request $request, string $slug): RedirectResponse
    {
        $validated = $request->validate([
            'dosage' => ['required', 'string', 'max:255'],
        ]);

        Dosage::create($validated);

        return redirect()
            ->route('hospital.medicine-dosages.index', ['slug' => $slug])
            ->with('success', 'Medicine dosage added successfully.');
    }

    public function update(Request $request, string $slug, int $id): RedirectResponse
    {
        $dosage = Dosage::findOrFail($id);

        $validated = $request->validate([
            'dosage' => ['required', 'string', 'max:255'],
        ]);

        $dosage->update($validated);

        return redirect()
            ->route('hospital.medicine-dosages.index', ['slug' => $slug])
            ->with('success', 'Medicine dosage updated successfully.');
    }

    public function destroy(string $slug, int $id): RedirectResponse
    {
        Dosage::findOrFail($id)->delete();

        return redirect()
            ->route('hospital.medicine-dosages.index', ['slug' => $slug])
            ->with('success', 'Medicine dosage deleted successfully.');
    }
}
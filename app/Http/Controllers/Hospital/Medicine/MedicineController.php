<?php

namespace App\Http\Controllers\Hospital\Medicine;

use App\Http\Controllers\Controller;
use App\Models\Hospital\Dosage;
use App\Models\Hospital\Medicine;
use App\Models\Hospital\MedicineType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MedicineController extends Controller
{
    public function index(string $slug): View
    {
        $medicineTypes = MedicineType::orderBy('name')->get();
        $dosages       = Dosage::orderBy('dosage')->get();
        $medicines     = Medicine::with('medicineType', 'dosage')
            ->when(request('search'), fn ($q, $s) => $q->where('name', 'like', "%{$s}%")
                ->orWhere('brand_name', 'like', "%{$s}%"))
            ->latest()
            ->paginate((int) config('app.pagination_limit', 25))
            ->withQueryString();

        return view('hospital.medicines.index', compact('slug', 'medicines', 'medicineTypes', 'dosages'));
    }

    public function create(string $slug): View
    {
        $medicineTypes = MedicineType::orderBy('name')->get();
        $dosages       = Dosage::orderBy('dosage')->get();

        return view('hospital.medicines.create', compact('slug', 'medicineTypes', 'dosages'));
    }

    public function store(Request $request, string $slug): RedirectResponse
    {
        $validated = $request->validate([
            'medicine_type_id' => ['required', 'exists:medicine_types,id'],
            'name'             => ['required', 'string', 'max:255'],
            'dosage_id'        => ['nullable', 'exists:dosages,id'],
            'duration'         => ['nullable', 'string', 'max:100'],
            'qty'              => ['nullable', 'string', 'max:50'],
            'composition'      => ['nullable', 'string'],
            'company'          => ['nullable', 'string', 'max:255'],
            'price'            => ['required', 'numeric', 'min:0'],
        ]);

        Medicine::create($validated);

        return redirect()->route('hospital.medicines.index', compact('slug'))
            ->with('success', 'Medicine added successfully.');
    }

    public function edit(string $slug, int $id): View
    {
        $medicine      = Medicine::findOrFail($id);
        $medicineTypes = MedicineType::orderBy('name')->get();
        $dosages       = Dosage::orderBy('dosage')->get();

        return view('hospital.medicines.edit', compact('slug', 'medicine', 'medicineTypes', 'dosages'));
    }

    public function update(Request $request, string $slug, int $id): RedirectResponse
    {
        $medicine = Medicine::findOrFail($id);
        $validated = $request->validate([
            'medicine_type_id' => ['required', 'exists:medicine_types,id'],
            'name'             => ['required', 'string', 'max:255'],
            'dosage_id'        => ['nullable', 'exists:dosages,id'],
            'duration'         => ['nullable', 'string', 'max:100'],
            'qty'              => ['nullable', 'string', 'max:50'],
            'composition'      => ['nullable', 'string'],
            'company'          => ['nullable', 'string', 'max:255'],
            'price'            => ['required', 'numeric', 'min:0'],
        ]);

        $medicine->update($validated);

        return redirect()->route('hospital.medicines.index', compact('slug'))
            ->with('success', 'Medicine updated successfully.');
    }

    public function destroy(string $slug, int $id): RedirectResponse
    {
        Medicine::findOrFail($id)->delete();

        return redirect()->route('hospital.medicines.index', compact('slug'))
            ->with('success', 'Medicine deleted successfully.');
    }
}

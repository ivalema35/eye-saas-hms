<?php

namespace App\Http\Controllers\Hospital\Medicine;

use App\Exports\HospitalMedicineSampleExport;
use App\Http\Controllers\Controller;
use App\Imports\HospitalMedicineImport;
use App\Models\Hospital\Dosage;
use App\Models\Hospital\Medicine;
use App\Models\Hospital\MedicineType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class MedicineController extends Controller
{
    public function index(string $slug): View
    {
        $medicineTypes = MedicineType::orderBy('name')->get();
        $dosages = Dosage::orderBy('dosage')->get();
        $medicines = Medicine::with('medicineType', 'dosage')
            ->latest()
            ->get();

        return view('hospital.medicines.index', compact('slug', 'medicines', 'medicineTypes', 'dosages'));
    }

    public function create(string $slug): View
    {
        $medicineTypes = MedicineType::orderBy('name')->get();
        $dosages = Dosage::orderBy('dosage')->get();

        return view('hospital.medicines.create', compact('slug', 'medicineTypes', 'dosages'));
    }

    public function store(Request $request, string $slug): RedirectResponse
    {
        $validated = $request->validate([
            'medicine_type_id' => ['required', 'exists:medicine_types,id'],
            'name' => ['required', 'string', 'max:255'],
            // 'usage_scope'      => ['required', 'in:opd,ot'],
            'dosage_id' => ['required', 'exists:dosages,id'],
            'duration' => ['required', 'string', 'max:100'],
            'qty' => ['required', 'integer', 'min:1', 'max:9999'],
            'composition' => ['nullable', 'string'],
            'company' => ['nullable', 'string', 'max:255'],
            'price' => ['nullable', 'numeric', 'min:0'],
        ]);

        Medicine::create($validated);

        return redirect()->route('hospital.medicines.index', compact('slug'))
            ->with('success', 'Medicine added successfully.');
    }

    public function edit(string $slug, int $id): View
    {
        $medicine = Medicine::findOrFail($id);
        $medicineTypes = MedicineType::orderBy('name')->get();
        $dosages = Dosage::orderBy('dosage')->get();

        return view('hospital.medicines.edit', compact('slug', 'medicine', 'medicineTypes', 'dosages'));
    }

    public function update(Request $request, string $slug, int $id): RedirectResponse
    {
        $medicine = Medicine::findOrFail($id);
        $validated = $request->validate([
            'medicine_type_id' => ['required', 'exists:medicine_types,id'],
            'name' => ['required', 'string', 'max:255'],
            // 'usage_scope'      => ['required', 'in:opd,ot'],
            'dosage_id' => ['required', 'exists:dosages,id'],
            'duration' => ['required', 'string', 'max:100'],
            'qty' => ['required', 'integer', 'min:1', 'max:9999'],
            'composition' => ['nullable', 'string'],
            'company' => ['nullable', 'string', 'max:255'],
            'price' => ['nullable', 'numeric', 'min:0'],
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

    public function import(Request $request, string $slug): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        $import = new HospitalMedicineImport();
        Excel::import($import, $request->file('file'));

        $summary = "Imported {$import->imported}, skipped {$import->skipped}.";

        if (empty($import->errors)) {
            return redirect()->route('hospital.medicines.index', compact('slug'))
                ->with('success', $summary);
        }

        $details = implode(' ', array_slice($import->errors, 0, 5));
        if (count($import->errors) > 5) {
            $details .= ' (+' . (count($import->errors) - 5) . ' more)';
        }

        return redirect()->route('hospital.medicines.index', compact('slug'))
            ->with($import->imported > 0 ? 'warning' : 'error', "{$summary} {$details}");
    }

    public function downloadSample()
    {
        return Excel::download(new HospitalMedicineSampleExport(), 'medicine-sample.xlsx');
    }
}

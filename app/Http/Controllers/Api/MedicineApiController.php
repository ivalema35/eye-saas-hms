<?php

namespace App\Http\Controllers\Api;

use App\Exports\HospitalMedicineSampleExport;
use App\Http\Controllers\Controller;
use App\Imports\HospitalMedicineImport;
use App\Models\Hospital\Dosage;
use App\Models\Hospital\Medicine;
use App\Models\Hospital\MedicineType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class MedicineApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $medicines = Medicine::with('medicineType', 'dosage')
            ->when(
                $request->filled('search'),
                fn ($q) => $q->where(function ($q2) use ($request) {
                    $s = $request->search;
                    $q2->where('name', 'like', "%{$s}%")
                       ->orWhere('company', 'like', "%{$s}%");
                })
            )
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return response()->json([
            'success'   => true,
            'data'      => collect($medicines->items())->map(fn ($m) => $this->format($m))->values(),
            'meta'      => [
                'current_page' => $medicines->currentPage(),
                'last_page'    => $medicines->lastPage(),
                'total'        => $medicines->total(),
                'per_page'     => $medicines->perPage(),
            ],
            'form_data' => [
                'types'   => MedicineType::orderBy('name')->get(['id', 'name']),
                'dosages' => Dosage::orderBy('dosage')->get(['id', 'dosage']),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'medicine_type_id' => ['required', 'exists:medicine_types,id'],
            'name'             => ['required', 'string', 'max:255'],
            'usage_scope'      => ['required', 'in:opd,ot'],
            'dosage_id'        => ['required', 'exists:dosages,id'],
            'duration'         => ['required', 'string', 'max:100'],
            'qty'              => ['required', 'string', 'max:50'],
            'composition'      => ['nullable', 'string'],
            'company'          => ['nullable', 'string', 'max:255'],
            'price'            => ['nullable', 'numeric', 'min:0'],
        ]);

        $med = Medicine::create($validated);

        return response()->json(['success' => true, 'message' => 'Medicine added successfully.', 'id' => $med->id], 201);
    }

    public function update(string $slug, Request $request, int $id): JsonResponse
    {
        $medicine  = Medicine::findOrFail($id);
        $validated = $request->validate([
            'medicine_type_id' => ['required', 'exists:medicine_types,id'],
            'name'             => ['required', 'string', 'max:255'],
            'usage_scope'      => ['required', 'in:opd,ot'],
            'dosage_id'        => ['required', 'exists:dosages,id'],
            'duration'         => ['required', 'string', 'max:100'],
            'qty'              => ['required', 'string', 'max:50'],
            'composition'      => ['nullable', 'string'],
            'company'          => ['nullable', 'string', 'max:255'],
            'price'            => ['nullable', 'numeric', 'min:0'],
        ]);

        $medicine->update($validated);

        return response()->json(['success' => true, 'message' => 'Medicine updated successfully.']);
    }

    public function destroy(string $slug, int $id): JsonResponse
    {
        Medicine::findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Medicine deleted successfully.']);
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ]);

        $import = new HospitalMedicineImport();
        Excel::import($import, $request->file('file'));

        return response()->json([
            'success'  => true,
            'message'  => "Imported {$import->imported}, skipped {$import->skipped}.",
            'imported' => $import->imported,
            'skipped'  => $import->skipped,
            'errors'   => $import->errors,
        ]);
    }

    public function downloadSample()
    {
        return Excel::download(new HospitalMedicineSampleExport(), 'medicine-sample.xlsx');
    }

    private function format(Medicine $m): array
    {
        return [
            'id'               => $m->id,
            'name'             => $m->name,
            'usage_scope'      => $m->usage_scope,
            'medicine_type_id' => $m->medicine_type_id,
            'medicine_type'    => $m->medicineType ? ['id' => $m->medicineType->id, 'name' => $m->medicineType->name] : null,
            'dosage_id'        => $m->dosage_id,
            'dosage'           => $m->dosage ? ['id' => $m->dosage->id, 'dosage' => $m->dosage->dosage] : null,
            'duration'         => $m->duration,
            'qty'              => $m->qty,
            'company'          => $m->company,
            'composition'      => $m->composition,
            'price'            => $m->price !== null ? (float) $m->price : null,
        ];
    }
}

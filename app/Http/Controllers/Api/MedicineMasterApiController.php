<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hospital\Dosage;
use App\Models\Hospital\MedicineCategory;
use App\Models\Hospital\MedicineRoute;
use App\Models\Hospital\MedicineType;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MedicineMasterApiController extends Controller
{
    // ── Dosages ──────────────────────────────────────────────────────────────

    public function dosages(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => Dosage::orderBy('dosage')->get(['id', 'dosage']),
        ]);
    }

    public function storeDosage(Request $request): JsonResponse
    {
        $request->validate(['dosage' => ['required', 'string', 'max:255']]);
        $d = Dosage::create(['dosage' => $request->dosage]);

        return response()->json(['success' => true, 'message' => 'Dosage added successfully.', 'id' => $d->id], 201);
    }

    public function updateDosage(string $slug, Request $request, int $id): JsonResponse
    {
        $request->validate(['dosage' => ['required', 'string', 'max:255']]);
        Dosage::findOrFail($id)->update(['dosage' => $request->dosage]);

        return response()->json(['success' => true, 'message' => 'Dosage updated successfully.']);
    }

    public function destroyDosage(string $slug, int $id): JsonResponse
    {
        try {
            Dosage::findOrFail($id)->delete();

            return response()->json(['success' => true, 'message' => 'Dosage deleted successfully.']);
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return response()->json([
                    'success' => false,
                    'message' => 'This dosage cannot be deleted because it is linked to one or more medicines.',
                ], 422);
            }
            throw $e;
        }
    }

    // ── Medicine Types ────────────────────────────────────────────────────────

    public function types(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => MedicineType::latest()->get(['id', 'name']),
        ]);
    }

    public function storeType(Request $request): JsonResponse
    {
        $request->validate(['name' => ['required', 'string', 'max:255']]);
        $t = MedicineType::create(['name' => $request->name]);

        return response()->json(['success' => true, 'message' => 'Medicine type added.', 'id' => $t->id], 201);
    }

    public function updateType(string $slug, Request $request, int $id): JsonResponse
    {
        $request->validate(['name' => ['required', 'string', 'max:255']]);
        MedicineType::findOrFail($id)->update(['name' => $request->name]);

        return response()->json(['success' => true, 'message' => 'Medicine type updated.']);
    }

    public function destroyType(string $slug, int $id): JsonResponse
    {
        MedicineType::findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Medicine type deleted.']);
    }

    // ── Medicine Categories ───────────────────────────────────────────────────

    public function categories(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => MedicineCategory::latest()->get(['id', 'name']),
        ]);
    }

    public function storeCategory(Request $request): JsonResponse
    {
        $request->validate(['name' => ['required', 'string', 'max:100']]);
        $c = MedicineCategory::create(['name' => $request->name]);

        return response()->json(['success' => true, 'message' => 'Category added.', 'id' => $c->id], 201);
    }

    public function updateCategory(string $slug, Request $request, int $id): JsonResponse
    {
        $request->validate(['name' => ['required', 'string', 'max:100']]);
        MedicineCategory::findOrFail($id)->update(['name' => $request->name]);

        return response()->json(['success' => true, 'message' => 'Category updated.']);
    }

    public function destroyCategory(string $slug, int $id): JsonResponse
    {
        MedicineCategory::findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Category deleted.']);
    }

    // ── Routes of Administration ──────────────────────────────────────────────

    public function medicineRoutes(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => MedicineRoute::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function storeMedicineRoute(Request $request): JsonResponse
    {
        $request->validate(['name' => ['required', 'string', 'max:100']]);
        $r = MedicineRoute::create(['name' => $request->name]);

        return response()->json(['success' => true, 'message' => 'Route added.', 'id' => $r->id], 201);
    }

    public function updateMedicineRoute(string $slug, Request $request, int $id): JsonResponse
    {
        $request->validate(['name' => ['required', 'string', 'max:100']]);
        MedicineRoute::findOrFail($id)->update(['name' => $request->name]);

        return response()->json(['success' => true, 'message' => 'Route updated.']);
    }

    public function destroyMedicineRoute(string $slug, int $id): JsonResponse
    {
        MedicineRoute::findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Route deleted.']);
    }
}

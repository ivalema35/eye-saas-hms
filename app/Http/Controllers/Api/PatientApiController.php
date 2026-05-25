<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hospital\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Patient REST API Controller
 *
 * Mobile/external API for patient data access.
 */
class PatientApiController extends Controller
{
    public function index(): JsonResponse
    {
        $patients = Patient::latest()->paginate(25);

        return response()->json(['success' => true, 'data' => $patients]);
    }

    public function show(int $id): JsonResponse
    {
        $patient = Patient::with(['primaryExamination', 'secondaryExamination'])->findOrFail($id);

        return response()->json(['success' => true, 'data' => $patient]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:15'],
            'gender' => ['required', 'in:male,female,other'],
        ]);

        $patient = Patient::create($validated);

        return response()->json(['success' => true, 'data' => $patient], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $patient = Patient::findOrFail($id);
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:15'],
        ]);
        $patient->update($validated);

        return response()->json(['success' => true, 'data' => $patient]);
    }

    public function destroy(int $id): JsonResponse
    {
        Patient::findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Deleted.']);
    }
}

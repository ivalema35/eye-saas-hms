<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hospital\Foc;
use App\Models\Hospital\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FocApiController extends Controller
{
    /**
     * List FOC requests.
     * Doctor: sees only their own requests.
     * Others (reception/admin): see all pending + recent.
     */
    public function index(Request $request): JsonResponse
    {
        $authUser = auth('sanctum')->user();
        $tenantId = app('tenant')->id;

        $query = Foc::with([
            'patient'        => fn ($q) => $q->withTrashed()->select('id', 'first_name', 'last_name', 'patient_code'),
            'doctor'         => fn ($q) => $q->select('id', 'name'),
            'acceptedByUser' => fn ($q) => $q->select('id', 'name'),
        ])->where('tenant_id', $tenantId);

        // Doctors only see their own FOC requests
        if ($authUser && $authUser->doctor_type !== null) {
            $query->where('doctor_id', $authUser->id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $focs = $query->latest()->paginate(20);

        return response()->json([
            'success' => true,
            'data'    => $focs,
        ]);
    }

    /**
     * Create a FOC request (doctor action).
     */
    public function store(Request $request): JsonResponse
    {
        $authUser = auth('sanctum')->user();
        $tenantId = app('tenant')->id;

        $validated = $request->validate([
            'patient_id' => ['required', 'integer'],
            'foc_fee'    => ['required', 'numeric', 'min:0'],
            'reason'     => ['required', 'string', 'max:1000'],
        ]);

        // Confirm patient belongs to this tenant
        $patientExists = Patient::where('tenant_id', $tenantId)
            ->where('id', $validated['patient_id'])
            ->exists();

        if (!$patientExists) {
            return response()->json([
                'success' => false,
                'message' => 'Patient not found.',
            ], 404);
        }

        // Duplicate guard — one active FOC per patient per day
        $alreadyExists = Foc::where('tenant_id', $tenantId)
            ->where('patient_id', $validated['patient_id'])
            ->whereDate('created_at', today())
            ->whereIn('status', ['pending', 'accepted'])
            ->exists();

        if ($alreadyExists) {
            return response()->json([
                'success' => false,
                'message' => 'An active FOC request already exists for this patient today.',
            ], 422);
        }

        $doctorId = ($authUser && $authUser->doctor_type !== null)
            ? $authUser->id
            : ($request->integer('doctor_id', 0) ?: null);

        $foc = Foc::create([
            'tenant_id'  => $tenantId,
            'patient_id' => $validated['patient_id'],
            'doctor_id'  => $doctorId,
            'foc_fee'    => $validated['foc_fee'],
            'reason'     => $validated['reason'],
            'status'     => 'pending',
            'accepted'   => false,
        ]);

        $foc->load([
            'patient' => fn ($q) => $q->withTrashed()->select('id', 'first_name', 'last_name', 'patient_code'),
            'doctor'  => fn ($q) => $q->select('id', 'name'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'FOC request submitted successfully.',
            'data'    => $foc,
        ], 201);
    }

    /**
     * Accept a pending FOC request (reception action).
     */
    public function accept(Request $request, string $slug, int $id): JsonResponse
    {
        $authUser = auth('sanctum')->user();
        $tenantId = app('tenant')->id;

        $foc = Foc::where('tenant_id', $tenantId)->find($id);

        if (!$foc) {
            return response()->json(['success' => false, 'message' => 'FOC request not found.'], 404);
        }

        if (!$foc->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'Only pending FOC requests can be accepted.',
            ], 422);
        }

        $foc->update([
            'status'      => 'accepted',
            'accepted'    => true,
            'accepted_by' => $authUser?->id,
            'accepted_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'FOC request accepted. Fee has been waived.',
            'data'    => $foc->fresh(['acceptedByUser']),
        ]);
    }

    /**
     * Reject a pending FOC request (reception action).
     */
    public function reject(Request $request, string $slug, int $id): JsonResponse
    {
        $tenantId = app('tenant')->id;

        $foc = Foc::where('tenant_id', $tenantId)->find($id);

        if (!$foc) {
            return response()->json(['success' => false, 'message' => 'FOC request not found.'], 404);
        }

        if (!$foc->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'Only pending FOC requests can be rejected.',
            ], 422);
        }

        $validated = $request->validate([
            'rejected_reason' => ['required', 'string', 'max:500'],
        ]);

        $foc->update([
            'status'          => 'rejected',
            'accepted'        => false,
            'rejected_reason' => $validated['rejected_reason'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'FOC request rejected.',
            'data'    => $foc->fresh(),
        ]);
    }
}

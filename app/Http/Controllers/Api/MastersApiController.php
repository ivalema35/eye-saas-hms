<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hospital\HospitalUser;
use App\Models\Hospital\Location;
use App\Models\Hospital\Referrer;
use App\Models\Hospital\Slot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MastersApiController extends Controller
{
    public function cases(): JsonResponse
    {
        $cases = DB::table('tbl_cases')
            ->where('tenant_id', app('tenant')->id)
            ->whereNull('deleted_at')
            ->select('id', 'case_type as name', 'case_fee as fee')
            ->orderBy('case_type')
            ->get();

        return response()->json(['success' => true, 'data' => $cases]);
    }

    public function doctors(): JsonResponse
    {
        $tenantId = app('tenant')->id;

        $doctors = HospitalUser::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNotNull('doctor_type')
                    ->orWhereHas('role', function ($r) {
                        $r->where(function ($inner) {
                            $inner->whereIn('slug', ['doctor', 'ot_doctor'])
                                ->orWhereIn('name', ['doctor', 'ot_doctor']);
                        });
                    });
            })
            ->select('id', 'name', 'doctor_type')
            ->orderBy('name')
            ->get();

        return response()->json(['success' => true, 'data' => $doctors]);
    }

    public function locations(): JsonResponse
    {
        $tenantId = app('tenant')->id;

        $locations = Location::where('tenant_id', $tenantId)
            ->select('id', 'city', 'district', 'state')
            ->orderBy('city')
            ->get();

        return response()->json(['success' => true, 'data' => $locations]);
    }

    public function storeLocation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'city'     => ['required', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'state'    => ['nullable', 'string', 'max:100'],
        ]);

        $validated['tenant_id'] = app('tenant')->id;

        $location = Location::create($validated);

        return response()->json([
            'success' => true,
            'id'      => $location->id,
            'name'    => $location->city,
            'data'    => $location,
            'message' => 'City added successfully.',
        ], 201);
    }

    public function slots(): JsonResponse
    {
        $tenantId = app('tenant')->id;

        $slots = Slot::where('tenant_id', $tenantId)
            ->select('id', 'slot_name as name', 'start_time', 'end_time')
            ->orderBy('slot_name')
            ->get();

        return response()->json(['success' => true, 'data' => $slots]);
    }

    public function referrers(): JsonResponse
    {
        $tenantId = app('tenant')->id;

        $referrers = Referrer::where('tenant_id', $tenantId)
            ->select('id', 'name', 'contact')
            ->orderBy('name')
            ->get();

        return response()->json(['success' => true, 'data' => $referrers]);
    }
}

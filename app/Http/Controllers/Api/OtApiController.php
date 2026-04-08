<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * OT (Operation Theatre) API Controller (Stub)
 *
 * API access for OT bookings and scheduling.
 * Implementation: Phase OT
 */
class OtApiController extends Controller
{
    public function bookings(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => [], 'message' => 'Coming in OT Phase']);
    }

    public function book(): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'Coming in OT Phase'], 501);
    }

    public function updateStatus(): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'Coming in OT Phase'], 501);
    }
}

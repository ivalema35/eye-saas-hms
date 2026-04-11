<?php

namespace App\Http\Controllers\Hospital\OT;

use App\Http\Controllers\Controller;
use App\Models\Hospital\OT\OtBooking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OtDischargeController extends Controller
{
    public function print(Request $request, string $slug, int $bookingId): View
    {
        $tenantId = (int) app('tenant')->id;

        $booking = OtBooking::query()
            ->with(['patient:id,patient_code,first_name,middle_name,last_name,contact_no', 'otDoctor:id,name'])
            ->findOrFail($bookingId);

        $surgery = DB::table('ot_surgeries')
            ->where('tenant_id', $tenantId)
            ->where('ot_booking_id', $booking->id)
            ->latest('id')
            ->first();

        $wardMedicines = [];
        if ($surgery && ! empty($surgery->ward_medicines)) {
            $wardMedicines = is_string($surgery->ward_medicines)
                ? (json_decode($surgery->ward_medicines, true) ?: [])
                : (array) $surgery->ward_medicines;
        }

        return view('hospital.ot.billing.discharge_print', [
            'slug' => $slug,
            'booking' => $booking,
            'surgery' => $surgery,
            'wardMedicines' => $wardMedicines,
        ]);
    }

    public function certificatePrint(Request $request, string $slug, int $bookingId): View
    {
        $tenantId = (int) app('tenant')->id;

        $booking = OtBooking::query()
            ->with(['patient:id,patient_code,first_name,middle_name,last_name,contact_no', 'otDoctor:id,name'])
            ->findOrFail($bookingId);

        $surgery = DB::table('ot_surgeries')
            ->where('tenant_id', $tenantId)
            ->where('ot_booking_id', $booking->id)
            ->latest('id')
            ->first();

        return view('hospital.ot.billing.certificate_print', [
            'slug' => $slug,
            'booking' => $booking,
            'surgery' => $surgery,
        ]);
    }

    public function medicineSlipPrint(Request $request, string $slug, int $bookingId): View
    {
        $tenantId = (int) app('tenant')->id;

        $booking = OtBooking::query()
            ->with(['patient:id,patient_code,first_name,middle_name,last_name,contact_no', 'otDoctor:id,name'])
            ->findOrFail($bookingId);

        $surgery = DB::table('ot_surgeries')
            ->where('tenant_id', $tenantId)
            ->where('ot_booking_id', $booking->id)
            ->latest('id')
            ->first();

        $wardMedicines = [];
        if ($surgery && ! empty($surgery->ward_medicines)) {
            $wardMedicines = is_string($surgery->ward_medicines)
                ? (json_decode($surgery->ward_medicines, true) ?: [])
                : (array) $surgery->ward_medicines;
        }

        return view('hospital.ot.billing.medicine_slip_print', [
            'slug' => $slug,
            'booking' => $booking,
            'surgery' => $surgery,
            'wardMedicines' => $wardMedicines,
        ]);
    }
}

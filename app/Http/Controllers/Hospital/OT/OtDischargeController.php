<?php

namespace App\Http\Controllers\Hospital\OT;

use App\Http\Controllers\Controller;
use App\Models\Hospital\OT\OtBooking;
use App\Models\Hospital\OT\OtSurgery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OtDischargeController extends Controller
{
    public function print(Request $request, string $slug, int $bookingId): View
    {
        $booking = OtBooking::query()
            ->with([
                'patient:id,patient_code,first_name,middle_name,last_name,contact_no,gender,age,location_id',
                'patient.masterCity:id,name,district_id',
                'patient.masterCity.district:id,name,state_id',
                'patient.masterCity.district.state:id,name',
                'patient.location:id,city,district,state',
                'otDoctor:id,name,registration_no,signature_path',
                'counselling:id,ot_booking_id,diagnosis',
            ])
            ->findOrFail($bookingId);

        $surgery = $this->latestSurgery($booking->id);

        return view('hospital.ot.billing.discharge_print', [
            'slug' => $slug,
            'booking' => $booking,
            'surgery' => $surgery,
            'wardMedicines' => $surgery?->medicinesForPrint() ?? [],
        ]);
    }

    public function certificatePrint(Request $request, string $slug, int $bookingId): View
    {
        $booking = OtBooking::query()
            ->with([
                'patient:id,patient_code,first_name,middle_name,last_name,contact_no,gender',
                'otDoctor:id,name,registration_no,signature_path',
            ])
            ->findOrFail($bookingId);

        $surgery = $this->latestSurgery($booking->id);

        $restDays = (int) $request->integer('rest_days', 7);
        if ($restDays < 1) {
            $restDays = 7;
        }
        if ($restDays > 90) {
            $restDays = 90;
        }

        return view('hospital.ot.billing.certificate_print', [
            'slug' => $slug,
            'booking' => $booking,
            'surgery' => $surgery,
            'restDays' => $restDays,
        ]);
    }

    public function medicineSlipPrint(Request $request, string $slug, int $bookingId): View
    {
        $booking = OtBooking::query()
            ->with(['patient:id,patient_code,location_id,first_name,middle_name,last_name,contact_no', 'patient.location:id,city,district,state', 'otDoctor:id,name'])
            ->findOrFail($bookingId);

        $surgery = $this->latestSurgery($booking->id);

        return view('hospital.ot.billing.medicine_slip_print', [
            'slug' => $slug,
            'booking' => $booking,
            'surgery' => $surgery,
            'wardMedicines' => $surgery?->medicinesForPrint() ?? [],
        ]);
    }

    /**
     * OT Workflow Upgrade — Phase 6 (Discharge Module). Post-op prescription —
     * same take-home medicines as the medicine slip, styled as a doctor's Rx.
     * See docs/OT_WORKFLOW_UPGRADE_PRD.md §6.
     */
    public function prescriptionPrint(Request $request, string $slug, int $bookingId): View
    {
        $booking = OtBooking::query()
            ->with(['patient:id,patient_code,first_name,middle_name,last_name,contact_no', 'otDoctor:id,name'])
            ->findOrFail($bookingId);

        $surgery = $this->latestSurgery($booking->id);

        return view('hospital.ot.billing.prescription_print', [
            'slug' => $slug,
            'booking' => $booking,
            'surgery' => $surgery,
            'wardMedicines' => $surgery?->medicinesForPrint() ?? [],
        ]);
    }

    /**
     * OT Workflow Upgrade — Phase 6 (Discharge Module). Lens Implant Details slip
     * — pulls from the expanded ot_lens_details (Phase 4). See docs/OT_WORKFLOW_UPGRADE_PRD.md §6.
     */
    public function lensSlipPrint(Request $request, string $slug, int $bookingId): View
    {
        $tenantId = (int) app('tenant')->id;

        $booking = OtBooking::query()
            ->with(['patient:id,patient_code,first_name,middle_name,last_name,contact_no', 'otDoctor:id,name'])
            ->findOrFail($bookingId);

        $lensDetail = DB::table('ot_lens_details')
            ->where('tenant_id', $tenantId)
            ->where('ot_booking_id', $booking->id)
            ->latest('id')
            ->first();

        return view('hospital.ot.billing.lens_slip_print', [
            'slug' => $slug,
            'booking' => $booking,
            'lensDetail' => $lensDetail,
        ]);
    }

    /**
     * OT Workflow Upgrade — Phase 6 (Discharge Module). Follow-up Appointment Slip
     * — requires the invoice's follow_up_date. See docs/OT_WORKFLOW_UPGRADE_PRD.md §6.
     */
    public function followupSlipPrint(Request $request, string $slug, int $bookingId): View
    {
        $tenantId = (int) app('tenant')->id;

        $booking = OtBooking::query()
            ->with(['patient:id,patient_code,first_name,middle_name,last_name,contact_no', 'otDoctor:id,name'])
            ->findOrFail($bookingId);

        $invoice = DB::table('ot_invoices')
            ->where('tenant_id', $tenantId)
            ->where('ot_booking_id', $booking->id)
            ->first();

        abort_if(! $invoice, 404, 'Generate the invoice/discharge first to set a follow-up date.');

        return view('hospital.ot.billing.followup_slip_print', [
            'slug' => $slug,
            'booking' => $booking,
            'invoice' => $invoice,
        ]);
    }

    /**
     * OT Workflow Upgrade — Phase 6 (Discharge Module). PDF Step 9: "the system
     * automatically generates and prints" all discharge documents together.
     * Renders every document as a framed page on one screen with a single
     * Print button, so one print job covers the full set.
     * See docs/OT_WORKFLOW_UPGRADE_PRD.md §6.
     */
    public function printAllBundle(Request $request, string $slug, int $bookingId): View
    {
        $tenantId = (int) app('tenant')->id;

        $booking = OtBooking::query()->findOrFail($bookingId);

        $hasInvoice = DB::table('ot_invoices')
            ->where('tenant_id', $tenantId)
            ->where('ot_booking_id', $booking->id)
            ->exists();

        abort_if(! $hasInvoice, 404, 'Generate the invoice first before printing the discharge bundle.');

        $documents = [
            ['label' => 'Invoice', 'route' => 'hospital.ot.invoice.print'],
            ['label' => 'Discharge Summary', 'route' => 'hospital.ot.discharge.print'],
            ['label' => 'Bill of Summary', 'route' => 'hospital.ot.summary-bill.print'],
            ['label' => 'Surgery Certificate', 'route' => 'hospital.ot.certificate.print'],
            ['label' => 'Prescription', 'route' => 'hospital.ot.prescription.print'],
            ['label' => 'Lens Implant Details', 'route' => 'hospital.ot.lens-slip.print'],
            ['label' => 'Take-Home Medicine Slip', 'route' => 'hospital.ot.medicine-slip.print'],
            ['label' => 'Follow-up Appointment Slip', 'route' => 'hospital.ot.followup-slip.print'],
        ];

        return view('hospital.ot.billing.print_all', [
            'slug' => $slug,
            'booking' => $booking,
            'documents' => $documents,
        ]);
    }

    private function latestSurgery(int $bookingId): ?OtSurgery
    {
        $tenantId = (int) app('tenant')->id;

        return OtSurgery::query()
            ->with(['medicines.medicine'])
            ->where('tenant_id', $tenantId)
            ->where('ot_booking_id', $bookingId)
            ->latest('id')
            ->first();
    }
}

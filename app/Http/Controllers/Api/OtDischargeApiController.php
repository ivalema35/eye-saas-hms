<?php

/**
 * OtDischargeApiController.php
 *
 * PURPOSE: Mobile/tablet API mirror of Hospital\OT\OtInvoiceController +
 *          Hospital\OT\OtDischargeController (web) — Phase 6 (Discharge Documents).
 *          See docs/OT_WORKFLOW_UPGRADE_PRD.md §5/§6 and
 *          docs/ROUND3_OT_MOBILE_API_PRD_PLAN.md §11 (FR-OT-33/35/36).
 *
 * PDF GENERATION: reuses the exact same blade views the web prints from, rendered
 * server-side via Barryvdh\DomPDF (same pattern already used by
 * Api\ReportsApiController::exportPdf) — not a separate mobile-only template.
 *
 * NOT IN THE ORIGINAL PLAN, ADDED: invoice generate() and billing bookings() list —
 * the plan doc only listed the 9 read-only print endpoints (FR-OT-35/36); without
 * generate(), mobile has no way to actually create the invoice that gates every
 * other document below, so it was added to make this phase functionally complete.
 *
 * DEVIATION: printAllBundle() does NOT return one merged PDF. The web version
 * (`hospital.ot.billing.print_all` blade) is a browser-only page that iframes each
 * individual print route for the user to print one-by-one — dompdf cannot execute
 * that (no JS/iframe support), and building real server-side PDF merging would be
 * new backend behavior beyond mirroring what already exists. Instead this returns a
 * JSON manifest of the 8 individual download URLs; the mobile app fetches/merges/
 * shares them itself. Flag to the user if a true single merged PDF is wanted later
 * — that would need a PDF-merge package added as a new dependency.
 *
 * PERMISSIONS: ot.billing.manage (single permission gates the whole group — verified
 *              against routes/hospital.php; the shipped PRD's per-endpoint
 *              ot.invoice.view split does not exist in the real route gating).
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hospital\OT\OtBooking;
use App\Models\Hospital\OT\OtSurgery;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class OtDischargeApiController extends Controller
{
    public function bookings(): JsonResponse
    {
        $tenantId = (int) app('tenant')->id;

        $bookings = OtBooking::query()
            ->where('tenant_id', $tenantId)
            ->with(['patient:id,first_name,middle_name,last_name,contact_no'])
            ->whereIn('ot_status', ['operated', 'discharged'])
            ->orderByDesc('surgery_date')
            ->orderByDesc('id')
            ->paginate(25);

        $invoiceBookingIds = DB::table('ot_invoices')
            ->where('tenant_id', $tenantId)
            ->pluck('ot_booking_id')
            ->map(static fn ($id) => (int) $id)
            ->all();

        return response()->json([
            'success' => true,
            'data' => $bookings,
            'meta' => ['invoice_booking_ids' => $invoiceBookingIds],
        ]);
    }

    public function generateInvoice(string $slug, Request $request, int $bookingId): JsonResponse
    {
        $tenantId = (int) app('tenant')->id;

        $booking = OtBooking::query()->where('tenant_id', $tenantId)->find($bookingId);
        if (! $booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found.'], 404);
        }

        $counselling = DB::table('ot_counselling')->where('tenant_id', $tenantId)->where('ot_booking_id', $booking->id)->first();
        $lensDetail = DB::table('ot_lens_details')->where('tenant_id', $tenantId)->where('ot_booking_id', $booking->id)->latest('id')->first();
        $chargeHeads = DB::table('ot_charge_heads')->where('tenant_id', $tenantId)->where('is_active', true)->orderBy('id')->get(['charge_name', 'percentage']);

        $validated = $request->validate([
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'follow_up_date' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        $packageAmount = (float) ($counselling?->package_amount ?? $booking->package_amount ?? 0);
        $lensMrp = (float) ($lensDetail?->lens_mrp ?? 0);

        $hasCounsellingBreakdown = $counselling && (
            (float) ($counselling->lens_cost ?? 0) > 0
            || (float) ($counselling->ot_charges ?? 0) > 0
            || (float) ($counselling->surgeon_charges ?? 0) > 0
            || (float) ($counselling->nursing_charges ?? 0) > 0
            || (float) ($counselling->consumables_charges ?? 0) > 0
        );

        $lineItems = [];

        if ($hasCounsellingBreakdown) {
            $breakdown = [
                'Lens Charges' => (float) ($counselling->lens_cost ?? $lensMrp),
                'OT Charges' => (float) ($counselling->ot_charges ?? 0),
                'Surgeon Charges' => (float) ($counselling->surgeon_charges ?? 0),
                'Nursing Charges' => (float) ($counselling->nursing_charges ?? 0),
                'Consumables' => (float) ($counselling->consumables_charges ?? 0),
            ];

            foreach ($breakdown as $head => $amount) {
                if ($amount > 0) {
                    $lineItems[] = ['head' => $head, 'percentage' => null, 'amount' => round($amount, 2)];
                }
            }
        } else {
            $remainingAmount = max(0, round($packageAmount - $lensMrp, 2));

            if ($lensMrp > 0) {
                $lineItems[] = ['head' => 'Lens Charges', 'percentage' => null, 'amount' => round($lensMrp, 2)];
            }

            if ($remainingAmount > 0) {
                if ($chargeHeads->isNotEmpty()) {
                    $distributed = 0.0;
                    $lastIndex = $chargeHeads->count() - 1;

                    foreach ($chargeHeads as $index => $chargeHead) {
                        $percentage = (float) $chargeHead->percentage;

                        if ($index === $lastIndex) {
                            $headAmount = max(0, round($remainingAmount - $distributed, 2));
                        } else {
                            $headAmount = round($remainingAmount * ($percentage / 100), 2);
                            $distributed += $headAmount;
                        }

                        $lineItems[] = ['head' => $chargeHead->charge_name, 'percentage' => $percentage, 'amount' => $headAmount];
                    }
                } else {
                    $lineItems[] = ['head' => 'OT Remaining Charges', 'percentage' => null, 'amount' => $remainingAmount];
                }
            }
        }

        $totalAmount = round(collect($lineItems)->sum(fn (array $item): float => (float) ($item['amount'] ?? 0)), 2);
        $roundingDiff = round($packageAmount - $totalAmount, 2);
        if ($roundingDiff !== 0.0 && ! empty($lineItems)) {
            $lastItemIndex = count($lineItems) - 1;
            $lineItems[$lastItemIndex]['amount'] = round((float) $lineItems[$lastItemIndex]['amount'] + $roundingDiff, 2);
            $totalAmount = round($packageAmount, 2);
        }

        $taxAmount = (float) ($validated['tax_amount'] ?? 0);
        $discount = (float) ($validated['discount'] ?? 0);
        $netAmount = max(0, $totalAmount + $taxAmount - $discount);

        $existingInvoice = DB::table('ot_invoices')->where('tenant_id', $tenantId)->where('ot_booking_id', $booking->id)->first();
        $invoiceNumber = $existingInvoice?->invoice_number ?: $this->generateUniqueInvoiceNumber($tenantId);
        $followUpDate = $validated['follow_up_date'] ?? $existingInvoice?->follow_up_date ?? now()->addDays(7)->toDateString();

        DB::transaction(function () use ($tenantId, $booking, $invoiceNumber, $lineItems, $totalAmount, $taxAmount, $discount, $netAmount, $followUpDate, $existingInvoice): void {
            $payload = [
                'invoice_number' => $invoiceNumber,
                'line_items' => json_encode($lineItems, JSON_UNESCAPED_UNICODE),
                'total_amount' => $totalAmount,
                'tax_amount' => $taxAmount,
                'discount' => $discount,
                'net_amount' => $netAmount,
                'follow_up_date' => $followUpDate,
                'is_finalized' => true,
                'generated_by' => (int) auth('sanctum')->id(),
                'updated_at' => now(),
            ];

            if ($existingInvoice) {
                DB::table('ot_invoices')->where('tenant_id', $tenantId)->where('ot_booking_id', $booking->id)->update($payload);
            } else {
                DB::table('ot_invoices')->insert([...$payload, 'tenant_id' => $tenantId, 'ot_booking_id' => $booking->id, 'created_at' => now()]);
            }

            $booking->update(['ot_status' => 'discharged', 'discharged_at' => now()]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Invoice generated successfully and patient marked as discharged.',
            'data' => ['invoice_number' => $invoiceNumber, 'total_amount' => $totalAmount, 'net_amount' => $netAmount],
        ], 201);
    }

    public function invoicePrint(string $slug, int $bookingId): Response
    {
        $tenantId = (int) app('tenant')->id;

        $booking = OtBooking::query()->where('tenant_id', $tenantId)
            ->with(['patient:id,patient_code,first_name,middle_name,last_name,contact_no', 'otDoctor:id,name'])
            ->find($bookingId);
        if (! $booking) {
            abort(404, 'Booking not found.');
        }

        $invoice = DB::table('ot_invoices')->where('tenant_id', $tenantId)->where('ot_booking_id', $booking->id)->first();
        abort_if(! $invoice, 404, 'Invoice not found for this booking.');

        $lineItems = is_string($invoice->line_items) ? (json_decode($invoice->line_items, true) ?: []) : (array) $invoice->line_items;

        return Pdf::loadView('hospital.ot.billing.invoice_print', compact('booking', 'invoice', 'lineItems'))
            ->download("Invoice_{$bookingId}.pdf");
    }

    public function summaryBillPrint(string $slug, int $bookingId): Response
    {
        $tenantId = (int) app('tenant')->id;

        $booking = OtBooking::query()->where('tenant_id', $tenantId)
            ->with([
                'patient:id,patient_code,first_name,middle_name,last_name,contact_no,location_id',
                'patient.masterCity:id,name',
                'patient.location:id,city,district,state',
                'otDoctor:id,name',
            ])
            ->find($bookingId);
        if (! $booking) {
            abort(404, 'Booking not found.');
        }

        $invoice = DB::table('ot_invoices')->where('tenant_id', $tenantId)->where('ot_booking_id', $booking->id)->first();
        abort_if(! $invoice, 404, 'Summary bill not found for this booking.');

        $lineItems = is_string($invoice->line_items) ? (json_decode($invoice->line_items, true) ?: []) : (array) $invoice->line_items;

        return Pdf::loadView('hospital.ot.billing.summary_bill_print', compact('booking', 'invoice', 'lineItems'))
            ->download("SummaryBill_{$bookingId}.pdf");
    }

    public function dischargePrint(string $slug, int $bookingId): Response
    {
        $booking = $this->findWithDischargeRelations($bookingId);
        $surgery = $this->latestSurgery($bookingId);

        return Pdf::loadView('hospital.ot.billing.discharge_print', [
            'booking' => $booking,
            'surgery' => $surgery,
            'wardMedicines' => $surgery?->medicinesForPrint() ?? [],
        ])->download("Discharge_{$bookingId}.pdf");
    }

    public function certificatePrint(string $slug, Request $request, int $bookingId): Response
    {
        $tenantId = (int) app('tenant')->id;
        $booking = OtBooking::query()->where('tenant_id', $tenantId)
            ->with(['patient:id,patient_code,first_name,middle_name,last_name,contact_no,gender', 'otDoctor:id,name,registration_no,signature_path'])
            ->find($bookingId);
        if (! $booking) {
            abort(404, 'Booking not found.');
        }

        $surgery = $this->latestSurgery($bookingId);
        $restDays = max(1, min(90, (int) $request->integer('rest_days', 7)));

        return Pdf::loadView('hospital.ot.billing.certificate_print', compact('booking', 'surgery', 'restDays'))
            ->download("Certificate_{$bookingId}.pdf");
    }

    public function medicineSlipPrint(string $slug, int $bookingId): Response
    {
        $tenantId = (int) app('tenant')->id;
        $booking = OtBooking::query()->where('tenant_id', $tenantId)
            ->with(['patient:id,patient_code,location_id,first_name,middle_name,last_name,contact_no', 'patient.location:id,city,district,state', 'otDoctor:id,name'])
            ->find($bookingId);
        if (! $booking) {
            abort(404, 'Booking not found.');
        }

        $surgery = $this->latestSurgery($bookingId);

        return Pdf::loadView('hospital.ot.billing.medicine_slip_print', [
            'booking' => $booking,
            'surgery' => $surgery,
            'wardMedicines' => $surgery?->medicinesForPrint() ?? [],
        ])->download("MedicineSlip_{$bookingId}.pdf");
    }

    public function prescriptionPrint(string $slug, int $bookingId): Response
    {
        $tenantId = (int) app('tenant')->id;
        $booking = OtBooking::query()->where('tenant_id', $tenantId)
            ->with(['patient:id,patient_code,first_name,middle_name,last_name,contact_no', 'otDoctor:id,name'])
            ->find($bookingId);
        if (! $booking) {
            abort(404, 'Booking not found.');
        }

        $surgery = $this->latestSurgery($bookingId);

        return Pdf::loadView('hospital.ot.billing.prescription_print', [
            'booking' => $booking,
            'surgery' => $surgery,
            'wardMedicines' => $surgery?->medicinesForPrint() ?? [],
        ])->download("Prescription_{$bookingId}.pdf");
    }

    public function lensSlipPrint(string $slug, int $bookingId): Response
    {
        $tenantId = (int) app('tenant')->id;
        $booking = OtBooking::query()->where('tenant_id', $tenantId)
            ->with(['patient:id,patient_code,first_name,middle_name,last_name,contact_no', 'otDoctor:id,name'])
            ->find($bookingId);
        if (! $booking) {
            abort(404, 'Booking not found.');
        }

        $lensDetail = DB::table('ot_lens_details')->where('tenant_id', $tenantId)->where('ot_booking_id', $booking->id)->latest('id')->first();

        return Pdf::loadView('hospital.ot.billing.lens_slip_print', compact('booking', 'lensDetail'))
            ->download("LensSlip_{$bookingId}.pdf");
    }

    public function followupSlipPrint(string $slug, int $bookingId): Response
    {
        $tenantId = (int) app('tenant')->id;
        $booking = OtBooking::query()->where('tenant_id', $tenantId)
            ->with(['patient:id,patient_code,first_name,middle_name,last_name,contact_no', 'otDoctor:id,name'])
            ->find($bookingId);
        if (! $booking) {
            abort(404, 'Booking not found.');
        }

        $invoice = DB::table('ot_invoices')->where('tenant_id', $tenantId)->where('ot_booking_id', $booking->id)->first();
        abort_if(! $invoice, 404, 'Generate the invoice/discharge first to set a follow-up date.');

        return Pdf::loadView('hospital.ot.billing.followup_slip_print', compact('booking', 'invoice'))
            ->download("FollowupSlip_{$bookingId}.pdf");
    }

    /**
     * See class docblock: no server-side merged PDF (would need a new dependency).
     * Returns a manifest of the 8 individual document download URLs instead.
     */
    public function printAllBundle(string $slug, int $bookingId): JsonResponse
    {
        $tenantId = (int) app('tenant')->id;

        $booking = OtBooking::query()->where('tenant_id', $tenantId)->find($bookingId);
        if (! $booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found.'], 404);
        }

        $hasInvoice = DB::table('ot_invoices')->where('tenant_id', $tenantId)->where('ot_booking_id', $booking->id)->exists();
        if (! $hasInvoice) {
            return response()->json(['success' => false, 'message' => 'Generate the invoice first before printing the discharge bundle.'], 422);
        }

        $documents = [
            ['label' => 'Invoice', 'route_name' => 'api.v1.hospital.ot.print.invoice'],
            ['label' => 'Discharge Summary', 'route_name' => 'api.v1.hospital.ot.print.discharge'],
            ['label' => 'Bill of Summary', 'route_name' => 'api.v1.hospital.ot.print.summary-bill'],
            ['label' => 'Surgery Certificate', 'route_name' => 'api.v1.hospital.ot.print.certificate'],
            ['label' => 'Prescription', 'route_name' => 'api.v1.hospital.ot.print.prescription'],
            ['label' => 'Lens Implant Details', 'route_name' => 'api.v1.hospital.ot.print.lens-slip'],
            ['label' => 'Take-Home Medicine Slip', 'route_name' => 'api.v1.hospital.ot.print.medicine-slip'],
            ['label' => 'Follow-up Appointment Slip', 'route_name' => 'api.v1.hospital.ot.print.followup-slip'],
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'documents' => collect($documents)->map(fn ($doc) => [
                    'label' => $doc['label'],
                    'download_url' => route($doc['route_name'], ['slug' => request()->route('slug'), 'id' => $bookingId]),
                ])->values(),
            ],
        ]);
    }

    private function findWithDischargeRelations(int $bookingId): OtBooking
    {
        $tenantId = (int) app('tenant')->id;

        $booking = OtBooking::query()->where('tenant_id', $tenantId)
            ->with([
                'patient:id,patient_code,first_name,middle_name,last_name,contact_no,gender,age,location_id',
                'patient.masterCity:id,name,district_id',
                'patient.masterCity.district:id,name,state_id',
                'patient.masterCity.district.state:id,name',
                'patient.location:id,city,district,state',
                'otDoctor:id,name,registration_no,signature_path',
                'counselling:id,ot_booking_id,diagnosis',
            ])
            ->find($bookingId);

        abort_if(! $booking, 404, 'Booking not found.');

        return $booking;
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

    private function generateUniqueInvoiceNumber(int $tenantId): string
    {
        do {
            $candidate = sprintf('INV-OT-%s-%04d', now()->format('Ymd'), random_int(1, 9999));
            $exists = DB::table('ot_invoices')->where('tenant_id', $tenantId)->where('invoice_number', $candidate)->exists();
        } while ($exists);

        return $candidate;
    }
}

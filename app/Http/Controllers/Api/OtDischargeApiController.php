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
 * PERMISSIONS: routes are split by endpoint semantics (invoice view, bill print,
 *              discharge generation/finalization, certificate print). The legacy
 *              ot_billing_manage grant remains an OR fallback for web compatibility.
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Hospital\OT\Concerns\FiltersOtDeskLists;
use App\Models\Hospital\OT\OtBooking;
use App\Models\Hospital\OT\OtDischargeSummary;
use App\Models\Hospital\OT\OtSurgery;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class OtDischargeApiController extends Controller
{
    use FiltersOtDeskLists;

    public function bookings(Request $request): JsonResponse
    {
        $tenantId = (int) app('tenant')->id;
        $activeFilter = $this->resolveOtDeskFilter($request);
        $isHistory = $activeFilter === 'history';
        [$fromDate, $toDate] = $this->resolveOtDeskDateRange($request, $isHistory);

        $query = OtBooking::query()
            ->where('tenant_id', $tenantId)
            ->with([
                'patient:id,patient_code,first_name,middle_name,last_name,contact_no',
                'otDoctor:id,name',
                'payments',
            ]);

        if ($isHistory) {
            $query->whereIn('ot_status', [
                OtBooking::STATUS_DISCHARGED,
                'DISCHARGED',
            ]);
            $this->applyOtDeskDateRange($query, $fromDate, $toDate);
            $query->orderByDesc('surgery_date')->orderByDesc('id');
        } else {
            $query->whereIn('ot_status', [
                OtBooking::STATUS_OPERATED,
                'OPERATED',
            ])
                ->orderByDesc('surgery_date')
                ->orderByDesc('id');
        }

        $bookings = $query->paginate((int) $request->integer('per_page', (int) config('app.pagination_limit', 25)));

        $invoiceBookingIds = DB::table('ot_invoices')
            ->where('tenant_id', $tenantId)
            ->pluck('ot_booking_id')
            ->map(static fn ($id) => (int) $id)
            ->all();

        // Append full_name (accessor, not auto-serialized) and the per-row
        // Generated/Pending invoice flag — mirrors web's $invoiceBookingIds
        // membership check so the app can render the same badge without a
        // second round trip per row. Also append discharge-print progress
        // (web pull 2026-09-28) so the desk list can show "Prints 2/3" the
        // same way `hospital/ot/billing/index.blade.php` now does, instead
        // of the old (now-wrong) assumption that having an invoice means
        // discharged.
        $bookings->getCollection()->each(function (OtBooking $b) use ($invoiceBookingIds) {
            $b->patient?->append('full_name');
            $b->has_invoice = in_array($b->id, $invoiceBookingIds, true);
            $b->discharge_prints_done = $b->dischargePrintsDoneCount();
            $b->discharge_prints_total = count(OtBooking::DISCHARGE_PRINTS);
            // summary_bill_printed_at/discharge_printed_at/certificate_printed_at
            // are real, uncast-hidden columns on OtBooking — already
            // serialized automatically, no extra work needed here for the
            // app to show a per-document checkmark.
        });

        return response()->json([
            'success' => true,
            'data' => $bookings,
            'meta' => [
                'filter' => $activeFilter,
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'invoice_booking_ids' => $invoiceBookingIds,
            ],
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
        });

        return response()->json([
            'success' => true,
            'message' => 'Invoice generated. Print Bill Summary, Discharge and Certificate to complete the discharge.',
            'data' => ['invoice_number' => $invoiceNumber, 'total_amount' => $totalAmount, 'net_amount' => $netAmount],
        ], 201);
    }

    /**
     * Invoice detail for a booking — not in the original PRD skeleton, added
     * so the app can tell whether an invoice already exists (and show its
     * summary/line items) instead of only ever seeing the one-shot
     * generateInvoice() response. See
     * OT_DISCHARGE_INVOICES_WEB_PARITY_FIX_PLAN.md TASK 2.2.
     * Returns `data: null` (200) when no invoice exists yet — this is a
     * normal "not generated" state, not an error.
     */
    public function invoiceDetail(string $slug, int $bookingId): JsonResponse
    {
        $tenantId = (int) app('tenant')->id;

        $booking = OtBooking::query()->where('tenant_id', $tenantId)->find($bookingId);
        if (! $booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found.'], 404);
        }

        $invoice = OtDischargeSummary::query()
            ->where('tenant_id', $tenantId)
            ->where('ot_booking_id', $booking->id)
            ->first();

        return response()->json([
            'success' => true,
            'data' => $invoice,
            // Web pull 2026-09-28: invoice generation no longer auto-discharges
            // the booking — it only flips to `discharged` once all 3
            // documents below are printed (OtBooking::markDischargePrinted()).
            // The app re-fetches this endpoint after each print action to
            // show live progress instead of assuming "discharged" the moment
            // the invoice exists.
            'booking' => [
                'ot_status' => $booking->ot_status,
                'discharged_at' => $booking->discharged_at,
                'summary_bill_printed_at' => $booking->summary_bill_printed_at,
                'discharge_printed_at' => $booking->discharge_printed_at,
                'certificate_printed_at' => $booking->certificate_printed_at,
                'discharge_prints_done' => $booking->dischargePrintsDoneCount(),
                'discharge_prints_total' => count(OtBooking::DISCHARGE_PRINTS),
            ],
        ]);
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

        return Pdf::loadView('hospital.ot.billing.invoice_print', compact('booking', 'invoice', 'lineItems', 'slug'))
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

        $html = $this->domPdfSafeHtml('hospital.ot.billing.summary_bill_print', compact('booking', 'invoice', 'lineItems'));
        $booking->markDischargePrinted('summary_bill');

        return Pdf::loadHTML($html)
            ->setPaper('a5', 'portrait')
            ->download("SummaryBill_{$bookingId}.pdf");
    }

    public function dischargePrint(string $slug, int $bookingId): Response
    {
        $booking = $this->findWithDischargeRelations($bookingId);
        $surgery = $this->latestSurgery($bookingId);

        $html = $this->domPdfSafeHtml('hospital.ot.billing.discharge_print', [
            'booking' => $booking,
            'surgery' => $surgery,
            'wardMedicines' => $surgery?->medicinesForPrint() ?? [],
        ]);
        $booking->markDischargePrinted('discharge');

        return Pdf::loadHTML($html)
            ->setPaper('a5', 'portrait')
            ->download("Discharge_{$bookingId}.pdf");
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
        // Web resets an out-of-range low value back to the default (7), not up
        // to 1 — OtDischargeController::certificatePrint.
        $restDaysInput = (int) $request->integer('rest_days', 7);
        $restDays = $restDaysInput < 1 ? 7 : min(90, $restDaysInput);

        $html = $this->domPdfSafeHtml('hospital.ot.billing.certificate_print', compact('booking', 'surgery', 'restDays'));
        $booking->markDischargePrinted('certificate');

        return Pdf::loadHTML($html)
            ->setPaper('a5', 'portrait')
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
            'slug' => $slug,
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

        return Pdf::loadView('hospital.ot.billing.lens_slip_print', compact('booking', 'lensDetail', 'slug'))
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

        return Pdf::loadView('hospital.ot.billing.followup_slip_print', compact('booking', 'invoice', 'slug'))
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

    /**
     * ROOT CAUSE, confirmed 2026-09-29 by generating real PDFs through this
     * exact method (not just reading the transformed HTML) and bisecting the
     * blade element-by-element until isolated: this DomPDF install overflows
     * the page whenever a block element that sits DIRECTLY under `<body>` is
     * given an explicit `width: 100%` — regardless of `box-sizing`, and
     * regardless of `@page`/`@media` rules (both already ruled out in
     * earlier passes; do not re-test them). It behaves as if that 100% is
     * resolved against the full physical page box rather than the printable
     * area, so any padding on that same element pushes its right edge past
     * the page — exactly the "Amount column / sign-block cut off on the
     * right" bug reported repeatedly. The three previous "fixes" here
     * (`@media` stripping, forcing `box-sizing: border-box`) were each
     * real/harmless but never addressed this, which is why the bug kept
     * coming back. The actual fix: give that top-level wrapper NO explicit
     * width at all — a block's default `width: auto` already fills its
     * container minus its own padding, correctly, in this DomPDF install (a
     * `.page` div declared with `width: 100%` ONE LEVEL DEEPER, i.e. not a
     * direct child of `<body>`, was confirmed fine in the same bisection —
     * this bug is specific to the outermost wrapper).
     * If this ever regresses again: reproduce via a real generated PDF
     * (Read tool renders PDFs visually) before touching CSS again, and
     * suspect any `width: 100%` added back onto this specific wrapper first.
     *
     * Separately (unrelated to the above, real, harmless): the
     * `hospital.ot.billing.*_print` blades hide a floating "Print" button and
     * a browser-preview-only backdrop behind `@media print` / `@media
     * screen` — correct for a real browser but meaningless for a
     * server-rendered PDF, so both are stripped here too, and the blade's own
     * `@page` margin is replaced by an explicit wrapper (below) since DomPDF
     * did not reliably honor `@page { margin }` in this install either. The
     * blade file itself, and web's own browser-rendered print/preview (which
     * never goes through this code path), are completely unaffected by any
     * of this.
     */
    private function domPdfSafeHtml(string $view, array $data): string
    {
        $html = view($view, $data)->render();
        $html = preg_replace('/<button[^>]*class="print-btn"[^>]*>.*?<\/button>/s', '', $html) ?? $html;
        $html = preg_replace('/@media\s+screen\s*\{(?:[^{}]|\{[^{}]*\})*\}/s', '', $html) ?? $html;
        $html = preg_replace('/@media\s+print\s*\{(?:[^{}]|\{[^{}]*\})*\}/s', '', $html) ?? $html;

        // The wrapper's padding gives the page its margin (replacing the
        // blade's own unreliable `@page { margin }`). Deliberately NO
        // `width`/`box-sizing` here — see docblock above; adding either back
        // reproduces the right-edge cut-off bug.
        $html = preg_replace(
            '/<body([^>]*)>(.*)<\/body>/is',
            '<body$1><div style="padding:14mm;">$2</div></body>',
            $html,
            1
        ) ?? $html;
        $html = preg_replace(
            '/<\/head>/i',
            '<style>html,body{margin:0 !important;}.page{width:100% !important;max-width:100% !important;margin:0 !important;padding:0 !important;box-sizing:border-box !important;}</style></head>',
            $html,
            1
        ) ?? $html;

        return $html;
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

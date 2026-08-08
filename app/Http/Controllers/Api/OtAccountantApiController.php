<?php

/**
 * OtAccountantApiController.php
 *
 * PURPOSE: Mobile/tablet API mirror of Hospital\OT\OtAccountantController (web) —
 *          Phase 5 (Billing / Payment). See docs/OT_WORKFLOW_UPGRADE_PRD.md §5 and
 *          docs/ROUND3_OT_MOBILE_API_PRD_PLAN.md §9 (FR-OT-33/34).
 *
 * CORRECTION vs. the plan doc: FR-OT-34 ("Counsellor Payment Verification /
 * verify-payment") was marked blocked in the plan because no standalone
 * `OtCounsellorController::verifyPayment()` exists. Reading OtAccountantController
 * more closely: the auto-advance-to-payment_verified behavior IS already live —
 * it happens automatically inside storePayment() the moment cumulative payments
 * reach the package amount (docs/tulsi.md §3's proposal, apparently already shipped).
 * So there is nothing separate to build for FR-OT-34 — it's covered by storePayment()
 * below, same pattern as Phase 4's checklist-inside-storeSurgery() finding.
 *
 * ALSO NOT IN THE ORIGINAL PLAN: the plan doc only listed a read-only payment-status
 * endpoint (FR-OT-33). It missed that Accountant also needs to actually *record*
 * payments from mobile/tablet — added paymentFormData()/storePayment()/receipt()
 * to mirror OtAccountantController::createPayment()/storePayment()/receiptPrint().
 *
 * PERMISSIONS: ot.invoice.view (payment-status), ot.payment.record (bookings queue,
 *              payment form, store, receipt — matches the web accountant route
 *              group's single permission gate).
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hospital\OT\OtBooking;
use App\Models\Hospital\OT\OtPayment;
use App\Models\Hospital\OT\OtRefund;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class OtAccountantApiController extends Controller
{
    public function paymentStatus(string $slug, int $bookingId): JsonResponse
    {
        $tenantId = (int) app('tenant')->id;

        $booking = OtBooking::query()
            ->where('tenant_id', $tenantId)
            ->with(['payments' => fn ($q) => $q->with('recordedBy:id,name')->latest('paid_at')])
            ->find($bookingId);

        if (! $booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                // payment_status/remaining_balance are model accessors — computed from
                // payments vs. package_amount, never a stored column (OtBooking.php).
                'payment_status' => $booking->payment_status,
                'required_total' => (float) ($booking->package_amount ?? 0),
                'total_paid' => (float) $booking->payments->sum('package_amount'),
                'remaining_balance' => $booking->remaining_balance,
                'payments' => $booking->payments->values(),
            ],
        ]);
    }

    public function bookings(Request $request): JsonResponse
    {
        $tenantId = (int) app('tenant')->id;
        $filter = strtolower((string) $request->query('filter', 'today'));
        if ($filter === 'all') {
            $filter = 'completed';
        }
        if (! in_array($filter, ['today', 'completed', 'refunds'], true)) {
            $filter = 'today';
        }

        $query = OtBooking::query()
            ->where('tenant_id', $tenantId)
            ->with(['patient:id,patient_code,location_id,first_name,middle_name,last_name,contact_no', 'patient.location:id,city,district,state', 'payments', 'refunds']);

        if ($filter === 'today') {
            // Pending payment queue — all counselled / partial-paid bookings awaiting
            // collection. surgery_date is often null after Recommend Surgery (date set
            // later), so do not require surgery_date = today or it never appears here.
            // Matches web exactly (web pull 2026-08-07). See
            // WEB_PULL_2026_08_07_APP_PARITY_AUDIT.md §4/§9.
            $query->whereIn('ot_status', [OtBooking::STATUS_COUNSELLED, OtBooking::STATUS_PAID])
                ->orderByRaw('CASE WHEN surgery_date IS NULL THEN 0 ELSE 1 END')
                ->orderBy('surgery_date')
                ->orderByDesc('id');
        } elseif ($filter === 'refunds') {
            // Surgery refused — full refund pending or done.
            $query->where('ot_status', OtBooking::STATUS_SURGERY_REFUSED)
                ->orderByDesc('updated_at')
                ->orderByDesc('id');
        } else {
            $query->whereIn('ot_status', [
                OtBooking::STATUS_PAYMENT_VERIFIED,
                OtBooking::STATUS_IN_WARD,
                OtBooking::STATUS_DILATED,
                OtBooking::STATUS_READY,
                OtBooking::STATUS_OPERATED,
                OtBooking::STATUS_DISCHARGED,
                OtBooking::STATUS_SURGERY_REFUSED,
            ])->orderByDesc('surgery_date')->orderByDesc('id');
        }

        $bookings = $query->paginate((int) $request->integer('per_page', 20));

        // payment_status/remaining_balance are Eloquent accessors, not real
        // columns — raw model serialization omits them unless explicitly
        // appended per-instance (NOT via the model's global $appends, which
        // would break OtCounsellorApiController::bookings() — it doesn't
        // eager-load `payments`, so the accessor would trigger a lazy-load
        // per row there). `payments` is already eager-loaded above, so this
        // is free here.
        // `refunds` is already eager-loaded above (every filter, matching
        // web), so appending the refund accessors here is free too — the
        // `refunds` filter/UI needs `refundable_balance` to know whether a
        // row is still refundable.
        $bookings->getCollection()->each(fn (OtBooking $b) => $b->append(['payment_status', 'remaining_balance', 'total_paid', 'total_refunded', 'refundable_balance']));

        // Matches web's Accountant dashboard money summary card exactly.
        $moneySummary = [
            'collected' => (float) OtPayment::query()->where('tenant_id', $tenantId)->sum('package_amount'),
            'refunded' => (float) OtRefund::query()->where('tenant_id', $tenantId)->sum('amount'),
            'refunds_pending' => OtBooking::query()
                ->where('tenant_id', $tenantId)
                ->where('ot_status', OtBooking::STATUS_SURGERY_REFUSED)
                ->with(['payments', 'refunds'])
                ->get()
                ->filter(fn (OtBooking $b) => ! $b->isFullyRefunded() && $b->refundable_balance > 0)
                ->count(),
        ];
        $moneySummary['net'] = round($moneySummary['collected'] - $moneySummary['refunded'], 2);

        return response()->json(['success' => true, 'data' => $bookings, 'meta' => ['filter' => $filter, 'money_summary' => $moneySummary]]);
    }

    public function paymentFormData(string $slug, int $bookingId): JsonResponse
    {
        $tenantId = (int) app('tenant')->id;

        $booking = OtBooking::query()
            ->where('tenant_id', $tenantId)
            ->with([
                'patient:id,patient_code,location_id,first_name,middle_name,last_name,contact_no',
                'patient.location:id,city,district,state',
                'payments',
            ])
            ->find($bookingId);

        if (! $booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found.'], 404);
        }

        $counselling = DB::table('ot_counselling')
            ->where('tenant_id', $tenantId)
            ->where('ot_booking_id', $booking->id)
            ->first();

        $requiredTotal = (float) ($counselling?->package_amount ?? $booking->package_amount ?? 0);
        $totalPaidSoFar = (float) $booking->payments->sum('package_amount');

        // Ensure a billing invoice exists so the receipt/invoice number shown is real —
        // same lazy-create-on-first-payment-visit behavior as the web form.
        $invoice = DB::table('ot_invoices')
            ->where('tenant_id', $tenantId)
            ->where('ot_booking_id', $booking->id)
            ->orderByDesc('id')
            ->first();

        if (! $invoice) {
            $invoiceNumber = $this->generateUniqueInvoiceNumber($tenantId);
            $now = now();
            $payload = [
                'tenant_id' => $tenantId,
                'ot_booking_id' => $booking->id,
                'invoice_number' => $invoiceNumber,
                'line_items' => json_encode([
                    ['head' => 'OT Package', 'percentage' => null, 'amount' => round($requiredTotal, 2)],
                ], JSON_UNESCAPED_UNICODE),
                'total_amount' => round($requiredTotal, 2),
                'tax_amount' => 0,
                'discount' => 0,
                'net_amount' => round($requiredTotal, 2),
                'generated_by' => (int) auth('sanctum')->id(),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (Schema::hasColumn('ot_invoices', 'is_finalized')) {
                $payload['is_finalized'] = false;
            }

            DB::table('ot_invoices')->insert($payload);

            $invoice = DB::table('ot_invoices')
                ->where('tenant_id', $tenantId)
                ->where('ot_booking_id', $booking->id)
                ->orderByDesc('id')
                ->first();
        }

        $hasMediclaim = (bool) ($counselling?->mediclaim ?? $booking->has_mediclaim);

        // See the matching comment in bookings() above — safe here too since
        // `payments` is already eager-loaded on this query.
        $booking->append(['payment_status', 'remaining_balance']);

        return response()->json([
            'success' => true,
            'data' => [
                'booking' => $booking,
                'counselling' => $counselling,
                'invoice' => $invoice,
                'default_package_amount' => $booking->remaining_balance,
                'total_paid_so_far' => $totalPaidSoFar,
                'required_total' => $requiredTotal,
                'auto_receipt_number' => 'RCP-' . now()->format('Ym') . '-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
                'default_payment_mode' => $hasMediclaim ? 'mediclaim' : 'cash',
            ],
        ]);
    }

    public function storePayment(string $slug, Request $request, int $bookingId): JsonResponse
    {
        $tenantId = (int) app('tenant')->id;
        $booking = OtBooking::query()->where('tenant_id', $tenantId)->with('payments')->find($bookingId);

        if (! $booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found.'], 404);
        }

        if (! in_array($booking->ot_status, [OtBooking::STATUS_COUNSELLED, OtBooking::STATUS_PAID], true)) {
            return response()->json(['success' => false, 'message' => 'Payment is only allowed after counselling (or for remaining balance on a paid booking).'], 422);
        }

        $requiredTotal = (float) ($booking->package_amount ?? 0);
        if ($requiredTotal <= 0) {
            return response()->json(['success' => false, 'message' => 'Package amount must be greater than zero before recording payment.'], 422);
        }

        $remaining = (float) $booking->remaining_balance;
        if ($remaining <= 0) {
            return response()->json(['success' => false, 'message' => 'This booking is already fully paid.'], 422);
        }

        $validated = $request->validate([
            'receipt_number' => ['nullable', 'string', 'max:255'],
            'payment_mode' => ['required', 'string', Rule::in(['cash', 'online', 'mediclaim'])],
        ]);

        // Ignore any client-submitted amount; force server remaining balance —
        // matches web exactly (partial payments removed, web pull 2026-08-07).
        $validated['package_amount'] = $remaining;

        $counselling = DB::table('ot_counselling')
            ->where('tenant_id', $tenantId)
            ->where('ot_booking_id', $booking->id)
            ->first();

        $hasMediclaim = (bool) ($counselling?->mediclaim ?? $booking->has_mediclaim);
        $thisPaymentAmount = (float) $validated['package_amount'];
        $exportAmount = $hasMediclaim ? $thisPaymentAmount : ($thisPaymentAmount / 2);
        $accountantId = (int) auth('sanctum')->id();

        $receiptNumber = trim((string) ($validated['receipt_number'] ?? ''));
        if ($receiptNumber === '') {
            $receiptNumber = 'RCP-' . now()->format('Ym') . '-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        }

        $isFullyPaid = false;
        $paymentId = null;

        DB::transaction(function () use ($booking, $validated, $hasMediclaim, $accountantId, $exportAmount, $tenantId, $receiptNumber, $requiredTotal, &$isFullyPaid, &$paymentId): void {
            $payload = [
                'tenant_id' => $tenantId,
                'ot_booking_id' => $booking->id,
                'package_amount' => $validated['package_amount'],
                'has_mediclaim' => $hasMediclaim,
                'receipt_number' => $receiptNumber,
                'payment_mode' => strtolower($validated['payment_mode']),
                'recorded_by' => $accountantId,
                'paid_at' => now(),
            ];

            if (Schema::hasColumn('ot_payments', 'export_amount')) {
                $payload['export_amount'] = $exportAmount;
            }
            if (Schema::hasColumn('ot_payments', 'accountant_id')) {
                $payload['accountant_id'] = $accountantId;
            }

            $payment = OtPayment::query()->create($payload);
            $paymentId = (int) $payment->id;

            $totalPaid = (float) OtPayment::query()
                ->where('tenant_id', $tenantId)
                ->where('ot_booking_id', $booking->id)
                ->sum('package_amount');

            $isFullyPaid = $totalPaid >= $requiredTotal;

            $updateData = [
                'payment_mode' => strtolower($validated['payment_mode']),
                'has_mediclaim' => $hasMediclaim,
            ];

            if ($isFullyPaid) {
                // Same auto-advance as web — no separate "verify payment" call needed.
                $updateData['ot_status'] = OtBooking::STATUS_PAYMENT_VERIFIED;
            }

            $booking->update($updateData);
        });

        $booking->refresh();

        return response()->json([
            'success' => true,
            'message' => $isFullyPaid
                ? 'Payment completed in full. Patient sent to Ward. Export amount: ' . number_format($exportAmount, 2)
                : 'Partial payment recorded. Remaining balance: ' . number_format($booking->remaining_balance, 2),
            'data' => [
                'payment_id' => $paymentId,
                'ot_status' => $booking->ot_status,
                'is_fully_paid' => $isFullyPaid,
                'remaining_balance' => $booking->remaining_balance,
            ],
        ], 201);
    }

    public function receipt(string $slug, int $paymentId): JsonResponse
    {
        $tenantId = (int) app('tenant')->id;

        $payment = OtPayment::query()
            ->with(['booking.patient:id,patient_code,first_name,middle_name,last_name,contact_no', 'booking.payments', 'recordedBy:id,name'])
            ->where('tenant_id', $tenantId)
            ->find($paymentId);

        if (! $payment) {
            return response()->json(['success' => false, 'message' => 'Payment not found.'], 404);
        }

        $totalPaid = (float) OtPayment::query()
            ->where('tenant_id', $tenantId)
            ->where('ot_booking_id', $payment->ot_booking_id)
            ->sum('package_amount');

        // See the matching comment in bookings()/paymentFormData() above —
        // safe here since `booking.payments` is now eager-loaded too.
        $payment->booking->append(['payment_status']);

        return response()->json([
            'success' => true,
            'data' => [
                'payment' => $payment,
                'total_paid' => $totalPaid,
                'required_total' => (float) ($payment->booking->package_amount ?? 0),
            ],
        ]);
    }

    /**
     * Full refund form data — mirrors `OtAccountantController::createRefund()`.
     * Only surgery_refused bookings with a refundable balance. See
     * WEB_PULL_2026_08_07_APP_PARITY_AUDIT.md §4.
     */
    public function refundFormData(string $slug, int $bookingId): JsonResponse
    {
        $tenantId = (int) app('tenant')->id;

        $booking = OtBooking::query()
            ->with(['patient:id,patient_code,first_name,middle_name,last_name,contact_no', 'payments', 'refunds'])
            ->where('tenant_id', $tenantId)
            ->find($bookingId);

        if (! $booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found.'], 404);
        }

        if ($booking->ot_status !== OtBooking::STATUS_SURGERY_REFUSED) {
            return response()->json(['success' => false, 'message' => 'Refund is only available for surgery_refused bookings.'], 422);
        }

        if ($booking->refundable_balance <= 0) {
            return response()->json(['success' => false, 'message' => 'This booking is already fully refunded (or has no payments).'], 422);
        }

        $autoReceipt = 'REF-' . now()->format('Ym') . '-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);

        return response()->json([
            'success' => true,
            'data' => [
                'booking' => $booking,
                'total_paid' => $booking->total_paid,
                'total_refunded' => $booking->total_refunded,
                'refund_amount' => $booking->refundable_balance,
                'auto_receipt_number' => $autoReceipt,
            ],
        ]);
    }

    /**
     * Record full refund (forced amount = refundable balance) — mirrors
     * `OtAccountantController::storeRefund()` exactly.
     */
    public function storeRefund(string $slug, Request $request, int $bookingId): JsonResponse
    {
        $tenantId = (int) app('tenant')->id;

        $booking = OtBooking::query()
            ->with(['payments', 'refunds'])
            ->where('tenant_id', $tenantId)
            ->find($bookingId);

        if (! $booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found.'], 404);
        }

        if ($booking->ot_status !== OtBooking::STATUS_SURGERY_REFUSED) {
            return response()->json(['success' => false, 'message' => 'Refund is only available for surgery_refused bookings.'], 422);
        }

        $refundable = (float) $booking->refundable_balance;
        if ($refundable <= 0) {
            return response()->json(['success' => false, 'message' => 'Nothing left to refund on this booking.'], 422);
        }

        $validated = $request->validate([
            'payment_mode' => ['required', 'string', Rule::in(['cash', 'online'])],
            'receipt_number' => ['nullable', 'string', 'max:100'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $receipt = trim((string) ($validated['receipt_number'] ?? ''));
        if ($receipt === '') {
            $receipt = 'REF-' . now()->format('Ym') . '-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        }

        $refund = OtRefund::query()->create([
            'tenant_id' => $tenantId,
            'ot_booking_id' => $booking->id,
            'amount' => $refundable,
            'payment_mode' => strtolower($validated['payment_mode']),
            'receipt_number' => $receipt,
            'reason' => $validated['reason'] ?? 'Patient refused OT — full refund',
            'refunded_by' => (int) auth('sanctum')->id(),
            'refunded_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Full refund of ' . number_format($refundable, 2) . ' recorded (receipt ' . $receipt . ').',
            'data' => $refund,
        ], 201);
    }

    private function generateUniqueInvoiceNumber(int $tenantId): string
    {
        do {
            $candidate = sprintf('INV-OT-%s-%04d', now()->format('Ymd'), random_int(1, 9999));

            $exists = DB::table('ot_invoices')
                ->where('tenant_id', $tenantId)
                ->where('invoice_number', $candidate)
                ->exists();
        } while ($exists);

        return $candidate;
    }
}

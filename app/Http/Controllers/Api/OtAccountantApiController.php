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
        if (! in_array($filter, ['today', 'completed'], true)) {
            $filter = 'today';
        }

        $query = OtBooking::query()
            ->where('tenant_id', $tenantId)
            ->with(['patient:id,patient_code,location_id,first_name,middle_name,last_name,contact_no', 'patient.location:id,city,district,state', 'payments']);

        if ($filter === 'today') {
            $query->whereIn('ot_status', [OtBooking::STATUS_COUNSELLED, OtBooking::STATUS_PAID])
                ->whereDate('surgery_date', today())
                ->orderBy('surgery_date')
                ->orderByDesc('id');
        } else {
            $query->whereIn('ot_status', [
                OtBooking::STATUS_PAYMENT_VERIFIED,
                OtBooking::STATUS_IN_WARD,
                OtBooking::STATUS_DILATED,
                OtBooking::STATUS_READY,
                OtBooking::STATUS_OPERATED,
                OtBooking::STATUS_DISCHARGED,
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
        $bookings->getCollection()->each(fn (OtBooking $b) => $b->append(['payment_status', 'remaining_balance']));

        return response()->json(['success' => true, 'data' => $bookings, 'meta' => ['filter' => $filter]]);
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
            'package_amount' => ['required', 'numeric', 'min:0.01', 'max:' . $remaining],
            'receipt_number' => ['nullable', 'string', 'max:255'],
            'payment_mode' => ['required', 'string', Rule::in(['cash', 'online', 'mediclaim'])],
        ], [
            'package_amount.max' => 'Payment cannot exceed remaining balance of ' . number_format($remaining, 2) . '.',
        ]);

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

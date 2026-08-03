<?php

namespace App\Http\Controllers\Hospital\OT;

use App\Http\Controllers\Controller;
use App\Models\Hospital\OT\OtBooking;
use App\Models\Hospital\OT\OtPayment;
use App\Models\Hospital\OT\OtPreOp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OtAccountantController extends Controller
{
    public function wardIndex(string $slug): View
    {
        $bookings = OtBooking::query()
            ->with(['patient:id,patient_code,location_id,first_name,middle_name,last_name,contact_no', 'patient.location:id,city,district,state', 'payments'])
            // Ward Management only sees a booking once payment is verified — now an
            // automatic transition, not a manual Counsellor click (docs/tulsi.md §3).
            ->whereIn('ot_status', [OtBooking::STATUS_PAYMENT_VERIFIED, OtBooking::STATUS_IN_WARD, OtBooking::STATUS_READY])
            ->orderBy('surgery_date')
            ->orderByDesc('id')
            ->paginate((int) config('app.pagination_limit', 25));

        return view('hospital.ot.accountant.ward', [
            'slug' => $slug,
            'bookings' => $bookings,
        ]);
    }

    public function dashboard(Request $request, string $slug): View
    {
        $filter = strtolower((string) $request->query('filter', 'today'));
        // Legacy "all" → completed (accountant desk history).
        if ($filter === 'all') {
            $filter = 'completed';
        }
        if (! in_array($filter, ['today', 'completed'], true)) {
            $filter = 'today';
        }

        $bookingsQuery = OtBooking::query()
            ->with(['patient:id,patient_code,location_id,first_name,middle_name,last_name,contact_no', 'patient.location:id,city,district,state', 'payments']);

        if ($filter === 'today') {
            // Pending payment queue — today's OT only.
            $bookingsQuery
                ->whereIn('ot_status', [OtBooking::STATUS_COUNSELLED, OtBooking::STATUS_PAID])
                ->whereDate('surgery_date', today())
                ->orderBy('surgery_date')
                ->orderByDesc('id');
        } else {
            // Completed for accountant: payment done (payment_verified and onward), date-wise.
            $bookingsQuery
                ->whereIn('ot_status', [
                    OtBooking::STATUS_PAYMENT_VERIFIED,
                    OtBooking::STATUS_IN_WARD,
                    OtBooking::STATUS_DILATED,
                    OtBooking::STATUS_READY,
                    OtBooking::STATUS_OPERATED,
                    OtBooking::STATUS_DISCHARGED,
                ])
                ->orderByDesc('surgery_date')
                ->orderByDesc('id');
        }

        $bookings = $bookingsQuery
            ->paginate(20)
            ->appends($request->query());

        return view('hospital.ot.accountant.dashboard', [
            'slug' => $slug,
            'bookings' => $bookings,
            'activeFilter' => $filter,
        ]);
    }

    public function createPayment(string $slug, int $bookingId): View
    {
        $tenantId = (int) app('tenant')->id;

        $booking = OtBooking::query()
            ->with([
                'patient:id,patient_code,location_id,first_name,middle_name,last_name,contact_no',
                'patient.location:id,city,district,state',
                'payments',
            ])
            ->findOrFail($bookingId);

        $counselling = DB::table('ot_counselling')
            ->where('tenant_id', $tenantId)
            ->where('ot_booking_id', $booking->id)
            ->first();

        $requiredTotal = (float) ($counselling?->package_amount ?? $booking->package_amount ?? 0);
        $totalPaidSoFar = (float) $booking->payments->sum('package_amount');

        // Ensure billing invoice number exists so the form shows a real auto value.
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
                    [
                        'head' => 'OT Package',
                        'percentage' => null,
                        'amount' => round($requiredTotal, 2),
                    ],
                ], JSON_UNESCAPED_UNICODE),
                'total_amount' => round($requiredTotal, 2),
                'tax_amount' => 0,
                'discount' => 0,
                'net_amount' => round($requiredTotal, 2),
                'generated_by' => (int) auth('hospital_user')->id(),
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
        $autoReceiptNumber = 'RCP-'.now()->format('Ym').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        $defaultPaymentMode = $hasMediclaim ? 'mediclaim' : 'cash';

        return view('hospital.ot.accountant.payment', [
            'slug' => $slug,
            'booking' => $booking,
            'counselling' => $counselling,
            'invoice' => $invoice,
            'defaultPackageAmount' => $booking->remaining_balance,
            'totalPaidSoFar' => $totalPaidSoFar,
            'requiredTotal' => $requiredTotal,
            'autoReceiptNumber' => $autoReceiptNumber,
            'defaultPaymentMode' => $defaultPaymentMode,
        ]);
    }

    public function storePayment(Request $request, string $slug, int $bookingId): RedirectResponse
    {
        $tenantId = (int) app('tenant')->id;
        $booking = OtBooking::query()
            ->where('tenant_id', $tenantId)
            ->with('payments')
            ->findOrFail($bookingId);

        if (! in_array($booking->ot_status, [OtBooking::STATUS_COUNSELLED, OtBooking::STATUS_PAID], true)) {
            return redirect()
                ->route('hospital.ot.accountant.dashboard', ['slug' => $slug])
                ->with('error', 'Payment is only allowed after counselling (or for remaining balance on a paid booking).');
        }

        $requiredTotal = (float) ($booking->package_amount ?? 0);
        if ($requiredTotal <= 0) {
            return redirect()->back()->with('error', 'Package amount must be greater than zero before recording payment.');
        }

        $remaining = (float) $booking->remaining_balance;
        if ($remaining <= 0) {
            return redirect()->back()->with('error', 'This booking is already fully paid.');
        }

        $validated = $request->validate([
            'package_amount' => ['required', 'numeric', 'min:0.01', 'max:'.$remaining],
            'receipt_number' => ['nullable', 'string', 'max:255'],
            'payment_mode' => ['required', 'string', Rule::in(['cash', 'online', 'mediclaim'])],
        ], [
            'package_amount.max' => 'Payment cannot exceed remaining balance of '.number_format($remaining, 2).'.',
        ]);

        $counselling = DB::table('ot_counselling')
            ->where('tenant_id', $tenantId)
            ->where('ot_booking_id', $booking->id)
            ->first();

        $hasMediclaim = (bool) ($counselling?->mediclaim ?? $booking->has_mediclaim);
        $thisPaymentAmount = (float) $validated['package_amount'];
        $exportAmount = $hasMediclaim ? $thisPaymentAmount : ($thisPaymentAmount / 2);
        $accountantId = (int) auth('hospital_user')->id();

        $receiptNumber = trim((string) ($validated['receipt_number'] ?? ''));
        if ($receiptNumber === '') {
            $receiptNumber = 'RCP-'.now()->format('Ym').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
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
                // Auto-advance straight to payment_verified — no manual Counsellor
                // confirmation step anymore (docs/tulsi.md §3). Ward Management picks
                // the booking up automatically from here.
                $updateData['ot_status'] = OtBooking::STATUS_PAYMENT_VERIFIED;
            }

            $booking->update($updateData);
        });

        $message = $isFullyPaid
            ? 'Payment completed in full. Patient sent to Ward. Export amount: '.number_format($exportAmount, 2)
            : 'Partial payment recorded. Remaining balance: '.number_format($booking->refresh()->remaining_balance, 2);

        return redirect()
            ->route('hospital.ot.payments.receipt', ['slug' => $slug, 'paymentId' => $paymentId])
            ->with('success', $message);
    }

    /**
     * PDF Billing Module — dedicated payment receipt print (A5).
     */
    public function receiptPrint(string $slug, int $paymentId): View
    {
        $tenantId = (int) app('tenant')->id;

        $payment = OtPayment::query()
            ->with([
                'booking.patient:id,patient_code,first_name,middle_name,last_name,contact_no',
                'recordedBy:id,name',
            ])
            ->where('tenant_id', $tenantId)
            ->findOrFail($paymentId);

        $booking = $payment->booking;
        $totalPaid = (float) OtPayment::query()
            ->where('tenant_id', $tenantId)
            ->where('ot_booking_id', $booking->id)
            ->sum('package_amount');

        return view('hospital.ot.billing.receipt_print', [
            'slug' => $slug,
            'payment' => $payment,
            'booking' => $booking,
            'totalPaid' => $totalPaid,
            'requiredTotal' => (float) ($booking->package_amount ?? 0),
        ]);
    }

    public function markReadyForOt(string $slug, OtBooking $booking): RedirectResponse
    {
        $tenantId = (int) app('tenant')->id;

        abort_unless((int) $booking->tenant_id === $tenantId, 403, 'Unauthorized booking access.');

        if (! in_array($booking->ot_status, [OtBooking::STATUS_PAYMENT_VERIFIED, OtBooking::STATUS_IN_WARD], true)) {
            return redirect()->back()->with('error', 'Only payment-verified or in-ward patients can be handed off to OT Assistant.');
        }

        $preOp = OtPreOp::query()
            ->where('tenant_id', $tenantId)
            ->where('ot_booking_id', $booking->id)
            ->first();

        if (! $preOp || $preOp->pre_op_status !== OtPreOp::STATUS_READY_FOR_SURGERY) {
            return redirect()->back()->with('error', 'Set Patient Status to Ready for OT and save before sending.');
        }

        if (empty($booking->ot_assistant_id)) {
            return redirect()->back()->with('error', 'Assign an OT Assistant on Patient Status before sending to OT.');
        }

        $booking->update([
            'ot_status' => OtBooking::STATUS_READY,
            'attended_at' => $booking->attended_at ?? now(),
        ]);

        return redirect()->back()->with('success', 'Patient is ready and handed off to OT Assistant');
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

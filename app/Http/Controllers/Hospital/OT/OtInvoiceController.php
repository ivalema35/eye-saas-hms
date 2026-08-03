<?php

namespace App\Http\Controllers\Hospital\OT;

use App\Http\Controllers\Controller;
use App\Models\Hospital\OT\OtBooking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OtInvoiceController extends Controller
{
    public function index(string $slug): View
    {
        $tenantId = (int) app('tenant')->id;

        $bookings = OtBooking::query()
            ->with(['patient:id,first_name,middle_name,last_name,contact_no'])
            ->whereIn('ot_status', ['operated', 'discharged', 'OPERATED', 'DISCHARGED'])
            ->orderByDesc('surgery_date')
            ->orderByDesc('id')
            ->paginate((int) config('app.pagination_limit', 25));

        $invoiceBookingIds = DB::table('ot_invoices')
            ->where('tenant_id', $tenantId)
            ->pluck('ot_booking_id')
            ->map(static fn ($id) => (int) $id)
            ->all();

        return view('hospital.ot.billing.index', [
            'slug' => $slug,
            'bookings' => $bookings,
            'invoiceBookingIds' => $invoiceBookingIds,
        ]);
    }

    public function generate(Request $request, string $slug, int $bookingId): RedirectResponse
    {
        $tenantId = (int) app('tenant')->id;

        $booking = OtBooking::query()
            ->with(['patient:id,first_name,middle_name,last_name,contact_no'])
            ->findOrFail($bookingId);

        $counselling = DB::table('ot_counselling')
            ->where('tenant_id', $tenantId)
            ->where('ot_booking_id', $booking->id)
            ->first();

        $lensDetail = DB::table('ot_lens_details')
            ->where('tenant_id', $tenantId)
            ->where('ot_booking_id', $booking->id)
            ->latest('id')
            ->first();

        $chargeHeads = DB::table('ot_charge_heads')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('id')
            ->get(['charge_name', 'percentage']);

        $validated = $request->validate([
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'follow_up_date' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        $packageAmount = (float) ($counselling?->package_amount
            ?? $booking->package_amount
            ?? 0);
        $lensMrp = (float) ($lensDetail?->lens_mrp ?? 0);

        // OT Workflow Upgrade — Phase 5: prefer the Counsellor's itemized cost
        // breakdown (Phase 1) when it was actually filled in; fall back to the
        // old charge-head percentage split for bookings counselled before that
        // existed. See docs/OT_WORKFLOW_UPGRADE_PRD.md §5.
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
                    $lineItems[] = [
                        'head' => $head,
                        'percentage' => null,
                        'amount' => round($amount, 2),
                    ];
                }
            }
        } else {
            $remainingAmount = max(0, round($packageAmount - $lensMrp, 2));

            if ($lensMrp > 0) {
                $lineItems[] = [
                    'head' => 'Lens Charges',
                    'percentage' => null,
                    'amount' => round($lensMrp, 2),
                ];
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

                        $lineItems[] = [
                            'head' => $chargeHead->charge_name,
                            'percentage' => $percentage,
                            'amount' => $headAmount,
                        ];
                    }
                } else {
                    $lineItems[] = [
                        'head' => 'OT Remaining Charges',
                        'percentage' => null,
                        'amount' => $remainingAmount,
                    ];
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

        $existingInvoice = DB::table('ot_invoices')
            ->where('tenant_id', $tenantId)
            ->where('ot_booking_id', $booking->id)
            ->first();

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
                'generated_by' => (int) auth('hospital_user')->id(),
                'updated_at' => now(),
            ];

            if ($existingInvoice) {
                DB::table('ot_invoices')
                    ->where('tenant_id', $tenantId)
                    ->where('ot_booking_id', $booking->id)
                    ->update($payload);
            } else {
                DB::table('ot_invoices')->insert([
                    ...$payload,
                    'tenant_id' => $tenantId,
                    'ot_booking_id' => $booking->id,
                    'created_at' => now(),
                ]);
            }

            $booking->update([
                'ot_status' => 'discharged',
                'discharged_at' => now(),
            ]);
        });

        return redirect()
            ->route('hospital.ot.invoice.print', ['slug' => $slug, 'bookingId' => $bookingId])
            ->with('success', 'Invoice generated successfully and patient marked as discharged.');
    }

    public function print(string $slug, int $bookingId): View
    {
        $tenantId = (int) app('tenant')->id;

        $booking = OtBooking::query()
            ->with(['patient:id,patient_code,first_name,middle_name,last_name,contact_no', 'otDoctor:id,name'])
            ->findOrFail($bookingId);

        $invoice = DB::table('ot_invoices')
            ->where('tenant_id', $tenantId)
            ->where('ot_booking_id', $booking->id)
            ->first();

        abort_if(! $invoice, 404, 'Invoice not found for this booking.');

        $lineItems = is_string($invoice->line_items)
            ? (json_decode($invoice->line_items, true) ?: [])
            : (array) $invoice->line_items;

        return view('hospital.ot.billing.invoice_print', [
            'slug' => $slug,
            'booking' => $booking,
            'invoice' => $invoice,
            'lineItems' => $lineItems,
        ]);
    }

    public function summaryBillPrint(string $slug, int $bookingId): View
    {
        $tenantId = (int) app('tenant')->id;

        $booking = OtBooking::query()
            ->with([
                'patient:id,patient_code,first_name,middle_name,last_name,contact_no,location_id',
                'patient.masterCity:id,name',
                'patient.location:id,city,district,state',
                'otDoctor:id,name',
            ])
            ->findOrFail($bookingId);

        $invoice = DB::table('ot_invoices')
            ->where('tenant_id', $tenantId)
            ->where('ot_booking_id', $booking->id)
            ->first();

        abort_if(! $invoice, 404, 'Summary bill not found for this booking.');

        $lineItems = is_string($invoice->line_items)
            ? (json_decode($invoice->line_items, true) ?: [])
            : (array) $invoice->line_items;

        return view('hospital.ot.billing.summary_bill_print', [
            'slug' => $slug,
            'booking' => $booking,
            'invoice' => $invoice,
            'lineItems' => $lineItems,
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

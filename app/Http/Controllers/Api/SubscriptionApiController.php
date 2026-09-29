<?php

/**
 * SubscriptionApiController.php
 *
 * PURPOSE: Mobile/tablet API mirror of Hospital\Subscription\SubscriptionController
 *          — VIEW-ONLY parity for the tenant's own "Subscription & Billing" page
 *          (Plan History table + per-row Invoice download), and the target screen
 *          for the dashboard's "Renew →" banner. See
 *          WEB_VIEW_BUTTONS_PARITY_AUDIT.md finding #1.
 *
 *          The actual renew/checkout flow (`checkout`/`confirm`, Razorpay SDK
 *          driven) is intentionally NOT ported here — the audit finding only
 *          asked for the View surface (seeing plan history, downloading past
 *          invoices), not the payment flow itself. A hospital admin who wants
 *          to renew still does so on web for now.
 *
 * PERMISSIONS: web restricts this whole feature to the tenant's own admin
 *              (`role->is_super`, via `ensureHospitalAdmin()`) — mirrored below,
 *              not a granular permission slug.
 *
 * ROUTE PLACEMENT: like web's routes/hospital.php ("accessible during grace —
 *              no subscription.active"), these routes must NOT sit behind the
 *              `subscription.active` middleware group in routes/api.php — an
 *              admin whose subscription just expired is exactly who needs to
 *              reach this screen (to see why / download past invoices), so
 *              gating it behind "subscription must be active" would lock them
 *              out of the one screen that explains and fixes that.
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Platform\Payment;
use App\Services\Platform\HospitalSubscriptionHistoryService;
use App\Services\Platform\InvoiceService;
use App\Services\Platform\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubscriptionApiController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService,
        private readonly HospitalSubscriptionHistoryService $historyService,
        private readonly InvoiceService $invoiceService,
    ) {
    }

    private function ensureHospitalAdmin(Request $request): void
    {
        if (! $request->user()?->role?->is_super) {
            abort(403, 'Only Hospital Admin can view subscription & billing.');
        }
    }

    public function index(string $slug, Request $request): JsonResponse
    {
        $this->ensureHospitalAdmin($request);

        $tenant = app('tenant');
        $ctx = $this->subscriptionService->resolveCountryContext($tenant);

        // Same computation as SubscriptionController::index — the dashboard
        // banner's "N days remaining" figure, repeated here so this screen's
        // header badge matches exactly what sent the user here.
        $subscriptionDaysLeft = null;
        $sub = $tenant->subscriptions()->latest()->first();
        if ($sub && $sub->ends_at) {
            $subscriptionDaysLeft = (int) now()->diffInDays($sub->ends_at, false);
        } elseif ($tenant->trial_ends_at) {
            $subscriptionDaysLeft = (int) now()->diffInDays($tenant->trial_ends_at, false);
        }

        $rows = $this->historyService->rowsFor($tenant)->map(fn (array $row) => [
            'type' => $row['type'],
            'start' => $row['start']?->toDateString(),
            'end' => $row['end']?->toDateString(),
            'days_used' => $row['days_used'],
            'days_remaining' => $row['days_remaining'],
            'status' => $row['status'],
            'payment_id' => $row['payment_id'],
            'notes' => $row['notes'],
        ])->values();

        return response()->json([
            'success' => true,
            'data' => [
                'tenant_name' => $tenant->name,
                'country_name' => $ctx['country_name'],
                'currency_code' => $ctx['currency_code'],
                'currency_symbol' => $ctx['currency_symbol'],
                'subscription_days_left' => $subscriptionDaysLeft,
                'history_rows' => $rows,
            ],
        ]);
    }

    public function downloadInvoice(string $slug, int $paymentId, Request $request): StreamedResponse
    {
        $this->ensureHospitalAdmin($request);
        $tenant = app('tenant');

        $payment = Payment::query()->findOrFail($paymentId);
        if ((int) $payment->tenant_id !== (int) $tenant->id || $payment->status !== 'success') {
            abort(403);
        }

        if (! $payment->invoice_path || ! Storage::disk('local')->exists($payment->invoice_path)) {
            $path = $this->invoiceService->generate($payment);
            $payment->update(['invoice_path' => $path]);
        }

        $filename = 'invoice-'.$this->invoiceService->invoiceNumber($payment).'.pdf';

        return Storage::disk('local')->download($payment->invoice_path, $filename);
    }
}

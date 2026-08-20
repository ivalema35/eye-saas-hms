<?php

namespace App\Http\Controllers\Hospital\Subscription;

use App\Http\Controllers\Controller;
use App\Models\Platform\Payment;
use App\Services\Platform\HospitalSubscriptionHistoryService;
use App\Services\Platform\InvoiceService;
use App\Services\Platform\PlatformPricingService;
use App\Services\Platform\RazorpayCheckoutService;
use App\Services\Platform\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService,
        private readonly PlatformPricingService $pricingService,
        private readonly HospitalSubscriptionHistoryService $historyService,
        private readonly RazorpayCheckoutService $razorpayService,
        private readonly InvoiceService $invoiceService,
    ) {
    }

    private function ensureHospitalAdmin(): void
    {
        $user = Auth::guard('hospital_user')->user();
        if (! $user?->role?->is_super) {
            abort(403, 'Only Hospital Admin can manage subscription.');
        }
    }

    public function index(Request $request): View
    {
        $this->ensureHospitalAdmin();
        $slug = $request->route('slug');
        $tenant = app('tenant');
        $ctx = $this->subscriptionService->resolveCountryContext($tenant);

        $plans = $this->pricingService->plansForCountry($ctx['country_id'], $ctx['fx_inr_per_unit']);
        $monthlyQuote = $this->pricingService->quoteForCountry(
            $ctx['country_id'],
            $ctx['fx_inr_per_unit'],
            'monthly',
            $ctx['country_code'],
            $ctx['country_name'],
        );
        $yearlyQuote = $this->pricingService->quoteForCountry(
            $ctx['country_id'],
            $ctx['fx_inr_per_unit'],
            'yearly',
            $ctx['country_code'],
            $ctx['country_name'],
        );

        $subscriptionDaysLeft = null;
        $sub = $tenant->subscriptions()->latest()->first();
        if ($sub && $sub->ends_at) {
            $subscriptionDaysLeft = (int) now()->diffInDays($sub->ends_at, false);
        } elseif ($tenant->trial_ends_at) {
            $subscriptionDaysLeft = (int) now()->diffInDays($tenant->trial_ends_at, false);
        }

        return view('hospital.subscription.index', [
            'slug' => $slug,
            'tenant' => $tenant,
            'ctx' => $ctx,
            'plans' => $plans,
            'monthlyQuote' => $monthlyQuote,
            'yearlyQuote' => $yearlyQuote,
            'historyRows' => $this->historyService->rowsFor($tenant),
            'subscriptionDaysLeft' => $subscriptionDaysLeft,
            'razorpayBypass' => $this->razorpayService->isBypassMode(),
            'razorpayKey' => $this->razorpayService->publicKey(),
        ]);
    }

    public function checkout(Request $request): JsonResponse
    {
        $this->ensureHospitalAdmin();
        $validated = $request->validate([
            'cycle' => ['required', 'in:monthly,yearly'],
        ]);

        $tenant = app('tenant');
        $ctx = $this->subscriptionService->resolveCountryContext($tenant);
        $quote = $this->pricingService->quoteForCountry(
            $ctx['country_id'],
            $ctx['fx_inr_per_unit'],
            $validated['cycle'],
            $ctx['country_code'],
            $ctx['country_name'],
        );

        $orderId = $this->razorpayService->simulatedOrderId();

        $payment = $this->subscriptionService->createPendingPayment(
            $tenant,
            $validated['cycle'],
            $quote,
            [
                'currency_code' => $ctx['currency_code'],
                'currency_symbol' => $ctx['currency_symbol'],
            ],
            $orderId,
        );

        $order = $this->razorpayService->createOrder($payment, $ctx['currency_code']);

        if ($payment->razorpay_order_id !== $order['order_id']) {
            $payment->update(['razorpay_order_id' => $order['order_id']]);
        }

        return response()->json([
            'payment_id' => $payment->id,
            'order_id' => $order['order_id'],
            'amount' => $order['amount'],
            'currency' => $order['currency'],
            'key' => $order['key'],
            'bypass' => $order['bypass'],
        ]);
    }

    public function confirm(Request $request): JsonResponse|RedirectResponse
    {
        $this->ensureHospitalAdmin();
        $validated = $request->validate([
            'payment_id' => ['required', 'integer', 'exists:payments,id'],
            'razorpay_payment_id' => ['nullable', 'string', 'max:255'],
            'razorpay_order_id' => ['nullable', 'string', 'max:255'],
            'razorpay_signature' => ['nullable', 'string', 'max:255'],
        ]);

        $tenant = app('tenant');
        $payment = Payment::query()->findOrFail($validated['payment_id']);

        if ((int) $payment->tenant_id !== (int) $tenant->id) {
            abort(403);
        }

        if ($payment->status === 'success') {
            return $this->confirmResponse($request, true, 'Subscription already active.');
        }

        if ($this->razorpayService->isBypassMode()) {
            $gatewayData = [
                'transaction_id' => $this->razorpayService->simulatedPaymentId(),
                'razorpay_signature' => 'bypass',
            ];
        } else {
            $orderId = $validated['razorpay_order_id'] ?? $payment->razorpay_order_id;
            $paymentId = $validated['razorpay_payment_id'] ?? '';
            $signature = $validated['razorpay_signature'] ?? '';

            if (! $this->razorpayService->verifySignature($orderId, $paymentId, $signature)) {
                $payment->update(['status' => 'failed']);

                return $this->confirmResponse($request, false, 'Payment verification failed.');
            }

            $gatewayData = [
                'transaction_id' => $paymentId,
                'razorpay_signature' => $signature,
            ];
        }

        $this->subscriptionService->confirmRenewal($payment, $gatewayData);

        try {
            $path = $this->invoiceService->generate($payment->fresh());
            $payment->update(['invoice_path' => $path]);
        } catch (\Throwable) {
            // Invoice generation is non-blocking for renewal success.
        }

        return $this->confirmResponse($request, true, 'Subscription renewed successfully.');
    }

    public function downloadInvoice(Request $request, string $slug, Payment $payment): StreamedResponse
    {
        $this->ensureHospitalAdmin();
        $tenant = app('tenant');

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

    private function confirmResponse(Request $request, bool $success, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => $success, 'message' => $message]);
        }

        $slug = $request->route('slug');

        return redirect()
            ->route('hospital.subscription.index', ['slug' => $slug])
            ->with($success ? 'success' : 'error', $message);
    }
}

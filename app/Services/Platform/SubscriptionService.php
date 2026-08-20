<?php

namespace App\Services\Platform;

use App\Jobs\SendSubscriptionEmail;
use App\Models\Platform\Payment;
use App\Models\Platform\Subscription;
use App\Models\Platform\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionService
{
    public function __construct(
        private readonly PlatformPricingService $pricing,
    ) {
    }

    /**
     * @param  array{subtotal: int, gst_rate: float, gst_amount: int, total: int}  $quote
     * @param  array{currency_code: string, currency_symbol: string}  $currencyMeta
     */
    public function createPendingPayment(
        Tenant $tenant,
        string $cycle,
        array $quote,
        array $currencyMeta,
        string $orderId,
    ): Payment {
        return Payment::create([
            'tenant_id' => $tenant->id,
            'amount' => $quote['total'],
            'currency_code' => $currencyMeta['currency_code'],
            'currency_symbol' => $currencyMeta['currency_symbol'],
            'subtotal' => $quote['subtotal'],
            'gst_rate' => $quote['gst_rate'],
            'gst_amount' => $quote['gst_amount'],
            'cycle' => $cycle,
            'method' => 'online',
            'gateway' => 'razorpay',
            'razorpay_order_id' => $orderId,
            'status' => 'pending',
        ]);
    }

    public function confirmRenewal(Payment $payment, array $gatewayData): Subscription
    {
        $subscription = DB::transaction(function () use ($payment, $gatewayData) {
            $payment->loadMissing('tenant');
            $tenant = $payment->tenant;
            $cycle = $payment->cycle;

            $payment->update([
                'status' => 'success',
                'paid_at' => now(),
                'transaction_id' => $gatewayData['transaction_id'] ?? null,
                'razorpay_signature' => $gatewayData['razorpay_signature'] ?? null,
            ]);

            $startDate = now();
            $endDate = match ($cycle) {
                'quarterly' => $startDate->copy()->addMonths(3),
                'yearly' => $startDate->copy()->addYear(),
                default => $startDate->copy()->addMonth(),
            };

            $subscription = Subscription::create([
                'tenant_id' => $tenant->id,
                'cycle' => $cycle,
                'price' => $payment->amount,
                'original_price' => $payment->subtotal ?? $payment->amount,
                'starts_at' => $startDate,
                'ends_at' => $endDate,
                'status' => 'active',
            ]);

            $tenant->update(['status' => 'active']);

            Log::info("Subscription renewed for tenant #{$tenant->id}: {$cycle} until {$endDate->toDateString()}");

            return $subscription;
        });

        // Mail must never roll back a successful renewal (SMTP often unconfigured in local/dev).
        try {
            SendSubscriptionEmail::dispatch($payment->tenant, 'renewal_success');
        } catch (\Throwable $e) {
            Log::warning(
                "Renewal email skipped for tenant #{$payment->tenant_id}: ".$e->getMessage()
            );
        }

        return $subscription;
    }

    /**
     * @param  array  $paymentData  Razorpay or offline payment details
     */
    public function renew(Tenant $tenant, string $cycle, array $paymentData): Subscription
    {
        $payment = Payment::create([
            'tenant_id' => $tenant->id,
            'amount' => $paymentData['amount'] ?? 0,
            'cycle' => $cycle,
            'method' => $paymentData['method'] ?? 'online',
            'gateway' => $paymentData['gateway'] ?? 'razorpay',
            'transaction_id' => $paymentData['transaction_id'] ?? null,
            'razorpay_order_id' => $paymentData['razorpay_order_id'] ?? null,
            'razorpay_signature' => $paymentData['razorpay_signature'] ?? null,
            'status' => 'pending',
            'currency_code' => $paymentData['currency_code'] ?? null,
            'currency_symbol' => $paymentData['currency_symbol'] ?? null,
            'subtotal' => $paymentData['subtotal'] ?? null,
            'gst_rate' => $paymentData['gst_rate'] ?? null,
            'gst_amount' => $paymentData['gst_amount'] ?? null,
        ]);

        return $this->confirmRenewal($payment, $paymentData);
    }

    /**
     * @return array{price: int, original: int}
     */
    public function calculatePrice(Tenant $tenant, string $cycle): array
    {
        $ctx = $this->resolveCountryContext($tenant);
        $plans = $this->pricing->plansForCountry($ctx['country_id'], $ctx['fx_inr_per_unit']);
        $plan = $plans[$cycle] ?? $plans['monthly'];

        return [
            'price' => (int) $plan['price'],
            'original' => (int) $plan['original'],
        ];
    }

    /**
     * @return array{
     *   country_id: int,
     *   country_code: string,
     *   country_name: string,
     *   fx_inr_per_unit: float,
     *   currency_code: string,
     *   currency_symbol: string
     * }
     */
    public function resolveCountryContext(Tenant $tenant): array
    {
        $countryName = \App\Models\Platform\MasterCountry::normalize($tenant->country ?? 'India');
        $country = \App\Models\Platform\MasterCountry::query()
            ->where('name', $countryName)
            ->first();

        return [
            'country_id' => (int) ($country?->id ?? 0),
            'country_code' => $country?->country_code ?? 'IN',
            'country_name' => $country?->name ?? ($tenant->country ?? 'India'),
            'fx_inr_per_unit' => (float) ($country?->fx_inr_per_unit ?: 1),
            'currency_code' => $tenant->currency_code ?: ($country?->currency_code ?? 'INR'),
            'currency_symbol' => $tenant->currency_symbol ?: ($country?->currency_symbol ?? '₹'),
        ];
    }
}

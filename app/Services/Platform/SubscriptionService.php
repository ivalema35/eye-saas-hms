<?php

/**
 * SubscriptionService.php
 *
 * PURPOSE: Subscription lifecycle management.
 *          Renew subscription, record payment.
 *
 * DATABASE COLUMNS (exact match with migrations):
 *   subscriptions: cycle, price, original_price, starts_at, ends_at, grace_ends_at
 *   payments: method(online/offline), transaction_id, status(pending/success/failed)
 */

namespace App\Services\Platform;

use App\Jobs\SendSubscriptionEmail;
use App\Models\Platform\Payment;
use App\Models\Platform\Subscription;
use App\Models\Platform\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionService
{
    /** Pricing: monthly base prices */
    private int $monthlyPrice = 999;

    /**
     * Renew subscription after successful payment.
     *
     * @param  array  $paymentData  Razorpay or offline payment details
     */
    public function renew(Tenant $tenant, string $cycle, array $paymentData): Subscription
    {
        return DB::transaction(function () use ($tenant, $cycle, $paymentData) {
            $startDate = now();
            $prices = $this->calculatePrice($cycle);

            $endDate = match ($cycle) {
                'quarterly' => $startDate->copy()->addMonths(3),
                'yearly' => $startDate->copy()->addYear(),
                default => $startDate->copy()->addMonth(),
            };

            // Record Payment
            Payment::create([
                'tenant_id' => $tenant->id,
                'amount' => $prices['price'],
                'cycle' => $cycle,
                'method' => $paymentData['method'] ?? 'online',
                'gateway' => $paymentData['gateway'] ?? 'razorpay',
                'transaction_id' => $paymentData['transaction_id'] ?? null,
                'razorpay_order_id' => $paymentData['razorpay_order_id'] ?? null,
                'razorpay_signature' => $paymentData['razorpay_signature'] ?? null,
                'status' => 'success',
                'paid_at' => now(),
                'notes' => $paymentData['notes'] ?? null,
            ]);

            // Create new Subscription record
            $subscription = Subscription::create([
                'tenant_id' => $tenant->id,
                'cycle' => $cycle,
                'price' => $prices['price'],
                'original_price' => $prices['original'],
                'starts_at' => $startDate,
                'ends_at' => $endDate,
                'status' => 'active',
            ]);

            // Activate tenant
            $tenant->update(['status' => 'active']);

            SendSubscriptionEmail::dispatch($tenant, 'renewal_success');

            Log::info("Subscription renewed for tenant #{$tenant->id}: {$cycle} until {$endDate->toDateString()}");

            return $subscription;
        });
    }

    /**
     * Calculate price for a billing cycle.
     *
     * @return array{price: int, original: int}
     */
    public function calculatePrice(string $cycle): array
    {
        return match ($cycle) {
            'quarterly' => ['price' => 2427, 'original' => 2697],
            'yearly' => ['price' => 9590, 'original' => 11988],
            default => ['price' => 999, 'original' => 999],
        };
    }
}

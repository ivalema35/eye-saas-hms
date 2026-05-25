<?php

/**
 * WebhookController.php
 *
 * PURPOSE: Razorpay payment webhook endpoint.
 *          Razorpay se payment confirmation aayegi yahan.
 *          Phase 2 me sirf stub — full logic Phase 3 me.
 *
 * ROUTE: POST /webhooks/razorpay (CSRF exempt)
 *
 * SECURITY: Razorpay webhook signature se verify karna hai.
 */

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * handle() — Razorpay webhook events receive karo.
     *
     * Phase 2: Sirf log karo aur 200 return karo.
     * Phase 3: Signature verify + payment process.
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();
        $event = $payload['event'] ?? 'unknown';

        Log::info("Razorpay webhook received: {$event}", [
            'payload' => $payload,
        ]);

        // TODO Phase 3: Verify webhook signature
        // $secret    = config('services.razorpay.webhook_secret');
        // $signature = $request->header('X-Razorpay-Signature');
        // $expected  = hash_hmac('sha256', $request->getContent(), $secret);
        // if (! hash_equals($expected, $signature)) {
        //     return response()->json(['error' => 'Invalid signature'], 403);
        // }

        // TODO Phase 3: Handle events
        // match ($event) {
        //     'payment.captured'       => $this->handlePaymentCaptured($payload),
        //     'subscription.activated' => $this->handleSubscriptionActivated($payload),
        //     default                  => Log::info("Unhandled Razorpay event: {$event}"),
        // };

        return response()->json(['status' => 'ok']);
    }
}

<?php

namespace App\Services\Platform;

use App\Models\Platform\Payment;
use App\Models\Platform\PlatformSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class RazorpayCheckoutService
{
    public function isBypassMode(): bool
    {
        if (config('services.razorpay.bypass')) {
            return true;
        }

        return blank(PlatformSetting::get('razorpay_key'));
    }

    public function publicKey(): ?string
    {
        return PlatformSetting::get('razorpay_key') ?: config('services.razorpay.key') ?: null;
    }

    public function simulatedOrderId(): string
    {
        return 'order_sim_'.Str::uuid();
    }

    public function simulatedPaymentId(): string
    {
        return 'pay_sim_'.Str::uuid();
    }

    /**
     * @return array{order_id: string, amount: int, currency: string, key: ?string, bypass: bool}
     */
    public function createOrder(Payment $payment, string $currencyCode): array
    {
        $amountPaise = (int) round((float) $payment->amount * 100);

        if ($this->isBypassMode()) {
            $orderId = $payment->razorpay_order_id ?: $this->simulatedOrderId();

            return [
                'order_id' => $orderId,
                'amount' => $amountPaise,
                'currency' => strtoupper($currencyCode),
                'key' => null,
                'bypass' => true,
            ];
        }

        $key = $this->publicKey();
        $secret = PlatformSetting::get('razorpay_secret') ?: config('services.razorpay.secret');

        $response = Http::withBasicAuth($key, $secret)
            ->post('https://api.razorpay.com/v1/orders', [
                'amount' => $amountPaise,
                'currency' => strtoupper($currencyCode),
                'receipt' => 'payment_'.$payment->id,
                'notes' => [
                    'tenant_id' => (string) $payment->tenant_id,
                    'cycle' => $payment->cycle,
                ],
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Unable to create Razorpay order.');
        }

        $data = $response->json();

        return [
            'order_id' => $data['id'],
            'amount' => (int) $data['amount'],
            'currency' => $data['currency'],
            'key' => $key,
            'bypass' => false,
        ];
    }

    public function verifySignature(string $orderId, string $paymentId, string $signature): bool
    {
        if ($this->isBypassMode()) {
            return true;
        }

        $secret = PlatformSetting::get('razorpay_secret') ?: config('services.razorpay.secret');
        if (blank($secret)) {
            return false;
        }

        $expected = hash_hmac('sha256', $orderId.'|'.$paymentId, $secret);

        return hash_equals($expected, $signature);
    }
}

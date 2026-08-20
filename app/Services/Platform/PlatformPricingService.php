<?php

namespace App\Services\Platform;

use App\Models\Platform\PlanCountryPrice;
use App\Models\Platform\PlatformSetting;

class PlatformPricingService
{
    /**
     * Base SaaS plan prices in platform currency (INR).
     *
     * @return array{
     *   monthly: array{price: int, original: int, label: string},
     *   quarterly: array{price: int, original: int, label: string, save: int},
     *   yearly: array{price: int, original: int, label: string, save: int}
     * }
     */
    public function basePlans(): array
    {
        $settings = PlatformSetting::whereIn('key', [
            'monthly_price', 'quarterly_discount', 'yearly_discount',
        ])->get()->keyBy('key');

        $monthly = (int) ($settings->get('monthly_price')?->value ?? 999);
        $qDisc = (int) ($settings->get('quarterly_discount')?->value ?? 10);
        $yDisc = (int) ($settings->get('yearly_discount')?->value ?? 20);

        $quarterlyOriginal = $monthly * 3;
        $yearlyOriginal = $monthly * 12;
        $quarterly = (int) round($quarterlyOriginal * (1 - $qDisc / 100));
        $yearly = (int) round($yearlyOriginal * (1 - $yDisc / 100));

        return [
            'monthly' => [
                'price' => $monthly,
                'original' => $monthly,
                'label' => 'Monthly',
            ],
            'quarterly' => [
                'price' => $quarterly,
                'original' => $quarterlyOriginal,
                'label' => 'Quarterly',
                'save' => max(0, $quarterlyOriginal - $quarterly),
            ],
            'yearly' => [
                'price' => $yearly,
                'original' => $yearlyOriginal,
                'label' => 'Yearly',
                'save' => max(0, $yearlyOriginal - $yearly),
            ],
        ];
    }

    /**
     * Convert an INR amount to local currency using master FX (INR per 1 local unit).
     */
    public function convertFromInr(float|int $inrAmount, float $fxInrPerUnit): int
    {
        $fx = $fxInrPerUnit > 0 ? (float) $fxInrPerUnit : 1.0;

        return (int) max(1, round((float) $inrAmount / $fx));
    }

    /**
     * Plans for a given FX rate (fx-conversion fallback).
     *
     * @return array<string, array{price: int, original: int, label: string, save?: int}>
     */
    public function plansForFx(float $fxInrPerUnit): array
    {
        $base = $this->basePlans();
        $out = [];

        foreach ($base as $key => $plan) {
            $converted = [
                'price' => $this->convertFromInr($plan['price'], $fxInrPerUnit),
                'original' => $this->convertFromInr($plan['original'], $fxInrPerUnit),
                'label' => $plan['label'],
            ];
            if (isset($plan['save'])) {
                $converted['save'] = max(0, $converted['original'] - $converted['price']);
            }
            $out[$key] = $converted;
        }

        return $out;
    }

    /**
     * Plans for a specific country — per-cycle override when set, else FX from INR base.
     *
     * @return array<string, array{price: int, original: int, label: string, save?: int}>
     */
    public function plansForCountry(int $countryId, float $fxInrPerUnit): array
    {
        $overrides = PlanCountryPrice::where('country_id', $countryId)
            ->where('is_active', true)
            ->get()
            ->keyBy('cycle');

        $base = $this->basePlans();
        // SuperAdmin stores country override plan prices in INR.
        // FX converts INR plan amounts into the country's local currency.
        $monthlyInr = $overrides->has('monthly')
            ? (float) $overrides['monthly']->price
            : (float) $base['monthly']['price'];

        $quarterlyInr = $overrides->has('quarterly')
            ? (float) $overrides['quarterly']->price
            : (float) $base['quarterly']['price'];

        $yearlyInr = $overrides->has('yearly')
            ? (float) $overrides['yearly']->price
            : (float) $base['yearly']['price'];

        $monthlyPrice = $this->convertFromInr($monthlyInr, $fxInrPerUnit);
        $quarterlyPrice = $this->convertFromInr($quarterlyInr, $fxInrPerUnit);
        $yearlyPrice = $this->convertFromInr($yearlyInr, $fxInrPerUnit);

        // "Original" is the non-discounted amount derived from monthly.
        $quarterlyOriginalLocal = $this->convertFromInr($monthlyInr * 3, $fxInrPerUnit);
        $yearlyOriginalLocal = $this->convertFromInr($monthlyInr * 12, $fxInrPerUnit);

        return [
            'monthly' => [
                'price' => $monthlyPrice,
                'original' => $monthlyPrice,
                'label' => 'Monthly',
            ],
            'quarterly' => [
                'price' => $quarterlyPrice,
                'original' => $quarterlyOriginalLocal,
                'label' => 'Quarterly',
                'save' => max(0, $quarterlyOriginalLocal - $quarterlyPrice),
            ],
            'yearly' => [
                'price' => $yearlyPrice,
                'original' => $yearlyOriginalLocal,
                'label' => 'Yearly',
                'save' => max(0, $yearlyOriginalLocal - $yearlyPrice),
            ],
        ];
    }

    /**
     * GST-inclusive quote for one billing cycle in the country's local currency.
     *
     * @return array{subtotal: int, gst_rate: float, gst_amount: int, total: int}
     */
    public function quoteForCountry(
        int $countryId,
        float $fxInrPerUnit,
        string $cycle,
        ?string $countryCode = null,
        ?string $countryName = null,
    ): array {
        $plans = $this->plansForCountry($countryId, $fxInrPerUnit);
        $subtotal = (int) ($plans[$cycle]['price'] ?? 0);
        $gstRate = platform_country_applies_gst($countryCode, $countryName)
            ? platform_gst_rate_india()
            : 0.0;
        $gstAmount = (int) round($subtotal * $gstRate / 100);

        return [
            'subtotal' => $subtotal,
            'gst_rate' => $gstRate,
            'gst_amount' => $gstAmount,
            'total' => $subtotal + $gstAmount,
        ];
    }
}

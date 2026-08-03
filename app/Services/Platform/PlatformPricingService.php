<?php

namespace App\Services\Platform;

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
}

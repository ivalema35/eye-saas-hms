<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Platform\PlatformSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlatformPlanApiController extends Controller
{
    public function index(): JsonResponse
    {
        $settings = PlatformSetting::whereIn('key', [
            'monthly_price', 'quarterly_discount', 'yearly_discount',
            'trial_days', 'grace_days', 'plan_features',
        ])->get()->keyBy('key');

        $monthlyPrice      = (int) ($settings->get('monthly_price')?->value      ?? 999);
        $quarterlyDiscount = (int) ($settings->get('quarterly_discount')?->value ?? 10);
        $yearlyDiscount    = (int) ($settings->get('yearly_discount')?->value    ?? 20);
        $trialDays         = (int) ($settings->get('trial_days')?->value         ?? 14);
        $graceDays         = (int) ($settings->get('grace_days')?->value         ?? 7);

        $quarterlyPrice    = (int) round($monthlyPrice * 3  * (1 - $quarterlyDiscount / 100));
        $yearlyPrice       = (int) round($monthlyPrice * 12 * (1 - $yearlyDiscount    / 100));
        $quarterlyOriginal = $monthlyPrice * 3;
        $yearlyOriginal    = $monthlyPrice * 12;

        $features = json_decode($settings->get('plan_features')?->value ?? '[]', true) ?: [
            'Complete OPD Management',
            'Eye Examination (Primary + Secondary)',
            'OT Surgery & Billing',
            'Patient History & Reports',
            'Medicine & Prescription Module',
            'Multi-Role Access (Admin, Doctor, Receptionist)',
            'REST API for Android App',
            'Excel & PDF Export',
            'Unlimited Patients',
            'Email Support',
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'monthly_price'      => $monthlyPrice,
                'quarterly_discount' => $quarterlyDiscount,
                'yearly_discount'    => $yearlyDiscount,
                'trial_days'         => $trialDays,
                'grace_days'         => $graceDays,
                'quarterly_price'    => $quarterlyPrice,
                'yearly_price'       => $yearlyPrice,
                'quarterly_original' => $quarterlyOriginal,
                'yearly_original'    => $yearlyOriginal,
                'features'           => $features,
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'monthly_price'      => ['required', 'integer', 'min:1', 'max:99999'],
            'quarterly_discount' => ['required', 'integer', 'min:0', 'max:70'],
            'yearly_discount'    => ['required', 'integer', 'min:0', 'max:70'],
            'trial_days'         => ['required', 'integer', 'min:1', 'max:90'],
            'grace_days'         => ['required', 'integer', 'min:1', 'max:30'],
            'features'           => ['nullable', 'array'],
            'features.*'         => ['string', 'max:120'],
        ]);

        foreach (['monthly_price', 'quarterly_discount', 'yearly_discount', 'trial_days', 'grace_days'] as $key) {
            PlatformSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $validated[$key], 'group' => 'pricing', 'is_encrypted' => false]
            );
        }

        $features = array_values(array_filter(array_map('trim', $validated['features'] ?? [])));
        PlatformSetting::updateOrCreate(
            ['key' => 'plan_features'],
            ['value' => json_encode($features), 'group' => 'pricing', 'is_encrypted' => false]
        );

        return response()->json(['success' => true, 'message' => 'Plan pricing updated successfully.']);
    }
}

<?php

/**
 * PlanController.php
 *
 * PURPOSE: Platform subscription plan pricing management.
 *          Monthly / Quarterly / Yearly pricing control for SuperAdmin.
 *          Prices stored in platform_settings (key-value).
 *
 * ROUTES:
 *   GET  /superadmin/plans        → index()
 *   PUT  /superadmin/plans/{plan} → update()
 *
 * GUARD: superadmin
 */

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Platform\MasterCountry;
use App\Models\Platform\PlanCountryPrice;
use App\Models\Platform\PlatformSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function index(): View
    {
        $settings = PlatformSetting::whereIn('key', [
            'monthly_price', 'quarterly_discount', 'yearly_discount',
            'trial_days', 'grace_days', 'plan_features',
        ])->get()->keyBy('key');

        $monthlyPrice = (int) ($settings->get('monthly_price')?->value ?? 999);
        $quarterlyDiscount = (int) ($settings->get('quarterly_discount')?->value ?? 10);
        $yearlyDiscount = (int) ($settings->get('yearly_discount')?->value ?? 20);
        $trialDays = platform_trial_days();
        $graceDays = (int) ($settings->get('grace_days')?->value ?? 7);

        $quarterlyPrice = (int) round($monthlyPrice * 3 * (1 - $quarterlyDiscount / 100));
        $yearlyPrice = (int) round($monthlyPrice * 12 * (1 - $yearlyDiscount / 100));
        $quarterlyOriginal = $monthlyPrice * 3;
        $yearlyOriginal = $monthlyPrice * 12;

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

        $countries = MasterCountry::active()->orderBy('name')->get(['id', 'name', 'currency_code', 'currency_symbol']);

        try {
            $countryPrices = PlanCountryPrice::with('country')
                ->where('is_active', true)
                ->orderBy('country_id')
                ->get()
                ->groupBy('country_id');
        } catch (\Exception $e) {
            $countryPrices = collect();
        }

        return view('superadmin.plans.index', compact(
            'monthlyPrice', 'quarterlyDiscount', 'yearlyDiscount',
            'trialDays', 'graceDays',
            'quarterlyPrice', 'yearlyPrice',
            'quarterlyOriginal', 'yearlyOriginal',
            'features',
            'countries',
            'countryPrices',
        ));
    }

    public function update(Request $request, string $plan): RedirectResponse
    {
        $validated = $request->validate([
            'monthly_price' => ['required', 'integer', 'min:1', 'max:99999'],
            'quarterly_discount' => ['required', 'integer', 'min:0', 'max:70'],
            'yearly_discount' => ['required', 'integer', 'min:0', 'max:70'],
            'trial_days' => ['required', 'integer', 'min:1', 'max:90'],
            'grace_days' => ['required', 'integer', 'min:1', 'max:30'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string', 'max:120'],
        ]);

        foreach (['monthly_price', 'quarterly_discount', 'yearly_discount', 'trial_days', 'grace_days'] as $key) {
            PlatformSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $validated[$key], 'group' => 'pricing', 'is_encrypted' => false]
            );
        }

        $features = array_values(array_filter(array_map('trim', $validated['features'] ?? [])));
        if ($features) {
            PlatformSetting::updateOrCreate(
                ['key' => 'plan_features'],
                ['value' => json_encode($features), 'group' => 'pricing', 'is_encrypted' => false]
            );
        }

        return redirect()->route('superadmin.plans.index')
            ->with('success', 'Plan pricing updated successfully.');
    }

    // Stub methods required by resource route registration
    public function create(): View
    {
        return $this->index();
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->update($request, 'monthly');
    }

    public function edit(string $plan): View
    {
        return $this->index();
    }

    public function destroy(string $plan): RedirectResponse
    {
        return redirect()->route('superadmin.plans.index');
    }

    /**
     * Save country-wise price overrides (AJAX POST)
     */
    public function saveCountryPrice(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'country_id' => ['required', 'integer', 'exists:tbl_master_countries,id'],
            'monthly'    => ['required', 'numeric', 'min:0'],
            'quarterly'  => ['required', 'numeric', 'min:0'],
            'yearly'     => ['required', 'numeric', 'min:0'],
        ]);

        foreach (['monthly', 'quarterly', 'yearly'] as $cycle) {
            PlanCountryPrice::updateOrCreate(
                ['country_id' => $validated['country_id'], 'cycle' => $cycle],
                ['price' => $validated[$cycle], 'is_active' => true]
            );
        }

        $country = MasterCountry::find($validated['country_id']);

        return response()->json([
            'success' => true,
            'message' => "Prices saved for {$country->name}",
            'country_id' => $validated['country_id'],
        ]);
    }

    /**
     * Delete all price overrides for a country
     */
    public function deleteCountryPrice(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'country_id' => ['required', 'integer', 'exists:tbl_master_countries,id'],
        ]);

        PlanCountryPrice::where('country_id', $validated['country_id'])->delete();

        return response()->json(['success' => true]);
    }
}

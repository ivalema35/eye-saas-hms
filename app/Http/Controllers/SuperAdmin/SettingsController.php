<?php

/**
 * SettingsController.php
 *
 * PURPOSE: Platform settings CRUD for SuperAdmin.
 *          Razorpay keys, trial days, pricing etc manage karna.
 *
 * ROUTES:
 *   GET  /superadmin/settings  → index()
 *   PUT  /superadmin/settings  → update()
 */

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Platform\PlatformSetting;
use App\Support\EmailRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(): View
    {
        $settings = PlatformSetting::all()->keyBy('key');

        return view('superadmin.settings.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'platform_name' => ['required', 'string', 'max:100'],
            'support_email' => EmailRules::required(100),
            'trial_days' => ['required', 'integer', 'min:1', 'max:90'],
            'gst_rate_india' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'razorpay_key' => ['nullable', 'string', 'max:255'],
            'razorpay_secret' => ['nullable', 'string', 'max:255'],
            'razorpay_webhook_secret' => ['nullable', 'string', 'max:255'],
            'mail_host' => ['nullable', 'string', 'max:255'],
            'mail_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'mail_username' => ['nullable', 'string', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:255'],
            'mail_from_name' => ['nullable', 'string', 'max:100'],
            'mail_from_email' => EmailRules::nullable(100),
            'monthly_price' => ['nullable', 'integer', 'min:1'],
            'quarterly_discount' => ['nullable', 'integer', 'min:0', 'max:50'],
            'yearly_discount' => ['nullable', 'integer', 'min:0', 'max:70'],
        ], [
            ...EmailRules::messages('support_email'),
            ...EmailRules::messages('mail_from_email'),
        ]);

        $encryptedKeys = ['razorpay_key', 'razorpay_secret', 'razorpay_webhook_secret', 'mail_password'];

        foreach ($validated as $key => $value) {
            if ($value === null && in_array($key, $encryptedKeys, true)) {
                continue;
            }

            $isEncrypted = in_array($key, $encryptedKeys, true);
            $group = match (true) {
                str_starts_with($key, 'razorpay_') => 'razorpay',
                str_starts_with($key, 'mail_') => 'email',
                in_array($key, ['monthly_price', 'quarterly_discount', 'yearly_discount', 'gst_rate_india'], true) => 'pricing',
                default => 'general',
            };

            PlatformSetting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value,
                    'is_encrypted' => $isEncrypted,
                    'group' => $group,
                ]
            );
        }

        return back()->with('success', 'Settings updated successfully.');
    }
}

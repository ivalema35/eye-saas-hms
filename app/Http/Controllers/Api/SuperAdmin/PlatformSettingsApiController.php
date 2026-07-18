<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Platform\PlatformSetting;
use App\Support\EmailRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlatformSettingsApiController extends Controller
{
    private const ENCRYPTED_KEYS = [
        'razorpay_key', 'razorpay_secret', 'razorpay_webhook_secret', 'mail_password',
    ];

    private const ALL_KEYS = [
        'platform_name', 'support_email', 'trial_days',
        'razorpay_key', 'razorpay_secret', 'razorpay_webhook_secret',
        'mail_host', 'mail_port', 'mail_username', 'mail_password',
        'mail_from_name', 'mail_from_email',
    ];

    public function index(): JsonResponse
    {
        $settings = PlatformSetting::whereIn('key', self::ALL_KEYS)->get()->keyBy('key');

        $data = [];
        foreach (self::ALL_KEYS as $key) {
            $setting = $settings->get($key);
            // Never return raw encrypted values — return mask or null
            $data[$key] = in_array($key, self::ENCRYPTED_KEYS, true)
                ? ($setting ? '••••••••' : null)
                : $setting?->value;
        }

        $data['has_razorpay_key']            = (bool) $settings->get('razorpay_key');
        $data['has_razorpay_secret']         = (bool) $settings->get('razorpay_secret');
        $data['has_razorpay_webhook_secret'] = (bool) $settings->get('razorpay_webhook_secret');
        $data['has_mail_password']           = (bool) $settings->get('mail_password');

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'platform_name'           => ['required', 'string', 'max:100'],
            'support_email'           => EmailRules::required(100),
            'trial_days'              => ['required', 'integer', 'min:1', 'max:90'],
            'razorpay_key'            => ['nullable', 'string', 'max:255'],
            'razorpay_secret'         => ['nullable', 'string', 'max:255'],
            'razorpay_webhook_secret' => ['nullable', 'string', 'max:255'],
            'mail_host'               => ['nullable', 'string', 'max:255'],
            'mail_port'               => ['nullable', 'integer', 'min:1', 'max:65535'],
            'mail_username'           => ['nullable', 'string', 'max:255'],
            'mail_password'           => ['nullable', 'string', 'max:255'],
            'mail_from_name'          => ['nullable', 'string', 'max:100'],
            'mail_from_email'         => EmailRules::nullable(100),
        ], [
            ...EmailRules::messages('support_email'),
            ...EmailRules::messages('mail_from_email'),
        ]);

        foreach ($validated as $key => $value) {
            // Skip encrypted fields when the client sends null (unchanged)
            if ($value === null && in_array($key, self::ENCRYPTED_KEYS, true)) {
                continue;
            }

            $isEncrypted = in_array($key, self::ENCRYPTED_KEYS, true);
            $group = match (true) {
                str_starts_with($key, 'razorpay_') => 'razorpay',
                str_starts_with($key, 'mail_')     => 'email',
                default                             => 'general',
            };

            PlatformSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'is_encrypted' => $isEncrypted, 'group' => $group]
            );
        }

        return response()->json(['success' => true, 'message' => 'Settings saved successfully.']);
    }
}

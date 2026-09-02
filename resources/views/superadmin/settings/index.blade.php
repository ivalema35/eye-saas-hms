@extends('superadmin.layouts.app')

@section('title', 'Settings')
@section('page-header', 'Platform Settings')

@section('content')

    <form method="POST" action="{{ route('superadmin.settings.update') }}">
        @csrf
        @method('PUT')

        {{-- General Settings --}}
        <div class="hms-card" style="margin-bottom:1.25rem">
            <div class="hms-card-header">
                <h3 class="hms-card-title">
                    <i class="bi bi-gear-fill" style="color:#1B4F72"></i>
                    General Settings
                </h3>
            </div>
            <div class="hms-form-grid-2" style="padding:1.25rem">
                <div class="hms-form-group">
                    <label class="hms-label">Platform Name</label>
                    <input type="text" name="platform_name" class="hms-input @error('platform_name') is-invalid @enderror"
                        value="{{ old('platform_name', $settings->get('platform_name')?->value ?? 'EYENOSIS') }}">
                    @error('platform_name') <span class="hms-error">{{ $message }}</span> @enderror
                </div>
                <div class="hms-form-group">
                    <label class="hms-label">Support Email</label>
                    <input type="email" name="support_email" class="hms-input @error('support_email') is-invalid @enderror"
                        value="{{ old('support_email', $settings->get('support_email')?->value ?? '') }}"
                        placeholder="support@eyehms.com">
                    @error('support_email') <span class="hms-error">{{ $message }}</span> @enderror
                </div>
                <div class="hms-form-group" style="margin-bottom:0">
                    <label class="hms-label">Trial Days (new registrations)</label>
                    <input type="number" name="trial_days" class="hms-input @error('trial_days') is-invalid @enderror"
                        value="{{ old('trial_days', $settings->get('trial_days')?->value ?? 14) }}" min="1" max="90">
                    @error('trial_days') <span class="hms-error">{{ $message }}</span> @enderror
                </div>
                <div class="hms-form-group" style="margin-bottom:0">
                    <label class="hms-label">GST Rate — India (%)</label>
                    <input type="number" name="gst_rate_india" class="hms-input @error('gst_rate_india') is-invalid @enderror"
                        value="{{ old('gst_rate_india', $settings->get('gst_rate_india')?->value ?? 18) }}" min="0" max="100" step="0.01">
                    @error('gst_rate_india') <span class="hms-error">{{ $message }}</span> @enderror
                    <small class="text-muted">Applied on registration plan total for India only. Other countries: 0%.</small>
                </div>
            </div>
        </div>

        {{-- Razorpay Settings --}}
        <div class="hms-card" style="margin-bottom:1.25rem">
            <div class="hms-card-header">
                <h3 class="hms-card-title">
                    <i class="bi bi-credit-card-fill" style="color:#1B4F72"></i>
                    Razorpay Configuration
                </h3>
            </div>
            <div style="padding:1.25rem">
                <div class="hms-alert hms-alert-warning" style="margin-bottom:1rem">
                    <i class="bi bi-shield-fill"></i>
                    <span>Razorpay keys are stored encrypted. Leave blank to keep existing values.</span>
                </div>
                <div class="hms-form-grid-2">
                    <div class="hms-form-group">
                        <label class="hms-label">Razorpay Key ID</label>
                        <input type="text" name="razorpay_key" class="hms-input" value="{{ old('razorpay_key') }}"
                            placeholder="rzp_live_xxxxx or rzp_test_xxxxx">
                    </div>
                    <div class="hms-form-group">
                        <label class="hms-label">Razorpay Secret</label>
                        <input type="password" name="razorpay_secret" class="hms-input" value="{{ old('razorpay_secret') }}"
                            placeholder="••••••••">
                    </div>
                    <div class="hms-form-group" style="margin-bottom:0">
                        <label class="hms-label">Webhook Secret</label>
                        <input type="password" name="razorpay_webhook_secret" class="hms-input"
                            value="{{ old('razorpay_webhook_secret') }}" placeholder="••••••••">
                    </div>
                </div>
            </div>
        </div>

        {{-- Email / SMTP --}}
        <div class="hms-card" style="margin-bottom:1.25rem">
            <div class="hms-card-header">
                <h3 class="hms-card-title">
                    <i class="bi bi-envelope-fill" style="color:#1B4F72"></i>
                    Email / SMTP Configuration
                </h3>
            </div>
            <div style="padding:1.25rem">
                <div class="hms-alert hms-alert-info" style="margin-bottom:1rem">
                    <i class="bi bi-info-circle-fill"></i>
                    <span>Platform emails (welcome, subscription, notifications) use these SMTP settings — not .env. Save first, then send a test email.</span>
                </div>
                <div class="hms-form-grid-2">
                    <div class="hms-form-group">
                        <label class="hms-label">SMTP Host</label>
                        <input type="text" name="mail_host" class="hms-input"
                            value="{{ old('mail_host', $settings->get('mail_host')?->value) }}"
                            placeholder="smtp.gmail.com">
                    </div>
                    <div class="hms-form-group">
                        <label class="hms-label">SMTP Port</label>
                        <input type="number" name="mail_port" class="hms-input"
                            value="{{ old('mail_port', $settings->get('mail_port')?->value ?? 587) }}" placeholder="587">
                    </div>
                    <div class="hms-form-group">
                        <label class="hms-label">SMTP Username</label>
                        <input type="text" name="mail_username" class="hms-input"
                            value="{{ old('mail_username', $settings->get('mail_username')?->value) }}"
                            placeholder="your@email.com">
                    </div>
                    <div class="hms-form-group">
                        <label class="hms-label">SMTP Password <small style="font-weight:400">(leave blank to keep
                                existing)</small></label>
                        <input type="password" name="mail_password" class="hms-input" placeholder="••••••••">
                    </div>
                    <div class="hms-form-group" style="margin-bottom:0">
                        <label class="hms-label">From Name</label>
                        <input type="text" name="mail_from_name" class="hms-input"
                            value="{{ old('mail_from_name', $settings->get('mail_from_name')?->value ?? config('app.name')) }}">
                    </div>
                    <div class="hms-form-group" style="margin-bottom:0">
                        <label class="hms-label">From Email</label>
                        <input type="email" name="mail_from_email" class="hms-input"
                            value="{{ old('mail_from_email', $settings->get('mail_from_email')?->value) }}"
                            placeholder="noreply@yourdomain.com">
                    </div>
                </div>

                <div style="margin-top:1.25rem;padding-top:1.25rem;border-top:1px solid #E2E8F0">
                    <div style="display:flex;flex-wrap:wrap;align-items:flex-end;gap:.75rem">
                        <div class="hms-form-group" style="margin-bottom:0;flex:1;min-width:220px">
                            <label class="hms-label">Test recipient email</label>
                            <input type="email" form="smtp-test-form" name="test_email" class="hms-input @error('test_email') is-invalid @enderror"
                                value="{{ old('test_email', $settings->get('support_email')?->value ?? auth('superadmin')->user()?->email) }}"
                                placeholder="you@example.com" required>
                            @error('test_email') <span class="hms-error">{{ $message }}</span> @enderror
                        </div>
                        <button type="submit" form="smtp-test-form" class="hms-btn hms-btn-outline" style="white-space:nowrap">
                            <i class="bi bi-send-fill"></i> Send Test Email
                        </button>
                    </div>
                    <small class="text-muted" style="display:block;margin-top:.5rem">Uses saved SMTP settings. Save changes above before testing new credentials.</small>
                </div>
            </div>
        </div>

        {{-- Subscription Pricing --}}
        <div class="hms-card" style="margin-bottom:1.25rem">
            <div class="hms-card-header">
                <h3 class="hms-card-title">
                    <i class="bi bi-cash-coin" style="color:#1b4f72"></i>
                    Subscription Pricing ({{ platform_currency_symbol() }})
                </h3>
                <a href="{{ route('superadmin.plans.index') }}" class="hms-btn hms-btn-outline hms-btn-sm">
                    <i class="bi bi-layers-fill"></i> Manage Plans
                </a>
            </div>
            <div class="hms-form-grid-3" style="padding:1.25rem">
                <div class="hms-form-group" style="margin-bottom:0">
                    <label class="hms-label">Monthly Base Price</label>
                    <input type="number" name="monthly_price" class="hms-input"
                        value="{{ old('monthly_price', $settings->get('monthly_price')?->value ?? 999) }}" min="1">
                </div>
                <div class="hms-form-group" style="margin-bottom:0">
                    <label class="hms-label">Quarterly Discount %</label>
                    <input type="number" name="quarterly_discount" class="hms-input"
                        value="{{ old('quarterly_discount', $settings->get('quarterly_discount')?->value ?? 10) }}" min="0"
                        max="50">
                </div>
                <div class="hms-form-group" style="margin-bottom:0">
                    <label class="hms-label">Yearly Discount %</label>
                    <input type="number" name="yearly_discount" class="hms-input"
                        value="{{ old('yearly_discount', $settings->get('yearly_discount')?->value ?? 20) }}" min="0"
                        max="70">
                </div>
            </div>
        </div>

        {{-- Submit --}}
        <div style="display:flex;justify-content:flex-end;gap:.75rem">
            <a href="{{ route('superadmin.dashboard') }}" class="hms-btn hms-btn-outline">Cancel</a>
            <button type="submit" class="hms-btn hms-btn-primary" style="color: #1b4f72;">
                <i class="bi bi-floppy-fill"></i> Save Settings
            </button>
        </div>
    </form>

    <form id="smtp-test-form" method="POST" action="{{ route('superadmin.settings.test-mail') }}" style="display:none">
        @csrf
    </form>

@endsection
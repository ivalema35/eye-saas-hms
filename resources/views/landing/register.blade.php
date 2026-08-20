@extends('landing.layouts.app')

@section('title', 'Register Your Hospital — EYENOSIS')
@section('meta_description')
    Start your free {{ $platformTrialLabel }} trial of EYENOSIS — complete hospital management software for eye clinics. No
    credit card required.
@endsection

@push('styles')
    <style>
        .password-field-wrap {
            position: relative !important;
        }

        .password-field-input {
            padding-right: 3rem !important;
        }

        .password-field-toggle {
            position: absolute !important;
            right: .7rem;
            top: 50%;
            transform: translateY(-50%) !important;
            border: 1px solid rgba(0, 0, 0, .08) !important;
            background: #fff !important;
            padding: 0 !important;
            margin: 0 !important;
            color: #000 !important;
            width: 2rem !important;
            height: 2rem !important;
            border-radius: 999px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            cursor: pointer !important;
            line-height: 1 !important;
            z-index: 3 !important;
            overflow: hidden !important;
            box-shadow: 0 1px 2px rgba(0, 0, 0, .04);
            transition: color .2s ease, border-color .2s ease, box-shadow .2s ease, transform .15s ease;
        }

        .password-field-toggle:hover {
            color: #000 !important;
            border-color: rgba(0, 0, 0, .16) !important;
            box-shadow: 0 4px 10px rgba(0, 0, 0, .08) !important;
            transform: translateY(-50%) scale(1.02) !important;
        }

        .password-field-toggle:focus-visible {
            outline: 2px solid rgba(0, 0, 0, .22) !important;
            outline-offset: 2px !important;
        }

        .password-field-toggle svg {
            width: 1rem !important;
            height: 1rem !important;
            display: block !important;
            stroke: currentColor !important;
            fill: none !important;
            color: #000 !important;
            pointer-events: none !important;
        }
    </style>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.min.css">
    <style>
        /* Tom Select — HMS form integration */
        .ts-wrapper {
            font-size: .9375rem;
        }

        .ts-wrapper .ts-control {
            border: 1px solid var(--hms-border, #dce3ed) !important;
            border-radius: var(--hms-radius-sm, 6px) !important;
            padding: .5rem .875rem !important;
            background: #fff !important;
            box-shadow: none !important;
            min-height: 42px;
            cursor: pointer;
            align-items: center;
        }

        .ts-wrapper.focus .ts-control {
            border-color: var(--hms-primary, #1B4F72) !important;
            box-shadow: 0 0 0 3px rgba(27, 79, 114, .1) !important;
            outline: none !important;
        }

        .ts-wrapper .ts-control input {
            font-size: .9375rem !important;
            color: var(--hms-text, #1a2535) !important;
            padding: 0 !important;
        }

        .ts-wrapper .ts-control .placeholder {
            color: var(--hms-text-muted, #8fa1b5) !important;
            font-size: .9375rem;
        }

        .ts-wrapper.disabled .ts-control {
            background: #f5f7fa !important;
            cursor: not-allowed !important;
            opacity: .65;
        }

        .ts-dropdown {
            border: 1px solid var(--hms-border, #dce3ed) !important;
            border-radius: var(--hms-radius-sm, 6px) !important;
            box-shadow: 0 4px 16px rgba(0, 0, 0, .1) !important;
            font-size: .9375rem;
        }

        .ts-dropdown .option {
            padding: .5rem .875rem;
            cursor: pointer;
        }

        .ts-dropdown .option.active {
            background: rgba(27, 79, 114, .08) !important;
            color: var(--hms-text, #1a2535) !important;
        }

        .ts-dropdown .option.selected {
            background: rgba(27, 79, 114, .14) !important;
            font-weight: 600;
        }

        .ts-dropdown .no-results {
            padding: .5rem .875rem;
            color: var(--hms-text-muted, #8fa1b5);
            font-size: .875rem;
        }

        /* ── Registration step wizard ─────────────────────────────────── */
        .reg-stepper {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .35rem;
            margin: 0 0 1.35rem;
            padding: 0 .15rem;
        }

        .reg-step {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .4rem;
            background: none;
            border: 0;
            padding: 0;
            cursor: pointer;
            position: relative;
            min-width: 0;
            color: #94a3b8;
            transition: color .2s ease;
        }

        .reg-step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 16px;
            left: calc(50% + 18px);
            right: calc(-50% + 18px);
            height: 2px;
            background: #e2e8f0;
            z-index: 0;
            transition: background .25s ease;
        }

        .reg-step.is-done:not(:last-child)::after,
        .reg-step.is-active:not(:last-child)::after {
            background: linear-gradient(90deg, #1B4F72, #2980B9);
        }

        .reg-step-num {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: .8rem;
            font-weight: 800;
            border: 2px solid #e2e8f0;
            background: #fff;
            color: #94a3b8;
            position: relative;
            z-index: 1;
            transition: all .2s ease;
        }

        .reg-step-label {
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .01em;
            text-align: center;
            line-height: 1.2;
            max-width: 6.2rem;
        }

        .reg-step.is-active {
            color: #1B4F72;
        }

        .reg-step.is-active .reg-step-num {
            background: linear-gradient(135deg, #1B4F72, #2980B9);
            border-color: #1B4F72;
            color: #fff;
            box-shadow: 0 6px 14px rgba(27, 79, 114, .28);
        }

        .reg-step.is-done {
            color: #1B4F72;
        }

        .reg-step.is-done .reg-step-num {
            background: #d6eaf8;
            border-color: #2980B9;
            color: #1B4F72;
        }

        .reg-step.is-done .reg-step-num i {
            font-size: .75rem;
        }

        .reg-step:hover:not(:disabled) .reg-step-num {
            border-color: #2980B9;
            transform: translateY(-1px);
        }

        .reg-step:disabled {
            cursor: not-allowed;
            opacity: .55;
        }

        .reg-step-panel {
            display: none;
            animation: regStepIn .28s ease;
        }

        .reg-step-panel.is-active {
            display: block;
        }

        @keyframes regStepIn {
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .reg-step-nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            margin-top: 1.15rem;
            padding-top: 1rem;
            border-top: 1px solid #eef2f6;
        }

        .reg-step-nav .reg-btn-prev,
        .reg-step-nav .reg-btn-next {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            border-radius: 10px;
            font-weight: 700;
            font-size: .9rem;
            padding: .7rem 1.15rem;
            border: 1.5px solid transparent;
            cursor: pointer;
            transition: all .2s ease;
        }

        .reg-btn-prev {
            background: #fff;
            border-color: #dce3ed !important;
            color: #1B4F72;
        }

        .reg-btn-prev:hover {
            background: #f0f7fb;
            border-color: #2980B9 !important;
        }

        .reg-btn-prev[hidden] {
            visibility: hidden;
            pointer-events: none;
        }

        .reg-btn-next {
            background: linear-gradient(135deg, #1B4F72, #2980B9);
            color: #fff;
            margin-left: auto;
            box-shadow: 0 8px 18px rgba(27, 79, 114, .22);
        }

        .reg-btn-next:hover {
            filter: brightness(1.05);
            transform: translateY(-1px);
        }

        .reg-step-error {
            display: none;
            margin: 0 0 .85rem;
            padding: .65rem .85rem;
            border-radius: 10px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            font-size: .82rem;
            font-weight: 600;
        }

        .reg-step-error.is-visible {
            display: block;
        }

        @media (max-width: 576px) {
            .reg-step-label {
                font-size: .62rem;
                max-width: 4.2rem;
            }

            .reg-step-num {
                width: 28px;
                height: 28px;
                font-size: .72rem;
            }

            .reg-step:not(:last-child)::after {
                top: 14px;
                left: calc(50% + 15px);
                right: calc(-50% + 15px);
            }
        }
    </style>
@endpush

@section('content')

    <div class="reg-page-body">
        <div class="reg-body">
            <div class="reg-shell">

                {{-- Left: value prop --}}
                <aside class="reg-left-panel">
                    <span class="reg-aside-badge">
                        <i class="fa-solid fa-shield-halved"></i> Free trial
                    </span>
                    <h3>Start your free {{ $platformTrialLabel }} trial</h3>
                    <p class="reg-aside-lead">
                        No credit card required. Full hospital CRM access from day one.
                    </p>

                    <ul class="reg-benefits">
                        <li>
                            <span class="rb-icon"><i class="fa-solid fa-check"></i></span>
                            <span>Complete Hospital Management System</span>
                        </li>
                        <li>
                            <span class="rb-icon"><i class="fa-solid fa-check"></i></span>
                            <span>Patient Records, Appointments &amp; Billing</span>
                        </li>
                        <li>
                            <span class="rb-icon"><i class="fa-solid fa-check"></i></span>
                            <span>Eye Examination &amp; OT Surgery Modules</span>
                        </li>
                        <li>
                            <span class="rb-icon"><i class="fa-solid fa-check"></i></span>
                            <span>Multi-user with Role-based Permissions</span>
                        </li>
                        <li>
                            <span class="rb-icon"><i class="fa-solid fa-check"></i></span>
                            <span>Secure Cloud — Access from Anywhere</span>
                        </li>
                        <li>
                            <span class="rb-icon"><i class="fa-solid fa-check"></i></span>
                            <span>Daily Automatic Backups</span>
                        </li>
                    </ul>

                    <div class="reg-stat-grid">
                        <div class="reg-stat-mini">
                            <span class="rsm-num">{{ $platformTrialDays }}</span>
                            <span class="rsm-lbl">Days Free</span>
                        </div>
                        <div class="reg-stat-mini">
                            <span class="rsm-num">OPD+OT</span>
                            <span class="rsm-lbl">Full stack</span>
                        </div>
                        <div class="reg-stat-mini">
                            <span class="rsm-num">Roles</span>
                            <span class="rsm-lbl">Desk ready</span>
                        </div>
                        <div class="reg-stat-mini">
                            <span class="rsm-num">Cloud</span>
                            <span class="rsm-lbl">Anywhere</span>
                        </div>
                    </div>

                </aside>

                {{-- Right: form --}}
                <div class="reg-form-card">
                    <div class="reg-form-head">
                        <div class="reg-form-head-row">
                            <div>
                                <h2>
                                    <span class="reg-head-ico"><i class="fa-solid fa-hospital-user"></i></span>
                                    Create your account
                                </h2>
                            </div>
                            <p class="reg-login-hint">
                                Already registered?
                                <a href="{{ route('login') }}">Login</a>
                                </p>
                            </div>
                        </div>

                        <div class="reg-form-body">

                            <form method="POST" action="{{ route('register.store') }}" id="registerForm">
                                @csrf

                                {{-- Step indicator --}}
                                <nav class="reg-stepper" id="regStepper" aria-label="Registration steps">
                                    <button type="button" class="reg-step is-active" data-step="1" aria-current="step">
                                        <span class="reg-step-num">1</span>
                                        <span class="reg-step-label">Hospital</span>
                                    </button>
                                    <button type="button" class="reg-step" data-step="2" disabled>
                                        <span class="reg-step-num">2</span>
                                        <span class="reg-step-label">Location &amp; Plan</span>
                                    </button>
                                    <button type="button" class="reg-step" data-step="3" disabled>
                                        <span class="reg-step-num">3</span>
                                        <span class="reg-step-label">Admin</span>
                                    </button>
                                </nav>

                                <div class="reg-step-error" id="regStepError" role="alert"></div>

                                {{-- Step 1: Hospital Details --}}
                                <div class="reg-step-panel is-active" data-step-panel="1">
                                    <div class="reg-section">
                                        <div class="reg-section-label"><i class="fa-solid fa-hospital"></i> Hospital Details</div>

                                        <div class="hms-form-group">
                                            <label>Hospital Name <span class="hms-required">*</span></label>
                                            <input type="text" name="hospital_name"
                                                class="hms-input @error('hospital_name') is-invalid @enderror"
                                                value="{{ old('hospital_name') }}" placeholder="e.g. Vision Eye Centre" required
                                                id="hospitalName">
                                            @error('hospital_name')
                                                <span class="hms-form-error"><i class="fa-solid fa-circle-exclamation"></i>
                                                    {{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="hms-form-group">
                                            <label>Hospital URL Slug <span class="hms-required">*</span></label>
                                            <div class="slug-input-wrap">
                                                <span class="slug-prefix">{{ parse_url(url('/'), PHP_URL_HOST) }}/</span>
                                                <input type="text" name="slug" id="slugInput"
                                                    class="slug-input-field @error('slug') is-invalid @enderror"
                                                    value="{{ old('slug') }}" placeholder="vision-eye-centre" required
                                                    pattern="[a-z0-9\-]+" minlength="3" maxlength="30">
                                            </div>
                                            <div class="slug-status" id="slugStatus"></div>
                                            @error('slug')
                                                <span class="hms-form-error"><i class="fa-solid fa-circle-exclamation"></i>
                                                    {{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="hms-form-group">
                                            <label>Hospital Code <span class="hms-required">*</span> <span
                                                    style="font-weight:400;color:#5d7084;font-size:.8rem">(3–4 letters — MRD prefix)</span></label>
                                            <div class="reg-code-row">
                                                <input type="text" name="hospital_code" id="hospitalCodeInput"
                                                    class="hms-input @error('hospital_code') is-invalid @enderror"
                                                    value="{{ old('hospital_code') }}"
                                                    placeholder="e.g. MAI" required maxlength="4">
                                                <span class="reg-mrd-preview">MRD preview: <strong
                                                        id="mrdPreview">---0001</strong></span>
                                            </div>
                                            <div class="slug-status" id="codeStatus"></div>
                                            @error('hospital_code')
                                                <span class="hms-form-error"><i class="fa-solid fa-circle-exclamation"></i>
                                                    {{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                {{-- Step 2: Location & Pricing --}}
                                <div class="reg-step-panel" data-step-panel="2">
                                    <div class="reg-section">
                                        <div class="reg-section-label"><i class="fa-solid fa-location-dot"></i> Location</div>

                                        <div class="form-row-2">
                                            <div class="hms-form-group">
                                                <label>Country <span class="hms-required">*</span></label>
                                                <select name="country" id="regCountry" class="hms-select">
                                                    <option value="">Search or Add Country</option>
                                                    @foreach($countries as $c)
                                                        <option value="{{ $c->name }}"
                                                                data-id="{{ $c->id }}"
                                                                data-country-code="{{ $c->country_code ?? '' }}"
                                                                data-currency-code="{{ $c->currency_code ?? 'INR' }}"
                                                                data-currency-symbol="{{ $c->currency_symbol ?? '₹' }}"
                                                                data-fx="{{ (float) ($c->fx_inr_per_unit ?: 1) }}"
                                                                {{ old('country') === $c->name ? 'selected' : '' }}>
                                                            {{ $c->name }}@if($c->country_code) ({{ $c->country_code }})@endif
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="hms-form-group">
                                                <label>State</label>
                                                <select name="state" id="regState" class="hms-select" disabled>
                                                    <option value="">Search or Add State</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-row-2">
                                            <div class="hms-form-group">
                                                <label>District</label>
                                                <select name="district" id="regDistrict" class="hms-select" disabled>
                                                    <option value="">Search or Add District</option>
                                                </select>
                                            </div>
                                            <div class="hms-form-group">
                                                <label>City</label>
                                                <select name="city" id="regCity" class="hms-select" disabled>
                                                    <option value="">Search or Add City</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="reg-section">
                                        <div class="reg-section-label"><i class="fa-solid fa-credit-card"></i> Plan After Trial</div>

                                        <div class="plan-cards" id="regPlanCards"
                                             data-monthly="{{ $planPricing['monthly']['price'] }}"
                                             data-quarterly="{{ $planPricing['quarterly']['price'] }}"
                                             data-yearly="{{ $planPricing['yearly']['price'] }}">
                                            <label class="plan-card-label">
                                                <input type="radio" name="plan" value="monthly" {{ old('plan', request('plan', 'monthly')) === 'monthly' ? 'checked' : '' }}>
                                                <div class="plan-card-inner">
                                                    <span class="pc-name">Monthly</span>
                                                    <span class="pc-price" data-plan="monthly">{{ platform_currency_symbol() }}{{ number_format($planPricing['monthly']['price']) }}/mo</span>
                                                </div>
                                            </label>
                                            <label class="plan-card-label">
                                                <input type="radio" name="plan" value="quarterly" {{ old('plan', request('plan')) === 'quarterly' ? 'checked' : '' }}>
                                                <div class="plan-card-inner">
                                                    <span class="pc-name">Quarterly</span>
                                                    <span class="pc-price" data-plan="quarterly">{{ platform_currency_symbol() }}{{ number_format($planPricing['quarterly']['price']) }}/qtr</span>
                                                    <span class="pc-save">Save 10% &#9733; Popular</span>
                                                </div>
                                            </label>
                                            <label class="plan-card-label">
                                                <input type="radio" name="plan" value="yearly" {{ old('plan', request('plan')) === 'yearly' ? 'checked' : '' }}>
                                                <div class="plan-card-inner">
                                                    <span class="pc-name">Yearly</span>
                                                    <span class="pc-price" data-plan="yearly">{{ platform_currency_symbol() }}{{ number_format($planPricing['yearly']['price']) }}/yr</span>
                                                    <span class="pc-save">Save 20%</span>
                                                </div>
                                            </label>
                                        </div>
                                        <p id="regPlanCurrencyHint" style="margin-top:.65rem;font-size:.75rem;color:#5d7084">
                                            Prices shown in <strong id="regPlanCurrencyLabel">{{ platform_currency_code() }}</strong> based on selected country.
                                        </p>
                                        <div id="regPlanTaxBox" class="reg-plan-tax" style="margin-top:.75rem;padding:.75rem .9rem;border:1px solid #dde3ea;border-radius:10px;background:#f8fafc;font-size:.8125rem;">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span style="color:#64748b">Plan amount</span>
                                                <strong id="regPlanSubtotal" style="color:#1B4F72">—</strong>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mb-1" id="regGstRow" style="display:none!important;">
                                                <span style="color:#64748b">GST (<span id="regGstRateLabel">{{ (int) $platformGstRateIndia }}</span>%)</span>
                                                <strong id="regPlanGst" style="color:#1B4F72">—</strong>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center pt-1 mt-1" style="border-top:1px solid #e2e8f0;">
                                                <span style="color:#334155;font-weight:600">Total after trial</span>
                                                <strong id="regPlanTotal" style="color:#1B4F72;font-size:.95rem">—</strong>
                                            </div>
                                        </div>
                                        @error('plan')
                                            <span class="hms-form-error" style="margin-top:.5rem"><i
                                                    class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Step 3: Admin Account --}}
                                <div class="reg-step-panel" data-step-panel="3">
                                    <div class="reg-section">
                                        <div class="reg-section-label"><i class="fa-solid fa-user-tie"></i> Admin Account</div>

                                        <div class="form-row-2">
                                            <div class="hms-form-group">
                                                <label>Admin Name <span class="hms-required">*</span></label>
                                                <input type="text" name="admin_name"
                                                    class="hms-input @error('admin_name') is-invalid @enderror"
                                                    value="{{ old('admin_name') }}" placeholder="Dr. John Smith" required>
                                                @error('admin_name')
                                                    <span class="hms-form-error"><i class="fa-solid fa-circle-exclamation"></i>
                                                        {{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="hms-form-group">
                                                <label>Admin Email <span class="hms-required">*</span></label>
                                                <input type="email" name="admin_email"
                                                    class="hms-input @error('admin_email') is-invalid @enderror"
                                                    value="{{ old('admin_email') }}" placeholder="admin@hospital.com" required>
                                                @error('admin_email')
                                                    <span class="hms-form-error"><i class="fa-solid fa-circle-exclamation"></i>
                                                        {{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="hms-form-group">
                                            <label>Phone Number <span class="hms-required">*</span></label>
                                            <input type="tel" name="admin_phone"
                                                class="hms-input @error('admin_phone') is-invalid @enderror"
                                                value="{{ old('admin_phone') }}" placeholder="+919876543210" required
                                                data-intl-phone>
                                            @error('admin_phone')
                                                <span class="hms-form-error"><i class="fa-solid fa-circle-exclamation"></i>
                                                    {{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="reg-section">
                                        <div class="reg-section-label"><i class="fa-solid fa-lock"></i> Security</div>

                                        <div class="form-row-2">
                                            <div class="hms-form-group">
                                                <label>Password <span class="hms-required">*</span></label>
                                                <div class="password-field-wrap">
                                                    <input type="password" name="password" id="registerPassword"
                                                        class="hms-input password-field-input @error('password') is-invalid @enderror"
                                                        placeholder="Min 8 characters" required minlength="8">
                                                    <button type="button" id="toggleRegisterPassword" class="password-field-toggle"
                                                        aria-label="Toggle password visibility">
                                                        <svg id="regPassEye" viewBox="0 0 24 24" aria-hidden="true">
                                                            <path d="M1.5 12s3.5-7 10.5-7 10.5 7 10.5 7-3.5 7-10.5 7S1.5 12 1.5 12Z"
                                                                stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                            </path>
                                                            <circle cx="12" cy="12" r="3.2" stroke-width="1.8"></circle>
                                                        </svg>
                                                    </button>
                                                </div>
                                                @error('password')
                                                    <span class="hms-form-error"><i class="fa-solid fa-circle-exclamation"></i>
                                                        {{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="hms-form-group">
                                                <label>Confirm Password <span class="hms-required">*</span></label>
                                                <div class="password-field-wrap">
                                                    <input type="password" name="password_confirmation" id="registerPasswordConfirm"
                                                        class="hms-input password-field-input" placeholder="Repeat password" required
                                                        minlength="8">
                                                    <button type="button" id="toggleRegisterPasswordConfirm"
                                                        class="password-field-toggle" aria-label="Toggle confirm password visibility">
                                                        <svg id="regPassConfirmEye" viewBox="0 0 24 24" aria-hidden="true">
                                                            <path d="M1.5 12s3.5-7 10.5-7 10.5 7 10.5 7-3.5 7-10.5 7S1.5 12 1.5 12Z"
                                                                stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                            </path>
                                                            <circle cx="12" cy="12" r="3.2" stroke-width="1.8"></circle>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <button type="submit" class="reg-submit" id="regSubmitBtn">
                                        <i class="fa-solid fa-rocket"></i> Start {{ $platformTrialDays }}-Day Free Trial
                                    </button>

                                    <p class="reg-legal">
                                        By registering, you agree to our <a href="#">Terms of Service</a>
                                        and <a href="#">Privacy Policy</a>.
                                    </p>
                                </div>

                                <div class="reg-step-nav" id="regStepNav">
                                    <button type="button" class="reg-btn-prev" id="regBtnPrev" hidden>
                                        <i class="fa-solid fa-arrow-left"></i> Previous
                                    </button>
                                    <button type="button" class="reg-btn-next" id="regBtnNext">
                                        Next <i class="fa-solid fa-arrow-right"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </div>

@endsection

@push('scripts')
    <script>
        (function () {
            var TOTAL = 3;
            var current = 1;
            var maxReached = 1;
            var form = document.getElementById('registerForm');
            var btnPrev = document.getElementById('regBtnPrev');
            var btnNext = document.getElementById('regBtnNext');
            var stepError = document.getElementById('regStepError');
            var stepNav = document.getElementById('regStepNav');

            if (!form || !btnNext) return;

            function showError(msg) {
                if (!stepError) return;
                stepError.textContent = msg;
                stepError.classList.add('is-visible');
            }

            function clearError() {
                if (!stepError) return;
                stepError.textContent = '';
                stepError.classList.remove('is-visible');
            }

            function markField(el, invalid) {
                if (!el) return;
                el.classList.toggle('is-invalid', !!invalid);
            }

            function validateStep(step) {
                clearError();
                if (step === 1) {
                    var name = form.querySelector('[name="hospital_name"]');
                    var slug = form.querySelector('[name="slug"]');
                    var code = form.querySelector('[name="hospital_code"]');
                    markField(name, !name.value.trim());
                    markField(slug, !slug.value.trim() || slug.value.trim().length < 3);
                    markField(code, !/^[A-Za-z]{3,4}$/.test((code.value || '').trim()));
                    if (!name.value.trim()) { showError('Please enter hospital name.'); name.focus(); return false; }
                    if (!slug.value.trim() || slug.value.trim().length < 3) { showError('Slug must be at least 3 characters.'); slug.focus(); return false; }
                    if (!/^[A-Za-z]{3,4}$/.test((code.value || '').trim())) { showError('Hospital code must be 3–4 letters.'); code.focus(); return false; }
                    return true;
                }
                if (step === 2) {
                    var country = form.querySelector('[name="country"]');
                    var countryVal = country ? country.value.trim() : '';
                    // Tom Select keeps value on the original select
                    if (!countryVal) {
                        showError('Please select a country — plan prices depend on it.');
                        return false;
                    }
                    var plan = form.querySelector('input[name="plan"]:checked');
                    if (!plan) { showError('Please select a plan.'); return false; }
                    return true;
                }
                if (step === 3) {
                    var adminName = form.querySelector('[name="admin_name"]');
                    var adminEmail = form.querySelector('[name="admin_email"]');
                    var adminPhone = form.querySelector('[name="admin_phone"]');
                    var pass = form.querySelector('[name="password"]');
                    var pass2 = form.querySelector('[name="password_confirmation"]');
                    markField(adminName, !adminName.value.trim());
                    markField(adminEmail, !adminEmail.value.trim() || !adminEmail.checkValidity());
                    markField(adminPhone, !adminPhone.value.trim());
                    markField(pass, !pass.value || pass.value.length < 8);
                    markField(pass2, pass.value !== pass2.value);
                    if (!adminName.value.trim()) { showError('Please enter admin name.'); adminName.focus(); return false; }
                    if (!adminEmail.value.trim() || !adminEmail.checkValidity()) { showError('Please enter a valid admin email.'); adminEmail.focus(); return false; }
                    if (!adminPhone.value.trim()) { showError('Please enter phone number.'); adminPhone.focus(); return false; }
                    if (!pass.value || pass.value.length < 8) { showError('Password must be at least 8 characters.'); pass.focus(); return false; }
                    if (pass.value !== pass2.value) { showError('Password and confirm password do not match.'); pass2.focus(); return false; }
                    return true;
                }
                return true;
            }

            function goTo(step, skipValidate) {
                step = Math.max(1, Math.min(TOTAL, step));
                if (step > current && !skipValidate) {
                    for (var s = current; s < step; s++) {
                        if (!validateStep(s)) return;
                    }
                } else if (step === current + 1 && !skipValidate) {
                    if (!validateStep(current)) return;
                }

                if (step > maxReached) maxReached = step;
                current = step;
                clearError();

                form.querySelectorAll('[data-step-panel]').forEach(function (panel) {
                    panel.classList.toggle('is-active', parseInt(panel.getAttribute('data-step-panel'), 10) === current);
                });

                form.querySelectorAll('.reg-step').forEach(function (btn) {
                    var n = parseInt(btn.getAttribute('data-step'), 10);
                    var isActive = n === current;
                    var isDone = n < current;
                    btn.classList.toggle('is-active', isActive);
                    btn.classList.toggle('is-done', isDone);
                    btn.disabled = n > maxReached;
                    btn.setAttribute('aria-current', isActive ? 'step' : 'false');

                    var numEl = btn.querySelector('.reg-step-num');
                    if (numEl) {
                        if (isDone) {
                            numEl.innerHTML = '<i class="fa-solid fa-check"></i>';
                        } else {
                            numEl.textContent = String(n);
                        }
                    }
                });

                if (btnPrev) btnPrev.hidden = current === 1;
                if (btnNext) btnNext.hidden = current === TOTAL;
                if (stepNav) stepNav.style.display = current === TOTAL ? 'flex' : 'flex';
                // On last step keep Previous visible; hide Next
                if (btnNext) {
                    btnNext.style.display = current === TOTAL ? 'none' : 'inline-flex';
                }

                var card = form.closest('.reg-form-card') || form;
                if (card && typeof card.scrollIntoView === 'function') {
                    card.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }

            btnNext.addEventListener('click', function () {
                if (!validateStep(current)) return;
                goTo(current + 1, true);
            });

            if (btnPrev) {
                btnPrev.addEventListener('click', function () {
                    goTo(current - 1, true);
                });
            }

            form.querySelectorAll('.reg-step').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var n = parseInt(btn.getAttribute('data-step'), 10);
                    if (n > maxReached) return;
                    if (n > current) {
                        goTo(n, false);
                    } else {
                        goTo(n, true);
                    }
                });
            });

            // If server validation errors exist, open the relevant step
            var errorFields = {
                1: ['hospital_name', 'slug', 'hospital_code'],
                2: ['country', 'state', 'district', 'city'],
                3: ['admin_name', 'admin_email', 'admin_phone', 'password'],
                4: ['plan']
            };
            var startStep = 1;
            Object.keys(errorFields).forEach(function (stepKey) {
                errorFields[stepKey].forEach(function (field) {
                    if (form.querySelector('[name="' + field + '"].is-invalid') ||
                        form.querySelector('.hms-form-error') && form.querySelector('[name="' + field + '"]')?.closest('.hms-form-group')?.querySelector('.hms-form-error')) {
                        // Prefer earliest error step
                        var el = form.querySelector('[name="' + field + '"]');
                        if (el && el.closest('.hms-form-group') && el.closest('.hms-form-group').querySelector('.hms-form-error')) {
                            var s = parseInt(stepKey, 10);
                            if (startStep === 1 || s < startStep) startStep = s;
                        }
                    }
                });
            });
            @if ($errors->any())
                @php
                    $errorStep = 1;
                    if ($errors->hasAny(['admin_name', 'admin_email', 'admin_phone', 'password'])) {
                        $errorStep = 3;
                    } elseif ($errors->hasAny(['country', 'state', 'district', 'city', 'plan'])) {
                        $errorStep = 2;
                    } elseif ($errors->hasAny(['hospital_name', 'slug', 'hospital_code'])) {
                        $errorStep = 1;
                    }
                @endphp
                maxReached = Math.max(maxReached, {{ (int) $errorStep }});
                goTo({{ (int) $errorStep }}, true);
            @else
                goTo(1, true);
            @endif
        }());
    </script>
@endpush

@push('scripts')
    <script>
        (function () {
            var hospitalName = document.getElementById('hospitalName');
            var slugInput = document.getElementById('slugInput');
            var slugStatus = document.getElementById('slugStatus');
            var codeInput = document.getElementById('hospitalCodeInput');
            var codeStatus = document.getElementById('codeStatus');
            var mrdPreview = document.getElementById('mrdPreview');
            var slugTimer, codeTimer;

            // ── hospital code helpers ─────────────────────────────────────
            function toCode(str) {
                return str.replace(/[^a-zA-Z]/g, '').substring(0, 4).toUpperCase();
            }

            function updateMrdPreview(code) {
                mrdPreview.textContent = (code.length >= 3 ? code : '----') + '0001';
            }

            function checkCode(code) {
                clearTimeout(codeTimer);
                updateMrdPreview(code);

                if (code.length < 3 || code.length > 4) {
                    codeStatus.innerHTML = 'Must be 3 or 4 letters';
                    codeStatus.className = 'slug-status slug-taken';
                    return;
                }

                codeStatus.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Checking...';
                codeStatus.className = 'slug-status slug-checking';

                codeTimer = setTimeout(function () {
                    fetch('{{ route("check-code") }}?code=' + encodeURIComponent(code))
                        .then(function (r) { return r.json(); })
                        .then(function (data) {
                            if (data.available) {
                                codeStatus.innerHTML = '<i class="fa-solid fa-circle-check"></i> Available';
                                codeStatus.className = 'slug-status slug-ok';
                            } else {
                                codeStatus.innerHTML =
                                    '<i class="fa-solid fa-circle-xmark"></i> ' +
                                    (data.message || 'Already taken');
                                codeStatus.className = 'slug-status slug-taken';
                            }
                        })
                        .catch(function () {
                            codeStatus.textContent = '';
                        });
                }, 400);
            }

            // Auto-generate code from hospital name
            hospitalName.addEventListener('input', function () {
                var code = toCode(this.value);
                codeInput.value = code;
                checkCode(code);
            });

            // Manual edit of code field
            codeInput.addEventListener('input', function () {
                this.value = this.value.replace(/[^a-zA-Z]/g, '').toUpperCase().substring(0, 4);
                checkCode(this.value);
            });

            // Initial preview if old() value present
            if (codeInput.value.length >= 3) { checkCode(codeInput.value); }

            // ── slug helpers ──────────────────────────────────────────────
            function toSlug(str) {
                return str.toLowerCase()
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-')
                    .substring(0, 30);
            }

            hospitalName.addEventListener('input', function () {
                var slug = toSlug(this.value);
                slugInput.value = slug;
                checkSlug(slug);
            });

            slugInput.addEventListener('input', function () {
                this.value = this.value.toLowerCase().replace(/[^a-z0-9-]/g, '');
                checkSlug(this.value);
            });

            function checkSlug(slug) {
                clearTimeout(slugTimer);
                if (slug.length < 3) {
                    slugStatus.innerHTML = 'Minimum 3 characters required';
                    slugStatus.className = 'slug-status slug-taken';
                    return;
                }
                slugStatus.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Checking...';
                slugStatus.className = 'slug-status slug-checking';

                slugTimer = setTimeout(function () {
                    fetch('{{ route("check-slug") }}?slug=' + encodeURIComponent(slug))
                        .then(function (r) { return r.json(); })
                        .then(function (data) {
                            if (data.available) {
                                slugStatus.innerHTML = '<i class="fa-solid fa-circle-check"></i> Available';
                                slugStatus.className = 'slug-status slug-ok';
                            } else {
                                var msg = '<i class="fa-solid fa-circle-xmark"></i> ' + (data.message || 'Not available');
                                if (data.suggestion) { msg += ' &mdash; Try: <strong>' + data.suggestion + '</strong>'; }
                                slugStatus.innerHTML = msg;
                                slugStatus.className = 'slug-status slug-taken';
                            }
                        })
                        .catch(function () { slugStatus.textContent = ''; });
                }, 400);
            }
        }());
    </script>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script>
        (function () {
            var CSRF = '{{ csrf_token() }}';

            // Country name → ID map for cascade (populated from server-rendered options)
            var countryIdMap = {
                @foreach($countries as $c)
                    '{{ addslashes($c->name) }}': {{ $c->id }},
                @endforeach
            };

            var countryMetaMap = {
                @foreach($countries as $c)
                    '{{ addslashes($c->name) }}': {
                        id: {{ $c->id }},
                        countryCode: @json($c->country_code),
                        countryName: @json($c->name),
                        currencyCode: @json($c->currency_code ?: 'INR'),
                        currencySymbol: @json($c->currency_symbol ?: '₹'),
                        fx: {{ (float) ($c->fx_inr_per_unit ?: 1) }}
                    },
                @endforeach
            };

            var basePlans = {
                monthly: {{ (int) $planPricing['monthly']['price'] }},
                quarterly: {{ (int) $planPricing['quarterly']['price'] }},
                yearly: {{ (int) $planPricing['yearly']['price'] }}
            };
            var planSuffix = { monthly: '/mo', quarterly: '/qtr', yearly: '/yr' };
            var gstRateIndia = {{ (float) $platformGstRateIndia }};

            // Per-country explicit price overrides are stored in INR.
            // When country changes, we convert those INR amounts using the country's FX.
            var countryPriceOverrides = @json($countryOverrides ?? []);

            function countryAppliesGst(meta) {
                if (!meta) return false;
                var code = (meta.countryCode || '').toUpperCase();
                if (code === 'IN') return true;
                return (meta.countryName || '').toLowerCase() === 'india';
            }

            function priceForCycle(key, meta, overrides) {
                if (overrides && overrides[key] != null && overrides[key] !== '') {
                    var rate = parseFloat(meta && meta.fx ? meta.fx : 1);
                    if (!rate || rate <= 0) rate = 1;
                    return convertFromInr(parseFloat(overrides[key]), rate);
                }
                var rate = parseFloat(meta && meta.fx ? meta.fx : 1);
                if (!rate || rate <= 0) rate = 1;
                return convertFromInr(basePlans[key], rate);
            }

            function selectedPlanKey() {
                var checked = document.querySelector('#regPlanCards input[name="plan"]:checked');
                return checked ? checked.value : 'monthly';
            }

            function updatePlanTaxBreakdown(meta) {
                var planKey = selectedPlanKey();
                var cid = (meta && meta.id) ? String(meta.id) : null;
                var overrides = (cid && countryPriceOverrides[cid]) ? countryPriceOverrides[cid] : null;
                var symbol = (meta && meta.currencySymbol) ? meta.currencySymbol : @json(platform_currency_symbol());
                var subtotal = meta
                    ? priceForCycle(planKey, meta, overrides)
                    : basePlans[planKey];
                var gstRate = countryAppliesGst(meta) ? gstRateIndia : 0;
                var gstAmount = Math.round(subtotal * gstRate / 100);
                var total = subtotal + gstAmount;

                var subEl = document.getElementById('regPlanSubtotal');
                var gstEl = document.getElementById('regPlanGst');
                var totalEl = document.getElementById('regPlanTotal');
                var gstRow = document.getElementById('regGstRow');
                var gstRateLbl = document.getElementById('regGstRateLabel');

                if (subEl) subEl.textContent = symbol + formatPlanAmount(subtotal);
                if (gstEl) gstEl.textContent = symbol + formatPlanAmount(gstAmount);
                if (totalEl) totalEl.textContent = symbol + formatPlanAmount(total);
                if (gstRateLbl) gstRateLbl.textContent = String(gstRate);
                if (gstRow) gstRow.style.display = gstRate > 0 ? 'flex' : 'none';
            }

            function convertFromInr(inrAmount, fx) {
                var rate = parseFloat(fx);
                if (!rate || rate <= 0) rate = 1;
                return Math.max(1, Math.round(inrAmount / rate));
            }

            function formatPlanAmount(n) {
                return Number(n).toLocaleString('en-IN');
            }

            function updatePlanPricing(meta) {
                var symbol = (meta && meta.currencySymbol) ? meta.currencySymbol : @json(platform_currency_symbol());
                var code = (meta && meta.currencyCode) ? meta.currencyCode : @json(platform_currency_code());
                var cid = (meta && meta.id) ? String(meta.id) : null;
                var overrides = (cid && countryPriceOverrides[cid]) ? countryPriceOverrides[cid] : null;

                Object.keys(basePlans).forEach(function (key) {
                    var el = document.querySelector('.pc-price[data-plan="' + key + '"]');
                    if (!el) return;
                    var local = priceForCycle(key, meta, overrides);
                    el.textContent = symbol + formatPlanAmount(local) + (planSuffix[key] || '');
                });
                var label = document.getElementById('regPlanCurrencyLabel');
                if (label) label.textContent = code + (meta && meta.countryCode ? ' · ' + meta.countryCode : '');
                updatePlanTaxBreakdown(meta);
            }

        // Track active parent IDs so child create callbacks know where to attach
        var activeCountryId = null;
        var activeStateId = null;
        var activeDistrictId = null;

        function postCreate(url, body, callback) {
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(body),
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data && data.id) {
                        callback({
                            value: data.name,
                            text: data.name,
                            locId: data.id,
                            countryCode: data.country_code || '',
                            currencyCode: data.currency_code || 'INR',
                            currencySymbol: data.currency_symbol || '₹',
                            fx: parseFloat(data.fx_inr_per_unit || 1)
                        });
                    } else {
                        callback(false);
                    }
                })
                .catch(function () { callback(false); });
        }

        function resetTs(ts) {
            ts.clear(true);
            ts.clearOptions();
            ts.disable();
        }

        function populateTs(ts, items) {
            ts.addOptions(items.map(function (item) {
                return { value: item.name, text: item.name, locId: item.id };
            }));
            // enable/disable is managed by the change handlers, not here
        }

        function cascadeFetch(url, params, callback) {
            var qs = Object.keys(params).map(function (k) {
                return encodeURIComponent(k) + '=' + encodeURIComponent(params[k]);
            }).join('&');
            fetch(url + '?' + qs)
                .then(function (r) { return r.json(); })
                .then(callback)
                .catch(function () { });
        }

        var tsCountry = new TomSelect('#regCountry', {
            placeholder: 'Search or Add Country',
            allowEmptyOption: true,
            maxOptions: 300,
            createOnBlur: false,
            create: function (input, callback) {
                postCreate('{{ route("location.create-country") }}', { name: input }, function (opt) {
                    if (opt) {
                        countryIdMap[opt.value] = opt.locId;
                        countryMetaMap[opt.value] = {
                            id: opt.locId,
                            countryCode: opt.countryCode || '',
                            countryName: opt.value || '',
                            currencyCode: opt.currencyCode || 'INR',
                            currencySymbol: opt.currencySymbol || '₹',
                            fx: opt.fx || 1
                        };
                    }
                    callback(opt || false);
                });
            },
        });

        var tsState = new TomSelect('#regState', {
            placeholder: 'Search or Add State',
            allowEmptyOption: true,
            maxOptions: 300,
            createOnBlur: false,
            create: function (input, callback) {
                if (!activeCountryId) { callback(false); return; }
                postCreate('{{ route("location.create-state") }}', { name: input, country_id: activeCountryId }, callback);
            },
        });
        tsState.disable();

        var tsDistrict = new TomSelect('#regDistrict', {
            placeholder: 'Search or Add District',
            allowEmptyOption: true,
            maxOptions: 300,
            createOnBlur: false,
            create: function (input, callback) {
                if (!activeStateId) { callback(false); return; }
                postCreate('{{ route("location.create-district") }}', { name: input, state_id: activeStateId }, callback);
            },
        });
        tsDistrict.disable();

        var tsCity = new TomSelect('#regCity', {
            placeholder: 'Search or Add City',
            allowEmptyOption: true,
            maxOptions: 300,
            createOnBlur: false,
            create: function (input, callback) {
                if (!activeDistrictId || !activeStateId) { callback(false); return; }
                postCreate('{{ route("location.create-city") }}', { name: input, district_id: activeDistrictId, state_id: activeStateId }, callback);
            },
        });
        tsCity.disable();

        tsCountry.on('change', function (name) {
            resetTs(tsState);
            resetTs(tsDistrict);
            resetTs(tsCity);
            activeCountryId = null;
            activeStateId = null;
            activeDistrictId = null;
            var id = name ? countryIdMap[name] : null;
            updatePlanPricing(name ? (countryMetaMap[name] || null) : null);
            if (!id) return;
            activeCountryId = id;
            tsState.enable(); // enable immediately — user can type/create even if no states exist yet
            cascadeFetch('{{ route("location.states") }}', { country_id: id }, function (data) {
                populateTs(tsState, data);
            });
        });

        // Initial pricing if country already selected (old input / default)
        if (tsCountry.getValue()) {
            updatePlanPricing(countryMetaMap[tsCountry.getValue()] || null);
        } else {
            updatePlanTaxBreakdown(null);
        }

        document.querySelectorAll('#regPlanCards input[name="plan"]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                var name = tsCountry.getValue();
                updatePlanTaxBreakdown(name ? (countryMetaMap[name] || null) : null);
            });
        });

        tsState.on('change', function (name) {
            resetTs(tsDistrict);
            resetTs(tsCity);
            activeStateId = null;
            activeDistrictId = null;
            var opt = name ? tsState.options[name] : null;
            if (!opt) return;
            activeStateId = opt.locId;
            tsDistrict.enable(); // enable immediately
            cascadeFetch('{{ route("location.districts") }}', { state_id: opt.locId }, function (data) {
                populateTs(tsDistrict, data);
            });
        });

        tsDistrict.on('change', function (name) {
            resetTs(tsCity);
            activeDistrictId = null;
            var opt = name ? tsDistrict.options[name] : null;
            if (!opt) return;
            activeDistrictId = opt.locId;
            tsCity.enable(); // enable immediately
            cascadeFetch('{{ route("location.cities") }}', { district_id: opt.locId }, function (data) {
                populateTs(tsCity, data);
            });
        });
        }());
    </script>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var pass = document.getElementById('registerPassword');
            var passConfirm = document.getElementById('registerPasswordConfirm');
            var togglePass = document.getElementById('toggleRegisterPassword');
            var togglePassConfirm = document.getElementById('toggleRegisterPasswordConfirm');
            var eye = document.getElementById('regPassEye');
            var eyeConfirm = document.getElementById('regPassConfirmEye');
            var eyeSvg = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M1.5 12s3.5-7 10.5-7 10.5 7 10.5 7-3.5 7-10.5 7S1.5 12 1.5 12Z" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path><circle cx="12" cy="12" r="3.2" stroke-width="1.8"></circle></svg>';
            var eyeSlashSvg = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3l18 18" stroke-width="1.8" stroke-linecap="round"></path><path d="M10.2 5.1A11.3 11.3 0 0 1 12 5c7 0 10.5 7 10.5 7a19 19 0 0 1-4.1 4.7" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path><path d="M9.9 9.9A3.2 3.2 0 0 0 12 15.2c.5 0 1-.1 1.4-.3" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path><path d="M7.1 7.4C4 9.6 1.5 12 1.5 12s3.5 7 10.5 7c1.7 0 3.2-.3 4.6-.8" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path></svg>';

            if (togglePass && pass && eye) {
                togglePass.addEventListener('click', function () {
                    var isPwd = pass.getAttribute('type') === 'password';
                    pass.setAttribute('type', isPwd ? 'text' : 'password');
                    eye.innerHTML = isPwd ? eyeSlashSvg : eyeSvg;
                });
            }

            if (togglePassConfirm && passConfirm && eyeConfirm) {
                togglePassConfirm.addEventListener('click', function () {
                    var isPwd = passConfirm.getAttribute('type') === 'password';
                    passConfirm.setAttribute('type', isPwd ? 'text' : 'password');
                    eyeConfirm.innerHTML = isPwd ? eyeSlashSvg : eyeSvg;
                });
            }
        });
    </script>
@endpush
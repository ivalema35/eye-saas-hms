@extends('hospital.layouts.app')
@section('title', 'Hospital Settings')
@section('page-header', 'Hospital Settings')

@section('content')
    <div class="hospital-settings-page">
        <style>
            .hospital-settings-page {
                --settings-primary: #1B4F72;
                --settings-secondary: #2980B9;
                --settings-success: #27AE60;
                --settings-soft: #ebf5fbeb;
                --settings-border: rgba(27, 79, 114, .12);
                --settings-muted: rgba(27, 79, 114, .62);
                color: var(--settings-primary);
                animation: settings-page-in 420ms ease both;
            }

            .settings-hero {
                display: flex;
                align-items: center;
                gap: .9rem;
                padding: 1.2rem 1.25rem;
                margin-bottom: 1rem;
                border: 1px solid var(--settings-border);
                border-radius: 22px;
                background: linear-gradient(135deg, rgba(235, 245, 251, .96), rgba(255, 255, 255, .94));
                box-shadow: 0 18px 48px rgba(27, 79, 114, .10);
                position: relative;
                overflow: hidden;
            }

            .settings-hero::before {
                content: '';
                position: absolute;
                inset: 0 auto 0 0;
                width: 5px;
                background: var(--settings-success);
            }

            .settings-hero-icon {
                width: 52px;
                height: 52px;
                border-radius: 17px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
                background: var(--settings-primary);
                color: #fff;
                box-shadow: 0 14px 30px rgba(27, 79, 114, .22);
            }

            .settings-kicker {
                display: inline-flex;
                align-items: center;
                color: var(--settings-secondary);
                font-size: .72rem;
                font-weight: 900;
                letter-spacing: .08em;
                text-transform: uppercase;
                margin-bottom: .15rem;
            }

            .settings-hero h4 {
                color: var(--settings-primary) !important;
                font-weight: 900;
            }

            .settings-hero p {
                margin: .15rem 0 0;
                color: var(--settings-muted);
                font-size: .9rem;
            }

            .settings-card {
                border: 1px solid var(--settings-border) !important;
                border-radius: 22px;
                background: rgba(255, 255, 255, .88);
                box-shadow: 0 18px 48px rgba(27, 79, 114, .10) !important;
                overflow: hidden;
                animation: settings-card-rise 520ms cubic-bezier(.2, .9, .2, 1) both;
            }

            .settings-card-header {
                background: linear-gradient(135deg, rgba(235, 245, 251, .92), rgba(255, 255, 255, .96)) !important;
                border-bottom: 1px solid var(--settings-border) !important;
            }

            .settings-tabs {
                gap: .5rem;
                flex-wrap: wrap;
            }

            .settings-tabs .nav-link {
                border: 1px solid var(--settings-border) !important;
                border-radius: 999px;
                color: var(--settings-primary);
                background: rgba(255, 255, 255, .78);
                font-weight: 700 !important;
                padding: .55rem .95rem;
                display: inline-flex;
                align-items: center;
                transition: transform 170ms ease, box-shadow 170ms ease, background 170ms ease, color 170ms ease;
            }

            .settings-tabs .nav-link:hover,
            .settings-tabs .nav-link.active {
                background: var(--settings-primary) !important;
                border-color: var(--settings-primary) !important;
                color: #fff !important;
                transform: translateY(-1px);
                box-shadow: 0 12px 26px rgba(27, 79, 114, .16);
            }

            .settings-card-body {
                background: var(--settings-soft) !important;
                padding: 1rem !important;
            }

            .settings-tab-content {
                border: 1px solid var(--settings-border) !important;
                border-radius: 18px !important;
                box-shadow: 0 12px 32px rgba(27, 79, 114, .08) !important;
                background: rgba(255, 255, 255, .94) !important;
                padding: 1.5rem !important;
            }

            .settings-logo-box {
                width: 110px !important;
                height: 110px !important;
                border: 3px solid var(--settings-border) !important;
                border-radius: 18px !important;
                background: linear-gradient(135deg, #fff, rgba(235, 245, 251, .72)) !important;
                box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .7), 0 8px 20px rgba(27, 79, 114, .08);
                overflow: hidden;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
                transition: border-color .2s, box-shadow .2s;
            }

            /* Dark preview box — sidebar pe kaisa dikhega */
            .settings-logo-box-dark {
                width: 110px !important;
                height: 110px !important;
                border: 3px solid rgba(27, 79, 114, .3) !important;
                border-radius: 18px !important;
                background: #1B4F72 !important;
                box-shadow: 0 8px 20px rgba(27, 79, 114, .18);
                overflow: hidden;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
                position: relative;
                transition: border-color .2s, box-shadow .2s;
            }

            .logo-style-option.active .settings-logo-box,
            .logo-style-option.active .settings-logo-box-dark {
                border-color: #16a34a !important;
                box-shadow: 0 0 0 5px rgba(22, 163, 74, .18), 0 8px 20px rgba(22, 163, 74, .12) !important;
            }

            .logo-style-option:hover .settings-logo-box,
            .logo-style-option:hover .settings-logo-box-dark {
                border-color: rgba(22, 163, 74, .5) !important;
            }

            .logo-style-option.active .logo-preview-label {
                color: #16a34a !important;
            }

            .settings-logo-box-dark::after {
                content: 'Sidebar Preview';
                position: absolute;
                bottom: 0;
                left: 0;
                right: 0;
                font-size: .60rem;
                font-weight: 700;
                text-align: center;
                color: rgba(255, 255, 255, .55);
                background: rgba(0, 0, 0, .18);
                padding: 3px 0;
                letter-spacing: .05em;
                text-transform: uppercase;
            }

            .logo-preview-label {
                font-size: .70rem;
                font-weight: 700;
                text-align: center;
                color: var(--settings-muted);
                text-transform: uppercase;
                letter-spacing: .07em;
                margin-top: .4rem;
            }

            .settings-upload-panel {
                min-width: min(100%, 340px);
            }

            .logo-style-option {
                cursor: pointer;
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: .5rem;
            }

            .logo-style-preview {
                width: 100px;
                height: 100px;
                border-radius: 18px;
                border: 3px solid rgba(27, 79, 114, .12);
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
                transition: border-color .2s, box-shadow .2s;
            }

            .logo-style-option:hover .logo-style-preview {
                border-color: rgba(27, 79, 114, .35);
            }

            .logo-style-option.active .logo-style-preview {
                border-color: #1B4F72;
                box-shadow: 0 0 0 5px rgba(27, 79, 114, .18);
            }

            .logo-style-label {
                font-size: .72rem;
                font-weight: 700;
                color: var(--settings-muted);
                text-transform: uppercase;
                letter-spacing: .06em;
            }

            .logo-style-option.active .logo-style-label {
                color: #1B4F72;
            }

            .logo-style-hint {
                font-size: .68rem;
                color: var(--settings-muted);
                text-align: center;
                max-width: 100px;
                line-height: 1.35;
            }

            .hospital-settings-page .form-label {
                color: var(--settings-primary);
                font-weight: 700;
                font-size: .875rem;
                margin-bottom: .35rem;
            }

            .hospital-settings-page .clinical-input {
                border: 1.5px solid rgba(27, 79, 114, .16);
                border-radius: 12px;
                background-color: #fbfdff;
                color: var(--settings-primary);
                font-weight: 600;
                padding: .62rem .85rem;
                transition: border-color 170ms ease, box-shadow 170ms ease, background 170ms ease;
            }

            .hospital-settings-page .clinical-input:focus {
                background: #fff;
                border-color: var(--settings-secondary);
                box-shadow: 0 0 0 4px rgba(41, 128, 185, .12);
                outline: none;
            }

            .hospital-settings-page .clinical-input.is-invalid {
                border-color: #C0392B;
            }

            .hospital-settings-page .password-field-wrap {
                position: relative;
            }

            .hospital-settings-page .password-field-input {
                padding-right: 3rem !important;
            }

            .hospital-settings-page .password-field-toggle {
                position: absolute;
                right: .7rem;
                top: 50%;
                transform: translateY(-50%);
                border: 1px solid rgba(27, 79, 114, .14);
                background: rgba(255, 255, 255, .98);
                padding: 0;
                margin: 0;
                color: var(--settings-primary);
                width: 2rem;
                height: 2rem;
                border-radius: 999px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                line-height: 1;
                z-index: 3;
                overflow: hidden;
                box-shadow: 0 1px 2px rgba(27, 79, 114, .06);
                transition: color .2s ease, border-color .2s ease, box-shadow .2s ease, transform .15s ease;
            }

            .hospital-settings-page .password-field-toggle:hover {
                border-color: rgba(27, 79, 114, .22);
                box-shadow: 0 4px 10px rgba(27, 79, 114, .10);
                transform: translateY(-50%) scale(1.02);
            }

            .hospital-settings-page .password-field-toggle:focus-visible {
                outline: 2px solid rgba(27, 79, 114, .22);
                outline-offset: 2px;
            }

            .hospital-settings-page .password-field-toggle svg {
                width: 1rem;
                height: 1rem;
                display: block;
                stroke: currentColor;
                fill: none;
                color: inherit;
                pointer-events: none;
            }

            /* Password section divider inside Profile tab */
            .pw-section-divider {
                display: flex;
                align-items: center;
                gap: .75rem;
                margin: 1.75rem 0 1.25rem;
            }

            .pw-section-divider::before,
            .pw-section-divider::after {
                content: '';
                flex: 1;
                border-top: 1px solid var(--settings-border);
            }

            .pw-section-label {
                display: flex;
                align-items: center;
                gap: .45rem;
                font-size: .75rem;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: .06em;
                color: var(--settings-muted);
                white-space: nowrap;
            }

            .settings-save-wrap {
                margin-top: 1.25rem;
                padding: 1rem 1.25rem;
                border: 1px solid var(--settings-border);
                border-radius: 16px;
                background: linear-gradient(135deg, rgba(235, 245, 251, .72), rgba(255, 255, 255, .96));
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
            }

            .settings-save-hint {
                font-size: .78rem;
                color: var(--settings-muted);
            }

            .settings-save-btn {
                border-radius: 13px;
                font-weight: 900;
                padding: .68rem 1.35rem !important;
                background: var(--settings-primary) !important;
                border-color: var(--settings-primary) !important;
                color: #fff !important;
                box-shadow: 0 12px 26px rgba(27, 79, 114, .18);
                transition: transform 170ms ease, box-shadow 170ms ease;
                white-space: nowrap;
            }

            .settings-save-btn:hover {
                transform: translateY(-1px);
                box-shadow: 0 16px 34px rgba(27, 79, 114, .24);
                color: #fff !important;
            }

            @keyframes settings-page-in {
                from {
                    opacity: 0;
                    transform: translateY(8px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            @keyframes settings-card-rise {
                from {
                    opacity: 0;
                    transform: translateY(12px) scale(.99);
                }

                to {
                    opacity: 1;
                    transform: translateY(0) scale(1);
                }
            }

            @media (prefers-reduced-motion: reduce) {

                .hospital-settings-page,
                .settings-card {
                    animation: none;
                }

                .hospital-settings-page * {
                    transition: none !important;
                }
            }

            @media (max-width: 576px) {
                .settings-hero {
                    align-items: flex-start;
                }

                .settings-hero-icon {
                    width: 44px;
                    height: 44px;
                    border-radius: 14px;
                }

                .settings-tabs .nav-link {
                    width: 100%;
                    justify-content: center;
                }

                .settings-save-wrap {
                    flex-direction: column;
                    align-items: stretch;
                }
            }

            /* Wait Status preview pill (reuse same classes) */
            .wait-dot {
                display: inline-block;
                width: 10px;
                height: 10px;
                border-radius: 50%;
                margin-right: 5px;
                vertical-align: middle;
            }

            .wait-pill {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                border-radius: 999px;
                padding: 3px 10px 3px 3px;
                font-weight: 700;
                white-space: nowrap;
            }

            .wait-pill.wait-green {
                background: rgba(22, 163, 74, .10);
                box-shadow: 0 0 0 1px rgba(22, 163, 74, .25);
            }

            .wait-pill.wait-orange {
                background: rgba(234, 88, 12, .10);
                box-shadow: 0 0 0 1px rgba(234, 88, 12, .25);
            }

            .wait-pill.wait-red {
                background: rgba(220, 38, 38, .10);
                box-shadow: 0 0 0 1px rgba(220, 38, 38, .25);
            }

            .wait-pill.wait-fire {
                background: rgba(220, 38, 38, .10);
                box-shadow: 0 0 0 1px rgba(220, 38, 38, .35);
                animation: wfire 1s ease-in-out infinite alternate;
            }

            @keyframes wfire {
                from {
                    box-shadow: 0 0 0 1px rgba(220, 38, 38, .35), 0 0 6px rgba(234, 88, 12, .4);
                }

                to {
                    box-shadow: 0 0 0 2px rgba(220, 38, 38, .55), 0 0 12px rgba(234, 88, 12, .6);
                }
            }

            .wp-r {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 24px;
                height: 24px;
                border-radius: 50%;
                font-size: .68rem;
                font-weight: 900;
                color: #fff;
            }

            .wait-green .wp-r {
                background: #16a34a;
            }

            .wait-orange .wp-r {
                background: #ea580c;
            }

            .wait-red .wp-r {
                background: #dc2626;
            }

            .wait-fire .wp-r {
                background: linear-gradient(135deg, #dc2626, #ea580c);
            }

            .wp-time {
                font-size: .75rem;
                font-weight: 700;
            }

            .wait-green .wp-time {
                color: #15803d;
            }

            .wait-orange .wp-time {
                color: #c2410c;
            }

            .wait-red .wp-time {
                color: #b91c1c;
            }

            .wait-fire .wp-time {
                color: #dc2626;
            }

            .settings-save-btn {
                width: 100%;
                text-align: center;
            }
            }
        </style>

        {{-- Hero --}}
        <div class="settings-hero">
            <span class="settings-hero-icon">
                <i class="bi bi-gear-fill fs-4"></i>
            </span>
            <div>
                <span class="settings-kicker"><i class="bi bi-sliders2 me-1"></i> Configuration</span>
                <h4 class="fw-bold mb-0">Hospital Settings</h4>
                <p>Manage hospital profile, billing defaults, print notes, and display preferences.</p>
            </div>
        </div>

        <div class="card border-0 settings-card">
            {{-- Tab Nav --}}
            <div class="card-header bg-white p-0 border-bottom settings-card-header">
                <ul class="nav nav-tabs px-3 py-3 border-0 settings-tabs" id="settingsTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile"
                            type="button" role="tab">
                            <i class="bi bi-building me-2"></i> Profile
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="billing-tab" data-bs-toggle="tab" data-bs-target="#billing"
                            type="button" role="tab">
                            <i class="bi bi-receipt me-2"></i> Billing
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="print-tab" data-bs-toggle="tab" data-bs-target="#print" type="button"
                            role="tab">
                            <i class="bi bi-printer me-2"></i> Print Settings
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="pagination-tab" data-bs-toggle="tab" data-bs-target="#pagination"
                            type="button" role="tab">
                            <i class="bi bi-table me-2"></i> Pagination
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="waitstatus-tab" data-bs-toggle="tab" data-bs-target="#waitstatus"
                            type="button" role="tab">
                            <i class="bi bi-hourglass-split me-2"></i> Wait Status
                        </button>
                    </li>
                </ul>
            </div>

            {{-- Card Body — one form wraps the whole tab-content --}}
            <div class="card-body settings-card-body">
                <form action="{{ route('hospital.settings.update', ['slug' => $slug]) }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="tab-content settings-tab-content" id="settingsTabsContent">

                        {{-- ══════════════ PROFILE TAB ══════════════ --}}
                        <div class="tab-pane fade show active" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                            <div class="row g-4">

                                {{-- Logo --}}
                                @php $currentLogoStyle = old('logo_sidebar_style', $settings['logo_sidebar_style'] ?? 'white'); @endphp
                                <input type="hidden" name="logo_sidebar_style" id="logoSidebarStyleInput"
                                    value="{{ $currentLogoStyle }}">

                                <div class="col-12">
                                    <div class="d-flex align-items-start gap-4 flex-wrap">

                                        {{-- Option 1: Original + Blur BG --}}
                                        <label
                                            class="logo-style-option {{ $currentLogoStyle === 'original_blur' ? 'active' : '' }}"
                                            onclick="selectLogoStyle('original_blur')">
                                            <div class="settings-logo-box" style="cursor:pointer;">
                                                @if(!empty($settings['hospital_logo']))
                                                    <img id="logoPreviewOriginal"
                                                        src="{{ asset('storage/' . $settings['hospital_logo']) }}"
                                                        alt="Hospital Logo"
                                                        style="max-width:88%;max-height:88%;object-fit:contain;">
                                                    <i id="logoPlaceholder1" class="bi bi-image text-muted"
                                                        style="font-size:2.2rem;opacity:.35;display:none"></i>
                                                @else
                                                    <img id="logoPreviewOriginal" src="{{ platform_logo_url() }}"
                                                        alt="Hospital Logo"
                                                        style="max-width:88%;max-height:88%;object-fit:contain;">
                                                    <i id="logoPlaceholder1" class="bi bi-image text-muted"
                                                        style="font-size:2.2rem;opacity:.35;display:none"></i>
                                                @endif
                                            </div>
                                            <div class="logo-preview-label">Original</div>
                                            <span class="logo-style-hint">Shows original logo colors</span>
                                        </label>

                                        {{-- Option 2: Pure White --}}
                                        @php
                                            $sidebarPreviewSrc = !empty($settings['hospital_logo_nobg'])
                                                ? asset('storage/' . $settings['hospital_logo_nobg'])
                                                : (!empty($settings['hospital_logo'])
                                                    ? asset('storage/' . $settings['hospital_logo'])
                                                    : platform_logo_url());
                                            $sidebarPreviewFilter = 'brightness(0) invert(1)';
                                        @endphp
                                        <label class="logo-style-option {{ $currentLogoStyle === 'white' ? 'active' : '' }}"
                                            onclick="selectLogoStyle('white')">
                                            <div class="settings-logo-box-dark" style="cursor:pointer;">
                                                <img id="logoPreviewDark" src="{{ $sidebarPreviewSrc }}"
                                                    alt="Sidebar Preview"
                                                    style="max-width:72%;max-height:72%;object-fit:contain;filter:{{ $sidebarPreviewFilter }};margin-bottom:14px;">
                                                <i id="logoPlaceholder2" class="bi bi-image"
                                                    style="font-size:2rem;color:rgba(255,255,255,.3);margin-bottom:14px;display:none"></i>
                                            </div>
                                            <div class="logo-preview-label">Sidebar View</div>
                                            <span class="logo-style-hint">Best with transparent PNG — removes
                                                background</span>
                                        </label>

                                        {{-- Upload Panel --}}
                                        <div class="settings-upload-panel">
                                            <label class="form-label">
                                                Hospital Logo
                                                <small class="fw-normal ms-1" style="color:var(--settings-muted)">(JPG, PNG,
                                                    SVG or WebP — max 2 MB)</small>
                                            </label>
                                            <input type="file" name="hospital_logo" accept="image/*" id="logoFileInput"
                                                class="form-control clinical-input @error('hospital_logo') is-invalid @enderror">
                                            <input type="hidden" name="logo_processed_base64" id="logoProcessedBase64">
                                            @error('hospital_logo')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                            <div id="bgRemoveNotice" class="mt-1 d-none"
                                                style="font-size:.75rem;color:#16a34a;font-weight:600;">
                                                <i class="bi bi-magic me-1"></i> White background will be auto-removed for
                                                sidebar style.
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                {{-- Hospital Info --}}
                                <div class="col-md-6">
                                    <label class="form-label">Hospital Name <span class="text-danger">*</span></label>
                                    <input type="text" name="hospital_name"
                                        class="form-control clinical-input @error('hospital_name') is-invalid @enderror"
                                        value="{{ old('hospital_name', $settings['hospital_name'] ?? '') }}"
                                        placeholder="Enter hospital name" required>
                                    @error('hospital_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Contact Number <span class="text-danger">*</span></label>
                                    <input type="text" name="hospital_phone"
                                        class="form-control clinical-input @error('hospital_phone') is-invalid @enderror"
                                        value="{{ old('hospital_phone', $settings['hospital_phone'] ?? '') }}"
                                        placeholder="+919876543210" required data-intl-phone>
                                    @error('hospital_phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Contact Email <span class="text-danger">*</span></label>
                                    <input type="email" name="hospital_email"
                                        class="form-control clinical-input @error('hospital_email') is-invalid @enderror"
                                        value="{{ old('hospital_email', $settings['hospital_email'] ?? '') }}"
                                        placeholder="hospital@example.com" required>
                                    @error('hospital_email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Full Address <span class="text-danger">*</span></label>
                                    <input type="text" name="hospital_address"
                                        class="form-control clinical-input @error('hospital_address') is-invalid @enderror"
                                        value="{{ old('hospital_address', $settings['hospital_address'] ?? '') }}"
                                        placeholder="Building, Street, City, State" required>
                                    @error('hospital_address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- ── Location & Timezone ── --}}
                                @php
                                    $savedCountry = old('hospital_country', $settings['hospital_country'] ?? '');
                                    $savedState = old('hospital_state', $settings['hospital_state'] ?? '');
                                    $savedDistrict = old('hospital_district', $settings['hospital_district'] ?? '');
                                    $savedCity = old('hospital_city', $settings['hospital_city'] ?? '');
                                    $savedTimezone = old('hospital_timezone', $settings['hospital_timezone'] ?? 'UTC');
                                @endphp

                                {{-- Country --}}
                                <div class="col-md-6">
                                    <label class="form-label">Country</label>
                                    <select name="hospital_country" id="settingCountry"
                                        class="form-select clinical-input @error('hospital_country') is-invalid @enderror">
                                        <option value="">— Select Country —</option>
                                        @foreach($countries as $c)
                                            <option value="{{ $c->name }}" data-id="{{ $c->id }}"
                                                data-timezone="{{ $c->default_timezone ?? 'UTC' }}"
                                                data-currency-code="{{ $c->currency_code ?? 'INR' }}"
                                                data-currency-symbol="{{ $c->currency_symbol ?? '₹' }}"
                                                data-country-code="{{ $c->country_code ?? '' }}" {{ $savedCountry === $c->name ? 'selected' : '' }}>
                                                {{ $c->name }}@if(!empty($c->country_code)) ({{ $c->country_code }})@endif
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('hospital_country')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Timezone (auto from country, editable) --}}
                                <div class="col-md-6">
                                    <label class="form-label">Timezone</label>
                                    <select name="hospital_timezone" id="settingTimezone"
                                        class="form-select clinical-input @error('hospital_timezone') is-invalid @enderror">
                                        @foreach(\App\Services\Platform\TimezoneService::groupedTimezones() as $region => $zones)
                                            <optgroup label="{{ $region }}">
                                                @foreach($zones as $tz)
                                                    <option value="{{ $tz }}" {{ $savedTimezone === $tz ? 'selected' : '' }}>
                                                        {{ $tz }}
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                    @error('hospital_timezone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Currency (auto from country master) --}}
                                <div class="col-md-6">
                                    <label class="form-label">Currency</label>
                                    @php
                                        $currencyPreview = $selectedCountry
                                            ? trim(($selectedCountry->currency_symbol ?? '') . ' ' . ($selectedCountry->currency_code ?? ''))
                                            : (currency_symbol() . ' ' . currency_code());
                                    @endphp
                                    <input type="text" id="settingCurrencyDisplay" class="form-control clinical-input"
                                        readonly value="{{ $currencyPreview }}">
                                </div>

                                {{-- State --}}
                                <div class="col-md-4">
                                    <label class="form-label">State</label>
                                    <select name="hospital_state" id="settingState"
                                        class="form-select clinical-input @error('hospital_state') is-invalid @enderror">
                                        <option value="">— Select State —</option>
                                        @foreach($states as $s)
                                            <option value="{{ $s->name }}" data-id="{{ $s->id }}" {{ $savedState === $s->name ? 'selected' : '' }}>
                                                {{ $s->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('hospital_state')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- District --}}
                                <div class="col-md-4">
                                    <label class="form-label">District</label>
                                    <select name="hospital_district" id="settingDistrict"
                                        class="form-select clinical-input @error('hospital_district') is-invalid @enderror">
                                        <option value="">— Select District —</option>
                                        @foreach($districts as $d)
                                            <option value="{{ $d->name }}" data-id="{{ $d->id }}" {{ $savedDistrict === $d->name ? 'selected' : '' }}>
                                                {{ $d->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('hospital_district')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- City --}}
                                <div class="col-md-4">
                                    <label class="form-label">City</label>
                                    <select name="hospital_city" id="settingCity"
                                        class="form-select clinical-input @error('hospital_city') is-invalid @enderror">
                                        <option value="">— Select City —</option>
                                        @foreach($cities as $city)
                                            <option value="{{ $city->name }}" {{ $savedCity === $city->name ? 'selected' : '' }}>
                                                {{ $city->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('hospital_city')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- ── Change Password divider ── --}}
                                <div class="col-12">
                                    <div class="pw-section-divider">
                                        <span class="pw-section-label">
                                            <i class="bi bi-lock-fill"></i> Change Password
                                        </span>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Current Password</label>
                                    <div class="password-field-wrap">
                                        <input type="password" name="current_password" id="currentPasswordField"
                                            class="form-control clinical-input password-field-input @error('current_password') is-invalid @enderror"
                                            autocomplete="current-password" placeholder="Enter current password">
                                        <button type="button" id="toggleCurrentPasswordField" class="password-field-toggle"
                                            aria-label="Toggle current password visibility">
                                            <svg id="currentPasswordEyeIcon" viewBox="0 0 24 24" aria-hidden="true">
                                                <path d="M1.5 12s3.5-7 10.5-7 10.5 7 10.5 7-3.5 7-10.5 7S1.5 12 1.5 12Z"
                                                    stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                </path>
                                                <circle cx="12" cy="12" r="3.2" stroke-width="1.8"></circle>
                                            </svg>
                                        </button>
                                    </div>
                                    @error('current_password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">New Password</label>
                                    <div class="password-field-wrap">
                                        <input type="password" name="new_password" id="newPasswordField"
                                            class="form-control clinical-input password-field-input @error('new_password') is-invalid @enderror"
                                            autocomplete="new-password" placeholder="Min 8 characters">
                                        <button type="button" id="toggleNewPasswordField" class="password-field-toggle"
                                            aria-label="Toggle new password visibility">
                                            <svg id="newPasswordEyeIcon" viewBox="0 0 24 24" aria-hidden="true">
                                                <path d="M1.5 12s3.5-7 10.5-7 10.5 7 10.5 7-3.5 7-10.5 7S1.5 12 1.5 12Z"
                                                    stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                </path>
                                                <circle cx="12" cy="12" r="3.2" stroke-width="1.8"></circle>
                                            </svg>
                                        </button>
                                    </div>
                                    @error('new_password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Confirm New Password</label>
                                    <div class="password-field-wrap">
                                        <input type="password" name="new_password_confirmation"
                                            id="newPasswordConfirmationField"
                                            class="form-control clinical-input password-field-input"
                                            autocomplete="new-password" placeholder="Repeat new password">
                                        <button type="button" id="toggleNewPasswordConfirmationField"
                                            class="password-field-toggle"
                                            aria-label="Toggle confirm new password visibility">
                                            <svg id="newPasswordConfirmationEyeIcon" viewBox="0 0 24 24" aria-hidden="true">
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
                        {{-- END PROFILE TAB --}}

                        {{-- ══════════════ BILLING TAB ══════════════ --}}
                        <div class="tab-pane fade" id="billing" role="tabpanel" aria-labelledby="billing-tab">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label class="form-label">Invoice Prefix <span class="text-danger">*</span></label>
                                    <input type="text" name="invoice_prefix"
                                        class="form-control clinical-input @error('invoice_prefix') is-invalid @enderror"
                                        value="{{ old('invoice_prefix', $settings['invoice_prefix'] ?? 'INV-') }}"
                                        placeholder="INV-" required>
                                    @error('invoice_prefix')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Default Tax Percentage (%) <span
                                            class="text-danger">*</span></label>
                                    <input type="number" step="0.01" name="tax_percentage"
                                        class="form-control clinical-input @error('tax_percentage') is-invalid @enderror"
                                        value="{{ old('tax_percentage', $settings['tax_percentage'] ?? '0') }}"
                                        placeholder="0.00" min="0" max="100" required>
                                    @error('tax_percentage')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        {{-- END BILLING TAB --}}

                        {{-- ══════════════ PRINT SETTINGS TAB ══════════════ --}}
                        <div class="tab-pane fade" id="print" role="tabpanel" aria-labelledby="print-tab">
                            <div class="row g-4">

                                {{-- Letter Pad --}}
                                @php $currentLetterPad = old('letter_pad', $settings['letter_pad'] ?? 'unavailable'); @endphp
                                <div class="col-12">
                                    <label class="form-label d-block mb-2">
                                        <i class="bi bi-file-earmark-text me-1"></i> Letter Pad
                                    </label>
                                    <div class="d-flex flex-wrap gap-3">
                                        <label
                                            class="letter-pad-option {{ $currentLetterPad === 'unavailable' ? 'active' : '' }}"
                                            onclick="selectLetterPad('unavailable')" style="cursor:pointer;">
                                            <div class="letter-pad-box"
                                                style="border:2px solid {{ $currentLetterPad === 'unavailable' ? '#1B4F72' : 'rgba(27,79,114,.18)' }};border-radius:14px;padding:14px 20px;background:{{ $currentLetterPad === 'unavailable' ? 'rgba(27,79,114,.06)' : '#fff' }};transition:all .2s;">
                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                    <div
                                                        style="width:16px;height:16px;border-radius:50%;border:2px solid {{ $currentLetterPad === 'unavailable' ? '#1B4F72' : 'rgba(27,79,114,.3)' }};display:flex;align-items:center;justify-content:center;">
                                                        @if($currentLetterPad === 'unavailable')
                                                            <div
                                                                style="width:8px;height:8px;border-radius:50%;background:#1B4F72;">
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <span
                                                        style="font-weight:800;font-size:.85rem;color:var(--settings-primary)">Unavailable</span>
                                                </div>
                                                <p
                                                    style="font-size:.74rem;color:var(--settings-muted);margin:0;max-width:220px;line-height:1.4">
                                                    Prescription header will show hospital name, address, phone &amp; logo.
                                                </p>
                                            </div>
                                        </label>

                                        <label
                                            class="letter-pad-option {{ $currentLetterPad === 'available' ? 'active' : '' }}"
                                            onclick="selectLetterPad('available')" style="cursor:pointer;">
                                            <div class="letter-pad-box"
                                                style="border:2px solid {{ $currentLetterPad === 'available' ? '#1B4F72' : 'rgba(27,79,114,.18)' }};border-radius:14px;padding:14px 20px;background:{{ $currentLetterPad === 'available' ? 'rgba(27,79,114,.06)' : '#fff' }};transition:all .2s;">
                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                    <div
                                                        style="width:16px;height:16px;border-radius:50%;border:2px solid {{ $currentLetterPad === 'available' ? '#1B4F72' : 'rgba(27,79,114,.3)' }};display:flex;align-items:center;justify-content:center;">
                                                        @if($currentLetterPad === 'available')
                                                            <div
                                                                style="width:8px;height:8px;border-radius:50%;background:#1B4F72;">
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <span
                                                        style="font-weight:800;font-size:.85rem;color:var(--settings-primary)">Available</span>
                                                </div>
                                                <p
                                                    style="font-size:.74rem;color:var(--settings-muted);margin:0;max-width:220px;line-height:1.4">
                                                    Prescription header will appear in black (physical letterhead is used).
                                                </p>
                                            </div>
                                        </label>
                                    </div>
                                    <input type="hidden" name="letter_pad" id="letterPadInput"
                                        value="{{ $currentLetterPad }}">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Print Header Note</label>
                                    <input type="text" name="print_header_note"
                                        class="form-control clinical-input @error('print_header_note') is-invalid @enderror"
                                        value="{{ old('print_header_note', $settings['print_header_note'] ?? '') }}"
                                        placeholder="e.g. Eye Care with Compassion">
                                    @error('print_header_note')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Print Footer Note</label>
                                    <input type="text" name="print_footer_note"
                                        class="form-control clinical-input @error('print_footer_note') is-invalid @enderror"
                                        value="{{ old('print_footer_note', $settings['print_footer_note'] ?? '') }}"
                                        placeholder="e.g. Thank you for your visit">
                                    @error('print_footer_note')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        {{-- END PRINT TAB --}}

                        {{-- ══════════════ PAGINATION TAB ══════════════ --}}
                        <div class="tab-pane fade" id="pagination" role="tabpanel" aria-labelledby="pagination-tab">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label class="form-label">Records Per Page <span class="text-danger">*</span></label>
                                    @php $currentLimit = (string) old('pagination_limit', $settings['pagination_limit'] ?? '25'); @endphp
                                    <select name="pagination_limit"
                                        class="form-select clinical-input @error('pagination_limit') is-invalid @enderror"
                                        required>
                                        <option value="10" {{ $currentLimit === '10' ? 'selected' : '' }}>10 Records</option>
                                        <option value="25" {{ $currentLimit === '25' ? 'selected' : '' }}>25 Records</option>
                                        <option value="50" {{ $currentLimit === '50' ? 'selected' : '' }}>50 Records</option>
                                        <option value="100" {{ $currentLimit === '100' ? 'selected' : '' }}>100 Records
                                        </option>
                                    </select>
                                    @error('pagination_limit')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        {{-- END PAGINATION TAB --}}

                        {{-- ══════════════ WAIT STATUS TAB ══════════════ --}}
                        <div class="tab-pane fade" id="waitstatus" role="tabpanel" aria-labelledby="waitstatus-tab">

                            <p class="text-muted small mb-3">
                                <i class="bi bi-info-circle me-1"></i>
                                Set the time thresholds (in minutes) for patient wait status colours shown in dashboards and
                                queues.
                            </p>

                            {{-- Default Dilation Time --}}
                            <div class="row g-4 mb-4 pb-4" style="border-bottom:1px solid rgba(27,79,114,.1);">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">
                                        <i class="bi bi-hourglass-split me-1" style="color:#ea580c;"></i>
                                        Default Dilation Time (minutes)
                                    </label>
                                    <input type="number" name="default_dilation_time" min="1" max="180"
                                        class="form-control clinical-input @error('default_dilation_time') is-invalid @enderror"
                                        value="{{ old('default_dilation_time', $settings['default_dilation_time'] ?? 40) }}"
                                        placeholder="40">
                                    @error('default_dilation_time')
                                    <div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            <div class="row g-4">
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">
                                        <span class="wait-dot" style="background:#16a34a;"></span>
                                        Green — up to (min)
                                    </label>
                                    <input type="number" name="wait_green_max" min="1" max="999"
                                        class="form-control clinical-input @error('wait_green_max') is-invalid @enderror"
                                        value="{{ old('wait_green_max', $settings['wait_green_max'] ?? 30) }}"
                                        placeholder="30">
                                    @error('wait_green_max')
                                    <div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">
                                        <span class="wait-dot" style="background:#ea580c;"></span>
                                        Orange — up to (min)
                                    </label>
                                    <input type="number" name="wait_orange_max" min="1" max="999"
                                        class="form-control clinical-input @error('wait_orange_max') is-invalid @enderror"
                                        value="{{ old('wait_orange_max', $settings['wait_orange_max'] ?? 60) }}"
                                        placeholder="60">
                                    @error('wait_orange_max')
                                    <div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">
                                        <span class="wait-dot" style="background:#dc2626;"></span>
                                        Red — up to (min)
                                    </label>
                                    <input type="number" name="wait_red_max" min="1" max="999"
                                        class="form-control clinical-input @error('wait_red_max') is-invalid @enderror"
                                        value="{{ old('wait_red_max', $settings['wait_red_max'] ?? 120) }}"
                                        placeholder="120">
                                    @error('wait_red_max')
                                    <div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">
                                        <span class="wait-dot"
                                            style="background:linear-gradient(135deg,#dc2626,#ea580c);"></span>
                                        Fire — above Red
                                    </label>
                                    <input type="text" class="form-control clinical-input"
                                        value="Automatic (above Red limit)" disabled>
                                </div>
                            </div>

                            {{-- Live preview R --}}
                            <div class="mt-4 p-3 rounded-3"
                                style="background:rgba(27,79,114,.04);border:1px solid rgba(27,79,114,.1);">
                                <p class="mb-2 fw-semibold" style="color:#1B4F72;font-size:.88rem;"><i
                                        class="bi bi-circle-fill me-1" style="color:#1B4F72;font-size:.6rem;"></i> R —
                                    Reception Entry Preview</p>
                                <div class="d-flex flex-wrap gap-3 align-items-center">
                                    <span class="wait-pill wait-green"><span class="wp-r">R</span><span class="wp-time"
                                            id="prev-green">0 – 30m</span></span>
                                    <span class="wait-pill wait-orange"><span class="wp-r">R</span><span class="wp-time"
                                            id="prev-orange">30 – 60m</span></span>
                                    <span class="wait-pill wait-red"><span class="wp-r">R</span><span class="wp-time"
                                            id="prev-red">60 – 120m</span></span>
                                    <span class="wait-pill wait-fire"><span class="wp-r">R</span><span class="wp-time"
                                            id="prev-fire">120m+</span></span>
                                </div>
                            </div>

                            {{-- D badge (Dilated) thresholds --}}
                            <hr class="my-4">
                            <h6 class="fw-bold mb-1" style="color:#1B4F72;">
                                <span class="wait-pill wait-orange" style="font-size:.75rem;"><span class="wp-r"
                                        style="width:20px;height:20px;font-size:.6rem;">D</span></span>
                                Dilated (D) — Primary done with Dilation = Yes
                            </h6>
                            <div class="row g-4">
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold"><span class="wait-dot"
                                            style="background:#16a34a;"></span> Green — up to (min)</label>
                                    <input type="number" name="wait_d_green_max" min="1" max="999"
                                        class="form-control clinical-input @error('wait_d_green_max') is-invalid @enderror"
                                        value="{{ old('wait_d_green_max', $settings['wait_d_green_max'] ?? 40) }}"
                                        placeholder="40">
                                    @error('wait_d_green_max')
                                    <div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold"><span class="wait-dot"
                                            style="background:#ea580c;"></span> Orange — up to (min)</label>
                                    <input type="number" name="wait_d_orange_max" min="1" max="999"
                                        class="form-control clinical-input @error('wait_d_orange_max') is-invalid @enderror"
                                        value="{{ old('wait_d_orange_max', $settings['wait_d_orange_max'] ?? 90) }}"
                                        placeholder="90">
                                    @error('wait_d_orange_max')
                                    <div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold"><span class="wait-dot"
                                            style="background:#dc2626;"></span> Red — up to (min)</label>
                                    <input type="number" name="wait_d_red_max" min="1" max="999"
                                        class="form-control clinical-input @error('wait_d_red_max') is-invalid @enderror"
                                        value="{{ old('wait_d_red_max', $settings['wait_d_red_max'] ?? 120) }}"
                                        placeholder="120">
                                    @error('wait_d_red_max')
                                    <div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="mt-3 p-3 rounded-3"
                                style="background:rgba(27,79,114,.04);border:1px solid rgba(27,79,114,.1);">
                                <p class="mb-2 fw-semibold" style="color:#1B4F72;font-size:.88rem;"><i
                                        class="bi bi-circle-fill me-1" style="color:#ea580c;font-size:.6rem;"></i> D —
                                    Dilated Preview</p>
                                <div class="d-flex flex-wrap gap-3 align-items-center">
                                    <span class="wait-pill wait-green"><span class="wp-r">D</span><span class="wp-time"
                                            id="prev-d-green">0 – 40m</span></span>
                                    <span class="wait-pill wait-orange"><span class="wp-r">D</span><span class="wp-time"
                                            id="prev-d-orange">40 – 90m</span></span>
                                    <span class="wait-pill wait-red"><span class="wp-r">D</span><span class="wp-time"
                                            id="prev-d-red">90 – 120m</span></span>
                                    <span class="wait-pill wait-fire"><span class="wp-r">D</span><span class="wp-time"
                                            id="prev-d-fire">120m+</span></span>
                                </div>
                            </div>

                            {{-- ND badge (Not Dilated) thresholds --}}
                            <hr class="my-4">
                            <h6 class="fw-bold mb-1" style="color:#1B4F72;">
                                <span class="wait-pill wait-orange" style="font-size:.75rem;"><span class="wp-r"
                                        style="width:20px;height:20px;font-size:.55rem;">ND</span></span>
                                Not Dilated (ND) — Primary done with Dilation = No
                            </h6>
                            <div class="row g-4">
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold"><span class="wait-dot"
                                            style="background:#16a34a;"></span> Green — up to (min)</label>
                                    <input type="number" name="wait_nd_green_max" min="1" max="999"
                                        class="form-control clinical-input @error('wait_nd_green_max') is-invalid @enderror"
                                        value="{{ old('wait_nd_green_max', $settings['wait_nd_green_max'] ?? 20) }}"
                                        placeholder="20">
                                    @error('wait_nd_green_max')
                                    <div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold"><span class="wait-dot"
                                            style="background:#ea580c;"></span> Orange — up to (min)</label>
                                    <input type="number" name="wait_nd_orange_max" min="1" max="999"
                                        class="form-control clinical-input @error('wait_nd_orange_max') is-invalid @enderror"
                                        value="{{ old('wait_nd_orange_max', $settings['wait_nd_orange_max'] ?? 60) }}"
                                        placeholder="60">
                                    @error('wait_nd_orange_max')
                                    <div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold"><span class="wait-dot"
                                            style="background:#dc2626;"></span> Red — up to (min)</label>
                                    <input type="number" name="wait_nd_red_max" min="1" max="999"
                                        class="form-control clinical-input @error('wait_nd_red_max') is-invalid @enderror"
                                        value="{{ old('wait_nd_red_max', $settings['wait_nd_red_max'] ?? 120) }}"
                                        placeholder="120">
                                    @error('wait_nd_red_max')
                                    <div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="mt-3 p-3 rounded-3"
                                style="background:rgba(27,79,114,.04);border:1px solid rgba(27,79,114,.1);">
                                <p class="mb-2 fw-semibold" style="color:#1B4F72;font-size:.88rem;"><i
                                        class="bi bi-circle-fill me-1" style="color:#ea580c;font-size:.6rem;"></i> ND — Not
                                    Dilated Preview</p>
                                <div class="d-flex flex-wrap gap-3 align-items-center">
                                    <span class="wait-pill wait-green"><span class="wp-r">ND</span><span class="wp-time"
                                            id="prev-nd-green">0 – 20m</span></span>
                                    <span class="wait-pill wait-orange"><span class="wp-r">ND</span><span class="wp-time"
                                            id="prev-nd-orange">20 – 60m</span></span>
                                    <span class="wait-pill wait-red"><span class="wp-r">ND</span><span class="wp-time"
                                            id="prev-nd-red">60 – 120m</span></span>
                                    <span class="wait-pill wait-fire"><span class="wp-r">ND</span><span class="wp-time"
                                            id="prev-nd-fire">120m+</span></span>
                                </div>
                            </div>
                        </div>
                        {{-- END WAIT STATUS TAB --}}

                    </div>{{-- /.tab-content --}}

                    {{-- Save Bar --}}
                    <div class="settings-save-wrap">
                        <button type="submit" class="btn btn-primary fw-bold px-5 settings-save-btn">
                            <i class="bi bi-save me-2"></i> Save All Settings
                        </button>
                    </div>

                </form>
            </div>{{-- /.card-body --}}
        </div>{{-- /.settings-card --}}
    </div>{{-- /.hospital-settings-page --}}

    @push('scripts')
        <script>
            (function () {
                var eyeSvg = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M1.5 12s3.5-7 10.5-7 10.5 7 10.5 7-3.5 7-10.5 7S1.5 12 1.5 12Z" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path><circle cx="12" cy="12" r="3.2" stroke-width="1.8"></circle></svg>';
                var eyeSlashSvg = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3l18 18" stroke-width="1.8" stroke-linecap="round"></path><path d="M10.2 5.1A11.3 11.3 0 0 1 12 5c7 0 10.5 7 10.5 7a19 19 0 0 1-4.1 4.7" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path><path d="M9.9 9.9A3.2 3.2 0 0 0 12 15.2c.5 0 1-.1 1.4-.3" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path><path d="M7.1 7.4C4 9.6 1.5 12 1.5 12s3.5 7 10.5 7c1.7 0 3.2-.3 4.6-.8" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path></svg>';

                function bindPasswordToggle(inputId, buttonId, iconId) {
                    var input = document.getElementById(inputId);
                    var button = document.getElementById(buttonId);
                    var icon = document.getElementById(iconId);

                    if (!input || !button || !icon) {
                        return;
                    }

                    button.addEventListener('click', function () {
                        var isHidden = input.type === 'password';
                        input.type = isHidden ? 'text' : 'password';
                        icon.innerHTML = isHidden ? eyeSlashSvg : eyeSvg;
                    });
                }

                document.addEventListener('DOMContentLoaded', function () {
                    bindPasswordToggle('currentPasswordField', 'toggleCurrentPasswordField', 'currentPasswordEyeIcon');
                    bindPasswordToggle('newPasswordField', 'toggleNewPasswordField', 'newPasswordEyeIcon');
                    bindPasswordToggle('newPasswordConfirmationField', 'toggleNewPasswordConfirmationField', 'newPasswordConfirmationEyeIcon');
                });
            })();

            // File select → preview + optional bg removal
            (function () {
                var input = document.getElementById('logoFileInput');
                var imgOrig = document.getElementById('logoPreviewOriginal');
                var imgDark = document.getElementById('logoPreviewDark');
                var ph1 = document.getElementById('logoPlaceholder1');
                var ph2 = document.getElementById('logoPlaceholder2');

                if (!input) return;

                input.addEventListener('change', function () {
                    var file = this.files && this.files[0];
                    if (!file) return;

                    var reader = new FileReader();
                    reader.onload = function (e) {
                        var src = e.target.result;
                        _currentRawSrc = src;
                        _currentNobgSrc = null;

                        // Original box — always raw
                        if (imgOrig) { imgOrig.src = src; imgOrig.style.display = 'block'; }
                        if (ph1) ph1.style.display = 'none';

                        // Sidebar box — raw first, bg-removal updates it
                        if (imgDark) { imgDark.src = src; imgDark.style.display = 'block'; }
                        if (ph2) ph2.style.display = 'none';

                        // Always process bg removal (store both versions)
                        processAndStore(src);
                    };
                    reader.readAsDataURL(file);
                });
            })();

            // ── Logo background removal ───────────────────────────────────────────────
            function removeWhiteBg(imgSrc, threshold, callback) {
                var img = new Image();
                img.crossOrigin = 'anonymous';
                img.onload = function () {
                    var canvas = document.createElement('canvas');
                    canvas.width = img.naturalWidth;
                    canvas.height = img.naturalHeight;
                    var ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0);
                    var data = ctx.getImageData(0, 0, canvas.width, canvas.height);
                    var px = data.data;
                    for (var i = 0; i < px.length; i += 4) {
                        // If pixel is near-white AND opaque → make transparent
                        if (px[i] > threshold && px[i + 1] > threshold && px[i + 2] > threshold) {
                            px[i + 3] = 0;
                        }
                    }
                    ctx.putImageData(data, 0, 0);
                    callback(canvas.toDataURL('image/png'));
                };
                img.src = imgSrc;
            }

            var _currentRawSrc = null;
            var _currentNobgSrc = null; // always keep bg-removed version

            function processAndStore(rawSrc) {
                // Always generate bg-removed version and store in hidden field
                var notice = document.getElementById('bgRemoveNotice');
                var processed = document.getElementById('logoProcessedBase64');
                if (notice) notice.classList.remove('d-none');
                removeWhiteBg(rawSrc, 230, function (transparentSrc) {
                    _currentNobgSrc = transparentSrc;
                    if (processed) processed.value = transparentSrc;
                    // Update sidebar preview based on current style
                    updateDarkPreview();
                });
            }

            function updateDarkPreview() {
                var imgDark = document.getElementById('logoPreviewDark');
                if (!imgDark) return;
                var src = _currentNobgSrc || _currentRawSrc;
                if (src) {
                    imgDark.src = src;
                    imgDark.style.display = 'block';
                    imgDark.style.filter = 'brightness(0) invert(1)';
                }
            }

            // Letter Pad selector
            function selectLetterPad(value) {
                document.getElementById('letterPadInput').value = value;
                document.querySelectorAll('.letter-pad-option').forEach(function (opt) {
                    var box = opt.querySelector('.letter-pad-box');
                    var dot = opt.querySelector('div > div > div');
                    var isActive = opt.getAttribute('onclick') === "selectLetterPad('" + value + "')";
                    if (box) {
                        box.style.border = isActive ? '2px solid #1B4F72' : '2px solid rgba(27,79,114,.18)';
                        box.style.background = isActive ? 'rgba(27,79,114,.06)' : '#fff';
                    }
                    if (dot) {
                        dot.style.borderColor = isActive ? '#1B4F72' : 'rgba(27,79,114,.3)';
                        dot.innerHTML = isActive ? '<div style="width:8px;height:8px;border-radius:50%;background:#1B4F72;"></div>' : '';
                    }
                });
            }

            // Logo style selector — only updates preview, doesn't re-process
            function selectLogoStyle(value) {
                document.getElementById('logoSidebarStyleInput').value = value;
                document.querySelectorAll('.logo-style-option').forEach(function (opt) {
                    opt.classList.remove('active');
                    if (opt.getAttribute('onclick') === "selectLogoStyle('" + value + "')") {
                        opt.classList.add('active');
                    }
                });
                updateDarkPreview();
            }

            // ── Location cascade dropdowns (Select2-safe) ─────────────────────────────
            // Build API URLs from the current page path so Laragon subfolders work even when
            // APP_URL is only "http://localhost" (route() would miss /eye-saas/public).
            // Page path: /eye-saas/public/{slug}/settings  →  base /eye-saas/public/location/...
            // Live path: /{slug}/settings               →  base /location/...
            // Timezone/currency still use option data-* (no fetch).
            (function () {
                function bootLocationCascade() {
                    const countrySel = document.getElementById('settingCountry');
                    const stateSel = document.getElementById('settingState');
                    const districtSel = document.getElementById('settingDistrict');
                    const citySel = document.getElementById('settingCity');
                    const tzSel = document.getElementById('settingTimezone');
                    if (!countrySel) return;

                    function appPublicBase() {
                        // Prefer real browser path (handles subfolder installs)
                        var path = window.location.pathname || '';
                        var match = path.match(/^(.*)\/[^\/]+\/settings\/?$/i);
                        if (match) {
                            return (match[1] || '').replace(/\/$/, '');
                        }
                        // Fallback: Laravel request base path (when rewrite exposes it)
                        var serverBase = @json(rtrim((string) request()->getBasePath(), '/'));
                        return serverBase || '';
                    }

                    var publicBase = appPublicBase();
                    const statesUrl = publicBase + '/location/states';
                    const districtsUrl = publicBase + '/location/districts';
                    const citiesUrl = publicBase + '/location/cities';

                    var suppressCascade = false;

                    function isSelect2(el) {
                        return !!(window.jQuery && el && jQuery(el).hasClass('select2-hidden-accessible'));
                    }

                    function optionDataId(el) {
                        if (!el) return '';
                        // Prefer selected option / Select2 value match
                        var opt = el.options[el.selectedIndex];
                        if (opt && (opt.getAttribute('data-id') || (opt.dataset && opt.dataset.id))) {
                            return opt.getAttribute('data-id') || opt.dataset.id || '';
                        }
                        // Fallback: find option by current value (Select2 edge cases)
                        var val = el.value;
                        if (!val) return '';
                        for (var i = 0; i < el.options.length; i++) {
                            if (el.options[i].value === val) {
                                return el.options[i].getAttribute('data-id') || el.options[i].dataset.id || '';
                            }
                        }
                        return '';
                    }

                    function refreshSelect2(sel) {
                        if (!window.jQuery || !sel || typeof jQuery.fn.select2 !== 'function') return;
                        // Options replaced in DOM — destroy/re-init Select2 so results rebuild.
                        // Note: initHmsSelect2(root) only scans descendants, not the select itself.
                        try {
                            var $el = jQuery(sel);
                            if ($el.hasClass('no-select2') || $el.hasClass('form-select-sm')) return;

                            var current = $el.val();
                            if ($el.hasClass('select2-hidden-accessible')) {
                                $el.select2('destroy');
                            }

                            var placeholder = jQuery.trim($el.find('option[value=""]').first().text()) || 'Select...';
                            var allowClear = !$el.prop('required') && $el.find('option[value=""]').length > 0;
                            $el.select2({
                                width: '100%',
                                placeholder: placeholder,
                                allowClear: allowClear,
                                dropdownAutoWidth: false
                            });
                            if (current) {
                                $el.val(current);
                            }
                            $el.trigger('change.select2');
                        } catch (e) {
                            try { jQuery(sel).trigger('change.select2'); } catch (e2) { /* ignore */ }
                        }
                    }

                    function resetSelect(sel, placeholder) {
                        if (!sel) return;
                        suppressCascade = true;
                        var empty = document.createElement('option');
                        empty.value = '';
                        empty.textContent = placeholder;
                        empty.selected = true;
                        sel.innerHTML = '';
                        sel.appendChild(empty);
                        refreshSelect2(sel);
                        suppressCascade = false;
                    }

                    function loadOptions(sel, items, savedValue, placeholderText) {
                        if (!sel) return;
                        items = Array.isArray(items) ? items : [];
                        suppressCascade = true;

                        var placeholder = placeholderText
                            || (sel.options[0] && sel.options[0].textContent)
                            || '— Select —';

                        sel.innerHTML = '';
                        var empty = document.createElement('option');
                        empty.value = '';
                        empty.textContent = placeholder;
                        sel.appendChild(empty);

                        var matchFound = false;
                        items.forEach(function (item) {
                            if (!item || item.name == null) return;
                            var opt = document.createElement('option');
                            opt.value = item.name;
                            opt.setAttribute('data-id', item.id);
                            opt.textContent = item.name;
                            if (savedValue && String(item.name) === String(savedValue)) {
                                opt.selected = true;
                                matchFound = true;
                            }
                            sel.appendChild(opt);
                        });

                        if (!matchFound) {
                            sel.value = '';
                        }

                        refreshSelect2(sel);
                        if (window.jQuery && isSelect2(sel) && matchFound) {
                            jQuery(sel).val(savedValue).trigger('change.select2');
                        }
                        suppressCascade = false;
                    }

                    function fetchJson(url, callback) {
                        fetch(url, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            credentials: 'same-origin'
                        })
                            .then(function (r) {
                                if (!r.ok) {
                                    throw new Error('HTTP ' + r.status);
                                }
                                return r.json();
                            })
                            .then(function (data) {
                                if (Array.isArray(data)) {
                                    callback(data);
                                } else if (data && Array.isArray(data.data)) {
                                    callback(data.data);
                                } else {
                                    callback([]);
                                }
                            })
                            .catch(function (err) {
                                console.error('Location cascade failed:', url, err);
                                callback([]);
                            });
                    }

                    function applyCountryMasterFields(selectEl) {
                        if (!selectEl || selectEl.selectedIndex < 0) return;
                        var opt = selectEl.options[selectEl.selectedIndex];
                        if (!opt || !opt.value) return;

                        if (tzSel && opt.dataset.timezone) {
                            tzSel.value = opt.dataset.timezone;
                            if (window.jQuery) {
                                jQuery(tzSel).val(opt.dataset.timezone).trigger('change');
                            }
                        }

                        var currencyDisplay = document.getElementById('settingCurrencyDisplay');
                        if (currencyDisplay) {
                            var sym = opt.dataset.currencySymbol || '';
                            var code = opt.dataset.currencyCode || '';
                            currencyDisplay.value = (sym || code) ? (sym + ' ' + code).trim() : '';
                        }
                    }

                    var countryChangeBusy = false;
                    function onCountryChange() {
                        if (suppressCascade || countryChangeBusy) return;
                        countryChangeBusy = true;
                        try {
                            applyCountryMasterFields(countrySel);

                            resetSelect(stateSel, '— Select State —');
                            resetSelect(districtSel, '— Select District —');
                            resetSelect(citySel, '— Select City —');

                            var countryId = optionDataId(countrySel);
                            if (!countryId) {
                                console.warn('Location cascade: country has no data-id');
                                return;
                            }

                            fetchJson(statesUrl + '?country_id=' + encodeURIComponent(countryId), function (data) {
                                loadOptions(stateSel, data, '', '— Select State —');
                            });
                        } finally {
                            setTimeout(function () { countryChangeBusy = false; }, 50);
                        }
                    }

                    function onStateChange() {
                        if (suppressCascade) return;
                        resetSelect(districtSel, '— Select District —');
                        resetSelect(citySel, '— Select City —');

                        var stateId = optionDataId(stateSel);
                        if (!stateId) return;

                        fetchJson(districtsUrl + '?state_id=' + encodeURIComponent(stateId), function (districts) {
                            loadOptions(districtSel, districts, '', '— Select District —');
                            if (!districts.length) {
                                fetchJson(citiesUrl + '?state_id=' + encodeURIComponent(stateId), function (cities) {
                                    loadOptions(citySel, cities, '', '— Select City —');
                                });
                            }
                        });
                    }

                    function onDistrictChange() {
                        if (suppressCascade) return;
                        resetSelect(citySel, '— Select City —');

                        var districtId = optionDataId(districtSel);
                        if (!districtId) return;

                        fetchJson(citiesUrl + '?district_id=' + encodeURIComponent(districtId), function (data) {
                            loadOptions(citySel, data, '', '— Select City —');
                        });
                    }

                    function bindSelectChange(el, handler) {
                        if (!el) return;
                        if (window.jQuery) {
                            jQuery(el).off('change.locCascade').on('change.locCascade', handler);
                        } else {
                            el.removeEventListener('change', handler);
                            el.addEventListener('change', handler);
                        }
                    }

                    bindSelectChange(countrySel, onCountryChange);
                    bindSelectChange(stateSel, onStateChange);
                    bindSelectChange(districtSel, onDistrictChange);

                    // Currency/timezone for already-selected country (do not wipe state lists)
                    setTimeout(function () {
                        applyCountryMasterFields(countrySel);
                    }, 200);
                }

                if (window.jQuery) {
                    // After global Select2 init (this stack runs before initHmsSelect2 script)
                    jQuery(function () {
                        setTimeout(bootLocationCascade, 0);
                    });
                } else if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', bootLocationCascade);
                } else {
                    bootLocationCascade();
                }
            })();

            // Wait Status preview
            (function () {
                function bindPreview(gName, oName, rName, pgId, poId, prId, pfId) {
                    const g = document.querySelector('[name="' + gName + '"]');
                    const o = document.querySelector('[name="' + oName + '"]');
                    const r = document.querySelector('[name="' + rName + '"]');
                    function update() {
                        const gv = parseInt(g?.value) || 0, ov = parseInt(o?.value) || 0, rv = parseInt(r?.value) || 0;
                        const pg = document.getElementById(pgId), po = document.getElementById(poId);
                        const pr = document.getElementById(prId), pf = document.getElementById(pfId);
                        if (pg) pg.textContent = '0 – ' + gv + 'm';
                        if (po) po.textContent = gv + ' – ' + ov + 'm';
                        if (pr) pr.textContent = ov + ' – ' + rv + 'm';
                        if (pf) pf.textContent = rv + 'm+';
                    }
                    [g, o, r].forEach(el => el?.addEventListener('input', update));
                    update();
                }
                bindPreview('wait_green_max', 'wait_orange_max', 'wait_red_max', 'prev-green', 'prev-orange', 'prev-red', 'prev-fire');
                bindPreview('wait_d_green_max', 'wait_d_orange_max', 'wait_d_red_max', 'prev-d-green', 'prev-d-orange', 'prev-d-red', 'prev-d-fire');
                bindPreview('wait_nd_green_max', 'wait_nd_orange_max', 'wait_nd_red_max', 'prev-nd-green', 'prev-nd-orange', 'prev-nd-red', 'prev-nd-fire');
            })();
        </script>
    @endpush
@endsection
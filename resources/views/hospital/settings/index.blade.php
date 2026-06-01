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
        background: linear-gradient(135deg, rgba(235,245,251,.96), rgba(255,255,255,.94));
        box-shadow: 0 18px 48px rgba(27,79,114,.10);
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
        width: 52px; height: 52px;
        border-radius: 17px;
        display: inline-flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        background: var(--settings-primary);
        color: #fff;
        box-shadow: 0 14px 30px rgba(27,79,114,.22);
    }
    .settings-kicker {
        display: inline-flex; align-items: center;
        color: var(--settings-secondary);
        font-size: .72rem; font-weight: 900;
        letter-spacing: .08em; text-transform: uppercase;
        margin-bottom: .15rem;
    }
    .settings-hero h4 { color: var(--settings-primary) !important; font-weight: 900; }
    .settings-hero p  { margin: .15rem 0 0; color: var(--settings-muted); font-size: .9rem; }

    .settings-card {
        border: 1px solid var(--settings-border) !important;
        border-radius: 22px;
        background: rgba(255,255,255,.88);
        box-shadow: 0 18px 48px rgba(27,79,114,.10) !important;
        overflow: hidden;
        animation: settings-card-rise 520ms cubic-bezier(.2,.9,.2,1) both;
    }
    .settings-card-header {
        background: linear-gradient(135deg, rgba(235,245,251,.92), rgba(255,255,255,.96)) !important;
        border-bottom: 1px solid var(--settings-border) !important;
    }

    .settings-tabs { gap: .5rem; flex-wrap: wrap; }
    .settings-tabs .nav-link {
        border: 1px solid var(--settings-border) !important;
        border-radius: 999px;
        color: var(--settings-primary);
        background: rgba(255,255,255,.78);
        font-weight: 700 !important;
        padding: .55rem .95rem;
        display: inline-flex; align-items: center;
        transition: transform 170ms ease, box-shadow 170ms ease, background 170ms ease, color 170ms ease;
    }
    .settings-tabs .nav-link:hover,
    .settings-tabs .nav-link.active {
        background: var(--settings-primary) !important;
        border-color: var(--settings-primary) !important;
        color: #fff !important;
        transform: translateY(-1px);
        box-shadow: 0 12px 26px rgba(27,79,114,.16);
    }

    .settings-card-body { background: var(--settings-soft) !important; padding: 1rem !important; }

    .settings-tab-content {
        border: 1px solid var(--settings-border) !important;
        border-radius: 18px !important;
        box-shadow: 0 12px 32px rgba(27,79,114,.08) !important;
        background: rgba(255,255,255,.94) !important;
        padding: 1.5rem !important;
    }

    .settings-logo-box {
        width: 110px !important; height: 110px !important;
        border: 1px solid var(--settings-border) !important;
        border-radius: 18px !important;
        background: linear-gradient(135deg, #fff, rgba(235,245,251,.72)) !important;
        box-shadow: inset 0 0 0 1px rgba(255,255,255,.7), 0 8px 20px rgba(27,79,114,.08);
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    /* Dark preview box — sidebar pe kaisa dikhega */
    .settings-logo-box-dark {
        width: 110px !important; height: 110px !important;
        border: 1px solid rgba(27,79,114,.3) !important;
        border-radius: 18px !important;
        background: #1B4F72 !important;
        box-shadow: 0 8px 20px rgba(27,79,114,.18);
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        position: relative;
    }
    .settings-logo-box-dark::after {
        content: 'Sidebar Preview';
        position: absolute;
        bottom: 0; left: 0; right: 0;
        font-size: .60rem;
        font-weight: 700;
        text-align: center;
        color: rgba(255,255,255,.55);
        background: rgba(0,0,0,.18);
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
    .settings-upload-panel { min-width: min(100%, 340px); }

    .hospital-settings-page .form-label {
        color: var(--settings-primary);
        font-weight: 700;
        font-size: .875rem;
        margin-bottom: .35rem;
    }
    .hospital-settings-page .clinical-input {
        border: 1.5px solid rgba(27,79,114,.16);
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
        box-shadow: 0 0 0 4px rgba(41,128,185,.12);
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
        background: linear-gradient(135deg, rgba(235,245,251,.72), rgba(255,255,255,.96));
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
        box-shadow: 0 12px 26px rgba(27,79,114,.18);
        transition: transform 170ms ease, box-shadow 170ms ease;
        white-space: nowrap;
    }
    .settings-save-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 16px 34px rgba(27,79,114,.24);
        color: #fff !important;
    }

    @keyframes settings-page-in {
        from { opacity: 0; transform: translateY(8px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    @keyframes settings-card-rise {
        from { opacity: 0; transform: translateY(12px) scale(.99); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }
    @media (prefers-reduced-motion: reduce) {
        .hospital-settings-page,
        .settings-card { animation: none; }
        .hospital-settings-page * { transition: none !important; }
    }
    @media (max-width: 576px) {
        .settings-hero { align-items: flex-start; }
        .settings-hero-icon { width: 44px; height: 44px; border-radius: 14px; }
        .settings-tabs .nav-link { width: 100%; justify-content: center; }
        .settings-save-wrap { flex-direction: column; align-items: stretch; }
        .settings-save-btn { width: 100%; text-align: center; }
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
                <button class="nav-link active" id="profile-tab" data-bs-toggle="tab"
                        data-bs-target="#profile" type="button" role="tab">
                    <i class="bi bi-building me-2"></i> Profile
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="billing-tab" data-bs-toggle="tab"
                        data-bs-target="#billing" type="button" role="tab">
                    <i class="bi bi-receipt me-2"></i> Billing
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="print-tab" data-bs-toggle="tab"
                        data-bs-target="#print" type="button" role="tab">
                    <i class="bi bi-printer me-2"></i> Print Settings
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="pagination-tab" data-bs-toggle="tab"
                        data-bs-target="#pagination" type="button" role="tab">
                    <i class="bi bi-table me-2"></i> Pagination
                </button>
            </li>
        </ul>
    </div>

    {{-- Card Body — one form wraps the whole tab-content --}}
    <div class="card-body settings-card-body">
        <form action="{{ route('hospital.settings.update', ['slug' => $slug]) }}"
              method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="tab-content settings-tab-content" id="settingsTabsContent">

                {{-- ══════════════ PROFILE TAB ══════════════ --}}
                <div class="tab-pane fade show active" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                    <div class="row g-4">

                        {{-- Logo --}}
                        <div class="col-12">
                            <div class="d-flex align-items-start gap-4 flex-wrap">

                                {{-- Preview 1: Original Logo (light background) --}}
                                <div class="d-flex flex-column align-items-center">
                                    <div class="settings-logo-box">
                                        @if(!empty($settings['hospital_logo']))
                                            <img id="logoPreviewOriginal"
                                                 src="{{ asset('storage/' . $settings['hospital_logo']) }}"
                                                 alt="Hospital Logo"
                                                 style="max-width:88%;max-height:88%;object-fit:contain;"
                                                 onerror="this.style.display='none';document.getElementById('logoPlaceholder1').style.display='block'">
                                            <i id="logoPlaceholder1" class="bi bi-image text-muted" style="font-size:2.2rem;opacity:.35;display:none"></i>
                                        @else
                                            <img id="logoPreviewOriginal" src="" alt="" style="max-width:88%;max-height:88%;object-fit:contain;display:none;">
                                            <i id="logoPlaceholder1" class="bi bi-image text-muted" style="font-size:2.2rem;opacity:.35"></i>
                                        @endif
                                    </div>
                                    <div class="logo-preview-label">Original</div>
                                </div>

                                {{-- Preview 2: Sidebar Preview (dark background, white logo) --}}
                                <div class="d-flex flex-column align-items-center">
                                    <div class="settings-logo-box-dark">
                                        @if(!empty($settings['hospital_logo']))
                                            <img id="logoPreviewDark"
                                                 src="{{ asset('storage/' . $settings['hospital_logo']) }}"
                                                 alt="Sidebar Preview"
                                                 style="max-width:72%;max-height:72%;object-fit:contain;filter:brightness(0) invert(1);margin-bottom:14px;"
                                                 onerror="this.style.display='none';document.getElementById('logoPlaceholder2').style.display='block'">
                                            <i id="logoPlaceholder2" class="bi bi-image" style="font-size:2rem;color:rgba(255,255,255,.3);display:none;margin-bottom:14px"></i>
                                        @else
                                            <img id="logoPreviewDark" src="" alt="" style="max-width:72%;max-height:72%;object-fit:contain;filter:brightness(0) invert(1);margin-bottom:14px;display:none;">
                                            <i id="logoPlaceholder2" class="bi bi-image" style="font-size:2rem;color:rgba(255,255,255,.3);margin-bottom:14px"></i>
                                        @endif
                                    </div>
                                    <div class="logo-preview-label">Sidebar View</div>
                                </div>

                                {{-- Upload Panel --}}
                                <div class="settings-upload-panel">
                                    <label class="form-label">
                                        Hospital Logo
                                        <small class="fw-normal ms-1" style="color:var(--settings-muted)">(JPG, PNG, SVG or WebP — max 2 MB)</small>
                                    </label>
                                    <input type="file" name="hospital_logo" accept="image/*"
                                           id="logoFileInput"
                                           class="form-control clinical-input @error('hospital_logo') is-invalid @enderror">
                                    @error('hospital_logo')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <div class="mt-2 d-flex align-items-center gap-2" style="font-size:.76rem;color:var(--settings-muted)">
                                        <i class="bi bi-info-circle"></i>
                                        Transparent PNG sabse acha — sidebar pe white dikhega, yahan original colors mein.
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
                                   placeholder="+91 98765 43210" required>
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
                            <div class="form-text" style="font-size:.75rem;color:var(--settings-muted)">Printed on bills and patient documents.</div>
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

                        {{-- ── Change Password divider ── --}}
                        <div class="col-12">
                            <div class="pw-section-divider">
                                <span class="pw-section-label">
                                    <i class="bi bi-lock-fill"></i> Change Password
                                </span>
                            </div>
                            <p style="font-size:.78rem;color:var(--settings-muted);margin-top:-.5rem">
                                Leave all three fields blank to keep your current password unchanged.
                            </p>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Current Password</label>
                            <div class="password-field-wrap">
                                <input type="password" name="current_password" id="currentPasswordField"
                                       class="form-control clinical-input password-field-input @error('current_password') is-invalid @enderror"
                                       autocomplete="current-password" placeholder="Enter current password">
                                <button type="button" id="toggleCurrentPasswordField" class="password-field-toggle" aria-label="Toggle current password visibility">
                                    <svg id="currentPasswordEyeIcon" viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M1.5 12s3.5-7 10.5-7 10.5 7 10.5 7-3.5 7-10.5 7S1.5 12 1.5 12Z" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
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
                                <button type="button" id="toggleNewPasswordField" class="password-field-toggle" aria-label="Toggle new password visibility">
                                    <svg id="newPasswordEyeIcon" viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M1.5 12s3.5-7 10.5-7 10.5 7 10.5 7-3.5 7-10.5 7S1.5 12 1.5 12Z" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
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
                                <input type="password" name="new_password_confirmation" id="newPasswordConfirmationField"
                                       class="form-control clinical-input password-field-input"
                                       autocomplete="new-password" placeholder="Repeat new password">
                                <button type="button" id="toggleNewPasswordConfirmationField" class="password-field-toggle" aria-label="Toggle confirm new password visibility">
                                    <svg id="newPasswordConfirmationEyeIcon" viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M1.5 12s3.5-7 10.5-7 10.5 7 10.5 7-3.5 7-10.5 7S1.5 12 1.5 12Z" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
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
                            <label class="form-label">Default Tax Percentage (%) <span class="text-danger">*</span></label>
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
                                <option value="10"  {{ $currentLimit === '10'  ? 'selected' : '' }}>10 Records</option>
                                <option value="25"  {{ $currentLimit === '25'  ? 'selected' : '' }}>25 Records</option>
                                <option value="50"  {{ $currentLimit === '50'  ? 'selected' : '' }}>50 Records</option>
                                <option value="100" {{ $currentLimit === '100' ? 'selected' : '' }}>100 Records</option>
                            </select>
                            @error('pagination_limit')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                {{-- END PAGINATION TAB --}}

            </div>{{-- /.tab-content --}}

            {{-- Save Bar --}}
            <div class="settings-save-wrap">
                <span class="settings-save-hint">
                    <i class="bi bi-info-circle me-1"></i>
                    Changes apply to the currently active tab section.
                </span>
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

// File select hone par existing preview boxes ka src update karo (koi duplicate nahi)
(function () {
    var input    = document.getElementById('logoFileInput');
    var imgOrig  = document.getElementById('logoPreviewOriginal');
    var imgDark  = document.getElementById('logoPreviewDark');
    var ph1      = document.getElementById('logoPlaceholder1');
    var ph2      = document.getElementById('logoPlaceholder2');

    if (!input) return;

    input.addEventListener('change', function () {
        var file = this.files && this.files[0];
        if (!file) return;

        var reader = new FileReader();
        reader.onload = function (e) {
            var src = e.target.result;

            // Original box update
            imgOrig.src = src;
            imgOrig.style.display = 'block';
            if (ph1) ph1.style.display = 'none';

            // Sidebar (dark) box update
            imgDark.src = src;
            imgDark.style.display = 'block';
            if (ph2) ph2.style.display = 'none';
        };
        reader.readAsDataURL(file);
    });
})();
</script>
@endpush
@endsection

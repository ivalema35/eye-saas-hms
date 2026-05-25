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
        --settings-border-strong: rgba(27, 79, 114, .22);
        --settings-muted: rgba(27, 79, 114, .68);
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
        letter-spacing: -.2px;
    }

    .settings-hero p {
        margin: .15rem 0 0;
        color: var(--settings-muted);
        font-size: .9rem;
        font-weight: 650;
    }

    .settings-card {
        border: 1px solid var(--settings-border) !important;
        border-radius: 22px;
        background: rgba(255, 255, 255, .88);
        box-shadow: 0 18px 48px rgba(27, 79, 114, .10) !important;
        overflow: hidden;
        animation: settings-card-rise 520ms cubic-bezier(.2,.9,.2,1) both;
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
        font-weight: 850 !important;
        padding: .58rem .95rem;
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
    }

    .settings-logo-box {
        width: 124px !important;
        height: 124px !important;
        border: 1px solid var(--settings-border) !important;
        border-radius: 20px !important;
        background: linear-gradient(135deg, #fff, rgba(235, 245, 251, .72)) !important;
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .7), 0 12px 28px rgba(27, 79, 114, .08);
    }

    .settings-upload-panel {
        min-width: min(100%, 360px);
    }

    .hospital-settings-page .form-label {
        color: var(--settings-primary);
        font-weight: 800;
        letter-spacing: -.05px;
    }

    .hospital-settings-page .clinical-input {
        border: 1.5px solid rgba(27, 79, 114, .16);
        border-radius: 12px;
        background-color: #fbfdff;
        color: var(--settings-primary);
        font-weight: 650;
        padding: .62rem .85rem;
        transition: border-color 170ms ease, box-shadow 170ms ease, background 170ms ease;
    }

    .hospital-settings-page .clinical-input:focus {
        background: #fff;
        border-color: var(--settings-secondary);
        box-shadow: 0 0 0 4px rgba(41, 128, 185, .12);
    }

    .settings-save-wrap {
        margin-top: 1rem;
        padding: 1rem;
        border: 1px solid var(--settings-border);
        border-radius: 18px;
        background: linear-gradient(135deg, rgba(235, 245, 251, .72), rgba(255, 255, 255, .96));
        display: flex;
        justify-content: flex-end;
    }

    .settings-save-btn {
        border-radius: 13px;
        font-weight: 900;
        padding: .68rem 1.35rem !important;
        background: var(--settings-primary) !important;
        border-color: var(--settings-primary) !important;
        box-shadow: 0 12px 26px rgba(27, 79, 114, .18);
        transition: transform 170ms ease, box-shadow 170ms ease;
    }

    .settings-save-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 16px 34px rgba(27, 79, 114, .24);
    }

    @keyframes settings-page-in {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes settings-card-rise {
        from { opacity: 0; transform: translateY(12px) scale(.99); }
        to { opacity: 1; transform: translateY(0) scale(1); }
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
            justify-content: stretch;
        }

        .settings-save-btn {
            width: 100%;
        }
    }
</style>

<div class="settings-hero">
    <span class="settings-hero-icon">
        <i class="bi bi-gear-fill fs-4"></i>
    </span>
    <div>
        <span class="settings-kicker"><i class="bi bi-sliders2 me-1"></i> Configuration</span>
        <h4 class="fw-bold mb-0" style="color: var(--color-primary);">
            Hospital Settings
        </h4>
        <p>Manage hospital profile, billing defaults, print notes, and table preferences.</p>
    </div>
</div>

<div class="card premium-card border-0 shadow-sm settings-card">
    <div class="card-header bg-white p-0 border-bottom settings-card-header">
        <ul class="nav nav-tabs px-3 py-3 border-0 settings-tabs" id="settingsTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-bold px-4" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab">
                    <i class="bi bi-building me-2"></i> Profile
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold px-4" id="billing-tab" data-bs-toggle="tab" data-bs-target="#billing" type="button" role="tab">
                    <i class="bi bi-receipt me-2"></i> Billing
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold px-4" id="print-tab" data-bs-toggle="tab" data-bs-target="#print" type="button" role="tab">
                    <i class="bi bi-printer me-2"></i> Print Settings
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold px-4" id="pagination-tab" data-bs-toggle="tab" data-bs-target="#pagination" type="button" role="tab">
                    <i class="bi bi-table me-2"></i> Pagination
                </button>
            </li>
        </ul>
    </div>

    <div class="card-body p-4 bg-light settings-card-body">
        <form action="{{ route('hospital.settings.update', ['slug' => $slug]) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="tab-content bg-white p-4 rounded-3 border shadow-sm settings-tab-content" id="settingsTabsContent">

                <div class="tab-pane fade show active" id="profile" role="tabpanel">
                    <div class="row g-4">
                        <div class="col-md-12 mb-2 d-flex align-items-center gap-4 flex-wrap">
                            <div class="bg-light border rounded-3 d-flex align-items-center justify-content-center settings-logo-box"
                                 style="width: 120px; height: 120px; overflow: hidden;">
                                @if(!empty($settings['hospital_logo']))
                                     <img src="{{ \Illuminate\Support\Facades\Storage::url($settings['hospital_logo']) }}"
                                         alt="Hospital Logo"
                                         class="img-fluid"
                                         style="max-height: 100%;">
                                @else
                                    <i class="bi bi-image text-muted fs-1"></i>
                                @endif
                            </div>
                            <div class="settings-upload-panel">
                                <label class="form-label fw-bold">Hospital Logo</label>
                                <input type="file" name="hospital_logo" class="form-control clinical-input @error('hospital_logo') is-invalid @enderror" accept="image/*">
                                @error('hospital_logo')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Hospital Name <span class="text-danger">*</span></label>
                            <input type="text" name="hospital_name" class="form-control clinical-input @error('hospital_name') is-invalid @enderror"
                                   value="{{ old('hospital_name', $settings['hospital_name'] ?? '') }}" placeholder="Enter hospital name" required>
                            @error('hospital_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Number <span class="text-danger">*</span></label>
                            <input type="text" name="hospital_phone" class="form-control clinical-input @error('hospital_phone') is-invalid @enderror"
                                   value="{{ old('hospital_phone', $settings['hospital_phone'] ?? '') }}" placeholder="Enter contact number" required>
                            @error('hospital_phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Official Email <span class="text-danger">*</span></label>
                            <input type="email" name="hospital_email" class="form-control clinical-input @error('hospital_email') is-invalid @enderror"
                                   value="{{ old('hospital_email', $settings['hospital_email'] ?? '') }}" placeholder="name@example.com" required>
                            @error('hospital_email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Full Address <span class="text-danger">*</span></label>
                            <input type="text" name="hospital_address" class="form-control clinical-input @error('hospital_address') is-invalid @enderror"
                                   value="{{ old('hospital_address', $settings['hospital_address'] ?? '') }}" placeholder="Enter full address" required>
                            @error('hospital_address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="billing" role="tabpanel">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label">Invoice Prefix <span class="text-danger">*</span></label>
                            <input type="text" name="invoice_prefix" class="form-control clinical-input @error('invoice_prefix') is-invalid @enderror"
                                   value="{{ old('invoice_prefix', $settings['invoice_prefix'] ?? 'INV-') }}" placeholder="INV-" required>
                            @error('invoice_prefix')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Default Tax Percentage (%) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="tax_percentage" class="form-control clinical-input @error('tax_percentage') is-invalid @enderror"
                                   value="{{ old('tax_percentage', $settings['tax_percentage'] ?? '0') }}" placeholder="0.00" required>
                            @error('tax_percentage')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="print" role="tabpanel">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label">Print Header Note</label>
                            <input type="text" name="print_header_note" class="form-control clinical-input @error('print_header_note') is-invalid @enderror"
                                   value="{{ old('print_header_note', $settings['print_header_note'] ?? '') }}"
                                   placeholder="e.g. Eye Care with Compassion">
                            @error('print_header_note')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Print Footer Note</label>
                            <input type="text" name="print_footer_note" class="form-control clinical-input @error('print_footer_note') is-invalid @enderror"
                                   value="{{ old('print_footer_note', $settings['print_footer_note'] ?? '') }}"
                                   placeholder="e.g. Thank you for your visit">
                            @error('print_footer_note')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="pagination" role="tabpanel">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label">Default Table Pagination (Per Page) <span class="text-danger">*</span></label>
                            @php $currentLimit = (string) old('pagination_limit', $settings['pagination_limit'] ?? '25'); @endphp
                            <select name="pagination_limit" class="form-select clinical-input @error('pagination_limit') is-invalid @enderror" required>
                                <option value="10" {{ $currentLimit === '10' ? 'selected' : '' }}>10 Records</option>
                                <option value="25" {{ $currentLimit === '25' ? 'selected' : '' }}>25 Records</option>
                                <option value="50" {{ $currentLimit === '50' ? 'selected' : '' }}>50 Records</option>
                                <option value="100" {{ $currentLimit === '100' ? 'selected' : '' }}>100 Records</option>
                            </select>
                            @error('pagination_limit')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

            </div>

            <div class="mt-4 text-end settings-save-wrap">
                <button type="submit" class="btn btn-primary px-5 fw-bold settings-save-btn">
                    <i class="bi bi-save me-2"></i> Save All Settings
                </button>
            </div>
        </form>
    </div>
</div>
</div>
@endsection

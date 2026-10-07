@extends('hospital.layouts.app')
@section('title', 'Record OT Payment')
{{-- Layout page-header intentionally unused — heading, breadcrumb and
actions render inside the content card instead, matching the panel design
used across the rest of the app. --}}

@section('content')
@php
$patient = $booking->patient;
$hasMediclaim = (bool) ($counselling?->mediclaim ?? $booking->has_mediclaim);
$packageLabel = trim((string) ($counselling?->package_name ?? ''));
if ($packageLabel === '') {
    $packageLabel = trim((string) ($booking->ot_type ?? 'OT Package'));
    $lens = trim((string) ($counselling?->lens_option ?? $booking->lens_option ?? ''));
    if ($lens !== '') {
        $packageLabel .= ' + ' . $lens;
    }
}
$paymentStatus = $booking->payment_status;
$paymentStatusLabel = match ($paymentStatus) {
    'paid' => 'Paid',
    'partially_paid' => 'Partially Paid',
    'unpriced' => 'Package Not Set',
    default => 'Pending',
};
$paymentStatusClass = match ($paymentStatus) {
    'paid' => 'text-bg-success',
    'partially_paid' => 'text-bg-info',
    'unpriced' => 'text-bg-secondary',
    default => 'text-bg-warning',
};
$invoiceNumber = $invoice->invoice_number ?? null;
$autoReceiptNumber = $autoReceiptNumber ?? ('RCP-' . now()->format('Ym') . '-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT));
$defaultPaymentMode = $defaultPaymentMode ?? (((bool) ($counselling?->mediclaim ?? $booking->has_mediclaim)) ? 'mediclaim' : 'cash');
@endphp
    <div class="ot-payment-page">
        <div class="row justify-content-center">
            <div class="col-xl-11">
                <div class="ot-outer-card">
                    <div class="ot-header-block">
                        <div>
                            <div class="ot-header-title"><i class="bi bi-receipt"></i> Record OT Payment</div>
                            <nav class="ot-breadcrumb" aria-label="breadcrumb">
                                <a href="{{ route('hospital.dashboard', ['slug' => $slug]) }}">Home</a>
                                <span class="ot-breadcrumb-sep">/</span>
                                <a href="{{ route('hospital.ot.accountant.dashboard', ['slug' => $slug]) }}">Billing</a>
                                <span class="ot-breadcrumb-sep">/</span>
                                <span class="ot-breadcrumb-current">Booking #{{ $booking->id }}</span>
                            </nav>
                        </div>
                        <a href="{{ route('hospital.ot.accountant.dashboard', ['slug' => $slug]) }}" class="hms-btn hms-btn-outline">
                            <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
                        </a>
                    </div>
                </div>

                @if($errors->any())
                    <div class="alert alert-danger mb-4 ot-alert">
                        <ul class="mb-0 ps-3">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="card ot-premium-card border-0">
                    <div class="ot-card-header">
                        <div class="ot-title-wrap">
                            <span class="ot-title-icon" aria-hidden="true"><i class="bi bi-cash-coin" style="font-size: 1.1rem;"></i></span>
                            <h5 class="ot-title mb-0">Collect Payment</h5>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST"
                            action="{{ route('hospital.ot.payments.store', ['slug' => $slug, 'bookingId' => $booking->id]) }}">
                            @csrf

                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">UHID</label>
                                            <input type="text" class="form-control ot-readonly"
                                                value="{{ $patient?->patient_code ?? '-' }}" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">OT Package</label>
                                            <input type="text" class="form-control ot-readonly"
                                                value="{{ $packageLabel }}" readonly>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Total Amount</label>
                                            <div class="input-group">
                                                <span class="input-group-text">{{ currency_code() }}</span>
                                                <input type="text" class="form-control ot-readonly"
                                                    value="{{ number_format((float) $requiredTotal, 2) }}" readonly>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Payment Status</label>
                                            <input type="text" class="form-control ot-readonly"
                                                value="{{ $paymentStatusLabel }}" readonly>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Mediclaim</label>
                                            <input type="text" class="form-control ot-readonly"
                                                value="{{ $hasMediclaim ? 'YES' : 'NO' }}" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Invoice Number <span class="text-muted">(Auto)</span></label>
                                            <input type="text" class="form-control ot-readonly"
                                                value="{{ $invoiceNumber ?: '—' }}" readonly>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Amount Being Paid Now <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text">{{ currency_code() }}</span>
                                                <input type="number" step="0.01" min="0.01" name="package_amount"
                                                    value="{{ $defaultPackageAmount }}"
                                                    class="form-control ot-readonly" readonly
                                                    @if($defaultPackageAmount <= 0) disabled @endif>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Payment Mode <span class="text-danger">*</span></label>
                                            <select name="payment_mode" class="form-select" required
                                                @if($defaultPackageAmount <= 0) disabled @endif>
                                                <option value="">Select payment mode...</option>
                                                <option value="cash" {{ old('payment_mode', $defaultPaymentMode) === 'cash' ? 'selected' : '' }}>Cash</option>
                                                <option value="online" {{ old('payment_mode', $defaultPaymentMode) === 'online' ? 'selected' : '' }}>Online</option>
                                                <option value="mediclaim" {{ old('payment_mode', $defaultPaymentMode) === 'mediclaim' ? 'selected' : '' }}>Mediclaim</option>
                                            </select>
                                        </div>

                                        <div class="col-md-8">
                                            <label class="form-label">Receipt Number <span class="text-muted">(Auto)</span></label>
                                            <input type="text" name="receipt_number" id="receipt_number"
                                                value="{{ old('receipt_number', $autoReceiptNumber) }}" class="form-control"
                                                placeholder="RCP-YYYYMM-XXXX"
                                                @if($defaultPackageAmount <= 0) disabled @endif>
                                        </div>
                                        <div class="col-md-4 d-grid align-items-end">
                                            <button type="button" id="autoReceiptBtn" class="btn ot-btn-outline mt-md-4"
                                                @if($defaultPackageAmount <= 0) disabled @endif>
                                                <i class="bi bi-magic me-1"></i> Auto-generate
                                            </button>
                                        </div>
                                    </div>

                                    <div class="d-flex flex-column flex-sm-row justify-content-end gap-2 mt-4 pt-3 ot-form-actions">
                                        <a href="{{ route('hospital.ot.accountant.dashboard', ['slug' => $slug]) }}"
                                            class="hms-btn hms-btn-outline px-4">Cancel</a>
                                        <button type="submit" class="hms-btn hms-btn-primary ot-save-btn px-4"
                                            @if($defaultPackageAmount <= 0) disabled @endif>
                                            <i class="bi bi-check2-circle me-1"></i> Save Payment
                                        </button>
                                    </div>
                                </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('styles')
    <style>
        /*
          OT Record Payment — simple design matching the Walk-in register form
          (reception-patient-form.css). CSS-only; Blade/dynamic logic untouched.
        */

        .ot-payment-page {
            --oc-primary: #1B4F72;
            --oc-primary-dark: #154360;
            --oc-accent: #2980B9;
            --oc-success: #27AE60;
            --oc-border: rgba(27, 79, 114, 0.14);
            --oc-input-border: #d1dce6;
            --oc-muted: #64748B;
            --oc-focus: rgba(39, 174, 96, 0.22);
            --oc-soft: #EBF5FB;

            padding: .25rem 0 1.25rem;
            color: #1a2a3a;
        }

        /* ── Page header ─────────────────────────────────────────── */
        .ot-outer-card {
            background: #fff;
            border: 1px solid var(--oc-border);
            border-radius: 10px;
            box-shadow: 0 8px 28px rgba(27, 79, 114, 0.08);
            padding: .85rem 1.25rem;
            margin-bottom: 1rem;
        }

        .ot-header-block {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: .75rem;
        }

        .ot-header-title {
            display: flex;
            align-items: center;
            gap: .5rem;
            font-weight: 800;
            font-size: 1.15rem;
            color: var(--oc-primary);
        }

        .ot-breadcrumb {
            margin-top: .25rem;
            display: flex;
            align-items: center;
            gap: .4rem;
            font-size: .8rem;
            color: var(--oc-muted);
        }

        .ot-breadcrumb a {
            color: var(--oc-muted);
            text-decoration: none;
        }

        .ot-breadcrumb a:hover {
            color: var(--oc-primary);
        }

        .ot-breadcrumb-sep {
            color: #c3c9d3;
        }

        .ot-breadcrumb-current {
            color: var(--oc-primary);
            font-weight: 700;
        }

        .ot-payment-page .hms-btn-outline {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: .4rem .9rem;
            border: 1px solid var(--oc-border);
            border-radius: 6px;
            background: #fff;
            color: var(--oc-primary);
            font-size: .8rem;
            font-weight: 700;
        }

        .ot-payment-page .hms-btn-outline:hover {
            background: var(--oc-soft);
            border-color: var(--oc-primary);
            color: var(--oc-primary);
        }

        /* ── Card ────────────────────────────────────────────────── */
        .ot-premium-card {
            background: #fff;
            border: 1px solid var(--oc-border) !important;
            border-radius: 10px;
            box-shadow: 0 8px 28px rgba(27, 79, 114, 0.08);
            overflow: hidden;
        }

        .ot-premium-card > .card-body {
            padding: 1rem 1.25rem 1.25rem !important;
        }

        /* Gradient strip header — same as Walk-in "ADD PATIENT" title */
        .ot-card-header {
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, var(--oc-primary) 0%, var(--oc-accent) 100%);
            padding: .55rem 1rem;
        }

        .ot-title-wrap {
            display: flex;
            align-items: center;
            gap: .6rem;
            min-width: 0;
        }

        .ot-title-icon {
            width: 26px;
            height: 26px;
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.18);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
        }

        .ot-title-icon i {
            font-size: .9rem !important;
        }

        .ot-title {
            margin: 0;
            color: #fff;
            font-size: .95rem;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .ot-alert {
            border-radius: 8px;
            font-size: .85rem;
            font-weight: 600;
        }

        /* ── Fields — Walk-in form look ──────────────────────────── */
        .ot-payment-page .form-label {
            display: block;
            margin: 0 0 .35rem;
            font-size: .7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: var(--oc-muted);
            line-height: 1.2;
        }

        .ot-payment-page .form-label .text-muted {
            text-transform: none;
            letter-spacing: 0;
            font-weight: 600;
        }

        .ot-payment-page [class*="col-"]:focus-within > .form-label {
            color: var(--oc-primary);
        }

        .ot-payment-page .form-control,
        .ot-payment-page .form-select {
            min-height: 38px;
            padding: .35rem .65rem;
            font-size: .84rem;
            color: #1a2a3a;
            background-color: #fff;
            border: 1px solid var(--oc-input-border);
            border-radius: 6px;
            box-shadow: none;
        }

        .ot-payment-page .form-control:focus,
        .ot-payment-page .form-select:focus {
            border-color: var(--oc-success);
            box-shadow: 0 0 0 3px var(--oc-focus);
            outline: none;
        }

        /* Read-only values — same as the Walk-in MRD field */
        .ot-payment-page .ot-readonly {
            background: #eef2f6 !important;
            color: var(--oc-primary);
            font-weight: 700;
        }

        .ot-payment-page .input-group-text {
            min-height: 38px;
            padding: .35rem .6rem;
            font-size: .75rem;
            font-weight: 800;
            color: var(--oc-primary);
            background: var(--oc-soft);
            border: 1px solid var(--oc-input-border);
            border-radius: 6px 0 0 6px;
        }

        .ot-payment-page .input-group .form-control {
            border-radius: 0 6px 6px 0;
        }

        .ot-payment-page .select2-container--default .select2-selection--single {
            min-height: 38px !important;
            height: 38px !important;
            display: flex;
            align-items: center;
            padding: 0 .45rem;
            border: 1px solid var(--oc-input-border) !important;
            border-radius: 6px !important;
        }

        .ot-payment-page .select2-container--default .select2-selection--single .select2-selection__rendered {
            font-size: .84rem;
            line-height: 36px !important;
            padding-left: 0 !important;
            color: #1a2a3a !important;
        }

        .ot-payment-page .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px !important;
        }

        .ot-payment-page .select2-container--default.select2-container--focus .select2-selection--single,
        .ot-payment-page .select2-container--default.select2-container--open .select2-selection--single {
            border-color: var(--oc-success) !important;
            box-shadow: 0 0 0 3px var(--oc-focus) !important;
        }

        /* Auto-generate receipt button */
        .ot-payment-page .ot-btn-outline {
            min-height: 38px;
            padding: .35rem .9rem;
            border: 1px solid var(--oc-primary);
            border-radius: 6px;
            background: #fff;
            color: var(--oc-primary);
            font-size: .8rem;
            font-weight: 800;
        }

        .ot-payment-page .ot-btn-outline:hover {
            background: var(--oc-primary);
            color: #fff;
        }

        /* ── Actions ─────────────────────────────────────────────── */
        .ot-form-actions {
            margin-top: 1rem !important;
            padding-top: .85rem !important;
            border-top: 1px solid var(--oc-border);
        }

        .ot-payment-page .ot-save-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .35rem;
            min-width: 140px;
            padding: .45rem 1.25rem;
            border: 2px solid var(--oc-primary);
            border-radius: 6px;
            background: var(--oc-primary);
            color: #fff !important;
            font-size: .82rem;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase;
            transition: background .15s ease, border-color .15s ease, box-shadow .15s ease;
        }

        .ot-payment-page .ot-save-btn:hover {
            background: var(--oc-primary-dark);
            border-color: var(--oc-primary-dark);
        }

        .ot-payment-page .ot-save-btn:focus {
            background: var(--oc-success);
            border-color: #1e8449;
            box-shadow: 0 0 0 4px var(--oc-focus);
            outline: none;
        }

        .ot-payment-page .ot-save-btn:disabled,
        .ot-payment-page .ot-btn-outline:disabled {
            background: #eef2f6;
            border-color: var(--oc-input-border);
            color: var(--oc-muted) !important;
            cursor: not-allowed;
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const receiptInput = document.getElementById('receipt_number');
            const autoBtn = document.getElementById('autoReceiptBtn');
            if (!receiptInput || !autoBtn) return;

            function pad(n) {
                return String(n).padStart(2, '0');
            }

            autoBtn.addEventListener('click', function () {
                const now = new Date();
                const yyyy = now.getFullYear();
                const mm = pad(now.getMonth() + 1);
                const rand = String(Math.floor(Math.random() * 9000) + 1000);
                receiptInput.value = `RCP-${yyyy}${mm}-${rand}`;
            });
        });
    </script>
@endpush

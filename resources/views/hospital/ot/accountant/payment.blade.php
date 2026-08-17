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
                                        <button type="submit" class="hms-btn hms-btn-primary px-4" style="color: #1b4f72;"
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
          OT Record Payment (Design refresh)
          Keep Blade/dynamic logic untouched; CSS-only + layout wrappers.
          Palette follows hospital shell theme (#1B4F72).
        */

        .ot-payment-page {
            --ot-secondary: #1B4F72;
            --ot-s2-06: rgba(27, 79, 114, 0.06);
            --ot-s2-08: rgba(27, 79, 114, 0.08);
            --ot-s2-12: rgba(27, 79, 114, 0.12);
            --ot-s2-18: rgba(27, 79, 114, 0.18);
            --ot-s2-24: rgba(27, 79, 114, 0.24);

            position: relative;
            padding: .25rem 0 1.25rem;
            color: var(--ot-secondary);
            animation: ot-page-in 420ms ease both;
        }

        @keyframes ot-page-in {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .ot-payment-page .btn,
        .ot-payment-page .hms-btn {
            border-radius: 12px;
            font-weight: 800;
            transition: transform 170ms ease, box-shadow 170ms ease, background 170ms ease, border-color 170ms ease, color 170ms ease;
        }

        .ot-payment-page .btn:hover,
        .ot-payment-page .hms-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 26px rgba(27, 79, 114, 0.14);
        }

        .ot-outer-card {
            background: #ffffff;
            border: 1px solid rgba(15, 79, 134, 0.12);
            border-radius: 16px;
            box-shadow: 0 12px 32px rgba(15, 79, 134, 0.08);
            padding: 1.1rem 1.5rem;
            margin-bottom: 1.25rem;
        }

        .ot-header-block {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: .75rem;
        }

        .ot-header-title {
            font-weight: 800;
            font-size: 1.3rem;
            color: var(--ot-secondary);
            letter-spacing: -.015em;
            display: flex;
            align-items: center;
            gap: .55rem;
        }

        .ot-header-title i {
            color: var(--ot-secondary);
            font-size: 1.2rem;
        }

        .ot-breadcrumb {
            margin-top: .4rem;
            display: flex;
            align-items: center;
            gap: .4rem;
            font-size: .85rem;
            color: #8891a0;
        }

        .ot-breadcrumb a {
            color: #8891a0;
            text-decoration: none;
        }

        .ot-breadcrumb a:hover {
            color: var(--ot-secondary);
        }

        .ot-breadcrumb-sep {
            color: #c3c9d3;
        }

        .ot-breadcrumb-current {
            color: #4a5568;
            font-weight: 600;
        }

        .ot-premium-card {
            background: #ffffff;
            border: 1px solid rgba(15, 79, 134, 0.08) !important;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(15, 79, 134, 0.05);
            overflow: hidden;
        }

        .ot-card-header {
            background: #1b4f72;
            border-bottom: 1px solid var(--ot-s2-12);
            padding: 1.15rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .ot-title-wrap {
            display: flex;
            align-items: center;
            gap: .85rem;
            min-width: 0;
        }

        .ot-title-icon {
            width: 40px;
            height: 40px;
            border-radius: 15px;
            background: #ffffff;
            color: #1b4f72;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 14px 30px rgba(27, 79, 114, 0.22);
            flex: 0 0 auto;
        }

        .ot-title {
            font-weight: 900;
            letter-spacing: -0.2px;
            margin: 0;
            color: #ffffff;
        }

        .ot-subtitle {
            margin: .15rem 0 0;
            font-weight: 650;
            color: rgba(27, 79, 114, 0.72);
            font-size: .85rem;
        }

        .ot-alert { border-radius: 14px; font-weight: 650; }

        .ot-payment-page .form-label {
            font-weight: 800;
            font-size: .82rem;
            color: var(--ot-secondary);
            letter-spacing: .01em;
        }

        .ot-payment-page .form-control,
        .ot-payment-page .form-select {
            border: 1px solid var(--ot-s2-18);
            border-radius: 12px;
            padding: .55rem .85rem;
            background: rgba(255, 255, 255, 0.92);
            color: var(--ot-secondary);
            font-weight: 600;
            transition: border-color 160ms ease, box-shadow 160ms ease;
        }

        .ot-payment-page .form-control:focus,
        .ot-payment-page .form-select:focus {
            border-color: var(--ot-secondary);
            box-shadow: 0 0 0 .2rem var(--ot-s2-12);
        }

        .ot-payment-page .input-group-text {
            background: var(--ot-s2-08);
            border: 1px solid var(--ot-s2-18);
            border-radius: 12px 0 0 12px;
            color: var(--ot-secondary);
            font-weight: 800;
        }

        .ot-payment-page .ot-readonly {
            background: rgba(27, 79, 114, 0.05) !important;
            color: rgba(27, 79, 114, 0.65);
        }

        .ot-payment-page .form-text {
            color: rgba(27, 79, 114, 0.6);
            font-weight: 600;
            font-size: .78rem;
        }

        .ot-btn-outline {
            border: 1px solid var(--ot-s2-24);
            color: var(--ot-secondary);
            background: #ffffff;
            border-radius: 12px;
            font-weight: 800;
        }

        .ot-btn-outline:hover {
            background: var(--ot-secondary);
            color: #ffffff;
            border-color: var(--ot-secondary);
        }

        .ot-form-actions {
            border-top: 1px solid var(--ot-s2-12);
        }

        @media (prefers-reduced-motion: reduce) {
            .ot-payment-page,
            .ot-premium-card,
            .ot-payment-page .btn,
            .ot-payment-page .hms-btn {
                animation: none !important;
                transition: none !important;
            }
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

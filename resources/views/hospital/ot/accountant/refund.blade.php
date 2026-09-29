@extends('hospital.layouts.app')
@section('title', 'OT Full Refund')
{{-- Layout page-header intentionally unused — heading, breadcrumb and
actions render inside the content card instead, matching the panel design
used across the rest of the OT accountant module (see payment.blade.php). --}}

@section('content')
    @php
        $patient = $booking->patient;
    @endphp
    <div class="ot-refund-page">
        <div class="row justify-content-center">
            <div class="col-xl-11">
                <div class="ot-outer-card">
                    <div class="ot-header-block">
                        <div>
                            <div class="ot-header-title"><i class="bi bi-cash-stack"></i> OT Full Refund</div>
                            <nav class="ot-breadcrumb" aria-label="breadcrumb">
                                <a href="{{ route('hospital.dashboard', ['slug' => $slug]) }}">Home</a>
                                <span class="ot-breadcrumb-sep">/</span>
                                <a href="{{ route('hospital.ot.accountant.dashboard', ['slug' => $slug, 'filter' => 'refunds']) }}">Billing</a>
                                <span class="ot-breadcrumb-sep">/</span>
                                <span class="ot-breadcrumb-current">Booking #{{ $booking->id }}</span>
                            </nav>
                        </div>
                        <a href="{{ route('hospital.ot.accountant.dashboard', ['slug' => $slug, 'filter' => 'refunds']) }}"
                            class="hms-btn hms-btn-outline">
                            <i class="bi bi-arrow-left me-1"></i> Back to Refunds
                        </a>
                    </div>
                </div>

                @if(session('error'))
                    <div class="alert alert-danger mb-4 ot-alert">{{ session('error') }}</div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger mb-4 ot-alert">
                        <ul class="mb-0 ps-3">
                            @foreach($errors->all() as $e)
                                <li>{{ $e }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="card ot-premium-card border-0">
                    <div class="ot-card-header">
                        <div class="ot-title-wrap">
                            <span class="ot-title-icon" aria-hidden="true"><i class="bi bi-arrow-return-left" style="font-size: 1.1rem;"></i></span>
                            <h5 class="ot-title mb-0">Full Refund — Surgery Refused</h5>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label text-muted small">Patient</label>
                                <input type="text" class="form-control ot-readonly" readonly value="{{ $patient?->full_name ?? '-' }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted small">UHID</label>
                                <input type="text" class="form-control ot-readonly" readonly value="{{ $patient?->patient_code ?? '-' }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted small">Total Paid</label>
                                <input type="text" class="form-control ot-readonly" readonly
                                    value="{{ money_code($booking->total_paid, 2) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted small">Already Refunded</label>
                                <input type="text" class="form-control ot-readonly" readonly
                                    value="{{ money_code($booking->total_refunded, 2) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted small">Refund Amount (full)</label>
                                <input type="text" class="form-control fw-bold ot-refund-amount" readonly
                                    value="{{ money_code($refundAmount, 2) }}">
                            </div>
                        </div>

                        <form method="POST"
                            action="{{ route('hospital.ot.refunds.store', ['slug' => $slug, 'bookingId' => $booking->id]) }}">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Refund Mode <span class="text-danger">*</span></label>
                                    <select name="payment_mode" class="form-select" required>
                                        <option value="cash" @selected(old('payment_mode', 'cash') === 'cash')>Cash</option>
                                        <option value="online" @selected(old('payment_mode') === 'online')>Online</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Receipt Number <span class="text-muted">(Auto)</span></label>
                                    <input type="text" name="receipt_number" class="form-control"
                                        value="{{ old('receipt_number', $autoReceiptNumber) }}">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Reason</label>
                                    <input type="text" name="reason" class="form-control" maxlength="500"
                                        value="{{ old('reason', 'Patient refused OT — full refund') }}">
                                </div>
                            </div>
                            <div class="d-flex flex-column flex-sm-row justify-content-end gap-2 mt-4 pt-3 ot-form-actions">
                                <a href="{{ route('hospital.ot.accountant.dashboard', ['slug' => $slug, 'filter' => 'refunds']) }}"
                                    class="hms-btn hms-btn-outline px-4">Cancel</a>
                                <button type="submit" class="hms-btn hms-btn-primary px-4" style="color: #1b4f72;"
                                    onclick="return confirm('Confirm full refund of {{ number_format((float) $refundAmount, 2) }}?');">
                                    <i class="bi bi-check2-circle me-1"></i> Record Full Refund
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
          OT Full Refund (Design refresh)
          Keep Blade/dynamic logic untouched; CSS-only + layout wrappers.
          Palette follows hospital shell theme (#1B4F72) — mirrors payment.blade.php.
        */

        .ot-refund-page {
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

        .ot-refund-page .btn,
        .ot-refund-page .hms-btn {
            border-radius: 12px;
            font-weight: 800;
            transition: transform 170ms ease, box-shadow 170ms ease, background 170ms ease, border-color 170ms ease, color 170ms ease;
        }

        .ot-refund-page .btn:hover,
        .ot-refund-page .hms-btn:hover {
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

        .ot-alert { border-radius: 14px; font-weight: 650; }

        .ot-refund-page .form-label {
            font-weight: 800;
            font-size: .82rem;
            color: var(--ot-secondary);
            letter-spacing: .01em;
        }

        .ot-refund-page .form-control,
        .ot-refund-page .form-select {
            border: 1px solid var(--ot-s2-18);
            border-radius: 12px;
            padding: .55rem .85rem;
            background: rgba(255, 255, 255, 0.92);
            color: var(--ot-secondary);
            font-weight: 600;
            transition: border-color 160ms ease, box-shadow 160ms ease;
        }

        .ot-refund-page .form-control:focus,
        .ot-refund-page .form-select:focus {
            border-color: var(--ot-secondary);
            box-shadow: 0 0 0 .2rem var(--ot-s2-12);
        }

        .ot-refund-page .ot-readonly {
            background: rgba(27, 79, 114, 0.05) !important;
            color: rgba(27, 79, 114, 0.65);
        }

        .ot-refund-page .ot-refund-amount {
            background: rgba(39, 174, 96, 0.08) !important;
            color: #1e8449 !important;
            border-color: rgba(39, 174, 96, 0.35) !important;
        }

        .ot-form-actions {
            border-top: 1px solid var(--ot-s2-12);
        }

        @media (prefers-reduced-motion: reduce) {
            .ot-refund-page,
            .ot-premium-card,
            .ot-refund-page .btn,
            .ot-refund-page .hms-btn {
                animation: none !important;
                transition: none !important;
            }
        }
    </style>
@endpush

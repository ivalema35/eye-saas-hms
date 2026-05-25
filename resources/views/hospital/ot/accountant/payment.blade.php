@extends('hospital.layouts.app')
@section('title', 'Record OT Payment')
@section('page-header', 'Record OT Payment')

@section('page-actions')
    <a href="{{ route('hospital.ot.accountant.dashboard', ['slug' => $slug]) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
    </a>
@endsection

@section('content')
<style>
    .ot-basic-card {
        border-color: rgba(27, 79, 114, 0.12);
        border-radius: 14px;
    }

    .ot-section-title {
        color: #1b4f72;
        font-weight: 700;
    }

    .ot-summary-box {
        background: #f8fbfe;
        border: 1px solid rgba(27, 79, 114, 0.08);
        border-radius: 12px;
        padding: .85rem 1rem;
        margin-bottom: .75rem;
    }

    .ot-summary-label {
        font-size: .78rem;
        color: rgba(27, 79, 114, 0.7);
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: .2rem;
    }

    .ot-summary-value {
        color: #173d57;
        font-weight: 600;
    }

    .form-control,
    .form-select,
    .input-group-text,
    .btn {
        border-radius: 10px;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: rgba(27, 79, 114, 0.45);
        box-shadow: 0 0 0 .2rem rgba(27, 79, 114, 0.08);
    }

    .input-group-text {
        background: #eef6fb;
        border-color: rgba(27, 79, 114, 0.16);
        color: #1b4f72;
    }

    .btn-theme-primary {
        background: #1b4f72;
        border-color: #1b4f72;
    }

    .btn-theme-primary:hover {
        background: #15405d;
        border-color: #15405d;
    }

    .btn-theme-outline {
        border-color: rgba(27, 79, 114, 0.24);
        color: #1b4f72;
        background: #ffffff;
    }

    .btn-theme-outline:hover {
        background: #1b4f72;
        color: #ffffff;
        border-color: #1b4f72;
    }
</style>

<div class="container-fluid px-0 px-lg-3 py-2">
    <div class="row justify-content-center">
        <div class="col-xl-10">
            <div class="card ot-basic-card shadow-sm mb-4">
                <div class="card-body d-flex flex-column flex-md-row justify-content-between gap-2 align-items-md-center">
                    <div>
                        <h4 class="mb-1 ot-section-title">
                            <i class="bi bi-receipt me-2"></i> Record OT Payment
                        </h4>
                        <div class="text-muted">Basic form to save payment details for this booking.</div>
                    </div>
                    <div class="text-md-end text-muted small">
                        Booking #{{ $booking->id }}<br>
                        {{ optional($booking->surgery_date)->format('d M Y') }}
                    </div>
                </div>
            </div>

            @if($errors->any())
                <div class="alert alert-danger mb-4">
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card ot-basic-card shadow-sm h-100">
                        <div class="card-header bg-white">
                            <strong class="ot-section-title">Patient Details</strong>
                        </div>
                        <div class="card-body">
                            <div class="ot-summary-box">
                                <div class="ot-summary-label">Patient Name</div>
                                <div class="ot-summary-value">{{ $booking->patient?->full_name ?? '-' }}</div>
                            </div>
                            <div class="ot-summary-box">
                                <div class="ot-summary-label">Phone</div>
                                <div class="ot-summary-value">{{ $booking->patient?->contact_no ?? '-' }}</div>
                            </div>
                            <div class="ot-summary-box">
                                <div class="ot-summary-label">OT Date</div>
                                <div class="ot-summary-value">{{ optional($booking->surgery_date)->format('d M Y') }}</div>
                            </div>
                            <div class="ot-summary-box mb-0">
                                <div class="ot-summary-label">Mediclaim</div>
                                <div class="ot-summary-value">{{ ($counselling?->mediclaim ?? $booking->has_mediclaim) ? 'YES' : 'NO' }}</div>
                            </div>
                            <div class="mt-3 small text-muted">
                                Default amount: INR {{ number_format((float) $defaultPackageAmount, 2) }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card ot-basic-card shadow-sm">
                        <div class="card-header bg-white">
                            <strong class="ot-section-title">Payment Form</strong>
                        </div>
                        <div class="card-body p-4">
                            <form method="POST" action="{{ route('hospital.ot.payments.store', ['slug' => $slug, 'bookingId' => $booking->id]) }}">
                                @csrf

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Package Amount <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text">INR</span>
                                            <input type="number" step="0.01" min="0" name="package_amount"
                                                   value="{{ old('package_amount', $defaultPackageAmount) }}"
                                                   class="form-control" required>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Payment Mode <span class="text-danger">*</span></label>
                                        <select name="payment_mode" class="form-select" required>
                                            <option value="">Select payment mode...</option>
                                            <option value="cash" {{ old('payment_mode') === 'cash' ? 'selected' : '' }}>Cash</option>
                                            <option value="online" {{ old('payment_mode') === 'online' ? 'selected' : '' }}>Online</option>
                                            <option value="mediclaim" {{ old('payment_mode') === 'mediclaim' ? 'selected' : '' }}>Mediclaim</option>
                                        </select>
                                    </div>

                                    <div class="col-md-8">
                                        <label class="form-label">Receipt Number</label>
                                        <input type="text" name="receipt_number" id="receipt_number"
                                               value="{{ old('receipt_number') }}"
                                               class="form-control" placeholder="RCP-YYYYMM-XXXX">
                                    </div>
                                    <div class="col-md-4 d-grid align-items-end">
                                        <button type="button" id="autoReceiptBtn" class="btn btn-theme-outline mt-md-4">
                                            <i class="bi bi-magic me-1"></i> Auto-generate
                                        </button>
                                    </div>
                                </div>

                                <div class="d-flex flex-column flex-sm-row justify-content-end gap-2 mt-4 pt-3 border-top">
                                    <a href="{{ route('hospital.ot.accountant.dashboard', ['slug' => $slug]) }}" class="btn btn-outline-secondary px-4">Cancel</a>
                                    <button type="submit" class="btn btn-theme-primary text-white px-4">
                                        <i class="bi bi-check2-circle me-1"></i> Save Payment
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const receiptInput = document.getElementById('receipt_number');
    const autoBtn = document.getElementById('autoReceiptBtn');

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
